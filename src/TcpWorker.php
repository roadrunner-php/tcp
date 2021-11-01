<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

use Spiral\RoadRunner\WorkerInterface;
use Spiral\RoadRunner\Payload;

/**
 * @psalm-type RequestContext = array{
 *      remote_addr: non-empty-string,
 *      server: non-empty-string,
 *      uuid: non-empty-string,
 *      event: non-empty-string
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

        // Close connection if server received empty payload
        if ($payload === null) {
            return $this->close();
        }

        /** @var RequestContext $context */
        $context = \json_decode($payload->header, true, 512, \JSON_THROW_ON_ERROR);

        return $this->createRequest($payload->body, $context);
    }

    /** {@inheritDoc} */
    public function respond(string $body, bool $close = false): void
    {
        $context = $close ? self::TCP_RESPOND_CLOSE : self::TCP_RESPOND;

        $this->worker->respond(
            new Payload($body, $context)
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
     * Creates request from received payload.
     *
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
     * Hydrates data from request context.
     *
     * @param Request $request
     * @param RequestContext $context
     *
     * @psalm-suppress InaccessibleProperty
     */
    private function hydrateRequest(Request $request, array $context): void
    {
        $request->remoteAddr = $context['remote_addr'];
        $request->server = $context['server'];
        $request->event = $context['event'];
        $request->connectionUuid = $context['uuid'];
    }
}
