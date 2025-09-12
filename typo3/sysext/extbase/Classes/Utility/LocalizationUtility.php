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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;

/**
 * Localization helper which should be used to fetch localized labels.
 */
class LocalizationUtility
{
    /**
     * Returns the localized label of the LOCAL_LANG key, $key.
     *
     * @param string $key The key from the LOCAL_LANG array for which to return the value.
     * @param string|null $extensionName The name of the extension
     * @param array|null $arguments The arguments of the extension, being passed over to sprintf
     * @param Locale|string|null $languageKey The language key or null for using the current language from the system
     * @return string|null The value from LOCAL_LANG or null if no translation was found.
     */
    public static function translate(string $key, ?string $extensionName = null, ?array $arguments = null, Locale|string|null $languageKey = null, ?ServerRequestInterface $request = null): ?string
    {
        if ($key === '') {
            // Early return guard: returns null if the key was empty, because the key may be a dynamic value
            // (from for example Fluid). Returning null allows null coalescing to a default value when that happens.
            return null;
        }

        // Validate extension name for plain keys
        if (!str_starts_with($key, 'LLL:') && empty($extensionName)) {
            throw new \InvalidArgumentException(
                'Parameter $extensionName cannot be empty if a fully-qualified key is not specified.',
                1498144052
            );
        }
        if (str_starts_with($key, 'LLL:')) {
            $keyParts = explode(':', $key);
            unset($keyParts[0]);
            $key = array_pop($keyParts);
            $languageFilePath = implode(':', $keyParts);
        } else {
            $languageFilePath = static::getLanguageFilePath($extensionName);
        }
        $locale = self::getLocale($languageKey);
        $languageService = self::buildLanguageService($locale, $languageFilePath);
        $overrideLabels = [];
        $request = $request ?? $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!empty($extensionName) && $request instanceof ServerRequestInterface) {
            $typoScript = $request->getAttribute('frontend.typoscript');
            if ($typoScript instanceof FrontendTypoScript) {
                // Loads local-language values by looking for a "locallang.xlf" file in the plugin resources directory and if found includes it.
                // Locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.xlf" file.
                $overrideLabels = $languageService->loadTypoScriptLabelsFromExtension($extensionName, $typoScript, self::getPluginName($request));
                if ($overrideLabels !== []) {
                    $languageService->overrideLabels($languageFilePath, $overrideLabels);
                }
            }
        }

        $resolvedLabel = $languageService->sL('LLL:' . $languageFilePath . ':' . $key);
        $value = $resolvedLabel !== '' ? $resolvedLabel : null;

        // Check if a value was explicitly set to "" via TypoScript, if so, we need to ensure that this is "" and not null
        if ($overrideLabels !== []) {
            $languageKey = $locale->getName();
            // @todo: probably cannot handle "de-DE" and "de" fallbacks
            if ($value === null && isset($overrideLabels[$languageKey][$key])) {
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
        return 'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/Resources/Private/Language/locallang.xlf';
    }

    /**
     * Resolves the currently active locale.
     * Using the Locales factory, as it handles dependencies (e.g. "de-AT" falls back to "de").
     */
    protected static function getLocale(Locale|string|null $localeOrLanguageKey): Locale
    {
        if ($localeOrLanguageKey instanceof Locale) {
            return $localeOrLanguageKey;
        }
        $localeFactory = GeneralUtility::makeInstance(Locales::class);
        if (is_string($localeOrLanguageKey)) {
            return $localeFactory->createLocale($localeOrLanguageKey);
        }
        return $localeFactory->createLocaleFromRequest($GLOBALS['TYPO3_REQUEST'] ?? null);
    }

    /**
     * Allow plugin.tx_myextension._LOCAL_LANG and plugin.tx_myextension_myplugin._LOCAL_LANG
     */
    protected static function getPluginName(?ServerRequestInterface $request = null): string
    {
        $request = $request ?? $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request instanceof Request) {
            return strtolower($request->getPluginName());
        }
        return '';
    }

    protected static function getRuntimeCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }
}
