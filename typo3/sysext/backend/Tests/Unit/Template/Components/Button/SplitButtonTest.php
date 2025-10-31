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
use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\SplitButton;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\IconState;
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

    #[Test]
    public function splitButtonCanUseLinkButtonInsideDropdown(): void
    {
        $icon = new Icon();
        $icon->setIdentifier('actions-document-save');
        $icon->setSize(IconSize::SMALL);
        $icon->setState(IconState::STATE_DEFAULT);
        $icon->setMarkup('[actions-document-save]');

        $saveButton = (new InputButton())
            ->setName('inputbutton-save')
            ->setValue('inputbutton-value')
            ->setIcon($icon)
            ->setTitle('inputbutton-title');

        $someLinkButton = (new LinkButton())
            ->setHref('/some/link')
            ->setDataAttributes(['customkey' => 'customval'])
            ->setShowLabelText(true)
            ->setTitle('linkbutton-title')
            ->setIcon($icon);

        $splitButtonElement = (new SplitButton())
            ->addItem($saveButton, true)
            ->addItem($someLinkButton);

        $html = $splitButtonElement->render();
        // Assertions checks for:
        // - matching href
        // - matching data attributes
        // - matching shown label
        // - matching title
        // - matching icon
        self::assertMatchesRegularExpression('@'
            . '<ul class="dropdown-menu">'
            . '\s+<li>'
            . '\s+<a role="button" href="/some/link" class="btn-sm btn-default dropdown-item " title="linkbutton-title" data-customkey="customval">'
            . '<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-document-save" data-identifier="actions-document-save" aria-hidden="true">'
            . '\s+<span class="icon-markup">\s+\[actions-document-save\]\s+</span>'
            . '\s+</span> linkbutton-title</a>'
            . '@imsU', $html);
    }

    #[Test]
    public function splitButtonCanUsePrimaryLinkButton(): void
    {
        // Test like splitButtonCanUseLinkButtonInsideDropdown, but
        // using LinkButton as the primary button
        $icon = new Icon();
        $icon->setIdentifier('actions-document-save');
        $icon->setSize(IconSize::SMALL);
        $icon->setState(IconState::STATE_DEFAULT);
        $icon->setMarkup('[actions-document-save]');

        $saveButton = (new InputButton())
            ->setName('inputbutton-save')
            ->setValue('inputbutton-value')
            ->setIcon($icon)
            ->setTitle('inputbutton-title');

        $someLinkButton = (new LinkButton())
            ->setHref('/some/link')
            ->setDataAttributes(['customkey' => 'customval'])
            ->setShowLabelText(true)
            ->setTitle('linkbutton-title')
            ->setIcon($icon);

        $splitButtonElement = (new SplitButton())
            ->addItem($someLinkButton, false)
            ->addItem($saveButton);

        $html = $splitButtonElement->render();
        // Assertions checks for:
        // - matching href
        // - matching data attributes
        // - matching shown label
        // - matching title
        // - matching icon
        self::assertMatchesRegularExpression('@'
            . '<div class="btn-group t3js-splitbutton">'
            . '\s+<a class="btn btn-sm btn-default " data-customkey="customval" href="/some/link" role="button" title="linkbutton-title">'
            . '\s+<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-document-save" data-identifier="actions-document-save" aria-hidden="true">'
            . '\s+<span class="icon-markup">\s+\[actions-document-save\]\s+</span>'
            . '\s+</span>'
            . '\s+linkbutton-title\s+</a>'
            . '@imsU', $html);
    }
}
