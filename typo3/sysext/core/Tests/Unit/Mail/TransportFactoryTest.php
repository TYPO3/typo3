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
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;
use TYPO3\CMS\Core\Mail\FileSpool;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Mail\MemorySpool;
use TYPO3\CMS\Core\Mail\TransportFactory;
use TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures\FakeFileSpoolFixture;
use TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures\FakeInvalidSpoolFixture;
use TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures\FakeMemorySpoolFixture;
use TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures\FakeValidSpoolFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TransportFactoryTest extends UnitTestCase
{
    use ProphecyTrait;

    protected bool $resetSingletonInstances = true;

    protected function getSubject(&$eventDispatcher): TransportFactory
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->willReturn(Argument::any());

        $logger = $this->prophesize(LoggerInterface::class);

        $logManager = $this->prophesize(LogManagerInterface::class);
        $logManager->getLogger(Argument::any())->willReturn($logger->reveal());
        $logManager->getLogger()->willReturn($logger->reveal());

        $transportFactory = new TransportFactory($eventDispatcher->reveal(), $logManager->reveal());
        $transportFactory->setLogger($logger->reveal());

        return $transportFactory;
    }

    /**
     * @test
     */
    public function getReturnsSpoolTransportUsingFileSpool(): void
    {
        $mailSettings = [
            'transport' => 'sendmail',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_smtp_restart_threshold' => 0,
            'transport_smtp_restart_threshold_sleep' => 0,
            'transport_smtp_ping_threshold' => 0,
            'transport_smtp_stream_options' => [],
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
            'transport_spool_type' => 'file',
            'transport_spool_filepath' => '.',
        ];

        // Register fixture class
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][FileSpool::class]['className'] = FakeFileSpoolFixture::class;

        $transport = $this->getSubject($eventDispatcher)->get($mailSettings);
        self::assertInstanceOf(DelayedTransportInterface::class, $transport);
        self::assertInstanceOf(FakeFileSpoolFixture::class, $transport);

        $path = $transport->getPath();
        self::assertStringContainsString($mailSettings['transport_spool_filepath'], $path);
    }

    /**
     * @test
     */
    public function getReturnsSpoolTransportUsingMemorySpool(): void
    {
        $mailSettings = [
            'transport' => 'mail',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_smtp_restart_threshold' => 0,
            'transport_smtp_restart_threshold_sleep' => 0,
            'transport_smtp_ping_threshold' => 0,
            'transport_smtp_stream_options' => [],
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
            'transport_spool_type' => 'memory',
            'transport_spool_filepath' => Environment::getVarPath() . '/messages/',
        ];

        // Register fixture class
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][MemorySpool::class]['className'] = FakeMemorySpoolFixture::class;

        $transport = $this->getSubject($eventDispatcher)->get($mailSettings);
        self::assertInstanceOf(DelayedTransportInterface::class, $transport);
        self::assertInstanceOf(MemorySpool::class, $transport);
    }

    /**
     * @test
     */
    public function getReturnsSpoolTransportUsingCustomSpool(): void
    {
        $mailSettings = [
            'transport' => 'sendmail',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_smtp_restart_threshold' => 0,
            'transport_smtp_restart_threshold_sleep' => 0,
            'transport_smtp_ping_threshold' => 0,
            'transport_smtp_stream_options' => [],
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
            'transport_spool_type' => FakeValidSpoolFixture::class,
            'transport_spool_filepath' => Environment::getVarPath() . '/messages/',
        ];

        $transport = $this->getSubject($eventDispatcher)->get($mailSettings);
        self::assertInstanceOf(DelayedTransportInterface::class, $transport);
        self::assertInstanceOf(FakeValidSpoolFixture::class, $transport);

        self::assertSame($mailSettings, $transport->getSettings());
    }

    /**
     * @test
     */
    public function getThrowsRuntimeExceptionForInvalidCustomSpool(): void
    {
        $this->expectExceptionCode(1466799482);

        $mailSettings = [
            'transport' => 'mail',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_smtp_restart_threshold' => 0,
            'transport_smtp_restart_threshold_sleep' => 0,
            'transport_smtp_ping_threshold' => 0,
            'transport_smtp_stream_options' => [],
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
            'transport_spool_type' => FakeInvalidSpoolFixture::class,
            'transport_spool_filepath' => Environment::getVarPath() . '/messages/',
        ];

        $this->getSubject($eventDispatcher)->get($mailSettings);
    }

    /**
     * @test
     */
    public function getThrowsExceptionForMissingDsnConfig(): void
    {
        $this->expectExceptionCode(1615021869);

        $mailSettings = [
            'transport' => 'dsn',
            'dsn' => '',
        ];

        $this->getSubject($eventDispatcher)->get($mailSettings);
    }

    /**
     * @test
     */
    public function dsnTransportCallsDispatchOfDispatcher(): void
    {
        $mailSettings = [
            'transport' => 'dsn',
            'dsn' => 'smtp://user:pass@smtp.example.com:25',
        ];

        $transport = $this->getSubject($eventDispatcher)->get($mailSettings);
        $message = new MailMessage();
        $message->setTo(['foo@bar.com'])
            ->text('foo')
            ->from('bar@foo.com')
        ;
        try {
            $transport->send($message);
        } catch (TransportExceptionInterface $exception) {
            // connection is not valid in tests, so we just catch the exception here.
        }

        $eventDispatcher->dispatch(Argument::any())->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function getReturnsMailerTransportInterface(): void
    {
        $mailSettings = [
            'transport' => 'smtp',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_smtp_restart_threshold' => 0,
            'transport_smtp_restart_threshold_sleep' => 0,
            'transport_smtp_ping_threshold' => 0,
            'transport_smtp_stream_options' => [],
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
            'transport_spool_type' => '',
            'transport_spool_filepath' => Environment::getVarPath() . '/messages/',
        ];

        $transport = $this->getSubject($eventDispatcher)->get($mailSettings);
        self::assertInstanceOf(TransportInterface::class, $transport);
    }

    /**
     * @test
     */
    public function smtpTransportCallsDispatchOfDispatcher(): void
    {
        $mailSettings = [
            'transport' => 'smtp',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_smtp_restart_threshold' => 0,
            'transport_smtp_restart_threshold_sleep' => 0,
            'transport_smtp_ping_threshold' => 0,
            'transport_smtp_stream_options' => [],
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
        ];

        $transport = $this->getSubject($eventDispatcher)->get($mailSettings);

        $message = new MailMessage();
        $message->setTo(['foo@bar.com'])
            ->text('foo')
            ->from('bar@foo.com')
        ;
        try {
            $transport->send($message);
        } catch (TransportExceptionInterface $exception) {
            // connection is not valid in tests, so we just catch the exception here.
        }

        $eventDispatcher->dispatch(Argument::any())->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function sendmailTransportCallsDispatchOfDispatcher(): void
    {
        $mailSettings = [
            'transport' => 'sendmail',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_smtp_restart_threshold' => 0,
            'transport_smtp_restart_threshold_sleep' => 0,
            'transport_smtp_ping_threshold' => 0,
            'transport_smtp_stream_options' => [],
            'transport_sendmail_command' => '',
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
        ];

        $transport = $this->getSubject($eventDispatcher)->get($mailSettings);
        $message = new MailMessage();
        $message->setTo(['foo@bar.com'])
            ->text('foo')
            ->from('bar@foo.com')
        ;
        try {
            $transport->send($message);
        } catch (TransportExceptionInterface $exception) {
            // connection is not valid in tests, so we just catch the exception here.
        }

        $eventDispatcher->dispatch(Argument::any())->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function nullTransportCallsDispatchOfDispatcher(): void
    {
        $mailSettings = [
            'transport' => NullTransport::class,
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_encrypt' => '',
            'transport_smtp_username' => '',
            'transport_smtp_password' => '',
            'transport_sendmail_command' => '',
            'transport_smtp_restart_threshold' => 0,
            'transport_smtp_restart_threshold_sleep' => 0,
            'transport_smtp_ping_threshold' => 0,
            'transport_smtp_stream_options' => [],
            'transport_mbox_file' => '',
            'defaultMailFromAddress' => '',
            'defaultMailFromName' => '',
        ];

        $transport = $this->getSubject($eventDispatcher)->get($mailSettings);
        $message = new MailMessage();
        $message->setTo(['foo@bar.com'])
            ->text('foo')
            ->from('bar@foo.com')
        ;
        $transport->send($message);

        $eventDispatcher->dispatch(Argument::any())->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function smtpTransportIsCorrectlyConfigured(): void
    {
        $mailSettings = [
            'transport' => 'smtp',
            'transport_smtp_server' => 'localhost:25',
            'transport_smtp_username' => 'username',
            'transport_smtp_password' => 'password',
            'transport_smtp_domain' => 'example.com',
        ];

        $transport = $this->getSubject($eventDispatcher)->get($mailSettings);

        self::assertInstanceOf(EsmtpTransport::class, $transport);
        self::assertSame(explode(':', $mailSettings['transport_smtp_server'], 2)[0], $transport->getStream()->getHost());
        self::assertSame((int)explode(':', $mailSettings['transport_smtp_server'], 2)[1], $transport->getStream()->getPort());
        self::assertSame($mailSettings['transport_smtp_username'], $transport->getUsername());
        self::assertSame($mailSettings['transport_smtp_password'], $transport->getPassword());
        self::assertSame($mailSettings['transport_smtp_domain'], $transport->getLocalDomain());
    }
}
