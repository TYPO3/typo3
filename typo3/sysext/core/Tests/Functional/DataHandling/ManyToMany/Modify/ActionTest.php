<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\ManyToMany\Modify;

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
class ActionTest extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\ManyToMany\AbstractActionTestCase
{
    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/ManyToMany/Modify/DataSet/';

    /**
     * MM Relations
     */

    /**
     * @test
     * @see DataSet/addCategoryRelation.csv
     */
    public function addCategoryRelation()
    {
        parent::addCategoryRelation();
        $this->assertAssertionDataSet('addCategoryRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B', 'Category A.A'));
    }

    /**
     * @test
     * @see DataSet/deleteCategoryRelation.csv
     */
    public function deleteCategoryRelation()
    {
        parent::deleteCategoryRelation();
        $this->assertAssertionDataSet('deleteCategoryRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C', 'Category A.A'));
    }

    /**
     * @test
     * @see DataSet/changeCategoryRelationSorting.csv
     */
    public function changeCategoryRelationSorting()
    {
        parent::changeCategoryRelationSorting();
        $this->assertAssertionDataSet('changeCategoryRelationSorting');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
    }

    /**
     * @test
     * @see DataSet/modifyCategoryRecordOfCategoryRelation.csv
     */
    public function modifyCategoryOfRelation()
    {
        parent::modifyCategoryOfRelation();
        $this->assertAssertionDataSet('modifyCategoryOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1', 'Category B'));
    }

    /**
     * @test
     * @see DataSet/modifyContentRecordOfCategoryRelation.csv
     */
    public function modifyContentOfRelation()
    {
        parent::modifyContentOfRelation();
        $this->assertAssertionDataSet('modifyContentOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * @see DataSet/modifyBothRecordsOfCategoryRelation.csv
     */
    public function modifyBothsOfRelation()
    {
        parent::modifyBothsOfRelation();
        $this->assertAssertionDataSet('modifyBothsOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Testing #1', 'Category B'));
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * @see DataSet/deleteContentRecordOfCategoryRelation.csv
     */
    public function deleteContentOfRelation()
    {
        parent::deleteContentOfRelation();
        $this->assertAssertionDataSet('deleteContentOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionDoesNotHaveRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Testing #1'));
    }

    /**
     * @test
     * @see DataSet/deleteCategoryRecordOfCategoryRelation.csv
     */
    public function deleteCategoryOfRelation()
    {
        parent::deleteCategoryOfRelation();
        $this->assertAssertionDataSet('deleteCategoryOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A'));
    }

    /**
     * @test
     * @see DataSet/copyContentRecordOfCategoryRelation.csv
     */
    public function copyContentOfRelation()
    {
        parent::copyContentOfRelation();
        $this->assertAssertionDataSet('copyContentOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentId'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }

    /**
     * @test
     * @see DataSet/copyCategoryRecordOfCategoryRelation.csv
     */
    public function copyCategoryOfRelation()
    {
        parent::copyCategoryOfRelation();
        $this->assertAssertionDataSet('copyCategoryOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category A (copy 1)'));
    }

    /**
     * @test
     * @see DataSet/copyContentToLanguageOfRelation.csv
     */
    public function copyContentToLanguageOfRelation()
    {
        parent::copyContentToLanguageOfRelation();
        $this->assertAssertionDataSet('copyContentToLanguageOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }

    /**
     * @test
     * @see DataSet/copyCategoryToLanguageOfRelation.csv
     */
    public function copyCategoryToLanguageOfRelation()
    {
        parent::copyCategoryToLanguageOfRelation();
        $this->assertAssertionDataSet('copyCategoryToLanguageOfRelation');
        //in this case the translated element is orphaned (no CE with relation to it, and it has no l10n_parent)
        //so on frontend there is no change.
        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
    }

    /**
     * @test
     * @see DataSet/localizeContentRecordOfCategoryRelation.csv
     */
    public function localizeContentOfRelation()
    {
        parent::localizeContentOfRelation();
        $this->assertAssertionDataSet('localizeContentOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }

    /**
     * @test
     * @see DataSet/localizeCategoryRecordOfCategoryRelation.csv
     */
    public function localizeCategoryOfRelation()
    {
        parent::localizeCategoryOfRelation();
        $this->assertAssertionDataSet('localizeCategoryOfRelation');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageId, self::VALUE_LanguageId)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdFirst)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('[Translate to Dansk:] Category A', 'Category B'));
    }

    /**
     * @test
     * @see DataSet/moveContentRecordOfCategoryRelationToDifferentPage.csv
     */
    public function moveContentOfRelationToDifferentPage()
    {
        parent::moveContentOfRelationToDifferentPage();
        $this->assertAssertionDataSet('moveContentOfRelationToDifferentPage');

        $responseSections = $this->getFrontendResponse(self::VALUE_PageIdTarget)->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . self::VALUE_ContentIdLast)->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }

    /**
     * @test
     * @see DataSet/copyPage.csv
     */
    public function copyPage()
    {
        parent::copyPage();
        $this->assertAssertionDataSet('copyPage');

        $responseSections = $this->getFrontendResponse($this->recordIds['newPageId'])->getResponseSections();
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Page)->setField('title')->setValues('Relations'));
        $this->assertThat($responseSections, $this->getRequestSectionHasRecordConstraint()
            ->setTable(self::TABLE_Content)->setField('header')->setValues('Regular Element #1', 'Regular Element #2'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdFirst'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category A', 'Category B'));
        $this->assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':' . $this->recordIds['newContentIdLast'])->setRecordField('categories')
            ->setTable(self::TABLE_Category)->setField('title')->setValues('Category B', 'Category C'));
    }
}
