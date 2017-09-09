<?php
namespace TYPO3\CMS\Backend\Template\Components\Buttons;

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

/**
 * FullyRenderedButton
 *
 * This button type is an intermediate solution for buttons that are rendered
 * by methods from TYPO3 itself, like the CSH buttons or Bookmark buttons.
 *
 * There should be no need to use them, so do yourself a favour and don't.
 *
 * EXAMPLE USAGE TO ADD A BUTTON TO THE FIRST BUTTON GROUP IN THE LEFT BAR:
 *
 * $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 * $myButton = $buttonBar->makeFullyRenderedButton()
 *      ->setHtmlSource('<span class="i-should-not-be-using-this>Foo</span>');
 * $buttonBar->addButton($myButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
 */
class FullyRenderedButton implements ButtonInterface
{
    /**
     * The full HTML source of the rendered button.
     * This source will be passed through to the frontend as is,
     * so keep htmlspecialchars() in mind
     *
     * @var string
     */
    protected $htmlSource = '';

    /**
     * Gets the HTML Source of the button
     *
     * @return string
     */
    public function getHtmlSource()
    {
        return $this->htmlSource;
    }

    /**
     * Sets the HTML Source of the button and returns itself
     *
     * @param string $htmlSource HTML sourcecode of the button
     *
     * @return FullyRenderedButton
     */
    public function setHtmlSource($htmlSource)
    {
        $this->htmlSource = $htmlSource;
        return $this;
    }

    /**
     * Gets the type of the button
     *
     * @return string
     */
    public function getType()
    {
        return static::class;
    }

    /**
     * Validator for a FullyRenderedButton
     *
     * @return bool
     */
    public function isValid()
    {
        if (
            trim($this->getHtmlSource()) !== ''
            && $this->getType() === self::class
        ) {
            return true;
        }
        return false;
    }

    /**
     * Renders the button
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Renders the button
     *
     * @return string
     */
    public function render()
    {
        return '<span class="btn btn-sm btn-default">' . $this->getHtmlSource() . '</span>';
    }
}
