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

namespace TYPO3\CMS\Backend\Localization;

/**
 * Enum representing available localization modes
 *
 * Each mode describes a different strategy for localizing records.
 */
enum LocalizationMode: string
{
    case COPY = 'copy';
    case TRANSLATE = 'localize';

    /**
     * Get the human-readable label for this mode
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::COPY => 'backend.layout:localize.wizard.button.copy',
            self::TRANSLATE => 'backend.layout:localize.wizard.button.translate',
        };
    }

    /**
     * Get the description for this mode
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::COPY => 'backend.layout:localize.educate.copy',
            self::TRANSLATE => 'backend.layout:localize.educate.translate',
        };
    }

    /**
     * Get the icon identifier for this mode
     */
    public function getIconIdentifier(): string
    {
        return match ($this) {
            self::COPY => 'actions-edit-copy',
            self::TRANSLATE => 'actions-localize',
        };
    }

    /**
     * Get the priority of this mode (higher number = higher priority)
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::COPY => 10,
            self::TRANSLATE => 20,
        };
    }

    /**
     * Get the DataHandler command for this mode
     */
    public function getDataHandlerCommand(): string
    {
        return match ($this) {
            self::COPY => 'copyToLanguage',
            self::TRANSLATE => 'localize',
        };
    }

    /**
     * Export mode data for JSON serialization (for API responses)
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->value,
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'iconIdentifier' => $this->getIconIdentifier(),
        ];
    }
}
