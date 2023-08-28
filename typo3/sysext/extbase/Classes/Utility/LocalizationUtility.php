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

namespace TYPO3\CMS\Extbase\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Localization helper which should be used to fetch localized labels.
 */
class LocalizationUtility
{
    protected static string $locallangPath = 'Resources/Private/Language/';

    /**
     * Returns the localized label of the LOCAL_LANG key, $key.
     *
     * @param string $key The key from the LOCAL_LANG array for which to return the value.
     * @param string|null $extensionName The name of the extension
     * @param array|null $arguments The arguments of the extension, being passed over to sprintf
     * @param Locale|string|null $languageKey The language key or null for using the current language from the system
     * @param string[]|null $alternativeLanguageKeys The alternative language keys if no translation was found. @deprecated will be removed in TYPO3 v13.0
     * @return string|null The value from LOCAL_LANG or null if no translation was found.
     */
    public static function translate(string $key, ?string $extensionName = null, array $arguments = null, Locale|string $languageKey = null, array $alternativeLanguageKeys = null): ?string
    {
        if ($key === '') {
            // Early return guard: returns null if the key was empty, because the key may be a dynamic value
            // (from for example Fluid). Returning null allows null coalescing to a default value when that happens.
            return null;
        }
        if (str_starts_with($key, 'LLL:')) {
            $keyParts = explode(':', $key);
            unset($keyParts[0]);
            $key = array_pop($keyParts);
            $languageFilePath = implode(':', $keyParts);
        } else {
            if (empty($extensionName)) {
                throw new \InvalidArgumentException(
                    'Parameter $extensionName cannot be empty if a fully-qualified key is not specified.',
                    1498144052
                );
            }
            $languageFilePath = static::getLanguageFilePath($extensionName);
        }
        if ($alternativeLanguageKeys !== null && $alternativeLanguageKeys !== []) {
            trigger_error('Calling LocalizationUtility::translate() with the argument $alternativeLanguageKeys will be removed in TYPO3 v13.0. Use Locales instead.', E_USER_DEPRECATED);
        }
        $locale = self::getLocale($languageKey, $alternativeLanguageKeys);
        $languageService = static::initializeLocalization($languageFilePath, $locale, $extensionName);
        $resolvedLabel = $languageService->sL('LLL:' . $languageFilePath . ':' . $key);
        $value = $resolvedLabel !== '' ? $resolvedLabel : null;

        // Check if a value was explicitly set to "" via TypoScript, if so, we need to ensure that this is "" and not null
        if ($extensionName) {
            $overrideLabels = static::loadTypoScriptLabels($extensionName);
            $languageKey = $locale->getName();
            // @todo: probably cannot handle "de-DE" and "de" fallbacks
            if ($value === null && isset($overrideLabels[$languageKey])) {
                $value = '';
            }
        }

        if (is_array($arguments) && $arguments !== [] && $value !== null) {
            // This unrolls arguments from $arguments - instead of calling vsprintf which receives arguments as an array.
            // The reason is that only sprintf() will return an error message if the number of arguments does not match
            // the number of placeholders in the format string. Whereas, vsprintf would silently return nothing.
            return sprintf($value, ...array_values($arguments)) ?: sprintf('Error: could not translate key "%s" with value "%s" and %d argument(s)!', $key, $value, count($arguments));
        }
        return $value;
    }

    /**
     * Loads local-language values by looking for a "locallang.xlf" file in the plugin resources directory and if found includes it.
     * Locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.xlf" file.
     */
    protected static function initializeLocalization(string $languageFilePath, Locale $locale, ?string $extensionName): LanguageService
    {
        $languageService = self::buildLanguageService($locale, $languageFilePath);
        if (!empty($extensionName)) {
            $overrideLabels = static::loadTypoScriptLabels($extensionName);
            if ($overrideLabels !== []) {
                $languageService->overrideLabels($languageFilePath, $overrideLabels);
            }
        }
        return $languageService;
    }

