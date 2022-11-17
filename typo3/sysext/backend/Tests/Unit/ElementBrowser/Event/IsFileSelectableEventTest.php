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

namespace TYPO3\CMS\Backend\Tests\Unit\ElementBrowser\Event;

use TYPO3\CMS\Backend\ElementBrowser\Event\IsFileSelectableEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class IsFileSelectableEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function eventMethodsReturnExpected(): void
    {
        $mockResourceStorage = $this->getMockBuilder(ResourceStorage::class)->disableOriginalConstructor()->getMock();
        $file = new File(
            [
                'width' => 100,
                'height' => 200,
            ],
            $mockResourceStorage
        );

        $event = new IsFileSelectableEvent($file);

        self::assertEquals($file, $event->getFile());

        self::assertTrue($event->isFileSelectable());
        $event->denyFileSelection();
        self::assertFalse($event->isFileSelectable());
        $event->allowFileSelection();
        self::assertTrue($event->isFileSelectable());
    }
}
