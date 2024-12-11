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

namespace TYPO3\CMS\Core\Tests\Functional\Mail;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Mail\Event\AfterMailerSentMessageEvent;
use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MailerTest extends FunctionalTestCase
{
    #[Test]
    public function mailerEventsAreTriggered(): void
    {
        $beforeMailerSentMessageEvent = null;
        $afterMailerSentMessageEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'before-mailer-sent-message-listener',
            static function (BeforeMailerSentMessageEvent $event) use (&$beforeMailerSentMessageEvent) {
                $beforeMailerSentMessageEvent = $event;
            }
        );
        $container->set(
            'after-mailer-sent-message-listener',
            static function (AfterMailerSentMessageEvent $event) use (&$afterMailerSentMessageEvent) {
                $afterMailerSentMessageEvent = $event;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeMailerSentMessageEvent::class, 'before-mailer-sent-message-listener');
        $eventListener->addListener(AfterMailerSentMessageEvent::class, 'after-mailer-sent-message-listener');

        $message = new RawMessage('some message');
        $envelope = new Envelope(new Address('kasperYYYY@typo3.org'), [new Address('acme@example.com')]);
        $mailer = (new Mailer(new NullTransport(), $container->get(EventDispatcherInterface::class)));

        $mailer->send($message, $envelope);

        self::assertInstanceOf(BeforeMailerSentMessageEvent::class, $beforeMailerSentMessageEvent);
        self::assertEquals($message, $beforeMailerSentMessageEvent->getMessage());
        self::assertEquals($envelope, $beforeMailerSentMessageEvent->getEnvelope());
        self::assertEquals($mailer, $beforeMailerSentMessageEvent->getMailer());

        self::assertInstanceOf(AfterMailerSentMessageEvent::class, $afterMailerSentMessageEvent);
        self::assertEquals($mailer, $afterMailerSentMessageEvent->getMailer());
    }
}
