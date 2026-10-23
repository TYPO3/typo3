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

namespace TYPO3\CMS\Backend\Tests\Functional\Command;

use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use TYPO3\CMS\Backend\Command\UnlockBackendUserMfaCommand;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\Command\AbstractCommandTestCase;

final class UnlockBackendUserMfaCommandTest extends AbstractCommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users_mfa_unlock.csv');
    }

    #[DataProvider('userIdentifierDataProvider')]
    #[Test]
    public function lockedProviderIsUnlockedByUserIdentifier(string $identifier): void
    {
        $result = $this->executeConsoleCommand('backend:user:mfa:unlock %s', $identifier);

        self::assertSame(0, $result['status'], $result['stdout'] . $result['stderr']);
        self::assertSame(0, $this->getTotpAttempts(2));
    }

    public static function userIdentifierDataProvider(): \Generator
    {
        yield 'uid' => ['2'];
        yield 'username' => ['locked_user'];
        yield 'email' => ['locked@example.com'];
    }

    #[Test]
    public function unlockingKeepsSecretAndLeavesOtherUsersLocked(): void
    {
        $this->executeConsoleCommand('backend:user:mfa:unlock %s', 'locked_user');

        self::assertSame(0, $this->getTotpAttempts(2));
        self::assertStringContainsString('KRMVATZTJFZUC53FONXW2ZJB', $this->getMfa(2));
        self::assertSame(3, $this->getTotpAttempts(3));
    }

    #[Test]
    public function disabledUserCanBeUnlocked(): void
    {
        $result = $this->executeConsoleCommand('backend:user:mfa:unlock %s', 'locked_other');

        self::assertSame(0, $result['status'], $result['stdout'] . $result['stderr']);
        self::assertSame(0, $this->getTotpAttempts(3));
    }

    #[Test]
    public function userWithoutLockedProviderIsLeftUntouched(): void
    {
        $result = $this->executeConsoleCommand('backend:user:mfa:unlock %s', 'unlocked_user');

        self::assertSame(0, $result['status'], $result['stdout'] . $result['stderr']);
        self::assertSame(1, $this->getTotpAttempts(4));
    }

    #[DataProvider('unknownUserDataProvider')]
    #[Test]
    public function unknownUserFails(string $identifier): void
    {
        $result = $this->executeConsoleCommand('backend:user:mfa:unlock %s', $identifier);

        self::assertNotSame(0, $result['status']);
    }

    public static function unknownUserDataProvider(): \Generator
    {
        yield 'unknown username' => ['nobody'];
        yield 'unknown uid' => ['999'];
        yield 'deleted user' => ['deleted_locked'];
    }

    #[Test]
    public function completionSuggestsOnlyUsersWithLockedProvider(): void
    {
        $command = new UnlockBackendUserMfaCommand(
            $this->get(ConnectionPool::class),
            $this->get(MfaProviderRegistry::class)
        );

        $suggestions = new CommandCompletionTester($command)->complete(['']);

        self::assertEqualsCanonicalizing(['locked_user', 'locked_other'], $suggestions);
    }

    private function getMfa(int $uid): string
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();
        $mfa = $queryBuilder
            ->select('mfa')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)))
            ->executeQuery()
            ->fetchOne();
        return is_resource($mfa) ? (string)stream_get_contents($mfa) : (string)$mfa;
    }

    private function getTotpAttempts(int $uid): int
    {
        return (int)json_decode($this->getMfa($uid), true)['totp']['attempts'];
    }
}
