<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

use Spiral\RoadRunner\WorkerAwareInterface;

interface TcpWorkerInterface extends WorkerAwareInterface
{
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
     */
    public function respond(string $body): void;
}