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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\FormEngine;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests for styleguide group element fields
 */
final class ElementsGroupCest
{
    /**
     * Open list module of styleguide elements group page
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');

        $I->click('List');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo', 'elements group']);
        $I->switchToContentFrame();

        $I->executeJS('window.name="TYPO3Main";');

        $I->waitForText('elements group', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_group a[aria-label="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForText('Edit Form', 3, 'h1');
    }

    public function sortElementsInGroup(ApplicationTester $I): void
    {
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(1) > fieldset:nth-of-type(1)';
        $formWizardsWrap = $fieldset . ' > div:nth-of-type(1) div.t3js-formengine-field-item > div:nth-of-type(1)';
        $select = $formWizardsWrap . ' > div:nth-of-type(2) > select';

        $selectOption1 = 'styleguide demo user 1';
        $multiselect = ['styleguide demo user 1', 'styleguide demo user 2'];

        $I->amGoingTo('put "' . $selectOption1 . '" on first position');
        $I->selectOption($select, $selectOption1);
        $I->click($formWizardsWrap . ' div:nth-of-type(3) > div > a.t3js-btn-moveoption-top');
        $I->see($selectOption1, $select . ' > option:nth-child(1)');

        $I->amGoingTo('put "' . $selectOption1 . '" one position down / on the second position');
        $I->selectOption($select, $selectOption1);
        $I->click($formWizardsWrap . ' div:nth-of-type(3) > div > a.t3js-btn-moveoption-down');
        $I->see($selectOption1, $select . ' > option:nth-child(2)');

        $I->amGoingTo('put "' . $selectOption1 . '" on the last position');
        $I->selectOption($select, $selectOption1);
        $I->click($formWizardsWrap . ' div:nth-of-type(3) > div > a.t3js-btn-moveoption-bottom');
        $I->see($selectOption1, $select . ' > option:nth-last-child(1)');

        $I->amGoingTo('put "' . $selectOption1 . '" one position up / on second last position');
        $I->selectOption($select, $selectOption1);
        $I->click($formWizardsWrap . ' div:nth-of-type(3) > div > a.t3js-btn-moveoption-up');
        $I->see($selectOption1, $select . ' > option:nth-last-child(2)');

        $I->amGoingTo('put ' . print_r($multiselect, true) . ' on first position');
        $I->selectOption($select, $multiselect);
        $I->click($formWizardsWrap . ' div:nth-of-type(3) > div > a.t3js-btn-moveoption-top');
        $I->see($multiselect[0], $select . ' > option:nth-child(1)');
        $I->see($multiselect[1], $select . ' > option:nth-child(2)');

        $I->amGoingTo('put ' . print_r($multiselect, true) . ' one position down');
        $I->selectOption($select, $multiselect);
        $I->click($formWizardsWrap . ' div:nth-of-type(3) > div > a.t3js-btn-moveoption-down');
        $I->see($multiselect[0], $select . ' > option:nth-child(2)');
        $I->see($multiselect[1], $select . ' > option:nth-child(3)');
    }

    public function addARecordWithRecordBrowserGroup(ApplicationTester $I): void
    {
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(1) > fieldset:nth-of-type(1)';
        $formWizardsWrap = $fieldset . ' > div:nth-of-type(1) div.t3js-formengine-field-item > div:nth-of-type(1)';

        $I->seeNumberOfElements('select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"] option', 4);
        $I->click($formWizardsWrap . ' div:nth-of-type(4) > div > a:nth-of-type(1)');

        $I->switchToWindow('typo3-backend');
        $I->switchToIFrame('modal_frame');

        $I->amGoingTo('click + button to select record and close DB-Browser');
        $I->click('#recordlist-be_users > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)');

        $I->switchToWindow('typo3-backend');
        $I->switchToContentFrame();
        $I->seeNumberOfElements('select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"] option', 5);
    }

    public function addTwoRecordWithRecordBrowserGroup(ApplicationTester $I): void
    {
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(1) > fieldset:nth-of-type(1)';
        $formWizardsWrap = $fieldset . ' > div:nth-of-type(1) div.t3js-formengine-field-item > div:nth-of-type(1)';

        $I->seeNumberOfElements('select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"] option', 4);
        $I->click($formWizardsWrap . ' div:nth-of-type(4) > div > a:nth-of-type(1)');

        $I->switchToWindow('typo3-backend');
        $I->switchToIFrame('modal_frame');

        $I->amGoingTo('click record + in DB-Browser');
        $I->click('#recordlist-be_groups > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)');
        $I->amGoingTo('click + button to select record and close DB-Browser');
        $I->click('#recordlist-be_users > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)');

        $I->switchToWindow('typo3-backend');
        $I->switchToContentFrame();
        $I->seeNumberOfElements('select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"] option', 6);
    }

    public function searchForARecordWithRecordBrowserGroup(ApplicationTester $I): void
    {
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(1) > fieldset:nth-of-type(1)';
        $formWizardsWrap = $fieldset . ' > div:nth-of-type(1) div.t3js-formengine-field-item > div:nth-of-type(1)';

        $I->seeNumberOfElements('select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"] option', 4);
        $I->click($formWizardsWrap . ' div:nth-of-type(4) > div > a:nth-of-type(1)');

        $I->switchToWindow('typo3-backend');
        $I->switchToIFrame('modal_frame');

        $I->amGoingTo('search record foo in DB-Browser');
        $I->fillField('#recordsearchbox-searchterm', 'foo');
        $I->click('button[name="search"]');
        $I->waitForElementNotVisible('.recordlist');

        $I->amGoingTo('search record admin in DB-Browser');
        $I->fillField('#recordsearchbox-searchterm', 'admin');
        $I->click('button[name="search"]');
        $I->waitForElement('.recordlist');
        $I->see('admin', '.recordlist');

        $I->amGoingTo('click + button to select record and close DB-Browser');
        $I->click('#recordlist-be_users > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)');
        $I->switchToWindow('typo3-backend');
        $I->click('.t3js-modal-close');

        $I->switchToContentFrame();
        $I->see('admin', 'select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"]');
        $I->click('.btn-toolbar button.btn:nth-child(2)');
        $I->click('button[name="_savedok"]');
    }
}
