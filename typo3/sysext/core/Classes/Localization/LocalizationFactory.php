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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * This class acts currently as facade around SymfonyTranslator.
 * User-land code should use LanguageService for the time being, and this class should not be exposed directly.
 *
 * Ideally, consider using a runtime cache if needed, if not using LanguageService.
 *
 * Hand in the locale to load, or english ("default").
 *
 * What it does:
 * - Caches on a system-level cache
 * - Handles loading default (= english) before translated files
 * - Handles file name juggling of translated files.
 * - Handles localization overrides via $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']
 *
 * This class only deals with full files, "resources" a.k.a. "translation domains" right now. It does not care about
 * the actual identifier WITHIN this label bag.
 *
 * The main issue with this class is that it does not resolve proper dependencies, thus the fallback logic
 * is marode. You can see this when checking for ArrayUtility both here and in LanguageService.
 */
readonly class LocalizationFactory
{
    protected const MOVED_FILES = [
        // @todo: remove the following files in TYPO3 v15.0, they serve as a fallback for old syntax and files that have been moved
        'EXT:seo/Resources/Private/Language/locallang_tca.xlf' => 'EXT:seo/Resources/Private/Language/db.xlf',
    ];

    public function __construct(
        protected Translator $translator,
        #[Autowire(service: 'cache.l10n')]
        protected FrontendInterface $systemCache,
        #[Autowire(service: 'cache.runtime')]
        protected FrontendInterface $runtimeCache,
        protected TranslationDomainMapper $translationDomainMapper,
        protected LabelFileResolver $labelFileResolver,
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
     * @return array<string, array<int, array<string, string>>>
     */
    public function getParsedData(string $fileReference, string $languageKey): array
    {
        $languageKey = $languageKey === 'default' ? 'en' : $languageKey;

        if (isset(self::MOVED_FILES[$fileReference])) {
            trigger_error('The file ' . $fileReference . ' has been moved to ' . self::MOVED_FILES[$fileReference] . '. Please update your code accordingly.', E_USER_DEPRECATED);
            $fileReference = self::MOVED_FILES[$fileReference];
        }

        $fileReference = $this->translationDomainMapper->mapDomainToFileName($fileReference);
        $systemCacheIdentifier = md5($fileReference . $languageKey);

        // If the content is in system cache, put it in runtime cache and use it
        $labels = $this->systemCache->get($systemCacheIdentifier);
        if (is_array($labels)) {
            return $labels;
        }

        try {
            $labels = $this->loadWithSymfonyTranslator($fileReference, $languageKey);
        } catch (FileNotFoundException) {
            $labels = [];
        }

        // Cache processed data
        $this->systemCache->set($systemCacheIdentifier, $labels);

        return $labels;
    }

    /**
     * Apply localization overrides by merging override file contents
     */
    protected function applyLocalizationOverrides(string $fileReference, string $languageKey, array $labels): array
    {
        $overrideFiles = $this->labelFileResolver->getOverrideFilePaths($fileReference, $languageKey);
        $domainName = $this->translationDomainMapper->mapFileNameToDomain($fileReference);
        foreach ($overrideFiles as $overrideFile) {
            $catalogue = $this->getMessageCatalogue($overrideFile, $languageKey, $domainName);
            $fallbackCatalogue = $this->getMessageCatalogue($overrideFile, $languageKey, $domainName, false);
            $overrideLabels = $this->convertCatalogueToLegacyFormat($catalogue, $fallbackCatalogue, $domainName);
            ArrayUtility::mergeRecursiveWithOverrule($labels, $overrideLabels, true, false);
        }

        return $labels;
    }

    /**
     * Get the catalogue and convert to TYPO3 format
     */
    protected function loadWithSymfonyTranslator(string $fileReference, string $languageKey): array
    {
        $domainName = $this->translationDomainMapper->mapFileNameToDomain($fileReference);
        $catalogue = $this->getMessageCatalogue($fileReference, $languageKey, $domainName);
        $fallbackCatalogue = $this->getMessageCatalogue($fileReference, $languageKey, $domainName, false);

        $labels = $this->convertCatalogueToLegacyFormat($catalogue, $fallbackCatalogue, $domainName);
        return $this->applyLocalizationOverrides($fileReference, $languageKey, $labels);
    }

    /**
     * Load translations of one resource using Symfony Translator
     */
    protected function getMessageCatalogue(string $fileReference, string $locale, string $domainName, bool $useDefault = true): MessageCatalogueInterface
    {
        $actualSourcePath = $this->labelFileResolver->resolveFileReference($fileReference, $locale, $useDefault);
        // Add the resource to Symfony Translator, if not added yet.
        $cacheIdentifier = 'symfony-translator-localization-factory-' . md5($actualSourcePath . '-' . $locale . '-' . $domainName);
        if (!$this->runtimeCache->has($cacheIdentifier)) {
            // @todo: we need to be more flexible with the file ending here.
            $fileExtension = (string)pathinfo($actualSourcePath, PATHINFO_EXTENSION);
            $this->translator->addResource($fileExtension ?: 'xlf', $actualSourcePath, $locale, $domainName);
            $this->runtimeCache->set($cacheIdentifier, true);
        }
        return $this->translator->getCatalogue($locale);
    }

    /**
     * Convert Symfony MessageCatalogue to TYPO3's legacy format
     */
    protected function convertCatalogueToLegacyFormat(MessageCatalogueInterface $catalogue, MessageCatalogueInterface $fallbackCatalogue, string $domain): array
    {
        $result = [];
        foreach ($fallbackCatalogue->all($domain) as $key => $value) {
            // Check if this is a plural form (contains ICU format)
            if (str_contains($value, '{0, plural,')) {
                $result[$key] = $this->parseIcuPlural($value);
            } else {
                // Regular translation
                $result[$key] = $value;
            }
        }
        foreach ($catalogue->all($domain) as $key => $value) {
            // Check if this is a plural form (contains ICU format)
            if (str_contains($value, '{0, plural,')) {
                $result[$key] = $this->parseIcuPlural($value);
            } else {
                // Regular translation
                $result[$key] = $value ?: $fallbackCatalogue->get($key, $domain);
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
