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

namespace TYPO3\CMS\Backend\Tests\Unit\Template\Components\Button;

use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for LinkButton
 */
class LinkButtonTest extends UnitTestCase
{
    /**
     * Try validating an empty button
     *
     * @test
     */
    public function isButtonValidBlankCallExpectFalse(): void
    {
        $button = new LinkButton();
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Omit the Icon
     *
     * @test
     */
    public function isButtonValidOmittedIconExpectFalse(): void
    {
        $button = new LinkButton();
        $button->setHref('#')->setTitle('huhu');
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Omit the title
     *
     * @test
     */
    public function isButtonValidOmittedTitleExpectFalse(): void
    {
        $button = new LinkButton();
        $icon = new Icon();
        $button->setHref('husel')->setIcon($icon);
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Omit Href
     *
     * @test
     */
    public function isButtonValidOmittedHrefExpectFalse(): void
    {
        $button = new LinkButton();
        $icon = new Icon();
        $button->setTitle('husel')->setIcon($icon);
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Send a valid button
     *
     * @test
     */
    public function isButtonValidAllValuesSetExpectTrue(): void
    {
        $button = new LinkButton();
        $icon = new Icon();
        $button->setTitle('husel')->setHref('husel')->setIcon($icon);
        $isValid = $button->isValid();
        self::assertTrue($isValid);
    }
}
