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
