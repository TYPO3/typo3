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

namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Tests\Functional\Authentication\Fixtures\AnyUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AuthenticationServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
        parent::tearDown();
    }

    public function processLoginDataProvider(): array
    {
        return [
            'Backend login with securityLevel "normal"' => [
                'normal',
                [
                    'status' => 'login',
                    'uname' => 'admin',
                    'uident' => 'password',
                ],
                [
                    'status' => 'login',
                    'uname' => 'admin',
                    'uident' => 'password',
                    'uident_text' => 'password',
                ],
            ],
            'Frontend login with securityLevel "normal"' => [
                'normal',
                [
                    'status' => 'login',
                    'uname' => 'admin',
                    'uident' => 'password',
                ],
                [
                    'status' => 'login',
                    'uname' => 'admin',
                    'uident' => 'password',
                    'uident_text' => 'password',
                ],
            ],
            'Frontend login with securityLevel "normal" and spaced passwords removes spaces' => [
                'normal',
                [
                    'status' => 'login',
                    'uname' => 'admin ',
                    'uident' => ' my password ',
                ],
                [
                    'status' => 'login',
                    'uname' => 'admin',
                    'uident' => 'my password',
                    'uident_text' => 'my password',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider processLoginDataProvider
     */
    public function processLoginReturnsCorrectData(string $passwordSubmissionStrategy, array $loginData, array $expectedProcessedData): void
    {
        $subject = new AuthenticationService();
        // Login data is modified by reference
        $subject->processLoginData($loginData, $passwordSubmissionStrategy);
        self::assertEquals($expectedProcessedData, $loginData);
    }

    /**
     * @test
     */
    public function authUserReturns100IfSubmittedPasswordIsEmpty(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
        $sessionId = 'f20bd8643811f5a2792605a689b619bc02caa7dc';
        $userSession = UserSession::createNonFixated($sessionId);
        $anyUserAuthentication = new AnyUserAuthentication($userSession);
        $anyUserAuthentication->loginType = 'BE';
        $subject = new AuthenticationService();
        $subject->initAuth('mode', ['uident_text' => '', 'uname' => 'user'], [], $anyUserAuthentication);
        self::assertSame(100, $subject->authUser([]));
    }

    /**
     * @test
     */
    public function authUserReturns100IfUserSubmittedUsernameIsEmpty(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
        $sessionId = 'f20bd8643811f5a2792605a689b619bc02caa7dc';
        $userSession = UserSession::createNonFixated($sessionId);
        $anyUserAuthentication = new AnyUserAuthentication($userSession);
        $anyUserAuthentication->loginType = 'BE';
        $subject = new AuthenticationService();
        $subject->initAuth('mode', ['uident_text' => 'foo', 'uname' => ''], [], $anyUserAuthentication);
        self::assertSame(100, $subject->authUser([]));
    }

    /**
     * @test
     */
    public function authUserThrowsExceptionIfUserTableIsNotSet(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
        $sessionId = 'f20bd8643811f5a2792605a689b619bc02caa7dc';
        $userSession = UserSession::createNonFixated($sessionId);
        $anyUserAuthentication = new AnyUserAuthentication($userSession);
        $anyUserAuthentication->loginType = 'BE';
        $subject = new AuthenticationService();
        $subject->initAuth('mode', ['uident_text' => 'password', 'uname' => 'user'], [], $anyUserAuthentication);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1533159150);
        $subject->authUser([]);
    }

    /**
     * @test
     */
    public function authUserThrowsExceptionIfPasswordInDbDoesNotResolveToAValidHash(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
        $sessionId = 'f20bd8643811f5a2792605a689b619bc02caa7dc';
        $userSession = UserSession::createNonFixated($sessionId);
        $anyUserAuthentication = new AnyUserAuthentication($userSession);
        $anyUserAuthentication->loginType = 'BE';
        $subject = new AuthenticationService();
        $subject->setLogger(new NullLogger());
        $subject->initAuth(
            'authUserBE',
            [
                'uident_text' => 'password',
                'uname' => 'lolli',
            ],
            [
                'db_user' => ['table' => 'be_users'],
                'HTTP_HOST' => '',
            ],
            $anyUserAuthentication
        );
        $dbUser = [
            'password' => 'aPlainTextPassword',
        ];
        self::assertEquals(100, $subject->authUser($dbUser));
    }

    /**
     * @test
     */
    public function authUserReturns0IfPasswordDoesNotMatch(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
        $sessionId = 'f20bd8643811f5a2792605a689b619bc02caa7dc';
        $userSession = UserSession::createNonFixated($sessionId);
        $anyUserAuthentication = new AnyUserAuthentication($userSession);
        $anyUserAuthentication->loginType = 'BE';
        $subject = new AuthenticationService();
        $subject->setLogger(new NullLogger());
        $subject->initAuth(
            'authUserBE',
            [
                'uident_text' => 'notMyPassword',
                'uname' => 'lolli',
            ],
            [
                'db_user' => ['table' => 'be_users'],
                'HTTP_HOST' => '',
            ],
            $anyUserAuthentication
        );
        $dbUser = [
            // a phpass hash of 'myPassword'
            'password' => '$P$C/2Vr3ywuuPo5C7cs75YBnVhgBWpMP1',
        ];
        self::assertSame(0, $subject->authUser($dbUser));
    }

    /**
     * @test
     */
    public function authUserReturns200IfPasswordMatch(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
        $sessionId = 'f20bd8643811f5a2792605a689b619bc02caa7dc';
        $userSession = UserSession::createNonFixated($sessionId);
        $anyUserAuthentication = new AnyUserAuthentication($userSession);
        $anyUserAuthentication->loginType = 'BE';
        $subject = new AuthenticationService();
        $subject->setLogger(new NullLogger());
        $subject->initAuth(
            'authUserBE',
            [
                'uident_text' => 'myPassword',
                'uname' => 'lolli',
            ],
            [
                'db_user' => ['table' => 'be_users'],
                'HTTP_HOST' => '',
            ],
            $anyUserAuthentication
        );
        $dbUser = [
            // an argon2i hash of 'myPassword'
            'password' => '$argon2i$v=19$m=65536,t=16,p=1$eGpyelFZbkpRdXN3QVhsUA$rd4abz2fcuksGu3b3fipglQZtHbIy+M3XoIS+sNVSl4',
        ];
        self::assertSame(200, $subject->authUser($dbUser));
    }
}
