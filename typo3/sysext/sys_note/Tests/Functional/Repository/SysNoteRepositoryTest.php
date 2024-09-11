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

namespace TYPO3\CMS\SysNote\Tests\Functional\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SysNoteRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['sys_note'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_notes.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
    }

    public static function sysNoteRepositoryQueryDataByCategoryRestrictedProvider(): array
    {
        return [
            'category null' => ['category' => null, 'expectedUids' => [1, 2, 3, 4, 9, 10]],
            'category 0' => ['category' => 0, 'expectedUids' => [1, 4]],
            'category set to valid id' => ['category' => 1, 'expectedUids' => [2]],
            'category set to invalid id' => ['category' => 99, 'expectedUids' => []],
        ];
    }

    #[DataProvider('sysNoteRepositoryQueryDataByCategoryRestrictedProvider')]
    #[Test]
    public function sysNoteRepositoryQueryDataByCategoryRestricted(?int $category, array $expectedUids): void
    {
        $this->setUpBackendUser(1);

        $subject = new SysNoteRepository(new ConnectionPool());
        $data = $subject->findByCategoryRestricted($category);

        $collectedUids = [];
        foreach ($data as $returnedRow) {
            $collectedUids[] = $returnedRow['uid'];
        }

        self::assertSame($expectedUids, $collectedUids);
    }

    public static function sysNoteRepositoryQueryDataByPidAndAuthorIdProvider(): array
    {
        return [
            'pid 0, owned' => ['pid' => 0, 'author' => 1, 'position' => null, 'expectedUids' => [1, 2, 3, 4]],
            'pid 0, unowned' => ['pid' => 0, 'author' => 2, 'position' => null, 'expectedUids' => [1, 2, 3, 5]],
            'pid 1, owned' => ['pid' => 1, 'author' => 1, 'position' => null, 'expectedUids' => [9, 10]],
            'pid 1, specific other author' => ['pid' => 1, 'author' => 2, 'position' => null, 'expectedUids' => [9, 10, 11, 12]],
            'pid 1, owned, position 0' => ['pid' => 1, 'author' => 3, 'position' => 0, 'expectedUids' => [13]],
            'pid 1, owned, valid position' => ['pid' => 1, 'author' => 1, 'position' => 1, 'expectedUids' => [9]],
            'pid 1, owned, invalid position' => ['pid' => 1, 'author' => 1, 'position' => 99, 'expectedUids' => []],
        ];
    }

    #[DataProvider('sysNoteRepositoryQueryDataByPidAndAuthorIdProvider')]
    #[Test]
    public function sysNoteRepositoryQueryDataByPidAndAuthorId(int $pid, int $author, ?int $position, array $expectedUids): void
    {
        $this->setUpBackendUser(1);

        $subject = new SysNoteRepository(new ConnectionPool());
        $data = $subject->findByPidAndAuthorId($pid, $author, $position);

        $collectedUids = [];
        foreach ($data as $returnedRow) {
            $collectedUids[] = $returnedRow['uid'];
        }

        self::assertSame($expectedUids, $collectedUids);
    }

}
