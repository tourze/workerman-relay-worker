<?php

namespace Tourze\Workerman\RelayWorker\Tests\LoadBalancer;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\LoadBalancer\IPHashLoadBalancer;
use Workerman\Connection\ConnectionInterface;

/**
 * IP哈希负载均衡器测试
 */
class IPHashLoadBalancerTest extends TestCase
{
    /**
     * 测试空目标列表抛出异常
     */
    public function testEmptyTargetsThrowsException(): void
    {
        $balancer = new IPHashLoadBalancer();

        $this->expectException(\InvalidArgumentException::class);
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
        for ($i = 0; $i < 6; $i++) {
            $selected = $balancer->select($targets);
            foreach ($targets as $index => $target) {
                if ($selected->getHost() === $target->getHost() && 
                    $selected->getPort() === $target->getPort()) {
                    $selectedIndices[] = $index;
                    break;
                }
            }
        }

        // 验证索引序列是否符合轮询模式: 0,1,0,1,0,1
        $this->assertEquals([0, 1, 0, 1, 0, 1], $selectedIndices);
    }

    /**
     * 测试构造函数设置连接
     */
    public function testConstructorSetsConnection(): void
    {
        // 创建模拟连接
        $mockConnection = $this->createMock(ConnectionInterface::class);
        $mockConnection->method('getRemoteAddress')->willReturn('192.168.1.100:12345');

        $balancer = new IPHashLoadBalancer($mockConnection);

        // 使用反射获取私有属性
        $reflection = new \ReflectionClass($balancer);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);

        // 检查连接是否正确设置
        $this->assertSame($mockConnection, $property->getValue($balancer));
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
     * 测试相同IP地址的请求被路由到相同的目标
     */
    public function testSameIPRoutesToSameTarget(): void
    {
        // 创建模拟连接，返回相同的IP地址
        $mockConnection = $this->createMock(ConnectionInterface::class);
        $mockConnection->method('getRemoteAddress')->willReturn('192.168.1.100:12345');

        $balancer = new IPHashLoadBalancer($mockConnection);

        $targets = [
            Address::create('192.168.1.1', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.2', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.3', 8080, ProtocolFamily::TCP),
        ];

        // 连续多次选择，应该始终选择相同的目标
        $selected1 = $balancer->select($targets);
        $selected2 = $balancer->select($targets);
        $selected3 = $balancer->select($targets);

        $this->assertSame($selected1, $selected2);
        $this->assertSame($selected2, $selected3);
    }

    /**
     * 测试不同IP地址的请求可能被路由到不同的目标
     */
    public function testDifferentIPsCanRouteToSameTarget(): void
    {
        // 创建两个返回不同IP地址的模拟连接
        $mockConnection1 = $this->createMock(ConnectionInterface::class);
        $mockConnection1->method('getRemoteAddress')->willReturn('192.168.1.101:12345');

        $mockConnection2 = $this->createMock(ConnectionInterface::class);
        $mockConnection2->method('getRemoteAddress')->willReturn('192.168.1.102:12345');

        $balancer1 = new IPHashLoadBalancer($mockConnection1);
        $balancer2 = new IPHashLoadBalancer($mockConnection2);

        $targets = [
            Address::create('192.168.1.1', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.2', 8080, ProtocolFamily::TCP),
        ];

        $selected1 = $balancer1->select($targets);
        $selected2 = $balancer2->select($targets);

        // 注意：这个测试不确定，因为两个不同的IP哈希后可能映射到相同的目标
        // 所以我们只是确保它不会抛出异常并正常运行
        $this->assertContains($selected1, $targets);
        $this->assertContains($selected2, $targets);
    }

    /**
     * 测试getClientIp方法
     */
    public function testGetClientIp(): void
    {
        // 创建一个模拟连接
        $mockConnection = $this->createMock(ConnectionInterface::class);
        $mockConnection->method('getRemoteAddress')->willReturn('192.168.1.100:12345');

        $balancer = new IPHashLoadBalancer($mockConnection);

        // 使用反射获取私有方法
        $reflection = new \ReflectionClass($balancer);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        // 检查是否正确解析了IP地址
        $this->assertEquals('192.168.1.100', $method->invoke($balancer));
    }

    /**
     * 测试客户端IP为空时的默认值
     */
    public function testDefaultIpWhenEmpty(): void
    {
        // 创建一个返回空地址的模拟连接
        $mockConnection = $this->createMock(ConnectionInterface::class);
        $mockConnection->method('getRemoteAddress')->willReturn('');

        $balancer = new IPHashLoadBalancer($mockConnection);

        // 使用反射获取私有方法
        $reflection = new \ReflectionClass($balancer);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        // 检查是否使用了默认IP
        $this->assertEquals('127.0.0.1', $method->invoke($balancer));
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
}
