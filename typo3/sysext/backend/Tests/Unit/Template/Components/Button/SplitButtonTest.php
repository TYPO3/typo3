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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\SplitButton;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SplitButtonTest extends UnitTestCase
{
    /**
     * Try to validate an empty button
     */
    #[Test]
    public function isButtonValidBlankCallExpectFalse(): void
    {
        $button = new SplitButton();
        $isValid = $button->isValid();
        self::assertFalse($isValid);
    }

    /**
     * Try adding an invalid button to a splitButton
     */
    #[Test]
    public function isButtonValidInvalidButtonGivenExpectFalse(): void
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
     */
    #[Test]
    public function isButtonValidBrokenSetupMultiplePrimaryActionsGivenExpectFalse(): void
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
     */
    #[Test]
    public function isButtonValidBrokenSetupInvalidButtonAsSecondParametersGivenExpectFalse(): void
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
     */
    #[Test]
    public function isButtonValidValidSetupExpectTrue(): void
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
