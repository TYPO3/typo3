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

use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Factory class to retrieve a locking method
 */
class LockFactory implements SingletonInterface
{
    /**
     * @var bool[]
     */
    protected $lockingStrategy = [
        SemaphoreLockStrategy::class => true,
        FileLockStrategy::class => true,
        SimpleLockStrategy::class => true,
    ];

    /**
     * Add a locking method
     *
     * @param string $className
     * @throws \InvalidArgumentException
     */
    public function addLockingStrategy($className)
    {
        $interfaces = class_implements($className);
        if (isset($interfaces[LockingStrategyInterface::class])) {
            $this->lockingStrategy[$className] = true;
        } else {
            throw new \InvalidArgumentException('The given class name ' . $className . ' does not implement the required LockingStrategyInterface interface.', 1425990198);
        }
    }

    /**
     * Remove a locking method
     *
     * @param string $className
     */
    public function removeLockingStrategy($className)
    {
        unset($this->lockingStrategy[$className]);
    }

    /**
     * Get best matching locking method
     *
     * @param string $id ID to identify this lock in the system
     * @param int $capabilities LockingStrategyInterface::LOCK_CAPABILITY_* elements combined with bit-wise OR
     * @return LockingStrategyInterface Class name for a locking method
     * @throws LockCreateException if no locker could be created with the requested capabilities
     */
    public function createLocker($id, $capabilities = LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE)
    {
        $queue = new \SplPriorityQueue();

        /** @var LockingStrategyInterface $method */
        foreach ($this->lockingStrategy as $method => $_) {
            $supportedCapabilities = $capabilities & $method::getCapabilities();
            if ($supportedCapabilities === $capabilities) {
                $queue->insert($method, $method::getPriority());
            }
        }
        if ($queue->count() > 0) {
            $className = $queue->top();
            // We use 'new' here on purpose!
            // Locking might be used very early in the bootstrap process, where makeInstance() does not work
            return new $className($id);
        }
        throw new LockCreateException('Could not find a matching locking method with requested capabilities.', 1425990190);
    }
}
