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

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

final class EnvironmentCest extends AbstractCest
{
    public function _before(ApplicationTester $I): void
    {
        parent::_before($I);
        $this->logIntoInstallTool($I);
        $I->click('Environment');
        $I->see('Environment', 'h1');
    }

    /**
     * @return string[][]
     */
    private function cardsDataProvider(): array
    {
        return [
            ['title' => 'Environment Overview', 'button' => 'Show System Information…', 'seeInModal' => 'Operating system'],
            ['title' => 'Environment Status', 'button' => 'Check Environment…', 'seeInModal' => 'File uploads allowed in PHP'],
            ['title' => 'Directory Status', 'button' => 'Check Environment…', 'seeInModal' => 'PHP version is fine'],
            ['title' => 'PHP Info', 'button' => 'View PHP Info…', 'seeInModal' => 'PHP Version'],
            ['title' => 'Test Mail Setup', 'button' => 'Test Mail Setup…', 'seeInModal' => 'Check the basic mail functionality by entering your email address here and clicking the button.'],
        ];
    }

    #[DataProvider('cardsDataProvider')]
    public function seeCardsAndModals(ApplicationTester $I, ModalDialog $modalDialog, Example $testData): void
    {
        $I->see($testData['title']);
        $I->click($testData['button']);
        $modalDialog->canSeeDialog();
        $I->see($testData['seeInModal'], ModalDialog::$openedModalSelector);
    }

    public function imageProcessingWorks(ApplicationTester $I): void
    {
        $I->click('Test Images');
        $I->waitForElementVisible('.modal-dialog');
        // This is odd: The 'close' X upper right does not immediately work, even though waitForElementVisible below
        // reports 'is ok' quickly. Worth a look of some JS jockey? We give the system some time to accumulate.
        $I->wait(1);
        $I->waitForElementVisible('.t3js-modal-close');
    }
}
