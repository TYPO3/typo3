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

/**
 * Tests the styleguide backend module can be loaded
 */
class GenerateCommandCest
{
    protected string $command = '../../../../../bin/typo3 styleguide:generate ';

    /**
     * @param \TYPO3\CMS\Styleguide\Tests\Acceptance\Support\BackendTester $I
     */
    public function runTcaCreateAndDelete(\TYPO3\CMS\Styleguide\Tests\Acceptance\Support\BackendTester $I): void
    {
        $I->amGoingTo('create the TCA page tree');
        $I->runShellCommand($this->command . 'tca --create');
        $I->seeInShellOutput('TCA page tree created!');

        $I->amGoingTo('create the TCA page tree while it already exists');
        $I->runShellCommand($this->command . 'tca --create');
        $I->seeInShellOutput('TCA page tree already exists!');

        $I->amGoingTo('delete the TCA page tree');
        $I->runShellCommand($this->command . 'tca --delete');
        $I->seeInShellOutput('TCA page tree deleted!');
    }

    /**
     * @param \TYPO3\CMS\Styleguide\Tests\Acceptance\Support\BackendTester $I
     */
    public function runFrontendCreateAndDelete(\TYPO3\CMS\Styleguide\Tests\Acceptance\Support\BackendTester $I): void
    {
        $I->amGoingTo('create the frontend page tree');
        $I->runShellCommand($this->command . 'frontend --create');
        $I->seeInShellOutput('Frontend page tree created!');

        $I->amGoingTo('create the frontend page tree while it already exists');
        $I->runShellCommand($this->command . 'frontend --create');
        $I->seeInShellOutput('Frontend page tree already exists!');

        $I->amGoingTo('delete the frontend page tree');
        $I->runShellCommand($this->command . 'frontend --delete');
        $I->seeInShellOutput('Frontend page tree deleted!');
    }

    /**
     * @param \TYPO3\CMS\Styleguide\Tests\Acceptance\Support\BackendTester $I
     * @depends runFrontendCreateAndDelete
     * @depends runTcaCreateAndDelete
     */
    public function runAllGeneratorsCreateAndDelete(\TYPO3\CMS\Styleguide\Tests\Acceptance\Support\BackendTester $I): void
    {
        $I->amGoingTo('create the all page trees');
        $I->runShellCommand($this->command . 'all --create');
        $I->seeInShellOutput('TCA page tree created!');
        $I->seeInShellOutput('Frontend page tree created!');

        $I->amGoingTo('create the all page trees while they already exists');
        $I->runShellCommand($this->command . 'all --create');
        $I->seeInShellOutput('TCA page tree already exists!');
        $I->seeInShellOutput('Frontend page tree already exists!');

        $I->amGoingTo('delete the all demo page trees');
        $I->runShellCommand($this->command . 'all --delete');
        $I->seeInShellOutput('TCA page tree deleted!');
        $I->seeInShellOutput('Frontend page tree deleted!');
    }
}
