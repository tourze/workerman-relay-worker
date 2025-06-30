<?php

namespace Tourze\Workerman\RelayWorker\LoadBalancer;

use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\Exception\NoAvailableWorkersException;

/**
 * 轮询负载均衡器
 *
 * 依次选择目标服务器，适用于服务器性能相近的场景
 */
class RoundRobinLoadBalancer implements LoadBalancerInterface
{
    /**
     * 当前索引
     *
     * @var int
     */
    private int $currentIndex = 0;

    public function select(array $targets): Address
    {
        if (empty($targets)) {
            throw new NoAvailableWorkersException('目标地址列表不能为空');
        }

        // 计算实际索引
        $index = $this->currentIndex % count($targets);

        // 更新索引，如果即将溢出则重置
        if ($this->currentIndex === PHP_INT_MAX) {
            $this->currentIndex = 0;
        } else {
            $this->currentIndex++;
        }

        // 返回选中的目标
        return $targets[$index];
    }
}
