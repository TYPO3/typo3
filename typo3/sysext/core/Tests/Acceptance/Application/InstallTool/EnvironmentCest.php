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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\InstallTool;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

class EnvironmentCest extends AbstractCest
{
    public function _before(ApplicationTester $I)
    {
        parent::_before($I);
        $this->logIntoInstallTool($I);
        $I->click('Environment');
        $I->see('Environment', 'h1');
    }

    /**
     * @param ApplicationTester $I
     * @throws \Exception
     */
    public function imageProcessingWorks(ApplicationTester $I)
    {
        $I->click('Test Images');
        $I->waitForElementVisible('.modal-dialog');
        // This is odd: The 'close' X upper right does not immediately work, even though waitForElementVisible below
        // reports 'is ok' quickly. Worth a look of some JS jockey? We give the system some time to accumulate.
        $I->wait(1);
        $I->waitForElementVisible('.t3js-modal-close');
        $I->click('.t3js-modal-close');
        $I->waitForElementNotVisible('.modal-dialog');
    }
}
