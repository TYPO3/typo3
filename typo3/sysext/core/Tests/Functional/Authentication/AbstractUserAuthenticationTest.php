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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Tests\Functional\Authentication\Fixtures\AnyUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AbstractUserAuthenticationTest extends FunctionalTestCase
{
    #[Test]
    public function pushModuleDataDoesNotRevealPlainSessionId(): void
    {
        $sessionId = bin2hex(random_bytes(20));
        $userSession = UserSession::createNonFixated($sessionId);
        $subject = new AnyUserAuthentication($userSession);
        $subject->pushModuleData(self::class, true);
        self::assertNotContains($sessionId, $subject->uc['moduleSessionID']);
    }

    #[Test]
    public function getModuleDataResolvesHashedSessionId(): void
    {
        $sessionId = bin2hex(random_bytes(20));
        $userSession = UserSession::createNonFixated($sessionId);
        $subject = new AnyUserAuthentication($userSession);
        $subject->pushModuleData(self::class, true);
        self::assertTrue($subject->getModuleData(self::class));
    }

    #[Test]
    public function getModuleDataFallsBackToPlainSessionId(): void
    {
        $sessionId = bin2hex(random_bytes(20));
        $userSession = UserSession::createNonFixated($sessionId);
        $subject = new AnyUserAuthentication($userSession);
        $subject->uc['moduleData'][self::class] = true;
        $subject->uc['moduleSessionID'][self::class] = $sessionId;
        self::assertTrue($subject->getModuleData(self::class));
    }

    public static function getAuthInfoArrayReturnsEmptyPidListIfNoCheckPidValueIsGivenDataProvider(): array
    {
        return [
            ['', ''],
            [null, ''],
            [0, '0'],
            ['0', '0'],
            ['12,31', '12, 31'],
        ];
    }

    #[DataProvider('getAuthInfoArrayReturnsEmptyPidListIfNoCheckPidValueIsGivenDataProvider')]
    #[Test]
    public function getAuthInfoArrayReturnsCorrectPidConstraintForGivenCheckPidValue(
        int|null|string $checkPid_value,
        string $expectedPids
    ): void {
        $sessionId = bin2hex(random_bytes(20));
        $userSession = UserSession::createNonFixated($sessionId);
        $subject = new AnyUserAuthentication($userSession);
        $subject->user_table = 'be_users';
        $subject->checkPid_value = $checkPid_value;
        $authInfoArray = $subject->getAuthInfoArray(new ServerRequest('https://example.com'));
        $enableClause = $authInfoArray['db_user']['enable_clause'];
        self::assertInstanceOf(CompositeExpression::class, $enableClause);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');
        $expectedEnableClause = '';
        if ($expectedPids !== '') {
            $expectedEnableClause = $connection->quoteIdentifier('be_users.pid') . ' IN (' . $expectedPids . ')';
        }
        self::assertSame($expectedEnableClause, (string)$enableClause);
    }
}
