<?php

namespace Tourze\Workerman\RelayWorker\LoadBalancer;

use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\Exception\InvalidWeightException;
use Tourze\Workerman\RelayWorker\Exception\NoAvailableWorkersException;

/**
 * 权重负载均衡器
 *
 * 根据目标服务器的权重进行选择，权重大的服务器被选中的概率大
 */
class WeightedLoadBalancer implements LoadBalancerInterface
{
    /**
     * 地址权重配置
     *
     * @var array<string, int>
     */
    private array $weights = [];

    /**
     * 设置目标地址的权重
     *
     * @param Address $address 目标地址
     * @param int $weight 权重值(>=1)
     */
    public function setWeight(Address $address, int $weight): void
    {
        if ($weight < 1) {
            throw new InvalidWeightException('权重值必须大于等于1');
        }

        $key = $this->getAddressKey($address);
        $this->weights[$key] = $weight;
    }

    /**
     * 获取目标地址的权重
     *
     * @param Address $address 目标地址
     * @return int 权重值
     */
    public function getWeight(Address $address): int
    {
        $key = $this->getAddressKey($address);
        return $this->weights[$key] ?? 1; // 默认权重为1
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

    /**
     * @inheritDoc
     */
    public function select(array $targets): Address
    {
        if (empty($targets)) {
            throw new NoAvailableWorkersException('目标地址列表不能为空');
        }

        // 总权重
        $totalWeight = 0;
        // 权重映射表
        $weightMap = [];

        // 计算总权重
        foreach ($targets as $target) {
            $weight = $this->getWeight($target);
            $totalWeight += $weight;
            $weightMap[] = [
                'target' => $target,
                'weight' => $weight
            ];
        }

        // 产生一个随机值
        $randomValue = mt_rand(1, $totalWeight);

        // 根据随机值选择目标
        $currentWeight = 0;
        foreach ($weightMap as $item) {
            $currentWeight += $item['weight'];
            if ($randomValue <= $currentWeight) {
                return $item['target'];
            }
        }

        // 理论上不会到这里，但为了安全起见
        return $targets[0];
    }
}
