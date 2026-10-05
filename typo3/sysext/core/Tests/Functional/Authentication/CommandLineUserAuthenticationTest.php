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
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Authentication\CommandLineUserCreation;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CommandLineUserAuthenticationTest extends FunctionalTestCase
{
    /**
     * Without any _cli_ user, authenticate() creates one as admin. No
     * $GLOBALS['BE_USER'] is set, as for a caller that authenticates the
     * command line user without the bootstrap.
     */
    #[Test]
    public function authenticateProvidesCliUserAsBackendUserAspect(): void
    {
        unset($GLOBALS['BE_USER']);
        $subject = GeneralUtility::makeInstance(CommandLineUserAuthentication::class);
        $subject->authenticate();

        $cliUser = $this->get(ConnectionPool::class)->getConnectionForTable('be_users')
            ->select(['uid'], 'be_users', ['username' => '_cli_'])
            ->fetchAssociative();
        $aspect = $this->get(Context::class)->getAspect('backend.user');
        self::assertInstanceOf(UserAspect::class, $aspect);
        self::assertSame((int)$cliUser['uid'], $aspect->get('id'));
        self::assertSame('_cli_', $aspect->get('username'));
        self::assertTrue($aspect->isAdmin());
    }

    #[Test]
    public function authenticateProvidesGroupsOfCliUserToBackendUserAspect(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CommandLineUser.csv');
        $subject = GeneralUtility::makeInstance(CommandLineUserAuthentication::class);
        $subject->authenticate();

        $aspect = $this->get(Context::class)->getAspect('backend.user');
        self::assertSame(7, $aspect->get('id'));
        self::assertFalse($aspect->isAdmin());
        self::assertSame([1], $aspect->getGroupIds());
        self::assertSame(['CLI group'], $aspect->getGroupNames());
    }

    /**
     * Of two _cli_ users none is picked, and no third one is created.
     */
    #[Test]
    public function authenticateRefusesCliUserThatExistsMoreThanOnce(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CommandLineUserDuplicate.csv');
        $subject = GeneralUtility::makeInstance(CommandLineUserAuthentication::class);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1484050401);
        $subject->authenticate();
    }

    /**
     * Another process creates the _cli_ user while this one waits for the
     * lock. This process then uses that user instead of failing.
     */
    #[Test]
    public function authenticateUsesCliUserCreatedWhileWaitingForLock(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');
        $lock = self::createStub(LockingStrategyInterface::class);
        $lock->method('acquire')->willReturnCallback(
            static function () use ($connection): bool {
                $connection->insert('be_users', ['uid' => 9, 'username' => '_cli_', 'admin' => 1]);
                return true;
            }
        );
        $lockFactory = self::createStub(LockFactory::class);
        $lockFactory->method('createLocker')->willReturn($lock);
        GeneralUtility::addInstance(CommandLineUserCreation::class, new CommandLineUserCreation(
            $this->get(ConnectionPool::class),
            $this->get(PasswordHashFactory::class),
            $lockFactory,
        ));
        $subject = GeneralUtility::makeInstance(CommandLineUserAuthentication::class);
        $subject->authenticate();

        self::assertSame(9, $this->get(Context::class)->getAspect('backend.user')->get('id'));
    }
}
