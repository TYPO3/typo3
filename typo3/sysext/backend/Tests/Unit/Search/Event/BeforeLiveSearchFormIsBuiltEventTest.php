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

namespace TYPO3\CMS\Backend\Tests\Unit\Search\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Search\Event\BeforeLiveSearchFormIsBuiltEvent;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BeforeLiveSearchFormIsBuiltEventTest extends UnitTestCase
{
    #[Test]
    public function getHintsReturnsHints(): void
    {
        $hints = ['Hint 1', 'Hint 2'];
        $event = new BeforeLiveSearchFormIsBuiltEvent(
            hints: $hints,
            request: new ServerRequest(),
        );

        self::assertSame($hints, $event->getHints());
    }

    #[Test]
    public function setHintsSetsHints(): void
    {
        $hints = ['Hint 1', 'Hint 2'];
        $event = new BeforeLiveSearchFormIsBuiltEvent(
            hints: [],
            request: new ServerRequest(),
        );

        $event->setHints($hints);

        self::assertSame($hints, $event->getHints());
    }

    #[Test]
    public function setHintsWithEmptyArrayResetHints(): void
    {
        $hints = ['Hint 1', 'Hint 2'];
        $event = new BeforeLiveSearchFormIsBuiltEvent(
            hints: $hints,
            request: new ServerRequest(),
        );
        self::assertSame($hints, $event->getHints());

        $event->setHints([]);

        self::assertSame([], $event->getHints());
    }

    #[Test]
    public function addHintAddsHint(): void
    {
        $hint = 'New Hint';
        $event = new BeforeLiveSearchFormIsBuiltEvent(
            hints: ['Hint 1', 'Hint 2'],
            request: new ServerRequest(),
        );

        $event->addHint($hint);

        $expectedHints = ['Hint 1', 'Hint 2', $hint];
        self::assertSame($expectedHints, $event->getHints());
    }

    #[Test]
    public function addHintsAddsSingleHint(): void
    {
        $hint = 'New Hint';
        $event = new BeforeLiveSearchFormIsBuiltEvent(
            hints: ['Hint 1', 'Hint 2'],
            request: new ServerRequest(),
        );

        $event->addHints($hint);

        $expectedHints = ['Hint 1', 'Hint 2', $hint];
        self::assertSame($expectedHints, $event->getHints());
    }

    #[Test]
    public function addHintsAddsMultipleHintsAsSingleArguments(): void
    {
        $hint1 = 'New Hint 1';
        $hint2 = 'New Hint 2';
        $event = new BeforeLiveSearchFormIsBuiltEvent(
            hints: ['Hint 1', 'Hint 2'],
            request: new ServerRequest(),
        );

        $event->addHints($hint1, $hint2);

        $expectedHints = ['Hint 1', 'Hint 2', $hint1, $hint2];
        self::assertSame($expectedHints, $event->getHints());
    }

    #[Test]
    public function addHintsAddsMultipleHintsUsingArraySpreadOperator(): void
    {
        $hint1 = 'New Hint 1';
        $hint2 = 'New Hint 2';
        $event = new BeforeLiveSearchFormIsBuiltEvent(
            hints: ['Hint 1', 'Hint 2'],
            request: new ServerRequest(),
        );

        $event->addHints(...[$hint1, $hint2]);

        $expectedHints = ['Hint 1', 'Hint 2', $hint1, $hint2];
        self::assertSame($expectedHints, $event->getHints());
    }

    #[Test]
    public function getRequestReturnsRequest(): void
    {
        $request = new ServerRequest('https://acme.com/test/path');
        $event = new BeforeLiveSearchFormIsBuiltEvent(
            hints: [],
            request: $request,
        );

        self::assertSame($request, $event->getRequest());
    }

    #[Test]
    public function setSearchDemandSetsSearchDemand(): void
    {
        $expectedSearchDemand = new SearchDemand();
        $event = new BeforeLiveSearchFormIsBuiltEvent(
            hints: [],
            request: new ServerRequest(),
        );
        $event->setSearchDemand($expectedSearchDemand);

        self::assertSame($expectedSearchDemand, $event->getSearchDemand());
    }

    #[Test]
    public function setAdditionalViewDataCanBeRetrieved(): void
    {
        $expectedAdditionalViewData = ['some' => 'value123'];
        $event = new BeforeLiveSearchFormIsBuiltEvent(
            hints: [],
            request: new ServerRequest(),
        );
        self::assertSame([], $event->getAdditionalViewData());

        $event->setAdditionalViewData($expectedAdditionalViewData);

        self::assertSame($expectedAdditionalViewData, $event->getAdditionalViewData());
    }

    #[Test]
    public function setAdditionalViewDataCanBeUsedToResetAdditionalViewData(): void
    {
        $expectedAdditionalViewData = ['some' => 'value123'];
        $event = new BeforeLiveSearchFormIsBuiltEvent(
            hints: [],
            request: new ServerRequest(),
        );
        $event->setAdditionalViewData($expectedAdditionalViewData);
        self::assertSame($expectedAdditionalViewData, $event->getAdditionalViewData());

        $event->setAdditionalViewData([]);

        self::assertSame([], $event->getAdditionalViewData());
    }
}
