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
use TYPO3\CMS\Backend\Authentication\PasswordReset;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PasswordResetTest extends FunctionalTestCase
{
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
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->warning()->withArguments(['Password reset sent to email address ' . $emailAddress . ' but multiple accounts found'])->shouldBeCalled();
        $subject->setLogger($loggerProphecy->reveal());
        $context = new Context();
        $request = new ServerRequest();
        $subject->initiateReset($request, $context, $emailAddress);
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
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->info()->withArguments(['Sent password reset email to email address ' . $emailAddress . ' for user ' . $username])->shouldBeCalled();
        $subject->setLogger($loggerProphecy->reveal());
        $context = new Context();
        $request = new ServerRequest();
        $subject->initiateReset($request, $context, $emailAddress);
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
