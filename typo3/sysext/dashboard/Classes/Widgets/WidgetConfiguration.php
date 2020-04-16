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

namespace TYPO3\CMS\Dashboard\Widgets;

class WidgetConfiguration implements WidgetConfigurationInterface
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var array
     */
    private $groupNames;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $iconIdentifier;

    /**
     * @var string
     */
    private $height;

    /**
     * @var string
     */
    private $width;

    /**
     * @var array
     */
    private $additionalCssClasses;

    /**
     * @throws \InvalidArgumentException If non valid parameters were provided.
     */
    public function __construct(
        string $identifier,
        string $serviceName,
        array $groupNames,
        string $title,
        string $description,
        string $iconIdentifier,
        string $height,
        string $width,
        array $additionalCssClasses
    ) {
        $allowedSizes = ['small', 'medium', 'large'];
        if (!in_array($height, $allowedSizes, true)) {
            throw new \InvalidArgumentException('Height of widgets has to be small, medium or large', 1584778196);
        }
        if (!in_array($height, $allowedSizes, true)) {
            throw new \InvalidArgumentException('Width of widgets has to be small, medium or large', 1585249769);
        }

        $this->identifier = $identifier;
        $this->serviceName = $serviceName;
        $this->groupNames = $groupNames;
        $this->title = $title;
        $this->description = $description;
        $this->iconIdentifier = $iconIdentifier;
        $this->height = $height;
        $this->width = $width;
        $this->additionalCssClasses = $additionalCssClasses;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getGroupNames(): array
    {
        return $this->groupNames;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier;
    }

    public function getHeight(): string
    {
        return $this->height;
    }

    public function getWidth(): string
    {
        return $this->width;
    }

    public function getAdditionalCssClasses(): string
    {
        return implode(' ', $this->additionalCssClasses);
    }
}
