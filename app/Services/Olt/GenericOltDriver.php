<?php
namespace App\Services\Olt;

use App\Models\Olt;
use phpseclib3\Net\SSH2;

class GenericOltDriver implements OltDriverInterface
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

    public function getOntList(int $slot, int $port): array
    {
        return [];
    }

    public function getOntStatus(int $slot, int $port, int $ontId): array
    {
        return ['status' => 'unknown', 'vendor' => $this->olt->vendor];
    }

    public function getOpticalPower(int $slot, int $port, int $ontId): array
    {
        return ['rx_power' => null, 'tx_power' => null];
    }

    public function getUnregisteredOnts(): array
    {
        return [];
    }

    public function registerOnt(int $slot, int $port, int $ontId, string $serialNumber, string $lineProfile, string $serviceProfile): bool
    {
        return false;
    }

    public function deregisterOnt(int $slot, int $port, int $ontId): bool
    {
        return false;
    }
}
