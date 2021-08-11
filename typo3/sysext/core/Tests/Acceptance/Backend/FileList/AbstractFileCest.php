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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\FileList;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\FileTree;

/**
 * Abstract class for file operations
 */
abstract class AbstractFileCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I, FileTree $tree)
    {
        $I->useExistingSession('admin');
        $I->amOnPage('/typo3/module/file/FilelistList');
        $I->switchToContentFrame();
    }

    /**
     * @param BackendTester $I
     * @param string $name
     * @throws \Exception
     */
    protected function uploadFile(BackendTester $I, string $name): void
    {
        $I->attachFile('input.upload-file-picker', 'Acceptance/Fixtures/Images/' . $name);
        $I->waitForElementNotVisible('.upload-queue-item .upload-queue-progress');
    }

    /**
     * @param BackendTester $I
     * @param string $title
     * @param string $action
     * @return RemoteWebElement
     */
    protected function getActionByTitle(BackendTester $I, string $title, string $action): RemoteWebElement
    {
        $I->comment('Get action in table row "' . $title . '"');
        return $I->executeInSelenium(
            function (RemoteWebDriver $webDriver) use ($title, $action) {
                return $webDriver->findElement(
                    \Facebook\WebDriver\WebDriverBy::xpath(
                        '//a[contains(text(),"' . $title . '")]/parent::node()/parent::node()//a[@data-bs-original-title="' . $action . '"]'
                    )
                );
            }
        );
    }

    /**
     * @param BackendTester $I
     * @param string $title
     * @return RemoteWebElement
     */
    protected function openActionDropdown(BackendTester $I, string $title): RemoteWebElement
    {
        $I->comment('Get open action dropdown "' . $title . '"');
        return $I->executeInSelenium(
            function (RemoteWebDriver $webDriver) use ($title) {
                return $webDriver->findElement(
                    \Facebook\WebDriver\WebDriverBy::xpath(
                        '//a[contains(text(),"' . $title . '")]/parent::node()/parent::node()//a[@data-bs-toggle="dropdown"]'
                    )
                );
            }
        );
    }
}
