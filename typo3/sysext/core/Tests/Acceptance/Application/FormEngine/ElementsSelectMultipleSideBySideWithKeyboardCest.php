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

use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

final class ElementsSelectMultipleSideBySideWithKeyboardCest
{
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');

        $I->click('List');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo', 'elements select']);
        $I->switchToContentFrame();

        $I->waitForText('elements select', 20);
        $editRecordLinkCssPath = '#recordlist-tx_styleguide_elements_select a[aria-label="Edit record"]';
        $I->click($editRecordLinkCssPath);
        $I->waitForText('Edit Form', 3, 'h1');

        $I->click('renderType=selectMultipleSideBySide');
    }

    public function addElementsWithEnterKey(ApplicationTester $I): void
    {
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(4) > fieldset:nth-of-type(1)';
        $formWizardsWrap = $fieldset . ' .form-wizards-element';
        $selectAvailable = $formWizardsWrap . ' > div:nth-of-type(1) > div:nth-of-type(2) select';

        $I->amGoingTo('move the focus to the select with available items');
        // sets the focus on the left select containing the current selected items
        // by default only "foo 2" is in the list
        $I->pressKey($selectAvailable, WebDriverKeys::TAB);
        $I->pressKey($selectAvailable, WebDriverKeys::ARROW_DOWN);

        $I->amGoingTo('add the first option by pressing the Enter key');
        $I->pressKey($selectAvailable, WebDriverKeys::ENTER);

        $selectSelected = $formWizardsWrap . ' > div:nth-of-type(1) > div:nth-of-type(1) select';
        $I->see('foo 1', $selectSelected . ' > option:nth-child(2)');
    }

    public function removeElementWithDeleteKey(ApplicationTester $I): void
    {
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(4) > fieldset:nth-of-type(1)';
        $formWizardsWrap = $fieldset . ' .form-wizards-element';
        $selectSelected = $formWizardsWrap . ' > div:nth-of-type(1) > div:nth-of-type(1) select';

        $I->amGoingTo('the first item in the list');
        $I->pressKey($selectSelected, WebDriverKeys::TAB);
        $I->pressKey($selectSelected, WebDriverKeys::ARROW_DOWN);
        $I->pressKey($selectSelected, WebDriverKeys::DELETE);

        $I->dontSee('foo 2', $selectSelected . ' > option:nth-child(1)');
    }
}
