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

use TYPO3\CMS\Backend\Template\Components\Buttons\FullyRenderedButton;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for FullyRenderedButton
 */
class FullyRenderedButtonTest extends UnitTestCase
{
    /**
     * Try to validate an empty button
     *
     * @test
     */
    public function isButtonValidBlankCallExpectFalse()
    {
        $button = new FullyRenderedButton();
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Tests a valid HTML Button
     *
     * @test
     */
    public function isButtonValidHtmlSourceGivenExpectTrue()
    {
        $button = new FullyRenderedButton();
        $button->setHtmlSource('<span>Husel</span>');
        $isValid = $button->isValid();
        self::assertTrue($isValid);
    }
}
