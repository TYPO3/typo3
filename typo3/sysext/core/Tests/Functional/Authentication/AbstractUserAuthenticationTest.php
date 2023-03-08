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

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Tests\Functional\Authentication\Fixtures\AnyUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class AbstractUserAuthenticationTest extends FunctionalTestCase
{
    private string $sessionId;
    private AnyUserAuthentication $subject;
    private UserSession $userSession;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
        $this->sessionId = bin2hex(random_bytes(20));
        $this->userSession = UserSession::createNonFixated($this->sessionId);
        $this->subject = new AnyUserAuthentication($this->userSession);
    }

    protected function tearDown(): void
    {
        unset($this->sessionId, $this->userSession, $this->subject);
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function pushModuleDataDoesNotRevealPlainSessionId(): void
    {
        $this->subject->pushModuleData(self::class, true);
        self::assertNotContains($this->sessionId, $this->subject->uc['moduleSessionID']);
    }

    /**
     * @test
     */
    public function getModuleDataResolvesHashedSessionId(): void
    {
        $this->subject->pushModuleData(self::class, true);
        self::assertTrue($this->subject->getModuleData(self::class));
    }

    /**
     * @test
     */
    public function getModuleDataFallsBackToPlainSessionId(): void
    {
        $this->subject->uc['moduleData'][self::class] = true;
        $this->subject->uc['moduleSessionID'][self::class] = $this->sessionId;
        self::assertTrue($this->subject->getModuleData(self::class));
    }

    /**
     * @test
     */
    public function getAuthInfoArrayReturnsEmptyPidListIfNoCheckPidValueIsGiven(): void
    {
        $this->subject->user_table = 'be_users';
        $this->subject->checkPid_value = null;

        $authInfoArray = $this->subject->getAuthInfoArray(new ServerRequest('https://example.com'));

        $enableClause = $authInfoArray['db_user']['enable_clause'];
        self::assertInstanceOf(CompositeExpression::class, $enableClause);
        self::assertSame('', (string)$enableClause);
    }
}
