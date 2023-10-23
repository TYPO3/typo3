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
 * Wrapper for locking API that uses two locks to not exhaust locking resources and still block properly.
 *
 * The schematics here is:
 * - First acquire an access lock. This is using the type of the requested lock as key.
 *   Since the number of types is rather limited we can use the type as key as it will only
 *   eat up a limited number of lock resources on the system (files, semaphores)
 * - Second, we acquire the actual lock. We can be sure we are the only process at this
 *   very moment, hence we either get the lock for the given key or we get an error as
 *   we request a non-blocking mode.
 *
 * Interleaving two locks is important, because the actual lock uses a hash value as key (see callers).
 * If we would simply employ a normal blocking lock, we would get a potentially unlimited number of
 * different locks. Depending on the available locking methods on the system we might run out of available
 * resources: For instance maximum limit of semaphores is a system setting and applies to the whole system.
 *
 * We therefore must make sure that page locks are destroyed again if they are not used anymore, such that
 * we never use more locking resources than parallel requests.
 *
 * In order to ensure this, we need to guarantee that no other process is waiting on a lock when
 * the process currently having the lock on the lock is about to release the lock again.
 *
 * This can only be achieved by using a non-blocking mode, such that a process is never put into wait state
 * by the kernel, but only checks the availability of the lock. The access lock is our guard to be sure
 * that no two processes are at the same time releasing/destroying a lock, whilst the other one tries to
 * get a lock for this page lock.
 *
 * The only drawback of this implementation is that we basically have to poll the availability of the page lock.
 *
 * Note that the access lock resources are NEVER deleted/destroyed, otherwise the whole thing would be broken.
 */
class ResourceMutex
{
    /**
     * @var array<string,LockingStrategyInterface|null>
     */
    private array $accessLocks = [];

    /**
     * @var array<string,LockingStrategyInterface|null>
     */
    private array $workerLocks = [];

    public function __construct(private readonly LockFactory $lockFactory) {}

    /**
     * Acquire a specific lock for the given scope.
     *
     * @throws LockAcquireException
     * @throws LockCreateException
     * @return bool True if we did not get the lock immediately and had to wait. This can be useful to
     *              know in the consumer since another process may have created something that we can
     *              re-use immediately.
     */
    public function acquireLock(string $scope, string $key): bool
    {
        $this->accessLocks[$scope] = $this->lockFactory->createLocker($scope);
        $this->workerLocks[$scope] = $this->lockFactory->createLocker(
            $key,
            LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
        );
        $hadToWaitForLock = false;
        do {
            if (!$this->accessLocks[$scope]->acquire()) {
                throw new \RuntimeException('Could not acquire access lock for "' . $scope . '".', 1601923209);
            }
            try {
                $locked = $this->workerLocks[$scope]->acquire(
                    LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
                );
            } catch (LockAcquireWouldBlockException $e) {
                // Somebody else has the lock, we keep waiting.
                // First release the access lock, it will be acquired in next iteration again.
                $this->accessLocks[$scope]->release();
                // Mark "We had to wait".
                $hadToWaitForLock = true;
                // Now lets make a short break (20ms) until we try again, since
                // the page generation by the lock owner will take a while.
                usleep(20000);
                continue;
            }
            $this->accessLocks[$scope]->release();
            if ($locked) {
                break;
            }
            throw new \RuntimeException('Could not acquire process lock for "' . $scope . '" with key "' . $key . '".', 1601923215);
        } while (true);
        return $hadToWaitForLock;
    }

    /**
     * Release a worker specific lock.
     *
     * @throws LockAcquireException
     * @throws LockAcquireWouldBlockException
     */
    public function releaseLock(string $scope): void
    {
        if ($this->accessLocks[$scope] ?? null) {
            if (!$this->accessLocks[$scope]->acquire()) {
                throw new \RuntimeException('Could not acquire access lock for "' . $scope . '".', 1601923319);
            }
            $this->workerLocks[$scope]->release();
            $this->workerLocks[$scope]->destroy();
            $this->workerLocks[$scope] = null;
            $this->accessLocks[$scope]->release();
            $this->accessLocks[$scope] = null;
        }
    }
}
