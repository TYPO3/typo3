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

namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Helper;

use Facebook\WebDriver\Interactions\Internal\WebDriverButtonReleaseAction;
use Facebook\WebDriver\Interactions\Internal\WebDriverClickAndHoldAction;
use Facebook\WebDriver\Interactions\Internal\WebDriverMouseMoveAction;
use Facebook\WebDriver\Interactions\WebDriverCompositeAction;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverMouse;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Several mouse actions for Backend usage.
 */
class Mouse
{
    protected BackendTester $tester;

    public function __construct(BackendTester $I)
    {
        $this->tester = $I;
    }

    /**
     * Perform drag and drop from source to target destination.
     *
     * Performs a normal "drag nd drop" operation from the given source element to the
     * given target destination. Additionally, checks whether the current drag & drop
     * node is displayed on the page.
     *
     * [!] Note that only CSS selectors are valid for both $source and $target.
     *
     * @param string $source Drag source, must be a valid CSS selector
     * @param string $target Drop target, must be a valid CSS selector
     */
    public function dragAndDrop(string $source, string $target): void
    {
        $I = $this->tester;
        $this->dragTo($source, $target, false);
        $I->canSeeElement('.node-dd');
        $this->release();
    }

    /**
     * Drag source element to target destination.
     *
     * Uses the mouse to drag the given source element to the given target destination.
     * If $release is set to `true`, the mouse is released afterwards. In this case, a
     * normal "drag and drop" action is performed.
     *
     * [!] Note that only CSS selectors are valid for both $source and $target.
     *
     * @param string $source Drag source, must be a valid CSS selector
     * @param string $target Drag target, must be a valid CSS selector
     * @param bool $release `true` if mouse should be released (default), `false` otherwise
     */
    public function dragTo(string $source, string $target, bool $release = true): void
    {
        (new WebDriverCompositeAction())
            ->addAction(
                new WebDriverClickAndHoldAction($this->getMouse(), $this->findElement($source))
            )
            ->addAction(
                new WebDriverMouseMoveAction($this->getMouse(), $this->findElement($target))
            )
            ->perform();
        if ($release) {
            $this->release();
        }
    }

    /**
     * Release mouse at current position.
     */
    public function release(): void
    {
        $action = new WebDriverButtonReleaseAction($this->getMouse());
        $action->perform();
    }

    protected function findElement(string $cssSelector): RemoteWebElement
    {
        $I = $this->tester;
        return $I->executeInSelenium(function (RemoteWebDriver $webDriver) use ($cssSelector) {
            return $webDriver->findElement(WebDriverBy::cssSelector($cssSelector));
        });
    }

    protected function getMouse(): WebDriverMouse
    {
        $I = $this->tester;
        return $I->executeInSelenium(function (RemoteWebDriver $webDriver) {
            return $webDriver->getMouse();
        });
    }
}
