<?php

namespace App\Services\Olt;

use App\Models\Olt;
use Illuminate\Support\Facades\Log;

class HisoOltDriver implements OltDriverInterface
{
    protected ?TelnetConnection $telnet = null;
    protected ?SnmpConnection $snmp = null;
    protected ?HisoWebClient $web = null;
    protected bool $telnetConnected = false;
    protected bool $webConnected = false;

    /**
     * CTC Standard EPON MIB OIDs (enterprise 17409)
     * Used by HIOSO, C-Data, and other CTC-compliant vendors
     */
    const OID_CTC_ONU_STATUS       = '1.3.6.1.4.1.17409.2.3.4.1.1.2';  // llidIfAdminStatus
    const OID_CTC_ONU_OPER_STATUS  = '1.3.6.1.4.1.17409.2.3.4.1.1.3';  // llidIfOperStatus
    const OID_CTC_ONU_RX_POWER    = '1.3.6.1.4.1.17409.2.3.4.1.3.1.5'; // onuPonOpticsRxPower
    const OID_CTC_ONU_TX_POWER    = '1.3.6.1.4.1.17409.2.3.4.1.3.1.4'; // onuPonOpticsTxPower
    const OID_CTC_ONU_TEMPERATURE = '1.3.6.1.4.1.17409.2.3.4.1.3.1.1'; // onuPonOpticsTemperature
    const OID_CTC_ONU_VOLTAGE     = '1.3.6.1.4.1.17409.2.3.4.1.3.1.2'; // onuPonOpticsVoltage

    /**
     * BDCOM-style OIDs (enterprise 3320)
     * Fallback for some EPON OLTs that use BDCOM MIBs
     */
    const OID_BDCOM_ONU_RX_POWER  = '1.3.6.1.4.1.3320.101.10.5.1.5';
    const OID_BDCOM_ONU_TX_POWER  = '1.3.6.1.4.1.3320.101.10.5.1.6';

    /**
     * Standard IF-MIB OIDs
     */
    const OID_IF_DESCR   = '1.3.6.1.2.1.2.2.1.2';   // ifDescr
    const OID_IF_OPER    = '1.3.6.1.2.1.2.2.1.8';    // ifOperStatus
    const OID_SYS_DESCR  = '1.3.6.1.2.1.1.1.0';      // sysDescr

    public function __construct(protected Olt $olt) {}

    // ─────────────────────────────────────────
    // Connection Management
    // ─────────────────────────────────────────

    public function connect(): bool
    {
        // Try Web interface first (most complete data for HIOSO)
        $this->initWeb();

        // Try SNMP
        $this->initSnmp();

        // Try Telnet last
        $this->connectTelnet();

        return $this->webConnected || $this->telnetConnected || $this->snmp !== null;
    }

    protected string $webMessage = '';

    protected function initWeb(): void
    {
        $this->web = new HisoWebClient($this->olt);
        $result = $this->web->testConnection();
        $this->webConnected = $result['connected'];
        $this->webMessage = $result['message'] ?? '';

        if (!$this->webConnected) {
            Log::info("Web interface not available for OLT {$this->olt->name}: {$this->webMessage}");
            $this->web = null;
        }
    }

    public function isWebConnected(): bool
    {
        return $this->webConnected;
    }

    public function getWebMessage(): string
    {
        return $this->webMessage;
    }

    protected function initSnmp(): void
    {
        if (!SnmpConnection::isAvailable()) {
            Log::info("PHP SNMP extension not available, skipping SNMP for OLT {$this->olt->name}");
            return;
        }

        $community = $this->olt->snmp_community;
        if (empty($community)) {
            return;
        }

        $this->snmp = new SnmpConnection(
            $this->olt->host,
            $community,
            $this->olt->snmp_port ?: 161
        );

        if (!$this->snmp->testConnection()) {
            Log::info("SNMP connection failed for OLT {$this->olt->name}");
            $this->snmp = null;
        }
    }

    protected function connectTelnet(): void
    {
        try {
            $this->telnet = new TelnetConnection(
                $this->olt->host,
                $this->olt->telnet_port ?: 23,
                10
            );

            $this->telnetConnected = $this->telnet->connect(
                $this->olt->username,
                $this->olt->password,
                '#'
            );
        } catch (\Throwable $e) {
            Log::info("Telnet connection failed for OLT {$this->olt->name}: {$e->getMessage()}");
            $this->telnetConnected = false;
        }
    }

