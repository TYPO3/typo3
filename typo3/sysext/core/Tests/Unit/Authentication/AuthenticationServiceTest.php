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

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AuthenticationServiceTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * Date provider for processLoginReturnsCorrectData
     *
     * @return array
     */
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
                ]
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
                ]
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
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider processLoginDataProvider
     */
    public function processLoginReturnsCorrectData($passwordSubmissionStrategy, $loginData, $expectedProcessedData): void
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
        $subject = new AuthenticationService();
        $subject->initAuth('mode', ['uident_text' => '', 'uname' => 'user'], [], null);
        self::assertSame(100, $subject->authUser([]));
    }

    /**
     * @test
     */
    public function authUserReturns100IfUserSubmittedUsernameIsEmpty(): void
    {
        $subject = new AuthenticationService();
        $subject->initAuth('mode', ['uident_text' => 'foo', 'uname' => ''], [], null);
        self::assertSame(100, $subject->authUser([]));
    }

    /**
     * @test
     */
    public function authUserThrowsExceptionIfUserTableIsNotSet(): void
    {
        $subject = new AuthenticationService();
        $subject->initAuth('mode', ['uident_text' => 'password', 'uname' => 'user'], [], null);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1533159150);
        $subject->authUser([]);
    }

    /**
     * @test
     */
    public function authUserThrowsExceptionIfPasswordInDbDoesNotResolveToAValidHash(): void
    {
        $subject = new AuthenticationService();
        $pObjProphecy = $this->prophesize(AbstractUserAuthentication::class);
        $pObjProphecy->loginType = 'BE';
        $loggerProphecy = $this->prophesize(Logger::class);
        $subject->setLogger($loggerProphecy->reveal());
        $subject->initAuth(
            'authUserBE',
            [
                'uident_text' => 'password',
                'uname' => 'lolli'
            ],
            [
                'db_user' => ['table' => 'be_users'],
                'HTTP_HOST' => ''
            ],
            $pObjProphecy->reveal()
        );
        $dbUser = [
            'password' => 'aPlainTextPassword',
            'lockToDomain' => ''
        ];
        self::assertEquals(100, $subject->authUser($dbUser));
    }

    /**
     * @test
     */
    public function authUserReturns0IfPasswordDoesNotMatch(): void
    {
        $subject = new AuthenticationService();
        $pObjProphecy = $this->prophesize(AbstractUserAuthentication::class);
        $pObjProphecy->loginType = 'BE';
        $loggerProphecy = $this->prophesize(Logger::class);
        $subject->setLogger($loggerProphecy->reveal());
        $subject->initAuth(
            'authUserBE',
            [
                'uident_text' => 'notMyPassword',
                'uname' => 'lolli'
            ],
            [
                'db_user' => ['table' => 'be_users'],
                'HTTP_HOST' => '',
            ],
            $pObjProphecy->reveal()
        );
        $dbUser = [
            // a phpass hash of 'myPassword'
            'password' => '$P$C/2Vr3ywuuPo5C7cs75YBnVhgBWpMP1',
            'lockToDomain' => ''
        ];
        self::assertSame(0, $subject->authUser($dbUser));
    }

    /**
     * @test
     */
    public function authUserReturns200IfPasswordMatch(): void
    {
        $subject = new AuthenticationService();
        $pObjProphecy = $this->prophesize(AbstractUserAuthentication::class);
        $pObjProphecy->loginType = 'BE';
        $loggerProphecy = $this->prophesize(Logger::class);
        $subject->setLogger($loggerProphecy->reveal());
        $subject->initAuth(
            'authUserBE',
            [
                'uident_text' => 'myPassword',
                'uname' => 'lolli'
            ],
            [
                'db_user' => ['table' => 'be_users'],
                'HTTP_HOST' => ''
            ],
            $pObjProphecy->reveal()
        );
        $dbUser = [
            // an argon2i hash of 'myPassword'
            'password' => '$argon2i$v=19$m=65536,t=16,p=1$eGpyelFZbkpRdXN3QVhsUA$rd4abz2fcuksGu3b3fipglQZtHbIy+M3XoIS+sNVSl4',
            'lockToDomain' => ''
        ];
        self::assertSame(200, $subject->authUser($dbUser));
    }

    /**
     * @test
     */
    public function authUserReturns0IfPasswordMatchButDomainLockDoesNotMatch(): void
    {
        $subject = new AuthenticationService();
        $pObjProphecy = $this->prophesize(AbstractUserAuthentication::class);
        $pObjProphecy->loginType = 'BE';
        $loggerProphecy = $this->prophesize(Logger::class);
        $subject->setLogger($loggerProphecy->reveal());
        $subject->initAuth(
            'authUserBE',
            [
                'uident_text' => 'myPassword',
                'uname' => 'lolli'
            ],
            [
                'db_user' => [
                    'table' => 'be_users',
                    'username_column' => 'username',
                ],
                'REMOTE_HOST' => '',
                'HTTP_HOST' => 'example.com',
            ],
            $pObjProphecy->reveal()
        );
        $dbUser = [
            // an argon2i hash of 'myPassword'
            'password' => '$argon2i$v=19$m=65536,t=16,p=2$LnUzc3ZISWJwQWlSbmpkYw$qD1sRsJFzkUmjcEaKzDeg6LtflwdTpo49VbH3tMeMXU',
            'username' => 'lolli',
            'lockToDomain' => 'not.example.com'
        ];
        self::assertSame(0, $subject->authUser($dbUser));
    }
}
