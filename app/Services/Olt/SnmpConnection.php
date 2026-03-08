<?php

namespace App\Services\Olt;

use Illuminate\Support\Facades\Log;

class SnmpConnection
{
    protected string $host;
    protected string $community;
    protected int $port;
    protected int $timeout;
    protected int $retries;

    public function __construct(string $host, string $community = 'public', int $port = 161, int $timeout = 5, int $retries = 2)
    {
        $this->host = $host;
        $this->community = $community;
        $this->port = $port;
        $this->timeout = $timeout * 1000000; // convert to microseconds
        $this->retries = $retries;
    }

    /**
     * Check if PHP SNMP extension is available
     */
    public static function isAvailable(): bool
    {
        return function_exists('snmp2_get');
    }

    /**
     * SNMP GET - single OID
     */
    public function get(string $oid): string|false
    {
        try {
            $result = @snmp2_get(
                "{$this->host}:{$this->port}",
                $this->community,
                $oid,
                $this->timeout,
                $this->retries
            );

            if ($result === false) {
                return false;
            }

            return $this->parseValue($result);
        } catch (\Throwable $e) {
            Log::debug("SNMP GET failed for {$this->host} OID {$oid}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * SNMP WALK - subtree
     */
    public function walk(string $oid): array
    {
        try {
            // Suppress warnings, handle errors via return value
            $result = @snmp2_walk(
                "{$this->host}:{$this->port}",
                $this->community,
                $oid,
                $this->timeout,
                $this->retries
            );

            if ($result === false) {
                return [];
            }

            $parsed = [];
            foreach ($result as $fullOid => $value) {
                // Extract the index from the full OID
                $index = str_replace($oid . '.', '', $fullOid);
                $parsed[$index] = $this->parseValue($value);
            }

            return $parsed;
        } catch (\Throwable $e) {
            Log::debug("SNMP WALK failed for {$this->host} OID {$oid}: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * SNMP GET multiple OIDs at once
     */
    public function getMultiple(array $oids): array
    {
        $results = [];
        foreach ($oids as $key => $oid) {
            $results[$key] = $this->get($oid);
        }
        return $results;
    }

    /**
     * Test SNMP connectivity
     */
    public function testConnection(): bool
    {
        // Try sysDescr.0 - should always work
        $result = $this->get('1.3.6.1.2.1.1.1.0');
        return $result !== false;
    }

    /**
     * Get system description
     */
    public function getSysDescr(): string
    {
        return $this->get('1.3.6.1.2.1.1.1.0') ?: '';
    }

    /**
     * Parse SNMP value - strip type prefix
     * e.g., "INTEGER: 42" -> "42", "STRING: hello" -> "hello"
     */
    protected function parseValue(string $value): string
    {
        // Remove type prefixes like INTEGER:, STRING:, Gauge32:, Counter32:, etc.
        if (preg_match('/^(?:INTEGER|STRING|Gauge32|Counter32|Counter64|Timeticks|OID|IpAddress|Hex-STRING|Opaque):\s*(.*)$/i', $value, $m)) {
            $value = trim($m[1]);
        }

        // Remove surrounding quotes
        $value = trim($value, '"\'');

        return $value;
    }
}
