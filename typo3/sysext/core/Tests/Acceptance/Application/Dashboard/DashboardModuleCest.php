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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Dashboard;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Tests concerning Reports Module
 */
final class DashboardModuleCest
{
    private static string $defaultDashboardTitle = 'My Dashboard';
    private static string $customDashboardTitle = 'My Custom Dashboard';
    private static string $dashboardActiveSelector = '.dashboard-tab--active';
    private static string $widgetTitle = 'Type of backend users';
    private static string $widgetTitleSelector = '.widget-title';

    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->click('[data-modulemenu-identifier="dashboard"]');
        $I->switchToContentFrame();
    }

    public function seeInitialDashboardAndWidgets(ApplicationTester $I): void
    {
        $I->see(self::$defaultDashboardTitle, self::$dashboardActiveSelector);
        $I->see('About TYPO3', self::$widgetTitleSelector);
        $I->see('Getting Started with TYPO3', self::$widgetTitleSelector);
    }

    public function createCustomDashboardAndWidgets(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        // Create Dashboard
        $I->click('.btn-dashboard-add-tab');
        $modalDialog->canSeeDialog();
        $I->fillField('#dashboard-form-add-title', self::$customDashboardTitle);
        $I->click('label[for="dashboard-form-add-preset-empty"]');
        $I->click('button[name="save"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->switchToContentFrame();
        $I->see(self::$customDashboardTitle, self::$dashboardActiveSelector);

        // Add widget
        $I->waitForElementVisible('.btn-dashboard-add-widget');
        $I->click('.btn-dashboard-add-widget');
        $modalDialog->canSeeDialog();
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"systemInfo\"]').click()");
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"typeOfUsers\"]').click()");
        $I->switchToContentFrame();
        $I->see(self::$widgetTitle, self::$widgetTitleSelector);
    }

    /**
     * @depends createCustomDashboardAndWidgets
     */
    public function deleteDashboardAndWidgets(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        // Delete widget
        $I->click(self::$customDashboardTitle, '.dashboard-tabs');
        $I->see('Type of backend users', self::$widgetTitleSelector);
        $I->click('.widget-action-remove');
        $modalDialog->canSeeDialog();
        $I->click('button[name="delete"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->switchToContentFrame();
        $I->seeElement('.dashboard-empty-content');

        // Delete custom dashboard
        $I->click('button[title="Delete dashboard"]');
        $modalDialog->canSeeDialog();
        $I->click('button[name="delete"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->dontSee(self::$customDashboardTitle, self::$dashboardActiveSelector);
    }
}
