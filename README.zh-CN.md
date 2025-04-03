# Workerman 转发工作器

[English](README.md) | [中文](README.zh-CN.md)

[![最新版本](https://img.shields.io/packagist/v/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)
[![总下载量](https://img.shields.io/packagist/dt/tourze/workerman-relay-worker.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-relay-worker)

适用于 Workerman 的连接转发工作器，支持在不同连接之间转发流量（TCP/UDP）。

## 功能特性

- 支持 TCP 和 UDP 连接转发
- 与 Workerman PHP 框架无缝协作
- 自动创建源连接和目标连接之间的管道
- 在设置转发目标前对连接进行缓冲
- 与现有 Workerman 应用程序轻松集成

## 安装

```bash
composer require tourze/workerman-relay-worker
```

## 快速开始

```php
<?php

use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\RelayWorker;
use Workerman\Connection\ConnectionInterface;

// 创建一个监听 TCP 端口的转发工作器
$worker = new RelayWorker('tcp://0.0.0.0:8080');

// 设置 onConnect 回调来建立转发目标
$worker->onConnect = function(ConnectionInterface $connection) {
    // 为连接定义目标地址
    $address = new Address('127.0.0.1', 9000, 'tcp');
    RelayWorker::setTarget($connection, $address);
};

// 启动工作器
Worker::runAll();
```

## 使用说明

RelayWorker 类继承了 Workerman 的 Worker 类，提供了连接转发机制。你可以用它来：

- 创建 TCP-to-TCP、TCP-to-UDP、UDP-to-TCP 或 UDP-to-UDP 转发
- 在设置目标之前缓冲传入数据
- 异步处理连接

对于每个连接，你需要使用 `RelayWorker::setTarget()` 设置目标地址。一旦设置，该连接上接收到的任何数据都将被转发到目标地址。

## 依赖

- PHP 8.1+
- workerman/workerman: ^5.1
- tourze/workerman-connection-pipe: 0.0.*

## 许可证

MIT 许可证 (MIT)。详情请参阅 [许可证文件](LICENSE)。
