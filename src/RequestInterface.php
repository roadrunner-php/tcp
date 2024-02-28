<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

interface RequestInterface
{
    /**
     * Returns the client's IP address.
     *
     * @return non-empty-string
     */
    public function getRemoteAddress(): string;

    /**
     * Returns the connection event type (CONNECTED, DATA, CLOSED).
     */
    public function getEvent(): TcpEvent;

    /**
     * Returns the received data from the connection.
     */
    public function getBody(): string;

    /**
     * Returns the connection UUID.
     *
     * @return non-empty-string
     */
    public function getConnectionUuid(): string;

    /**
     * Returns the server name that received the data.
     *
     * @return non-empty-string
     */
    public function getServer(): string;
}
