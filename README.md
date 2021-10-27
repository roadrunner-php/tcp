<p align="center">
 <img src="https://user-images.githubusercontent.com/796136/50286124-6f7f3780-046f-11e9-9f45-e8fedd4f786d.png" height="75px" alt="RoadRunner">
</p>

RoadRunner is an open-source (MIT licensed) high-performance PHP application server, load balancer, and process manager.
It supports running as a service with the ability to extend its functionality on a per-project basis.

RoadRunner includes TCP server and can be used to replace classic TCP setup
with much greater performance and flexib*ility.

<p align="center">
	<a href="https://roadrunner.dev/"><b>Official Website</b></a> | 
	<a href="https://roadrunner.dev/docs"><b>Documentation</b></a>
</p>

Repository:*
--------

This repository contains the codebase TCP PHP workers.
Check [spiral/roadrunner](https://github.com/spiral/roadrunner) to get application server.

Installation:
--------

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

Usage:
-------
For example, such a configuration would be quite feasible to run:

```yaml
tcp:
  servers:
    tcp_access_point_1:
      addr: tcp://127.0.0.1:7777
      delimiter: '\r\n'
    server2:
      addr: tcp://127.0.0.1:8889
      delimiter: '\n\r\.\n\r'

  pool:
    num_workers: 2
    max_jobs: 0
    allocate_timeout: 60s
    destroy_timeout: 60s
```

Example:
-------

To init abstract RoadRunner worker:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Tcp\TcpWorker;

// Create new RoadRunner worker from global environment
$worker = Worker::create();

$tcpWorker = new TcpWorker($worker);

while (true) {
    try {
        $request = $tcpWorker->waitRequest();
        
        $tcpWorker->respond(json_encode([
            'remote_addr' => $request->remoteAddr,
            'server' => $request->server,
            'uuid' => $request->connectionUuid,
            'body' => $request->body
        ]));
        
    } catch (\Throwable $e) {
        $tcpWorker->respond("Something went wrong\r\n");
        
        $worker->error((string)$e);
        
        continue;
    }
}
```

Testing:
--------

This codebase is automatically tested via host repository - [spiral/roadrunner](https://github.com/spiral/roadrunner).

License:
--------

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained
by [Spiral Scout](https://spiralscout.com).
