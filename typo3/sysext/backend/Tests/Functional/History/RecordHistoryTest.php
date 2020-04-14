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

namespace TYPO3\CMS\Backend\Tests\Functional\History;

use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RecordHistoryTest extends FunctionalTestCase
{
    /**
     * @var RecordHistory
     */
    private $subject;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/sys_history.xml');
        $this->subject = new RecordHistory();
    }

    public function findEventsForCorrelationWorksAsExpectedDataProvider(): array
    {
        return [
            'da625644-8e50-47ee-8c11-bb19552a79e4' => ['da625644-8e50-47ee-8c11-bb19552a79e4', 2],
            '3479dc73-7bc8-4d3b-a8bc-0370a68cedd2' => ['3479dc73-7bc8-4d3b-a8bc-0370a68cedd2', 1],
        ];
    }

    /**
     * @test
     * @dataProvider findEventsForCorrelationWorksAsExpectedDataProvider
     * @param string $correlationId
     * @param int $amountOfEntries
     */
    public function findEventsForCorrelationWorksAsExpected(string $correlationId, int $amountOfEntries): void
    {
        self::assertCount($amountOfEntries, $this->subject->findEventsForCorrelation($correlationId));
    }
}
