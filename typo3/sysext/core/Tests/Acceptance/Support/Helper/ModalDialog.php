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

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\TestingFramework\Core\Acceptance\Helper\AbstractModalDialog;

/**
 * @see AbstractModalDialog
 */
class ModalDialog extends AbstractModalDialog
{
    /**
     * Selector for a visible modal window
     * Adapted for Boostrap 5
     *
     * @var string
     */
    public static $openedModalSelector = '.modal.show';

    /**
     * Selector for the container in the modal where the buttons are located
     * Adapted for Boostrap 5
     *
     * @var string
     */
    public static $openedModalButtonContainerSelector = '.modal.show .modal-footer';

    /**
     * @var AcceptanceTester
     */
    protected $tester;

    /**
     * Inject our core AcceptanceTester actor into ModalDialog
     *
     * @param ApplicationTester $I
     */
    public function __construct(ApplicationTester $I)
    {
        $this->tester = $I;
    }

    /**
     * Perform a click on a link or a button, given by a locator.
     *
     * @param string $buttonLinkLocator the button title
     * @see \Codeception\Module\WebDriver::click()
     */
    public function clickButtonInDialog(string $buttonLinkLocator)
    {
        $I = $this->tester;
        $this->canSeeDialog();
        $I->click($buttonLinkLocator, self::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(self::$openedModalSelector);
    }

    /**
     * Check if modal dialog is visible in top frame
     */
    public function canSeeDialog()
    {
        $I = $this->tester;
        $I->switchToIFrame();
        $I->waitForElement(self::$openedModalSelector);
        // I will wait two seconds to prevent failing tests
        $I->wait(2);
    }
}
