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
 * A DTO for transferring localization information
 */
final readonly class LocalizationInstructions
{
    public function __construct(
        // The record type (e.g., 'pages', 'tt_content')
        public string $mainRecordType,
        // The record UID to localize
        public int $recordUid,
        // The source language UID
        public int $sourceLanguageId,
        // The target language UID
        public int $targetLanguageId,
        public LocalizationMode $mode,
        // Additional data from the wizard steps (e.g., selected records for pages)
        public array $additionalData
    ) {}

    public static function create(array $parameters): self
    {
        if (!isset($parameters['recordType'], $parameters['recordUid'], $parameters['sourceLanguage'], $parameters['targetLanguage'], $parameters['mode'])) {
            throw new \InvalidArgumentException('Missing required parameters given', 1762977203);
        }

        // Convert mode string to enum
        // We do not use tryFrom() so a valueError can be thrown
        $mode = LocalizationMode::from($parameters['mode']);

        return new self(
            $parameters['recordType'],
            (int)$parameters['recordUid'],
            (int)$parameters['sourceLanguage'],
            (int)$parameters['targetLanguage'],
            $mode,
            $parameters['additionalData'] ?? []
        );
    }
}
