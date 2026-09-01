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

namespace TYPO3\CMS\Backend\Tests\Unit\History;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RecordHistoryTest extends UnitTestCase
{
    private static function entry(int $actionType, array $additionalFields = []): array
    {
        $entry = [
            'tablename' => 'tt_content',
            'recuid' => 5,
            'actiontype' => $actionType,
        ];
        // Mirrors RecordHistory->prepareEventDataFromQueryBuilder()
        $action = match ($actionType) {
            RecordHistoryStore::ACTION_ADD, RecordHistoryStore::ACTION_UNDELETE => 'insert',
            RecordHistoryStore::ACTION_DELETE => 'delete',
            RecordHistoryStore::ACTION_PUBLISH => 'publish',
            default => null,
        };
        if ($action !== null) {
            $entry['action'] = $action;
        }
        return array_merge($entry, $additionalFields);
    }

    private static function modifyEntry(array $oldRecord, array $newRecord): array
    {
        return self::entry(RecordHistoryStore::ACTION_MODIFY, [
            'oldRecord' => $oldRecord,
            'newRecord' => $newRecord,
        ]);
    }

    public static function insertsDeletesAreCountedDataProvider(): \Generator
    {
        yield 'created record is counted as insert' => [
            [self::entry(RecordHistoryStore::ACTION_ADD)],
            ['tt_content:5' => 1],
        ];
        yield 'undeleted record is counted as insert' => [
            [self::entry(RecordHistoryStore::ACTION_UNDELETE)],
            ['tt_content:5' => 1],
        ];
        yield 'deleted record is counted as delete' => [
            [self::entry(RecordHistoryStore::ACTION_DELETE)],
            ['tt_content:5' => -1],
        ];
        yield 'created and deleted record cancel each other out' => [
            [
                self::entry(RecordHistoryStore::ACTION_DELETE),
                self::entry(RecordHistoryStore::ACTION_ADD),
            ],
            [],
        ];
        yield 'moved record is not counted' => [
            [self::entry(RecordHistoryStore::ACTION_MOVE, [
                'history_data' => ['oldData' => ['pid' => 1], 'newData' => ['pid' => 2]],
            ])],
            [],
        ];
        yield 'stage change is not counted' => [
            [self::entry(RecordHistoryStore::ACTION_STAGECHANGE, [
                'history_data' => ['current' => 0, 'next' => 1],
            ])],
            [],
        ];
        yield 'publish is not counted' => [
            [self::entry(RecordHistoryStore::ACTION_PUBLISH, [
                'oldRecord' => ['header' => 'live'],
                'newRecord' => ['header' => 'published'],
            ])],
            [],
        ];
        yield 'modified record is not counted' => [
            [self::modifyEntry(['header' => 'old'], ['header' => 'new'])],
            [],
        ];
        yield 'delete is still detected next to a move and a publish' => [
            [
                self::entry(RecordHistoryStore::ACTION_PUBLISH),
                self::entry(RecordHistoryStore::ACTION_MOVE),
                self::entry(RecordHistoryStore::ACTION_DELETE),
            ],
            ['tt_content:5' => -1],
        ];
    }

    #[DataProvider('insertsDeletesAreCountedDataProvider')]
    #[Test]
    public function onlyAddUndeleteAndDeleteAreCountedAsInsertsDeletes(array $changeLog, array $expected): void
    {
        $subject = new RecordHistory('tt_content:5');
        self::assertSame($expected, $subject->getDiff($changeLog)['insertsDeletes']);
    }

    #[Test]
    public function modificationsAreMergedIntoOldAndNewData(): void
    {
        $subject = new RecordHistory('tt_content:5');
        $result = $subject->getDiff([
            self::modifyEntry(['header' => 'second'], ['header' => 'third']),
            self::modifyEntry(['header' => 'first', 'bodytext' => 'a'], ['header' => 'second', 'bodytext' => 'b']),
        ]);
        self::assertSame(['tt_content:5' => ['header' => 'third']], $result['newData']);
        self::assertSame(['tt_content:5' => ['header' => 'first', 'bodytext' => 'a']], $result['oldData']);
        self::assertSame([], $result['insertsDeletes']);
    }

    #[Test]
    public function movedRecordDoesNotProduceRollbackData(): void
    {
        $subject = new RecordHistory('tt_content:5');
        $result = $subject->getDiff([
            self::entry(RecordHistoryStore::ACTION_MOVE, [
                'history_data' => ['oldData' => ['pid' => 1], 'newData' => ['pid' => 2]],
            ]),
        ]);
        self::assertSame(['newData' => [], 'oldData' => [], 'insertsDeletes' => []], $result);
    }
}
