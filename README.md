# Workerman Relay Worker

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)
[![PHP Version Require](https://img.shields.io/packagist/php-v/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)
[![License](https://img.shields.io/packagist/l/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo/master.svg?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

A connection relay worker for Workerman that enables traffic forwarding between different connections (TCP/UDP).

## Features

- Support for TCP and UDP connection relaying
- Works with the Workerman PHP framework
- Automatic pipeline creation between source and target connections
- Connection buffering while setting up the relay
- Seamless integration with existing Workerman applications
- Built-in load balancers for distributing connections across multiple targets:
  - Round Robin Load Balancer
  - Random Load Balancer
  - Weighted Load Balancer
  - IP Hash Load Balancer
  - Least Connections Load Balancer

## Installation

```bash
composer require tourze/workerman-relay-worker
```

## Quick Start

```php
<?php

use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\RelayWorker;
use Workerman\Connection\ConnectionInterface;

// Create a relay worker listening on a TCP port
$worker = new RelayWorker('tcp://0.0.0.0:8080');

// Set onConnect callback to establish the relay target
$worker->onConnect = function(ConnectionInterface $connection) {
    // Define target address for the connection
    $address = Address::create('127.0.0.1', 9000, ProtocolFamily::TCP);
    RelayWorker::addTarget($connection, $address);
};

// Start the worker
Worker::runAll();
```

## Usage

The RelayWorker class extends Workerman's Worker class and provides a connection relay mechanism. You can use it to:

- Create TCP-to-TCP, TCP-to-UDP, UDP-to-TCP, or UDP-to-UDP relays
- Buffer incoming data until a target is set
- Process connections asynchronously

For each connection, you need to add target addresses using `RelayWorker::addTarget()` or set multiple 
targets using `RelayWorker::setTargets()`. Once targets are set, data received on that connection will 
be forwarded using the configured load balancer.

### Load Balancing

The package includes several load balancing strategies for distributing connections across multiple targets:

```php
<?php

use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\LoadBalancer\LoadBalancerFactory;
use Tourze\Workerman\RelayWorker\RelayWorker;
use Workerman\Connection\ConnectionInterface;

$worker = new RelayWorker('tcp://0.0.0.0:8080');

// Define multiple target servers
$targets = [
    Address::create('192.168.1.10', 9000, ProtocolFamily::TCP),
    Address::create('192.168.1.11', 9000, ProtocolFamily::TCP),
    Address::create('192.168.1.12', 9000, ProtocolFamily::TCP),
];

$worker->onConnect = function(ConnectionInterface $connection) use ($targets) {
    // Set multiple targets and load balancer
    RelayWorker::setTargets($connection, $targets);
    
    // Use round robin load balancer
    $loadBalancer = LoadBalancerFactory::createRoundRobin();
    RelayWorker::setLoadBalancer($connection, $loadBalancer);
};
```

#### Available Load Balancers

- **Round Robin**: Distributes requests evenly across all targets in order
- **Random**: Randomly selects a target for each connection
- **Weighted**: Distributes requests based on assigned weights
- **IP Hash**: Routes connections from the same IP to the same target
- **Least Connections**: Routes to the target with the fewest active connections

## Dependencies

- PHP 8.1+
- workerman/workerman: ^5.1
- tourze/workerman-connection-pipe: 0.0.*

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
