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

namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterRecordIsRenderedEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\ModifyRecordsAfterFetchingContentEvent;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Page\PageParts;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ContentContentObjectTest extends FunctionalTestCase
{
    #[Test]
    public function modifyRecordsAfterFetchingContentEventIsCalled(): void
    {
        $records = [['uid' => 2004, 'title' => 'my content']];
        $finalContent = 'my final content';
        $modifyRecordsAfterFetchingContentEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'modify-records-after-fetching-content-listener',
            static function (ModifyRecordsAfterFetchingContentEvent $event) use (&$modifyRecordsAfterFetchingContentEvent, $records, $finalContent) {
                $modifyRecordsAfterFetchingContentEvent = $event;
                $modifyRecordsAfterFetchingContentEvent->setRecords($records);
                $modifyRecordsAfterFetchingContentEvent->setFinalContent($finalContent);
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(ModifyRecordsAfterFetchingContentEvent::class, 'modify-records-after-fetching-content-listener');

        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $contentObjectRenderer->setRequest($request);
        $subject = $contentObjectRenderer->getContentObject('CONTENT');
        $result = $subject->render(['table' => 'tt_content']);
        self::assertEquals($finalContent, $result);
        self::assertInstanceOf(ModifyRecordsAfterFetchingContentEvent::class, $modifyRecordsAfterFetchingContentEvent);
        self::assertEquals($records, $modifyRecordsAfterFetchingContentEvent->getRecords());
        self::assertEquals($finalContent, $modifyRecordsAfterFetchingContentEvent->getFinalContent());
    }

    #[Test]
    public function afterRecordIsRenderedEventIsDispatchedForEachRecord(): void
    {
        [$result, $dispatchedEvents, $pageInformation] = $this->renderContentAndCollectAfterRecordIsRenderedEvents([
            'table' => 'tt_content',
            'select.' => ['orderBy' => 'sorting'],
            'renderObj' => 'TEXT',
            'renderObj.' => ['field' => 'header'],
        ]);

        self::assertSame('[First][Second]', $result);
        self::assertCount(2, $dispatchedEvents);
        self::assertSame('tt_content.header', $dispatchedEvents[1]->getRecord()->getFullType());
        self::assertSame(2, $dispatchedEvents[1]->getRecord()->getUid());
        self::assertSame('Second', $dispatchedEvents[1]->getRecord()->get('header'));
        self::assertSame($pageInformation, $dispatchedEvents[1]->getRequest()->getAttribute('frontend.page.information'));
    }

    #[Test]
    public function afterRecordIsRenderedEventProvidesRawRecordIfRowLacksSystemFields(): void
    {
        [$result, $dispatchedEvents] = $this->renderContentAndCollectAfterRecordIsRenderedEvents([
            'table' => 'tt_content',
            'select.' => ['orderBy' => 'sorting', 'selectFields' => 'uid, pid, {#CType}, header'],
            'renderObj' => 'TEXT',
            'renderObj.' => ['field' => 'header'],
        ]);

        self::assertSame('[First][Second]', $result);
        self::assertCount(2, $dispatchedEvents);
        self::assertInstanceOf(RawRecord::class, $dispatchedEvents[1]->getRecord());
        self::assertSame('tt_content.header', $dispatchedEvents[1]->getRecord()->getFullType());
        self::assertSame('Second', $dispatchedEvents[1]->getRecord()->get('header'));
    }

    #[Test]
    public function afterRecordIsRenderedEventIsNotDispatchedIfRecordCannotBeCreated(): void
    {
        [$result, $dispatchedEvents] = $this->renderContentAndCollectAfterRecordIsRenderedEvents([
            'table' => 'tt_content',
            'select.' => ['orderBy' => 'sorting', 'selectFields' => 'uid, pid, header'],
            'renderObj' => 'TEXT',
            'renderObj.' => ['field' => 'header'],
        ]);

        self::assertSame('FirstSecond', $result);
        self::assertCount(0, $dispatchedEvents);
    }

    /**
     * @return array{string, list<AfterRecordIsRenderedEvent>, PageInformation}
     */
    private function renderContentAndCollectAfterRecordIsRenderedEvents(array $conf): array
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AfterRecordIsRendered.csv');
        $dispatchedEvents = [];
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-record-is-rendered-listener',
            static function (AfterRecordIsRenderedEvent $event) use (&$dispatchedEvents) {
                $dispatchedEvents[] = $event;
                $event->setRenderedRecord('[' . $event->getRenderedRecord() . ']');
            }
        );
        $container->get(ListenerProvider::class)->addListener(AfterRecordIsRenderedEvent::class, 'after-record-is-rendered-listener');

        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $contentObjectRenderer->setRequest($request);
        $result = $contentObjectRenderer->getContentObject('CONTENT')->render($conf);
        return [$result, $dispatchedEvents, $pageInformation];
    }
}