    public function isTelnetConnected(): bool
    {
        return $this->telnetConnected;
    }

    public function disconnect(): void
    {
        $this->telnet?->disconnect();
        $this->telnet = null;
        $this->snmp = null;
        $this->web = null;
        $this->telnetConnected = false;
        $this->webConnected = false;
    }

    protected function execute(string $command): string
    {
        if (!$this->telnetConnected) {
            $this->connectTelnet();
        }

        return $this->telnet?->exec($command) ?? '';
    }

    public function hasSnmp(): bool
    {
        return $this->snmp !== null;
    }

    // ─────────────────────────────────────────
    // SNMP Query Methods
    // ─────────────────────────────────────────

    /**
     * Get optical power via SNMP for a specific ONU
     * EPON ifIndex format: (slot+1)*10000000 + port*100000 + onuId
     * or simpler: portIfIndex.onuId depending on vendor
     */
    public function getOpticalPowerSnmp(int $slot, int $port, int $ontId): array
    {
        if (!$this->snmp) return [];

        $data = [];

        // Try multiple ifIndex calculations (varies per firmware version)
        $ifIndexes = $this->calculateIfIndexes($slot, $port, $ontId);

        foreach ($ifIndexes as $ifIndex) {
            // Try CTC standard OIDs first
            $rx = $this->snmp->get(self::OID_CTC_ONU_RX_POWER . ".{$ifIndex}");
            $tx = $this->snmp->get(self::OID_CTC_ONU_TX_POWER . ".{$ifIndex}");

            if ($rx !== false && $tx !== false) {
                $data['rx_power'] = $this->convertSnmpPower($rx);
                $data['tx_power'] = $this->convertSnmpPower($tx);

                // Get temperature and voltage too
                $temp = $this->snmp->get(self::OID_CTC_ONU_TEMPERATURE . ".{$ifIndex}");
                $volt = $this->snmp->get(self::OID_CTC_ONU_VOLTAGE . ".{$ifIndex}");
                if ($temp !== false) $data['temperature'] = round((float) $temp / 256, 1);
                if ($volt !== false) $data['voltage'] = round((float) $volt / 10000, 2);

                return $data;
            }
        }

        // Fallback: try BDCOM-style OIDs
        foreach ($ifIndexes as $ifIndex) {
            $rx = $this->snmp->get(self::OID_BDCOM_ONU_RX_POWER . ".{$ifIndex}");
            $tx = $this->snmp->get(self::OID_BDCOM_ONU_TX_POWER . ".{$ifIndex}");

            if ($rx !== false && $tx !== false) {
                $data['rx_power'] = $this->convertSnmpPower($rx);
                $data['tx_power'] = $this->convertSnmpPower($tx);
                return $data;
            }
        }

        return $data;
    }

    /**
     * Get all ONU optical power on a port via SNMP
     */
    public function getAllOpticalPowerSnmp(int $slot, int $port): array
    {
        if (!$this->snmp) return [];

        $results = [];

        // Walk CTC Rx Power OID
        $rxWalk = $this->snmp->walk(self::OID_CTC_ONU_RX_POWER);
        $txWalk = $this->snmp->walk(self::OID_CTC_ONU_TX_POWER);

        if (!empty($rxWalk)) {
            foreach ($rxWalk as $index => $rxValue) {
                $onuId = $this->extractOnuIdFromIndex($index, $slot, $port);
                if ($onuId !== null) {
                    $results[$onuId]['rx_power'] = $this->convertSnmpPower($rxValue);
                }
            }
            foreach ($txWalk as $index => $txValue) {
                $onuId = $this->extractOnuIdFromIndex($index, $slot, $port);
                if ($onuId !== null) {
                    $results[$onuId]['tx_power'] = $this->convertSnmpPower($txValue);
                }
            }
            if (!empty($results)) return $results;
        }

        // Fallback: BDCOM-style walk
        $rxWalk = $this->snmp->walk(self::OID_BDCOM_ONU_RX_POWER);
        $txWalk = $this->snmp->walk(self::OID_BDCOM_ONU_TX_POWER);

        foreach ($rxWalk as $index => $rxValue) {
            $onuId = $this->extractOnuIdFromIndex($index, $slot, $port);
            if ($onuId !== null) {
                $results[$onuId]['rx_power'] = $this->convertSnmpPower($rxValue);
            }
        }
        foreach ($txWalk as $index => $txValue) {
            $onuId = $this->extractOnuIdFromIndex($index, $slot, $port);
            if ($onuId !== null) {
                $results[$onuId]['tx_power'] = $this->convertSnmpPower($txValue);
            }
        }

        return $results;
    }

