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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\FAL;

use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;

abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    use SiteBasedTestTrait;

    protected const VALUE_PageId = 89;
    protected const VALUE_PageIdTarget = 90;
    protected const VALUE_PageIdWebsite = 1;
    protected const VALUE_ContentIdFirst = 330;
    protected const VALUE_ContentIdLast = 331;
    protected const VALUE_FileIdFirst = 1;
    protected const VALUE_FileIdSecond = 21;
    protected const VALUE_FileIdThird = 22;
    protected const VALUE_LanguageId = 1;

    protected const VALUE_FileReferenceContentFirstFileFirst = 126;
    protected const VALUE_FileReferenceContentFirstFileLast = 127;
    protected const VALUE_FileReferenceContentLastFileLast = 128;
    protected const VALUE_FileReferenceContentLastFileFirst = 129;

    protected const TABLE_Page = 'pages';
    protected const TABLE_Content = 'tt_content';
    protected const TABLE_File = 'sys_file';
    protected const TABLE_FileMetadata = 'sys_file_metadata';
    protected const TABLE_FileReference = 'sys_file_reference';

    protected const FIELD_ContentImage = 'image';
    protected const FIELD_FileReferenceImage = 'uid_local';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected function setUp(): void
    {
        parent::setUp();
        // Show copied pages records in frontend request
        $GLOBALS['TCA']['pages']['ctrl']['hideAtCopy'] = false;
        // Show copied tt_content records in frontend request
        $GLOBALS['TCA']['tt_content']['ctrl']['hideAtCopy'] = false;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->importCSVDataSet(static::SCENARIO_DataSet);
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/sys_file_storage.csv');
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

    /**
     * Content records
     */

    /**
     * Modify a content element
     */
    public function modifyContent(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, ['header' => 'Testing #1']);
    }

    public function deleteContent(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
    }

    public function copyContent(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See Modify/DataSet/copyContentToLanguage.csv
     */
    public function copyContentToLanguage(): void
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContent(): void
    {
        // Create translated page first
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Page, self::VALUE_PageId, self::VALUE_LanguageId);
        $this->recordIds['localizedPageId'] = $newTableIds[self::TABLE_Page][self::VALUE_PageId];
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function changeContentSorting(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
    }

    /**
     * Returns mixed here because \TYPO3\CMS\Workspaces\Tests\Functional\DataScenarios\FAL\Discard\ActionTest
     * returns the array and the rest uses void
     * @return mixed
     */
    public function moveContentToDifferentPage()
    {
        return $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
    }

    public function moveContentToDifferentPageAndChangeSorting(): void
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, -self::VALUE_ContentIdLast);
    }

    public function createContentWithFileReference(): void
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

    public function modifyContentWithFileReference(): void
    {
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdLast, 'header' => 'Testing #1', self::FIELD_ContentImage => self::VALUE_FileReferenceContentLastFileLast . ',' . self::VALUE_FileReferenceContentLastFileFirst],
                self::TABLE_FileReference => ['uid' => self::VALUE_FileReferenceContentLastFileFirst, 'title' => 'Image #1'],
            ]
        );
    }

    public function modifyContentAndAddFileReference(): void
    {
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['uid' => self::VALUE_ContentIdLast, self::FIELD_ContentImage => self::VALUE_FileReferenceContentLastFileLast . ',' . self::VALUE_FileReferenceContentLastFileFirst . ',__nextUid'],
                self::TABLE_FileReference => ['uid' => '__NEW', 'title' => 'Image #3', self::FIELD_FileReferenceImage => self::VALUE_FileIdFirst],
            ]
        );
    }

    public function modifyContentAndDeleteFileReference(): void
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            [self::FIELD_ContentImage => self::VALUE_FileReferenceContentLastFileFirst],
            [self::TABLE_FileReference => [self::VALUE_FileReferenceContentLastFileLast]]
        );
    }

    public function modifyContentAndDeleteAllFileReference(): void
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            [self::FIELD_ContentImage => ''],
            [self::TABLE_FileReference => [self::VALUE_FileReferenceContentLastFileFirst, self::VALUE_FileReferenceContentLastFileLast]]
        );
    }

    public function modifyContentDeleteFileRefAddFileRefCopyElement(): void
    {
        // This scenario is mainly targeted to workspaces.
        // tt_content:331 has file refs 128 and 129 attached. delete 128.
        // creates an overlay (t3ver_state=0) sys_fire_reference for 129, and a delete placeholder (t3ver_state=2) for 128
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            [self::FIELD_ContentImage => self::VALUE_FileReferenceContentLastFileFirst],
            [self::TABLE_FileReference => [self::VALUE_FileReferenceContentLastFileLast]]
        );
        // attach third image 22 to tt_content:331, creating a new (t3ver_state=1) sys_file_reference
        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => [
                    'uid' => self::VALUE_ContentIdLast,
                    self::FIELD_ContentImage => '130,__nextUid',
                ],
                self::TABLE_FileReference => [
                    'uid' => '__NEW',
                    'title' => 'Image #3',
                    self::FIELD_FileReferenceImage => self::VALUE_FileIdThird,
                ],
            ]
        );
        // we now have a content element in live with 2 attached images, one being deleted in workspaces, another
        // one being added in workspaces. now copy that element.
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    protected function createContentWithFileReferenceAndDeleteFileReference(): void
    {
        // Create content element with a file reference
        $newTableIds = $this->actionService->createNewRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => ['header' => 'Testing #1', self::FIELD_ContentImage => '__nextUid'],
                self::TABLE_FileReference => ['title' => 'Image #1', self::FIELD_FileReferenceImage => self::VALUE_FileIdFirst],
            ]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
        $this->recordIds['newSysFileReference'] = $newTableIds[self::TABLE_FileReference][0];
        // Delete the file reference again, but keep the content element
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            $this->recordIds['newContentId'],
            [self::FIELD_ContentImage => ''],
            [self::TABLE_FileReference => [$this->recordIds['newSysFileReference']]]
        );
    }
}
