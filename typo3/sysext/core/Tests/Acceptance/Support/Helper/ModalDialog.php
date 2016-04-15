<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Helper;

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

use AcceptanceTester;

/**
 * Helper to interact with modal dialogs that appear for example when
 * you delete a record or have to confirm something.
 *
 *  --------------------------------
 * | Would you like to continue?    |
 * |                                |
 * |            [no] [maybe] [yeah] |
 *  --------------------------------
 */
class ModalDialog
{
    /**
     * Selector for a visible modal window
     *
     * @var string
     */
    public static $openedModalSelector = '.t3-modal.in';

    /**
     * Selector for the container in the modal where the buttons are located
     *
     * @var string
     */
    public static $openedModalButtonContainerSelector = '.t3-modal.in .modal-footer';

    /**
     * @var AcceptanceTester
     */
    protected $tester;

    /**
     * @param AcceptanceTester $I
     */
    public function __construct(\AcceptanceTester $I)
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
    }
}
