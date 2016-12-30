<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Group;

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
    const VALUE_ContentIdFirst = 297;
    const VALUE_ContentIdLast = 298;
    const VALUE_LanguageId = 1;
    const VALUE_ElementIdFirst = 1;
    const VALUE_ElementIdSecond = 2;
    const VALUE_ElementIdThird = 3;

    const TABLE_Content = 'tt_content';
    const TABLE_Element = 'tx_testdatahandler_element';

    const FIELD_ContentElement = 'tx_testdatahandler_group';

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Group/DataSet/';

    protected function setUp()
    {
        $this->testExtensionsToLoad[] = 'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler';

        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');
        $this->importScenarioDataSet('ReferenceIndex');

        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.ts']);
    }

    /**
     * Relations
     */

    /**
     * @test
     * @see DataSet/addElementRelation.csv
     */
    public function addElementRelation()
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content, self::VALUE_ContentIdFirst, self::FIELD_ContentElement, [self::VALUE_ElementIdFirst, self::VALUE_ElementIdSecond, self::VALUE_ElementIdThird]
        );
    }

    /**
     * @test
     * @see DataSet/deleteElementRelation.csv
     */
    public function deleteElementRelation()
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content, self::VALUE_ContentIdFirst, self::FIELD_ContentElement, [self::VALUE_ElementIdFirst]
        );
    }

    /**
     * @test
     * @see DataSet/changeElementSorting.csv
     */
    public function changeElementSorting()
    {
        $this->actionService->moveRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, -self::VALUE_ElementIdSecond);
    }

    /**
     * @test
     * @see DataSet/changeElementRelationSorting.csv
     */
    public function changeElementRelationSorting()
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content, self::VALUE_ContentIdFirst, self::FIELD_ContentElement, [self::VALUE_ElementIdSecond, self::VALUE_ElementIdFirst]
        );
    }

    /**
     * @test
     * @see DataSet/createContentNAddRelation.csv
     */
    public function createContentAndAddElementRelation()
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1', self::FIELD_ContentElement => self::VALUE_ElementIdFirst]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
    }

    /**
     * @test
     * @see DataSet/createContentNCreateRelation.csv
     */
    public function createContentAndCreateElementRelation()
    {
        $newElementIds = $this->actionService->createNewRecord(self::TABLE_Element, self::VALUE_PageId, ['title' => 'Testing #1']);
        $this->recordIds['newElementId'] = $newElementIds[self::TABLE_Element][0];
        // It's not possible to use "NEW..." values for the TCA type 'group'
        $newContentIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1', self::FIELD_ContentElement => $this->recordIds['newElementId']]);
        $this->recordIds['newContentId'] = $newContentIds[self::TABLE_Content][0];
    }

    /**
     * @test
     * @see DataSet/modifyElementOfRelation.csv
     */
    public function modifyElementOfRelation()
    {
        $this->actionService->modifyRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, ['title' => 'Testing #1']);
    }

    /**
     * @test
     * @see DataSet/modifyContentOfRelation.csv
     */
    public function modifyContentOfRelation()
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Testing #1']);
    }

    /**
     * @test
     * @see DataSet/modifyBothSidesOfRelation.csv
     */
    public function modifyBothSidesOfRelation()
    {
        $this->actionService->modifyRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, ['title' => 'Testing #1']);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Testing #1']);
    }

    /**
     * @test
     * @see DataSet/deleteContentOfRelation.csv
     */
    public function deleteContentOfRelation()
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
    }

    /**
     * @test
     * @see DataSet/deleteElementOfRelation.csv
     */
    public function deleteElementOfRelation()
    {
        $this->actionService->deleteRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
    }

    /**
     * @test
     * @see DataSet/copyContentOfRelation.csv
     */
    public function copyContentOfRelation()
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * @test
     * @see DataSet/copyElementOfRelation.csv
     */
    public function copyElementOfRelation()
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_PageId);
        $this->recordIds['copiedElementId'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdFirst];
    }

    /**
     * @test
     * @see DataSet/copyContentToLanguageOfRelation.csv
     */
    public function copyContentToLanguageOfRelation()
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * @test
     * @see DataSet/copyElementToLanguageOfRelation.csv
     */
    public function copyElementToLanguageOfRelation()
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedElementId'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdFirst];
    }

    /**
     * @test
     * @see DataSet/localizeContentOfRelation.csv
     */
    public function localizeContentOfRelation()
    {
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * @test
     * @see DataSet/localizeElementOfRelation.csv
     */
    public function localizeElementOfRelation()
    {
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedElementId'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdFirst];
    }

    /**
     * @test
     * @see DataSet/moveContentOfRelationToDifferentPage.csv
     */
    public function moveContentOfRelationToDifferentPage()
    {
        $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
    }
}
