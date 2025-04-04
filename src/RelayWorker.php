<?php

namespace Tourze\Workerman\RelayWorker;

use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\ConnectionPipe\Pipe\AbstractConnectionPipe;
use Tourze\Workerman\ConnectionPipe\PipeFactory;
use WeakMap;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\AsyncUdpConnection;
use Workerman\Connection\ConnectionInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\UdpConnection;
use Workerman\Worker;

/**
 * 转发服务
 */
class RelayWorker extends Worker
{
    public function __construct(string $socketName)
    {
        parent::__construct($socketName);

        $this->onMessage = $this->onMessage(...);
    }

    private static ?WeakMap $targetMap = null;

    private static function initTargetMap(): void
    {
        if (self::$targetMap === null) {
            self::$targetMap = new WeakMap();
        }
    }

    public static function getTarget(ConnectionInterface $connection): ?Address
    {
        self::initTargetMap();
        return self::$targetMap[$connection] ?? null;
    }

    public static function setTarget(ConnectionInterface $connection, Address $address): void
    {
        self::initTargetMap();
        self::$targetMap[$connection] = $address;
    }

    private static WeakMap $bufferMap;

    private static function initBufferMap(): void
    {
        if (self::$bufferMap === null) {
            self::$bufferMap = new WeakMap();
        }
    }

    private static function appendBuffer(ConnectionInterface $connection, string $buffer): void
    {
        self::initBufferMap();
        if (isset(self::$bufferMap[$connection])) {
            $buffer = self::$bufferMap[$connection] . $buffer;
        }
        self::$targetMap[$connection] = $buffer;
    }

    private static function popBuffer(ConnectionInterface $connection): string
    {
        self::initBufferMap();
        return self::$targetMap[$connection] ?? '';
    }

    protected function onMessage(ConnectionInterface $connection, string $buffer): void
    {
        if (strlen($buffer) === 0) {
            return;
        }

        $target = self::getTarget($connection);
        // 如果还没有转发目标，我们先暂存起来数据
        if ($target === null) {
            self::appendBuffer($connection, $buffer);
            return;
        }

        // 创建目标连接
        if ($target->getProtocol() === ProtocolFamily::TCP) {
            $targetConnection = new AsyncTcpConnection("tcp://{$target->getHost()}:{$target->getPort()}");
        } else {
            $targetConnection = new AsyncUdpConnection("udp://{$target->getHost()}:{$target->getPort()}");
        }

        // 创建从客户端到目标服务器的管道
        $clientToTarget = $this->createPipeline($connection, $targetConnection);
        $clientToTarget->pipe();

        // 创建从目标服务器到客户端的管道
        $targetToClient = $this->createPipeline($targetConnection, $connection);
        $targetToClient->pipe();

        // 连接目标服务器
        $targetConnection->connect();
        // 转发当前已收到的数据
        $clientToTarget->forward(self::popBuffer($connection) . $buffer);
    }

    private function createPipeline(ConnectionInterface $source, ConnectionInterface $target): AbstractConnectionPipe
    {
        if ($source instanceof TcpConnection) {
            if ($target instanceof TcpConnection) {
                return PipeFactory::createTcpToTcp($source, $target);
            }
            if ($target instanceof UdpConnection) {
                return PipeFactory::createTcpToUdp($source, $target);
            }
        }
        if ($source instanceof UdpConnection) {
            if ($target instanceof TcpConnection) {
                return PipeFactory::createUdpToTcp($source, $target);
            }
            if ($target instanceof UdpConnection) {
                return PipeFactory::createUdpToUdp($source, $target);
            }
        }
        throw new \RuntimeException('找不到合适的转发逻辑');
    }
}
