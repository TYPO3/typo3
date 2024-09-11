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
 * Tests for type=file (FAL)
 */
final class LinkBrowserCest
{
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');

        $I->click('List');
        $pageTree->openPath(['styleguide TCA demo', 'elements basic']);
        $I->switchToContentFrame();

        $I->waitForText('Unique ID', 20);

        $I->click('#t3-table-tx_styleguide_elements_basic a[aria-label="Edit record"]');
        $I->waitForText('Edit Form', 3, 'h1');

        $I->click('link', '.t3js-tabs');
    }

    public function closeLinkBrowserInIframeOneEscapeKey(ApplicationTester $I): void
    {
        $I->click('.tab-pane.active .form-wizards-item-aside--field-control');
        $I->switchToMainFrame();
        $I->waitForElement('.t3js-modal-iframe');
        $I->switchToIFrame('.t3js-modal-iframe');
        $I->fillField('input[name="lclass"]', 'lazy-dave');

        $I->amGoingTo('see the modal disappear when the user hits ESC');
        $I->pressKey('input[name="lclass"]', WebDriverKeys::ESCAPE);
        $I->dontSeeElement('.t3js-modal-iframe');
    }
}
