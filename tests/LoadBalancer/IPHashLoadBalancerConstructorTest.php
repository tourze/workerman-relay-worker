<?php

namespace Tourze\Workerman\RelayWorker\Tests\LoadBalancer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\LoadBalancer\IPHashLoadBalancer;
use Workerman\Connection\ConnectionInterface;

/**
 * IPHashLoadBalancer构造函数测试
 *
 * 专门测试构造函数和相关功能
 *
 * @internal
 */
#[CoversClass(IPHashLoadBalancer::class)]
final class IPHashLoadBalancerConstructorTest extends TestCase
{
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
     * 测试构造函数可以接受null连接
     */
    public function testConstructorAcceptsNullConnection(): void
    {
        $balancer = new IPHashLoadBalancer(null);

        // 使用反射获取私有属性
        $reflection = new \ReflectionClass($balancer);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);

        // 检查连接是否为null
        $this->assertNull($property->getValue($balancer));
    }

    /**
     * 测试构造函数默认参数
     */
    public function testConstructorDefaultParameter(): void
    {
        $balancer = new IPHashLoadBalancer();

        // 使用反射获取私有属性
        $reflection = new \ReflectionClass($balancer);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);

        // 检查连接是否为null
        $this->assertNull($property->getValue($balancer));
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
     * 测试不同IP地址的请求路由行为
     */
    public function testDifferentIPsRouting(): void
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

        // 验证选择的目标都在目标列表中
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
     * 测试IPv6地址解析
     */
    public function testIPv6AddressParsing(): void
    {
        // 测试IPv6地址
        $mockConnection = $this->createMock(ConnectionInterface::class);
        $mockConnection->method('getRemoteAddress')->willReturn('[2001:db8::1]:8080');

        $balancer = new IPHashLoadBalancer($mockConnection);

        // 使用反射获取私有方法
        $reflection = new \ReflectionClass($balancer);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        // IPv6地址应该返回第一个冒号前的部分
        $this->assertEquals('[2001', $method->invoke($balancer));
    }

    /**
     * 测试select方法基本功能
     */
    public function testSelect(): void
    {
        // 创建模拟连接
        $mockConnection = $this->createMock(ConnectionInterface::class);
        $mockConnection->method('getRemoteAddress')->willReturn('192.168.1.100:12345');

        $balancer = new IPHashLoadBalancer($mockConnection);
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);
    }
}
