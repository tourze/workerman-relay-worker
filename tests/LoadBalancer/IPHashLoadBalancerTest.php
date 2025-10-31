<?php

namespace Tourze\Workerman\RelayWorker\Tests\LoadBalancer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\Exception\NoAvailableWorkersException;
use Tourze\Workerman\RelayWorker\LoadBalancer\IPHashLoadBalancer;
use Workerman\Connection\ConnectionInterface;

/**
 * IP哈希负载均衡器测试
 *
 * @internal
 */
#[CoversClass(IPHashLoadBalancer::class)]
final class IPHashLoadBalancerTest extends TestCase
{
    /**
     * 测试空目标列表抛出异常
     */
    public function testEmptyTargetsThrowsException(): void
    {
        $balancer = new IPHashLoadBalancer();

        $this->expectException(NoAvailableWorkersException::class);
        $this->expectExceptionMessage('目标地址列表不能为空');

        $balancer->select([]);
    }

    /**
     * 测试单个目标总是被选中
     */
    public function testSingleTargetAlwaysSelected(): void
    {
        $balancer = new IPHashLoadBalancer();
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);

        // 再次选择，结果应该相同
        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);
    }

    /**
     * 测试无连接时降级为轮询策略
     */
    public function testFallbackToRoundRobinWhenNoConnection(): void
    {
        $balancer = new IPHashLoadBalancer();

        $targets = [
            Address::create('192.168.1.1', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.2', 8080, ProtocolFamily::TCP),
        ];

        // 创建一个数组来跟踪选择的索引
        $selectedIndices = [];

        // 多次选择，验证是否按照轮询方式选择
        for ($i = 0; $i < 6; ++$i) {
            $selected = $balancer->select($targets);
            foreach ($targets as $index => $target) {
                if ($selected->getHost() === $target->getHost()
                    && $selected->getPort() === $target->getPort()) {
                    $selectedIndices[] = $index;
                    break;
                }
            }
        }

        // 验证索引序列是否符合轮询模式: 0,1,0,1,0,1
        $this->assertEquals([0, 1, 0, 1, 0, 1], $selectedIndices);
    }

    /**
     * 测试设置连接方法
     */
    public function testSetConnection(): void
    {
        $balancer = new IPHashLoadBalancer();

        // 创建模拟连接
        $mockConnection = $this->createMock(ConnectionInterface::class);
        $mockConnection->method('getRemoteAddress')->willReturn('192.168.1.100:12345');

        $balancer->setConnection($mockConnection);

        // 使用反射获取私有属性
        $reflection = new \ReflectionClass($balancer);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);

        // 检查连接是否正确设置
        $this->assertSame($mockConnection, $property->getValue($balancer));
    }

    /**
     * 测试没有连接时的默认IP
     */
    public function testDefaultIpWhenNoConnection(): void
    {
        $balancer = new IPHashLoadBalancer();

        // 使用反射获取私有方法
        $reflection = new \ReflectionClass($balancer);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        // 检查是否使用了默认IP
        $this->assertEquals('127.0.0.1', $method->invoke($balancer));
    }

    /**
     * 测试select方法基本功能
     */
    public function testSelect(): void
    {
        $balancer = new IPHashLoadBalancer();

        // 测试单个目标
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);
        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);

        // 测试多个目标
        $targets = [
            Address::create('192.168.1.1', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.2', 8080, ProtocolFamily::TCP),
        ];

        $selected = $balancer->select($targets);
        $this->assertContains($selected, $targets);
    }
}
