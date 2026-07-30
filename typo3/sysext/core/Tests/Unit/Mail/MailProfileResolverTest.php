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

namespace TYPO3\CMS\Core\Tests\Unit\Mail;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\MailProfileResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[BackupGlobals(true)]
final class MailProfileResolverTest extends UnitTestCase
{
    private MailProfileResolver $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new MailProfileResolver();
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = [
            'transport' => 'smtp',
            'transport_smtp_server' => 'smtp.example.com:25',
            'defaultMailFromAddress' => 'noreply@example.com',
            'profiles' => [
                'newsletter' => [
                    'transport_smtp_server' => 'smtp.newsletter.example.com:587',
                ],
                'local' => [
                    'transport' => 'sendmail',
                    'transport_sendmail_command' => '/usr/sbin/sendmail -bs',
                ],
                'invalid' => 'not-an-array',
            ],
        ];
    }

    #[Test]
    public function globalSettingsAreReturnedWithoutProfileName(): void
    {
        self::assertSame($GLOBALS['TYPO3_CONF_VARS']['MAIL'], $this->subject->getSettings());
        self::assertSame($GLOBALS['TYPO3_CONF_VARS']['MAIL'], $this->subject->getSettings(''));
    }

    #[Test]
    public function profileSettingsAreMergedOnTopOfGlobalSettings(): void
    {
        self::assertSame(
            [
                'transport' => 'smtp',
                'transport_smtp_server' => 'smtp.newsletter.example.com:587',
                'defaultMailFromAddress' => 'noreply@example.com',
            ],
            $this->subject->getSettings('newsletter')
        );
    }

    #[Test]
    public function profileCanOverrideTheTransport(): void
    {
        $settings = $this->subject->getSettings('local');
        self::assertSame('sendmail', $settings['transport']);
        self::assertSame('/usr/sbin/sendmail -bs', $settings['transport_sendmail_command']);
        self::assertSame('noreply@example.com', $settings['defaultMailFromAddress']);
    }

    #[Test]
    public function resolvedSettingsDoNotContainTheProfilesThemselves(): void
    {
        self::assertArrayNotHasKey('profiles', $this->subject->getSettings('newsletter'));
    }

    #[Test]
    public function unknownProfileThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1785920446);
        $this->subject->getSettings('does-not-exist');
    }

    #[Test]
    public function profileWithNonArrayConfigurationIsNotAvailable(): void
    {
        self::assertFalse($this->subject->hasProfile('invalid'));
        $this->expectException(\InvalidArgumentException::class);
        $this->subject->getSettings('invalid');
    }

    #[Test]
    public function hasProfileDetectsConfiguredProfiles(): void
    {
        self::assertTrue($this->subject->hasProfile('newsletter'));
        self::assertFalse($this->subject->hasProfile('does-not-exist'));
    }

    #[Test]
    public function profileNamesAreReturned(): void
    {
        self::assertSame(['newsletter', 'local'], $this->subject->getProfileNames());
    }

    #[Test]
    public function spoolOfGlobalSettingsIsNotInheritedByProfile(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_filepath'] = '/var/spool/global';
        $settings = $this->subject->getSettings('newsletter');
        self::assertArrayNotHasKey('transport_spool_type', $settings);
        self::assertArrayNotHasKey('transport_spool_filepath', $settings);
    }

    #[Test]
    public function profileCanConfigureItsOwnSpool(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_filepath'] = '/var/spool/global';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_spool_filepath'] = '/var/spool/newsletter';
        $settings = $this->subject->getSettings('newsletter');
        self::assertSame('file', $settings['transport_spool_type']);
        self::assertSame('/var/spool/newsletter', $settings['transport_spool_filepath']);
    }

    #[Test]
    public function profileSharingTheFileSpoolOfGlobalSettingsThrowsException(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_filepath'] = '/var/spool/global';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_spool_filepath'] = '/var/spool/global/';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1794593262);
        $this->subject->getSettings('newsletter');
    }

    #[Test]
    public function profileSharingTheFileSpoolOfGlobalSettingsInAnotherSpellingThrowsException(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_filepath'] = 'typo3temp/mail-spool';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_spool_filepath'] = Environment::getPublicPath() . '/typo3temp/mail-spool/';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1794593262);
        $this->subject->getSettings('newsletter');
    }

    #[Test]
    public function profileSharingTheFileSpoolOfAnotherProfileThrowsException(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_spool_filepath'] = '/var/spool/shared';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['local']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['local']['transport_spool_filepath'] = '/var/spool/shared/';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1794593262);
        $this->subject->getSettings('local');
    }

    #[Test]
    public function profilesWithFileSpoolsInDifferentDirectoriesAreAccepted(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_filepath'] = '/var/spool/global';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_spool_filepath'] = '/var/spool/newsletter';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['local']['transport_spool_type'] = 'file';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['local']['transport_spool_filepath'] = '/var/spool/local';
        self::assertSame('/var/spool/newsletter', $this->subject->getSettings('newsletter')['transport_spool_filepath']);
        self::assertSame('/var/spool/local', $this->subject->getSettings('local')['transport_spool_filepath']);
    }

    #[Test]
    public function profileWithOwnSmtpServerDoesNotInheritCredentials(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = 'global-user';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = 'global-password';
        $settings = $this->subject->getSettings('newsletter');
        self::assertSame('smtp.newsletter.example.com:587', $settings['transport_smtp_server']);
        self::assertArrayNotHasKey('transport_smtp_username', $settings);
        self::assertArrayNotHasKey('transport_smtp_password', $settings);
    }

    #[Test]
    public function profileWithOwnDsnDoesNotInheritCredentials(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = 'global-user';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = 'global-password';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter'] = ['dsn' => 'smtp://bulk.example.com:587'];
        $settings = $this->subject->getSettings('newsletter');
        self::assertArrayNotHasKey('transport_smtp_username', $settings);
        self::assertArrayNotHasKey('transport_smtp_password', $settings);
    }

    #[Test]
    public function profileWithOwnTransportDoesNotInheritDsn(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'dsn';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['dsn'] = 'smtp://global-user:global-password@smtp.example.com:25';
        $settings = $this->subject->getSettings('local');
        self::assertSame('sendmail', $settings['transport']);
        self::assertArrayNotHasKey('dsn', $settings);
    }

    #[Test]
    public function profileWithOwnSmtpServerKeepsItsOwnCredentials(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = 'global-user';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = 'global-password';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_smtp_username'] = 'bulk-user';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter']['transport_smtp_password'] = 'bulk-password';
        $settings = $this->subject->getSettings('newsletter');
        self::assertSame('bulk-user', $settings['transport_smtp_username']);
        self::assertSame('bulk-password', $settings['transport_smtp_password']);
    }

    #[Test]
    public function profileWithoutOwnConnectionInheritsCredentials(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = 'global-user';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = 'global-password';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter'] = ['defaultMailFromAddress' => 'news@example.com'];
        $settings = $this->subject->getSettings('newsletter');
        self::assertSame('smtp.example.com:25', $settings['transport_smtp_server']);
        self::assertSame('global-user', $settings['transport_smtp_username']);
        self::assertSame('global-password', $settings['transport_smtp_password']);
    }

    #[Test]
    public function profileWithDsnOnlyUsesDsnTransport(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['newsletter'] = ['dsn' => 'smtp://bulk.example.com:587'];
        $settings = $this->subject->getSettings('newsletter');
        self::assertSame('dsn', $settings['transport']);
        self::assertSame('smtp://bulk.example.com:587', $settings['dsn']);
    }

    #[Test]
    public function profileWithDsnKeepsExplicitlyConfiguredTransport(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']['local']['dsn'] = 'smtp://bulk.example.com:587';
        self::assertSame('sendmail', $this->subject->getSettings('local')['transport']);
    }

    #[Test]
    public function missingProfilesConfigurationIsTolerated(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = ['transport' => 'sendmail'];
        self::assertSame([], $this->subject->getProfileNames());
        self::assertSame(['transport' => 'sendmail'], $this->subject->getSettings());
    }
}
