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

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Main API to fetch labels from XLF (label files) based on the current system
 * language of TYPO3. It is able to resolve references to files + their pointers to the
 * proper language. If you see something about "LLL", this class does the trick for you. It
 * is not related to language handling of content, but rather of labels for plugins.
 *
 * Usually this is injected into $GLOBALS['LANG'] when in backend or CLI context, and
 * populated by the current backend user. Do not rely on $GLOBAL['LANG'] in frontend, as it is only
 * available under certain circumstances!
 * In frontend, this is also used to translate "labels", see TypoScriptFrontendController->sL()
 * for that.
 *
 * As TYPO3 internally does not match the proper ISO locale standard, the "locale" here
 * is actually a list of supported language keys, (see Locales class), whereas "English"
 * has the language key "default".
 *
 * Further usages on setting up your own LanguageService in BE:
 *
 * ```
 * $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)
 *     ->createFromUserPreferences($GLOBALS['BE_USER']);
 * ```
 */
class LanguageService
{
    /**
     * This is set to the language that is currently running for the user
     */
    public string $lang = 'default';

    protected ?Locale $locale = null;

    /**
     * If true, will show the key/location of labels in the backend.
     *
     * @deprecated since TYPO3 v12.4. will be removed in TYPO3 v13.0.
     */
    public bool $debugKey = false;

    /**
     * @var string[][]
     */
    protected array $labels = [];

    /**
     * @var string[][]
     */
    protected array $overrideLabels = [];

    protected Locales $locales;
    protected LocalizationFactory $localizationFactory;
    protected FrontendInterface $runtimeCache;

    /**
     * @internal use LanguageServiceFactory instead
     */
    public function __construct(Locales $locales, LocalizationFactory $localizationFactory, FrontendInterface $runtimeCache)
    {
        $this->locales = $locales;
        $this->localizationFactory = $localizationFactory;
        $this->runtimeCache = $runtimeCache;
        // @deprecated since TYPO3 v12.4. will be removed in TYPO3 v13.0.
        //             Remove together with code in LanguageServiceFactory, clean up
        //             DefaultConfiguration and DefaultConfigurationDescription.yaml,
        //             remove debugLL below, clean up SilentConfigurationUpgradeService
        //             to remove option, remove migrateLangDebug(), use some different
        //             toggle in SettingsCest.
        $this->debugKey = (bool)$GLOBALS['TYPO3_CONF_VARS']['BE']['languageDebug'];
    }

    /**
     * Initializes the language to fetch XLF labels for.
     *
     * ```
     * $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)
     *     ->createFromUserPreferences($GLOBALS['BE_USER']);
     * ```
     *
     * @throws \RuntimeException
     * @param Locale|string $languageKey The language key (two character string from backend users profile)
     * @internal use one of the factory methods instead
     */
    public function init(Locale|string $languageKey): void
    {
        if ($languageKey instanceof Locale) {
            $this->locale = $languageKey;
        } else {
            $this->locale = $this->locales->createLocale($languageKey);
        }
        $this->lang = $this->getTypo3LanguageKey();
    }

    /**
     * Debugs the localization key.
     *
     * @param string $labelIdentifier to be shown next to the value
     * @deprecated since TYPO3 v12.4. will be removed in TYPO3 v13.0.
     */
    protected function debugLL(string $labelIdentifier): string
    {
        return $this->debugKey ? '[' . $labelIdentifier . ']' : '';
    }

    /**
     * Returns the label with key $index from the globally loaded $LOCAL_LANG array.
     * Mostly used from modules with only one LOCAL_LANG file loaded into the global space.
     *
     * @param string $index Label key
     * @return string
     * @deprecated will be removed in TYPO3 v13.0. Use sL() instead.
     */
    public function getLL($index)
    {
        trigger_error('Calling LanguageService->getLL() will be removed in TYPO3 v13.0. Use LanguageService->sL() instead.', E_USER_DEPRECATED);
        return $this->getLLL($index, $this->labels);
    }

