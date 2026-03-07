<?php
namespace App\Services\Olt;

interface OltDriverInterface
{
    public function connect(): bool;
    public function disconnect(): void;
    public function getOntList(int $slot, int $port): array;
    public function getOntStatus(int $slot, int $port, int $ontId): array;
    public function getOpticalPower(int $slot, int $port, int $ontId): array;
    public function getUnregisteredOnts(): array;
    public function registerOnt(int $slot, int $port, int $ontId, string $serialNumber, string $lineProfile, string $serviceProfile): bool;
    public function deregisterOnt(int $slot, int $port, int $ontId): bool;
}
