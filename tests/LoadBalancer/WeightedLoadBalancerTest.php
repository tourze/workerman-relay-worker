<?php

namespace Tourze\Workerman\RelayWorker\Tests\LoadBalancer;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\LoadBalancer\WeightedLoadBalancer;

/**
 * 权重负载均衡器测试
 */
class WeightedLoadBalancerTest extends TestCase
{
    /**
     * 测试空目标列表抛出异常
     */
    public function testEmptyTargetsThrowsException(): void
    {
        $balancer = new WeightedLoadBalancer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('目标地址列表不能为空');

        $balancer->select([]);
    }

    /**
     * 测试单个目标总是被选中
     */
    public function testSingleTargetAlwaysSelected(): void
    {
        $balancer = new WeightedLoadBalancer();
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);

        // 设置权重后再次测试
        $balancer->setWeight($target, 5);
        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);
    }

    /**
     * 测试权重为负值时抛出异常
     */
    public function testNegativeWeightThrowsException(): void
    {
        $balancer = new WeightedLoadBalancer();
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('权重值必须大于等于1');

        $balancer->setWeight($target, 0);
    }

    /**
     * 测试默认权重为1
     */
    public function testDefaultWeightIsOne(): void
    {
        $balancer = new WeightedLoadBalancer();
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        $this->assertEquals(1, $balancer->getWeight($target));
    }

    /**
     * 测试设置和获取权重
     */
    public function testSetAndGetWeight(): void
    {
        $balancer = new WeightedLoadBalancer();
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        $balancer->setWeight($target, 10);
        $this->assertEquals(10, $balancer->getWeight($target));

        $balancer->setWeight($target, 5);
        $this->assertEquals(5, $balancer->getWeight($target));
    }

    /**
     * 测试不同权重的目标选择概率
     */
    public function testWeightedSelection(): void
    {
        $balancer = new WeightedLoadBalancer();

        $target1 = Address::create('192.168.1.1', 8080, ProtocolFamily::TCP);
        $target2 = Address::create('192.168.1.2', 8080, ProtocolFamily::TCP);

        // 设置不同的权重
        $balancer->setWeight($target1, 80);
        $balancer->setWeight($target2, 20);

        $targets = [$target1, $target2];
        $selections = [];
        $iterations = 1000;

        // 进行多次选择
        for ($i = 0; $i < $iterations; $i++) {
            $selected = $balancer->select($targets);
            $address = $selected->getHost() . ':' . $selected->getPort();
            $selections[$address] = ($selections[$address] ?? 0) + 1;
        }

        // 检查两个目标都被选中了
        $this->assertCount(2, $selections);

        // 检查权重为80的目标被选中的次数应该大约是权重为20的4倍
        // 由于随机性，我们允许一定的误差范围，比如±20%
        $ratio = $selections['192.168.1.1:8080'] / $selections['192.168.1.2:8080'];
        $this->assertGreaterThan(2.5, $ratio); // 4 * 0.8 - 0.7 = 2.5
        $this->assertLessThan(5.5, $ratio);    // 4 * 1.2 + 0.7 = 5.5
    }

    /**
     * 测试多个目标的权重选择
     */
    public function testMultipleTargetsWeightedSelection(): void
    {
        $balancer = new WeightedLoadBalancer();

        $targets = [
            Address::create('192.168.1.1', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.2', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.3', 8080, ProtocolFamily::TCP),
        ];

        // 设置不同的权重
        $balancer->setWeight($targets[0], 10);
        $balancer->setWeight($targets[1], 20);
        $balancer->setWeight($targets[2], 30);

        $selections = [];
        $iterations = 1000;

        // 进行多次选择
        for ($i = 0; $i < $iterations; $i++) {
            $selected = $balancer->select($targets);
            $address = $selected->getHost() . ':' . $selected->getPort();
            $selections[$address] = ($selections[$address] ?? 0) + 1;
        }

        // 检查三个目标都被选中了
        $this->assertCount(3, $selections);

        // 检查选择的大致比例符合权重比例 1:2:3
        $sum = $selections['192.168.1.1:8080'] + $selections['192.168.1.2:8080'] + $selections['192.168.1.3:8080'];
        $ratio1 = $selections['192.168.1.1:8080'] / $sum;
        $ratio2 = $selections['192.168.1.2:8080'] / $sum;
        $ratio3 = $selections['192.168.1.3:8080'] / $sum;

        // 预期比例：10/(10+20+30)=1/6, 20/(10+20+30)=1/3, 30/(10+20+30)=1/2
        // 允许±0.1的误差
        $this->assertGreaterThan(1 / 6 - 0.1, $ratio1);
        $this->assertLessThan(1 / 6 + 0.1, $ratio1);

        $this->assertGreaterThan(1 / 3 - 0.1, $ratio2);
        $this->assertLessThan(1 / 3 + 0.1, $ratio2);

        $this->assertGreaterThan(1 / 2 - 0.1, $ratio3);
        $this->assertLessThan(1 / 2 + 0.1, $ratio3);
    }
}
