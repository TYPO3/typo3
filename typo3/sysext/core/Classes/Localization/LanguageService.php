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
 * is not related for language handling of content, but rather of labels for plugins.
 *
 * Usually this is injected into $GLOBALS['LANG'] when in backend or CLI context, and
 * populated by the current backend user. Do not rely on $GLOBAL['LANG'] in frontend, as it is only
 * available in certain circumstances!
 * In Frontend, this is also used to translate "labels", see TypoScriptFrontendController->sL()
 * for that.
 *
 * As TYPO3 internally does not match the proper ISO locale standard, the "locale" here
 * is actually a list of supported language keys, (see Locales class), whereas "English"
 * has the language key "default".
 *
 * Further usages on setting up your own LanguageService in BE:
 * $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)
 *   ->createFromUserPreferences($GLOBALS['BE_USER']);
 */
class LanguageService
{
    /**
     * This is set to the language that is currently running for the user
     */
    public string $lang = 'default';

    /**
     * If TRUE, will show the key/location of labels in the backend.
     */
    public bool $debugKey = false;

    /**
     * List of language dependencies for an actual language. This setting is used for local variants of a language
     * that depend on their "main" language, like Brazilian Portuguese or Canadian French.
     *
     * @var array<int, string>
     */
    protected array $languageDependencies = [];

    /**
     * @var string[][]
     */
    protected array $labels = [];

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
     * @param string $languageKey The language key (two character string from backend users profile)
     * @internal use one of the factory methods instead
     */
    public function init(string $languageKey): void
    {
        // Find the requested language in this list based on the $languageKey
        // Language is found. Configure it:
        if ($this->locales->isValidLanguageKey($languageKey)) {
            // The current language key
            $this->lang = $languageKey;
            $this->languageDependencies = array_merge([$languageKey], $this->locales->getLocaleDependencies($languageKey));
            $this->languageDependencies = array_reverse($this->languageDependencies);
        }
    }

    /**
     * Debugs the localization key.
     *
     * @param string $labelIdentifier to be shown next to the value
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
     */
    public function getLL($index)
    {
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
        // Get Local Language. Special handling for all extensions that
        // read PHP LL files and pass arrays here directly.
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
        return $value . $this->debugLL($index);
    }

    /**
     * Main and most often used method.
     *
     * Resolve strings like these:
     *
     * ```
     *      'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'
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

        $cacheIdentifier = 'labels_' . $this->lang . '_' . md5($input . '_' . (int)$this->debugKey);
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
        $output = $this->getLLL($parts[1] ?? '', $this->readLLfile($parts[0]));
        $output .= $this->debugLL($input);
        $this->runtimeCache->set($cacheIdentifier, $output);
        return $output;
    }

    /**
     * Includes locallang file (and possibly additional localized version if configured for)
     * Read language labels will be merged with $LOCAL_LANG (if $setGlobal === true).
     *
     * @param string $fileRef $fileRef is a file-reference
     * @return array returns the loaded label file
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
     * Includes a locallang file and returns the $LOCAL_LANG array found inside.
     *
     * @param string $fileRef Input is a file-reference to be a 'local_lang' file containing a $LOCAL_LANG array
     * @return array value of $LOCAL_LANG found in the included file, empty if none found
     */
    protected function readLLfile(string $fileRef): array
    {
        $cacheIdentifier = 'labels_file_' . md5($fileRef . $this->lang);
        $cacheEntry = $this->runtimeCache->get($cacheIdentifier);
        if (is_array($cacheEntry)) {
            return $cacheEntry;
        }

        $languages = $this->lang === 'default' ? ['default'] : $this->languageDependencies;
        $localLanguage = [];
        foreach ($languages as $language) {
            $tempLL = $this->localizationFactory->getParsedData($fileRef, $language);
            $localLanguage['default'] = $tempLL['default'];
            if (!isset($localLanguage[$this->lang])) {
                $localLanguage[$this->lang] = $localLanguage['default'];
            }
            if ($this->lang !== 'default' && isset($tempLL[$language])) {
                // Merge current language labels onto labels from previous language
                // This way we have a labels with fall back applied
                ArrayUtility::mergeRecursiveWithOverrule($localLanguage[$this->lang], $tempLL[$language], true, false);
            }
        }

        $this->runtimeCache->set($cacheIdentifier, $localLanguage);
        return $localLanguage;
    }
}
