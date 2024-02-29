<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp\Tests;

use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\Tcp\TcpEvent;
use Spiral\RoadRunner\Tcp\TcpResponse;
use Spiral\RoadRunner\Tcp\TcpWorker;
use Spiral\RoadRunner\WorkerInterface;

final class TcpWorkerTest extends TestCase
{
    private TcpWorker $tcpWorker;
    private WorkerInterface $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tcpWorker = new TcpWorker(
            $this->worker = $this->createMock(WorkerInterface::class)
        );
    }

    public function testNullablePayloadShouldCloseConnection()
    {
        $this->worker
            ->expects($this->once())
            ->method('waitPayload')
            ->willReturn(null);

        $this->worker
            ->expects($this->once())
            ->method('respond')
            ->with($this->callback(function(Payload $payload){
                return $payload->body === '' && $payload->header === TcpResponse::Close->value;
            }));

        $this->tcpWorker->waitRequest();
    }

    public function testInvalidHeaderShouldThrowException()
    {
        $this->expectException(\JsonException::class);

        $this->worker
            ->expects($this->once())
            ->method('waitPayload')
            ->willReturn(new Payload('', '{123}'));

        $this->tcpWorker->waitRequest();
    }

    public function testRequestShouldBeCreated()
    {
        $remoteIp = '192.168.1.1';
        $server = 'homestead';
        $uuid = '5191d583-4661-4781-bfe8-4461aab5072e';
        $event = TcpEvent::Connected;

        $this->worker
            ->expects($this->once())
            ->method('waitPayload')
            ->willReturn(new Payload('foo', json_encode([
                'remote_addr' => $remoteIp, 'server' => $server,
                'uuid' => $uuid, 'event' => $event->value
            ])));

        $request = $this->tcpWorker->waitRequest();

        $this->assertSame($remoteIp, $request->getRemoteAddress());
        $this->assertSame($server, $request->getServer());
        $this->assertSame($uuid, $request->getConnectionUuid());
        $this->assertSame($event, $request->getEvent());
    }

    public function testReadResponse()
    {
        $this->worker
            ->expects($this->once())
            ->method('respond')
            ->with($this->callback(function(Payload $payload){
                return $payload->body === '' && $payload->header === TcpResponse::Read->value;
            }));

        $this->tcpWorker->read();
    }

    public function testCloseConnectionResponse()
    {
        $this->worker
            ->expects($this->once())
            ->method('respond')
            ->with($this->callback(function(Payload $payload){
                return $payload->body === '' && $payload->header === TcpResponse::Close->value;
            }));

        $this->tcpWorker->close();
    }

    public function testRespond()
    {
        $this->worker
            ->expects($this->once())
            ->method('respond')
            ->with($this->callback(function(Payload $payload){
                return $payload->body === 'foo' && $payload->header === TcpResponse::Respond->value;
            }));

        $this->tcpWorker->respond('foo');
    }

    public function testCloseRespondAndCloseConnection()
    {
        $this->worker
            ->expects($this->once())
            ->method('respond')
            ->with($this->callback(function(Payload $payload){
                return $payload->body === 'foo' && $payload->header === TcpResponse::RespondClose->value;
            }));

        $this->tcpWorker->respond('foo', TcpResponse::RespondClose);
    }

    public function testGetsWorker()
    {
        $this->assertSame($this->worker, $this->tcpWorker->getWorker());
    }
}
