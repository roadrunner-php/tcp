<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

final class Request implements RequestInterface
{
    /**
     * @param non-empty-string $remoteAddr Client IP Address
     * @param TcpEvent $event Connection event type (CONNECTED, DATA, CLOSED)
     * @param string $body Received data from the connection
     * @param non-empty-string $connectionUuid Connection UUID
     * @param non-empty-string $server Server name, which received data
     */
    public function __construct(
        private readonly string $remoteAddr,
        private readonly TcpEvent $event,
        private readonly string $body,
        private readonly string $connectionUuid,
        private readonly string $server,
    ) {}

    /**
     * Returns the client's IP address.
     *
     * @return non-empty-string
     */
    #[\Override]
    public function getRemoteAddress(): string
    {
        return $this->remoteAddr;
    }

    /**
     * Returns the connection event type (CONNECTED, DATA, CLOSED).
     */
    #[\Override]
    public function getEvent(): TcpEvent
    {
        return $this->event;
    }

    /**
     * Returns the received data from the connection.
     */
    #[\Override]
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Returns the connection UUID.
     *
     * @return non-empty-string
     */
    #[\Override]
    public function getConnectionUuid(): string
    {
        return $this->connectionUuid;
    }

    /**
     * Returns the server name that received the data.
     *
     * @return non-empty-string
     */
    #[\Override]
    public function getServer(): string
    {
        return $this->server;
    }
}
