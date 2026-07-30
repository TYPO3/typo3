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
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Core\Mail\MailerFactory;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\MailProfileResolver;
use TYPO3\CMS\Core\Mail\TransportFactory;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[BackupGlobals(true)]
final class MailerFactoryTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = [
            'transport' => 'null',
            'defaultMailFromAddress' => 'noreply@example.com',
            'profiles' => [
                'newsletter' => [
                    'transport' => 'sendmail',
                    'transport_sendmail_command' => '/usr/sbin/sendmail -bs',
                    'defaultMailFromAddress' => 'newsletter@example.com',
                ],
            ],
        ];
    }

    #[Test]
    public function mailerWithoutProfileIsTheInjectedMailer(): void
    {
        $mailer = self::createStub(MailerInterface::class);
        $subject = $this->createSubject(null, $mailer);
        self::assertSame($mailer, $subject->getMailer());
        self::assertSame($mailer, $subject->getMailer(''));
    }

    #[Test]
    public function mailerOfProfileUsesTransportOfThatProfile(): void
    {
        self::assertInstanceOf(SendmailTransport::class, $this->createSubject()->getMailer('newsletter')->getTransport());
    }

    #[Test]
    public function mailerOfProfileIsReused(): void
    {
        $subject = $this->createSubject();
        self::assertSame($subject->getMailer('newsletter'), $subject->getMailer('newsletter'));
        self::assertSame($subject->getMailer(), $subject->getMailer());
        self::assertNotSame($subject->getMailer(), $subject->getMailer('newsletter'));
    }

    #[Test]
    public function unknownProfileThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1785920446);
        $this->createSubject()->getMailer('does-not-exist');
    }

    #[Test]
    public function mailerOfProfileUsesSenderOfThatProfile(): void
    {
        $transport = new class implements TransportInterface {
            public ?Email $sentMessage = null;

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                if ($message instanceof Email) {
                    $this->sentMessage = $message;
                }
                return null;
            }

            public function __toString(): string
            {
                return 'recording';
            }
        };
        $transportFactory = self::createStub(TransportFactory::class);
        $transportFactory->method('get')->willReturn($transport);

        $this->createSubject($transportFactory)->getMailer('newsletter')->send(
            new Email()->to('editor@example.com')->text('hello')
        );

        self::assertSame('newsletter@example.com', $transport->sentMessage?->getFrom()[0]->getAddress());
    }

    private function createSubject(?TransportFactory $transportFactory = null, ?MailerInterface $mailer = null): MailerFactory
    {
        $eventDispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        };
        return new MailerFactory(
            new MailProfileResolver(),
            $transportFactory ?? $this->createTransportFactory(),
            $eventDispatcher,
            $mailer ?? self::createStub(MailerInterface::class)
        );
    }

    private function createTransportFactory(): TransportFactory
    {
        return new TransportFactory(
            self::createStub(SymfonyEventDispatcherInterface::class),
            self::createStub(LogManagerInterface::class),
            new NullLogger(),
            self::createStub(FileNameValidator::class)
        );
    }
}
