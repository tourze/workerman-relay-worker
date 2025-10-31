<?php

declare(strict_types=1);

namespace Tourze\Workerman\RelayWorker\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\RelayWorker\Exception\ConnectionException;

/**
 * @internal
 */
#[CoversClass(ConnectionException::class)]
final class ConnectionExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInstanceOf(): void
    {
        $exception = new ConnectionException();

        $this->assertInstanceOf(ConnectionException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'Connection failed';
        $exception = new ConnectionException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Connection timeout';
        $code = 100;
        $exception = new ConnectionException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new ConnectionException('Connection error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
