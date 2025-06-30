<?php

declare(strict_types=1);

namespace Tourze\Workerman\RelayWorker\Exception;

use InvalidArgumentException;

final class NoAvailableWorkersException extends InvalidArgumentException
{
}