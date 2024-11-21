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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordHistoryTest extends FunctionalTestCase
{
    public static function findEventsForCorrelationWorksAsExpectedDataProvider(): array
    {
        return [
            'da625644-8e50-47ee-8c11-bb19552a79e4' => ['da625644-8e50-47ee-8c11-bb19552a79e4', 2],
            '3479dc73-7bc8-4d3b-a8bc-0370a68cedd2' => ['3479dc73-7bc8-4d3b-a8bc-0370a68cedd2', 1],
        ];
    }

    #[DataProvider('findEventsForCorrelationWorksAsExpectedDataProvider')]
    #[Test]
    public function findEventsForCorrelationWorksAsExpected(string $correlationId, int $amountOfEntries): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_history.csv');
        $subject = new RecordHistory();
        self::assertCount($amountOfEntries, $subject->findEventsForCorrelation($correlationId));
    }
}
