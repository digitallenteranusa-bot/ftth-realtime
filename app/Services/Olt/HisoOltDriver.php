<?php

namespace App\Services\Olt;

use App\Models\Olt;

class HisoOltDriver implements OltDriverInterface
{
    protected ?TelnetConnection $connection = null;

    public function __construct(protected Olt $olt) {}

    public function connect(): bool
    {
        try {
            $this->connection = new TelnetConnection(
                $this->olt->host,
                $this->olt->telnet_port ?: 23,
                15
            );

            return $this->connection->connect(
                $this->olt->username,
                $this->olt->password,
                '#'
            );
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function disconnect(): void
    {
        $this->connection?->disconnect();
        $this->connection = null;
    }

    protected function execute(string $command): string
    {
        if (!$this->connection || !$this->connection->isConnected()) {
            $this->connect();
        }

        return $this->connection?->exec($command) ?? '';
    }

    /**
     * Get all ONUs on a specific EPON port
     * Command: show epon onu-information interface epon 0/{slot}/{port}
     */
    public function getOntList(int $slot, int $port): array
    {
        $output = $this->execute("show epon onu-information interface epon 0/{$slot}/{$port}");
        return $this->parseOnuList($output);
    }

    /**
     * Get ONU status details
     * Command: show epon onu-information interface epon 0/{slot}/{port} onu {ontId}
     */
    public function getOntStatus(int $slot, int $port, int $ontId): array
    {
        $output = $this->execute("show epon onu-information interface epon 0/{$slot}/{$port} onu {$ontId}");
        return $this->parseOnuStatus($output);
    }

    /**
     * Get optical power (Rx/Tx) for a specific ONU
     * Command: show epon onu-optical-transceiver-diagnosis interface epon 0/{slot}/{port} onu {ontId}
     */
    public function getOpticalPower(int $slot, int $port, int $ontId): array
    {
        $output = $this->execute("show epon onu-optical-transceiver-diagnosis interface epon 0/{$slot}/{$port} onu {$ontId}");
        return $this->parseOpticalPower($output);
    }

    /**
     * Get all optical power for all ONUs on a port
     */
    public function getAllOpticalPower(int $slot, int $port): array
    {
        $output = $this->execute("show epon onu-optical-transceiver-diagnosis interface epon 0/{$slot}/{$port}");
        return $this->parseAllOpticalPower($output);
    }

    /**
     * Get unregistered/unauthorized ONUs
     */
    public function getUnregisteredOnts(): array
    {
        $onts = [];

        // Query all EPON ports for unauthorized ONUs
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
     * Register/bind ONU on HIOSO EPON
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
     * Deregister/unbind ONU from HIOSO EPON
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

    /**
     * Parse ONU list from "show epon onu-information" output
     *
     * Typical HIOSO output format:
     * ONU  MAC Address       Status    Auth    Rx Power  Tx Power
     * 1    aa:bb:cc:dd:ee:ff  online   auth    -18.50    2.30
     */
    protected function parseOnuList(string $output): array
    {
        $onts = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            // Match: ONU_ID  MAC_ADDRESS  STATUS
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

    /**
     * Parse single ONU status
     */
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

    /**
     * Parse optical power from "show epon onu-optical-transceiver-diagnosis" output
     *
     * Typical output:
     * Temperature     : 45 C
     * Voltage         : 3.30 V
     * Tx Bias Current : 18 mA
     * Tx Power        : 2.50 dBm
     * Rx Power        : -18.30 dBm
     */
    protected function parseOpticalPower(string $output): array
    {
        $data = [];

        // Match Rx Power in dBm
        if (preg_match('/Rx\s*(?:Optical\s*)?Power\s*[:=]\s*([-\d.]+)\s*(?:dBm)?/i', $output, $m)) {
            $data['rx_power'] = (float) $m[1];
        }

        // Match Tx Power in dBm
        if (preg_match('/Tx\s*(?:Optical\s*)?Power\s*[:=]\s*([-\d.]+)\s*(?:dBm)?/i', $output, $m)) {
            $data['tx_power'] = (float) $m[1];
        }

        // Match Temperature
        if (preg_match('/Temperature\s*[:=]\s*([-\d.]+)/i', $output, $m)) {
            $data['temperature'] = (float) $m[1];
        }

        // Match Voltage
        if (preg_match('/Voltage\s*[:=]\s*([-\d.]+)/i', $output, $m)) {
            $data['voltage'] = (float) $m[1];
        }

        return $data;
    }

    /**
     * Parse all optical power data from port-level query
     * Returns array keyed by ONU ID
     */
    protected function parseAllOpticalPower(string $output): array
    {
        $results = [];
        $lines = explode("\n", $output);
        $currentOnuId = null;

        foreach ($lines as $line) {
            $line = trim($line);

            // Detect ONU ID header (e.g., "ONU 1:" or "onu-id: 1" or just "1  aa:bb:...")
            if (preg_match('/(?:ONU|onu.id)\s*[:=]?\s*(\d+)/i', $line, $m)) {
                $currentOnuId = (int) $m[1];
                if (!isset($results[$currentOnuId])) {
                    $results[$currentOnuId] = [];
                }
            }

            // Table format: ID  MAC  RxPower  TxPower ...
            if (preg_match('/^\s*(\d+)\s+[\da-fA-F:.\-]+\s+([-\d.]+)\s+([-\d.]+)/i', $line, $m)) {
                $onuId = (int) $m[1];
                $results[$onuId] = [
                    'rx_power' => (float) $m[2],
                    'tx_power' => (float) $m[3],
                ];
            }

            // Line-by-line format within ONU block
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
