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
     * @var string the default size
     */
    public const SIZE_DEFAULT = 'default'; // 1em

    /**
     * @var string the small size
     */
    public const SIZE_SMALL = 'small'; // 16px

    /**
     * @var string the default size
     */
    public const SIZE_MEDIUM = 'medium'; // 32px

    /**
     * @var string the large size
     */
    public const SIZE_LARGE = 'large'; // 48px

    /**
     * @var string the mega size
     */
    public const SIZE_MEGA = 'mega'; // 64px

    /**
     * @internal
     * @var string the overlay size, which depends on icon size
     */
    public const SIZE_OVERLAY = 'overlay';

    /**
     * The identifier which the PHP code that calls the IconFactory hands over
     */
    protected string $identifier;

    /**
     * The title rendered to the icon
     */
    protected ?string $title = null;

    /**
     * The identifier for a possible overlay icon
     */
    protected ?Icon $overlayIcon = null;

    /**
     * Contains the size string ("large", "small" or "default")
     */
    protected string $size = '';

    /**
     * Flag to indicate if the icon has a spinning animation
     */
    protected bool $spinning = false;

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
     */
    public function getMarkup(?string $alternativeMarkupIdentifier = null): string
    {
        if ($alternativeMarkupIdentifier !== null && isset($this->alternativeMarkups[$alternativeMarkupIdentifier])) {
            return $this->alternativeMarkups[$alternativeMarkupIdentifier];
        }
        return $this->markup;
    }

    /**
     * @return $this
     */
    public function setMarkup(string $markup): self
    {
        $this->markup = $markup;
        return $this;
    }

    public function getAlternativeMarkup(string $markupIdentifier): string
    {
        return $this->alternativeMarkups[$markupIdentifier] ?: '';
    }

    /**
     * @return $this
     */
    public function setAlternativeMarkup(string $markupIdentifier, string $markup): self
    {
        $this->alternativeMarkups[$markupIdentifier] = $markup;
        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return $this
     */
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getOverlayIcon(): ?Icon
    {
        return $this->overlayIcon;
    }

    /**
     * @return $this
     */
    public function setOverlayIcon(?Icon $overlayIcon): self
    {
        $this->overlayIcon = $overlayIcon;
        return $this;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * Sets the size and creates the new dimension
     *
     * @return $this
     */
    public function setSize(string $size): self
    {
        $this->size = $size;
        $this->dimension = GeneralUtility::makeInstance(Dimension::class, $size);
        return $this;
    }

    public function isSpinning(): bool
    {
        return $this->spinning;
    }

    /**
     * @return $this
     */
    public function setSpinning(bool $spinning): self
    {
        $this->spinning = $spinning;
        return $this;
    }

    public function getState(): IconState
    {
        return $this->state;
    }

    /**
     * @return $this
     */
    public function setState(IconState $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getDimension(): Dimension
    {
        return $this->dimension;
    }

    public function render(?string $alternativeMarkupIdentifier = null): string
    {
        $overlayIconMarkup = '';
        if ($this->overlayIcon !== null) {
            $overlayIconMarkup = '<span class="icon-overlay icon-' . htmlspecialchars($this->overlayIcon->getIdentifier()) . '">' . $this->overlayIcon->getMarkup() . '</span>';
        }
        return str_replace('{overlayMarkup}', $overlayIconMarkup, $this->wrappedIcon($alternativeMarkupIdentifier));
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Wrap icon markup in unified HTML code
     */
    protected function wrappedIcon(?string $alternativeMarkupIdentifier = null): string
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

        $attributes = [];
        $attributes['title'] = $this->getTitle();
        $attributes['class'] = implode(' ', $classes);
        $attributes['data-identifier'] = $this->getIdentifier();
        $attributes['aria-hidden'] = 'true';

        $markup = [];
        $markup[] = '<span ' . GeneralUtility::implodeAttributes($attributes, true) . '>';
        $markup[] = '	<span class="icon-markup">';
        $markup[] = $this->getMarkup($alternativeMarkupIdentifier);
        $markup[] = '	</span>';
        $markup[] = '	{overlayMarkup}';
        $markup[] = '</span>';

        return implode(LF, $markup);
    }
}
