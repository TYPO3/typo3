<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Formhandler;

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

use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;
use TYPO3\TestingFramework\Core\Acceptance\Support\Page\PageTree;

/**
 * Tests for inline 1n
 */
class FalMetadataInheritanceCest
{
    public function _before(Admin $I, PageTree $pageTree)
    {
        $I->useExistingSession();
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
     * @param Admin $I
     * @param PageTree $pageTree
     * @throws \Exception
     */
    public function checkIfUpdatedFileMetadataIsUpdatedInContent(Admin $I, PageTree $pageTree)
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
        $I->switchToIFrame('typo3-navigationContainerIframe');
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
     * - given a image with filled metadata (created in checkIfUpdatedFileMetadataIsUpdatedInContent test)
     * - creates a new tt_content
     * - attaches an image to tt_content
     * - checks if metadata is propagated to tt_content
     * - checks if checkboxes are unchecked and inputs are disabled
     *
     * test for https://forge.typo3.org/issues/81235
     *
     * @param Admin $I
     * @param PageTree $pageTree
     * @throws \Exception
     * @depends checkIfUpdatedFileMetadataIsUpdatedInContent
     */
    public function checkIfFileMetadataIsInheritedInContent(Admin $I)
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
     * @param Admin $I
     * @param PageTree $pageTree
     * @throws \Exception
     */
    protected function goToPageModule(Admin $I, PageTree $pageTree)
    {
        $I->click('Page');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->switchToContentFrame();
        $I->waitForText('styleguide TCA demo');
    }
}
