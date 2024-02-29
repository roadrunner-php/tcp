<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\WorkerInterface;

/**
 * @psalm-type RequestContext = array{
 *      remote_addr: non-empty-string,
 *      server: non-empty-string,
 *      uuid: non-empty-string,
 *      event: enum-string<TcpEvent>
 * }
 *
 * @see Request
 */
class TcpWorker implements TcpWorkerInterface
{
    public function __construct(
        private readonly WorkerInterface $worker,
    ) {
    }

    public function getWorker(): WorkerInterface
    {
        return $this->worker;
    }

    public function waitRequest(): ?RequestInterface
    {
        $payload = $this->worker->waitPayload();

        // Close connection if server received empty payload
        if ($payload === null) {
            $this->close();
            return null;
        }

        /** @var RequestContext $context */
        $context = \json_decode($payload->header, true, 512, \JSON_THROW_ON_ERROR);

        return $this->createRequest($payload->body, $context);
    }

    public function respond(string $body, TcpResponse $response = TcpResponse::Respond): void
    {
        $this->worker->respond(
            new Payload($body, $response->value),
        );
    }

    public function read(): void
    {
        $this->respond('', TcpResponse::Read);
    }

    public function close(): void
    {
        $this->respond('', TcpResponse::Close);
    }

    /**
     * Creates request from received payload.
     *
     * @param RequestContext $context
     * @psalm-suppress InaccessibleProperty
     */
    private function createRequest(string $body, array $context): Request
    {
        return new Request(
            remoteAddr: $context['remote_addr'],
            event: TcpEvent::tryFrom($context['event'] ?? 'UNKNOWN') ?? TcpEvent::Unknown,
            body: $body,
            connectionUuid: $context['uuid'],
            server: $context['server'],
        );
    }
}
