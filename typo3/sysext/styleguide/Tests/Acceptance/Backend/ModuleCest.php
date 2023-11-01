<?php

declare(strict_types=1);

namespace TYPO3\CMS\Styleguide\Tests\Acceptance\Backend;

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

use TYPO3\CMS\Styleguide\Tests\Acceptance\Support\BackendTester;

/**
 * Tests the styleguide backend module can be loaded
 */
class ModuleCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin', 1);
        $I->canSee('Styleguide');
        $I->click('Styleguide');
        $I->switchToContentFrame();
    }

    /**
     * @param BackendTester $I
     */
    public function styleguideInTopbarHelpCanBeCalled(BackendTester $I): void
    {
        $I->see('TYPO3 CMS Backend Styleguide', 'h1');
    }

    /**
     * @depends styleguideInTopbarHelpCanBeCalled
     * @param BackendTester $I
     */
    public function creatingTcaDemoDataWorks(BackendTester $I): void
    {
        $I->click('Index');
        $I->waitForText('Page tree including TCA demo records');
        $I->click('#t3-tca-pagetree-create');
        $this->seeResponse($I, 'A page tree with TCA demo records was created.');
    }

    /**
     * @depends creatingTcaDemoDataWorks
     * @param BackendTester $I
     */
    public function deletingTcaDemoDataWorks(BackendTester $I): void
    {
        $I->click('Index');
        $I->waitForText('Page tree including TCA demo records');
        $I->click('#t3-tca-pagetree-delete');
        $this->seeResponse($I, 'The page tree and all related records were deleted.');
    }

    /**
     * @depends styleguideInTopbarHelpCanBeCalled
     * @param BackendTester $I
     */
    public function creatingFrontendDemoDataWorks(BackendTester $I): void
    {
        $I->click('Index');
        $I->waitForText('Page tree including content elements');
        $I->click('#t3-ce-pagetree-create');
        $this->seeResponse($I, 'A page tree with content elements was created.');
    }

    /**
     * @depends creatingTcaDemoDataWorks
     * @param BackendTester $I
     */
    public function deletingFrontendDemoDataWorks(BackendTester $I): void
    {
        $I->click('Index');
        $I->waitForText('Page tree including content elements');
        $I->click('#t3-ce-pagetree-delete');
        $this->seeResponse($I, 'The page tree and all related records were deleted.');
    }

    private function seeResponse(BackendTester $I, string $message): void
    {
        $I->seeElement('.t3js-generator-action .icon-spinner-circle');
        $I->switchToMainFrame();
        $I->waitForText($message, 60, '.alert-message');
        $I->switchToContentFrame();
        $I->dontSeeElement('.t3js-generator-action .icon-spinner-circle');
    }
}
