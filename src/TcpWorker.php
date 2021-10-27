<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

use Spiral\RoadRunner\WorkerInterface;
use Spiral\RoadRunner\Payload;

/**
 * @psalm-type RequestContext = array{
 *      remoteAddr: string|null,
 *      server: non-empty-string,
 *      id: non-empty-string
 * }
 *
 * @see Request
 */
class TcpWorker implements TcpWorkerInterface
{
    private WorkerInterface $worker;

    public function __construct(WorkerInterface $worker)
    {
        $this->worker = $worker;
    }

    /** {@inheritDoc} */
    public function getWorker(): WorkerInterface
    {
        return $this->worker;
    }

    /** {@inheritDoc} */
    public function waitRequest(): ?Request
    {
        $payload = $this->worker->waitPayload();

        // Termination request
        if ($payload === null || !$payload->body) {
            return null;
        }

        /** @var RequestContext $context */
        $context = \json_decode($payload->header, true, 512, \JSON_THROW_ON_ERROR);

        return $this->createRequest($payload->body, $context);
    }

    /** {@inheritDoc} */
    public function respond(string $body): void
    {
        $this->worker->respond(
            new Payload($body, self::TCP_RESPOND)
        );
    }

    /** {@inheritDoc} */
    public function read(): void
    {
        $this->worker->respond(
            new Payload('', self::TCP_READ)
        );
    }

    /** {@inheritDoc} */
    public function close(): void
    {
        $this->worker->respond(
            new Payload('', self::TCP_CLOSE)
        );
    }

    /**
     * @param string $body
     * @param RequestContext $context
     * @return Request
     *
     * @psalm-suppress InaccessibleProperty
     */
    private function createRequest(string $body, array $context): Request
    {
        $request = new Request();
        $request->body = $body;

        $this->hydrateRequest($request, $context);

        return $request;
    }

    /**
     * @param Request $request
     * @param RequestContext $context
     *
     * @psalm-suppress InaccessibleProperty
     */
    private function hydrateRequest(Request $request, array $context): void
    {
        $request->remoteAddr = $context['remote_addr'] ?? '';
        $request->server = $context['server'];
        $request->connectionUuid = $context['uuid'];
    }
}