    /**
     * Get ONU status via SNMP
     */
    public function getOntStatusSnmp(int $slot, int $port, int $ontId): array
    {
        if (!$this->snmp) return [];

        $data = [];
        $ifIndexes = $this->calculateIfIndexes($slot, $port, $ontId);

        foreach ($ifIndexes as $ifIndex) {
            $status = $this->snmp->get(self::OID_CTC_ONU_OPER_STATUS . ".{$ifIndex}");
            if ($status !== false) {
                $data['status'] = ((int) $status === 1) ? 'online' : 'offline';
                return $data;
            }

            // Try standard ifOperStatus
            $status = $this->snmp->get(self::OID_IF_OPER . ".{$ifIndex}");
            if ($status !== false) {
                $data['status'] = ((int) $status === 1) ? 'online' : 'offline';
                return $data;
            }
        }

        return $data;
    }

    /**
     * Test SNMP connection and return device info
     */
    public function testSnmpConnection(): array
    {
        if (!$this->snmp) {
            return ['connected' => false, 'reason' => 'SNMP not configured or PHP SNMP extension not available'];
        }

        $sysDescr = $this->snmp->getSysDescr();
        if (empty($sysDescr)) {
            return ['connected' => false, 'reason' => 'SNMP connection failed'];
        }

        return [
            'connected' => true,
            'sys_descr' => $sysDescr,
        ];
    }

    /**
     * Calculate possible ifIndex values for an ONU
     * Different HIOSO firmware versions use different index schemes
     */
    protected function calculateIfIndexes(int $slot, int $port, int $onuId): array
    {
        return [
            // Format 1: (slot+1)*10000000 + port*100000 + onuId (common HIOSO)
            ($slot + 1) * 10000000 + $port * 100000 + $onuId,
            // Format 2: port*10000 + onuId (simpler)
            $port * 10000 + $onuId,
            // Format 3: slot*1000000 + port*10000 + onuId
            $slot * 1000000 + $port * 10000 + $onuId,
            // Format 4: direct compound index port.onuId
            (int) "{$port}{$onuId}",
        ];
    }

    /**
     * Extract ONU ID from SNMP walk index based on port
     */
    protected function extractOnuIdFromIndex(string $index, int $slot, int $port): ?int
    {
        $index = (int) $index;

        // Try Format 1
        $base1 = ($slot + 1) * 10000000 + $port * 100000;
        if ($index > $base1 && $index < $base1 + 1000) {
            return $index - $base1;
        }

        // Try Format 2
        $base2 = $port * 10000;
        if ($index > $base2 && $index < $base2 + 1000) {
            return $index - $base2;
        }

        // Try Format 3
        $base3 = $slot * 1000000 + $port * 10000;
        if ($index > $base3 && $index < $base3 + 1000) {
            return $index - $base3;
        }

        // If index is small number, it might be the ONU ID directly
        if ($index > 0 && $index < 256) {
            return $index;
        }

        return null;
    }

    /**
     * Convert SNMP power value to dBm
     * SNMP typically returns in units of 0.01 dBm or 0.1 dBm
     */
    protected function convertSnmpPower(string $value): float
    {
        $numValue = (float) $value;

        // If value is very large (e.g., > 1000), it's likely in 0.01 dBm units
        if (abs($numValue) > 1000) {
            return round($numValue / 100, 2);
        }

        // If value is moderate (e.g., > 100), it's likely in 0.1 dBm units
        if (abs($numValue) > 100) {
            return round($numValue / 10, 2);
        }

        // Already in dBm
        return round($numValue, 2);
    }

