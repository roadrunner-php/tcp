<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp\Tests\Unit;

use Spiral\RoadRunner\Tcp\Request;
use Spiral\RoadRunner\Tcp\TcpEvent;
use Testo\Assert;
use Testo\Test;

#[Test]
final class RequestTest
{
    public function testGetRemoteAddress(): void
    {
        $request = new Request('127.0.0.1', TcpEvent::Close, '', '', '');

        Assert::same($request->getRemoteAddress(), '127.0.0.1');
    }

    public function testGetEvent(): void
    {
        $request = new Request('', TcpEvent::Close, '', '', '');

        Assert::same($request->getEvent(), TcpEvent::Close);
    }

    public function testGetBody(): void
    {
        $request = new Request('', TcpEvent::Close, 'foo', '', '');

        Assert::same($request->getBody(), 'foo');
    }

    public function testGetConnectionUuid(): void
    {
        $request = new Request('', TcpEvent::Close, '', 'bar', '');

        Assert::same($request->getConnectionUuid(), 'bar');
    }

    public function testGetServer(): void
    {
        $request = new Request('', TcpEvent::Close, '', '', 'baz');

        Assert::same($request->getServer(), 'baz');
    }
}
