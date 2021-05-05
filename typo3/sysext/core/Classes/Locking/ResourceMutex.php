<?php

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

namespace TYPO3\CMS\Core\Locking;

use TYPO3\CMS\Core\Locking\Exception\LockAcquireException;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\Exception\LockCreateException;

/**
 * Wrapper for locking API that uses two locks
 * to not exhaust locking resources and still block properly
 */
class ResourceMutex
{
    /**
     * @var LockFactory
     */
    private $lockFactory;

    /**
     * Access lock
     *
     * @var array<string,LockingStrategyInterface|null>
     */
    private $accessLocks = [];

    /**
     * Image processing lock
     *
     * @var array<string,LockingStrategyInterface|null>
     */
    private $workerLocks = [];

    public function __construct(LockFactory $lockFactory)
    {
        $this->lockFactory = $lockFactory;
    }

    /**
     * Acquire a specific lock for the given scope
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::acquireLock
     *
     * @param string $scope
     * @param string $key
     * @throws LockAcquireException
     * @throws LockCreateException
     */
    public function acquireLock(string $scope, string $key): void
    {
        $this->accessLocks[$scope] = $this->lockFactory->createLocker($scope);

        $this->workerLocks[$scope] = $this->lockFactory->createLocker(
            $key,
            LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
        );

        do {
            if (!$this->accessLocks[$scope]->acquire()) {
                throw new \RuntimeException('Could not acquire access lock for "' . $scope . '"".', 1601923209);
            }

            try {
                $locked = $this->workerLocks[$scope]->acquire(
                    LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
                );
            } catch (LockAcquireWouldBlockException $e) {
                // somebody else has the lock, we keep waiting

                // first release the access lock
                $this->accessLocks[$scope]->release();
                // now lets make a short break (100ms) until we try again, since
                // the page generation by the lock owner will take a while anyways
                usleep(100000);
                continue;
            }
            $this->accessLocks[$scope]->release();
            if ($locked) {
                break;
            }
            throw new \RuntimeException('Could not acquire image process lock for ' . $key . '.', 1601923215);
        } while (true);
    }

    /**
     * Release a worker specific lock
     *
     * @param string $scope
     * @throws LockAcquireException
     * @throws LockAcquireWouldBlockException
     */
    public function releaseLock(string $scope): void
    {
        if ($this->accessLocks[$scope] ?? null) {
            if (!$this->accessLocks[$scope]->acquire()) {
                throw new \RuntimeException('Could not acquire access lock for "' . $scope . '"".', 1601923319);
            }

            $this->workerLocks[$scope]->release();
            $this->workerLocks[$scope]->destroy();
            $this->workerLocks[$scope] = null;

            $this->accessLocks[$scope]->release();
            $this->accessLocks[$scope] = null;
        }
    }
}
