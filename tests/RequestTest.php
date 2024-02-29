<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp\Tests;

use Spiral\RoadRunner\Tcp\Request;
use Spiral\RoadRunner\Tcp\TcpEvent;

final class RequestTest extends TestCase
{
    public function testGetRemoteAddress(): void
    {
        $request = new Request('127.0.0.1', TcpEvent::Close, '', '', '');

        $this->assertSame('127.0.0.1', $request->getRemoteAddress());
    }

    public function testGetEvent(): void
    {
        $request = new Request('', TcpEvent::Close, '', '', '');

        $this->assertSame(TcpEvent::Close, $request->getEvent());
    }

    public function testGetBody(): void
    {
        $request = new Request('', TcpEvent::Close, 'foo', '', '');

        $this->assertSame('foo', $request->getBody());
    }

    public function testGetConnectionUuid(): void
    {
        $request = new Request('', TcpEvent::Close, '', 'bar', '');

        $this->assertSame('bar', $request->getConnectionUuid());
    }

    public function testGetServer(): void
    {
        $request = new Request('', TcpEvent::Close, '', '', 'baz');

        $this->assertSame('baz', $request->getServer());
    }
}
