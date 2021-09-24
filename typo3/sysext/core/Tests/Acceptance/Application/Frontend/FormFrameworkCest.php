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

class FormFrameworkCest
{
    protected string $sidebarSelector = '.sidebar.list-group';
    protected string $nameSelector = '[id^=simpleform] input[placeholder="Name"]';
    protected string $subjectSelector = '[id^=simpleform] input[placeholder="Subject"]';
    protected string $emailSelector = '[id^=simpleform] input[placeholder="Email address"]';
    protected string $textareaSelector = '[id^=simpleform] textarea';
    protected string $submitSelector = '[id^=simpleform] button[type=submit]';
    protected string $summaryValueSelector = '[id^=simpleform] table td:not(.summary-table-first-col)';

    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('Page');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
        $pageTree->openPath(['styleguide frontend demo']);
        $I->switchToContentFrame();
        $I->click('.t3js-module-docheader-bar a[title="View webpage"]');
        $I->executeInSelenium(static function (RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            $lastWindow = end($handles);
            $webdriver->switchTo()->window($lastWindow);
        });

        $I->scrollTo('//a[contains(., "form_formframework")]');
        $I->click('form_formframework', $this->sidebarSelector);
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
