<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp\Tests\Unit;

use Mockery\MockInterface;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\Tcp\TcpEvent;
use Spiral\RoadRunner\Tcp\TcpResponse;
use Spiral\RoadRunner\Tcp\TcpWorker;
use Spiral\RoadRunner\WorkerInterface;
use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class TcpWorkerTest
{
    private TcpWorker $tcpWorker;
    private MockInterface&WorkerInterface $worker;

    public function testNullablePayloadShouldCloseConnection(): void
    {
        $this->worker->shouldReceive('waitPayload')->once()->andReturn(null);

        $this->worker->shouldReceive('respond')->once()->with(\Mockery::on(function (Payload $payload) {
            return $payload->body === '' && $payload->header === TcpResponse::Close->value;
        }), \Mockery::andAnyOtherArgs());

        $this->tcpWorker->waitRequest();
    }

    public function testInvalidHeaderShouldThrowException(): void
    {
        Expect::exception(\JsonException::class);

        $this->worker->shouldReceive('waitPayload')->once()->andReturn(new Payload('', '{123}'));

        $this->tcpWorker->waitRequest();
    }

    public function testRequestShouldBeCreated(): void
    {
        $remoteIp = '192.168.1.1';
        $server = 'homestead';
        $uuid = '5191d583-4661-4781-bfe8-4461aab5072e';
        $event = TcpEvent::Connected;

        $this->worker->shouldReceive('waitPayload')->once()->andReturn(new Payload('foo', json_encode([
            'remote_addr' => $remoteIp, 'server' => $server,
            'uuid' => $uuid, 'event' => $event->value,
        ])));

        $request = $this->tcpWorker->waitRequest();

        Assert::same($request->getRemoteAddress(), $remoteIp);
        Assert::same($request->getServer(), $server);
        Assert::same($request->getConnectionUuid(), $uuid);
        Assert::same($request->getEvent(), $event);
    }

    public function testNullablePayloadReturnsNoRequest(): void
    {
        $this->worker->shouldReceive('waitPayload')->once()->andReturn(null);

        Assert::null($this->tcpWorker->waitRequest());
    }

    public function testRequestKeepsPayloadBody(): void
    {
        $this->worker->shouldReceive('waitPayload')->once()->andReturn(new Payload("raw\0data", $this->header('DATA')));

        Assert::same($this->tcpWorker->waitRequest()?->getBody(), "raw\0data");
    }

    #[DataSet(['CONNECTED', TcpEvent::Connected], 'connected')]
    #[DataSet(['DATA', TcpEvent::Data], 'data')]
    #[DataSet(['CLOSE', TcpEvent::Close], 'close')]
    #[DataSet(['UNKNOWN', TcpEvent::Unknown], 'unknown')]
    #[DataSet(['SOMETHING_ELSE', TcpEvent::Unknown], 'unsupported event')]
    #[DataSet(['connected', TcpEvent::Unknown], 'event names are case-sensitive')]
    public function testEventIsMappedFromHeader(string $event, TcpEvent $expected): void
    {
        $this->worker->shouldReceive('waitPayload')->once()->andReturn(new Payload('', $this->header($event)));

        Assert::same($this->tcpWorker->waitRequest()?->getEvent(), $expected);
    }

    public function testMissingEventIsUnknown(): void
    {
        $this->worker->shouldReceive('waitPayload')->once()->andReturn(new Payload('', \json_encode([
            'remote_addr' => '127.0.0.1', 'server' => 'tcp', 'uuid' => 'uuid',
        ])));

        Assert::same($this->tcpWorker->waitRequest()?->getEvent(), TcpEvent::Unknown);
    }

    #[DataSet([TcpResponse::Respond, 'WRITE'], 'write')]
    #[DataSet([TcpResponse::RespondClose, 'WRITECLOSE'], 'write and close')]
    #[DataSet([TcpResponse::Read, 'CONTINUE'], 'continue reading')]
    #[DataSet([TcpResponse::Close, 'CLOSE'], 'close')]
    public function testResponseHeaderMatchesServerProtocol(TcpResponse $response, string $header): void
    {
        $this->worker->shouldReceive('respond')->once()->with(\Mockery::on(
            static fn(Payload $payload): bool => $payload->body === 'body' && $payload->header === $header,
        ), \Mockery::andAnyOtherArgs());

        $this->tcpWorker->respond('body', $response);
    }

    public function testReadResponse(): void
    {
        $this->worker->shouldReceive('respond')->once()->with(\Mockery::on(function (Payload $payload) {
            return $payload->body === '' && $payload->header === TcpResponse::Read->value;
        }), \Mockery::andAnyOtherArgs());

        $this->tcpWorker->read();
    }

    public function testCloseConnectionResponse(): void
    {
        $this->worker->shouldReceive('respond')->once()->with(\Mockery::on(function (Payload $payload) {
            return $payload->body === '' && $payload->header === TcpResponse::Close->value;
        }), \Mockery::andAnyOtherArgs());

        $this->tcpWorker->close();
    }

    public function testRespond(): void
    {
        $this->worker->shouldReceive('respond')->once()->with(\Mockery::on(function (Payload $payload) {
            return $payload->body === 'foo' && $payload->header === TcpResponse::Respond->value;
        }), \Mockery::andAnyOtherArgs());

        $this->tcpWorker->respond('foo');
    }

    public function testCloseRespondAndCloseConnection(): void
    {
        $this->worker->shouldReceive('respond')->once()->with(\Mockery::on(function (Payload $payload) {
            return $payload->body === 'foo' && $payload->header === TcpResponse::RespondClose->value;
        }), \Mockery::andAnyOtherArgs());

        $this->tcpWorker->respond('foo', TcpResponse::RespondClose);
    }

    public function testGetsWorker(): void
    {
        Assert::same($this->tcpWorker->getWorker(), $this->worker);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->tcpWorker = new TcpWorker(
            $this->worker = \Mockery::mock(WorkerInterface::class)->shouldIgnoreMissing(),
        );
    }

    private function header(string $event): string
    {
        return \json_encode([
            'remote_addr' => '127.0.0.1',
            'server' => 'tcp',
            'uuid' => '5191d583-4661-4781-bfe8-4461aab5072e',
            'event' => $event,
        ], \JSON_THROW_ON_ERROR);
    }
}
