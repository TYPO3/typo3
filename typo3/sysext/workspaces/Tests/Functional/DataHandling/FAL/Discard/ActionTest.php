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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\FAL\Discard;

use TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\FAL\AbstractActionTestCase;

/**
 * Functional test for the DataHandler
 */
class ActionTest extends AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/FAL/Discard/DataSet/';

    /**
     * @test
     */
    public function verifyCleanReferenceIndex()
    {
        // The test verifies the imported data set has a clean reference index by the check in tearDown()
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function modifyContent()
    {
        parent::modifyContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('modifyContent');
    }

    /**
     * @test
     */
    public function deleteContent()
    {
        parent::deleteContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('deleteContent');
    }

    /**
     * @test
     */
    public function copyContent()
    {
        parent::copyContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['copiedContentId']);
        $this->assertAssertionDataSet('copyContent');
    }

    /**
     * @test
     */
    public function localizeContent()
    {
        parent::localizeContent();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['localizedContentId']);
        $this->assertAssertionDataSet('localizeContent');
    }

    /**
     * @test
     */
    public function changeContentSorting()
    {
        parent::changeContentSorting();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdFirst);
        $this->assertAssertionDataSet('changeContentSorting');
    }

    /**
     * @test
     */
    public function moveContentToDifferentPage()
    {
        $newRecordIds = parent::moveContentToDifferentPage();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $newRecordIds[self::TABLE_Content][self::VALUE_ContentIdLast]);
        $this->assertAssertionDataSet('moveContentToDifferentPage');
    }

    /**
     * @test
     */
    public function moveContentToDifferentPageAndChangeSorting()
    {
        parent::moveContentToDifferentPageAndChangeSorting();
        $this->actionService->clearWorkspaceRecords([
            self::TABLE_Content => [self::VALUE_ContentIdFirst, self::VALUE_ContentIdLast],
        ]);
        $this->assertAssertionDataSet('moveContentToDifferentPageNChangeSorting');
    }

    /**
     * File references
     */

    /**
     * @test
     */
    public function createContentWithFileReference()
    {
        parent::createContentWithFileReference();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createContentWFileReference');
    }

    /**
     * @test
     */
    public function modifyContentWithFileReference()
    {
        parent::modifyContentWithFileReference();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('modifyContentWFileReference');
    }

    /**
     * @test
     */
    public function modifyContentAndAddFileReference()
    {
        parent::modifyContentAndAddFileReference();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('modifyContentNAddFileReference');
    }

    /**
     * @test
     */
    public function modifyContentAndDeleteFileReference()
    {
        parent::modifyContentAndDeleteFileReference();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('modifyContentNDeleteFileReference');
    }

    /**
     * @test
     */
    public function modifyContentAndDeleteAllFileReference()
    {
        parent::modifyContentAndDeleteAllFileReference();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
        $this->assertAssertionDataSet('modifyContentNDeleteAllFileReference');
    }

    /**
     * @test
     */
    public function createContentWithFileReferenceAndDeleteFileReference()
    {
        parent::createContentWithFileReferenceAndDeleteFileReference();
        $this->actionService->clearWorkspaceRecord(self::TABLE_Content, $this->recordIds['newContentId']);
        $this->assertAssertionDataSet('createContentWFileReferenceNDeleteFileReference');
        // No FE test: Create and delete scenarios have FE coverage, this test is only about DB state.
    }
}
