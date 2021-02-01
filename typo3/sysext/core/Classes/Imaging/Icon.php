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

namespace TYPO3\CMS\Core\Imaging;

use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Icon object, holds all information for one icon, identified by the "identifier" property.
 * Is available to render itself as string.
 */
class Icon
{
    /**
     * @var string the small size
     */
    const SIZE_SMALL = 'small'; // 16

    /**
     * @var string the default size
     */
    const SIZE_DEFAULT = 'default'; // 32

    /**
     * @var string the large size
     */
    const SIZE_LARGE = 'large'; // 48

    /**
     * @internal
     * @var string the overlay size, which depends on icon size
     */
    const SIZE_OVERLAY = 'overlay';

    /**
     * The identifier which the PHP code that calls the IconFactory hands over
     *
     * @var string
     */
    protected $identifier;

    /**
     * The identifier for a possible overlay icon
     *
     * @var Icon|null
     */
    protected $overlayIcon;

    /**
     * Contains the size string ("large", "small" or "default")
     *
     * @var string
     */
    protected $size = '';

    /**
     * Flag to indicate if the icon has a spinning animation
     *
     * @var bool
     */
    protected $spinning = false;

    /**
     * Contains the state information
     *
     * @var IconState
     */
    protected $state;

    /**
     * @var Dimension
     */
    protected $dimension;

    /**
     * @var string
     */
    protected $markup;

    /**
     * @var array
     */
    protected $alternativeMarkups = [];

    /**
     * @internal this method is used for internal processing, to get the prepared and final markup use render()
     *
     * @param string $alternativeMarkupIdentifier
     *
     * @return string
     */
    public function getMarkup($alternativeMarkupIdentifier = null)
    {
        if ($alternativeMarkupIdentifier !== null && isset($this->alternativeMarkups[$alternativeMarkupIdentifier])) {
            return $this->alternativeMarkups[$alternativeMarkupIdentifier];
        }
        return $this->markup;
    }

    /**
     * @param string $markup
     */
    public function setMarkup($markup)
    {
        $this->markup = $markup;
    }

    /**
     * @param string $markupIdentifier
     *
     * @return string
     */
    public function getAlternativeMarkup($markupIdentifier)
    {
        return $this->alternativeMarkups[$markupIdentifier];
    }

    /**
     * @param string $markupIdentifier
     * @param string $markup
     */
    public function setAlternativeMarkup($markupIdentifier, $markup)
    {
        $this->alternativeMarkups[$markupIdentifier] = $markup;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return Icon|null
     */
    public function getOverlayIcon()
    {
        return $this->overlayIcon;
    }

    /**
     * @param Icon $overlayIcon
     */
    public function setOverlayIcon($overlayIcon)
    {
        $this->overlayIcon = $overlayIcon;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Sets the size and creates the new dimension
     *
     * @param string $size
     */
    public function setSize($size)
    {
        $this->size = $size;
        $this->dimension = GeneralUtility::makeInstance(Dimension::class, $size);
    }

    /**
     * @return bool
     */
    public function isSpinning()
    {
        return $this->spinning;
    }

    /**
     * @param bool $spinning
     */
    public function setSpinning($spinning)
    {
        $this->spinning = $spinning;
    }

    /**
     * @return IconState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Sets the state of the icon
     *
     * @param IconState $state
     */
    public function setState(IconState $state)
    {
        $this->state = $state;
    }

    /**
     * @return Dimension
     */
    public function getDimension()
    {
        return $this->dimension;
    }

    /**
     * Render the icon as HTML code
     *
     * @param string $alternativeMarkupIdentifier
     *
     * @return string
     */
    public function render($alternativeMarkupIdentifier = null)
    {
        $overlayIconMarkup = '';
        if ($this->overlayIcon !== null) {
            $overlayIconMarkup = '<span class="icon-overlay icon-' . htmlspecialchars($this->overlayIcon->getIdentifier()) . '">' . $this->overlayIcon->getMarkup() . '</span>';
        }
        return str_replace('{overlayMarkup}', $overlayIconMarkup, $this->wrappedIcon($alternativeMarkupIdentifier));
    }

    /**
     * Render the icon as HTML code
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Wrap icon markup in unified HTML code
     *
     * @param string $alternativeMarkupIdentifier
     *
     * @return string
     */
    protected function wrappedIcon($alternativeMarkupIdentifier = null)
    {
        $classes = [];
        $classes[] = 't3js-icon';
        $classes[] = 'icon';
        $classes[] = 'icon-size-' . $this->size;
        $classes[] = 'icon-state-' . htmlspecialchars((string)$this->state);
        $classes[] = 'icon-' . $this->getIdentifier();
        if ($this->isSpinning()) {
            $classes[] = 'icon-spin';
        }

        $markup = [];
        $markup[] = '<span class="' . htmlspecialchars(implode(' ', $classes)) . '" data-identifier="' . htmlspecialchars($this->getIdentifier()) . '">';
        $markup[] = '	<span class="icon-markup">';
        $markup[] = $this->getMarkup($alternativeMarkupIdentifier);
        $markup[] = '	</span>';
        $markup[] = '	{overlayMarkup}';
        $markup[] = '</span>';

        return implode(LF, $markup);
    }
}
