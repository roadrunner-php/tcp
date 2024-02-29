<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

use Spiral\RoadRunner\WorkerAwareInterface;

interface TcpWorkerInterface extends WorkerAwareInterface
{
    /**
     * Wait for incoming tcp request.
     */
    public function waitRequest(): ?RequestInterface;

    /**
     * Send response to the application server.
     *
     * @param string $body Body of response
     * @param TcpResponse $response Close connection after respond by default
     */
    public function respond(string $body, TcpResponse $response = TcpResponse::Respond): void;

    /**
     * Close current connection.
     */
    public function close(): void;

    /**
     * Continue read from connection.
     */
    public function read(): void;
}
