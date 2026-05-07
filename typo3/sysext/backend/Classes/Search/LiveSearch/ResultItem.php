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
    private ?array $language = null;
    private ?string $thumbnailUrl = null;
    /**
     * @var array<string, string>
     */
    private array $properties = [];
    /**
     * @var ResultItemAction[]
     */
    private array $actions = [];
    private ?ResultItemAction $defaultAction = null;
    private array $extraData = [];
    private array $internalData = [];

    /**
     * @param class-string $providerClassName
     */
    public function __construct(private readonly string $providerClassName) {}

    public function getProviderClassName(): string
    {
        return $this->providerClassName;
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

    public function setThumbnailUrl(?string $thumbnailUrl): self
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }

    public function addProperty(string $label, string $value): self
    {
        $this->properties[$label] = $value;

        return $this;
    }

    public function setActions(ResultItemAction ...$action): self
    {
        $this->actions = $action;

        return $this;
    }

    public function addAction(ResultItemAction $action): self
    {
        $this->actions[] = $action;

        return $this;
    }

    public function getDefaultAction(): ?ResultItemAction
    {
        return $this->defaultAction;
    }

    public function setDefaultAction(?ResultItemAction $defaultAction): ResultItem
    {
        $this->defaultAction = $defaultAction;

        return $this;
    }

    public function getExtraData(): array
    {
        return $this->extraData;
    }

    public function setExtraData(array $extraData): ResultItem
    {
        $this->extraData = $extraData;
        return $this;
    }

    public function getInternalData(): array
    {
        return $this->internalData;
    }

    public function setInternalData(array $internalData): ResultItem
    {
        $this->internalData = $internalData;

        return $this;
    }

    public function setLanguage(?array $language): self
    {
        $this->language = $language;

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
            'thumbnailUrl' => $this->thumbnailUrl,
            'properties' => $this->properties,
            'actions' => $this->actions,
            'defaultAction' => $this->defaultAction ?? $this->actions[0],
            'language' => $this->language,
            'extraData' => $this->extraData,
        ];
    }
}
