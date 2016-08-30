<?php
namespace TYPO3\CMS\Core\Tests\Unit\Mail;

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
use TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures\FakeTransportFixture;

/**
 * Testcase for the TYPO3\CMS\Core\Mail\Mailer class.
 */
class MailerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Mail\Mailer
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = $this->getMock(\TYPO3\CMS\Core\Mail\Mailer::class, ['emitPostInitializeMailerSignal'], [], '', false);
    }

    //////////////////////////
    // Tests concerning TYPO3\CMS\Core\Mail\Mailer
    //////////////////////////
    /**
     * @test
     */
    public function injectedSettingsAreNotReplacedByGlobalSettings()
    {
        $settings = ['transport' => 'mbox', 'transport_mbox_file' => '/path/to/file'];
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = ['transport' => 'sendmail', 'transport_sendmail_command' => 'sendmail'];
        $this->subject->injectMailSettings($settings);
        $this->subject->__construct();
        $this->assertAttributeSame($settings, 'mailSettings', $this->subject);
    }

    /**
     * @test
     */
    public function globalSettingsAreUsedIfNoSettingsAreInjected()
    {
        $settings = ($GLOBALS['TYPO3_CONF_VARS']['MAIL'] = ['transport' => 'sendmail', 'transport_sendmail_command' => 'sendmail']);
        $this->subject->__construct();
        $this->assertAttributeSame($settings, 'mailSettings', $this->subject);
    }

    /**
     * Data provider for wrongConfigigurationThrowsException
     *
     * @return array Data sets
     */
    public static function wrongConfigigurationProvider()
    {
        return [
            'smtp but no host' => [['transport' => 'smtp']],
            'sendmail but no command' => [['transport' => 'sendmail']],
            'mbox but no file' => [['transport' => 'mbox']],
            'no instance of Swift_Transport' => [['transport' => \TYPO3\CMS\Core\Messaging\ErrorpageMessage::class]]
        ];
    }

    /**
     * @test
     * @param $settings
     * @dataProvider wrongConfigigurationProvider
     * @expectedException \TYPO3\CMS\Core\Exception
     */
    public function wrongConfigigurationThrowsException($settings)
    {
        $this->subject->injectMailSettings($settings);
        $this->subject->__construct();
    }

    /**
     * @test
     */
    public function providingCorrectClassnameDoesNotThrowException()
    {
        $this->subject->injectMailSettings(['transport' => FakeTransportFixture::class]);
        $this->subject->__construct();
    }

    /**
     * @test
     */
    public function noPortSettingSetsPortTo25()
    {
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getPort();
        $this->assertEquals(25, $port);
    }

    /**
     * @test
     */
    public function emptyPortSettingSetsPortTo25()
    {
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost:']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getPort();
        $this->assertEquals(25, $port);
    }

    /**
     * @test
     */
    public function givenPortSettingIsRespected()
    {
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost:12345']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getPort();
        $this->assertEquals(12345, $port);
    }
}
