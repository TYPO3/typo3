<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular;

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

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase
{
    const VALUE_PageId = 89;
    const VALUE_PageIdTarget = 90;
    const VALUE_PageIdWebsite = 1;
    const VALUE_ContentIdFirst = 297;
    const VALUE_ContentIdSecond = 298;
    const VALUE_ContentIdThird = 299;
    const VALUE_ContentIdThirdLocalized = 300;
    const VALUE_LanguageId = 1;
    const VALUE_LanguageIdSecond = 2;

    const TABLE_Page = 'pages';
    const TABLE_Content = 'tt_content';

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Regular/DataSet/';

    protected function setUp()
    {
        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');

        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.ts']);
        $this->backendUser->workspace = 0;
    }

    /**
     * Content records
     */

    /**
     * @test
     * @see DataSet/createContentRecords.csv
     */
    public function createContents()
    {
        // Creating record at the beginning of the page
        $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1']);
        // Creating record at the end of the page (after last one)
        $this->actionService->createNewRecord(self::TABLE_Content, -self::VALUE_ContentIdSecond, ['header' => 'Testing #2']);
    }

    /**
     * @test
     * @see DataSet/modifyContentRecord.csv
     */
    public function modifyContent()
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, ['header' => 'Testing #1']);
    }

    /**
     * @test
     * @see DataSet/deleteContentRecord.csv
     */
    public function deleteContent()
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdSecond);
    }

    /**
     * @see DataSet/deleteLocalizedContentNDeleteContent.csv
     */
    public function deleteLocalizedContentAndDeleteContent()
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized);
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdThird);
    }

    /**
     * @test
     * @see DataSet/copyContentRecord.csv
     */
    public function copyContent()
    {
        $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageId);
    }

    /**
     * @test
     * @see DataSet/copyContentToLanguage.csv
     */
    public function copyContentToLanguage()
    {
        $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
    }

    /**
     * Free mode "translation" of a record in non default language
     *
     * @test
     * @see DataSet/copyContentToLanguageFromNonDefaultLanguage.csv
     */
    public function copyContentToLanguageFromNonDefaultLanguage()
    {
        $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized, self::VALUE_LanguageIdSecond);
    }

    /**
     * @test
     * @see DataSet/copyPasteContent.csv
     */
    public function copyPasteContent()
    {
        $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageId, ['header' => 'Testing #1']);
    }

    /**
     * @test
     * @see DataSet/localizeContentRecord.csv
     */
    public function localizeContent()
    {
        $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_LanguageId);
    }

    /**
     * @test
     * @see DataSet/localizeContentFromNonDefaultLanguage.csv
     */
    public function localizeContentFromNonDefaultLanguage()
    {
        $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdThirdLocalized, self::VALUE_LanguageIdSecond);
    }

    /**
     * @test
     * @see DataSet/changeContentRecordSorting.csv
     */
    public function changeContentSorting()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
    }

    /**
     * @test
     * @see DataSet/moveContentRecordToDifferentPage.csv
     */
    public function moveContentToDifferentPage()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget);
    }

    /**
     * @test
     * @see DataSet/movePasteContentToDifferentPage.csv
     */
    public function movePasteContentToDifferentPage()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget, ['header' => 'Testing #1']);
    }

    /**
     * @test
     * @see DataSet/moveContentRecordToDifferentPageAndChangeSorting.csv
     */
    public function moveContentToDifferentPageAndChangeSorting()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdSecond, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdSecond);
    }

    /**
     * Page records
     */

    /**
     * @test
     * @see DataSet/createPageRecord.csv
     */
    public function createPage()
    {
        $newTableIds = $this->actionService->createNewRecord(self::TABLE_Page, self::VALUE_PageId, ['title' => 'Testing #1', 'hidden' => 0]);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][0];
    }

    /**
     * @test
     * @see DataSet/modifyPageRecord.csv
     */
    public function modifyPage()
    {
        $this->actionService->modifyRecord(self::TABLE_Page, self::VALUE_PageId, ['title' => 'Testing #1']);
    }

    /**
     * @test
     * @see DataSet/deletePageRecord.csv
     */
    public function deletePage()
    {
        $this->actionService->deleteRecord(self::TABLE_Page, self::VALUE_PageId);
    }

    /**
     * @test
     * @see DataSet/copyPage.csv
     */
    public function copyPage()
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
    }

    /**
     * @test
     * @see DataSet/copyPageFreeMode.csv
     */
    public function copyPageFreeMode()
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdTarget);
        $this->recordIds['newPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageIdTarget];
    }

    /**
     * @test
     * @see DataSet/localizePageRecord.csv
     */
    public function localizePage()
    {
        $this->actionService->localizeRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
    }

    /**
     * @test
     * @see DataSet/changePageRecordSorting.csv
     */
    public function changePageSorting()
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
    }

    /**
     * @test
     * @see DataSet/movePageRecordToDifferentPage.csv
     */
    public function movePageToDifferentPage()
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, self::VALUE_PageIdTarget);
    }

    /**
     * @test
     * @see DataSet/movePageRecordToDifferentPageAndChangeSorting.csv
     */
    public function movePageToDifferentPageAndChangeSorting()
    {
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageIdTarget, self::VALUE_PageIdWebsite);
        $this->actionService->moveRecord(self::TABLE_Page, self::VALUE_PageId, -self::VALUE_PageIdTarget);
    }
}
