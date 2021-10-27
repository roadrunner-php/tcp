<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

use Spiral\RoadRunner\WorkerAwareInterface;

interface TcpWorkerInterface extends WorkerAwareInterface
{
    const TCP_CLOSE = 'CLOSE';
    const TCP_RESPOND = 'WRITE';
    const TCP_RESPOND_CLOSE = 'WRITECLOSE';
    const TCP_READ = 'CONTINUE';

    /**
     * Wait for incoming tcp request.
     *
     * @return Request|null
     */
    public function waitRequest(): ?Request;

    /**
     * Send response to the application server.
     *
     * @param string $body Body of response
     * @param bool $close Close connection after respond
     */
    public function respond(string $body, bool $close = false): void;

    /**
     * Close current connection.
     */
    public function close(): void;

    /**
     * Continue read from connection.
     */
    public function read(): void;
}