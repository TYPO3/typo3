<?php

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

namespace TYPO3\CMS\Backend\Tests\Unit\Template\Components\Button;

use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\SplitButton;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class BackendModuleRequestHandlerTest
 */
class SplitButtonTest extends UnitTestCase
{
    /**
     * Try to validate an empty button
     *
     * @test
     */
    public function isButtonValidBlankCallExpectFalse()
    {
        $button = new SplitButton();
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Try adding an invalid button to a splitButton
     *
     * @test
     */
    public function isButtonValidInvalidButtonGivenExpectFalse()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1441706330);
        $button = new SplitButton();

        $primaryAction = new LinkButton();
        $button->addItem($primaryAction);

        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Try to add multiple primary actions
     *
     * @test
     */
    public function isButtonValidBrokenSetupMultiplePrimaryActionsGivenExpectFalse()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1441706340);
        $button = new SplitButton();

        $primaryAction = new LinkButton();
        $icon = new Icon();
        $primaryAction->setTitle('husel')->setHref('husel')->setIcon($icon);
        $button->addItem($primaryAction, true);

        $anotherPrimaryAction = new LinkButton();
        $anotherPrimaryAction->setTitle('husel')->setHref('husel')->setIcon($icon);
        $button->addItem($anotherPrimaryAction, true);

        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Try to add an invalid button as second parameter
     *
     * @test
     */
    public function isButtonValidBrokenSetupInvalidButtonAsSecondParametersGivenExpectFalse()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1441706330);
        $button = new SplitButton();

        $primaryAction = new LinkButton();
        $icon = new Icon();
        $primaryAction->setTitle('husel')->setHref('husel')->setIcon($icon);
        $button->addItem($primaryAction, true);

        $anotherPrimaryAction = new LinkButton();
        $anotherPrimaryAction->setTitle('husel')->setHref('husel');
        $button->addItem($anotherPrimaryAction, true);

        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Send in a valid button
     *
     * @test
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
        self::assertTrue($isValid);
    }
}
