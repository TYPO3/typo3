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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Event\ModifyImageSourceCollectionEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyImageSourceCollectionEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $sourceCollection = '<source src="bar-file.jpg" media="(max-device-width: 600px)">';
        $fullSourceCollection = '<source src="bar-file.jpg" media="(max-device-width: 600px)"><source src="bar-file.jpg" media="(max-device-width: 1280px)">';
        $sourceConfiguration = [
            'small.' => [
                'width' => 200,
                'srcsetCandidate' => '600w',
                'mediaQuery' => '(max-device-width: 600px)',
                'dataKey' => 'small',
            ],
        ];
        $sourceRenderConfiguration = [
            'file' => 'foo.jpg',
        ];
        $contentObjectRenderer = (new ContentObjectRenderer());
        $event = new ModifyImageSourceCollectionEvent(
            $sourceCollection,
            $fullSourceCollection,
            $sourceConfiguration,
            $sourceRenderConfiguration,
            $contentObjectRenderer
        );

        self::assertEquals($sourceCollection, $event->getSourceCollection());
        self::assertEquals($fullSourceCollection, $event->getFullSourceCollection());
        self::assertEquals($sourceConfiguration, $event->getSourceConfiguration());
        self::assertEquals($sourceRenderConfiguration, $event->getSourceRenderConfiguration());
        self::assertEquals($contentObjectRenderer, $event->getContentObjectRenderer());
    }

    #[Test]
    public function setOverwritesSourceCollectionData(): void
    {
        $event = new ModifyImageSourceCollectionEvent(
            '---foo---',
            '',
            [],
            [],
            new ContentObjectRenderer()
        );

        self::assertEquals('---foo---', $event->getSourceCollection());

        $event->setSourceCollection('---modified---');

        self::assertEquals('---modified---', $event->getSourceCollection());

    }
}
