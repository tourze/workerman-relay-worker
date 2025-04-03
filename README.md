# Workerman Relay Worker

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)

A connection relay worker for Workerman that enables traffic forwarding between different connections (TCP/UDP).

## Features

- Support for TCP and UDP connection relaying
- Works with the Workerman PHP framework
- Automatic pipeline creation between source and target connections
- Connection buffering while setting up the relay
- Seamless integration with existing Workerman applications

## Installation

```bash
composer require tourze/workerman-relay-worker
```

## Quick Start

```php
<?php

use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\RelayWorker;
use Workerman\Connection\ConnectionInterface;

// Create a relay worker listening on a TCP port
$worker = new RelayWorker('tcp://0.0.0.0:8080');

// Set onConnect callback to establish the relay target
$worker->onConnect = function(ConnectionInterface $connection) {
    // Define target address for the connection
    $address = new Address('127.0.0.1', 9000, 'tcp');
    RelayWorker::setTarget($connection, $address);
};

// Start the worker
Worker::runAll();
```

## Usage

The RelayWorker class extends Workerman's Worker class and provides a connection relay mechanism. You can use it to:

- Create TCP-to-TCP, TCP-to-UDP, UDP-to-TCP, or UDP-to-UDP relays
- Buffer incoming data until a target is set
- Process connections asynchronously

For each connection, you need to set a target address using `RelayWorker::setTarget()`. Once set, any data received on that connection will be forwarded to the target address.

## Dependencies

- PHP 8.1+
- workerman/workerman: ^5.1
- tourze/workerman-connection-pipe: 0.0.*

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