    /**
     * Discover all ONUs via SNMP walk on ifDescr
     * Returns array of discovered ONUs with their interface info
     */
    public function discoverOnuSnmp(): array
    {
        if (!$this->snmp) return [];

        $onus = [];

        // Walk ifDescr to find EPON ONU interfaces
        $ifDescrs = $this->snmp->walk(self::OID_IF_DESCR);
        $ifOpers = $this->snmp->walk(self::OID_IF_OPER);

        foreach ($ifDescrs as $ifIndex => $descr) {
            // Match EPON ONU interface names like "EPON0/0/1:1", "epon0/1:2", "EPON 0/0/1:3"
            if (preg_match('/[Ee][Pp][Oo][Nn]\s*(\d+)\/(?:(\d+)\/)?(\d+):(\d+)/', $descr, $m)) {
                $slot = isset($m[2]) && $m[2] !== '' ? (int)$m[2] : (int)$m[1];
                $port = (int)$m[3];
                $onuId = (int)$m[4];

                // If format is epon0/1:1 (no middle number), slot=0, port=1
                if (!isset($m[2]) || $m[2] === '') {
                    $slot = 0;
                    $port = (int)$m[3];
                }

                $status = isset($ifOpers[$ifIndex]) ? ((int)$ifOpers[$ifIndex] === 1 ? 'online' : 'offline') : 'unknown';

                $onus[] = [
                    'if_index' => (int)$ifIndex,
                    'if_descr' => $descr,
                    'slot' => $slot,
                    'port' => $port,
                    'onu_id' => $onuId,
                    'status' => $status,
                ];
            }
        }

        return $onus;
    }

    /**
     * SNMP walk raw - for debugging/discovery
     */
    public function snmpWalkRaw(string $oid): array
    {
        if (!$this->snmp) return [];
        return $this->snmp->walk($oid);
    }

    // ─────────────────────────────────────────
    // Main Interface Methods (SNMP → Telnet fallback)
    // ─────────────────────────────────────────

    /**
     * Get all ONUs on a specific EPON port
     * Priority: Web > SNMP > Telnet
     */
    public function getOntList(int $slot, int $port): array
    {
        // Try Web interface first (most complete data)
        if ($this->web) {
            $onus = $this->web->getOnuList($slot, $port);
            if (!empty($onus)) {
                return $onus;
            }
        }

        // Try SNMP discovery
        if ($this->snmp) {
            $allOnus = $this->discoverOnuSnmp();
            $filtered = array_filter($allOnus, fn($o) => $o['slot'] === $slot && $o['port'] === $port);
            if (!empty($filtered)) {
                return array_values(array_map(fn($o) => [
                    'ont_id' => $o['onu_id'],
                    'status' => $o['status'],
                    'if_index' => $o['if_index'],
                ], $filtered));
            }
        }

        // Fallback to Telnet
        if ($this->telnetConnected) {
            $output = $this->execute("show epon onu-information interface epon 0/{$slot}/{$port}");
            return $this->parseOnuList($output);
        }

        return [];
    }

    /**
     * Get ONU status
     */
    public function getOntStatus(int $slot, int $port, int $ontId): array
    {
        // Web interface
        if ($this->web) {
            $onus = $this->web->getOnuList($slot, $port);
            foreach ($onus as $onu) {
                if ($onu['onu_id'] === $ontId) {
                    return ['status' => $onu['status'], 'mac' => $onu['mac'] ?? null, 'distance' => $onu['distance'] ?? null];
                }
            }
        }

        // SNMP
        $data = $this->getOntStatusSnmp($slot, $port, $ontId);
        if (!empty($data)) return $data;

        // Telnet
        if ($this->telnetConnected) {
            $output = $this->execute("show epon onu-information interface epon 0/{$slot}/{$port} onu {$ontId}");
            return $this->parseOnuStatus($output);
        }

        return [];
    }

