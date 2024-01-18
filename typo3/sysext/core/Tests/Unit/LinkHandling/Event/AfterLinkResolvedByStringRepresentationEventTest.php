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

namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling\Event;

use TYPO3\CMS\Core\LinkHandling\Event\AfterLinkResolvedByStringRepresentationEvent;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AfterLinkResolvedByStringRepresentationEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function gettersReturnInitializedObjects(): void
    {
        $result = [
            'type' => 'url',
        ];
        $urn = 'myhandler://123';
        $resolveException = new UnknownLinkHandlerException('Unknown LinkHandler', 1705660731);

        $event = new AfterLinkResolvedByStringRepresentationEvent(
            result: $result,
            urn: $urn,
            resolveException: $resolveException
        );

        self::assertSame($result, $event->getResult());
        self::assertSame($urn, $event->getUrn());
        self::assertSame($resolveException, $event->getResolveException());
    }

    /**
     * @test
     */
    public function setterOverwritesResult(): void
    {
        $result = [
            'type' => 'url',
        ];
        $urn = 'myhandler://123';
        $resolveException = new UnknownLinkHandlerException('Unknown LinkHandler', 1705660732);

        $event = new AfterLinkResolvedByStringRepresentationEvent(
            result: $result,
            urn: $urn,
            resolveException: $resolveException
        );

        self::assertSame($result, $event->getResult());
        self::assertSame($urn, $event->getUrn());
        self::assertSame($resolveException, $event->getResolveException());

        $newResult = ['type' => 'my-type'];
        $event->setResult($newResult);

        self::assertSame($newResult, $event->getResult());
    }
}
