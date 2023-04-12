<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Tcp;

enum TcpResponse: string
{
    case Close = 'CLOSE';
    case Respond = 'WRITE';
    case RespondClose = 'WRITECLOSE';
    case Read = 'CONTINUE';
}
