<?php

namespace Tourze\Workerman\RelayWorker\Tests\LoadBalancer;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\LoadBalancer\RoundRobinLoadBalancer;

/**
 * 轮询负载均衡器测试
 */
class RoundRobinLoadBalancerTest extends TestCase
{
    /**
     * 测试空目标列表抛出异常
     */
    public function testEmptyTargetsThrowsException(): void
    {
        $balancer = new RoundRobinLoadBalancer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('目标地址列表不能为空');

        $balancer->select([]);
    }

    /**
     * 测试单个目标总是被选中
     */
    public function testSingleTargetAlwaysSelected(): void
    {
        $balancer = new RoundRobinLoadBalancer();
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);

        // 再次选择，结果应该相同
        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);
    }

    /**
     * 测试轮询策略按顺序选择目标
     */
    public function testRoundRobinSelection(): void
    {
        $balancer = new RoundRobinLoadBalancer();

        $targets = [
            Address::create('192.168.1.1', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.2', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.3', 8080, ProtocolFamily::TCP),
        ];

        // 第一次选择应该是第一个目标
        $selected = $balancer->select($targets);
        $this->assertSame($targets[0], $selected);

        // 第二次选择应该是第二个目标
        $selected = $balancer->select($targets);
        $this->assertSame($targets[1], $selected);

        // 第三次选择应该是第三个目标
        $selected = $balancer->select($targets);
        $this->assertSame($targets[2], $selected);

        // 第四次选择应该再次回到第一个目标
        $selected = $balancer->select($targets);
        $this->assertSame($targets[0], $selected);
    }

    /**
     * 测试计数器溢出情况下的正确行为
     */
    public function testCounterOverflow(): void
    {
        $balancer = new RoundRobinLoadBalancer();

        // 设置私有属性 currentIndex 为 PHP_INT_MAX
        $reflection = new \ReflectionClass($balancer);
        $property = $reflection->getProperty('currentIndex');
        $property->setAccessible(true);
        $property->setValue($balancer, PHP_INT_MAX);

        $targets = [
            Address::create('192.168.1.1', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.2', 8080, ProtocolFamily::TCP),
        ];

        // 尽管计数器已达到最大值，但应该正确取模并选择目标
        $selected = $balancer->select($targets);
        $this->assertSame($targets[PHP_INT_MAX % 2], $selected);

        // 溢出后应继续正常工作
        $selected = $balancer->select($targets);
        $this->assertSame($targets[0], $selected);
    }
}