    /**
     * Get optical power for a specific ONU
     * Priority: Web > SNMP > Telnet
     */
    public function getOpticalPower(int $slot, int $port, int $ontId): array
    {
        // Web interface (has all data including temperature, voltage)
        if ($this->web) {
            $onus = $this->web->getOnuList($slot, $port);
            foreach ($onus as $onu) {
                if ($onu['onu_id'] === $ontId) {
                    Log::debug("Got optical power via Web for ONU {$ontId} on port {$slot}/{$port}");
                    return [
                        'rx_power' => $onu['rx_power'],
                        'tx_power' => $onu['tx_power'],
                        'temperature' => $onu['temperature'] ?? null,
                        'voltage' => $onu['voltage'] ?? null,
                    ];
                }
            }
        }

        // SNMP
        $data = $this->getOpticalPowerSnmp($slot, $port, $ontId);
        if (!empty($data)) {
            Log::debug("Got optical power via SNMP for ONU {$ontId} on port {$slot}/{$port}");
            return $data;
        }

        // Telnet
        if ($this->telnetConnected) {
            Log::debug("Falling back to Telnet for ONU {$ontId} on port {$slot}/{$port}");
            $output = $this->execute("show epon onu-optical-transceiver-diagnosis interface epon 0/{$slot}/{$port} onu {$ontId}");
            return $this->parseOpticalPower($output);
        }

        return [];
    }

    /**
     * Get all optical power for all ONUs on a port
     * Priority: Web > SNMP > Telnet
     */
    public function getAllOpticalPower(int $slot, int $port): array
    {
        // Web interface (returns all data at once)
        if ($this->web) {
            $onus = $this->web->getOnuList($slot, $port);
            if (!empty($onus)) {
                $results = [];
                foreach ($onus as $onu) {
                    $results[$onu['onu_id']] = [
                        'rx_power' => $onu['rx_power'],
                        'tx_power' => $onu['tx_power'],
                        'temperature' => $onu['temperature'] ?? null,
                        'voltage' => $onu['voltage'] ?? null,
                        'name' => $onu['name'] ?? null,
                        'mac' => $onu['mac'] ?? null,
                        'status' => $onu['status'] ?? null,
                        'distance' => $onu['distance'] ?? null,
                    ];
                }
                Log::debug("Got bulk optical power via Web for port {$slot}/{$port}: " . count($results) . " ONUs");
                return $results;
            }
        }

        // SNMP walk
        $data = $this->getAllOpticalPowerSnmp($slot, $port);
        if (!empty($data)) {
            Log::debug("Got bulk optical power via SNMP for port {$slot}/{$port}");
            return $data;
        }

        // Telnet
        if ($this->telnetConnected) {
            $output = $this->execute("show epon onu-optical-transceiver-diagnosis interface epon 0/{$slot}/{$port}");
            return $this->parseAllOpticalPower($output);
        }

        return [];
    }

    /**
     * Get unregistered/unauthorized ONUs
     */
    public function getUnregisteredOnts(): array
    {
        $onts = [];

        $ponPorts = $this->olt->ponPorts()->where('is_active', true)->get();
        foreach ($ponPorts as $ponPort) {
            $output = $this->execute("show epon onu-information interface epon 0/{$ponPort->slot}/{$ponPort->port}");
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (preg_match('/(\d+)\s+([\da-fA-F.:]+)\s+.*?(unauth|deregist)/i', $line, $m)) {
                    $onts[] = [
                        'slot' => $ponPort->slot,
                        'port' => $ponPort->port,
                        'ont_id' => (int) $m[1],
                        'serial' => $m[2],
                        'status' => $m[3],
                    ];
                }
            }
        }

