<?php

namespace Tourze\Workerman\RelayWorker\LoadBalancer;

use Tourze\Workerman\ConnectionPipe\Model\Address;

/**
 * 负载均衡器接口
 */
interface LoadBalancerInterface
{
    /**
     * 从可用目标地址中选择一个
     *
     * @param Address[] $targets 可用目标地址
     *
     * @return Address 选择的目标地址
     */
    public function select(array $targets): Address;
}
