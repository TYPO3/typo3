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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\FormEngine;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for fal metadata checks
 */
class FalMetadataCest
{
    /**
     * Call backend and open page module of styleguide page
     *
     * @param BackendTester $I
     * @param PageTree $pageTree
     */
    public function _before(BackendTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');
        $this->goToPageModule($I, $pageTree);
    }

    /**
     * This scenario tests whether updated sys_file_metadata fields are propagated to sys_file_reference
     * attached to tt_content
     * - creates tt_content
     * - attaches an image with empty metadata
     * - modifies image metadata
     * - checks if metadata is propagated to tt_content
     *
     * @param BackendTester $I
     * @param PageTree $pageTree
     * @throws \Exception
     */
    public function checkIfUpdatedFileMetadataIsUpdatedInContent(BackendTester $I, PageTree $pageTree)
    {
        $I->amGoingTo('Create new CE with image');
        $I->click('.t3js-page-new-ce a');
        $I->switchToMainFrame();
        $I->waitForElement('.t3js-modal.in');
        $I->wait(3);
        $I->waitForElementNotVisible('div#nprogess');
        $I->click('Text & Images');
        $I->switchToContentFrame();
        $I->waitForText('Create new Page Content on page');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[tt_content]") and contains(@data-formengine-input-name, "[header]")]', 'tt_content with image');

        $I->click('Images');
        $I->click('Add image');
        $I->switchToWindow();
        $I->switchToIFrame('modal_frame');
        $I->click('.list-tree-control-closed');
        $I->click('styleguide');
        $I->click('bus_lane.jpg');
        $I->switchToWindow();
        $I->switchToContentFrame();
        $I->waitForText('bus_lane.jpg');

        $I->see('Set element specific value (No default)', '.t3js-form-field-eval-null-placeholder-checkbox');

        $I->seeElementInDOM('//input[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[title]")]', ['placeholder' => '', 'value' => '']);
        $I->seeElementInDOM('//input[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[alternative]")]', ['placeholder' => '', 'value' => '']);
        $I->seeElementInDOM('//textarea[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[description]")]', ['placeholder' => '']);
        //textarea value is not in the attribute, so we need to check it separately
        $I->seeInField('//textarea[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[description]")]', '');

        $I->click('button[name="_savedok"]');
        $I->wait(3);
        $I->click('a[title="Close"]');

        $I->amGoingTo('Change default metadata');
        $I->switchToMainFrame();
        $I->click('Filelist');
        $I->switchToIFrame('nav_frame');
        $I->waitForText('fileadmin/ (auto-created)');
        $I->click('styleguide');

        $I->switchToWindow();
        $I->switchToContentFrame();
        $I->click('bus_lane.jpg');
        $I->waitForText('Edit File Metadata "bus_lane.jpg" on root level');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[sys_file_metadata]") and contains(@data-formengine-input-name, "[title]")]', 'Test title');
        $I->fillField('//textarea[contains(@data-formengine-input-name, "data[sys_file_metadata]") and contains(@data-formengine-input-name, "[description]")]', 'Test description');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[sys_file_metadata]") and contains(@data-formengine-input-name, "[alternative]")]', 'Test alternative');

        $I->click('button[name="_savedok"]');
        $I->wait(3);
        $I->click('a[title="Close"]');

        $I->amGoingTo('Check metadata of sys_file_reference displayed in tt_content');
        $this->goToPageModule($I, $pageTree);
        $I->switchToWindow();
        $I->switchToContentFrame();
        $I->click('tt_content with image');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Edit Page Content "tt_content with image" on page "styleguide TCA demo"');
        $I->click('Images');
        $I->click('.form-irre-header');

        $I->see('(Default: "Test title")', '.t3js-form-field-eval-null-placeholder-checkbox');
        $I->see('(Default: "Test alternative")', '.t3js-form-field-eval-null-placeholder-checkbox');
        $I->see('(Default: "Test description")', '.t3js-form-field-eval-null-placeholder-checkbox');

        $I->seeElementInDOM('//input[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[title]")]', ['placeholder' => 'Test title', 'value' => '']);
        $I->seeElementInDOM('//input[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[alternative]")]', ['placeholder' => 'Test alternative', 'value' => '']);
        $I->seeElementInDOM('//textarea[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[description]")]', ['placeholder' => 'Test description']);
        //textarea value is not in the attribute, so we need to check it separately
        $I->seeInField('//textarea[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[description]")]', '');
    }

