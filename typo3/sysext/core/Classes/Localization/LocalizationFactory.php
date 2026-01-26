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

/**
 * This class acts currently as facade around SymfonyTranslator.
 * User-land code should use LanguageService for the time being, and this class should not be exposed directly.
 *
 * Ideally, consider using a runtime cache if needed, if not using LanguageService.
 *
 * Hand in the locale to load, or english ("en").
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
        'EXT:core/Resources/Private/Language/locallang_t3lib_fullsearch.xlf' => 'EXT:lowlevel/Resources/Private/Language/fullsearch.xlf',
        'EXT:dashboard/Resources/Private/Language/locallang_tca.xlf' => 'EXT:dashboard/Resources/Private/Language/db.xlf',
        'EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf' => 'EXT:filemetadata/Resources/Private/Language/db.xlf',
        'EXT:lowlevel/Resources/Private/Language/locallang.xlf' => 'EXT:lowlevel/Resources/Private/Language/messages.xlf',
        'EXT:impexp/Resources/Private/Language/locallang_tca.xlf' => 'EXT:impexp/Resources/Private/Language/db.xlf',
        'EXT:linkvalidator/Resources/Private/Language/locallang.xlf' => 'EXT:linkvalidator/Resources/Private/Language/db.xlf',
        'EXT:seo/Resources/Private/Language/locallang_tca.xlf' => 'EXT:seo/Resources/Private/Language/db.xlf',
        'EXT:webhooks/Resources/Private/Language/Modules/webhooks.xlf' => 'EXT:webhooks/Resources/Private/Language/module.xlf',
        'EXT:reactions/Resources/Private/Language/Modules/webhooks.xlf' => 'EXT:reactions/Resources/Private/Language/module.xlf',
    ];

    protected const DEPRECATED_FILES = [
        // @todo: remove the following files in TYPO3 v15.0
        'EXT:backend/Resources/Private/Language/locallang_view_help.xlf',
        'EXT:backend/Resources/Private/Language/locallang_view_help.xlf',
        'EXT:backend/Resources/Private/Language/locallang_sitesettings_module.xlf',
        'EXT:backend/Resources/Private/Language/locallang_siteconfiguration_module.xlf',
        'EXT:backend/Resources/Private/Language/locallang_mod.xlf',
        'EXT:belog/Resources/Private/Language/locallang_mod.xlf',
        'EXT:beuser/Resources/Private/Language/locallang_mod.xlf',
        'EXT:core/Resources/Private/Language/locallang_mod_usertools.xlf',
        'EXT:core/Resources/Private/Language/locallang_mod_system.xlf',
        'EXT:core/Resources/Private/Language/locallang_mod_site.xlf',
        'EXT:core/Resources/Private/Language/locallang_mod_file.xlf',
        'EXT:core/Resources/Private/Language/locallang_mod_help.xlf',
        'EXT:core/Resources/Private/Language/locallang_mod_admintools.xlf',
        'EXT:core/Resources/Private/Language/locallang_tsfe.xlf',
        'EXT:dashboard/Resources/Private/Language/locallang_mod.xlf',
        'EXT:extensionmanager/Resources/Private/Language/locallang_mod.xlf',
        'EXT:form/Resources/Private/Language/locallang_module.xlf',
        'EXT:indexed_search/Resources/Private/Language/locallang_mod.xlf',
        'EXT:install/Resources/Private/Language/ModuleInstallUpgrade.xlf',
        'EXT:install/Resources/Private/Language/ModuleInstallSettings.xlf',
        'EXT:install/Resources/Private/Language/ModuleInstallMaintenance.xlf',
        'EXT:install/Resources/Private/Language/ModuleInstallEnvironment.xlf',
        'EXT:install/Resources/Private/Language/BackendModule.xlf',
        'EXT:info/Resources/Private/Language/locallang_mod_web_info.xlf',
        'EXT:linkvalidator/Resources/Private/Language/Module/locallang_mod.xlf',
        'EXT:recycler/Resources/Private/Language/locallang_mod.xlf',
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
        $this->translator->setFallbackLocales(['default']);
    }

    /**
     * @internal Not part of TYPO3 Core API. Do not use outside of TYPO3 Core as this method may vanish at any time.
     */
    public function isLanguageFileDeprecated(string $fileReference): bool
    {
        return in_array($fileReference, self::DEPRECATED_FILES) ||
            isset(self::MOVED_FILES[$fileReference]);
    }

    /**
     * Preload files into Symfony Translator without retrieving catalogues.
     *
     * This is used during cache warmup to batch all addResource() calls before
     * any getCatalogue() calls, avoiding O(n²) catalogue rebuilds.
     *
     * @internal
     */
    public function warmupTranslatorResource(string $fileReference, Locale $locale): void
    {
        [$fileReference, $domainName, $allLanguageKeysAsOrderedFallback] = $this->computeFileDomainAndFallbacks($fileReference, $locale);
        $this->loadLanguagesIntoSymfonyTranslator($fileReference, $domainName, $allLanguageKeysAsOrderedFallback);
    }

    /**
     * Returns parsed data from a given file and language key.
     *
     * @param string $fileReference Input is a file-reference (see \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName). That file is expected to be a supported locallang file format
     * @param Locale|string|null $locale Locale with dependencies or language key. Null value is set to 'en' with fallback 'default'. @internal Language key as string loads language data with "en" and "default" as the default fallback dependency.
     * @param bool $renewCache Recompute data and renew cache entry.
     *
     * @return array<string, string|array<int, array<string, string>>>
     */
    public function getParsedData(string $fileReference, Locale|string|null $locale, bool $renewCache = false): array
    {
        if ($locale === null) {
            $locale = new Locale('en', ['default']);
        }
        if (is_string($locale)) {
            // Load language data with fallback "en" and "default", as these are always implicitly the default fallback dependencies.
            $locale = new Locale($locale);
        }
        $languageKey = $locale->getName();

        [$fileReference, $domainName, $allLanguageKeysAsOrderedFallback] = $this->computeFileDomainAndFallbacks($fileReference, $locale);
        $systemCacheIdentifier = md5($domainName . $languageKey . serialize($allLanguageKeysAsOrderedFallback));

        // If the content is in system cache, put it in runtime cache and use it
        if (!$renewCache) {
            $labels = $this->systemCache->get($systemCacheIdentifier);
            if (is_array($labels)) {
                return $labels;
            }
        }

        // Add files for all locales to Symfony Translator catalogue - order does not matter here.
        $this->loadLanguagesIntoSymfonyTranslator($fileReference, $domainName, $allLanguageKeysAsOrderedFallback);

        // Set order of fallback locales in Symfony Translator.
        if ($this->translator->getFallbackLocales() !== $allLanguageKeysAsOrderedFallback) {
            // Performance: Setting fallbacks clears all catalogues, which results in computational expensive regeneration of catalogues!
            $this->translator->setFallbackLocales($allLanguageKeysAsOrderedFallback);
        }
        $labels = $this->loadWithSymfonyTranslator($languageKey, $domainName);

        // Cache processed data
        $this->systemCache->set($systemCacheIdentifier, $labels);

        return $labels;
    }

    /**
     * Prepares file reference, domain, language fallbacks
     *
     * @return array{string, string, array<string>}
     */
    protected function computeFileDomainAndFallbacks(string $fileReference, Locale $locale): array
    {
        if (in_array($fileReference, self::DEPRECATED_FILES)) {
            trigger_error(
                sprintf('The file "%s" is deprecated. Please use a label from a different language file instead.', $fileReference),
                E_USER_DEPRECATED
            );
        }
        if (isset(self::MOVED_FILES[$fileReference])) {
            trigger_error('The file ' . $fileReference . ' has been moved to ' . self::MOVED_FILES[$fileReference] . '. Please update your code accordingly.', E_USER_DEPRECATED);
            $fileReference = self::MOVED_FILES[$fileReference];
        }

        $fileReference = $this->translationDomainMapper->mapDomainToFileName($fileReference);
        $domainName = $this->translationDomainMapper->mapFileNameToDomain($fileReference);
        $allLanguageKeysAsOrderedFallback = $this->computeAllLanguageKeys($locale);

        return [$fileReference, $domainName, $allLanguageKeysAsOrderedFallback];
    }

    protected function computeAllLanguageKeys(Locale $locale): array
    {
        if ($locale->getName() === 'default') {
            return ['default'];
        }

        $mainLocales = [$locale->getName()];
        $dependencyLocales = $locale->getDependencies();

        // Firstly, remove 'default' if exists. 'en' must be added before 'default'.
        if (($keyDefault = array_search('default', $dependencyLocales, true)) !== false) {
            unset($dependencyLocales[$keyDefault]);
        }
        // 'en' and 'default' is always added as the default fallback dependency
        $allLocales = array_merge($mainLocales, $dependencyLocales, ['en', 'default']);
        $allLocales = array_unique($allLocales);
        return $allLocales;
    }

    /**
     * Load languages into Symfony Translator
     */
    protected function loadLanguagesIntoSymfonyTranslator(string $fileReference, string $domainName, array $allLanguageKeysAsOrderedFallback): void
    {
        // Add files for all locales to Symfony Translator catalogue - order does not matter here.
        foreach ($allLanguageKeysAsOrderedFallback as $currentLanguageKey) {
            $this->loadFilesIntoSymfonyTranslator($fileReference, $currentLanguageKey, $domainName);
        }
    }

    /**
     * Load files into Symfony Translator
     */
    protected function loadFilesIntoSymfonyTranslator(string $fileReference, string $languageKey, string $domainName): void
    {
        // Early exit if this file+locale combination has already been fully processed (including overrides).
        // This avoids redundant resolveFileReference() and getOverrideFilePaths() calls when processing
        // fallback locales that have already been loaded for previous files.
        $loadedCacheIdentifier = 'localization-factory-loaded-' . md5($fileReference . '-' . $languageKey . '-' . $domainName);
        if ($this->runtimeCache->has($loadedCacheIdentifier)) {
            return;
        }

        // Firstly, load language into catalogue.
        try {
            $this->addFileReferenceToTranslator($fileReference, $languageKey, $domainName);
        } catch (FileNotFoundException) {
            // Run localization override, regardless of file reference not found.
        }

        // Finally, apply localization overrides.
        $overrideFiles = $this->labelFileResolver->getOverrideFilePaths($fileReference, $languageKey);
        foreach ($overrideFiles as $overrideFile) {
            try {
                $this->addFileReferenceToTranslator($overrideFile, $languageKey, $domainName);
            } catch (FileNotFoundException) {
            }
        }

        $this->runtimeCache->set($loadedCacheIdentifier, true);
    }

    /**
     * Get the catalogue and convert to TYPO3 format
     */
    protected function loadWithSymfonyTranslator(string $languageKey, string $domainName): array
    {
        $catalogue = $this->getMessageCatalogue($languageKey);
        return $this->convertCatalogueToLegacyFormat($catalogue, $domainName);
    }

    /**
     * Load complete catalogue for locale using Symfony Translator
     */
    protected function getMessageCatalogue(string $locale): MessageCatalogueInterface
    {
        return $this->translator->getCatalogue($locale);
    }

    /**
     * Adds translations of one resource to Symfony Translator
     *
     * @throws FileNotFoundException
     */
    protected function addFileReferenceToTranslator(string $fileReference, string $locale, string $domainName): void
    {
        $actualSourcePath = $this->labelFileResolver->resolveFileReference($fileReference, $locale);
        if ($actualSourcePath === null) {
            // No file found. This might be the case if there is no localized version.
            return;
        }
        // Add the resource to Symfony Translator, if not added yet.
        $cacheIdentifier = 'symfony-translator-localization-factory-' . md5($actualSourcePath . '-' . $locale . '-' . $domainName);
        if (!$this->runtimeCache->has($cacheIdentifier)) {
            // @todo: we need to be more flexible with the file ending here.
            $fileExtension = (string)pathinfo($actualSourcePath, PATHINFO_EXTENSION);
            $this->translator->addResource($fileExtension ?: 'xlf', $actualSourcePath, $locale, $domainName);
            $this->runtimeCache->set($cacheIdentifier, true);
        }
    }

    /**
     * Convert Symfony MessageCatalogue to TYPO3's legacy format
     */
    protected function convertCatalogueToLegacyFormat(MessageCatalogueInterface $catalogue, string $domain): array
    {
        $result = [];
        $fallbackCatalogue = $catalogue->getFallbackCatalogue();
        if ($fallbackCatalogue !== null) {
            $result = $this->convertCatalogueToLegacyFormat($fallbackCatalogue, $domain);
        }
        foreach ($catalogue->all($domain) as $key => $value) {
            // Check if this is a plural form (contains ICU format)
            if (str_contains($value, '{0, plural,')) {
                $result[$key] = $this->parseIcuPlural($value);
            } else {
                // Regular translation
                $result[$key] = $value;
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
