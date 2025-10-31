<?php

declare(strict_types=1);

namespace Tourze\Workerman\RelayWorker\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RelayWorker\RelayWorker;

/**
 * RelayWorker构造函数测试
 *
 * 专门测试构造函数功能
 *
 * @internal
 */
#[CoversClass(RelayWorker::class)]
final class RelayWorkerConstructorTest extends TestCase
{
    /**
     * 测试构造函数正确设置onMessage回调
     */
    public function testConstructor(): void
    {
        $socketName = 'tcp://0.0.0.0:8080';
        $worker = new RelayWorker($socketName);

        self::assertIsCallable($worker->onMessage);
    }

    /**
     * 测试构造函数继承Worker的功能
     */
    public function testConstructorInheritsFromWorker(): void
    {
        $socketName = 'tcp://0.0.0.0:8080';
        $worker = new RelayWorker($socketName);

        // 验证继承了Worker的属性
        self::assertSame($socketName, $worker->getSocketName());
    }
}
