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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Mail\TransportFactory;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MailerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private $subject;
    private LogManagerInterface $logManager;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(Mailer::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $this->logManager = new class () implements LogManagerInterface {
            public function getLogger(string $name = ''): LoggerInterface
            {
                return new NullLogger();
            }
        };
        $this->eventDispatcher = new class () implements EventDispatcherInterface {
            public function dispatch(object $event, ?string $eventName = null): object
            {
                return $event;
            }
        };
    }

    #[Test]
    public function injectedSettingsAreNotReplacedByGlobalSettings(): void
    {
        $settings = ['transport' => 'mbox', 'transport_mbox_file' => '/path/to/file'];
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = [
            'transport' => 'sendmail',
            'transport_sendmail_command' => 'sendmail -bs',
        ];
        $transportFactory = $this->createMock(TransportFactory::class);
        $transportFactory->expects($this->atLeastOnce())->method('get')->with($settings)
            ->willReturn($this->createMock(SendmailTransport::class));
        GeneralUtility::addInstance(TransportFactory::class, $transportFactory);
        $this->subject->injectMailSettings($settings);
        $this->subject->__construct();
    }

    #[Test]
    public function globalSettingsAreUsedIfNoSettingsAreInjected(): void
    {
        $settings = ($GLOBALS['TYPO3_CONF_VARS']['MAIL'] = [
            'transport' => 'sendmail',
            'transport_sendmail_command' => 'sendmail -bs',
        ]);
        $transportFactory = $this->createMock(TransportFactory::class);
        $transportFactory->expects($this->atLeastOnce())->method('get')->with($settings)
            ->willReturn($this->createMock(SendmailTransport::class));
        GeneralUtility::addInstance(TransportFactory::class, $transportFactory);
        $this->subject->injectMailSettings($settings);
        $this->subject->__construct();
    }

    public static function wrongConfigurationProvider(): array
    {
        return [
            'smtp but no host' => [['transport' => 'smtp']],
            'mbox but no file' => [['transport' => 'mbox']],
        ];
    }

    #[DataProvider('wrongConfigurationProvider')]
    #[Test]
    public function wrongConfigurationThrowsException(array $settings): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1291068569);
        $transportFactory = new TransportFactory($this->eventDispatcher, $this->logManager, new Logger('foo'), new FileNameValidator());
        GeneralUtility::addInstance(TransportFactory::class, $transportFactory);
        $this->subject->injectMailSettings($settings);
        $this->subject->__construct();
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function providingCorrectClassnameDoesNotThrowException(): void
    {
        $transportFactory = new TransportFactory($this->eventDispatcher, $this->logManager, new Logger('foo'), new FileNameValidator());
        GeneralUtility::addInstance(TransportFactory::class, $transportFactory);
        $this->subject->injectMailSettings(['transport' => NullTransport::class]);
        $this->subject->__construct();
    }

    #[Test]
    public function noPortSettingSetsPortTo25(): void
    {
        $transportFactory = new TransportFactory($this->eventDispatcher, $this->logManager, new Logger('foo'), new FileNameValidator());
        GeneralUtility::addInstance(TransportFactory::class, $transportFactory);
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getStream()->getPort();
        self::assertEquals(25, $port);
    }

    #[Test]
    public function emptyPortSettingSetsPortTo25(): void
    {
        $transportFactory = new TransportFactory($this->eventDispatcher, $this->logManager, new Logger('foo'), new FileNameValidator());
        GeneralUtility::addInstance(TransportFactory::class, $transportFactory);
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost:']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getStream()->getPort();
        self::assertEquals(25, $port);
    }

    #[Test]
    public function givenPortSettingIsRespected(): void
    {
        $transportFactory = new TransportFactory($this->eventDispatcher, $this->logManager, new Logger('foo'), new FileNameValidator());
        GeneralUtility::addInstance(TransportFactory::class, $transportFactory);
        $this->subject->injectMailSettings(['transport' => 'smtp', 'transport_smtp_server' => 'localhost:12345']);
        $this->subject->__construct();
        $port = $this->subject->getTransport()->getStream()->getPort();
        self::assertEquals(12345, $port);
    }

    public static function getRealTransportReturnsNoSpoolTransportProvider(): array
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

    #[DataProvider('getRealTransportReturnsNoSpoolTransportProvider')]
    #[Test]
    public function getRealTransportReturnsNoSpoolTransport($settings): void
    {
        $transportFactory = new TransportFactory($this->eventDispatcher, $this->logManager, new Logger('foo'), new FileNameValidator());
        GeneralUtility::addInstance(TransportFactory::class, $transportFactory);
        $this->subject->injectMailSettings($settings);
        $transport = $this->subject->getRealTransport();
        self::assertInstanceOf(TransportInterface::class, $transport);
        self::assertNotInstanceOf(DelayedTransportInterface::class, $transport);
    }
}
