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
 * PSR-20 clock that is frozen at the start of the current request
 * and returns the same instant on every call.
 *
 * Inject this concrete class when multiple reads within one request
 * must yield a consistent "time of the current request" - the same
 * semantics as reading $GLOBALS['EXEC_TIME']. For the actual, advancing
 * wall-clock time (e.g. expiry checks, long-running CLI processes),
 * inject ClockInterface to receive the SystemClock instead.
 */
final class RequestClock implements ClockInterface
{
    private readonly \DateTimeImmutable $frozenAt;

    public function __construct(?\DateTimeImmutable $frozenAt = null)
    {
        $this->frozenAt = $frozenAt ?? new \DateTimeImmutable();
    }

    /**
     * Creates a clock frozen at the timestamp the current PHP process
     * started to handle the request. The instance is provided to the
     * dependency injection container during boot.
     *
     * @internal bridges the request start time currently taken during
     *           low-level system environment setup, only to be used
     *           during TYPO3 bootstrap
     */
    public static function create(): self
    {
        $requestStart = $GLOBALS['EXEC_TIME'] ?? null;
        return new self(is_int($requestStart) ? new \DateTimeImmutable('@' . $requestStart) : null);
    }

    public function now(): \DateTimeImmutable
    {
        return $this->frozenAt;
    }
}
