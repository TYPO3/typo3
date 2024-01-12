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

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapFunctionsInitializedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class EnhanceStdWrapEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function gettersReturnInitializedObjects(): void
    {
        $content = 'content';
        $configuration = ['foo' => 'bar'];
        $contentObjectRenderer = new ContentObjectRenderer();

        // Note: We are using a child event since EnhanceStdWrapEvent is abstract
        $event = new BeforeStdWrapFunctionsInitializedEvent($content, $configuration, $contentObjectRenderer);

        self::assertEquals($content, $event->getContent());
        self::assertEquals($configuration, $event->getConfiguration());
        self::assertEquals($contentObjectRenderer, $event->getContentObjectRenderer());
    }

    /**
     * @test
     */
    public function setContentOverwritesStdWrapResult(): void
    {
        // Note: We are using a child event since EnhanceStdWrapEvent is abstract
        $event = new BeforeStdWrapFunctionsInitializedEvent(null, [], new ContentObjectRenderer());

        self::assertNull($event->getContent());

        $content = 'modified content';
        $event->setContent($content);
        self::assertEquals($content, $event->getContent());

        // unset content again
        $event->setContent('');
        self::assertEmpty($event->getContent());
    }
}