        return $onts;
    }

    /**
     * Register/bind ONU on HIOSO EPON (Telnet only)
     */
    public function registerOnt(int $slot, int $port, int $ontId, string $serialNumber, string $lineProfile, string $serviceProfile): bool
    {
        $commands = [
            "configure terminal",
            "interface epon 0/{$slot}/{$port}",
            "epon bind-onu mac {$serialNumber} sequence {$ontId}",
            "epon onu-profile {$lineProfile} sequence {$ontId}",
            "exit",
            "exit",
            "write memory",
        ];

        $output = '';
        foreach ($commands as $cmd) {
            $output .= $this->execute($cmd);
        }

        return !str_contains($output, 'Error') && !str_contains($output, 'Invalid') && !str_contains($output, 'fail');
    }

    /**
     * Deregister/unbind ONU from HIOSO EPON (Telnet only)
     */
    public function deregisterOnt(int $slot, int $port, int $ontId): bool
    {
        $commands = [
            "configure terminal",
            "interface epon 0/{$slot}/{$port}",
            "no epon bind-onu sequence {$ontId}",
            "exit",
            "exit",
            "write memory",
        ];

        $output = '';
        foreach ($commands as $cmd) {
            $output .= $this->execute($cmd);
        }

        return !str_contains($output, 'Error') && !str_contains($output, 'Invalid') && !str_contains($output, 'fail');
    }

    // ─────────────────────────────────────────
    // Telnet Output Parsers
    // ─────────────────────────────────────────

    protected function parseOnuList(string $output): array
    {
        $onts = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^\s*(\d+)\s+([\da-fA-F]{2}[:\-.][\da-fA-F]{2}[:\-.][\da-fA-F]{2}[:\-.][\da-fA-F]{2}[:\-.][\da-fA-F]{2}[:\-.][\da-fA-F]{2})\s+(\S+)/i', $line, $m)) {
                $onts[] = [
                    'ont_id' => (int) $m[1],
                    'mac' => strtolower(str_replace(['-', '.'], ':', $m[2])),
                    'status' => strtolower($m[3]),
                ];
            }
        }

        return $onts;
    }

    protected function parseOnuStatus(string $output): array
    {
        $data = [];

        if (preg_match('/MAC\s*(?:Address)?\s*[:=]\s*([\da-fA-F:.\-]+)/i', $output, $m)) {
            $data['mac'] = strtolower(str_replace(['-', '.'], ':', $m[1]));
        }
        if (preg_match('/Status\s*[:=]\s*(\S+)/i', $output, $m)) {
            $data['status'] = strtolower($m[1]);
        }
        if (preg_match('/(?:Auth|Register)\s*[:=]\s*(\S+)/i', $output, $m)) {
            $data['auth'] = strtolower($m[1]);
        }
        if (preg_match('/Distance\s*[:=]\s*([\d.]+)/i', $output, $m)) {
            $data['distance'] = $m[1];
        }

        return $data;
    }

    protected function parseOpticalPower(string $output): array
    {
        $data = [];

        if (preg_match('/Rx\s*(?:Optical\s*)?Power\s*[:=]\s*([-\d.]+)\s*(?:dBm)?/i', $output, $m)) {
            $data['rx_power'] = (float) $m[1];
        }
        if (preg_match('/Tx\s*(?:Optical\s*)?Power\s*[:=]\s*([-\d.]+)\s*(?:dBm)?/i', $output, $m)) {
            $data['tx_power'] = (float) $m[1];
        }
        if (preg_match('/Temperature\s*[:=]\s*([-\d.]+)/i', $output, $m)) {
            $data['temperature'] = (float) $m[1];
        }
        if (preg_match('/Voltage\s*[:=]\s*([-\d.]+)/i', $output, $m)) {
            $data['voltage'] = (float) $m[1];
        }

        return $data;
    }

    protected function parseAllOpticalPower(string $output): array
    {
        $results = [];
        $lines = explode("\n", $output);
        $currentOnuId = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/(?:ONU|onu.id)\s*[:=]?\s*(\d+)/i', $line, $m)) {
                $currentOnuId = (int) $m[1];
                if (!isset($results[$currentOnuId])) {
                    $results[$currentOnuId] = [];
                }
            }

            if (preg_match('/^\s*(\d+)\s+[\da-fA-F:.\-]+\s+([-\d.]+)\s+([-\d.]+)/i', $line, $m)) {
                $onuId = (int) $m[1];
                $results[$onuId] = [
                    'rx_power' => (float) $m[2],
                    'tx_power' => (float) $m[3],
                ];
            }

            if ($currentOnuId !== null) {
                if (preg_match('/Rx\s*(?:Optical\s*)?Power\s*[:=]\s*([-\d.]+)/i', $line, $m)) {
                    $results[$currentOnuId]['rx_power'] = (float) $m[1];
                }
                if (preg_match('/Tx\s*(?:Optical\s*)?Power\s*[:=]\s*([-\d.]+)/i', $line, $m)) {
                    $results[$currentOnuId]['tx_power'] = (float) $m[1];
                }
            }
        }

        return $results;
    }
}
