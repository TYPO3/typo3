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

namespace TYPO3\CMS\Core\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\Repository\RecordDescendantsRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordDescendantsRepositoryTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordDescendants.csv');
    }

    public static function findDescendantUidsDataProvider(): \Generator
    {
        yield 'all levels below a record, in the default language and all languages, without deleted records and their children' => [[1], 0, [2, 3, 12, 14, 15]];
        yield 'several records' => [[2, 6], 0, [3, 7, 12]];
        yield 'leaf record' => [[3], 0, []];
        yield 'parent cycle ends without the start record' => [[8], 0, [9]];
        yield 'invalid uids' => [[0, -1], 0, []];
        yield 'workspace: moved, new and deleted records' => [[1], 1, [2, 3, 7, 11, 14, 15]];
        yield 'workspace: record moved away' => [[6], 1, []];
    }

    #[DataProvider('findDescendantUidsDataProvider')]
    #[Test]
    public function findDescendantUidsReturnsLiveUidsOfAllDescendants(array $uids, int $workspaceId, array $expected): void
    {
        $result = $this->get(RecordDescendantsRepository::class)->findDescendantUids('sys_category', 'parent', $uids, $workspaceId);

        self::assertEqualsCanonicalizing($expected, $result);
    }
}
