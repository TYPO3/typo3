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

namespace TYPO3\CMS\Dashboard\Dto;

/**
 * @internal
 */
final readonly class Dashboard implements \JsonSerializable
{
    /**
     * @param list<WidgetConfiguration> $widgets
     * @param array<string, list<WidgetPosition>> $widgetPositions
     */
    public function __construct(
        private string $identifier,
        private string $title,
        private array $widgets,
        private array $widgetPositions,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'identifier' => $this->identifier,
            'title' => $this->title,
            'widgets' => $this->widgets,
            'widgetPositions' => (object)$this->widgetPositions,
        ];
    }
}
