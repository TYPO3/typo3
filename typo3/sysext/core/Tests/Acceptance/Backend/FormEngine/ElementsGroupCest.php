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
 * Tests for styleguide group element fields
 */
class ElementsGroupCest
{
    /**
     * Open list module of styleguide elements group page
     *
     * @param BackendTester $I
     * @param PageTree $pageTree
     */
    public function _before(BackendTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');

        $I->click('List');
        $pageTree->openPath(['styleguide TCA demo', 'elements group']);
        $I->switchToContentFrame();

        $I->executeJS('window.name="TYPO3Main";');

        $I->waitForText('elements group', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_group a[data-original-title="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForText('Edit Form', 3, 'h1');
    }

    /**
     * @param BackendTester $I
     */
    public function sortElementsInGroup(BackendTester $I)
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

    /**
     * @param BackendTester $I
     */
    public function addARecordWithRecordBrowserGroup(BackendTester $I)
    {
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(1) > fieldset:nth-of-type(1)';
        $formWizardsWrap = $fieldset . ' > div:nth-of-type(1) div.t3js-formengine-field-item > div:nth-of-type(1)';

        $I->seeNumberOfElements('select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"] option', 4);
        $I->click($formWizardsWrap . ' div:nth-of-type(4) > div > a:nth-of-type(1)');

        $I->switchToWindow();
        $I->switchToIFrame('modal_frame');

        $I->amGoingTo('click + button to select record and close DB-Browser');
        $I->click('#recordlist-be_users > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)');

        $I->switchToWindow();
        $I->switchToContentFrame();
        $I->seeNumberOfElements('select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"] option', 5);
    }

    /**
     * @param BackendTester $I
     */
    public function addTwoRecordWithRecordBrowserGroup(BackendTester $I)
    {
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(1) > fieldset:nth-of-type(1)';
        $formWizardsWrap = $fieldset . ' > div:nth-of-type(1) div.t3js-formengine-field-item > div:nth-of-type(1)';

        $I->seeNumberOfElements('select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"] option', 4);
        $I->click($formWizardsWrap . ' div:nth-of-type(4) > div > a:nth-of-type(1)');

        $I->switchToWindow();
        $I->switchToIFrame('modal_frame');

        $I->amGoingTo('click record + in DB-Browser');
        $I->click('#recordlist-be_groups > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)');
        $I->amGoingTo('click + button to select record and close DB-Browser');
        $I->click('#recordlist-be_users > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)');

        $I->switchToWindow();
        $I->switchToContentFrame();
        $I->seeNumberOfElements('select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"] option', 6);
    }

    /**
     * @param BackendTester $I
     */
    public function searchForARecordWithRecordBrowserGroup(BackendTester $I)
    {
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(1) > fieldset:nth-of-type(1)';
        $formWizardsWrap = $fieldset . ' > div:nth-of-type(1) div.t3js-formengine-field-item > div:nth-of-type(1)';

        $I->seeNumberOfElements('select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"] option', 4);
        $I->click($formWizardsWrap . ' div:nth-of-type(4) > div > a:nth-of-type(1)');

        $I->switchToWindow();
        $I->switchToIFrame('modal_frame');

        $I->amGoingTo("search record '' and limit 1 in DB-Browser");
        $I->fillField('#showLimit', 1);
        $I->click('button[name="search"]');
        $I->waitForElement('.recordlist');
        $I->seeNumberOfElements('.recordlist #recordlist-be_groups  table tbody tr', 1);

        $I->amGoingTo('search record style and limit 1 in DB-Browser');
        $I->fillField('#search_field', 'style');
        $I->click('button[name="search"]');
        $I->waitForElement('.recordlist');
        $I->seeNumberOfElements('.recordlist #recordlist-be_groups  table tbody tr', 1);

        $I->amGoingTo('reset limit');
        $I->fillField('#showLimit', '');
        $I->amGoingTo('search record foo in DB-Browser');
        $I->fillField('#search_field', 'foo');
        $I->click('button[name="search"]');
        $I->waitForElementNotVisible('.recordlist');

        $I->amGoingTo('search record admin in DB-Browser');
        $I->fillField('#search_field', 'admin');
        $I->click('button[name="search"]');
        $I->waitForElement('.recordlist');
        $I->see('admin', '.recordlist');

        $I->amGoingTo('click + button to select record and close DB-Browser');
        $I->click('#recordlist-be_users > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)');
        $I->switchToWindow();
        $I->click('.t3js-modal-close');

        $I->switchToContentFrame();
        $I->see('admin', 'select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"]');
        $I->click('.btn-toolbar button.btn:nth-child(2)');
        $I->click('button[name="_savedok"]');
    }
}
