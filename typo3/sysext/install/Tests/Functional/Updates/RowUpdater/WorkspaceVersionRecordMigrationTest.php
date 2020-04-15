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

namespace TYPO3\CMS\Install\Tests\Functional\Updates\RowUpdater;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Install\Updates\RowUpdater\WorkspaceVersionRecordsMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class WorkspaceVersionRecordMigrationTest extends FunctionalTestCase
{
    private $records;

    /**
     * @var WorkspaceVersionRecordsMigration|MockObject
     */
    private $subject;

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['frontend'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->records = $this->fetchRecordsFromCsv();
        $this->subject = $this->getMockBuilder(WorkspaceVersionRecordsMigration::class)
            ->onlyMethods(['fetchPageId'])
            ->getMock();
    }

    protected function tearDown(): void
    {
        unset($this->records, $this->subject);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function hasPotentialUpdateForTtContent(): void
    {
        $this->subject->expects(self::never())->method('fetchPageId');
        self::assertTrue($this->subject->hasPotentialUpdateForTable('tt_content'));
    }

    /**
     * @test
     */
    public function recordsAreUpdated(): void
    {
        $this->subject->expects(self::atLeastOnce())
            ->method('fetchPageId')
            ->willReturnCallback(function ($tableName, $id) {
                if ($tableName !== 'tt_content' || !isset($this->records[$id]['pid'])) {
                    return null;
                }
                return ['pid' => $this->records[$id]['pid']];
            });
        $records = [];
        foreach ($this->records as $record) {
            $records[] = $this->subject->updateTableRow('tt_content', $record);
        }
        $pageIds = array_column($records, 'pid', 'uid');
        $pageIds = array_map('intval', $pageIds);
        self::assertFalse(in_array(-1, $pageIds));

        $differences = array_diff_key(
            [
                21 => 20, // modified
                22 => 20, // deleted
                23 => 30, // moved
                26 => 20, // created
                28 => 20, // created & discarded
                30 => 41, // created & moved
                32 => 20, // localized
                34 => 20, // localized
            ],
            $pageIds
        );
        self::assertEmpty(
            $differences,
            sprintf('Different values for record IDs %s', implode(', ', array_keys($differences)))
        );
    }

    /**
     * Scenarios taken from https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/Workspaces/Index.html#overview
     * @return array
     */
    private function fetchRecordsFromCsv(): array
    {
        $records = [];
        $resource = fopen(dirname(__DIR__) . '/Fixtures/tt_content_versions.csv', 'r');
        while (false !== ($record = fgetcsv($resource))) {
            $records[] = $record;
        }
        fclose($resource);

        $names = array_shift($records);
        $records = array_map(
            function (array $values) use ($names) {
                return array_combine($names, $values);
            },
            $records
        );
        $records = array_column($records, null, 'uid');
        return $records;
    }
}
