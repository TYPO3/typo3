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

namespace TYPO3\CMS\Backend\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Security\SessionAuthenticator;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Pipeline\Authenticated;
use TYPO3\CMS\Core\Authentication\Pipeline\NotApplicable;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SessionAuthenticatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_mfa.csv');
    }

    #[Test]
    public function logoutIsLoggedForTheUserOfTheSession(): void
    {
        $userRecord = $this->getBackendUserRecordFromDatabase(2);
        $session = new BackendUserAuthentication()->createUserSession($userRecord);
        $request = new ServerRequest('https://typo3-testing.local/typo3/logout', 'POST', null, [], ['REMOTE_ADDR' => '127.0.0.1']);
        $request = $request
            ->withCookieParams([BackendUserAuthentication::getCookieName() => $session->getJwt()])
            ->withParsedBody(['login_status' => 'logout'])
            ->withAttribute('normalizedParams', NormalizedParams::createFromServerParams(['REMOTE_ADDR' => '127.0.0.1']));

        $result = $this->get(SessionAuthenticator::class)->authenticate($request, Realm::backend());

        self::assertInstanceOf(NotApplicable::class, $result);
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $logEntry = $queryBuilder
            ->select('userid', 'details', 'log_data')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq('type', SystemLogType::LOGIN),
                $queryBuilder->expr()->eq('action', SystemLogLoginAction::LOGOUT)
            )
            ->executeQuery()
            ->fetchAssociative();
        self::assertIsArray($logEntry);
        self::assertSame(2, (int)$logEntry['userid']);
        self::assertStringContainsString('editor', (string)$logEntry['log_data']);
    }

    #[Test]
    public function logoutStatusInTheQueryOfAGetRequestKeepsTheSession(): void
    {
        $userRecord = $this->getBackendUserRecordFromDatabase(2);
        $session = new BackendUserAuthentication()->createUserSession($userRecord);
        $request = new ServerRequest('https://typo3-testing.local/typo3/main?login_status=logout', 'GET', null, [], ['REMOTE_ADDR' => '127.0.0.1']);
        $request = $request
            ->withCookieParams([BackendUserAuthentication::getCookieName() => $session->getJwt()])
            ->withQueryParams(['login_status' => 'logout'])
            ->withAttribute('normalizedParams', NormalizedParams::createFromServerParams(['REMOTE_ADDR' => '127.0.0.1']));

        $result = $this->get(SessionAuthenticator::class)->authenticate($request, Realm::backend());

        self::assertInstanceOf(Authenticated::class, $result);
        self::assertSame(2, $result->principal->context->id);
        self::assertFalse(UserSessionManager::create('BE')->createFromRequestOrAnonymous($request, BackendUserAuthentication::getCookieName())->isAnonymous());
    }
}
