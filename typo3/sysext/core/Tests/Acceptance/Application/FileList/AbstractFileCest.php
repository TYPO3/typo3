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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\FileList;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\FileTree;

/**
 * Abstract class for file operations
 */
abstract class AbstractFileCest
{
    public function _before(ApplicationTester $I, FileTree $tree): void
    {
        $I->useExistingSession('admin');
        $I->amOnPage('/typo3/module/file/list');
        $I->switchToContentFrame();
    }

    /**
     * @throws \Exception
     */
    protected function uploadFile(ApplicationTester $I, string $name): void
    {
        $I->attachFile('input.upload-file-picker', 'Acceptance/Fixtures/Images/' . $name);
        $I->waitForElementNotVisible('.upload-queue-item .upload-queue-progress');
    }

    protected function openActionDropdown(ApplicationTester $I, string $title): RemoteWebElement
    {
        $I->comment('Get open action dropdown "' . $title . '"');
        return $I->executeInSelenium(
            static function (RemoteWebDriver $webDriver) use ($title) {
                return $webDriver->findElement(
                    \Facebook\WebDriver\WebDriverBy::xpath(
                        '//a[contains(text(),"' . $title . '")]/parent::node()/parent::node()//a[contains(@href, "#actions_") and contains(@data-bs-toggle, "dropdown")]'
                    )
                );
            }
        );
    }
}