    /**
     * Returns the label with key $index from the $LOCAL_LANG array used as the second argument
     *
     * @param string $index Label key
     * @param array $localLanguage $LOCAL_LANG array to get label key from
     * @return string
     */
    protected function getLLL(string $index, array $localLanguage): string
    {
        if (isset($localLanguage[$this->lang][$index])) {
            $value = is_string($localLanguage[$this->lang][$index])
                ? $localLanguage[$this->lang][$index]
                : $localLanguage[$this->lang][$index][0]['target'];
        } elseif (isset($localLanguage['default'][$index])) {
            $value = is_string($localLanguage['default'][$index])
                ? $localLanguage['default'][$index]
                : $localLanguage['default'][$index][0]['target'];
        } else {
            $value = '';
        }
        // @deprecated since TYPO3 v12.4. will be removed in TYPO3 v13.0. Skip calling debugLL().
        return $value . $this->debugLL($index);
    }

    /**
     * Main and most often used method.
     *
     * Resolve strings like these:
     *
     * ```
     * 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'
     * ```
     *
     * This looks up the given .xlf file path in the 'core' extension for label labels.depth_0
     *
     * @param string $input Label key/reference
     */
    public function sL($input): string
    {
        $input = (string)$input;
        // early return for empty input to avoid cache and language file reading on first hit.
        if ($input === '') {
            return $input;
        }
        // Use a constant non-localizable label
        if (!str_starts_with(trim($input), 'LLL:')) {
            return $input;
        }

        // @deprecated since TYPO3 v12.4. will be removed in TYPO3 v13.0. Remove $this->debugKey handling.
        $cacheIdentifier = 'labels_' . (string)$this->locale . '_' . md5($input . '_' . (int)$this->debugKey);
        $cacheEntry = $this->runtimeCache->get($cacheIdentifier);
        if ($cacheEntry !== false) {
            return $cacheEntry;
        }
        // Remove the LLL: prefix
        $restStr = substr(trim($input), 4);
        $extensionPrefix = '';
        // ll-file referred to is found in an extension
        if (PathUtility::isExtensionPath(trim($restStr))) {
            $restStr = substr(trim($restStr), 4);
            $extensionPrefix = 'EXT:';
        }
        $parts = explode(':', trim($restStr));
        $parts[0] = $extensionPrefix . $parts[0];
        $labelsFromFile = $this->readLLfile($parts[0]);
        if (is_array($this->overrideLabels[$parts[0]] ?? null)) {
            $labelsFromFile = array_replace_recursive($labelsFromFile, $this->overrideLabels[$parts[0]]);
        }
        $output = $this->getLLL($parts[1] ?? '', $labelsFromFile);
        // @deprecated since TYPO3 v12.4. will be removed in TYPO3 v13.0. Remove line.
        $output .= $this->debugLL($input);
        $this->runtimeCache->set($cacheIdentifier, $output);
        return $output;
    }

    /**
     * Includes locallang file (and possibly additional localized version, if configured for)
     * Read language labels will be merged with $LOCAL_LANG.
     *
     * @param string $fileRef $fileRef is a file-reference
     * @return array returns the loaded label file
     * @internal do not rely on this method as it is only used for internal purposes in TYPO3 v13.0.
     */
    public function includeLLFile(string $fileRef): array
    {
        $localLanguage = $this->readLLfile($fileRef);
        if (!empty($localLanguage)) {
            $this->labels = array_replace_recursive($this->labels, $localLanguage);
        }
        return $localLanguage;
    }

    /**
     * Translates prepared labels which are handed in, and also uses the fallback if no language is given.
     * This is common in situations such as page TSconfig where labels or references to labels are used.
     * @internal not part of TYPO3 Core API for the time being.
     */
    public function translateLabel(array|string $input, string $fallback): string
    {
        if (is_array($input) && isset($input[$this->lang])) {
            return $this->sL((string)$input[$this->lang]);
        }
        if (is_string($input)) {
            return $this->sL($input);
        }
        return $this->sL($fallback);
    }

