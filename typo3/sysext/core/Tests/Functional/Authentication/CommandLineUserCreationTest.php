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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\CommandLineUserCreation;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CommandLineUserCreationTest extends FunctionalTestCase
{
    #[Test]
    public function ensureCliUserExistsCreatesCliUserOnce(): void
    {
        $subject = $this->get(CommandLineUserCreation::class);

        $subject->ensureCliUserExists();
        $subject->ensureCliUserExists();
        self::assertSame(1, $this->countCliUsers());
    }

    /**
     * Another process creates the user while this one waits for the lock.
     * The existence check runs again under the lock, so no second user is
     * created.
     */
    #[Test]
    public function ensureCliUserExistsDoesNotCreateCliUserCreatedWhileWaitingForLock(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');
        $lock = $this->createMock(LockingStrategyInterface::class);
        $lock->expects($this->once())->method('acquire')->willReturnCallback(
            static function () use ($connection): bool {
                $connection->insert('be_users', ['username' => '_cli_', 'admin' => 1]);
                return true;
            }
        );
        $lock->expects($this->once())->method('release');
        $lockFactory = self::createStub(LockFactory::class);
        $lockFactory->method('createLocker')->willReturn($lock);
        $subject = new CommandLineUserCreation(
            $this->get(ConnectionPool::class),
            $this->get(PasswordHashFactory::class),
            $lockFactory,
        );

        $subject->ensureCliUserExists();
        self::assertSame(1, $this->countCliUsers());
    }

    #[Test]
    public function ensureCliUserExistsDoesNotLockForExistingCliUser(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('be_users')
            ->insert('be_users', ['username' => '_cli_', 'admin' => 1]);
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects($this->never())->method('createLocker');
        $subject = new CommandLineUserCreation(
            $this->get(ConnectionPool::class),
            $this->get(PasswordHashFactory::class),
            $lockFactory,
        );

        $subject->ensureCliUserExists();
        self::assertSame(1, $this->countCliUsers());
    }

    private function countCliUsers(): int
    {
        return (int)$this->get(ConnectionPool::class)->getConnectionForTable('be_users')
            ->count('*', 'be_users', ['username' => '_cli_']);
    }
}
