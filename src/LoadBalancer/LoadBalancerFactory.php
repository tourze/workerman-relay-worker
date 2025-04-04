<?php

namespace Tourze\Workerman\RelayWorker\LoadBalancer;

use Workerman\Connection\ConnectionInterface;

/**
 * 负载均衡器工厂
 */
class LoadBalancerFactory
{
    /**
     * 创建轮询负载均衡器
     *
     * @return LoadBalancerInterface
     */
    public static function createRoundRobin(): LoadBalancerInterface
    {
        return new RoundRobinLoadBalancer();
    }

    /**
     * 创建随机负载均衡器
     *
     * @return LoadBalancerInterface
     */
    public static function createRandom(): LoadBalancerInterface
    {
        return new RandomLoadBalancer();
    }

    /**
     * 创建最少连接负载均衡器
     *
     * @return LoadBalancerInterface
     */
    public static function createLeastConnections(): LoadBalancerInterface
    {
        return new LeastConnectionsLoadBalancer();
    }

    /**
     * 创建权重负载均衡器
     *
     * @return LoadBalancerInterface
     */
    public static function createWeighted(): LoadBalancerInterface
    {
        return new WeightedLoadBalancer();
    }

    /**
     * 创建IP哈希负载均衡器
     *
     * @param ConnectionInterface|null $connection 客户端连接
     * @return LoadBalancerInterface
     */
    public static function createIPHash(?ConnectionInterface $connection = null): LoadBalancerInterface
    {
        return new IPHashLoadBalancer($connection);
    }
}
