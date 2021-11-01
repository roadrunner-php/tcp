<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

use JetBrains\PhpStorm\Immutable;

/**
 * @param TcpWorkerInterface::EVENT_* $event
 */
#[Immutable]
final class Request
{
    /**
     * Client IP Address
     */
    public string $remoteAddr = '127.0.0.1';

    /**
     * Connection event type (CONNECTED, DATA, CLOSED)
     */
    public string $event = '';

    /**
     * Received data from the connection
     */
    public string $body = '';

    /**
     * Connection UUID
     */
    public string $connectionUuid = '';

    /**
     * Server name, which received data
     */
    public string $server = '';
}