    /**
     * This scenario tests whether sys_file_metadata fields are propagated to sys_file_reference
     * attached to tt_content
     *
     * - given an image with filled metadata (created in checkIfUpdatedFileMetadataIsUpdatedInContent test)
     * - creates a new tt_content
     * - attaches an image to tt_content
     * - checks if metadata is propagated to tt_content
     * - checks if checkboxes are unchecked and inputs are disabled
     *
     * test for https://forge.typo3.org/issues/81235
     *
     * @param BackendTester $I
     * @throws \Exception
     * @depends checkIfUpdatedFileMetadataIsUpdatedInContent
     */
    public function checkIfFileMetadataIsInheritedInContent(BackendTester $I)
    {
        $I->amGoingTo('Create new CE with image with filled metadata');
        $I->click('.t3js-page-new-ce a');
        $I->switchToMainFrame();
        $I->waitForElement('.t3js-modal.in');
        $I->wait(3);
        $I->waitForElementNotVisible('div#nprogess');
        $I->click('Text & Images');
        $I->switchToContentFrame();
        $I->waitForText('Create new Page Content on page');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[tt_content]") and contains(@data-formengine-input-name, "[header]")]', 'tt_content with image with filled metadata');

        $I->click('Images');
        $I->click('Add image');
        $I->switchToWindow();
        $I->switchToIFrame('modal_frame');
        $I->click('.list-tree-control-closed');
        $I->click('styleguide');
        $I->click('bus_lane.jpg');
        $I->switchToWindow();
        $I->switchToContentFrame();
        $I->waitForText('bus_lane.jpg');

        $I->waitForText('Image Metadata');

        $I->seeInField('//input[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[title]")]', '');
        $I->seeInField('//input[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[alternative]")]', '');
        $I->seeInField('//textarea[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[description]")]', '');

        $I->see('(Default: "Test title")', '.t3js-form-field-eval-null-placeholder-checkbox');
        $I->see('(Default: "Test alternative")', '.t3js-form-field-eval-null-placeholder-checkbox');
        $I->see('(Default: "Test description")', '.t3js-form-field-eval-null-placeholder-checkbox');

        $I->amGoingTo('assert checkboxes are not checked');
        $I->dontSeeCheckboxIsChecked('//input[contains(@name, "[title]") and @type="checkbox" and contains(@name, "control[active][sys_file_reference]")]');
        $I->dontSeeCheckboxIsChecked('//input[contains(@name, "[alternative]") and @type="checkbox" and contains(@name, "control[active][sys_file_reference]")]');
        $I->dontSeeCheckboxIsChecked('//input[contains(@name, "[description]") and @type="checkbox" and contains(@name, "control[active][sys_file_reference]")]');

        $I->amGoingTo('Assert hidden control field value (default value which is used when checkbox is not checked');
        $I->seeInField('//input[contains(@name, "[title]") and @type="hidden" and contains(@name, "control[active][sys_file_reference]")]', 0);
        $I->seeInField('//input[contains(@name, "[alternative]") and @type="hidden" and contains(@name, "control[active][sys_file_reference]")]', 0);
        $I->seeInField('//input[contains(@name, "[description]") and @type="hidden" and contains(@name, "control[active][sys_file_reference]")]', 0);

        //when checkbox is unchecked the disabled input is shown
        //<input type="text" class="form-control" disabled="disabled" value="Test title">
        $I->seeInField('input.form-control:disabled', 'Test title');
        $I->seeInField('input.form-control:disabled', 'Test alternative');
        $I->seeInField('textarea.form-control:disabled', 'Test description');
    }

    /**
     * This scenario tests whether activating a null placeholder checkbox focuses its assigned text field
     *
     * @param BackendTester $I
     * @param PageTree $pageTree
     * @depends checkIfUpdatedFileMetadataIsUpdatedInContent
     * @throws \Exception
     */
    public function checkIfDeactivatingNullCheckboxesFocusesTextFields(BackendTester $I, PageTree $pageTree): void
    {
        $I->amGoingTo('Check if deactivating null checkboxes focuses text fields');
        $this->goToPageModule($I, $pageTree);
        $I->switchToWindow();
        $I->switchToContentFrame();
        $I->click('tt_content with image');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Edit Page Content "tt_content with image" on page "styleguide TCA demo"');
        $I->click('Images');
        $I->click('.form-irre-header');

        $I->see('(Default: "Test title")', '.t3js-form-field-eval-null-placeholder-checkbox');
        $I->see('(Default: "Test alternative")', '.t3js-form-field-eval-null-placeholder-checkbox');
        $I->see('(Default: "Test description")', '.t3js-form-field-eval-null-placeholder-checkbox');

        $I->amGoingTo('assert checkboxes are not checked');
        $I->dontSeeCheckboxIsChecked('//input[contains(@name, "[title]") and @type="checkbox" and contains(@name, "control[active][sys_file_reference]")]');
        $I->dontSeeCheckboxIsChecked('//input[contains(@name, "[alternative]") and @type="checkbox" and contains(@name, "control[active][sys_file_reference]")]');
        $I->dontSeeCheckboxIsChecked('//input[contains(@name, "[description]") and @type="checkbox" and contains(@name, "control[active][sys_file_reference]")]');

        $I->seeInField('input.form-control:disabled', 'Test title');
        $I->seeInField('input.form-control:disabled', 'Test alternative');
        $I->seeInField('textarea.form-control:disabled', 'Test description');

        $I->amGoingTo('enable checkboxes and see whether fields are focused');
        foreach (['title', 'alternative', 'description'] as $fieldName) {
            $I->checkOption('//input[contains(@name, "[' . $fieldName . ']") and @type="checkbox" and contains(@name, "control[active][sys_file_reference]")]');
            $focus = $I->executeJS('let referenceUid = document.querySelector(\'[data-object-uid]\').dataset.objectUid; return document.querySelector(\'[data-formengine-input-name="data[sys_file_reference][\' + referenceUid + \'][' . $fieldName . ']"]\').matches(\':focus\')');
            $I->assertEquals(true, $focus);

            // Remove focus from field, otherwise codeception can't find other checkboxes
            $I->click('.form-irre-object .form-section');
        }
    }

    /**
     * Open page module of styleguide page
     *
     * @param BackendTester $I
     * @param PageTree $pageTree
     */
    private function goToPageModule(BackendTester $I, PageTree $pageTree)
    {
        $I->switchToMainFrame();
        $I->click('Page');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->switchToContentFrame();
        $I->waitForText('styleguide TCA demo');
    }
}
