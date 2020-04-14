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

use Prophecy\Argument;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Mail\TransportFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MailerTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var Mailer
     */
    protected $subject;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(Mailer::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @test
     */
    public function injectedSettingsAreNotReplacedByGlobalSettings()
    {
        $settings = ['transport' => 'mbox', 'transport_mbox_file' => '/path/to/file'];
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = ['transport' => 'sendmail', 'transport_sendmail_command' => 'sendmail -bs'];

        $transportFactory = $this->prophesize(TransportFactory::class);
        $transportFactory->get(Argument::any())->willReturn($this->prophesize(SendmailTransport::class));
        GeneralUtility::setSingletonInstance(TransportFactory::class, $transportFactory->reveal());
        $this->subject->injectMailSettings($settings);
        $this->subject->__construct();

        $transportFactory->get($settings)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function globalSettingsAreUsedIfNoSettingsAreInjected()
    {
        $settings = ($GLOBALS['TYPO3_CONF_VARS']['MAIL'] = ['transport' => 'sendmail', 'transport_sendmail_command' => 'sendmail -bs']);
        $this->subject->__construct();
        $transportFactory = $this->prophesize(TransportFactory::class);
        $transportFactory->get(Argument::any())->willReturn($this->prophesize(SendmailTransport::class));
        GeneralUtility::setSingletonInstance(TransportFactory::class, $transportFactory->reveal());
        $this->subject->injectMailSettings($settings);
        $this->subject->__construct();

        $transportFactory->get($settings)->shouldHaveBeenCalled();
    }

    /**
     * Data provider for wrongConfigurationThrowsException
     *
     * @return array Data sets
     */
    public static function wrongConfigurationProvider()
    {
        return [
            'smtp but no host' => [['transport' => 'smtp']],
            'mbox but no file' => [['transport' => 'mbox']],
            'no instance of TransportInterface' => [['transport' => ErrorPageController::class]]
        ];
    }

    /**
     * @test
     * @param $settings
     * @dataProvider wrongConfigurationProvider
     */
    public function wrongConfigurationThrowsException($settings)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1291068569);

        $this->subject->injectMailSettings($settings);
        $this->subject->__construct();
    }

    /**
     * @test
     */
    public function providingCorrectClassnameDoesNotThrowException()
    {
        $this->subject->injectMailSettings(['transport' => NullTransport::class]);
        $this->subject->__construct();
    }

    /**
     * @test
     */
    public function noPortSettingSetsPortTo25()
    {
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getStream()->getPort();
        self::assertEquals(25, $port);
    }

    /**
     * @test
     */
    public function emptyPortSettingSetsPortTo25()
    {
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost:']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getStream()->getPort();
        self::assertEquals(25, $port);
    }

    /**
     * @test
     */
    public function givenPortSettingIsRespected()
    {
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost:12345']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getStream()->getPort();
        self::assertEquals(12345, $port);
    }

    /**
     * @test
     * @dataProvider getRealTransportReturnsNoSpoolTransportProvider
     */
    public function getRealTransportReturnsNoSpoolTransport($settings)
    {
        $this->subject->injectMailSettings($settings);
        $transport = $this->subject->getRealTransport();

        self::assertInstanceOf(TransportInterface::class, $transport);
        self::assertNotInstanceOf(DelayedTransportInterface::class, $transport);
    }

    /**
     * Data provider for getRealTransportReturnsNoSpoolTransport
     *
     * @return array Data sets
     */
    public static function getRealTransportReturnsNoSpoolTransportProvider()
    {
        return [
            'without spool' => [[
                'transport' => 'sendmail',
                'spool' => '',
            ]],
            'with spool' => [[
                'transport' => 'sendmail',
                'spool' => 'memory',
            ]],
        ];
    }
}
