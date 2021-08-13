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
    protected string $filterInputFieldClearButton = '#typo3-filestoragetree .tree-toolbar span[data-identifier=actions-close]';
    protected string $filterInputField = '#typo3-filestoragetree .tree-toolbar .search-input';
    protected string $reloadButton = '#typo3-filestoragetree .tree-toolbar button[data-tree-icon=actions-refresh]';
    protected string $withinTree = '#typo3-filestoragetree .nodes';
    protected string $newSubfolder = 'random_subfolder';

    public function _before(ApplicationTester $I, FileTree $tree)
    {
        $I->useExistingSession('admin');
        $I->click('Filelist');

        $tree->openPath(['fileadmin']);
        $I->waitForElement($this->withinTree . ' .node', 5);

        $this->createNewFolder($I);
    }

    public function filterTreeForFolder(ApplicationTester $I)
    {
        $I->cantSeeElement($this->filterInputFieldClearButton);

        $I->fillField($this->filterInputField, 'styleguide');
        $this->waitForAjaxRequestToFinish($I);

        $I->amGoingTo('prove filter reset button is visible upon input');
        $I->canSeeElement($this->filterInputFieldClearButton);

        $I->cantSee($this->newSubfolder, $this->withinTree);
        $I->canSee('styleguide', $this->withinTree);

        $I->click($this->reloadButton);
        $this->waitForAjaxRequestToFinish($I);

        // filter must still apply after tree reload
        $I->amGoingTo('prove the filter applies after tree reload');
        $I->cantSee($this->newSubfolder, $this->withinTree);
        $I->seeInField($this->filterInputField, 'styleguide');
    }

    public function clearFilterReloadsTreeWithoutFilterApplied(ApplicationTester $I)
    {
        $I->fillField($this->filterInputField, 'styleguide');
        $this->waitForAjaxRequestToFinish($I);

        $I->canSee('styleguide', $this->withinTree);
        $I->cantSee($this->newSubfolder, $this->withinTree);

        $I->click($this->filterInputFieldClearButton);
        $this->waitForAjaxRequestToFinish($I);

        $I->canSee('styleguide', $this->withinTree);
        $I->canSee($this->newSubfolder, $this->withinTree);
    }

    protected function clearTreeFilters(ApplicationTester $I): void
    {
        $I->click($this->filterInputFieldClearButton);
        $I->click($this->reloadButton);
        $I->cantSeeElement($this->filterInputFieldClearButton);
    }

    protected function waitForAjaxRequestToFinish(ApplicationTester $I): void
    {
        $I->waitForJS('return $.active == 0;', 10);
        // sometimes rendering is still slower that ajax being finished.
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
}