    protected static function buildLanguageService(Locale $locale, string $languageFilePath): LanguageService
    {
        $languageKeyHash = sha1(json_encode(array_merge([(string)$locale], $locale->getDependencies(), [$languageFilePath])));
        $cache = self::getRuntimeCache();
        if (!$cache->get($languageKeyHash)) {
            $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create($locale);
            $languageService->includeLLFile($languageFilePath);
            $cache->set($languageKeyHash, $languageService);
        }
        return $cache->get($languageKeyHash);
    }

    /**
     * Returns the default path and filename for an extension
     */
    protected static function getLanguageFilePath(string $extensionName): string
    {
        return 'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/' . self::$locallangPath . 'locallang.xlf';
    }

    /**
     * Resolves the currently active locale.
     * Using the Locales factory, as it handles dependencies (e.g. "de-AT" falls back to "de").
     */
    protected static function getLocale(Locale|string|null $localeOrLanguageKey, ?array $alternativeLanguageKeys): Locale
    {
        $localeFactory = GeneralUtility::makeInstance(Locales::class);
        if ($localeOrLanguageKey instanceof Locale) {
            $locale = $localeOrLanguageKey;
        } elseif (is_string($localeOrLanguageKey) && $localeOrLanguageKey !== '') {
            $locale = $localeFactory->createLocale($localeOrLanguageKey);
        } else {
            $locale = $localeFactory->createLocaleFromRequest($GLOBALS['TYPO3_REQUEST'] ?? null);
        }
        if (!empty($alternativeLanguageKeys)) {
            $locale->setDependencies($alternativeLanguageKeys);
        }
        return $locale;
    }

    /**
     * Overwrites labels that are set via TypoScript.
     * TS labels have to be configured like:
     *     plugin.tx_myextension._LOCAL_LANG.languageKey.key = value
     */
    protected static function loadTypoScriptLabels(string $extensionName): array
    {
        // Only allow overrides in Frontend Context
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface || !ApplicationType::fromRequest($request)->isFrontend()) {
            return [];
        }
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $frameworkConfiguration = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $extensionName);
        if (!is_array($frameworkConfiguration['_LOCAL_LANG'] ?? false)) {
            return [];
        }
        $finalLabels = [];
        foreach ($frameworkConfiguration['_LOCAL_LANG'] as $languageKey => $labels) {
            if (!is_array($labels)) {
                continue;
            }
            foreach ($labels as $labelKey => $labelValue) {
                if (is_string($labelValue)) {
                    $finalLabels[$languageKey][$labelKey] = $labelValue;
                } elseif (is_array($labelValue)) {
                    $labelValue = self::flattenTypoScriptLabelArray($labelValue, $labelKey);
                    foreach ($labelValue as $key => $value) {
                        $finalLabels[$languageKey][$key] = $value;
                    }
                }
            }
        }
        return $finalLabels;
    }

    /**
     * Flatten TypoScript label array; converting a hierarchical array into a flat
     * array with the keys separated by dots.
     *
     * Example Input:  array('k1' => array('subkey1' => 'val1'))
     * Example Output: array('k1.subkey1' => 'val1')
     *
     * @param array $labelValues Hierarchical array of labels
     * @param string $parentKey the name of the parent key in the recursion; is only needed for recursion.
     * @return array flattened array of labels.
     */
    protected static function flattenTypoScriptLabelArray(array $labelValues, string $parentKey = ''): array
    {
        $result = [];
        foreach ($labelValues as $key => $labelValue) {
            if (!empty($parentKey)) {
                if ($key === '_typoScriptNodeValue') {
                    $key = $parentKey;
                } else {
                    $key = $parentKey . '.' . $key;
                }
            }
            if (is_array($labelValue)) {
                $labelValue = self::flattenTypoScriptLabelArray($labelValue, $key);
                $result = array_merge($result, $labelValue);
            } else {
                $result[$key] = $labelValue;
            }
        }
        return $result;
    }

    protected static function getRuntimeCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }
}
