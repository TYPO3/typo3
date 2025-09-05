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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\OnlineMedia\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Event\AfterVideoPreviewFetchedEvent;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\YouTubeHelper;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AfterVideoPreviewFetchedEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $onlineMediaId = '2004';
        $onlineMediaHelper = $this->getMockBuilder(YouTubeHelper::class)->disableOriginalConstructor()->getMock();
        $onlineMediaHelper->expects($this->atLeastOnce())->method('getOnlineMediaId')->willReturn($onlineMediaId);

        $oldPreviewImageFilename = '/var/www/previewOld.png';
        $newPreviewImageFilename = '/var/www/previewNew.png';
        $file = new File(['name' => 'MyVideo'], $this->createMock(ResourceStorage::class), []);

        $event = new AfterVideoPreviewFetchedEvent(
            $file,
            $onlineMediaHelper,
            $oldPreviewImageFilename
        );

        self::assertEquals($file, $event->getFile());
        self::assertEquals($onlineMediaId, $event->getOnlineMediaId());
        self::assertEquals($oldPreviewImageFilename, $event->getPreviewImageFilename());
        $event->setPreviewImageFilename($newPreviewImageFilename);
        self::assertEquals($newPreviewImageFilename, $event->getPreviewImageFilename());
    }
}
