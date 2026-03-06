<?php
namespace App\Services\Olt;

use App\Models\Olt;
use phpseclib3\Net\SSH2;

class ZteOltDriver implements OltDriverInterface
{
    protected ?SSH2 $connection = null;

    public function __construct(protected Olt $olt) {}

    public function connect(): bool
    {
        try {
            $this->connection = new SSH2($this->olt->host, $this->olt->ssh_port);
            return $this->connection->login($this->olt->username, $this->olt->password);
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
        if (!$this->connection) {
            $this->connect();
        }
        return $this->connection?->exec($command . "\n") ?? '';
    }

    public function getOntList(int $slot, int $port): array
    {
        $output = $this->execute("show gpon onu state gpon-olt_1/{$slot}/{$port}");
        return $this->parseOntList($output);
    }

    public function getOntStatus(int $slot, int $port, int $ontId): array
    {
        $output = $this->execute("show gpon onu detail-info gpon-onu_1/{$slot}/{$port}:{$ontId}");
        return $this->parseOntDetail($output);
    }

    public function getOpticalPower(int $slot, int $port, int $ontId): array
    {
        $output = $this->execute("show pon power onu-rx gpon-onu_1/{$slot}/{$port}:{$ontId}");
        return $this->parseOpticalPower($output);
    }

    public function getUnregisteredOnts(): array
    {
        $output = $this->execute("show gpon onu uncfg");
        return $this->parseUnregistered($output);
    }

    protected function parseOntList(string $output): array
    {
        $onts = [];
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match('/gpon-onu_1\/(\d+)\/(\d+):(\d+)\s+(\S+)\s+(\S+)/', $line, $matches)) {
                $onts[] = [
                    'slot' => (int)$matches[1],
                    'port' => (int)$matches[2],
                    'ont_id' => (int)$matches[3],
                    'admin_state' => $matches[4],
                    'oper_state' => $matches[5],
                ];
            }
        }
        return $onts;
    }

    protected function parseOntDetail(string $output): array
    {
        $data = [];
        if (preg_match('/Serial number:\s*(\S+)/', $output, $m)) $data['serial'] = $m[1];
        if (preg_match('/Phase state:\s*(\S+)/', $output, $m)) $data['phase_state'] = $m[1];
        if (preg_match('/Online duration:\s*(.+)/', $output, $m)) $data['online_duration'] = trim($m[1]);
        return $data;
    }

    protected function parseOpticalPower(string $output): array
    {
        $data = [];
        if (preg_match('/Rx\s*:\s*([-\d.]+)\s*dBm/', $output, $m)) $data['rx_power'] = (float)$m[1];
        if (preg_match('/Tx\s*:\s*([-\d.]+)\s*dBm/', $output, $m)) $data['tx_power'] = (float)$m[1];
        return $data;
    }

    protected function parseUnregistered(string $output): array
    {
        $onts = [];
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match('/gpon-onu_1\/(\d+)\/(\d+):(\d+)\s+(\S+)/', $line, $matches)) {
                $onts[] = [
                    'slot' => (int)$matches[1],
                    'port' => (int)$matches[2],
                    'ont_id' => (int)$matches[3],
                    'serial' => $matches[4],
                ];
            }
        }
        return $onts;
    }
}