    /**
     * Load all labels from a resource/file and returns them in a translated fashion.
     * @return array<string, string>
     * @internal not part of TYPO3 Core API for the time being.
     */
    public function getLabelsFromResource(string $fileRef): array
    {
        $labelArray = [];
        $labelsFromFile = $this->readLLfile($fileRef);
        foreach ($labelsFromFile['default'] as $key => $value) {
            $labelArray[$key] = $this->getLLL($key, $labelsFromFile);
        }
        return $labelArray;
    }

    /**
     * Includes a locallang file and returns the $LOCAL_LANG array found inside.
     *
     * @param string $fileRef Input is a file-reference to be a 'local_lang' file containing a $LOCAL_LANG array
     * @return array value of $LOCAL_LANG found in the included file, empty if none found
     */
    protected function readLLfile(string $fileRef): array
    {
        $cacheIdentifier = 'labels_file_' . md5($fileRef . (string)$this->locale);
        $cacheEntry = $this->runtimeCache->get($cacheIdentifier);
        if (is_array($cacheEntry)) {
            return $cacheEntry;
        }

        $mainLanguageKey = $this->getTypo3LanguageKey();
        $localLanguage = [];
        $allLocales = array_merge([$mainLanguageKey], $this->locale->getDependencies());
        $allLocales = array_reverse($allLocales);
        foreach ($allLocales as $locale) {
            $tempLL = $this->localizationFactory->getParsedData($fileRef, $locale);
            $localLanguage['default'] = $tempLL['default'];
            if (!isset($localLanguage[$mainLanguageKey])) {
                $localLanguage[$mainLanguageKey] = $localLanguage['default'];
            }
            if ($mainLanguageKey !== 'default') {
                // Fallback as long as TYPO3 supports "da_DK" and "da-DK"
                if ((!isset($tempLL[$locale]) || $tempLL[$locale] === []) && str_contains($locale, '-')) {
                    $underscoredLocale = str_replace('-', '_', $locale);
                    $tempLL = $this->localizationFactory->getParsedData($fileRef, $underscoredLocale);
                    if (isset($tempLL[$underscoredLocale])) {
                        $tempLL[$locale] = $tempLL[$underscoredLocale];
                    }
                }
                if (isset($tempLL[$locale])) {
                    // Merge current language labels onto labels from previous language
                    // This way we have a labels with fall back applied
                    ArrayUtility::mergeRecursiveWithOverrule($localLanguage[$mainLanguageKey], $tempLL[$locale], true, false);
                }
            }
        }

        $this->runtimeCache->set($cacheIdentifier, $localLanguage);
        return $localLanguage;
    }

    /**
     * Define custom labels which can be overridden for a given file. This is typically
     * the case for TypoScript plugins.
     */
    public function overrideLabels(string $fileRef, array $labels): void
    {
        $localLanguage = [
            'default' => $labels['default'] ?? [],
        ];
        $mainLanguageKey = $this->getTypo3LanguageKey();
        if ($mainLanguageKey !== 'default') {
            $allLocales = array_merge([$mainLanguageKey], $this->locale->getDependencies());
            $allLocales = array_reverse($allLocales);
            foreach ($allLocales as $language) {
                // Populate the initial values with default, if no labels for the current language are given
                if (!isset($localLanguage[$mainLanguageKey])) {
                    $localLanguage[$mainLanguageKey] = $localLanguage['default'];
                }
                if (isset($labels[$language])) {
                    $localLanguage[$mainLanguageKey] = array_replace_recursive($localLanguage[$mainLanguageKey], $labels[$language]);
                }
            }
        }
        $this->overrideLabels[$fileRef] = $localLanguage;
    }

    public function getLocale(): ?Locale
    {
        return $this->locale;
    }

    private function getTypo3LanguageKey(): string
    {
        if ($this->locale === null) {
            return 'default';
        }
        if ($this->locale->getName() === 'en') {
            return 'default';
        }
        return $this->locale->getName();
    }
}
