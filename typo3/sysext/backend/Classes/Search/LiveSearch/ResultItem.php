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
 * Class that represents a search result item
 *
 * @internal Class may change in further iterations, do not rely on it
 */
final class ResultItem implements \JsonSerializable
{
    private string $itemTitle = '';
    private string $typeLabel = '';
    private ?Icon $icon = null;
    private string $actionUrl = '';
    private array $extraData = [];

    /**
     * @param class-string $providerClassName
     */
    public function __construct(private readonly string $providerClassName)
    {
    }

    public function setItemTitle(string $itemTitle): self
    {
        $this->itemTitle = $itemTitle;

        return $this;
    }

    public function setTypeLabel(string $typeLabel): self
    {
        $this->typeLabel = $typeLabel;

        return $this;
    }

    public function setIcon(Icon $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setActionUrl(string $actionUrl): self
    {
        $this->actionUrl = $actionUrl;

        return $this;
    }

    public function setExtraData(array $extraData): ResultItem
    {
        $this->extraData = $extraData;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'provider' => $this->providerClassName,
            'itemTitle' => $this->itemTitle,
            'typeLabel' => $this->typeLabel,
            'icon' => [
                'identifier' => $this->icon?->getIdentifier(),
                'overlay' => $this->icon?->getOverlayIcon()?->getIdentifier(),
            ],
            'actionUrl' => $this->actionUrl,
            'extraData' => $this->extraData,
        ];
    }
}
