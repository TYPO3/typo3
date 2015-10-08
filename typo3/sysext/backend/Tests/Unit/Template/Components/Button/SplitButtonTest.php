<?php
namespace TYPO3\CMS\Backend\Tests\Template\Components\Buttons;

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

use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\SplitButton;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class BackendModuleRequestHandlerTest
 */
class SplitButtonTest extends UnitTestCase
{
    /**
     * Try to validate an empty button
     *
     * @test
     * @return void
     */
    public function isButtonValidBlankCallExpectFalse()
    {
        $button = new SplitButton();
        $isValid = $button->isValid();
        $this->assertFalse($isValid);
    }

    /**
     * Try adding an invalid button to a splitButton
     *
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1441706330
     * @return void
     */
    public function isButtonValidInvalidButtonGivenExpectFalse()
    {
        $button = new SplitButton();

        $primaryAction = new LinkButton();
        $button->addItem($primaryAction);

        $isValid = $button->isValid();
        $this->assertFalse($isValid);
    }

    /**
     * Try to add multiple primary actions
     *
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1441706340
     * @return void
     */
    public function isButtonValidBrokenSetupMultiplePrimaryActionsGivenExpectFalse()
    {
        $button = new SplitButton();

        $primaryAction = new LinkButton();
        $icon = new Icon();
        $primaryAction->setTitle('husel')->setHref('husel')->setIcon($icon);
        $button->addItem($primaryAction, true);

        $anotherPrimaryAction = new LinkButton();
        $anotherPrimaryAction->setTitle('husel')->setHref('husel')->setIcon($icon);
        $button->addItem($anotherPrimaryAction, true);

        $isValid = $button->isValid();
        $this->assertFalse($isValid);
    }

    /**
     * Try to add an invalid button as second parameter
     *
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1441706330
     * @return void
     */
    public function isButtonValidBrokenSetupInvalidButtonAsSecondParametersGivenExpectFalse()
    {
        $button = new SplitButton();

        $primaryAction = new LinkButton();
        $icon = new Icon();
        $primaryAction->setTitle('husel')->setHref('husel')->setIcon($icon);
        $button->addItem($primaryAction, true);

        $anotherPrimaryAction = new LinkButton();
        $anotherPrimaryAction->setTitle('husel')->setHref('husel');
        $button->addItem($anotherPrimaryAction, true);

        $isValid = $button->isValid();
        $this->assertFalse($isValid);
    }

    /**
     * Send in a valid button
     *
     * @test
     * @return void
     */
    public function isButtonValidValidSetupExpectTrue()
    {
        $button = new SplitButton();

        $primaryAction = new LinkButton();
        $icon = new Icon();
        $primaryAction->setTitle('husel')->setHref('husel')->setIcon($icon);
        $button->addItem($primaryAction, true);

        $anotherAction = new LinkButton();
        $anotherAction->setTitle('husel')->setHref('husel')->setIcon($icon);
        $button->addItem($anotherAction);

        $isValid = $button->isValid();
        $this->assertTrue($isValid);
    }
}
