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

namespace TYPO3\CMS\Core\Imaging\Event;

use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * This is an Event every time an icon for a resource (file or folder) is fetched, allowing
 * to modify the icon or overlay in an event listener.
 */
final class ModifyIconForResourcePropertiesEvent
{
    public function __construct(
        private readonly ResourceInterface $resource,
        private readonly string $size,
        private readonly array $options,
        private ?string $iconIdentifier,
        private ?string $overlayIdentifier
    ) {}

    public function getResource(): ResourceInterface
    {
        return $this->resource;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getIconIdentifier(): ?string
    {
        return $this->iconIdentifier;
    }

    public function setIconIdentifier(?string $iconIdentifier): void
    {
        $this->iconIdentifier = $iconIdentifier;
    }

    public function getOverlayIdentifier(): ?string
    {
        return $this->overlayIdentifier;
    }

    public function setOverlayIdentifier(?string $overlayIdentifier): void
    {
        $this->overlayIdentifier = $overlayIdentifier;
    }
}
