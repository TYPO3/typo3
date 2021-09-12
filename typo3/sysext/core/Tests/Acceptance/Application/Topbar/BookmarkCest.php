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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Topbar;

use Codeception\Scenario;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\TestingFramework\Core\Acceptance\Helper\Topbar;

/**
 * Test for the "Bookmark" functionality
 */
class BookmarkCest
{
    /**
     * Selector for the module container in the topbar
     */
    public static string $topBarModuleSelector = '#typo3-cms-backend-backend-toolbaritems-shortcuttoolbaritem';

    /**
     * Selector for the "Add to bookmark" button
     */
    protected static string $docHeaderBookmarkButtonSelector = '#dropdownShortcutMenu';

    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function checkThatBookmarkListIsInitiallyEmpty(ApplicationTester $I): void
    {
        $this->clickBookmarkDropdownToggleInTopbar($I);
        $I->cantSeeElement(self::$topBarModuleSelector . ' .shortcut');
    }

    public function checkThatAddingABookmarkAddsItemToTheBookmarkList(ApplicationTester $I, ModalDialog $dialog, Scenario $scenario): ApplicationTester
    {
        // open the scheduler module as we would like to put it into the bookmark list
        $I->click('Scheduler', '.scaffold-modulemenu');

        $I->switchToContentFrame();

        $I->click(self::$docHeaderBookmarkButtonSelector);
        $I->waitForElementVisible('.module-docheader .dropdown-menu');
        $I->click('.module-docheader .dropdown-menu button:nth-of-type(1)');
        // cancel the action to test the functionality
        // don't use $modalDialog->clickButtonInDialog due to too low timeout
        $dialog->canSeeDialog();
        $I->click('Cancel', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);

        // check if the list is still empty
        $this->clickBookmarkDropdownToggleInTopbar($I);
        $I->cantSeeElement(self::$topBarModuleSelector . ' .shortcut');

        $I->switchToContentFrame();
        $I->click(self::$docHeaderBookmarkButtonSelector);
        $I->waitForElementVisible('.module-docheader .dropdown-menu');
        $I->click('.module-docheader .dropdown-menu button:nth-of-type(1)');

        $dialog->canSeeDialog();
        $dialog->clickButtonInDialog('OK');

        $this->clickBookmarkDropdownToggleInTopbar($I);
        $I->waitForText('Scheduled tasks', 15, self::$topBarModuleSelector . ' ' . Topbar::$dropdownListSelector);

        // @test complete test when https://forge.typo3.org/issues/75689 is fixed
        $scenario->comment(
            'Tests for deleting the item in the list and re-adding it are missing ' .
            'as this is currently broken in the core. See https://forge.typo3.org/issues/75689'
        );

        return $I;
    }

    /**
     * @depends checkThatAddingABookmarkAddsItemToTheBookmarkList
     */
    public function checkIfBookmarkItemLinksToTarget(ApplicationTester $I): void
    {
        $this->clickBookmarkDropdownToggleInTopbar($I);
        $I->click('Scheduled tasks', self::$topBarModuleSelector);
        $I->switchToContentFrame();
        $I->canSee('Scheduled tasks', 'h1');
    }

    /**
     * @depends checkThatAddingABookmarkAddsItemToTheBookmarkList
     */
    public function checkIfEditBookmarkItemWorks(ApplicationTester $I): void
    {
        $this->clickBookmarkDropdownToggleInTopbar($I);
        $firstShortcutSelector = self::$topBarModuleSelector . ' .t3js-topbar-shortcut';
        $I->click('.t3js-shortcut-edit', $firstShortcutSelector);
        $secondShortcutSelector = self::$topBarModuleSelector . ' form.shortcut-form';
        $I->fillField($secondShortcutSelector . ' input[name="shortcut-title"]', 'Scheduled tasks renamed');
        $I->click('.shortcut-form-save', $secondShortcutSelector);

        // searching in a specific context fails with an "Stale Element Reference Exception"
        // see http://docs.seleniumhq.org/exceptions/stale_element_reference.jsp
        // currently don't know how to fix that so we search in the whole context.
        $I->waitForText('Scheduled tasks renamed');
    }

    /**
     * @depends checkThatAddingABookmarkAddsItemToTheBookmarkList
     */
    public function checkIfDeleteBookmarkWorks(ApplicationTester $I, ModalDialog $dialog): void
    {
        $this->clickBookmarkDropdownToggleInTopbar($I);

        $I->canSee('Scheduled tasks renamed', self::$topBarModuleSelector);
        $I->click('.t3js-shortcut-delete', self::$topBarModuleSelector . ' .t3js-topbar-shortcut');
        $dialog->clickButtonInDialog('OK');

        $I->cantSee('Scheduled tasks renamed', self::$topBarModuleSelector);
    }

    protected function clickBookmarkDropdownToggleInTopbar(ApplicationTester $I): void
    {
        $I->waitForElementVisible(self::$topBarModuleSelector . ' ' . Topbar::$dropdownToggleSelector);
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
    }
}
