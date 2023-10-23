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

namespace TYPO3\CMS\Backend\Search\LiveSearch;

use TYPO3\CMS\Core\Imaging\Icon;

/**
 * Class that represents a search result item action
 *
 * @internal Class may change in further iterations, do not rely on it
 */
final class ResultItemAction implements \JsonSerializable
{
    private string $label = '';
    private ?Icon $icon = null;
    private string $url = '';

    /**
     * @param non-empty-string $identifier
     */
    public function __construct(private readonly string $identifier) {}

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function setIcon(?Icon $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'identifier' => $this->identifier,
            'label' => $this->label,
            'icon' => [
                'identifier' => $this->icon?->getIdentifier(),
                'overlay' => $this->icon?->getOverlayIcon()?->getIdentifier(),
            ],
            'url' => $this->url,
        ];
    }
}
