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
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterRecordIsRenderedEvent;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Page\PageParts;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordsContentObjectTest extends FunctionalTestCase
{
    #[Test]
    public function afterRecordIsRenderedEventIsDispatchedForEachRecord(): void
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
        $request = new ServerRequest()
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $contentObjectRenderer->setRequest($request);
        $result = $contentObjectRenderer->getContentObject('RECORDS')->render([
            'tables' => 'tt_content',
            'source' => '2,1',
            'dontCheckPid' => 1,
            'conf.' => [
                'tt_content' => 'TEXT',
                'tt_content.' => ['field' => 'header'],
            ],
        ]);

        self::assertSame('[Second][First]', $result);
        self::assertCount(2, $dispatchedEvents);
        self::assertSame('tt_content.header', $dispatchedEvents[0]->getRecord()->getFullType());
        self::assertSame(2, $dispatchedEvents[0]->getRecord()->getUid());
        self::assertSame($pageInformation, $dispatchedEvents[0]->getRequest()->getAttribute('frontend.page.information'));
    }
}
