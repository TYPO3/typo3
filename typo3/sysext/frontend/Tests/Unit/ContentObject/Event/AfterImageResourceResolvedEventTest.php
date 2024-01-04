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

use TYPO3\CMS\Core\Imaging\ImageResource;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterImageResourceResolvedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AfterImageResourceResolvedEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function gettersReturnInitializedObjects(): void
    {
        $file = 'field:title';
        $fileArray = ['title' => 'my title'];
        $imageResource = new ImageResource(128, 128, '.jpg', 'full/path');

        $event = new AfterImageResourceResolvedEvent($file, $fileArray, $imageResource);

        self::assertEquals($file, $event->getFile());
        self::assertEquals($fileArray, $event->getFileArray());
        self::assertEquals($imageResource, $event->getImageResource());
    }

    /**
     * @test
     */
    public function setImageResourceOverwritesResolvedDto(): void
    {
        $event = new AfterImageResourceResolvedEvent('', [], null);

        self::assertNull($event->getImageResource());

        $imageResource = new ImageResource(128, 128, '.jpg', 'full/path');
        $event->setImageResource($imageResource);
        self::assertEquals($imageResource, $event->getImageResource());

        // unset imageResource again
        $event->setImageResource(null);
        self::assertNull($event->getImageResource());
    }
}
