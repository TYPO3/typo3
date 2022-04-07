<?php

declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\FileList;

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

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\FileTree;

class FileStorageTreeFilterCest
{
    protected string $openPath = '#typo3-filestoragetree .nodes > .node:first-child .node-toggle';
    protected string $filterInputFieldClearButton = '#typo3-filestoragetree .tree-toolbar span[data-identifier=actions-close]';
    protected string $filterInputField = '#typo3-filestoragetree .tree-toolbar .search-input';
    protected string $withinTree = '#typo3-filestoragetree .nodes';
    protected string $newSubfolder = 'random_subfolder';

    public function _before(ApplicationTester $I, FileTree $tree): void
    {
        // Remove folder in case it already exists, to make sure we have a clean state
        @unlink(__DIR__ . '/../../../../../../typo3temp/var/tests/acceptance/fileadmin/random_subfolder');
        $I->useExistingSession('admin');
        $I->click('Filelist');
        $I->waitForElement('svg .nodes .node');

        // @todo extend testing-frameworks AbstractPageTree.php:openPath to make it usable with the file tree.
        $I->click($this->openPath);
        $I->waitForElement($this->withinTree . ' .node', 5);

        $this->createNewFolder($I);
    }

    public function filterTreeForFolder(ApplicationTester $I): void
    {
        $I->fillField($this->filterInputField, 'styleguide');
        $this->timeoutForAjaxRequest($I);

        $I->amGoingTo('prove filter reset button is visible upon input');

        $I->cantSee($this->newSubfolder, $this->withinTree);
        $I->canSee('styleguide', $this->withinTree);

        $this->reloadTree($I);
        $this->timeoutForAjaxRequest($I);

        // filter must still apply after tree reload
        $I->amGoingTo('prove the filter applies after tree reload');
        $I->cantSee($this->newSubfolder, $this->withinTree);
        $I->seeInField($this->filterInputField, 'styleguide');
    }

    /**
     * @todo: Method protected! This means the test is disabled.
     */
    protected function clearFilterReloadsTreeWithoutFilterApplied(ApplicationTester $I): void
    {
        $I->fillField($this->filterInputField, 'styleguide');
        $this->timeoutForAjaxRequest($I);

        $I->canSee('styleguide', $this->withinTree);
        $I->cantSee($this->newSubfolder, $this->withinTree);

        $I->click($this->filterInputFieldClearButton);
        $this->timeoutForAjaxRequest($I);

        $I->canSee('styleguide', $this->withinTree);
        $I->canSee($this->newSubfolder, $this->withinTree);
    }

    protected function clearTreeFilters(ApplicationTester $I): void
    {
        $I->click($this->filterInputFieldClearButton);
        $this->reloadTree($I);
        $I->cantSeeElement($this->filterInputFieldClearButton);
    }

    protected function timeoutForAjaxRequest(ApplicationTester $I): void
    {
        $I->wait(0.5);
    }

    protected function createNewFolder(ApplicationTester $I): void
    {
        // Create a new folder
        $I->switchToContentFrame();
        $addButtonLink = '.btn-toolbar [title="New"]';
        $I->waitForElement($addButtonLink, 30);
        $I->click($addButtonLink);
        $I->wait(5);
        $I->fillField('#folder_new_0', $this->newSubfolder);
        $I->click('form[name="editform"] input[type="submit"]');
        $I->wait(5);
        $I->switchToMainFrame();
    }

    protected function reloadTree($I)
    {
        $I->click('#typo3-filestoragetree .svg-toolbar__menuitem');
        $I->click('#typo3-filestoragetree .dropdown-item [identifier="actions-refresh"]');
    }
}
