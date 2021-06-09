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

namespace TYPO3\CMS\Backend\Tests\Functional\Authentication;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use TYPO3\CMS\Backend\Authentication\PasswordReset;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PasswordResetTest extends FunctionalTestCase
{
    protected LoggerInterface $logger;

    public function setUp(): void
    {
        parent::setUp();
        $this->logger = new class() implements LoggerInterface {
            use LoggerTrait;
            public array $records = [];
            public function log($level, $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context
                ];
            }
        };
    }

    /**
     * @test
     */
    public function isNotEnabledWorks(): void
    {
        $subject = new PasswordReset();
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = false;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = false;
        self::assertFalse($subject->isEnabled());
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        self::assertFalse($subject->isEnabled());
    }

    /**
     * @test
     */
    public function isNotEnabledWithNoUsers(): void
    {
        $subject = new PasswordReset();
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = false;
        self::assertFalse($subject->isEnabled());
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        self::assertFalse($subject->isEnabled());
    }

    /**
     * @test
     */
    public function isEnabledExcludesAdministrators(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/be_users_only_admins.xml');
        $subject = new PasswordReset();
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = false;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = false;
        self::assertFalse($subject->isEnabled());
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = false;
        self::assertFalse($subject->isEnabled());
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        self::assertTrue($subject->isEnabled());
    }

    /**
     * @test
     */
    public function isEnabledForUserTest(): void
    {
        $subject = new PasswordReset();
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = false;

        // False since no users exist
        self::assertFalse($subject->isEnabledForUser(3));

        $this->importDataSet(__DIR__ . '/Fixtures/be_users.xml');

        // False since reset for admins is not enabled
        self::assertFalse($subject->isEnabledForUser(1));
        // False since user has no email set
        self::assertFalse($subject->isEnabledForUser(2));
        // False since user has no password set
        self::assertFalse($subject->isEnabledForUser(4));
        // False since user is disabled
        self::assertFalse($subject->isEnabledForUser(7));

        // Now true since user with email+password exist
        self::assertTrue($subject->isEnabledForUser(3));

        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        // True since "passwordResetForAdmins" is now set
        self::assertTrue($subject->isEnabledForUser(1));
    }

    /**
     * @test
     */
    public function noEmailIsFound(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/be_users.xml');
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'null';
        $emailAddress = 'does-not-exist@example.com';
        $subject = new PasswordReset();
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->warning()->withArguments(['Password reset requested for email but no valid users'])->shouldBeCalled();
        $subject->setLogger($loggerProphecy->reveal());
        $context = new Context();
        $request = new ServerRequest();
        $subject->initiateReset($request, $context, $emailAddress);
    }

    /**
     * @test
     */
    public function ambiguousEmailIsTriggeredForMultipleValidUsers(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/be_users.xml');
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'null';
        $emailAddress = 'duplicate@example.com';
        $subject = new PasswordReset();
        $subject->setLogger($this->logger);
        $context = new Context();
        $request = new ServerRequest();
        $subject->initiateReset($request, $context, $emailAddress);
        self::assertEquals('warning', $this->logger->records[0]['level']);
        self::assertEquals($emailAddress, $this->logger->records[0]['context']['email']);
    }

    /**
     * @test
     */
    public function passwordResetEmailIsTriggeredForValidUser(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/be_users.xml');
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'null';
        $emailAddress = 'editor-with-email@example.com';
        $username = 'editor-with-email';
        $subject = new PasswordReset();
        $subject->setLogger($this->logger);
        $context = new Context();
        $request = new ServerRequest();
        $subject->initiateReset($request, $context, $emailAddress);
        self::assertEquals('info', $this->logger->records[0]['level']);
        self::assertEquals($emailAddress, $this->logger->records[0]['context']['email']);
        self::assertEquals($username, $this->logger->records[0]['context']['username']);
    }

    /**
     * @test
     */
    public function invalidTokenCannotResetPassword(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/be_users.xml');
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'null';
        $subject = new PasswordReset();
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->debug()->withArguments(['Password reset not possible due to weak password'])->shouldBeCalled();
        $subject->setLogger($loggerProphecy->reveal());

        $context = new Context();
        $request = new ServerRequest();
        $request = $request->withQueryParams(['t' => 'token', 'i' => 'identity', 'e' => 13465444]);
        $subject->resetPassword($request, $context);

        // Now with a password
        $request = $request->withParsedBody(['password' => 'str0NGpassw0RD!', 'passwordrepeat' => 'str0NGpassw0RD!']);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->warning()->withArguments(['Password reset not possible. Valid user for token not found.'])->shouldBeCalled();
        $subject->setLogger($loggerProphecy->reveal());
        $subject->resetPassword($request, $context);
    }
}
