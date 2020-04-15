<?php

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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\FAL;

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    const VALUE_PageId = 89;
    const VALUE_PageIdTarget = 90;
    const VALUE_PageIdWebsite = 1;
    const VALUE_ContentIdFirst = 330;
    const VALUE_ContentIdLast = 331;
    const VALUE_FileIdFirst = 1;
    const VALUE_FileIdLast = 21;
    const VALUE_LanguageId = 1;

    const VALUE_FileReferenceContentFirstFileFirst = 126;
    const VALUE_FileReferenceContentFirstFileLast = 127;
    const VALUE_FileReferenceContentLastFileLast = 128;
    const VALUE_FileReferenceContentLastFileFirst = 129;

    const TABLE_Page = 'pages';
    const TABLE_Content = 'tt_content';
    const TABLE_File = 'sys_file';
    const TABLE_FileMetadata = 'sys_file_metadata';
    const TABLE_FileReference = 'sys_file_reference';

    const FIELD_ContentImage = 'image';
    const FIELD_FileReferenceImage = 'uid_local';

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/FAL/DataSet/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');

        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);
        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    /**
     * Content records
     */

    /**
     * See Modify/DataSet/modifyContent.csv
     */
    public function modifyContent()
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, ['header' => 'Testing #1']);
    }

    /**
     * See Modify/DataSet/deleteContent.csv
     */
    public function deleteContent()
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
    }

    /**
     * See Modify/DataSet/copyContent.csv
     */
    public function copyContent()
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See Modify/DataSet/copyContentToLanguage.csv
     */
    public function copyContentToLanguage()
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See Modify/DataSet/localizeContent.csv
     */
    public function localizeContent()
    {
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See Modify/DataSet/changeContentSorting.csv
     */
    public function changeContentSorting()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
    }

    /**
     * See Modify/DataSet/moveContentToDifferentPage.csv
     */
    public function moveContentToDifferentPage()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
    }

    /**
     * See Modify/DataSet/moveContentToDifferentPageNChangeSorting.csv
     */
    public function moveContentToDifferentPageAndChangeSorting()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
    }

    /**
     * File references
     */

    /**
     * See Modify/DataSet/createContentWFileReference.csv
     */
    public function createContentWithFileReference()
    {
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Testing #1', self::FIELD_ContentImage => '__nextUid'],
                self::TABLE_FileReference => ['title' => 'Image #1', self::FIELD_FileReferenceImage => self::VALUE_FileIdFirst],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
    }

    /**
     * See Modify/DataSet/modifyContentWFileReference.csv
     */
    public function modifyContentWithFileReference()
    {
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdLast, 'header' => 'Testing #1', self::FIELD_ContentImage => self::VALUE_FileReferenceContentLastFileLast . ',' . self::VALUE_FileReferenceContentLastFileFirst],
                self::TABLE_FileReference => ['uid' => self::VALUE_FileReferenceContentLastFileFirst, 'title' => 'Image #1'],
            ]
        );
    }

    /**
     * See Modify/DataSet/modifyContentNAddFileReference.csv
     */
    public function modifyContentAndAddFileReference()
    {
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdLast, self::FIELD_ContentImage => self::VALUE_FileReferenceContentLastFileLast . ',' . self::VALUE_FileReferenceContentLastFileFirst . ',__nextUid'],
                self::TABLE_FileReference => ['uid' => '__NEW', 'title' => 'Image #3', self::FIELD_FileReferenceImage => self::VALUE_FileIdFirst],
            ]
        );
    }

    /**
     * See Modify/DataSet/modifyContentNDeleteFileReference.csv
     */
    public function modifyContentAndDeleteFileReference()
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            [self::FIELD_ContentImage => self::VALUE_FileReferenceContentLastFileFirst],
            [self::TABLE_FileReference => [self::VALUE_FileReferenceContentLastFileLast]]
        );
    }

    /**
     * See Modify/DataSet/modifyContentNDeleteAllFileReference.csv
     */
    public function modifyContentAndDeleteAllFileReference()
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            [self::FIELD_ContentImage => ''],
            [self::TABLE_FileReference => [self::VALUE_FileReferenceContentLastFileFirst, self::VALUE_FileReferenceContentLastFileLast]]
        );
    }
}
