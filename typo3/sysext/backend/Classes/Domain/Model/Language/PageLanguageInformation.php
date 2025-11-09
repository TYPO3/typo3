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

namespace TYPO3\CMS\Backend\Domain\Model\Language;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Data Transfer Object representing language availability information for a specific page.
 * Contains both existing translations and potential new translations.
 *
 * This provides a single source of truth for:
 * - Which languages are configured and available
 * - Which languages have translations on this page
 * - Which languages can be created (permissions)
 * - Translation records with workspace overlay applied
 * - UI-ready language items for display
 *
 * @internal
 */
final readonly class PageLanguageInformation
{
    /**
     * @param int $pageId Page ID this information belongs to
     * @param SiteLanguage[] $availableLanguages All available languages
     * @param array<int, LanguageStatus> $languageStatuses Status for each language (existing/creatable/unavailable)
     * @param array<int, array> $existingTranslations Raw page translation records, keyed by language ID
     * @param int[] $creatableLanguageIds IDs of languages that can be created
     * @param bool $canUserCreateTranslations Whether user has permission to create translations
     * @param LanguageItem[] $languageItems UI-ready language items
     */
    public function __construct(
        public int $pageId,
        public array $availableLanguages,
        public array $languageStatuses,
        public array $existingTranslations,
        public array $creatableLanguageIds,
        public bool $canUserCreateTranslations,
        public array $languageItems,
    ) {}

    /**
     * Check if a translation exists for a specific language.
     */
    public function hasTranslation(int $languageId): bool
    {
        return array_key_exists($languageId, $this->existingTranslations);
    }

    /**
     * Check if a translation can be created for a specific language.
     */
    public function canCreateTranslation(int $languageId): bool
    {
        return in_array($languageId, $this->creatableLanguageIds, true);
    }

    /**
     * Get the translation record for a specific language.
     *
     * @return array|null Translation record or null if not found
     */
    public function getTranslationRecord(int $languageId): ?array
    {
        return $this->existingTranslations[$languageId] ?? null;
    }

    /**
     * Get the status of a specific language.
     */
    public function getLanguageStatus(int $languageId): LanguageStatus
    {
        return $this->languageStatuses[$languageId] ?? LanguageStatus::Unavailable;
    }

    /**
     * Get all language IDs including default (0) and all translations.
     *
     * @return int[]
     */
    public function getAllExistingLanguageIds(): array
    {
        return array_merge([0], array_keys($this->existingTranslations));
    }
}
