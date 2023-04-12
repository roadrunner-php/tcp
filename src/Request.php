<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

final class Request
{
    /**
     * @param non-empty-string $remoteAddr Client IP Address
     * @param TcpEvent $event Connection event type (CONNECTED, DATA, CLOSED)
     * @param string $body Received data from the connection
     * @param non-empty-string $connectionUuid Connection UUID
     * @param non-empty-string $server Server name, which received data
     */
    public function __construct(
        public readonly string $remoteAddr,
        public readonly TcpEvent $event,
        public readonly string $body,
        public readonly string $connectionUuid,
        public readonly string $server,
    ) {
    }
}
