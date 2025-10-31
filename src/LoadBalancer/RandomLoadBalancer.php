<?php

namespace Tourze\Workerman\RelayWorker\LoadBalancer;

use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\Exception\NoAvailableWorkersException;

/**
 * 随机负载均衡器
 *
 * 随机选择目标服务器，适用于短连接和无状态服务
 */
class RandomLoadBalancer implements LoadBalancerInterface
{
    public function select(array $targets): Address
    {
        if ([] === $targets) {
            throw new NoAvailableWorkersException('目标地址列表不能为空');
        }

        // 随机选择一个索引
        $index = array_rand($targets);

        // 返回选中的目标
        return $targets[$index];
    }
}
