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

namespace TYPO3\CMS\Core\Tests\Functional\Html\EventListener;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Html\Event\AfterTransformTextForPersistenceEvent;
use TYPO3\CMS\Core\Html\Event\AfterTransformTextForRichTextEditorEvent;
use TYPO3\CMS\Core\Html\Event\BeforeTransformTextForPersistenceEvent;
use TYPO3\CMS\Core\Html\Event\BeforeTransformTextForRichTextEditorEvent;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RteHtmlParserTest extends FunctionalTestCase
{
    protected array $procOptions = ['overruleMode' => 'default', 'allowTagsOutside' => 'hr,abbr,figure'];

    #[Test]
    public function beforeTransformTextForRichTextEditorEventIsTriggered(): void
    {
        $beforeTransformTextForRichTextEditorEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'before-transform-text-for-richtexteditor',
            static function (BeforeTransformTextForRichTextEditorEvent $event) use (&$beforeTransformTextForRichTextEditorEvent) {
                $beforeTransformTextForRichTextEditorEvent = $event;
                $beforeTransformTextForRichTextEditorEvent->setHtmlContent($event->getHtmlContent() . '[modified]');
            }
        );

        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(BeforeTransformTextForRichTextEditorEvent::class, 'before-transform-text-for-richtexteditor');

        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $subject = new RteHtmlParser($eventDispatcher);

        $result = $subject->transformTextForRichTextEditor('Something something dark side', $this->procOptions);
        self::assertInstanceOf(BeforeTransformTextForRichTextEditorEvent::class, $beforeTransformTextForRichTextEditorEvent);
        self::assertEquals('<p>Something something dark side[modified]</p>', $result);
    }

    #[Test]
    public function afterTransformTextForRichTextEditorEventIsTriggered(): void
    {
        $afterTransformTextForRichTextEditorEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-transform-text-for-richtexteditor',
            static function (AfterTransformTextForRichTextEditorEvent $event) use (&$afterTransformTextForRichTextEditorEvent) {
                $afterTransformTextForRichTextEditorEvent = $event;
                $afterTransformTextForRichTextEditorEvent->setHtmlContent($event->getHtmlContent() . '[modified]');
            }
        );

        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(AfterTransformTextForRichTextEditorEvent::class, 'after-transform-text-for-richtexteditor');

        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $subject = new RteHtmlParser($eventDispatcher);

        $result = $subject->transformTextForRichTextEditor('Something something dark side', $this->procOptions);
        self::assertInstanceOf(AfterTransformTextForRichTextEditorEvent::class, $afterTransformTextForRichTextEditorEvent);
        self::assertEquals('<p>Something something dark side</p>[modified]', $result);
    }

    #[Test]
    public function beforeTransformTextForPersistenceEventIsTriggered(): void
    {
        $beforeTransformTextForPersistenceEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'before-transform-text-for-richtexteditor',
            static function (BeforeTransformTextForPersistenceEvent $event) use (&$beforeTransformTextForPersistenceEvent) {
                $beforeTransformTextForPersistenceEvent = $event;
                $beforeTransformTextForPersistenceEvent->setHtmlContent($event->getHtmlContent() . '[modified]');
            }
        );

        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(BeforeTransformTextForPersistenceEvent::class, 'before-transform-text-for-richtexteditor');

        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $subject = new RteHtmlParser($eventDispatcher);

        $result = $subject->transformTextForPersistence("\n\nSomething something dark side\n\n", $this->procOptions);
        self::assertInstanceOf(BeforeTransformTextForPersistenceEvent::class, $beforeTransformTextForPersistenceEvent);
        self::assertEquals(' Something something dark side [modified]', $result);
    }

    #[Test]
    public function afterTransformTextForPersistenceEventIsTriggered(): void
    {
        $afterTransformTextForPersistenceEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-transform-text-for-richtexteditor',
            static function (AfterTransformTextForPersistenceEvent $event) use (&$afterTransformTextForPersistenceEvent) {
                $afterTransformTextForPersistenceEvent = $event;
                $afterTransformTextForPersistenceEvent->setHtmlContent($event->getHtmlContent() . '[modified]');
            }
        );

        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(AfterTransformTextForPersistenceEvent::class, 'after-transform-text-for-richtexteditor');

        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $subject = new RteHtmlParser($eventDispatcher);

        $result = $subject->transformTextForPersistence("\n\nSomething something dark side\n\n", $this->procOptions);
        self::assertInstanceOf(AfterTransformTextForPersistenceEvent::class, $afterTransformTextForPersistenceEvent);
        self::assertEquals(' Something something dark side [modified]', $result);
    }
}
