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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackagePathException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Provides a language parser factory.
 */
class LocalizationFactory implements SingletonInterface
{
    /**
     * @var FrontendInterface
     */
    protected $cacheInstance;

    /**
     * @var \TYPO3\CMS\Core\Localization\LanguageStore
     */
    public $store;

    public function __construct(LanguageStore $languageStore, CacheManager $cacheManager)
    {
        $this->store = $languageStore;
        $this->cacheInstance = $cacheManager->getCache('l10n');
    }

    /**
     * Returns parsed data from a given file and language key.
     *
     * @param string $fileReference Input is a file-reference (see \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName). That file is expected to be a supported locallang file format
     * @param string $languageKey Language key
     * @param null $_ unused
     * @param null $__ unused
     * @param bool $isLocalizationOverride TRUE if $fileReference is a localization override
     *
     * @return array<string, array<string, array<int, array<string, string>>>>
     */
    public function getParsedData($fileReference, $languageKey, $_ = null, $__ = null, $isLocalizationOverride = false)
    {
        $hash = md5($fileReference . $languageKey);

        // Check if the default language is processed before processing other language
        if (!$this->store->hasData($fileReference, 'default') && $languageKey !== 'default') {
            $this->getParsedData($fileReference, 'default');
        }
        // If the content is parsed (local cache), use it
        if ($this->store->hasData($fileReference, $languageKey)) {
            return $this->store->getData($fileReference);
        }

        // If the content is in cache (system cache), use it
        $data = $this->cacheInstance->get($hash);
        if ($data !== false) {
            $this->store->setData($fileReference, $languageKey, $data);
            return $this->store->getData($fileReference);
        }

        try {
            $this->store->setConfiguration($fileReference, $languageKey);
            $parser = $this->store->getParserInstance($fileReference);
            if (is_callable([$parser, 'parseExtensionResource']) && PathUtility::isExtensionPath($fileReference)) {
                $LOCAL_LANG = $parser->parseExtensionResource($this->store->getAbsoluteFileReference($fileReference), $languageKey, $this->store->getLocalizedLabelsPathPattern($fileReference));
            } else {
                // @todo: this (providing an absolute file system path) likely does not work properly anyway in all cases and should rather be deprecated
                $LOCAL_LANG = $parser->getParsedData($this->store->getAbsoluteFileReference($fileReference), $languageKey);
            }
        } catch (FileNotFoundException | UnknownPackagePathException $exception) {
            // Source localization file not found, set empty data as there could be an override
            $this->store->setData($fileReference, $languageKey, []);
            $LOCAL_LANG = $this->store->getData($fileReference);
        }

        // Override localization
        if (!$isLocalizationOverride && isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'])) {
            $this->localizationOverride($fileReference, $languageKey, $LOCAL_LANG);
        }

        // Save parsed data in cache
        $this->store->setData($fileReference, $languageKey, $LOCAL_LANG[$languageKey]);

        // Cache processed data
        $this->cacheInstance->set($hash, $this->store->getDataByLanguage($fileReference, $languageKey));

        return $this->store->getData($fileReference);
    }

    /**
     * Override localization file
     *
     * This method merges the content of the override file with the default file
     *
     * @param string $fileReference
     * @param string $languageKey
     * @param array $LOCAL_LANG
     */
    protected function localizationOverride($fileReference, $languageKey, array &$LOCAL_LANG)
    {
        $overrides = [];
        $fileReferenceWithoutExtension = $this->store->getFileReferenceWithoutExtension($fileReference);
        $locallangXMLOverride = $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'];
        foreach ($this->store->getSupportedExtensions() as $extension) {
            if (isset($locallangXMLOverride[$languageKey][$fileReferenceWithoutExtension . '.' . $extension]) && is_array($locallangXMLOverride[$languageKey][$fileReferenceWithoutExtension . '.' . $extension])) {
                $overrides = array_merge($overrides, $locallangXMLOverride[$languageKey][$fileReferenceWithoutExtension . '.' . $extension]);
            } elseif (isset($locallangXMLOverride[$fileReferenceWithoutExtension . '.' . $extension]) && is_array($locallangXMLOverride[$fileReferenceWithoutExtension . '.' . $extension])) {
                $overrides = array_merge($overrides, $locallangXMLOverride[$fileReferenceWithoutExtension . '.' . $extension]);
            }
        }
        if (!empty($overrides)) {
            foreach ($overrides as $overrideFile) {
                $languageOverrideFileName = $overrideFile;
                if (!PathUtility::isExtensionPath($overrideFile)) {
                    $languageOverrideFileName = GeneralUtility::getFileAbsFileName($overrideFile);
                }
                $parsedData = $this->getParsedData($languageOverrideFileName, $languageKey, null, null, true);
                if (is_array($parsedData)) {
                    ArrayUtility::mergeRecursiveWithOverrule($LOCAL_LANG, $parsedData);
                }
            }
        }
    }
}
