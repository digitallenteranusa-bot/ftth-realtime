<?php

namespace App\Services\Olt;

class TelnetConnection
{
    protected $socket = null;
    protected string $prompt = '#';
    protected int $timeout;

    public function __construct(
        protected string $host,
        protected int $port = 23,
        int $timeout = 10
    ) {
        $this->timeout = $timeout;
    }

    public function connect(string $username, string $password, string $prompt = '#'): bool
    {
        $this->prompt = $prompt;

        try {
            $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
            if (!$this->socket) {
                return false;
            }

            stream_set_timeout($this->socket, $this->timeout);

            // Wait for login prompt and send username
            $response = $this->readUntil(['Username:', 'login:', 'name:'], 10);
            if ($response === false) {
                return false;
            }
            $this->write($username);

            // Wait for password prompt and send password
            $response = $this->readUntil(['Password:', 'password:'], 10);
            if ($response === false) {
                return false;
            }
            $this->write($password);

            // Wait for command prompt
            $response = $this->readUntil([$this->prompt, '>', '#'], 10);
            if ($response === false) {
                return false;
            }

            // Try to enter enable mode if we got >
            if (str_contains($response, '>')) {
                $this->write('enable');
                $response = $this->readUntil([$this->prompt, '#', 'Password:'], 5);
                if (str_contains($response, 'Password:')) {
                    $this->write($password);
                    $this->readUntil(['#'], 5);
                }
            }

            // Disable paging
            $this->exec('terminal length 0');

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function exec(string $command): string
    {
        if (!$this->socket) {
            return '';
        }

        $this->write($command);
        $response = $this->readUntil(['#', '>'], $this->timeout);

        // Remove the command echo and prompt from output
        $lines = explode("\n", $response);
        if (!empty($lines) && str_contains($lines[0], $command)) {
            array_shift($lines);
        }
        if (!empty($lines)) {
            $lastLine = end($lines);
            if (str_contains($lastLine, '#') || str_contains($lastLine, '>')) {
                array_pop($lines);
            }
        }

        return implode("\n", $lines);
    }

    protected function write(string $data): void
    {
        fwrite($this->socket, $data . "\r\n");
    }

    protected function readUntil(array $prompts, int $timeout = 10): string|false
    {
        $buffer = '';
        $startTime = time();

        while (true) {
            if ((time() - $startTime) > $timeout) {
                return $buffer ?: false;
            }

            $char = @fgetc($this->socket);
            if ($char === false) {
                $info = stream_get_meta_data($this->socket);
                if ($info['timed_out'] || $info['eof']) {
                    return $buffer ?: false;
                }
                usleep(10000); // 10ms
                continue;
            }

            $buffer .= $char;

            foreach ($prompts as $prompt) {
                if (str_contains($buffer, $prompt)) {
                    return $buffer;
                }
            }

            // Handle --More-- pagination
            if (str_contains($buffer, '--More--') || str_contains($buffer, '-- more --')) {
                fwrite($this->socket, " ");
                $buffer = str_replace(['--More--', '-- more --'], '', $buffer);
            }
        }
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            $this->write('exit');
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    public function isConnected(): bool
    {
        return $this->socket !== null;
    }
}
