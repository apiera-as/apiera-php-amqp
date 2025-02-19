<?php

declare(strict_types=1);

namespace Apiera\Amqp;

final readonly class Configuration
{
    /**
     * @param string $host AMQP server host (max 1024 chars)
     * @param int $port Server port
     * @param string $login Username (max 128 chars)
     * @param string $password Password (max 128 chars)
     * @param string $vhost Virtual host (max 128 chars)
     * @param float $readTimeout Read timeout in seconds
     * @param float $writeTimeout Write timeout in seconds
     * @param float $connectTimeout Connect timeout in seconds
     * @param float $rpcTimeout RPC timeout in seconds
     * @param int $channelMax Max channels (0 = default)
     * @param int $frameMax Max frame size (0 = default)
     * @param int $heartbeat Heartbeat interval (0 = disabled)
     * @param string|null $cacert CA certificate path
     * @param string|null $cert Client certificate path
     * @param string|null $key Client key path
     * @param bool $verify Enable SSL verification
     * @param string|null $connectionName Custom connection name
     */
    public function __construct(
        private string $host,
        private int $port,
        private string $login,
        private string $password,
        private string $vhost,
        private float $readTimeout = 0.0,
        private float $writeTimeout = 0.0,
        private float $connectTimeout = 0.0,
        private float $rpcTimeout = 0.0,
        private int $channelMax = 256,
        private int $frameMax = 131072,
        private int $heartbeat = 0,
        private ?string $cacert = null,
        private ?string $cert = null,
        private ?string $key = null,
        private bool $verify = true,
        private ?string $connectionName = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getConnectionArguments(): array
    {
        $config = [
            'host' => $this->host,
            'port' => $this->port,
            'login' => $this->login,
            'password' => $this->password,
            'vhost' => $this->vhost,
            'read_timeout' => $this->readTimeout,
            'write_timeout' => $this->writeTimeout,
            'connect_timeout' => $this->connectTimeout,
            'rpc_timeout' => $this->rpcTimeout,
            'channel_max' => $this->channelMax,
            'frame_max' => $this->frameMax,
            'heartbeat' => $this->heartbeat,
            'verify' => $this->verify,
        ];

        // Only add TLS settings if they're set
        if ($this->cacert !== null) {
            $config['cacert'] = $this->cacert;
        }

        if ($this->cert !== null) {
            $config['cert'] = $this->cert;
        }

        if ($this->key !== null) {
            $config['key'] = $this->key;
        }

        // Add connection name if set
        if ($this->connectionName !== null) {
            $config['connection_name'] = $this->connectionName;
        }

        return $config;
    }
}
