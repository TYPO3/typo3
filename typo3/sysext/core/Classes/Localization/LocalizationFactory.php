<?php

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

namespace TYPO3\CMS\Core\Localization;

use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * This class acts currently as facade around SymfonyTranslator.
 * User-land code should use LanguageService for the time being, and this class should not be exposed directly.
 *
 * Hand in the locale to load, or english ("default").
 *
 * What it does:
 * - Caches on a system-level cache
 * - Caches on a runtime memory cache ($this->data) per file
 * - Handles loading default (= english) before translated files
 * - Handles file name juggling of translated files.
 * - Handles localization overrides via $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']
 */
class LocalizationFactory
{
    /**
     * In-memory store for parsed data to avoid re-parsing within the same request.
     *
     * @var array<string, array<string, array<string, array<int, array<string, string>>>>>
     */
    protected array $dataStore = [];

    public function __construct(
        protected readonly Translator $translator,
        protected readonly FrontendInterface $systemCache,
        protected readonly FrontendInterface $runtimeCache,
        protected readonly LabelFileResolver $labelFileResolver,
    ) {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['LANG']['loader'] ?? [] as $key => $loader) {
            if (class_exists($loader)) {
                $this->translator->addLoader($key, new $loader());
            }
        }
        $this->translator->setFallbackLocales(['en']);
    }

    /**
     * Returns parsed data from a given file and language key.
     *
     * @param string $fileReference Input is a file-reference (see \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName). That file is expected to be a supported locallang file format
     * @param string $languageKey Language key
     *
     * @return array<string, array<string, array<int, array<string, string>>>>
     */
    public function getParsedData(string $fileReference, string $languageKey): array
    {
        $languageKey = $languageKey === 'default' ? 'en' : $languageKey;
        $systemCacheIdentifier = md5($fileReference . $languageKey);

        // Check if the default language is processed before processing any other language
        if (!$this->hasData($fileReference, 'en') && $languageKey !== 'en') {
            $this->getParsedData($fileReference, 'en');
        }

        // If the content is parsed (runtime cache), use it
        if ($this->hasData($fileReference, $languageKey)) {
            return $this->getData($fileReference);
        }

        // If the content is in system cache, put it in runtime cache and use it
        $data = $this->systemCache->get($systemCacheIdentifier);
        if ($data !== false) {
            $this->setData($fileReference, $languageKey, $data);
            return $this->getData($fileReference);
        }

        try {
            $labels = $this->loadWithSymfonyTranslator($fileReference, $languageKey);
        } catch (FileNotFoundException) {
            // Source localization file not found, set empty data as there could be an override
            $this->setData($fileReference, $languageKey, []);
            $labels = $this->getData($fileReference);
        }

        // Save parsed data in runtime cache
        $this->setData($fileReference, $languageKey, $labels[$languageKey] ?? []);

        // Cache processed data
        $this->systemCache->set($systemCacheIdentifier, $this->getDataByLanguage($fileReference, $languageKey));

        return $this->getData($fileReference);
    }

    /**
     * Apply localization overrides by merging override file contents
     */
    protected function applyLocalizationOverrides(string $fileReference, string $languageKey, array $labels): array
    {
        $overrideFiles = $this->labelFileResolver->getOverrideFilePaths($fileReference, $languageKey);

        foreach ($overrideFiles as $overrideFile) {
            $catalogue = $this->getMessageCatalogue($overrideFile, $languageKey);
            $fallbackCatalogue = $this->getMessageCatalogue($overrideFile, $languageKey, false);
            $overrideLabels = $this->convertCatalogueToLegacyFormat($catalogue, $languageKey, $fallbackCatalogue);
            ArrayUtility::mergeRecursiveWithOverrule($labels, $overrideLabels, true, false);
        }

        return $labels;
    }

    /**
     * Check if data is available for the given file reference and language
     */
    protected function hasData(string $fileReference, string $languageKey): bool
    {
        return is_array($this->dataStore[$fileReference][$languageKey] ?? null);
    }

    /**
     * Get all data for a file reference
     */
    protected function getData(string $fileReference): array
    {
        return $this->dataStore[$fileReference] ?? [];
    }

    /**
     * Get data for a specific language
     */
    protected function getDataByLanguage(string $fileReference, string $languageKey): array
    {
        return $this->dataStore[$fileReference][$languageKey] ?? [];
    }

    /**
     * Set data for a file reference and language
     */
    protected function setData(string $fileReference, string $languageKey, array $data): void
    {
        $this->dataStore[$fileReference][$languageKey] = $data;
    }

    /**
     * Get the catalogue and convert to TYPO3 format
     */
    protected function loadWithSymfonyTranslator(string $fileReference, string $languageKey): array
    {
        $catalogue = $this->getMessageCatalogue($fileReference, $languageKey);
        $fallbackCatalogue = $this->getMessageCatalogue($fileReference, $languageKey, false);

        $labels = $this->convertCatalogueToLegacyFormat($catalogue, $languageKey, $fallbackCatalogue);
        return $this->applyLocalizationOverrides($fileReference, $languageKey, $labels);
    }

    /**
     * Load translations of one resource using Symfony Translator
     */
    protected function getMessageCatalogue(string $fileReference, string $locale, bool $useDefault = true): MessageCatalogueInterface
    {
        $actualSourcePath = $this->labelFileResolver->resolveFileReference($fileReference, $locale, $useDefault);
        // @todo: we need to be more flexible with the file ending here.
        $fileExtension = (string)pathinfo($actualSourcePath, PATHINFO_EXTENSION);
        // Add the resource to Symfony Translator
        $this->translator->addResource($fileExtension ?: 'xlf', $actualSourcePath, $locale, 'messages');
        return $this->translator->getCatalogue($locale);
    }

    /**
     * Convert Symfony MessageCatalogue to TYPO3's legacy format
     */
    protected function convertCatalogueToLegacyFormat(MessageCatalogueInterface $catalogue, string $languageKey, MessageCatalogueInterface $fallbackCatalogue): array
    {
        $result = [];
        foreach ($fallbackCatalogue->all() as $translations) {
            foreach ($translations as $key => $value) {
                // Check if this is a plural form (contains ICU format)
                if (str_contains($value, '{0, plural,')) {
                    $result[$languageKey][$key] = $this->parseIcuPlural($value);
                } else {
                    // Regular translation
                    $result[$languageKey][$key] = $value;
                }
            }
        }
        foreach ($catalogue->all() as $translations) {
            foreach ($translations as $key => $value) {
                // Check if this is a plural form (contains ICU format)
                if (str_contains($value, '{0, plural,')) {
                    $result[$languageKey][$key] = $this->parseIcuPlural($value);
                } else {
                    // Regular translation
                    $result[$languageKey][$key] = $value ?: $fallbackCatalogue->get($key);
                }
            }
        }

        return $result;
    }

    /**
     * Simple parser for ICU plural format - extracts plural values
     */
    protected function parseIcuPlural(string $icuString): array
    {
        $plurals = [];

        // Extract content within plural braces
        if (preg_match('/\{0, plural,(.+)\}$/', $icuString, $matches)) {
            $content = trim($matches[1]);

            // Parse forms like "one {text1} other {text2}"
            if (preg_match_all('/(\w+)\s*\{([^}]+)\}/', $content, $formMatches, PREG_SET_ORDER)) {
                foreach ($formMatches as $match) {
                    $form = $match[1];
                    $text = $match[2];

                    // Map ICU forms to indices (simplified mapping)
                    $index = match ($form) {
                        'one' => 0,
                        'other' => 1,
                        default => count($plurals)
                    };

                    $plurals[$index] = $text;
                }
            }
        }

        return $plurals;
    }
}
