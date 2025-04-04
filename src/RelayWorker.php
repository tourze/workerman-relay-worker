<?php

namespace Tourze\Workerman\RelayWorker;

use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\ConnectionPipe\Pipe\AbstractConnectionPipe;
use Tourze\Workerman\ConnectionPipe\PipeFactory;
use Tourze\Workerman\RelayWorker\LoadBalancer\LoadBalancerInterface;
use Tourze\Workerman\RelayWorker\LoadBalancer\RoundRobinLoadBalancer;
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

    /**
     * @var WeakMap<ConnectionInterface, Address[]>|null
     */
    private static ?WeakMap $targetsMap = null;

    /**
     * @var WeakMap<ConnectionInterface, LoadBalancerInterface>|null
     */
    private static ?WeakMap $loadBalancerMap = null;

    private static function initMaps(): void
    {
        if (self::$targetsMap === null) {
            self::$targetsMap = new WeakMap();
        }

        if (self::$loadBalancerMap === null) {
            self::$loadBalancerMap = new WeakMap();
        }
    }

    /**
     * 获取连接的所有目标地址
     *
     * @param ConnectionInterface $connection 连接
     * @return Address[] 目标地址数组
     */
    public static function getTargets(ConnectionInterface $connection): array
    {
        self::initMaps();
        return self::$targetsMap[$connection] ?? [];
    }

    /**
     * 获取负载均衡器选择的目标地址
     *
     * @param ConnectionInterface $connection 连接
     * @return ?Address 选择的目标地址
     */
    public static function getTarget(ConnectionInterface $connection): ?Address
    {
        $targets = self::getTargets($connection);
        if (empty($targets)) {
            return null;
        }

        self::initMaps();
        $loadBalancer = self::$loadBalancerMap[$connection] ?? null;

        if ($loadBalancer === null) {
            // 默认使用轮询负载均衡器
            $loadBalancer = new RoundRobinLoadBalancer();
            self::$loadBalancerMap[$connection] = $loadBalancer;
        }

        return $loadBalancer->select($targets);
    }

    /**
     * 设置连接的多个目标地址
     *
     * @param ConnectionInterface $connection 连接
     * @param Address[] $addresses 目标地址数组
     */
    public static function setTargets(ConnectionInterface $connection, array $addresses): void
    {
        self::initMaps();
        self::$targetsMap[$connection] = $addresses;
    }

    /**
     * 为连接添加一个目标地址
     *
     * @param ConnectionInterface $connection 连接
     * @param Address $address 目标地址
     */
    public static function addTarget(ConnectionInterface $connection, Address $address): void
    {
        $targets = self::getTargets($connection);
        $targets[] = $address;
        self::setTargets($connection, $targets);
    }

    /**
     * 设置连接的负载均衡器
     *
     * @param ConnectionInterface $connection 连接
     * @param LoadBalancerInterface $loadBalancer 负载均衡器
     */
    public static function setLoadBalancer(ConnectionInterface $connection, LoadBalancerInterface $loadBalancer): void
    {
        self::initMaps();
        self::$loadBalancerMap[$connection] = $loadBalancer;
    }

    /**
     * @var WeakMap<ConnectionInterface, string>|null
     */
    private static ?WeakMap $bufferMap = null;

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
        self::$bufferMap[$connection] = $buffer;
    }

    private static function popBuffer(ConnectionInterface $connection): string
    {
        self::initBufferMap();
        $buffer = self::$bufferMap[$connection] ?? '';
        unset(self::$bufferMap[$connection]);
        return $buffer;
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
