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

namespace TYPO3\CMS\Backend\Template\Components\Buttons;

/**
 * This button type is an intermediate solution for buttons that are rendered
 * by methods from TYPO3 itself, like the CSH buttons or Bookmark buttons.
 *
 * There should be no need to use them, so do yourself a favour and don't.
 *
 * Example:
 *
 * ```
 * public function __construct(
 *     protected readonly ComponentFactory $componentFactory,
 * ) {}
 *
 * public function myAction(): ResponseInterface
 * {
 *     $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 *     $myButton = $this->componentFactory->createFullyRenderedButton()
 *          ->setHtmlSource('<span class="i-should-not-be-using-this>Foo</span>');
 *     $buttonBar->addButton($myButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
 * }
 * ```
 */
class FullyRenderedButton implements ButtonInterface
{
    /**
     * The full HTML source of the rendered button.
     * This source will be passed through to the frontend as is, so keep htmlspecialchars() in mind.
     *
     * @var string
     */
    protected string $htmlSource = '';

    public function getHtmlSource(): string
    {
        return $this->htmlSource;
    }

    public function setHtmlSource(string $htmlSource): static
    {
        $this->htmlSource = $htmlSource;
        return $this;
    }

    public function getType(): string
    {
        return static::class;
    }

    public function isValid(): bool
    {
        return trim($this->getHtmlSource()) !== '' && $this->getType() === static::class;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function render(): string
    {
        return '<span class="btn btn-sm btn-default">' . $this->getHtmlSource() . '</span>';
    }
}
