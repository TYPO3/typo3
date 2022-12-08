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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\OnlineMedia\Processing;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Event\AfterVideoPreviewFetchedEvent;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\YouTubeHelper;
use TYPO3\CMS\Core\Resource\OnlineMedia\Processing\PreviewProcessing;
use TYPO3\CMS\Core\Resource\Processing\AbstractTask;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PreviewProcessingTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function afterVideoPreviewFetchedEventIsTriggered(): void
    {
        $afterVideoPreviewFetchedEvent = null;
        $afterVideoPreviewFetchedEventListener = 'after-video-preview-fetched';
        $initialPreviewImageFilename = '';
        $oldPreviewImageFilename = '/var/www/previewOld.png';
        $newPreviewImageFilename = '/var/www/previewNew.png';
        $onlineMediaId = '2004';

        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            $afterVideoPreviewFetchedEventListener,
            static function (AfterVideoPreviewFetchedEvent $event) use (
                &$afterVideoPreviewFetchedEvent,
                &$initialPreviewImageFilename,
                $newPreviewImageFilename
            ) {
                $initialPreviewImageFilename = $event->getPreviewImageFilename();
                $event->setPreviewImageFilename($newPreviewImageFilename);
                $afterVideoPreviewFetchedEvent = $event;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterVideoPreviewFetchedEvent::class, $afterVideoPreviewFetchedEventListener);

        $onlineMediaHelper = $this->getMockBuilder(YouTubeHelper::class)->disableOriginalConstructor()->getMock();
        $onlineMediaHelper->expects(self::atLeastOnce())->method('getPreviewImage')->willReturn($oldPreviewImageFilename);
        $onlineMediaHelper->expects(self::atLeastOnce())->method('getOnlineMediaId')->willReturn($onlineMediaId);
        $onlineMediaHelperRegistry = $this->getMockBuilder(OnlineMediaHelperRegistry::class)->disableOriginalConstructor()->getMock();
        $onlineMediaHelperRegistry->expects(self::atLeastOnce())->method('getOnlineMediaHelper')->willReturn($onlineMediaHelper);

        $subject = new PreviewProcessing(
            $onlineMediaHelperRegistry,
            $container->get(EventDispatcherInterface::class),
            $this->createMock(LocalImageProcessor::class)
        );

        $file = new File(['name' => 'MyVideo'], $this->createMock(ResourceStorage::class), []);
        $taskMock = $this->createMock(AbstractTask::class);
        $taskMock->method('getSourceFile')->willReturn($file);

        $subject->processTask($taskMock);

        self::assertInstanceOf(AfterVideoPreviewFetchedEvent::class, $afterVideoPreviewFetchedEvent);
        self::assertEquals($initialPreviewImageFilename, $oldPreviewImageFilename);
        self::assertEquals($file, $afterVideoPreviewFetchedEvent->getFile());
        self::assertEquals($onlineMediaId, $afterVideoPreviewFetchedEvent->getOnlineMediaId());
        self::assertEquals($newPreviewImageFilename, $afterVideoPreviewFetchedEvent->getPreviewImageFilename());
        self::assertEquals($newPreviewImageFilename, $afterVideoPreviewFetchedEvent->getPreviewImageFilename());
    }
}
