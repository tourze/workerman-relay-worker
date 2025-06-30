<?php

namespace Tourze\Workerman\RelayWorker\LoadBalancer;

use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\Exception\NoAvailableWorkersException;
use Workerman\Connection\ConnectionInterface;

/**
 * IP哈希负载均衡器
 *
 * 根据客户端IP地址哈希值选择服务器，保证同一IP的请求总是发送到同一服务器
 */
class IPHashLoadBalancer implements LoadBalancerInterface
{
    /**
     * 客户端连接
     *
     * @var ConnectionInterface|null
     */
    private ?ConnectionInterface $connection = null;

    /**
     * 用于无连接时的轮询负载均衡器实例
     */
    private static ?RoundRobinLoadBalancer $roundRobinInstance = null;

    /**
     * 构造函数
     *
     * @param ConnectionInterface|null $connection 客户端连接
     */
    public function __construct(?ConnectionInterface $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * 设置客户端连接
     *
     * @param ConnectionInterface $connection 客户端连接
     */
    public function setConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
    }

    public function select(array $targets): Address
    {
        if (empty($targets)) {
            throw new NoAvailableWorkersException('目标地址列表不能为空');
        }

        if ($this->connection === null) {
            // 如果没有连接信息，退化为轮询
            if (self::$roundRobinInstance === null) {
                self::$roundRobinInstance = new RoundRobinLoadBalancer();
            }
            return self::$roundRobinInstance->select($targets);
        }

        // 获取客户端IP
        $clientIp = $this->getClientIp();

        // 使用IP的哈希值选择服务器
        $hash = crc32($clientIp);
        $index = abs($hash) % count($targets);

        return $targets[$index];
    }

    /**
     * 获取客户端IP地址
     *
     * @return string 客户端IP地址
     */
    private function getClientIp(): string
    {
        if ($this->connection === null) {
            return '127.0.0.1';
        }

        $remoteAddress = $this->connection->getRemoteAddress();
        if (empty($remoteAddress)) {
            return '127.0.0.1';
        }

        // 解析地址中的IP部分
        $parts = explode(':', $remoteAddress);
        return $parts[0] ?? '127.0.0.1';
    }
}
