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

namespace TYPO3\CMS\Core\Messenger\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;

/**
 * Stops the worker when the time limit is reached.
 *
 * @internal
 */
class StopWorkerOnTimeLimitListener
{
    private float $endTime = 0;

    private bool $stopped = false;

    public function __construct(
        private readonly int $timeLimitInSeconds = 60 * 60, // 1 hour
        private readonly ?LoggerInterface $logger = null
    ) {
        if ($timeLimitInSeconds <= 0) {
            throw new InvalidArgumentException('Time limit must be greater than zero.', 1631254000);
        }
    }
    public function onWorkerStarted(WorkerStartedEvent $event): void
    {
        $startTime = microtime(true);
        $this->endTime = $startTime + $this->timeLimitInSeconds;
    }

    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        if ($this->endTime < microtime(true)) {
            $event->getWorker()->stop();
            $this->logger?->info('Worker stopped due to time limit of {timeLimit}s exceeded', ['timeLimit' => $this->timeLimitInSeconds]);
            $this->stopped = true;
        }
    }

    public function hasStopped(): bool
    {
        return $this->stopped;
    }
}
