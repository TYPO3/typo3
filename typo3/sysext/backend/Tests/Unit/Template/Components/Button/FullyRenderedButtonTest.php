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

use TYPO3\CMS\Backend\Template\Components\Buttons\FullyRenderedButton;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case for FullyRenderedButton
 */
class FullyRenderedButtonTest extends UnitTestCase
{
    /**
     * Try to valide an empty button
     *
     * @test
     * @return void
     */
    public function isButtonValidBlankCallExpectFalse()
    {
        $button = new FullyRenderedButton();
        $isValid = $button->isValid();
        $this->assertFalse($isValid);
    }

    /**
     * Tests a valid HTML Button
     *
     * @test
     * @return void
     */
    public function isButtonValidHtmlSourceGivenExpectTrue()
    {
        $button = new FullyRenderedButton();
        $button->setHtmlSource('<span>Husel</span>');
        $isValid = $button->isValid();
        $this->assertTrue($isValid);
    }
}
