<?php
namespace App\Services\Mikrotik;

use App\Models\Mikrotik;
use RouterOS\Query;

class MikrotikApiService
{
    public function __construct(protected MikrotikConnectionManager $connectionManager) {}

    public function getActivePppoe(Mikrotik $mikrotik): array
    {
        try {
            $client = $this->connectionManager->getConnection($mikrotik);
            $query = new Query('/ppp/active/print');
            return $client->query($query)->read();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    public function disconnectPppoe(Mikrotik $mikrotik, string $id): bool
    {
        try {
            $client = $this->connectionManager->getConnection($mikrotik);
            $query = (new Query('/ppp/active/remove'))->equal('.id', $id);
            $client->query($query)->read();
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function getInterfaces(Mikrotik $mikrotik): array
    {
        try {
            $client = $this->connectionManager->getConnection($mikrotik);
            $query = new Query('/interface/print');
            return $client->query($query)->read();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    public function getInterfaceTraffic(Mikrotik $mikrotik, string $interface): array
    {
        try {
            $client = $this->connectionManager->getConnection($mikrotik);
            $query = (new Query('/interface/monitor-traffic'))
                ->equal('interface', $interface)
                ->equal('once', '');
            return $client->query($query)->read();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    public function getSystemResources(Mikrotik $mikrotik): array
    {
        try {
            $client = $this->connectionManager->getConnection($mikrotik);
            $query = new Query('/system/resource/print');
            $result = $client->query($query)->read();
            return $result[0] ?? [];
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    public function getQueues(Mikrotik $mikrotik): array
    {
        try {
            $client = $this->connectionManager->getConnection($mikrotik);
            $query = new Query('/queue/simple/print');
            return $client->query($query)->read();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    public function getHotspotUsers(Mikrotik $mikrotik): array
    {
        try {
            $client = $this->connectionManager->getConnection($mikrotik);
            $query = new Query('/ip/hotspot/active/print');
            return $client->query($query)->read();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    public function getLogs(Mikrotik $mikrotik, int $limit = 50): array
    {
        try {
            $client = $this->connectionManager->getConnection($mikrotik);
            $query = new Query('/log/print');
            $logs = $client->query($query)->read();
            return array_slice($logs, -$limit);
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }
}
