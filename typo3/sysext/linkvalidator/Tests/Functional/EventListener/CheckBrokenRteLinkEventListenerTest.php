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

namespace TYPO3\CMS\Linkvalidator\Tests\Functional\EventListener;

use TYPO3\CMS\Core\Html\Event\BrokenLinkAnalysisEvent;
use TYPO3\CMS\Linkvalidator\EventListener\CheckBrokenRteLinkEventListener;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CheckBrokenRteLinkEventListenerTest extends FunctionalTestCase
{
    protected CheckBrokenRteLinkEventListener $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CheckBrokenRteLinkEventListener(
            new BrokenLinkRepository()
        );
    }

    /**
     * @test
     * @dataProvider checkPageLinkTestDataProvider
     */
    public function checkPageLinkTest(string $linkType, array $linkData, bool $isMarkedAsBroken): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');

        $event = new BrokenLinkAnalysisEvent(
            $linkType,
            $linkData
        );

        $this->subject->checkPageLink($event);
        self::assertEquals($isMarkedAsBroken, $event->isBrokenLink());
    }

    public static function checkPageLinkTestDataProvider(): \Generator
    {
        yield 'No uid parameter given' => [
            'page',
            [
                'parameters' => 'alias=-',
                'type' => 'page',
            ],
            false,
        ];
        yield 'Page exist' => [
            'page',
            [
                'parameters' => 'alias=foo',
                'pageuid' => '2',
                'type' => 'page',
            ],
            false,
        ];
        yield 'Page not found' => [
            'page',
            [
                'parameters' => 'alias=foo',
                'pageuid' => '12345',
                'type' => 'page',
            ],
            true,
        ];
    }
}
