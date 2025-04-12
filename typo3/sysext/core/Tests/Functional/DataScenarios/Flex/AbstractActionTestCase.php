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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\Flex;

use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;

abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    use SiteBasedTestTrait;

    protected const VALUE_PageId = 89;
    protected const VALUE_LanguageId = 1;
    protected const VALUE_ElementIdFirst = 1;

    protected const TABLE_Page = 'pages';
    protected const TABLE_Element = 'tx_testflex';
    protected const FIELD_Flex = 'flex_1';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_flex',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // Show copied pages records in frontend request
        $GLOBALS['TCA']['pages']['ctrl']['hideAtCopy'] = false;
        // Show copied tt_content records in frontend request
        $GLOBALS['TCA']['tt_content']['ctrl']['hideAtCopy'] = false;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->importCSVDataSet(static::SCENARIO_DataSet);
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DA', '/da/', ['EN']),
                $this->buildLanguageConfiguration('DE', '/de/', ['DA', 'EN']),
            ]
        );
        $this->setUpFrontendRootPage(1, ['EXT:core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
    }
    public function moveRecordBelowOtherRecordOnSamePage(): void
    {
        $this->actionService->moveRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, -2);
    }

    public function moveRecordToDifferentPageMovesFlexChildren(): void
    {
        $this->actionService->moveRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, 90);
    }

    public function moveRecordToDifferentPageBelowOtherRecordMovesFlexChildren(): void
    {
        $this->actionService->moveRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, -3);
    }
    public function localizeRecord(): void
    {
        // Localize page first.
        $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdFirst];
    }

    public function deleteRecord(): void
    {
        $newTableIds = $this->actionService->deleteRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
        // Usually this is the record ID itself, but when in a workspace, the ID is the one from the versioned record
        $this->recordIds['deletedRecordId'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdFirst] ?? self::VALUE_ElementIdFirst;
    }
}
