<?php

declare(strict_types=1);

namespace Tourze\Workerman\RelayWorker\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\RelayWorker\Exception\NoAvailableWorkersException;

/**
 * @internal
 */
#[CoversClass(NoAvailableWorkersException::class)]
final class NoAvailableWorkersExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInstanceOf(): void
    {
        $exception = new NoAvailableWorkersException();

        $this->assertInstanceOf(NoAvailableWorkersException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'No workers available';
        $exception = new NoAvailableWorkersException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'All workers are down';
        $code = 300;
        $exception = new NoAvailableWorkersException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \InvalidArgumentException('Previous worker error');
        $exception = new NoAvailableWorkersException('No available workers', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
