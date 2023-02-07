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

/**
 * Tests for styleguide group element fields
 */
final class ElementsGroupWithKeyboardCest
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

    public function sortElementsInGroupWithArrowAndAltKeys(ApplicationTester $I): void
    {
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(1) > fieldset:nth-of-type(1)';
        $formWizardsWrap = $fieldset . ' > div:nth-of-type(1) div.t3js-formengine-field-item > div:nth-of-type(1)';
        $select = $formWizardsWrap . ' > div:nth-of-type(2) > select';

        $selectOption1 = 'styleguide demo user 1';
        $multiselect = ['styleguide demo user 1', 'styleguide demo user 2'];

        $I->amGoingTo('put "' . $selectOption1 . '" on first position');
        $I->selectOption($select, $selectOption1);
        $I->pressKey($select, [WebDriverKeys::ALT, WebDriverKeys::SHIFT, WebDriverKeys::UP]);
        $I->see($selectOption1, $select . ' > option:nth-child(1)');

        $I->amGoingTo('put "' . $selectOption1 . '" one position down / on the second position');
        $I->selectOption($select, $selectOption1);
        $I->pressKey($select, [WebDriverKeys::ALT, WebDriverKeys::DOWN]);
        $I->see($selectOption1, $select . ' > option:nth-child(2)');

        $I->amGoingTo('put "' . $selectOption1 . '" on the last position');
        $I->selectOption($select, $selectOption1);
        $I->pressKey($select, [WebDriverKeys::ALT, WebDriverKeys::SHIFT, WebDriverKeys::DOWN]);
        $I->see($selectOption1, $select . ' > option:nth-last-child(1)');

        $I->amGoingTo('put "' . $selectOption1 . '" one position up / on second last position');
        $I->selectOption($select, $selectOption1);
        $I->pressKey($select, [WebDriverKeys::ALT, WebDriverKeys::UP]);
        $I->see($selectOption1, $select . ' > option:nth-last-child(2)');

        $I->amGoingTo('put ' . print_r($multiselect, true) . ' on first position');
        $I->selectOption($select, $multiselect);
        $I->pressKey($select, [WebDriverKeys::ALT, WebDriverKeys::SHIFT, WebDriverKeys::UP]);
        $I->see($multiselect[0], $select . ' > option:nth-child(1)');
        $I->see($multiselect[1], $select . ' > option:nth-child(2)');

        $I->amGoingTo('put ' . print_r($multiselect, true) . ' one position down');
        $I->selectOption($select, $multiselect);
        $I->pressKey($select, [WebDriverKeys::ALT, WebDriverKeys::DOWN]);
        $I->see($multiselect[0], $select . ' > option:nth-child(2)');
        $I->see($multiselect[1], $select . ' > option:nth-child(3)');
    }

    public function removeElementInGroupWithDeleteKey(ApplicationTester $I): void
    {
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(1) > fieldset:nth-of-type(1)';
        $formWizardsWrap = $fieldset . ' > div:nth-of-type(1) div.t3js-formengine-field-item > div:nth-of-type(1)';
        $select = $formWizardsWrap . ' > div:nth-of-type(2) > select';

        $selectOption1 = 'styleguide demo user 1';
        $multiselect = ['styleguide demo user 1', 'styleguide demo user 2'];

        $I->amGoingTo('remove "' . $selectOption1);
        $I->selectOption($select, $selectOption1);
        $I->pressKey($select, WebDriverKeys::DELETE);
        $I->dontSee($selectOption1, $select . ' > option:nth-child(1)');

        $I->amGoingTo('remove ' . print_r($multiselect, true));
        $I->selectOption($select, $multiselect);
        $I->pressKey($select, WebDriverKeys::DELETE);
        $I->dontSee($multiselect[0], $select . ' > option:nth-child(1)');
        $I->dontSee($multiselect[1], $select . ' > option:nth-child(2)');
    }
}
