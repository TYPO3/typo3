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

use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for InputButton
 */
class InputButtonTest extends UnitTestCase
{
    /**
     * Try to validate an empty button
     *
     * @test
     */
    public function isButtonValidBlankCallExpectFalse()
    {
        $button = new InputButton();
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Omit the Icon
     *
     * @test
     */
    public function isButtonValidOmittedIconExpectFalse()
    {
        $button = new InputButton();
        $button->setName('husel')->setValue('1')->setTitle('huhu');
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Omit the title
     *
     * @test
     */
    public function isButtonValidOmittedTitleExpectFalse()
    {
        $button = new InputButton();
        $icon = new Icon();
        $button->setName('husel')->setValue('1')->setIcon($icon);
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Omit the name
     *
     * @test
     */
    public function isButtonValidOmittedNameExpectFalse()
    {
        $button = new InputButton();
        $icon = new Icon();
        $button->setTitle('husel')->setValue('1')->setIcon($icon);
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Omit the Value
     *
     * @test
     */
    public function isButtonValidOmittedValueExpectFalse()
    {
        $button = new InputButton();
        $icon = new Icon();
        $button->setTitle('husel')->setName('husel')->setIcon($icon);
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Set a 100% valid button
     *
     * @test
     */
    public function isButtonValidAllValuesSetExpectTrue()
    {
        $button = new InputButton();
        $icon = new Icon();
        $button->setTitle('husel')->setName('husel')->setIcon($icon)->setValue('1');
        $isValid = $button->isValid();
        self::assertTrue($isValid);
    }
}
