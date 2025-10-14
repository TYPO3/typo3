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
    protected function getLLL(string $index, array $localLanguage, bool $returnNullIfNotSet = false): ?string
    {
        if (isset($localLanguage[$this->lang][$index])) {
            $value = is_string($localLanguage[$this->lang][$index])
                ? $localLanguage[$this->lang][$index]
                : $localLanguage[$this->lang][$index][0];
        } else {
            $value = $returnNullIfNotSet ? null : '';
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
     * 'LLL:core.messages:labels.depth_0'
     * 'core.messages:labels.depth_0'  // LLL: prefix is optional
     * ```
     *
     * This looks up the given .xlf file path or translation domain in the 'core' extension for label labels.depth_0
     *
     * The LLL: prefix is optional. If the input contains a colon (:), it will be treated as a label reference.
     * If no colon is found, the input string is returned as-is (constant non-localizable label).
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

        $trimmedInput = trim($input);
        $hasLLLPrefix = str_starts_with($trimmedInput, 'LLL:');
        $restStr = $trimmedInput;

        // Remove the LLL: prefix if present
        if ($hasLLLPrefix) {
            $restStr = substr($trimmedInput, 4);
        }

        $extensionPrefix = '';
        // Check if ll-file is referred to by extension path (EXT:)
        if (PathUtility::isExtensionPath(trim($restStr))) {
            $restStr = substr(trim($restStr), 4);
            $extensionPrefix = 'EXT:';
        }

        $parts = explode(':', trim($restStr), 2);
        if (isset($parts[1])) {
            // Handle both domain references and file paths
            if ($extensionPrefix === '') {
                // This could be a domain reference (e.g., "core.tabs:general")
                // The file path resolution happens in LocalizationFactory
                $fileReference = $parts[0];
            } else {
                // Traditional EXT: file path
                $fileReference = $extensionPrefix . $parts[0];
            }
            $result = (string)$this->translate($parts[1], $fileReference);
            if ($hasLLLPrefix) {
                return $result;
            }
            // If LLL: prefix was not used, we return the input as-is if no translation was found
            return $result !== '' ? $result : $input;

        }

        // No colon found
        // If LLL: prefix was used, return empty string (original behavior for invalid references)
        // Otherwise, return input as-is (constant non-localizable label)
        return $hasLLLPrefix ? '' : $input;
    }

    /**
     * This is different from sL() as it can also return null, and expects a domain (can be a file reference as well),
     * NULL is returned then the "id" is wrong.
     *
     * @internal for the time being, there might be $locale added a later point, as well as $quantity and $defaultValue
     */
    public function translate(string $id, string $domain, array $arguments = []): ?string
    {
        $cacheIdentifier = 'labels_' . (string)$this->locale . '_' . md5($domain . ':' . $id);
        $result = $this->runtimeCache->get($cacheIdentifier);
        if (!is_string($result) && !is_null($result)) {
            $labelsFromDomain = $this->readLLfile($domain);
            if (is_array($this->overrideLabels[$domain] ?? null)) {
                $labelsFromDomain = array_replace_recursive($labelsFromDomain, $this->overrideLabels[$domain]);
            }
            $result = $this->getLLL($id, $labelsFromDomain, true);
            // Check if a value was explicitly set to "" via TypoScript, if so, we need to ensure that this is "" and not null
            if (isset($this->overrideLabels[$domain][$id]) && $this->overrideLabels[$domain][$id] === '') {
                $result = '';
            }
            $this->runtimeCache->set($cacheIdentifier, $result);
        }
        if ($result === '' || $result === null) {
            return $result;
        }
        if ($arguments !== []) {
            try {
                // We use vsprintf() over sprintf() here on purpose.
                // The reason is that only sprintf() will return an error message if the number of arguments does not match
                // the number of placeholders in the format string. Whereas, vsprintf would silently return nothing.
                return vsprintf($result, array_values($arguments));
            } catch (\ValueError $e) {
                // @todo: we could at some point add a logger or a custom exception if needed, and hand over the $result differently
                return new \ValueError($result, 123, $e);
            }
        }
        return $result;
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
    public function getLabelsFromResource(string $fileReferenceOrDomain): array
    {
        $labelArray = [];
        $labelsFromFile = $this->readLLfile($fileReferenceOrDomain);
        foreach ($labelsFromFile['en'] as $key => $value) {
            $labelArray[$key] = $this->getLLL($key, $labelsFromFile);
        }
        return $labelArray;
    }

    /**
     * Includes a locallang file and returns the labels found inside.
     *
     * @param string $fileReferenceOrDomain Input is a file-reference to be a 'local_lang' file containing a $LOCAL_LANG array
     * @return array value of $LOCAL_LANG found in the included file, empty if none found
     */
    protected function readLLfile(string $fileReferenceOrDomain): array
    {
        // Translate a possible domain into a fileReference
        $cacheIdentifier = 'labels_file_' . md5($fileReferenceOrDomain . (string)$this->locale);
        $cacheEntry = $this->runtimeCache->get($cacheIdentifier);
        if (is_array($cacheEntry)) {
            return $cacheEntry;
        }

        // Get english first
        $allLabels = [
            'en' => $this->localizationFactory->getParsedData($fileReferenceOrDomain, 'en'),
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
                $labels = $this->localizationFactory->getParsedData($fileReferenceOrDomain, $locale);
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
