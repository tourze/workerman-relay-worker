<?php

namespace Tourze\Workerman\RelayWorker\Tests\LoadBalancer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\RelayWorker\Exception\NoAvailableWorkersException;
use Tourze\Workerman\RelayWorker\LoadBalancer\RandomLoadBalancer;

/**
 * 随机负载均衡器测试
 *
 * @internal
 */
#[CoversClass(RandomLoadBalancer::class)]
final class RandomLoadBalancerTest extends TestCase
{
    /**
     * 测试空目标列表抛出异常
     */
    public function testEmptyTargetsThrowsException(): void
    {
        $balancer = new RandomLoadBalancer();

        $this->expectException(NoAvailableWorkersException::class);
        $this->expectExceptionMessage('目标地址列表不能为空');

        $balancer->select([]);
    }

    /**
     * 测试单个目标总是被选中
     */
    public function testSingleTargetAlwaysSelected(): void
    {
        $balancer = new RandomLoadBalancer();
        $target = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);

        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);

        // 再次选择，结果应该相同
        $selected = $balancer->select([$target]);
        $this->assertSame($target, $selected);
    }

    /**
     * 测试随机选择在范围内
     */
    public function testRandomSelectionInRange(): void
    {
        $balancer = new RandomLoadBalancer();

        $targets = [
            Address::create('192.168.1.1', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.2', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.3', 8080, ProtocolFamily::TCP),
        ];

        // 多次测试，确保选择的目标始终在目标列表中
        for ($i = 0; $i < 10; ++$i) {
            $selected = $balancer->select($targets);
            $this->assertContains($selected, $targets);
        }
    }

    /**
     * 测试随机性（虽然不能保证，但可以增加测试覆盖率）
     */
    public function testRandomness(): void
    {
        $balancer = new RandomLoadBalancer();

        $targets = [
            Address::create('192.168.1.1', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.2', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.3', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.4', 8080, ProtocolFamily::TCP),
            Address::create('192.168.1.5', 8080, ProtocolFamily::TCP),
        ];

        $selections = [];
        $iterations = 100;

        // 进行多次选择
        for ($i = 0; $i < $iterations; ++$i) {
            $selected = $balancer->select($targets);
            $address = $selected->getHost() . ':' . $selected->getPort();
            $selections[$address] = ($selections[$address] ?? 0) + 1;
        }

        // 检查是否至少有3个不同的目标被选中（在概率上很可能）
        $this->assertGreaterThanOrEqual(3, count($selections));

        // 检查不是每次都选择了同一个目标
        foreach ($selections as $count) {
            $this->assertLessThan($iterations, $count);
        }
    }

    /**
     * 测试select方法基本功能
     */
    public function testSelect(): void
    {
        $balancer = new RandomLoadBalancer();

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
