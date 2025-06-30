<?php

declare(strict_types=1);

namespace Tourze\Tests\RelayWorker;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Model\Address;
use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\RelayWorker\LoadBalancer\RoundRobinLoadBalancer;
use Tourze\Workerman\RelayWorker\RelayWorker;
use Workerman\Connection\ConnectionInterface;

/**
 * @covers \Tourze\Workerman\RelayWorker\RelayWorker
 */
final class RelayWorkerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 确保每个测试都从干净的状态开始
        $reflection = new \ReflectionClass(RelayWorker::class);
        $targetsMapProperty = $reflection->getProperty('targetsMap');
        $targetsMapProperty->setAccessible(true);
        $targetsMapProperty->setValue(null, null);
        
        $loadBalancerMapProperty = $reflection->getProperty('loadBalancerMap');
        $loadBalancerMapProperty->setAccessible(true);
        $loadBalancerMapProperty->setValue(null, null);
        
        $bufferMapProperty = $reflection->getProperty('bufferMap');
        $bufferMapProperty->setAccessible(true);
        $bufferMapProperty->setValue(null, null);
    }

    public function testSetAndGetTargets(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $address1 = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);
        $address2 = Address::create('127.0.0.1', 8081, ProtocolFamily::TCP);
        
        $targets = [$address1, $address2];
        RelayWorker::setTargets($connection, $targets);
        
        $result = RelayWorker::getTargets($connection);
        self::assertSame($targets, $result);
    }

    public function testGetTargetsReturnsEmptyArrayForUnknownConnection(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $result = RelayWorker::getTargets($connection);
        self::assertSame([], $result);
    }

    public function testAddTarget(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $address1 = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);
        $address2 = Address::create('127.0.0.1', 8081, ProtocolFamily::TCP);
        
        RelayWorker::addTarget($connection, $address1);
        RelayWorker::addTarget($connection, $address2);
        
        $result = RelayWorker::getTargets($connection);
        self::assertCount(2, $result);
        self::assertSame($address1, $result[0]);
        self::assertSame($address2, $result[1]);
    }

    public function testGetTargetWithEmptyTargets(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $result = RelayWorker::getTarget($connection);
        self::assertNull($result);
    }

    public function testGetTargetWithDefaultLoadBalancer(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $address = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);
        
        RelayWorker::setTargets($connection, [$address]);
        $result = RelayWorker::getTarget($connection);
        
        self::assertSame($address, $result);
    }

    public function testSetLoadBalancer(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $loadBalancer = new RoundRobinLoadBalancer();
        $address1 = Address::create('127.0.0.1', 8080, ProtocolFamily::TCP);
        $address2 = Address::create('127.0.0.1', 8081, ProtocolFamily::TCP);
        
        RelayWorker::setTargets($connection, [$address1, $address2]);
        RelayWorker::setLoadBalancer($connection, $loadBalancer);
        
        // 第一次调用应该返回第一个地址
        $result1 = RelayWorker::getTarget($connection);
        self::assertSame($address1, $result1);
        
        // 第二次调用应该返回第二个地址（轮询）
        $result2 = RelayWorker::getTarget($connection);
        self::assertSame($address2, $result2);
    }

    public function testConstructor(): void
    {
        $socketName = 'tcp://0.0.0.0:8080';
        $worker = new RelayWorker($socketName);
        
        self::assertInstanceOf(RelayWorker::class, $worker);
        self::assertIsCallable($worker->onMessage);
    }
}