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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class InlineChildLanguageTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mm',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_inline_two_parents',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguage.csv');
    }

    #[Test]
    public function childrenFollowStoredLanguageOfNewParent(): void
    {
        $dataHandler = $this->processDataMapAsUser(3, [
            'tx_testirrecsv_hotel' => ['NEW1' => ['pid' => 1, 'title' => 'Hotel #2', 'sys_language_uid' => -1, 'offers' => 'NEW2']],
            'tx_testirrecsv_offer' => ['NEW2' => ['pid' => 1, 'title' => 'Offer #2', 'sys_language_uid' => -1]],
        ]);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfNewParentWithoutLanguageAccess.csv');
    }

    #[Test]
    public function childrenOfMmInlineRelationKeepTheirLanguage(): void
    {
        $dataHandler = $this->processDataMapAsUser(1, ['tx_testirremm_hotel' => [1 => ['sys_language_uid' => -1]]]);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfMmRelation.csv');
    }

    #[Test]
    public function childrenOfCyclicInlineRelationAreAligned(): void
    {
        $GLOBALS['TCA']['tx_testirrecsv_offer']['columns']['prices']['config']['foreign_table'] = 'tx_testirrecsv_hotel';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $dataHandler = $this->processDataMapAsUser(1, ['tx_testirrecsv_hotel' => [1 => ['sys_language_uid' => -1]]]);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfCyclicRelation.csv');
    }

    #[Test]
    public function editorWithoutAccessToChildLanguageFieldChangesLanguageOfChildren(): void
    {
        $dataHandler = $this->processDataMapAsUser(2, ['tt_content' => [1 => ['sys_language_uid' => 0]]]);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageChanged.csv');
    }

    #[Test]
    public function editorWithoutAccessToChildLanguageFieldChangesLanguageOfChildrenInWorkspace(): void
    {
        $dataHandler = $this->processDataMapAsUser(2, ['tt_content' => [1 => ['sys_language_uid' => 0]]], 1);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageChangedInWorkspace.csv');
    }

    #[Test]
    public function editorWithoutAccessToChildTableChangesLanguageOfChildrenInWorkspace(): void
    {
        $this->processDataMapAsUser(4, ['tt_content' => [1 => ['sys_language_uid' => 0]]], 1);

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageChangedInWorkspace.csv');
    }

    #[Test]
    public function translatedChildKeepsLanguageWhenChildIsAddedToParentReferencedByExcludedField(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfSecondParentMembers.csv');

        $dataHandler = $this->processDataMapAsUser(1, [
            'tx_testinlinetwoparents_group' => [61 => ['members' => '51,52,NEW1']],
            'tx_testinlinetwoparents_member' => ['NEW1' => ['pid' => 1, 'title' => 'Member #2', 'sys_language_uid' => 0]],
        ]);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfSecondParentKept.csv');
    }

    #[Test]
    public function translatedChildKeepsLanguageWhenChildIsAddedToParentReferencedByExcludedFieldInWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfSecondParentMembers.csv');

        $dataHandler = $this->processDataMapAsUser(1, [
            'tx_testinlinetwoparents_group' => [61 => ['members' => '51,52,NEW1']],
            'tx_testinlinetwoparents_member' => ['NEW1' => ['pid' => 1, 'title' => 'Member #2', 'sys_language_uid' => 0]],
        ], 1);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfSecondParentKeptInWorkspace.csv');
    }

    #[Test]
    public function translatedChildKeepsLanguageWhenChildIsAddedToParentReferencedBySynchronizedField(): void
    {
        unset($GLOBALS['TCA']['tx_testinlinetwoparents_member']['columns']['group_uid']['l10n_mode']);
        $GLOBALS['TCA']['tx_testinlinetwoparents_member']['columns']['group_uid']['config']['behaviour']['allowLanguageSynchronization'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfSecondParentMembers.csv');

        $dataHandler = $this->processDataMapAsUser(1, [
            'tx_testinlinetwoparents_group' => [61 => ['members' => '51,52,NEW1']],
            'tx_testinlinetwoparents_member' => ['NEW1' => ['pid' => 1, 'title' => 'Member #2', 'sys_language_uid' => 0]],
        ]);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfSecondParentKept.csv');
    }

    public static function translatedChildKeepsLanguageWhenCreatedWithParentReferencedByExcludedFieldDataProvider(): array
    {
        $group = ['tx_testinlinetwoparents_group' => ['NEW61' => ['pid' => 1, 'title' => 'Group #1', 'sys_language_uid' => 0]]];
        $content = ['tt_content' => [21 => ['tx_testinlinetwoparents_members' => 'NEW51'], 22 => ['tx_testinlinetwoparents_members' => 'NEW52']]];
        $members = ['tx_testinlinetwoparents_member' => [
            'NEW51' => ['pid' => 1, 'title' => 'Member #1', 'sys_language_uid' => 0, 'group_uid' => 'NEW61'],
            'NEW52' => ['pid' => 1, 'title' => '[Translate to Dansk:] Member #1', 'sys_language_uid' => 1, 'l10n_parent' => 'NEW51', 'l10n_source' => 'NEW51', 'group_uid' => 'NEW61'],
        ]];
        return [
            'group first' => [$group + $members + $content],
            'group last' => [$content + $members + $group],
        ];
    }

    #[DataProvider('translatedChildKeepsLanguageWhenCreatedWithParentReferencedByExcludedFieldDataProvider')]
    #[Test]
    public function translatedChildKeepsLanguageWhenCreatedWithParentReferencedByExcludedField(array $dataMap): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfSecondParent.csv');

        $dataHandler = $this->processDataMapAsUser(1, $dataMap);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfSecondParentImported.csv');
    }

    #[Test]
    public function childrenInFlexFormFollowChangedLanguageOfParent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageInFlexForm.csv');

        $dataHandler = $this->processDataMapAsUser(1, ['tt_content' => [2 => ['sys_language_uid' => -1]]]);

        self::assertSame([], $dataHandler->errorLog);
        self::assertSame(-1, $this->getLanguageOfRecord('tx_testirreforeignfield_hotel', 1));
        self::assertSame(-1, $this->getLanguageOfRecord('sys_file_reference', 2));
    }

    #[Test]
    public function childrenInFlexFormFollowChangedLanguageOfParentInWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageInFlexForm.csv');

        $dataHandler = $this->processDataMapAsUser(1, ['tt_content' => [2 => ['sys_language_uid' => -1]]], 1);

        self::assertSame([], $dataHandler->errorLog);
        self::assertSame(0, $this->getLanguageOfRecord('tx_testirreforeignfield_hotel', 1));
        self::assertSame(-1, $this->getLanguageOfWorkspaceVersion('tx_testirreforeignfield_hotel', 1, 1));
        self::assertSame(0, $this->getLanguageOfRecord('sys_file_reference', 2));
        self::assertSame(-1, $this->getLanguageOfWorkspaceVersion('sys_file_reference', 2, 1));
    }

    #[Test]
    public function childInFlexFormFollowsLanguageOfNewParent(): void
    {
        $dataHandler = $this->processDataMapAsUser(1, [
            'tt_content' => ['NEW1' => [
                'pid' => 1,
                'header' => 'New content with FlexForm child',
                'sys_language_uid' => -1,
                'tx_testirreforeignfield_flexform' => ['data' => ['sDEF' => ['lDEF' => ['hotels' => ['vDEF' => 'NEW2']]]]],
            ]],
            'tx_testirreforeignfield_hotel' => ['NEW2' => ['pid' => 1, 'title' => 'New hotel in FlexForm', 'sys_language_uid' => 0]],
        ]);

        self::assertSame([], $dataHandler->errorLog);
        self::assertSame(-1, $this->getLanguageOfRecord('tx_testirreforeignfield_hotel', (int)$dataHandler->substNEWwithIDs['NEW2']));
    }

    public static function childAddedToFlexFormOfExistingParentFollowsItsLanguageDataProvider(): \Generator
    {
        yield 'parent in all languages' => [3, -1];
        yield 'translated parent' => [5, 1];
    }

    #[DataProvider('childAddedToFlexFormOfExistingParentFollowsItsLanguageDataProvider')]
    #[Test]
    public function childAddedToFlexFormOfExistingParentFollowsItsLanguage(int $parentUid, int $expectedLanguage): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageInFlexForm.csv');

        $dataHandler = $this->processDataMapAsUser(1, [
            'tt_content' => [$parentUid => [
                'tx_testirreforeignfield_flexform' => ['data' => ['sDEF' => ['lDEF' => ['hotels' => ['vDEF' => 'NEW2']]]]],
            ]],
            'tx_testirreforeignfield_hotel' => ['NEW2' => ['pid' => 1, 'title' => 'New hotel in FlexForm', 'sys_language_uid' => 0]],
        ]);

        self::assertSame([], $dataHandler->errorLog);
        self::assertSame($expectedLanguage, $this->getLanguageOfRecord('tx_testirreforeignfield_hotel', (int)$dataHandler->substNEWwithIDs['NEW2']));
    }

    /**
     * The record type changes in the same data map, so the new child is part of
     * the FlexForm data structure of the new record type only.
     */
    #[Test]
    public function childAddedToFlexFormOfChangedRecordTypeFollowsLanguageOfParent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageInFlexForm.csv');

        $dataHandler = $this->processDataMapAsUser(1, [
            'tt_content' => [4 => [
                'CType' => 'text',
                'tx_testirreforeignfield_flexform' => ['data' => ['sDEF' => ['lDEF' => ['hotels' => ['vDEF' => 'NEW2']]]]],
            ]],
            'tx_testirreforeignfield_hotel' => ['NEW2' => ['pid' => 1, 'title' => 'New hotel in FlexForm', 'sys_language_uid' => 0]],
        ]);

        self::assertSame([], $dataHandler->errorLog);
        self::assertSame(-1, $this->getLanguageOfRecord('tx_testirreforeignfield_hotel', (int)$dataHandler->substNEWwithIDs['NEW2']));
    }

    private function getLanguageOfRecord(string $table, int $uid): int
    {
        $record = $this->get(ConnectionPool::class)->getConnectionForTable($table)
            ->select(['sys_language_uid'], $table, ['uid' => $uid])
            ->fetchAssociative();
        self::assertIsArray($record, $table . ':' . $uid . ' does not exist');
        return (int)$record['sys_language_uid'];
    }

    private function getLanguageOfWorkspaceVersion(string $table, int $liveUid, int $workspaceId): int
    {
        $record = $this->get(ConnectionPool::class)->getConnectionForTable($table)
            ->select(['sys_language_uid'], $table, ['t3ver_oid' => $liveUid, 't3ver_wsid' => $workspaceId])
            ->fetchAssociative();
        self::assertIsArray($record, 'No workspace version of ' . $table . ':' . $liveUid);
        return (int)$record['sys_language_uid'];
    }

    private function processDataMapAsUser(int $userId, array $dataMap, int $workspaceId = 0): DataHandler
    {
        $backendUser = $this->setUpBackendUser($userId);
        $backendUser->setWorkspace($workspaceId);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start($dataMap, [], $backendUser);
        $dataHandler->process_datamap();
        return $dataHandler;
    }
}
