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
 * LinkButton
 *
 * This button type renders a regular anchor tag with TYPO3s way to render a
 * button control.
 *
 * EXAMPLE USAGE TO ADD A BUTTON TO THE FIRST BUTTON GROUP IN THE LEFT BAR:
 *
 * $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 * $saveButton = $buttonBar->makeLinkButton()
 *      ->setHref('#')
 *      ->setDataAttributes([
 *          'foo' => 'bar'
 *      ])
 *      ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL))
 *      ->setTitle('Save');
 * $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
 */
class LinkButton extends AbstractButton
{
    /**
     * HREF attribute of the link
     *
     * @var string
     */
    protected $href = '';

    /**
     * Get href
     *
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * Set href
     *
     * @param string $href HREF attribute
     *
     * @return LinkButton
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * Validates the current button
     *
     * @return bool
     */
    public function isValid()
    {
        if (
            trim($this->getHref()) !== ''
            && trim($this->getTitle()) !== ''
            && $this->getType() === self::class
            && $this->getIcon() !== null
        ) {
            return true;
        }
        return false;
    }

    /**
     * Renders the markup for the button
     *
     * @return string
     */
    public function render()
    {
        $attributes = [
            'href' => $this->getHref(),
            'class' => 'btn btn-default btn-sm ' . $this->getClasses(),
            'title' => $this->getTitle()
        ];
        $labelText = '';
        if ($this->showLabelText) {
            $labelText = ' ' . $this->title;
        }
        foreach ($this->dataAttributes as $attributeName => $attributeValue) {
            $attributes['data-' . $attributeName] = $attributeValue;
        }
        if ($this->onClick !== '') {
            $attributes['onclick'] = $this->onClick;
        }
        $attributesString = '';
        foreach ($attributes as $key => $value) {
            $attributesString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }

        return '<a ' . $attributesString . '>'
            . $this->getIcon()->render() . htmlspecialchars($labelText)
        . '</a>';
    }

    /**
     * Magic method so Fluid can access a button via {button}
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
