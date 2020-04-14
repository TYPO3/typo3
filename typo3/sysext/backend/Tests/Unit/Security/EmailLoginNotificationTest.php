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

namespace TYPO3\CMS\Backend\Tests\Unit\Security;

use Prophecy\Argument;
use TYPO3\CMS\Backend\Security\EmailLoginNotification;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class EmailLoginNotificationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function emailAtLoginSendsAnEmailIfUserHasValidEmailAndOptin(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'My TYPO3 Inc.';
        $backendUser = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->getMock();
        $backendUser->uc['emailMeAtLogin'] = 1;

        $userData = [
            'email' => 'test@acme.com'
        ];

        $mailMessage = $this->setUpMailMessageProphecy();
        $mailerProphecy = $this->prophesize(Mailer::class);
        $mailerProphecy->send($mailMessage)->shouldBeCalledOnce();
        GeneralUtility::addInstance(Mailer::class, $mailerProphecy->reveal());

        $subject = new EmailLoginNotification();
        $subject->emailAtLogin(['user' => $userData], $backendUser);
    }

    /**
     * @test
     */
    public function emailAtLoginDoesNotSendAnEmailIfUserHasNoOptin(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'My TYPO3 Inc.';
        $backendUser = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->getMock();
        $backendUser->uc['emailMeAtLogin'] = 0;

        $userData = [
            'username' => 'karl',
            'email' => 'test@acme.com'
        ];

        $subject = new EmailLoginNotification();
        $subject->emailAtLogin(['user' => $userData], $backendUser);

        // no additional assertion here, as the test would fail due to missing mail mocking if it actually tried to send an email
    }

    /**
     * @test
     */
    public function emailAtLoginDoesNotSendAnEmailIfUserHasInvalidEmail(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'My TYPO3 Inc.';
        $backendUser = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->getMock();
        $backendUser->uc['emailMeAtLogin'] = 1;

        $userData = [
            'username' => 'karl',
            'email' => 'dot.com'
        ];

        $subject = new EmailLoginNotification();
        $subject->emailAtLogin(['user' => $userData], $backendUser);

        // no additional assertion here, as the test would fail due to missing mail mocking if it actually tried to send an email
    }

    /**
     * @test
     */
    public function emailAtLoginSendsEmailToCustomEmailIfAdminWarningIsEnabled(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'My TYPO3 Inc.';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'] = 'typo3-admin@acme.com';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_mode'] = 2;
        $backendUser = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->getMock();
        $backendUser->expects(self::any())->method('isAdmin')->willReturn(true);

        $userData = [
            'username' => 'karl'
        ];

        $mailMessage = $this->setUpMailMessageProphecy();
        $mailerProphecy = $this->prophesize(Mailer::class);
        $mailerProphecy->send($mailMessage)->shouldBeCalledOnce();
        GeneralUtility::addInstance(Mailer::class, $mailerProphecy->reveal());

        $subject = new EmailLoginNotification();
        $subject->emailAtLogin(['user' => $userData], $backendUser);

        $mailMessage->to('typo3-admin@acme.com')->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function emailAtLoginSendsEmailToCustomEmailIfRegularWarningIsEnabled(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'My TYPO3 Inc.';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'] = 'typo3-admin@acme.com';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_mode'] = 1;
        $backendUser = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->getMock();
        $backendUser->expects(self::any())->method('isAdmin')->willReturn(true);

        $userData = [
            'username' => 'karl'
        ];

        $mailMessage = $this->setUpMailMessageProphecy();
        $mailerProphecy = $this->prophesize(Mailer::class);
        $mailerProphecy->send($mailMessage)->shouldBeCalledOnce();
        GeneralUtility::addInstance(Mailer::class, $mailerProphecy->reveal());

        $subject = new EmailLoginNotification();
        $subject->emailAtLogin(['user' => $userData], $backendUser);

        $mailMessage->to('typo3-admin@acme.com')->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function emailAtLoginSendsEmailToCustomEmailIfRegularWarningIsEnabledAndNoAdminIsLoggingIn(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'My TYPO3 Inc.';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'] = 'typo3-admin@acme.com';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_mode'] = 1;
        $backendUser = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->getMock();
        $backendUser->expects(self::any())->method('isAdmin')->willReturn(false);

        $userData = [
            'username' => 'karl'
        ];

        $mailMessage = $this->setUpMailMessageProphecy();
        $mailerProphecy = $this->prophesize(Mailer::class);
        $mailerProphecy->send($mailMessage)->shouldBeCalledOnce();
        GeneralUtility::addInstance(Mailer::class, $mailerProphecy->reveal());

        $subject = new EmailLoginNotification();
        $subject->emailAtLogin(['user' => $userData], $backendUser);

        $mailMessage->to('typo3-admin@acme.com')->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function emailAtLoginSendsNoEmailIfAdminWarningIsEnabledAndNoAdminIsLoggingIn(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'My TYPO3 Inc.';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'] = 'typo3-admin@acme.com';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_mode'] = 2;
        $backendUser = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->getMock();
        $backendUser->expects(self::any())->method('isAdmin')->willReturn(false);

        $userData = [
            'username' => 'karl'
        ];

        $subject = new EmailLoginNotification();
        $subject->emailAtLogin(['user' => $userData], $backendUser);

        // no additional assertion here as the test would fail due to not mocking the email API
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy|FluidEmail
     */
    protected function setUpMailMessageProphecy()
    {
        $mailMessage = $this->prophesize(FluidEmail::class);
        $mailMessage->to(Argument::any())->willReturn($mailMessage->reveal());
        $mailMessage->setTemplate(Argument::any())->willReturn($mailMessage->reveal());
        $mailMessage->from(Argument::any())->willReturn($mailMessage->reveal());
        $mailMessage->setRequest(Argument::any())->willReturn($mailMessage->reveal());
        $mailMessage->assignMultiple(Argument::cetera())->willReturn($mailMessage->reveal());
        GeneralUtility::addInstance(FluidEmail::class, $mailMessage->reveal());
        return $mailMessage;
    }
}
