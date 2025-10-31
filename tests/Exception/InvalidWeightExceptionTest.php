<?php

declare(strict_types=1);

namespace Tourze\Workerman\RelayWorker\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\RelayWorker\Exception\InvalidWeightException;

/**
 * @internal
 */
#[CoversClass(InvalidWeightException::class)]
final class InvalidWeightExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInstanceOf(): void
    {
        $exception = new InvalidWeightException();

        $this->assertInstanceOf(InvalidWeightException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'Invalid weight value';
        $exception = new InvalidWeightException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Weight must be positive';
        $code = 200;
        $exception = new InvalidWeightException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \InvalidArgumentException('Previous validation error');
        $exception = new InvalidWeightException('Invalid weight', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
