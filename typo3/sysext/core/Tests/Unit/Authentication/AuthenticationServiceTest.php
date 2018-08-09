<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

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
        $this->assertEquals($expectedProcessedData, $loginData);
    }

    /**
     * @test
     */
    public function authUserReturns100IfSubmittedPasswordIsEmpty(): void
    {
        $subject = new AuthenticationService();
        $subject->initAuth('mode', ['uident_text' => '', 'uname' => 'user'], [], null);
        $this->assertSame(100, $subject->authUser([]));
    }

    /**
     * @test
     */
    public function authUserReturns100IfUserSubmittedUsernameIsEmpty(): void
    {
        $subject = new AuthenticationService();
        $subject->initAuth('mode', ['uident_text' => 'foo', 'uname' => ''], [], null);
        $this->assertSame(100, $subject->authUser([]));
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
    public function authUserReturns100IfPasswordInDbIsNotASaltedPassword(): void
    {
        $subject = new AuthenticationService();
        $pObjProphecy = $this->prophesize(AbstractUserAuthentication::class);
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
        $this->assertSame(100, $subject->authUser($dbUser));
    }

    /**
     * @test
     */
    public function authUserReturns0IfPasswordDoesNotMatch(): void
    {
        $subject = new AuthenticationService();
        $pObjProphecy = $this->prophesize(AbstractUserAuthentication::class);
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
        $this->assertSame(0, $subject->authUser($dbUser));
    }

    /**
     * @test
     */
    public function authUserReturns200IfPasswordMatch(): void
    {
        $subject = new AuthenticationService();
        $pObjProphecy = $this->prophesize(AbstractUserAuthentication::class);
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
            'password' => '$argon2i$v=19$m=16384,t=16,p=2$Ty9zOFVWdDBVQmlWTldVbg$kiVbkrYeTvgNg84i97WZBMQszmza66IohBxUtOnzRvU',
            'lockToDomain' => ''
        ];
        $this->assertSame(200, $subject->authUser($dbUser));
    }

    /**
     * @test
     */
    public function authUserReturns0IfPasswordMatchButDomainLockDoesNotMatch(): void
    {
        $subject = new AuthenticationService();
        $pObjProphecy = $this->prophesize(AbstractUserAuthentication::class);
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
            'password' => '$argon2i$v=19$m=16384,t=16,p=2$Ty9zOFVWdDBVQmlWTldVbg$kiVbkrYeTvgNg84i97WZBMQszmza66IohBxUtOnzRvU',
            'username' => 'lolli',
            'lockToDomain' => 'not.example.com'
        ];
        $this->assertSame(0, $subject->authUser($dbUser));
    }
}
