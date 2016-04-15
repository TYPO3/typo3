<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Topbar;

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

use Codeception\Scenario;
use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Admin;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\Topbar;

/**
 * Test for the "Favorite" functionality
 */
class FavoriteCest
{
    /**
     * Selector for the module container in the topbar
     *
     * @var string
     */
    public static $topBarModuleSelector = '#typo3-cms-backend-backend-toolbaritems-shortcuttoolbaritem';

    /**
     * Selector for the "Add to favorite" button
     *
     * @var string
     */
    protected static $docHeaderFavoriteButtonSelector = '.module-docheader .btn[title="Create a bookmark to this page"]';

    /**
     * @param Admin $I
     */
    public function _before(Admin $I)
    {
        $I->useExistingSession();
        $I->switchToIFrame('content');
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions
        $I->waitForText('Web>Page module');
    }

    /**
     * @param Admin $I
     * @return Admin
     */
    public function checkThatFavoriteListIsInitiallyEmpty(Admin $I)
    {
        $this->clickFavoriteDropdownToggleInTopbar($I);
        $I->cantSeeElement(self::$topBarModuleSelector . ' .shortcut');
        return $I;
    }

    /**
     * @depends checkThatFavoriteListIsInitiallyEmpty
     * @param Admin $I
     * @param ModalDialog $dialog
     * @return Admin
     */
    public function checkThatAddingAFavoriteAddAItemToTheFavoriteList(Admin $I, ModalDialog $dialog, Scenario $scenario)
    {
        $I->switchToIFrame();
        // open the scheduler module as we would like to put it into the favorite liste
        $I->click('Scheduler', '#typo3-module-menu');

        $I->switchToIFrame('content');

        $I->click(self::$docHeaderFavoriteButtonSelector);
        // cancel the action to test the functionality
        $dialog->clickButtonInDialog('Cancel');

        // check if the list is still empty
        $this->checkThatFavoriteListIsInitiallyEmpty($I);

        $I->switchToIFrame('content');
        $I->click(self::$docHeaderFavoriteButtonSelector);

        $dialog->clickButtonInDialog('OK');

        $this->clickFavoriteDropdownToggleInTopbar($I);
        $I->canSee('Scheduled tasks', self::$topBarModuleSelector . ' ' . Topbar::$dropdownContainerSelector);

        // @test complese test when https://forge.typo3.org/issues/75689 is fixed
        $scenario->comment(
            'Test for deleting the item in the list and readd it are missing ' .
            'as this is currently broken in the core. See https://forge.typo3.org/issues/75689'
        );

        return $I;
    }

    /**
     * @param Admin $I
     * @depends checkThatAddingAFavoriteAddAItemToTheFavoriteList
     */
    public function checkIfFavoriteItemLinksToTarget(Admin $I)
    {
        $this->clickFavoriteDropdownToggleInTopbar($I);
        $I->click('Scheduled tasks', self::$topBarModuleSelector);
        $I->switchToIFrame('content');
        $I->canSee('Scheduled tasks', 'h1');
    }

    /**
     * @param Admin $I
     * @depends checkThatAddingAFavoriteAddAItemToTheFavoriteList
     */
    public function checkIfEditFavoriteItemWorks(Admin $I)
    {
        $this->clickFavoriteDropdownToggleInTopbar($I);
        $firstShortcutSelector = self::$topBarModuleSelector . ' .shortcut';
        $I->click('.shortcut-edit', $firstShortcutSelector);

        $I->fillField($firstShortcutSelector . ' input[name="shortcut-title"]', 'Scheduled tasks renamed');
        $I->click('.shortcut-form-save', $firstShortcutSelector);

        // searching in a specific context fails with an "Stale Element Reference Exception"
        // see http://docs.seleniumhq.org/exceptions/stale_element_reference.jsp
        // currently don't know how to fix that so we search in the whole context.
        $I->waitForText('Scheduled tasks renamed');
    }

    /**
     * @param Admin $I
     * @depends checkThatAddingAFavoriteAddAItemToTheFavoriteList
     */
    public function checkIfDeleteFavoriteWorks(Admin $I, ModalDialog $dialog)
    {
        $this->clickFavoriteDropdownToggleInTopbar($I);

        $I->canSee('Scheduled tasks renamed', self::$topBarModuleSelector);
        $I->click('.shortcut-delete', self::$topBarModuleSelector . ' .shortcut');
        $dialog->clickButtonInDialog('OK');

        $I->cantSee('Scheduled tasks renamed', self::$topBarModuleSelector);
    }

    /**
     * @param Admin $I
     */
    protected function clickFavoriteDropdownToggleInTopbar(Admin $I)
    {
        $I->switchToIFrame();
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
    }
}
