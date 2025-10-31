# Workerman 转发工作器

[English](README.md) | [中文](README.zh-CN.md)

[![最新版本](https://img.shields.io/packagist/v/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)
[![总下载量](https://img.shields.io/packagist/dt/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)
[![PHP 版本要求](https://img.shields.io/packagist/php-v/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)
[![许可证](https://img.shields.io/packagist/l/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)
[![构建状态](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![代码覆盖率](https://img.shields.io/codecov/c/github/tourze/php-monorepo/master.svg?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

适用于 Workerman 的连接转发工作器，支持在不同连接之间转发流量（TCP/UDP）。

## 功能特性

- 支持 TCP 和 UDP 连接转发
- 与 Workerman PHP 框架无缝协作
- 自动创建源连接和目标连接之间的管道
- 在设置转发目标前对连接进行缓冲
- 与现有 Workerman 应用程序轻松集成
- 内置多种负载均衡器，支持在多个目标间分发连接：
  - 轮询负载均衡器
  - 随机负载均衡器
  - 权重负载均衡器
  - IP 哈希负载均衡器
  - 最少连接负载均衡器

## 安装

```bash
composer require tourze/workerman-relay-worker
```

## 快速开始

```php
<?php

use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\RelayWorker;
use Workerman\Connection\ConnectionInterface;

// 创建一个监听 TCP 端口的转发工作器
$worker = new RelayWorker('tcp://0.0.0.0:8080');

// 设置 onConnect 回调来建立转发目标
$worker->onConnect = function(ConnectionInterface $connection) {
    // 为连接定义目标地址
    $address = Address::create('127.0.0.1', 9000, ProtocolFamily::TCP);
    RelayWorker::addTarget($connection, $address);
};

// 启动工作器
Worker::runAll();
```

## 使用说明

RelayWorker 类继承了 Workerman 的 Worker 类，提供了连接转发机制。你可以用它来：

- 创建 TCP-to-TCP、TCP-to-UDP、UDP-to-TCP 或 UDP-to-UDP 转发
- 在设置目标之前缓冲传入数据
- 异步处理连接

对于每个连接，你需要使用 `RelayWorker::addTarget()` 添加目标地址或使用 `RelayWorker::setTargets()` 设置多个目标。一旦设置目标，该连接上接收到的数据将通过配置的负载均衡器进行转发。

### 负载均衡

该包包含多种负载均衡策略，用于在多个目标之间分发连接：

```php
<?php

use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\LoadBalancer\LoadBalancerFactory;
use Tourze\Workerman\RelayWorker\RelayWorker;
use Workerman\Connection\ConnectionInterface;

$worker = new RelayWorker('tcp://0.0.0.0:8080');

// 定义多个目标服务器
$targets = [
    Address::create('192.168.1.10', 9000, ProtocolFamily::TCP),
    Address::create('192.168.1.11', 9000, ProtocolFamily::TCP),
    Address::create('192.168.1.12', 9000, ProtocolFamily::TCP),
];

$worker->onConnect = function(ConnectionInterface $connection) use ($targets) {
    // 设置多个目标和负载均衡器
    RelayWorker::setTargets($connection, $targets);
    
    // 使用轮询负载均衡器
    $loadBalancer = LoadBalancerFactory::createRoundRobin();
    RelayWorker::setLoadBalancer($connection, $loadBalancer);
};
```

#### 可用的负载均衡器

- **轮询 (Round Robin)**: 按顺序在所有目标之间均匀分发请求
- **随机 (Random)**: 为每个连接随机选择一个目标
- **权重 (Weighted)**: 根据分配的权重分发请求
- **IP 哈希 (IP Hash)**: 将来自同一 IP 的连接路由到同一目标
- **最少连接 (Least Connections)**: 路由到活跃连接最少的目标

## 依赖

- PHP 8.1+
- workerman/workerman: ^5.1
- tourze/workerman-connection-pipe: 0.0.*

## 许可证

MIT 许可证 (MIT)。详情请参阅 [许可证文件](LICENSE)。
