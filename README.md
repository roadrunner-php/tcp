<p align="center">
    <a href="https://roadrunner.dev"><picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://github.com/roadrunner-server/.github/assets/8040338/e6bde856-4ec6-4a52-bd5b-bfe78736c1ff">
        <img alt="RoadRunner" src="https://github.com/roadrunner-server/.github/assets/8040338/040fb694-1dd3-4865-9d29-8e0748c2c8b8" style="width: 6in; display: block">
    </picture></a>
</p>

<p align="center">PHP worker for the RoadRunner TCP plugin</p>

<div align="center">

[![Documentation](https://img.shields.io/badge/Documentation-blue?style=for-the-badge&logo=gitbook&logoColor=white)](https://docs.roadrunner.dev/docs/plugins/tcp)
[![Sponsor](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsor&logo=githubsponsors&logoColor=white&color=%23EA4AAA)](https://github.com/sponsors/roadrunner-server)

[![Psalm Level](https://shepherd.dev/github/roadrunner-php/tcp/level.svg)](https://shepherd.dev/github/roadrunner-php/tcp)
[![Type Coverage](https://shepherd.dev/github/roadrunner-php/tcp/coverage.svg)](https://shepherd.dev/github/roadrunner-php/tcp)
[![Codecov](https://codecov.io/gh/roadrunner-php/tcp/branch/4.x/graph/badge.svg)](https://codecov.io/gh/roadrunner-php/tcp)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Froadrunner-php%2Ftcp%2F4.x)](https://dashboard.stryker-mutator.io/reports/github.com/roadrunner-php/tcp/4.x)

</div>

<br />

RoadRunner can serve raw TCP connections and pass their events to PHP workers.
This package provides the worker side: it receives connection events and data from the TCP servers configured
in [RoadRunner](https://github.com/roadrunner-server/roadrunner) and responds, keeps reading, or closes the connection.

> [!WARNING]
> The TCP plugin is not included in the standard RoadRunner v3 build. To use the `tcp:` plugin with RoadRunner v3,
> build the server yourself with [Velox](https://github.com/roadrunner-server/docs/blob/release/v3/customization/build.md)
> and add the `github.com/roadrunner-server/tcp/v6` plugin, or stay on RoadRunner v2025.
> At the time of the RoadRunner v3.0.0 release, `github.com/roadrunner-server/tcp/v6` has only beta tags.
> This does not affect the `tcp://` transport for RPC or worker relays.
> See the [TCP plugin documentation](https://github.com/roadrunner-server/docs/blob/release/v3/plugins/tcp.md).

## Get Started

### Installation

```bash
composer require roadrunner/tcp
```

[![PHP](https://img.shields.io/packagist/php-v/roadrunner/tcp.svg?style=flat-square&logo=php)](https://packagist.org/packages/roadrunner/tcp)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/roadrunner/tcp.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/roadrunner/tcp)
[![License](https://img.shields.io/packagist/l/roadrunner/tcp.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/roadrunner/tcp.svg?style=flat-square)](https://packagist.org/packages/roadrunner/tcp/stats)

### Application Server

The package contains only the PHP worker; the RoadRunner binary is installed separately.
You can use the convenient installer to download the latest available compatible version of RoadRunner assembly:

```bash
composer require roadrunner/cli --dev
```

To download latest version of application server (the standard build, without the TCP plugin):

```bash
vendor/bin/rr get
```

### Configuration

Declare the TCP servers and the worker pool in `.rr.yaml`:

```yaml
server:
  command: "php worker.php"

tcp:
  servers:
    smtp:
      addr: tcp://127.0.0.1:1025
      delimiter: "\r\n" # by default
    server2:
      addr: tcp://127.0.0.1:8889

  pool:
    num_workers: 2
    max_jobs: 0
    allocate_timeout: 60s
    destroy_timeout: 60s
```

If you have more than 1 worker in your pool TCP server will send received packets to different workers,
and if you need to collect data you have to use storage, that can be accessed by all workers, for example [RoadRunner Key Value](https://github.com/roadrunner-php/kv).

See the [TCP plugin documentation](https://docs.roadrunner.dev/docs/plugins/tcp) for all options.

### Writing a Worker

`worker.php` wraps the RoadRunner worker into `TcpWorker` and handles connection events in a loop:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Tcp\TcpWorker;
use Spiral\RoadRunner\Tcp\TcpResponse;
use Spiral\RoadRunner\Tcp\TcpEvent;

// Create new RoadRunner worker from global environment
$worker = Worker::create();

$tcpWorker = new TcpWorker($worker);

while ($request = $tcpWorker->waitRequest()) {

    try {
        if ($request->getEvent() === TcpEvent::Connected) {
            // You can close connection according your restrictions
            if ($request->getRemoteAddress() !== '127.0.0.1') {
                $tcpWorker->close();
                continue;
            }
            
            // -----------------
            
            // Or continue read data from server
            // By default, server closes connection if a worker doesn't send CONTINUE response 
            $tcpWorker->read();
            
            // -----------------
            
            // Or send response to the TCP connection, for example, to the SMTP client
            $tcpWorker->respond("220 mailamie \r\n");
            
        } elseif ($request->getEvent() === TcpEvent::Data) {
                   
            $body = $request->getBody();
            
            // ... handle request from TCP server [smtp]
            if ($request->getServer() === 'smtp') {

                // Send response and close connection
                $tcpWorker->respond('Access denied', TcpResponse::RespondClose);
               
            // ... handle request from TCP server [server2] 
            } elseif ($request->getServer() === 'server2') {
                
                // Send response to the TCP connection and wait for the next request
                $tcpWorker->respond(\json_encode([
                    'remote_addr' => $request->getRemoteAddress(),
                    'server' => $request->getServer(),
                    'uuid' => $request->getConnectionUuid(),
                    'body' => $request->getBody(),
                    'event' => $request->getEvent()
                ]));
            }
           
        // Handle closed connection event 
        } elseif ($request->getEvent() === TcpEvent::Close) {
            // Do something ...
            
            // You don't need to send response on closed connection
        }
        
    } catch (\Throwable $e) {
        $tcpWorker->respond("Something went wrong\r\n", TcpResponse::RespondClose);
        $worker->error((string)$e);
    }
}
```

<a href="https://spiral.dev/">
<img src="https://user-images.githubusercontent.com/773481/220979012-e67b74b5-3db1-41b7-bdb0-8a042587dedc.jpg" alt="try Spiral Framework" />
</a>

## Testing

```bash
composer tests
```
