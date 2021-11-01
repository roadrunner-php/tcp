<?php
declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp\Tests;

use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\Tcp\TcpWorker;
use Spiral\RoadRunner\Tcp\TcpWorkerInterface;
use Spiral\RoadRunner\WorkerInterface;

class TcpWorkerTestCase extends TestCase
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
                return $payload->body === '' && $payload->header === TcpWorkerInterface::TCP_CLOSE;
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
        $event = TcpWorkerInterface::EVENT_CONNECTED;

        $this->worker
            ->expects($this->once())
            ->method('waitPayload')
            ->willReturn(new Payload('foo', json_encode([
                'remote_addr' => $remoteIp, 'server' => $server,
                'uuid' => $uuid, 'event' => $event
            ])));

        $request = $this->tcpWorker->waitRequest();

        $this->assertSame($remoteIp, $request->remoteAddr);
        $this->assertSame($server, $request->server);
        $this->assertSame($uuid, $request->connectionUuid);
        $this->assertSame($event, $request->event);
    }

    public function testReadResponse()
    {
        $this->worker
            ->expects($this->once())
            ->method('respond')
            ->with($this->callback(function(Payload $payload){
                return $payload->body === '' && $payload->header === TcpWorkerInterface::TCP_READ;
            }));

        $this->tcpWorker->read();
    }

    public function testCloseConnectionResponse()
    {
        $this->worker
            ->expects($this->once())
            ->method('respond')
            ->with($this->callback(function(Payload $payload){
                return $payload->body === '' && $payload->header === TcpWorkerInterface::TCP_CLOSE;
            }));

        $this->tcpWorker->close();
    }

    public function testRespond()
    {
        $this->worker
            ->expects($this->once())
            ->method('respond')
            ->with($this->callback(function(Payload $payload){
                return $payload->body === 'foo' && $payload->header === TcpWorkerInterface::TCP_RESPOND;
            }));

        $this->tcpWorker->respond('foo');
    }

    public function testCloseRespondAndCloseConnection()
    {
        $this->worker
            ->expects($this->once())
            ->method('respond')
            ->with($this->callback(function(Payload $payload){
                return $payload->body === 'foo' && $payload->header === TcpWorkerInterface::TCP_RESPOND_CLOSE;
            }));

        $this->tcpWorker->respond('foo', true);
    }

    public function testGetsWorker()
    {
        $this->assertSame($this->worker, $this->tcpWorker->getWorker());
    }
}
