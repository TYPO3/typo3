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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Frontend;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

final class FormFrameworkCest
{
    private string $sidebarSelector = '.sidebar.list-group';
    private string $nameSelector = '[id^=simpleform] input[placeholder="Name"]';
    private string $subjectSelector = '[id^=simpleform] input[placeholder="Subject"]';
    private string $emailSelector = '[id^=simpleform] input[placeholder="Email address"]';
    private string $textareaSelector = '[id^=simpleform] textarea';
    private string $submitSelector = '[id^=simpleform] button[type=submit]:not([formnovalidate])';
    private string $summaryValueSelector = '[id^=simpleform] .summary-list dd';

    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('Page');
        $pageTree->openPath(['styleguide frontend demo']);
        $I->switchToContentFrame();
        $I->waitForElementVisible('select[name=actionMenu]');
        $I->selectOption('select[name=actionMenu]', 'Layout');
        $I->waitForElementVisible('.t3js-module-docheader-bar a[title="View webpage"]');
        $I->wait(1);
        $I->click('.t3js-module-docheader-bar a[title="View webpage"]');
        $I->wait(1);
        $I->executeInSelenium(static function (RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            $lastWindow = end($handles);
            $webdriver->switchTo()->window($lastWindow);
        });
        $I->wait(1);
        $I->see('TYPO3 Styleguide Frontend', '.content');
        $I->scrollTo('//a[contains(., "form_formframework")]');
        $I->click('form_formframework', $this->sidebarSelector);
    }

    public function _after(ApplicationTester $I): void
    {
        // Close FE tab again and switch to BE to avoid side effects
        $I->executeInSelenium(static function (RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            // Avoid closing the main backend tab (holds the webdriver session) if the test failed to open the frontend tab
            // (All subsequent tests would fail with "[Facebook\WebDriver\Exception\InvalidSessionIdException] invalid session id"
            if (count($handles) > 1) {
                $webdriver->close();
                $firstWindow = current($handles);
                $webdriver->switchTo()->window($firstWindow);
            }
        });
    }

    public function sentInvalidForm(ApplicationTester $I): void
    {
        $mandatory = 'This field is mandatory.';
        $mandatoryEmail = 'You must enter a valid email address.';

        $I->fillField($this->emailSelector, 'invalid mail');
        $I->click($this->submitSelector);
        $I->see($mandatory, $this->nameSelector . ' + span');
        $I->see($mandatory, $this->subjectSelector . ' + span');
        $I->see($mandatoryEmail, $this->emailSelector . ' + span');
        $I->see($mandatory, $this->textareaSelector . ' + span');
    }

    public function sentValidForm(ApplicationTester $I): void
    {
        $name = 'Jane Doe';
        $subject = 'Welcome to TYPO3';
        $email = 'jane.doe@example.org';
        $message = 'Happy to have you!';

        $I->fillField($this->nameSelector, $name);
        $I->fillField($this->subjectSelector, $subject);
        $I->fillField($this->emailSelector, $email);
        $I->fillField($this->textareaSelector, $message);

        $I->click($this->submitSelector);
        $I->see($name, $this->summaryValueSelector);
        $I->see($subject, $this->summaryValueSelector);
        $I->see($email, $this->summaryValueSelector);
        $I->see($email, $this->summaryValueSelector);

        $I->click($this->submitSelector);
        $I->see('E-Mail sent', '[id^=simpleform]');
    }
}
