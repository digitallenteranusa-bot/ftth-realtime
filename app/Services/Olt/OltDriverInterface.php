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
}
