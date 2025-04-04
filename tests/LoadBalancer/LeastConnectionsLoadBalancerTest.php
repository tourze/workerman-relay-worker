<?php

namespace Tourze\Workerman\RelayWorker\Tests\LoadBalancer;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\LoadBalancer\LeastConnectionsLoadBalancer;

/**
 * 最少连接负载均衡器测试
 */
class LeastConnectionsLoadBalancerTest extends TestCase
{
    /**
     * 测试空目标列表抛出异常
     */
    public function testEmptyTargetsThrowsException(): void
    {
        $balancer = new LeastConnectionsLoadBalancer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('目标地址列表不能为空');

        $balancer->select([]);
    }

    /**
     * 测试单个目标总是被选中
     */
    public function testSingleTargetAlwaysSelected(): void
    {
        $balancer = new LeastConnectionsLoadBalancer();
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);

        // 再次选择，结果应该相同
        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);
    }

    /**
     * 测试初始连接计数为0
     */
    public function testInitialConnectionCountIsZero(): void
    {
        $balancer = new LeastConnectionsLoadBalancer();
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        // 使用反射获取私有方法
        $reflection = new \ReflectionClass($balancer);
        $method = $reflection->getMethod('getConnectionCount');
        $method->setAccessible(true);

        // 检查初始连接计数为0
        $this->assertEquals(0, $method->invoke($balancer, $target));
    }

    /**
     * 测试增加连接计数
     */
    public function testIncrementConnectionCount(): void
    {
        $balancer = new LeastConnectionsLoadBalancer();
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        // 使用反射获取私有方法
        $reflection = new \ReflectionClass($balancer);
        $method = $reflection->getMethod('getConnectionCount');
        $method->setAccessible(true);

        // 增加连接计数前为0
        $this->assertEquals(0, $method->invoke($balancer, $target));

        // 增加连接计数
        $balancer->incrementConnectionCount($target);
        $this->assertEquals(1, $method->invoke($balancer, $target));

        // 再次增加
        $balancer->incrementConnectionCount($target);
        $this->assertEquals(2, $method->invoke($balancer, $target));
    }

    /**
     * 测试减少连接计数
     */
    public function testDecrementConnectionCount(): void
    {
        $balancer = new LeastConnectionsLoadBalancer();
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        // 使用反射获取私有方法
        $reflection = new \ReflectionClass($balancer);
        $method = $reflection->getMethod('getConnectionCount');
        $method->setAccessible(true);

        // 增加连接计数
        $balancer->incrementConnectionCount($target);
        $balancer->incrementConnectionCount($target);
        $this->assertEquals(2, $method->invoke($balancer, $target));

        // 减少连接计数
        $balancer->decrementConnectionCount($target);
        $this->assertEquals(1, $method->invoke($balancer, $target));

        // 再次减少
        $balancer->decrementConnectionCount($target);
        $this->assertEquals(0, $method->invoke($balancer, $target));

        // 连接计数已为0，再减少也应该保持为0
        $balancer->decrementConnectionCount($target);
        $this->assertEquals(0, $method->invoke($balancer, $target));
    }

    /**
     * 测试最少连接策略选择
     */
    public function testLeastConnectionsSelection(): void
    {
        $balancer = new LeastConnectionsLoadBalancer();

        $target1 = Address::create('192.168.1.1', 8080, ProtocolFamily::TCP);
        $target2 = Address::create('192.168.1.2', 8080, ProtocolFamily::TCP);
        $target3 = Address::create('192.168.1.3', 8080, ProtocolFamily::TCP);

        $targets = [$target1, $target2, $target3];

        // 设置不同的连接数
        $balancer->incrementConnectionCount($target1); // 连接数=1
        $balancer->incrementConnectionCount($target1); // 连接数=2
        $balancer->incrementConnectionCount($target2); // 连接数=1

        // target3的连接数为0，应该被选中
        $selected = $balancer->select($targets);
        $this->assertSame($target3, $selected);

        // target3被选中后，连接数增加为1
        // target2的连接数为1，target3的连接数为1
        // 由于target2和target3连接数相同，应该选择在数组中先出现的target2
        $selected = $balancer->select($targets);
        $this->assertSame($target2, $selected);
    }

    /**
     * 测试所有目标有相同连接数时的选择
     */
    public function testEqualConnectionCountsSelection(): void
    {
        $balancer = new LeastConnectionsLoadBalancer();

        $target1 = Address::create('192.168.1.1', 8080, ProtocolFamily::TCP);
        $target2 = Address::create('192.168.1.2', 8080, ProtocolFamily::TCP);
        $target3 = Address::create('192.168.1.3', 8080, ProtocolFamily::TCP);

        $targets = [$target1, $target2, $target3];

        // 所有目标的连接数都是0，应该选择第一个目标
        $selected = $balancer->select($targets);
        $this->assertSame($target1, $selected);

        // 手动设置所有目标的连接数为相同值
        $balancer->incrementConnectionCount($target2);
        $balancer->incrementConnectionCount($target3);

        // 目标1已选过一次，所以连接数为1
        // 目标2和目标3的连接数也设置为1
        // 由于连接数相同，应该选择数组中的第一个目标
        $selected = $balancer->select($targets);
        $this->assertSame($target1, $selected);
    }

    /**
     * 测试地址键生成
     */
    public function testAddressKey(): void
    {
        $balancer = new LeastConnectionsLoadBalancer();

        // 使用反射获取私有方法
        $reflection = new \ReflectionClass($balancer);
        $method = $reflection->getMethod('getAddressKey');
        $method->setAccessible(true);

        $tcpAddress = Address::create('192.168.1.1', 8080, ProtocolFamily::TCP);
        $udpAddress = Address::create('192.168.1.1', 8080, ProtocolFamily::UDP);

        // 检查TCP和UDP地址生成不同的键
        $tcpKey = $method->invoke($balancer, $tcpAddress);
        $udpKey = $method->invoke($balancer, $udpAddress);

        $this->assertNotEquals($tcpKey, $udpKey);
        $this->assertStringContainsString('tcp://', $tcpKey);
        $this->assertStringContainsString('udp://', $udpKey);
        $this->assertStringContainsString('192.168.1.1:8080', $tcpKey);
        $this->assertStringContainsString('192.168.1.1:8080', $udpKey);
    }
}
