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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Group;

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    const VALUE_PageId = 89;
    const VALUE_PageIdTarget = 90;
    const VALUE_ContentIdFirst = 297;
    const VALUE_ContentIdLast = 298;
    const VALUE_LanguageId = 1;
    const VALUE_LanguageIdSecond = 2;
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

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler';

        parent::setUp();
        $this->importScenarioDataSet('ImportDefault');

        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);
        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    public function addElementRelation(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_ContentElement,
            [self::VALUE_ElementIdFirst, self::VALUE_ElementIdSecond, self::VALUE_ElementIdThird]
        );
    }

    public function deleteElementRelation(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_ContentElement,
            [self::VALUE_ElementIdFirst]
        );
    }

    public function changeElementSorting(): void
    {
        $this->actionService->moveRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, -self::VALUE_ElementIdSecond);
    }

    public function changeElementRelationSorting(): void
    {
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdFirst,
            self::FIELD_ContentElement,
            [self::VALUE_ElementIdSecond, self::VALUE_ElementIdFirst]
        );
    }

    public function createContentAndAddElementRelation(): void
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_Content,
            self::VALUE_PageId,
            ['header' => 'Testing #1', self::FIELD_ContentElement => self::VALUE_ElementIdFirst]
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];
    }

    public function createContentAndCreateElementRelation(): void
    {
        $newElementIds = $this->actionService->createNewRecord(self::TABLE_Element, self::VALUE_PageId, ['title' => 'Testing #1']);
        $this->recordIds['newElementId'] = $newElementIds[self::TABLE_Element][0];
        // It's not possible to use "NEW..." values for the TCA type 'group'
        $newContentIds = $this->actionService->createNewRecord(self::TABLE_Content, self::VALUE_PageId, ['header' => 'Testing #1', self::FIELD_ContentElement => $this->recordIds['newElementId']]);
        $this->recordIds['newContentId'] = $newContentIds[self::TABLE_Content][0];
    }

    public function modifyElementOfRelation(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, ['title' => 'Testing #1']);
    }

    public function modifyContentOfRelation(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Testing #1']);
    }

    public function modifyBothSidesOfRelation(): void
    {
        $this->actionService->modifyRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, ['title' => 'Testing #1']);
        $this->actionService->modifyRecord(self::TABLE_Content, self::VALUE_ContentIdFirst, ['header' => 'Testing #1']);
    }

    public function deleteContentOfRelation(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Content, self::VALUE_ContentIdLast);
    }

    public function deleteElementOfRelation(): void
    {
        $this->actionService->deleteRecord(self::TABLE_Element, self::VALUE_ElementIdFirst);
    }

    public function copyContentOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageId);
        $this->recordIds['copiedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function copyElementOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_PageId);
        $this->recordIds['copiedElementId'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdFirst];
    }

    /**
     * See DataSet/copyContentToLanguageOfRelation.csv
     */
    public function copyContentToLanguageOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    /**
     * See DataSet/copyElementToLanguageOfRelation.csv
     */
    public function copyElementToLanguageOfRelation(): void
    {
        $newTableIds = $this->actionService->copyRecordToLanguage(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedElementId'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdFirst];
    }

    public function localizeContentOfRelation(): void
    {
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
    }

    public function localizeContentOfRelationWithLanguageSynchronization(): void
    {
        $GLOBALS['TCA']['tt_content']['columns'][self::FIELD_ContentElement]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_ContentElement,
            [self::VALUE_ElementIdSecond, self::VALUE_ElementIdThird]
        );
    }

    public function localizeContentChainOfRelationWithLanguageSynchronizationSource(): void
    {
        $GLOBALS['TCA']['tt_content']['columns'][self::FIELD_ContentElement]['config']['behaviour']['allowLanguageSynchronization'] = true;
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_LanguageId);
        $this->recordIds['localizedContentIdFirst'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Content, $this->recordIds['localizedContentIdFirst'], self::VALUE_LanguageIdSecond);
        $this->recordIds['localizedContentIdSecond'] = $newTableIds[self::TABLE_Content][$this->recordIds['localizedContentIdFirst']];
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            $this->recordIds['localizedContentIdSecond'],
            ['l10n_state' => [self::FIELD_ContentElement => 'source']]
        );
        $this->actionService->modifyReferences(
            self::TABLE_Content,
            self::VALUE_ContentIdLast,
            self::FIELD_ContentElement,
            [self::VALUE_ElementIdSecond, self::VALUE_ElementIdThird]
        );
    }

    public function localizeElementOfRelation(): void
    {
        $newTableIds = $this->actionService->localizeRecord(self::TABLE_Element, self::VALUE_ElementIdFirst, self::VALUE_LanguageId);
        $this->recordIds['localizedElementId'] = $newTableIds[self::TABLE_Element][self::VALUE_ElementIdFirst];
    }

    public function moveContentOfRelationToDifferentPage(): void
    {
        $newTableIds = $this->actionService->moveRecord(self::TABLE_Content, self::VALUE_ContentIdLast, self::VALUE_PageIdTarget);
        // In workspaces new records are created and discard drops this one again, live creates no new record
        if (isset($newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast])) {
            $this->recordIds['movedContentId'] = $newTableIds[self::TABLE_Content][self::VALUE_ContentIdLast];
        }
    }
}
