<?php
namespace App\Services\Olt;

use App\Models\Olt;
use phpseclib3\Net\SSH2;

class HuaweiOltDriver implements OltDriverInterface
{
    protected ?SSH2 $connection = null;

    public function __construct(protected Olt $olt) {}

    public function connect(): bool
    {
        try {
            $this->connection = new SSH2($this->olt->host, $this->olt->ssh_port);
            if ($this->connection->login($this->olt->username, $this->olt->password)) {
                $this->execute('enable');
                return true;
            }
            return false;
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
        $output = $this->execute("display ont info {$slot} {$port} all");
        return $this->parseOntList($output);
    }

    public function getOntStatus(int $slot, int $port, int $ontId): array
    {
        $output = $this->execute("display ont info {$slot} {$port} {$ontId}");
        return $this->parseOntDetail($output);
    }

    public function getOpticalPower(int $slot, int $port, int $ontId): array
    {
        $output = $this->execute("display ont optical-info {$slot} {$port} {$ontId}");
        return $this->parseOpticalPower($output);
    }

    public function getUnregisteredOnts(): array
    {
        $output = $this->execute("display ont autofind all");
        return $this->parseUnregistered($output);
    }

    protected function parseOntList(string $output): array
    {
        $onts = [];
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match('/^\s*(\d+)\s+(\S+)\s+(\S+)\s+(\S+)/', $line, $m)) {
                $onts[] = ['ont_id' => (int)$m[1], 'serial' => $m[2], 'control_flag' => $m[3], 'run_state' => $m[4]];
            }
        }
        return $onts;
    }

    protected function parseOntDetail(string $output): array
    {
        $data = [];
        if (preg_match('/SN\s*:\s*(\S+)/', $output, $m)) $data['serial'] = $m[1];
        if (preg_match('/Run state\s*:\s*(\S+)/', $output, $m)) $data['run_state'] = $m[1];
        return $data;
    }

    protected function parseOpticalPower(string $output): array
    {
        $data = [];
        if (preg_match('/OLT Rx ONT optical power\(dBm\)\s*:\s*([-\d.]+)/', $output, $m)) $data['rx_power'] = (float)$m[1];
        return $data;
    }

    protected function parseUnregistered(string $output): array
    {
        $onts = [];
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match('/(\d+)\s+(\d+)\s+(\S+)/', $line, $m)) {
                $onts[] = ['slot' => 0, 'port' => (int)$m[1], 'ont_id' => (int)$m[2], 'serial' => $m[3]];
            }
        }
        return $onts;
    }
}
