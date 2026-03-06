<?php
namespace App\Services\Mikrotik;

use App\Models\Mikrotik;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class MikrotikConnectionManager
{
    protected array $connections = [];

    public function getConnection(Mikrotik $mikrotik): Client
    {
        $key = $mikrotik->id;
        if (!isset($this->connections[$key])) {
            $config = new Config([
                'host' => $mikrotik->host,
                'port' => $mikrotik->api_port,
                'user' => $mikrotik->api_username,
                'pass' => $mikrotik->api_password,
                'timeout' => 10,
            ]);
            $this->connections[$key] = new Client($config);
        }
        return $this->connections[$key];
    }

    public function disconnect(Mikrotik $mikrotik): void
    {
        unset($this->connections[$mikrotik->id]);
    }

    public function disconnectAll(): void
    {
        $this->connections = [];
    }
}
