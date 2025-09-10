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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This class acts currently as facade around SymfonyTranslator.
 * User-land code should use LanguageService for the time being, and this class should not be exposed directly.
 *
 * Hand in the locale to load, or "default" for english (not en).
 *
 * What it does:
 * - Caches on a system-level cache
 * - Caches on a runtime memory cache ($this->data) per file
 * - Handles loading "default" (= english) over translated files
 * - Handles file name juggling of translated files.
 * - Handles localization overrides via $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']
 *
 * Should also handle multiple loaders in the future (XLIFF, PHP, etc.)
 */
class LocalizationFactory implements SingletonInterface
{
    /**
     * In-memory store for parsed data to avoid re-parsing within the same request.
     *
     * @var array<string, array<string, array<string, array<int, array<string, string>>>>>
     */
    protected array $dataStore = [];

    public function __construct(
        protected readonly PackageManager $packageManager,
        protected readonly Translator $translator,
        protected readonly FrontendInterface $systemCache,
        protected readonly FrontendInterface $runtimeCache,
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
     * @param bool $isLocalizationOverride TRUE if $fileReference is a localization override
     *
     * @return array<string, array<string, array<int, array<string, string>>>>
     */
    public function getParsedData(string $fileReference, string $languageKey, bool $isLocalizationOverride = false): array
    {
        $languageKey = $languageKey === 'default' ? 'en' : $languageKey;
        $hash = md5($fileReference . $languageKey);

        // Check if the default language is processed before processing other language
        if (!$this->hasData($fileReference, 'en') && $languageKey !== 'en') {
            $this->getParsedData($fileReference, 'en');
        }

        // If the content is parsed (local cache), use it
        if ($this->hasData($fileReference, $languageKey)) {
            return $this->getData($fileReference);
        }

        // If the content is in cache (system cache), use it
        $data = $this->systemCache->get($hash);
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

        // Override localization
        if (!$isLocalizationOverride && isset($GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides'])) {
            $labels = $this->localizationOverride($fileReference, $languageKey, $labels);
        }

        // Save parsed data in cache
        $this->setData($fileReference, $languageKey, $labels[$languageKey] ?? []);

        // Cache processed data
        $this->systemCache->set($hash, $this->getDataByLanguage($fileReference, $languageKey));

        return $this->getData($fileReference);
    }

    /**
     * Override localization file
     *
     * This method merges the content of the override file with the default file
     */
    protected function localizationOverride(string $fileReference, string $languageKey, array $labels): array
    {
        $validOverrideFiles = [];
        $fileReferenceWithoutExtension = $this->getFileReferenceWithoutExtension($fileReference);
        $overrideFiles = $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides'];
        $supportedExtensions = $this->getSupportedExtensions();

        foreach ($supportedExtensions as $extension) {
            if (isset($overrideFiles[$languageKey][$fileReferenceWithoutExtension . '.' . $extension]) && is_array($overrideFiles[$languageKey][$fileReferenceWithoutExtension . '.' . $extension])) {
                $validOverrideFiles = array_merge($validOverrideFiles, $overrideFiles[$languageKey][$fileReferenceWithoutExtension . '.' . $extension]);
            } elseif (isset($overrideFiles[$fileReferenceWithoutExtension . '.' . $extension]) && is_array($overrideFiles[$fileReferenceWithoutExtension . '.' . $extension])) {
                $validOverrideFiles = array_merge($validOverrideFiles, $overrideFiles[$fileReferenceWithoutExtension . '.' . $extension]);
            }
        }
        foreach ($validOverrideFiles as $overrideFile) {
            $languageOverrideFileName = $overrideFile;
            if (!PathUtility::isExtensionPath($overrideFile)) {
                $languageOverrideFileName = GeneralUtility::getFileAbsFileName($overrideFile);
            }
            $parsedData = $this->getParsedData($languageOverrideFileName, $languageKey, true);
            ArrayUtility::mergeRecursiveWithOverrule($labels, $parsedData, true, false);
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
     * Get absolute file reference
     */
    protected function getAbsoluteFileReference(string $fileReference): string
    {
        $fileReferenceWithoutExtension = $this->getFileReferenceWithoutExtension($fileReference);
        $supportedExtensions = $this->getSupportedExtensions();

        foreach ($supportedExtensions as $extension) {
            $fullPath = GeneralUtility::getFileAbsFileName($fileReferenceWithoutExtension . '.' . $extension);
            if (@is_file($fullPath)) {
                return $fullPath;
            }
        }

        throw new FileNotFoundException(sprintf('Source localization file (%s) not found', $fileReference), 1306410755);
    }

    /**
     * Get file reference without extension
     */
    protected function getFileReferenceWithoutExtension(string $fileReference): string
    {
        return preg_replace('/\\.[a-z0-9]+$/i', '', $fileReference) ?? $fileReference;
    }

    /**
     * Get localized labels path pattern for extensions
     */
    protected function getLocalizedLabelsPathPattern(string $fileReference): string
    {
        if (!PathUtility::isExtensionPath($fileReference)) {
            throw new \InvalidArgumentException(sprintf('Invalid file reference configuration for the current file (%s)', $fileReference), 1635863703);
        }

        $packageKey = $this->packageManager->extractPackageKeyFromPackagePath($fileReference);
        $relativeFileName = substr($fileReference, strlen($packageKey) + 5);
        $directory = dirname($relativeFileName);
        $fileName = basename($relativeFileName);

        return sprintf(
            '/%%1$s/%s/%s%%1$s.%s',
            $packageKey,
            ($directory !== '.' ? $directory . '/' : ''),
            $fileName
        );
    }

    /**
     * Get supported extensions
     */
    protected function getSupportedExtensions(): array
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['LANG']['format']['priority']) && trim($GLOBALS['TYPO3_CONF_VARS']['LANG']['format']['priority']) !== '') {
            return GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['LANG']['format']['priority']);
        }
        return ['xlf'];
    }

