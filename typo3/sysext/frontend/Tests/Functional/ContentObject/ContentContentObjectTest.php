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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Event\ModifyRecordsAfterFetchingContentEvent;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
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

        $typoScriptFrontendController = GeneralUtility::makeInstance(TypoScriptFrontendController::class);
        $contentObjectRenderer = new ContentObjectRenderer($typoScriptFrontendController);
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = (new ServerRequest())
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
}
