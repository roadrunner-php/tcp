# RoadRunner TCP Plugin

[![Latest Stable Version](https://poser.pugx.org/spiral/roadrunner-tcp/version)](https://packagist.org/packages/spiral/roadrunner-tcp)
[![Build Status](https://github.com/spiral/roadrunner-tcp/workflows/build/badge.svg)](https://github.com/spiral/roadrunner-tcp/actions)
[![Codecov](https://codecov.io/gh/spiral/roadrunner-tcp/branch/master/graph/badge.svg)](https://codecov.io/gh/spiral/roadrunner-tcp/)

RoadRunner is an open-source (MIT licensed) high-performance PHP application server, load balancer, and process manager.
It supports running as a service with the ability to extend its functionality on a per-project basis.

RoadRunner includes TCP server and can be used to replace classic TCP setup with much greater performance and flexibility.

<p align="center">
	<a href="https://roadrunner.dev/"><b>Official Website</b></a> | 
	<a href="https://roadrunner.dev/docs"><b>Documentation</b></a>
</p>

This repository contains the codebase TCP PHP workers. Check [spiral/roadrunner](https://github.com/spiral/roadrunner)
to get application server.

## Installation

To install application server and TCP codebase:

```bash
$ composer require spiral/roadrunner-tcp
```

You can use the convenient installer to download the latest available compatible version of RoadRunner assembly:

```bash
$ composer require spiral/roadrunner-cli --dev
```

To download latest version of application server:

```bash
$ vendor/bin/rr get
```

## Usage

For example, such a configuration would be quite feasible to run:

```yaml
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
and if you need to collect data you have to use storage, that can be accessed by all workers, for example [RoadRunner Key Value](https://github.com/spiral/roadrunner-kv)

### Example

To init abstract RoadRunner worker:

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
        if ($request->event === TcpEvent::Connected) {
            // You can close connection according your restrictions
            if ($request->remoteAddr !== '127.0.0.1') {
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
            
        } elseif ($request->event === TcpEvent::Data) {
                   
            $body = $request->body;
            
            // ... handle request from TCP server [tcp_access_point_1]
            if ($request->server === 'tcp_access_point_1') {

                // Send response and close connection
                $tcpWorker->respond('Access denied', TcpResponse::RespondClose);
               
            // ... handle request from TCP server [server2] 
            } elseif ($request->server === 'server2') {
                
                // Send response to the TCP connection and wait for the next request
                $tcpWorker->respond(\json_encode([
                    'remote_addr' => $request->remoteAddr,
                    'server' => $request->server,
                    'uuid' => $request->connectionUuid,
                    'body' => $request->body,
                    'event' => $request->event
                ]));
            }
           
        // Handle closed connection event 
        } elseif ($request->event === TcpEvent::Close) {
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

## Testing:

This codebase is automatically tested via host repository - [spiral/roadrunner](https://github.com/spiral/roadrunner).

## License:

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained
by [Spiral Scout](https://spiralscout.com).
