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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Frontend\ContentObject\Event\ModifyRecordsAfterFetchingContentEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyRecordsAfterFetchingContentEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $records = [
            [
                'uid' => 2004,
                'title' => 'content',
            ],
        ];
        $finalContent = 'foo';
        $slide = 0;
        $slideCollect = 0;
        $slideCollectReverse = false;
        $slideCollectFuzzy = true;
        $configuration = ['table' => 'tt_content'];

        $event = new ModifyRecordsAfterFetchingContentEvent(
            $records,
            $finalContent,
            $slide,
            $slideCollect,
            $slideCollectReverse,
            $slideCollectFuzzy,
            $configuration
        );

        self::assertEquals($records, $event->getRecords());
        self::assertEquals($finalContent, $event->getFinalContent());
        self::assertEquals($slide, $event->getSlide());
        self::assertEquals($slideCollect, $event->getSlideCollect());
        self::assertEquals($slideCollectReverse, $event->getSlideCollectReverse());
        self::assertEquals($slideCollectFuzzy, $event->getSlideCollectFuzzy());
        self::assertEquals($configuration, $event->getConfiguration());
    }

    #[Test]
    public function setOverwritesSourceCollectionData(): void
    {
        $event = new ModifyRecordsAfterFetchingContentEvent(
            [],
            '',
            0,
            0,
            false,
            false,
            []
        );

        self::assertEmpty($event->getRecords());
        self::assertEmpty($event->getFinalContent());
        self::assertEmpty($event->getConfiguration());
        self::assertFalse((bool)$event->getSlide());
        self::assertFalse((bool)$event->getSlideCollect());
        self::assertFalse($event->getSlideCollectReverse());
        self::assertFalse($event->getSlideCollectFuzzy());

        $records = [
            [
                'uid' => 2004,
                'title' => 'content',
            ],
        ];
        $finalContent = 'foo';
        $slide = 123;
        $slideCollect = 456;
        $slideCollectReverse = true;
        $slideCollectFuzzy = true;
        $configuration = ['table' => 'tt_content'];

        $event->setRecords($records);
        $event->setFinalContent($finalContent);
        $event->setSlide($slide);
        $event->setSlideCollect($slideCollect);
        $event->setSlideCollectReverse($slideCollectReverse);
        $event->setSlideCollectFuzzy($slideCollectFuzzy);
        $event->setConfiguration($configuration);

        self::assertEquals($records, $event->getRecords());
        self::assertEquals($finalContent, $event->getFinalContent());
        self::assertEquals($slide, $event->getSlide());
        self::assertEquals($slideCollect, $event->getSlideCollect());
        self::assertEquals($slideCollectReverse, $event->getSlideCollectReverse());
        self::assertEquals($slideCollectFuzzy, $event->getSlideCollectFuzzy());
        self::assertEquals($configuration, $event->getConfiguration());

        // unset again
        $event->setRecords([]);
        $event->setFinalContent('');
        $event->setSlide(0);
        $event->setSlideCollect(0);
        $event->setSlideCollectReverse(false);
        $event->setSlideCollectFuzzy(false);
        $event->setConfiguration([]);

        self::assertEmpty($event->getRecords());
        self::assertEmpty($event->getFinalContent());
        self::assertEmpty($event->getConfiguration());
        self::assertFalse((bool)$event->getSlide());
        self::assertFalse((bool)$event->getSlideCollect());
        self::assertFalse($event->getSlideCollectReverse());
        self::assertFalse($event->getSlideCollectFuzzy());
    }
}
