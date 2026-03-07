<?php
namespace App\Services\Olt;

use App\Models\Olt;
use phpseclib3\Net\SSH2;

class FiberHomeOltDriver implements OltDriverInterface
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
        if (!$this->connection) $this->connect();
        return $this->connection?->exec($command . "\n") ?? '';
    }

    public function getOntList(int $slot, int $port): array
    {
        $output = $this->execute("show onu running config gpon-olt {$slot}/{$port}");
        return $this->parseOntList($output);
    }

    public function getOntStatus(int $slot, int $port, int $ontId): array
    {
        $output = $this->execute("show onu state gpon-olt {$slot}/{$port} {$ontId}");
        return $this->parseOntDetail($output);
    }

    public function getOpticalPower(int $slot, int $port, int $ontId): array
    {
        $output = $this->execute("show onu optical-transceiver-diagnosis gpon-olt {$slot}/{$port} {$ontId}");
        return $this->parseOpticalPower($output);
    }

    public function getUnregisteredOnts(): array
    {
        $output = $this->execute("show onu unauth");
        return $this->parseUnregistered($output);
    }

    protected function parseOntList(string $output): array
    {
        $onts = [];
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match('/ONU\s+(\d+)\s+(\S+)\s+(\S+)/', $line, $m)) {
                $onts[] = ['ont_id' => (int)$m[1], 'serial' => $m[2], 'status' => $m[3]];
            }
        }
        return $onts;
    }

    protected function parseOntDetail(string $output): array
    {
        $data = [];
        if (preg_match('/SerialNumber\s*:\s*(\S+)/', $output, $m)) $data['serial'] = $m[1];
        if (preg_match('/AdminState\s*:\s*(\S+)/', $output, $m)) $data['admin_state'] = $m[1];
        return $data;
    }

    protected function parseOpticalPower(string $output): array
    {
        $data = [];
        if (preg_match('/Rx Power\s*:\s*([-\d.]+)/', $output, $m)) $data['rx_power'] = (float)$m[1];
        if (preg_match('/Tx Power\s*:\s*([-\d.]+)/', $output, $m)) $data['tx_power'] = (float)$m[1];
        return $data;
    }

    public function registerOnt(int $slot, int $port, int $ontId, string $serialNumber, string $lineProfile, string $serviceProfile): bool
    {
        $commands = [
            "cd gpon",
            "set whitelist phy_addr address {$serialNumber} password null action add slot {$slot} link {$port} onu {$ontId}",
            "set onu_profile name {$serviceProfile} slot {$slot} link {$port} onu {$ontId}",
            "set line_profile name {$lineProfile} slot {$slot} link {$port} onu {$ontId}",
            "cd ..",
            "save",
        ];

        $output = '';
        foreach ($commands as $cmd) {
            $output .= $this->execute($cmd);
        }

        return !str_contains($output, 'Error') && !str_contains($output, 'FAIL');
    }

    public function deregisterOnt(int $slot, int $port, int $ontId): bool
    {
        $commands = [
            "cd gpon",
            "set whitelist phy_addr action delete slot {$slot} link {$port} onu {$ontId}",
            "cd ..",
            "save",
        ];

        $output = '';
        foreach ($commands as $cmd) {
            $output .= $this->execute($cmd);
        }

        return !str_contains($output, 'Error') && !str_contains($output, 'FAIL');
    }

    protected function parseUnregistered(string $output): array
    {
        $onts = [];
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match('/(\d+)\/(\d+)\s+(\d+)\s+(\S+)/', $line, $m)) {
                $onts[] = ['slot' => (int)$m[1], 'port' => (int)$m[2], 'ont_id' => (int)$m[3], 'serial' => $m[4]];
            }
        }
        return $onts;
    }
}
