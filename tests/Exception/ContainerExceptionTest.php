<?php

declare(strict_types=1);

namespace Tourze\Workerman\RelayWorker\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\RelayWorker\Exception\ContainerException;

/**
 * 容器异常测试
 *
 * @internal
 */
#[CoversClass(ContainerException::class)]
final class ContainerExceptionTest extends AbstractExceptionTestCase
{
    /**
     * 测试异常实例
     */
    public function testExceptionInstance(): void
    {
        $exception = new ContainerException('Test message');
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(ContainerException::class, $exception);
    }

    /**
     * 测试异常消息
     */
    public function testExceptionWithMessage(): void
    {
        $message = 'Container initialization failed';
        $exception = new ContainerException($message);
        $this->assertSame($message, $exception->getMessage());
    }

    /**
     * 测试异常代码
     */
    public function testExceptionWithCode(): void
    {
        $code = 1001;
        $exception = new ContainerException('Test message', $code);
        $this->assertSame($code, $exception->getCode());
    }

    /**
     * 测试异常链
     */
    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new ContainerException('Test message', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }
}
