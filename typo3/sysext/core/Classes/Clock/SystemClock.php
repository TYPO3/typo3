<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Clock;

use Psr\Clock\ClockInterface;

/**
 * Default PSR-20 clock, returning the actual current system time
 * on every call.
 *
 * This is the implementation received when injecting ClockInterface.
 * Use it whenever the real wall-clock time matters, such as expiry
 * of sessions or tokens - especially in long-running CLI processes,
 * where a timestamp taken at process start goes stale. To read a
 * consistent "time of the current request" instead, inject the
 * concrete RequestClock class.
 */
final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
