<?php
declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

use JetBrains\PhpStorm\Immutable;

#[Immutable]
final class Request
{
    /**
     * @var string
     */
    public string $remoteAddr = '127.0.0.1';

    /**
     * @var string
     */
    public string $body = '';

    /**
     * @var string
     */
    public string $server = '';

    /**
     * @var string
     */
    public string $connectionId = '';
}