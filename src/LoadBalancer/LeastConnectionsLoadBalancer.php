<?php

namespace Tourze\Workerman\RelayWorker\LoadBalancer;

use Tourze\Workerman\ConnectionPipe\Model\Address;

/**
 * 最少连接负载均衡器
 *
 * 选择活动连接数最少的目标服务器，适用于长连接和有状态服务
 */
class LeastConnectionsLoadBalancer implements LoadBalancerInterface
{
    /**
     * 连接计数器
     *
     * @var array<string, int>
     */
    private array $connectionCount = [];

    /**
     * 增加指定地址的连接计数
     *
     * @param Address $address 目标地址
     */
    public function incrementConnectionCount(Address $address): void
    {
        $key = $this->getAddressKey($address);
        if (!isset($this->connectionCount[$key])) {
            $this->connectionCount[$key] = 0;
        }
        $this->connectionCount[$key]++;
    }

    /**
     * 减少指定地址的连接计数
     *
     * @param Address $address 目标地址
     */
    public function decrementConnectionCount(Address $address): void
    {
        $key = $this->getAddressKey($address);
        if (isset($this->connectionCount[$key]) && $this->connectionCount[$key] > 0) {
            $this->connectionCount[$key]--;
        }
    }

    /**
     * 获取地址唯一标识
     *
     * @param Address $address 目标地址
     * @return string 地址唯一标识
     */
    private function getAddressKey(Address $address): string
    {
        return $address->getProtocol()->value . '://' . $address->getHost() . ':' . $address->getPort();
    }

    public function select(array $targets): Address
    {
        if (empty($targets)) {
            throw new \InvalidArgumentException('目标地址列表不能为空');
        }

        // 初始化为第一个目标
        $selectedTarget = $targets[0];
        $minConnections = $this->getConnectionCount($selectedTarget);

        // 遍历所有目标，寻找连接数最小的
        foreach ($targets as $target) {
            $count = $this->getConnectionCount($target);
            if ($count < $minConnections) {
                $minConnections = $count;
                $selectedTarget = $target;
            }
        }

        // 增加选中目标的连接计数
        $this->incrementConnectionCount($selectedTarget);

        return $selectedTarget;
    }

    /**
     * 获取指定地址的连接计数
     *
     * @param Address $address 目标地址
     * @return int 连接计数
     */
    private function getConnectionCount(Address $address): int
    {
        $key = $this->getAddressKey($address);
        return $this->connectionCount[$key] ?? 0;
    }
} 