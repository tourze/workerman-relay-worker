<?php

namespace Tourze\Workerman\RelayWorker\Tests\LoadBalancer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RelayWorker\LoadBalancer\IPHashLoadBalancer;
use Tourze\Workerman\RelayWorker\LoadBalancer\LeastConnectionsLoadBalancer;
use Tourze\Workerman\RelayWorker\LoadBalancer\LoadBalancerFactory;
use Tourze\Workerman\RelayWorker\LoadBalancer\LoadBalancerInterface;
use Tourze\Workerman\RelayWorker\LoadBalancer\RandomLoadBalancer;
use Tourze\Workerman\RelayWorker\LoadBalancer\RoundRobinLoadBalancer;
use Tourze\Workerman\RelayWorker\LoadBalancer\WeightedLoadBalancer;
use Workerman\Connection\ConnectionInterface;

/**
 * 负载均衡器工厂测试
 *
 * @internal
 */
#[CoversClass(LoadBalancerFactory::class)]
final class LoadBalancerFactoryTest extends TestCase
{
    /**
     * 测试创建轮询负载均衡器
     */
    public function testCreateRoundRobin(): void
    {
        $balancer = LoadBalancerFactory::createRoundRobin();

        $this->assertInstanceOf(LoadBalancerInterface::class, $balancer);
        $this->assertInstanceOf(RoundRobinLoadBalancer::class, $balancer);
    }

    /**
     * 测试创建随机负载均衡器
     */
    public function testCreateRandom(): void
    {
        $balancer = LoadBalancerFactory::createRandom();

        $this->assertInstanceOf(LoadBalancerInterface::class, $balancer);
        $this->assertInstanceOf(RandomLoadBalancer::class, $balancer);
    }

    /**
     * 测试创建最少连接负载均衡器
     */
    public function testCreateLeastConnections(): void
    {
        $balancer = LoadBalancerFactory::createLeastConnections();

        $this->assertInstanceOf(LoadBalancerInterface::class, $balancer);
        $this->assertInstanceOf(LeastConnectionsLoadBalancer::class, $balancer);
    }

    /**
     * 测试创建权重负载均衡器
     */
    public function testCreateWeighted(): void
    {
        $balancer = LoadBalancerFactory::createWeighted();

        $this->assertInstanceOf(LoadBalancerInterface::class, $balancer);
        $this->assertInstanceOf(WeightedLoadBalancer::class, $balancer);
    }

    /**
     * 测试创建IP哈希负载均衡器（无连接）
     */
    public function testCreateIPHashWithoutConnection(): void
    {
        $balancer = LoadBalancerFactory::createIPHash();

        $this->assertInstanceOf(LoadBalancerInterface::class, $balancer);
        $this->assertInstanceOf(IPHashLoadBalancer::class, $balancer);
    }

    /**
     * 测试创建IP哈希负载均衡器（有连接）
     */
    public function testCreateIPHashWithConnection(): void
    {
        // 创建模拟连接
        $mockConnection = $this->createMock(ConnectionInterface::class);

        $balancer = LoadBalancerFactory::createIPHash($mockConnection);

        $this->assertInstanceOf(LoadBalancerInterface::class, $balancer);
        $this->assertInstanceOf(IPHashLoadBalancer::class, $balancer);

        // 使用反射检查连接是否正确设置
        $reflection = new \ReflectionClass($balancer);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);

        $this->assertSame($mockConnection, $property->getValue($balancer));
    }
}
