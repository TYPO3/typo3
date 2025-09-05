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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Finishers;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Domain\Finishers\EmailFinisher;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Event\BeforeEmailFinisherInitializedEvent;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EmailFinisherTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function beforeEmailFinisherInitializedEventIsCalled(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');

        // Define a TranslationService mock which skips all the translation but simply returns the $optionValue
        // without any further processing.
        $translationServiceMock = $this->createMock(TranslationService::class);
        $translationServiceMock->method('translateFinisherOption')->willReturnCallback(static function () {
            return func_get_arg(3);
        });
        $container->set(TranslationService::class, $translationServiceMock);

        // Define the MailerInterface implementation to be able to test the correct subject in the final mail.
        // The method ->send() must be called exactly once and the subject of the passed FluidMail object must match
        // our defined string.
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once())->method('send')->willReturnCallback(static function (FluidEmail $mail) {
            self::assertEquals('dynamic event subject', $mail->getSubject());
        });
        $container->set(MailerInterface::class, $mailerMock);

        // define a custom event to set "subject" to the defined string
        $beforeEmailFinisherInitializedEvent = null;
        $container->set(
            'before-email-finisher-initialized-event-listener',
            static function (BeforeEmailFinisherInitializedEvent $event) use (&$beforeEmailFinisherInitializedEvent) {
                $beforeEmailFinisherInitializedEvent = $event;
                $options = $event->getOptions();
                $options['subject'] = 'dynamic event subject';
                $event->setOptions($options);
            }
        );
        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforeEmailFinisherInitializedEvent::class, 'before-email-finisher-initialized-event-listener');

        // create $subject with required configurations
        $subject = new EmailFinisher($this->get(EventDispatcher::class));
        $subject->setOptions([
            'senderAddress' => 'sender@example.org',
            'templateName' => 'template',
            'recipients' => ['user@example.org' => 'John Doe'],
            'subject' => 'default subject',
        ]);
        // finally execute the finisher
        $subject->execute(new FinisherContext($this->createMock(FormRuntime::class), $this->createMock(Request::class)));

        self::assertInstanceOf(BeforeEmailFinisherInitializedEvent::class, $beforeEmailFinisherInitializedEvent);
        self::assertEquals([
            'senderAddress' => 'sender@example.org',
            'templateName' => 'template',
            'recipients' => ['user@example.org' => 'John Doe'],
            'subject' => 'dynamic event subject',
        ], $beforeEmailFinisherInitializedEvent->getOptions());
    }
}
