<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

enum TcpEvent: string
{
    case Connected = 'CONNECTED';
    case Data = 'DATA';
    case Close = 'CLOSE';
    case Unknown = 'UNKNOWN';
}
