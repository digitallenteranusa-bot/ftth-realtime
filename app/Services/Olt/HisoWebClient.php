<?php

namespace App\Services\Olt;

use App\Models\Olt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HisoWebClient
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;

    public function __construct(protected Olt $olt)
    {
        $this->baseUrl = "http://{$olt->host}";
        $this->username = $olt->username;
        $this->password = $olt->password;
    }

    /**
     * Fetch ONU list for a specific PON port via web interface
     * URL: /onuOverview.asp?oltponno={slot}/{port}
     *
     * Returns array of ONUs with all diagnostic data
     */
    public function getOnuList(int $slot, int $port): array
    {
        $url = "{$this->baseUrl}/onuOverview.asp?oltponno={$slot}/{$port}";

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(15)
                ->get($url);

            if (!$response->successful()) {
                Log::warning("HIOSO web request failed: HTTP {$response->status()} for {$url}");
                return [];
            }

            return $this->parseOnuTable($response->body());
        } catch (\Throwable $e) {
            Log::warning("HIOSO web request error: {$e->getMessage()} for {$url}");
            return [];
        }
    }

    /**
     * Fetch ONU list for all active PON ports
     */
    public function getAllOnuList(): array
    {
        $allOnus = [];
        $ponPorts = $this->olt->ponPorts()->where('is_active', true)->get();

        foreach ($ponPorts as $ponPort) {
            $onus = $this->getOnuList($ponPort->slot, $ponPort->port);
            $allOnus = array_merge($allOnus, $onus);
        }

        return $allOnus;
    }

    /**
     * Test web connection
     */
    public function testConnection(): array
    {
        try {
            $url = "{$this->baseUrl}/onuOverview.asp?oltponno=0/1";
            Log::debug("HIOSO web test: connecting to {$url} with user={$this->username}");

            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(10)
                ->get($url);

            $status = $response->status();
            $hasTable = str_contains($response->body(), 'onutable');

            Log::debug("HIOSO web test: HTTP {$status}, onutable=" . ($hasTable ? 'found' : 'not found') . ", body_length=" . strlen($response->body()));

            if ($response->successful() && $hasTable) {
                return ['connected' => true, 'message' => "HTTP {$status} OK"];
            }

            return ['connected' => false, 'message' => "HTTP {$status}" . ($response->successful() ? ', onutable not found' : '')];
        } catch (\Throwable $e) {
            Log::warning("HIOSO web test failed: {$e->getMessage()}");
            return ['connected' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Parse the JavaScript onutable array from HTML response
     *
     * Format per ONU (16 fields):
     * 'port:id','name','mac','status','fw','chip','ports','?','ctcver','?','distance',
     * 'temperature','voltage','bias_current','tx_power','rx_power'
     */
    protected function parseOnuTable(string $html): array
    {
        $onus = [];

        // Extract the onutable array content
        if (!preg_match('/var\s+onutable\s*=\s*new\s+Array\s*\((.*?)\)\s*;/s', $html, $match)) {
            Log::debug("HIOSO web: onutable not found in response");
            return [];
        }

        $arrayContent = $match[1];

        // Parse each ONU entry (16 quoted values per ONU)
        // Match groups of quoted strings separated by commas
        preg_match_all("/'([^']*)'/", $arrayContent, $values);

        if (empty($values[1])) {
            return [];
        }

        $fields = $values[1];
        $fieldsPerOnu = 16;
        $onuCount = intdiv(count($fields), $fieldsPerOnu);

        for ($i = 0; $i < $onuCount; $i++) {
            $offset = $i * $fieldsPerOnu;

            // Parse port:id format (e.g., "0/1:3")
            $portId = $fields[$offset];
            if (!preg_match('/(\d+)\/(\d+):(\d+)/', $portId, $pm)) {
                continue;
            }

            $onus[] = [
                'slot' => (int) $pm[1],
                'port' => (int) $pm[2],
                'onu_id' => (int) $pm[3],
                'name' => $fields[$offset + 1],
                'mac' => $fields[$offset + 2],
                'status' => strtolower($fields[$offset + 3]),
                'fw_version' => $fields[$offset + 4],
                'chip_id' => $fields[$offset + 5],
                'distance' => (int) $fields[$offset + 10],
                'temperature' => (float) $fields[$offset + 11],
                'voltage' => (float) $fields[$offset + 12],
                'tx_power' => (float) $fields[$offset + 14],
                'rx_power' => (float) $fields[$offset + 15],
            ];
        }

        return $onus;
    }
}
