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

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Security\EmailLoginNotification;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedInEvent;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
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
        $backendUser->user = [
            'email' => 'test@acme.com',
        ];

        $mailMessage = $this->setUpMailMessageMock();
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects(self::once())->method('send')->with($mailMessage);

        $subject = new EmailLoginNotification($mailerMock);
        $subject->emailAtLogin(new AfterUserLoggedInEvent($backendUser));
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
        $backendUser->user = [
            'username' => 'karl',
            'email' => 'test@acme.com',
        ];
        $mailerMock = $this->createMock(MailerInterface::class);

        $subject = new EmailLoginNotification($mailerMock);
        $subject->emailAtLogin(new AfterUserLoggedInEvent($backendUser));

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
        $backendUser->user = [
            'username' => 'karl',
            'email' => 'dot.com',
        ];
        $mailerMock = $this->createMock(MailerInterface::class);

        $subject = new EmailLoginNotification($mailerMock);
        $subject->emailAtLogin(new AfterUserLoggedInEvent($backendUser));

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
        $backendUser->method('isAdmin')->willReturn(true);
        $backendUser->user = [
            'username' => 'karl',
        ];

        $mailMessage = $this->setUpMailMessageMock('typo3-admin@acme.com');
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects(self::once())->method('send')->with($mailMessage);

        $subject = new EmailLoginNotification($mailerMock);
        $subject->emailAtLogin(new AfterUserLoggedInEvent($backendUser));
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
        $backendUser->method('isAdmin')->willReturn(true);
        $backendUser->user = [
            'username' => 'karl',
        ];

        $mailMessage = $this->setUpMailMessageMock('typo3-admin@acme.com');
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects(self::once())->method('send')->with($mailMessage);

        $subject = new EmailLoginNotification($mailerMock);
        $subject->emailAtLogin(new AfterUserLoggedInEvent($backendUser));
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
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->user = [
            'username' => 'karl',
        ];

        $mailMessage = $this->setUpMailMessageMock('typo3-admin@acme.com');
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects(self::once())->method('send')->with($mailMessage);

        $subject = new EmailLoginNotification($mailerMock);
        $subject->emailAtLogin(new AfterUserLoggedInEvent($backendUser));
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
        $backendUser->method('isAdmin')->willReturn(false);
        $backendUser->user = [
            'username' => 'karl',
        ];
        $mailerMock = $this->createMock(MailerInterface::class);

        $subject = new EmailLoginNotification($mailerMock);
        $subject->emailAtLogin(new AfterUserLoggedInEvent($backendUser));

        // no additional assertion here as the test would fail due to not mocking the email API
    }

    protected function setUpMailMessageMock(string $recipient = ''): FluidEmail&MockObject
    {
        $mailMessage = $this->createMock(FluidEmail::class);

        if ($recipient === '') {
            $mailMessage->method('to')->withAnyParameters()->willReturn($mailMessage);
        } else {
            $mailMessage->expects(self::atLeastOnce())->method('to')->with($recipient)->willReturn($mailMessage);
        }
        $mailMessage->method('setTemplate')->withAnyParameters()->willReturn($mailMessage);
        $mailMessage->method('from')->withAnyParameters()->willReturn($mailMessage);
        $mailMessage->method('setRequest')->withAnyParameters()->willReturn($mailMessage);
        $mailMessage->method('assignMultiple')->withAnyParameters()->willReturn($mailMessage);
        GeneralUtility::addInstance(FluidEmail::class, $mailMessage);
        return $mailMessage;
    }
}