    /**
     * Get the catalogue and convert to TYPO3 format
     */
    protected function loadWithSymfonyTranslator(string $fileReference, string $languageKey): array
    {
        $catalogue = $this->getMessageCatalogue($fileReference, $languageKey);
        $fallbackCatalogue = $this->getMessageCatalogue($fileReference, $languageKey, false);
        return $this->convertCatalogueToLegacyFormat($catalogue, $languageKey, $fallbackCatalogue);
    }

    /**
     * Load translations of one resource using Symfony Translator
     */
    protected function getMessageCatalogue(string $fileReference, string $locale, bool $useDefault = true): MessageCatalogueInterface
    {
        $absoluteFileReference = $this->getAbsoluteFileReference($fileReference);

        $actualSourcePath = $absoluteFileReference;
        if (PathUtility::isExtensionPath($fileReference)) {
            $actualSourcePath = $this->resolveExtensionResourcePath($absoluteFileReference, $locale, $fileReference);
        }
        $actualSourcePath = $useDefault ? $this->resolveLocalizedFilePath($actualSourcePath, $locale) : $actualSourcePath;

        // Add the resource to Symfony Translator
        // @todo: we need to be more flexible with the file ending here.
        $fileExtension = (string)pathinfo($actualSourcePath, PATHINFO_EXTENSION);
        $this->translator->addResource($fileExtension ?: 'xlf', $actualSourcePath, $locale, 'messages');
        return $this->translator->getCatalogue($locale);
    }

    /**
     * Resolve extension resource path similar to parseExtensionResource
     */
    protected function resolveExtensionResourcePath(string $sourcePath, string $languageKey, string $fileReference): string
    {
        $localizedLabelsPathPattern = $this->getLocalizedLabelsPathPattern($fileReference);
        $fileName = Environment::getLabelsPath() . sprintf($localizedLabelsPathPattern, $languageKey);

        if (@is_file($fileName)) {
            return $fileName;
        }

        // Fallback to source path if localized version doesn't exist
        return $sourcePath;
    }

    /**
     * Resolve localized file path similar to AbstractXmlParser::getLocalizedFileName
     *
     * But can also handle "de-CH.locallang.xlf" and "de_CH.locallang.xlf" - both variants.
     */
    protected function resolveLocalizedFilePath(string $sourcePath, string $languageKey): string
    {
        $possiblePrefixes = [$languageKey];
        if (str_contains($languageKey, '_')) {
            $possiblePrefixes[] = str_replace('_', '-', $languageKey);
        } elseif (str_contains($languageKey, '-')) {
            $possiblePrefixes[] = str_replace('-', '_', $languageKey);
        }

        foreach ($possiblePrefixes as $languageKey) {
            $fileName = PathUtility::basename($sourcePath);
            if (str_starts_with($fileName, $languageKey . '.')) {
                return $sourcePath;
            }

            // Try same location first
            $sameLocationPath = str_replace($fileName, $languageKey . '.' . $fileName, $sourcePath);
            if (@is_file($sameLocationPath)) {
                return $sameLocationPath;
            }

            // Try labels directory structure
            if (str_starts_with($sourcePath, Environment::getFrameworkBasePath() . '/')) {
                $validatedPrefix = Environment::getFrameworkBasePath() . '/';
            } elseif (str_starts_with($sourcePath, Environment::getExtensionsPath() . '/')) {
                $validatedPrefix = Environment::getExtensionsPath() . '/';
            } else {
                return $sourcePath;
            }

            [$extensionKey, $file_extPath] = explode('/', substr($sourcePath, strlen($validatedPrefix)), 2);
            $temp = GeneralUtility::revExplode('/', $file_extPath, 2);
            if (count($temp) === 1) {
                array_unshift($temp, '');
            }
            [$file_extPath, $file_fileName] = $temp;

            $localizedPath = Environment::getLabelsPath() . '/' . $languageKey . '/' . $extensionKey . '/' . ($file_extPath ? $file_extPath . '/' : '') . $languageKey . '.' . $file_fileName;

            if (@is_file($localizedPath)) {
                return $localizedPath;
            }
        }

        return $sourcePath;
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
                    $plurals = $this->parseIcuPlural($value);
                    $result[$languageKey][$key] = [];
                    foreach ($plurals as $index => $pluralValue) {
                        $result[$languageKey][$key][$index] = [
                            'source' => $pluralValue,
                            'target' => $pluralValue,
                        ];
                    }
                } else {
                    // Regular translation
                    $result[$languageKey][$key][0] = [
                        'source' => $value,
                        'target' => $value,
                    ];
                }
            }
        }
        foreach ($catalogue->all() as $translations) {
            foreach ($translations as $key => $value) {
                // Check if this is a plural form (contains ICU format)
                if (str_contains($value, '{0, plural,')) {
                    $plurals = $this->parseIcuPlural($value);
                    $result[$languageKey][$key] = [];
                    foreach ($plurals as $index => $pluralValue) {
                        $result[$languageKey][$key][$index] = [
                            'source' => $pluralValue, // In practice, we'd need the source from the original
                            'target' => $pluralValue,
                        ];
                    }
                } else {
                    // Regular translation
                    $result[$languageKey][$key][0] = [
                        'source' => $value ?: $fallbackCatalogue->get($key), // In practice, we'd need the source from the original
                        'target' => $value ?: $fallbackCatalogue->get($key),
                    ];
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
