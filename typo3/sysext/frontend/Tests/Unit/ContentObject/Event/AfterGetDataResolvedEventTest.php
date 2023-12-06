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
use TYPO3\CMS\Frontend\ContentObject\Event\AfterGetDataResolvedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AfterGetDataResolvedEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function gettersReturnInitializedObjects(): void
    {
        $parameterString = 'field:title';
        $alternativeFieldArray = ['title' => 'my title'];
        $result = 'my title';
        $contentObjectRenderer = (new ContentObjectRenderer());

        $event = new AfterGetDataResolvedEvent($parameterString, $alternativeFieldArray, $result, $contentObjectRenderer);

        self::assertEquals($parameterString, $event->getParameterString());
        self::assertEquals($alternativeFieldArray, $event->getAlternativeFieldArray());
        self::assertEquals($result, $event->getResult());
        self::assertEquals($contentObjectRenderer, $event->getContentObjectRenderer());
    }

    /**
     * @test
     */
    public function setReturnOverwritesResolvedData(): void
    {
        $contentObjectRenderer = (new ContentObjectRenderer());
        $event = new AfterGetDataResolvedEvent('', [], 'my result', $contentObjectRenderer);

        self::assertEquals('my result', $event->getResult());

        $event->setResult('modified result');

        self::assertEquals('modified result', $event->getResult());

    }
}
