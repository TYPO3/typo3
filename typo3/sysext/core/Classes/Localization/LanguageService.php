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

use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 *
 * As TYPO3 internally does not match the proper ISO locale standard, the "locale" here
 * is actually a list of supported language keys, (see Locales class), whereas "English"
 * is always the fallback ("default language").
 *
 * Further usages on setting up your own LanguageService in BE:
 *
 * ```
 * $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)
 *     ->createFromUserPreferences($GLOBALS['BE_USER']);
 * ```
 */
#[Exclude]
class LanguageService
{
    /**
     * This is set to the language which is currently running for the user
     */
    public string $lang = 'en';

    protected ?Locale $locale = null;

    /**
     * @var string[][]
     */
    protected array $labels = [];

    /**
     * @var string[][]
     */
    protected array $overrideLabels = [];

    /**
     * @internal use LanguageServiceFactory instead
     */
    public function __construct(
        protected Locales $locales,
        protected readonly LocalizationFactory $localizationFactory,
        protected readonly FrontendInterface $runtimeCache
    ) {}

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
     * Returns the label with key $index from the $LOCAL_LANG array used as the second argument
     *
     * @param string $index Label key
     * @param array $localLanguage $LOCAL_LANG array to get label key from
     */
    protected function getLLL(string $index, array $localLanguage): string
    {
        if (isset($localLanguage[$this->lang][$index])) {
            $value = is_string($localLanguage[$this->lang][$index])
                ? $localLanguage[$this->lang][$index]
                : $localLanguage[$this->lang][$index][0];
        } else {
            $value = '';
        }
        return $value;
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
     * Only the plain string contents of a language key, like "Record title: %s" are returned.
     * Placeholder interpolation must be performed separately, for example via `sprintf()`, like
     * `LocalizationUtility::translate()` does internally (which should only be used in Extbase
     * context)
     *
     * Example:
     * Label is defined in `EXT:my_ext/Resources/Private/Language/locallang.xlf` as:
     *
     * ```
     * <trans-unit id="downloaded_times">
     *     <source>downloaded %d times from %s locations</source>
     * </trans-unit>
     * ```
     *
     * The following code example assumes `$this->request` to hold the current request object.
     * There are several ways to create the LanguageService using the Factory, depending on the
     * context. Please adjust this example to your use case:
     *
     * ```
     * $language = $this->request->getAttribute('language');
     * $languageService =
     *   GeneralUtility::makeInstance(LanguageServiceFactory::class)
     *   ->createFromSiteLanguage($language);
     * $label = sprintf(
     *      $languageService->sL(
     *          'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:downloaded_times'
     *      ),
     *      27,
     *      'several'
     * );
     * ```
     *
     * This will result in `$label` to contain `'downloaded 27 times from several locations'`.
     *
     * @param string $input Label key/reference
     * @see LocalizationUtility::translate()
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

        $cacheIdentifier = 'labels_' . (string)$this->locale . '_' . md5($input);
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
        $output = '';
        $parts = explode(':', trim($restStr));
        if (isset($parts[1])) {
            $parts[0] = $extensionPrefix . $parts[0];
            $labelsFromFile = $this->readLLfile($parts[0]);
            if (is_array($this->overrideLabels[$parts[0]] ?? null)) {
                $labelsFromFile = array_replace_recursive($labelsFromFile, $this->overrideLabels[$parts[0]]);
            }
            $output = $this->getLLL($parts[1], $labelsFromFile);
        }
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
        foreach ($labelsFromFile['en'] as $key => $value) {
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

        // Get english first
        $allLabels = [
            'en' => $this->localizationFactory->getParsedData($fileRef, 'en'),
        ];
        $mainLanguageKey = $this->getTypo3LanguageKey();
        if ($mainLanguageKey !== 'en') {
            $allLabels[$mainLanguageKey] = $allLabels['en'];
            $allLocales = array_merge([$mainLanguageKey], $this->locale->getDependencies());
            $allLocales = array_unique($allLocales);
            $allLocales = array_reverse($allLocales);
            foreach ($allLocales as $locale) {
                // Merge current language labels onto labels from previous language
                // This way we have a labels with fallback applied
                $labels = $this->localizationFactory->getParsedData($fileRef, $locale);
                ArrayUtility::mergeRecursiveWithOverrule($allLabels[$mainLanguageKey], $labels, true, false);
            }
        }

        $this->runtimeCache->set($cacheIdentifier, $allLabels);
        return $allLabels;
    }

    /**
     * Define custom labels which can be overridden for a given file. This is typically
     * the case for TypoScript plugins.
     */
    public function overrideLabels(string $fileRef, array $labels): void
    {
        $localLanguage = [
            // Default is kept for fallback purposes when coming from TypoScript
            'en' => $labels['en'] ?? $labels['default'] ?? [],
        ];
        $mainLanguageKey = $this->getTypo3LanguageKey();
        if ($mainLanguageKey !== 'en') {
            // Populate the initial values with "en", if no labels for the current language are given
            $localLanguage[$mainLanguageKey] = $localLanguage['en'];
            $allLocales = array_merge([$mainLanguageKey], $this->locale->getDependencies());
            $allLocales = array_unique($allLocales);
            $allLocales = array_reverse($allLocales);
            foreach ($allLocales as $language) {
                if (isset($labels[$language])) {
                    $localLanguage[$mainLanguageKey] = array_replace_recursive($localLanguage[$mainLanguageKey], $labels[$language]);
                }
            }
        }
        $this->overrideLabels[$fileRef] = $localLanguage;
    }

    /**
     * Overwrites labels that are set via TypoScript.
     *
     * TS labels have to be configured like:
     *     plugin.tx_myextension._LOCAL_LANG.languageKey.key = value
     *
     * @internal not part of TYPO3 Core API.
     */
    public function loadTypoScriptLabelsFromExtension(string $extensionName, FrontendTypoScript $typoScript, string $pluginName = ''): array
    {
        $extensionName = str_replace('_', '', $extensionName);
        $extensionName = strtolower($extensionName);

        $allLabels = $typoScript->getSetupArray()['plugin.']['tx_' . $extensionName . '.']['_LOCAL_LANG.'] ?? [];
        if ($pluginName !== '') {
            $allLabels = array_replace_recursive(
                $allLabels,
                $typoScript->getSetupArray()['plugin.']['tx_' . $extensionName . '_' . strtolower($pluginName) . '.']['_LOCAL_LANG.'] ?? [],
            );
        }
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $allLabels = $typoScriptService->convertTypoScriptArrayToPlainArray($allLabels);
        $finalLabels = [];
        foreach ($allLabels as $languageKey => $labels) {
            foreach ($labels ?? [] as $labelKey => $labelValue) {
                if (is_string($labelValue)) {
                    $finalLabels[$languageKey][$labelKey] = $labelValue;
                } elseif (is_array($labelValue)) {
                    $labelValue = $typoScriptService->flattenTypoScriptLabelArray($labelValue, $labelKey);
                    foreach ($labelValue as $key => $value) {
                        $finalLabels[$languageKey][$key] = $value;
                    }
                }
            }
        }
        return $finalLabels;
    }

    public function getLocale(): ?Locale
    {
        return $this->locale;
    }

    private function getTypo3LanguageKey(): string
    {
        return $this->locale?->getName() ?? 'en';
    }
}
