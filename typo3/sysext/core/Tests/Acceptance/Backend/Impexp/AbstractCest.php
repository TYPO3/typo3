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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Impexp;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Common test helper functions
 *
 * @todo: only used once, inline to consuming class?
 */
abstract class AbstractCest
{
    protected function setPageAccess(BackendTester $I, PageTree $pageTree, array $pagePath, int $userGroupId, int $recursionLevel = 1): void
    {
        $I->switchToMainFrame();
        $I->click('Access');
        $I->waitForElement($this->inPageTree . ' .node', 5);
        $pageTree->openPath($pagePath);
        $I->switchToContentFrame();
        $I->click('//table[@id="typo3-permissionList"]/tbody/tr[1]/td[2]/a[@title="Change permissions"]');
        $I->waitForElementVisible('#PermissionControllerEdit');
        $I->selectOption('//select[@id="selectGroup"]', ['value' => $userGroupId]);
        $recursionLevelOption = $I->grabTextFrom('//select[@id="recursionLevel"]/option[' . $recursionLevel . ']');
        $I->selectOption('//select[@id="recursionLevel"]', ['value' => $recursionLevelOption]);
        $I->click($this->inModuleHeader . ' .btn[title="Save and close"]');
    }

    protected function setModAccess(BackendTester $I, int $userGroupId, array $modAccessByName): void
    {
        try {
            $I->seeElement($this->inModuleHeader . ' [name=BackendUserModuleMenu]');
        } catch (\Exception $e) {
            $I->switchToMainFrame();
            $I->click('Backend Users');
            $I->switchToContentFrame();
        }

        $I->selectOption($this->inModuleHeader . ' [name=BackendUserModuleMenu]', ['text'=>'Backend user groups']);
        $I->waitForText('Backend User Group Listing');
        $I->makeScreenshot('setModAccess');
        $I->click('//table/tbody/tr[descendant::a[@data-uid="' . $userGroupId . '"]]/td[2]/a');
        $I->waitForElementVisible('#EditDocumentController');
        $I->click('//form[@id="EditDocumentController"]//ul/li[2]/a');

        foreach ($modAccessByName as $modName => $modAccess) {
            if ((bool)$modAccess) {
                $I->checkOption('//input[@value="' . $modName . '"]');
            } else {
                $I->uncheckOption('//input[@value="' . $modName . '"]');
            }
        }

        $I->click($this->inModuleHeader . ' .btn[title="Save"]');
        $I->wait(0.5);
        $I->click($this->inModuleHeader . ' .btn[title="Close"]');
        $I->waitForText('Backend User Group Listing');
    }

    protected function setUserTsConfig(BackendTester $I, int $userId, string $userTsConfig): void
    {
        try {
            $I->seeElement($this->inModuleHeader . ' [name=BackendUserModuleMenu]');
        } catch (\Exception $e) {
            $I->switchToMainFrame();
            $I->click('Backend Users');
            $I->switchToContentFrame();
        }

        $I->selectOption($this->inModuleHeader . ' [name=BackendUserModuleMenu]', ['text'=>'Backend users']);
        $I->waitForElement('#typo3-backend-user-list');
        $I->click('//table[@id="typo3-backend-user-list"]/tbody/tr[descendant::a[@data-uid="' . $userId . '"]]//a[@title="Edit"]');
        $I->waitForElement('#EditDocumentController');
        $I->click('//form[@id="EditDocumentController"]//ul/li[4]/a');
        $I->fillField('//div[@class="tab-content"]/div[4]/fieldset[1]//textarea', $userTsConfig);
        $I->click($this->inModuleHeader . ' .btn[title="Save"]');
        $I->wait(0.5);
        $I->click($this->inModuleHeader . ' .btn[title="Close"]');
        $I->waitForElement('#typo3-backend-user-list');
    }
}
