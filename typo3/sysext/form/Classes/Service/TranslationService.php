<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Service;

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

use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Advanced translations
 * This class is subjected to change.
 * **Do NOT subclass**
 *
 * Scope: frontend / backend
 * @internal
 */
class TranslationService implements SingletonInterface
{

    /**
     * Local Language content
     *
     * @var array
     */
    protected $LOCAL_LANG = [];

    /**
     * Contains those LL keys, which have been set to (empty) in TypoScript.
     * This is necessary, as we cannot distinguish between a nonexisting
     * translation and a label that has been cleared by TS.
     * In both cases ['key'][0]['target'] is "".
     *
     * @var array
     */
    protected $LOCAL_LANG_UNSET = [];

    /**
     * Key of the language to use
     *
     * @var string
     */
    protected $languageKey = null;

    /**
     * Pointer to alternative fall-back language to use
     *
     * @var array
     */
    protected $alternativeLanguageKeys = [];

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager = null;

    /**
     * Return TranslationService as singleton
     *
     * @return TranslationService
     * @internal
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get(self::class);
    }

    /**
     * Returns the localized label of the LOCAL_LANG key, $key.
     *
     * @param mixed $key The key from the LOCAL_LANG array for which to return the value.
     * @param array $arguments the arguments of the extension, being passed over to vsprintf
     * @param string $locallangPathAndFilename
     * @param string $language
     * @param mixed $defaultValue
     * @return mixed The value from LOCAL_LANG or $defaultValue if no translation was found.
     * @internal
     */
    public function translate(
        $key,
        array $arguments = null,
        string $locallangPathAndFilename = null,
        string $language = null,
        $defaultValue = ''
    ) {
        $value = null;
        $key = (string)$key;

        if ($locallangPathAndFilename) {
            $key = $locallangPathAndFilename . ':' . $key;
        }

        $keyParts = explode(':', $key);
        if (GeneralUtility::isFirstPartOfStr($key, 'LLL:')) {
            $locallangPathAndFilename = $keyParts[1] . ':' . $keyParts[2];
            $key = $keyParts[3];
        } elseif (GeneralUtility::isFirstPartOfStr($key, 'EXT:')) {
            $locallangPathAndFilename = $keyParts[0] . ':' . $keyParts[1];
            $key = $keyParts[2];
        } else {
            if (count($keyParts) === 2) {
                $locallangPathAndFilename = $keyParts[0];
                $key = $keyParts[1];
            }
        }

        if ($language) {
            $this->languageKey = $language;
        }

        $this->initializeLocalization($locallangPathAndFilename);

        // The "from" charset of csConv() is only set for strings from TypoScript via _LOCAL_LANG
        if (!empty($this->LOCAL_LANG[$this->languageKey][$key][0]['target'])
            || isset($this->LOCAL_LANG_UNSET[$this->languageKey][$key])
        ) {
            // Local language translation for key exists
            $value = $this->LOCAL_LANG[$this->languageKey][$key][0]['target'];
        } elseif (!empty($this->alternativeLanguageKeys)) {
            $languages = array_reverse($this->alternativeLanguageKeys);
            foreach ($languages as $language) {
                if (!empty($this->LOCAL_LANG[$language][$key][0]['target'])
                    || isset($this->LOCAL_LANG_UNSET[$language][$key])
                ) {
                    // Alternative language translation for key exists
                    $value = $this->LOCAL_LANG[$language][$key][0]['target'];
                    break;
                }
            }
        }

        if ($value === null && (!empty($this->LOCAL_LANG['default'][$key][0]['target'])
            || isset($this->LOCAL_LANG_UNSET['default'][$key]))
        ) {
            // Default language translation for key exists
            // No charset conversion because default is English and thereby ASCII
            $value = $this->LOCAL_LANG['default'][$key][0]['target'];
        }

        if (is_array($arguments) && $value !== null) {
            $value = vsprintf($value, $arguments);
        } else {
            if (empty($value)) {
                $value = $defaultValue;
            }
        }

        return $value;
    }

    /**
     * Recursively translate values.
     *
     * @param array $array
     * @param string $translationFile
     * @return array the modified array
     * @internal
     */
    public function translateValuesRecursive(array $array, string $translationFile = null): array
    {
        $result = $array;
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->translateValuesRecursive($value, $translationFile);
            } else {
                $result[$key] = $this->translate($value, null, $translationFile, null, $value);
            }
        }
        return $result;
    }

    /**
     * @param FormRuntime $formRuntime
     * @param string $finisherIdentifier
     * @param string $optionKey
     * @param string $optionValue
     * @param array $renderingOptions
     * @return string
     * @throws \InvalidArgumentException
     * @api
     */
    public function translateFinisherOption(
        FormRuntime $formRuntime,
        string $finisherIdentifier,
        string $optionKey,
        string $optionValue,
        array $renderingOptions = []
    ): string {
        if (empty($finisherIdentifier)) {
            throw new \InvalidArgumentException('The argument "finisherIdentifier" is empty', 1476216059);
        }
        if (empty($optionKey)) {
            throw new \InvalidArgumentException('The argument "optionKey" is empty', 1476216060);
        }

        $finisherIdentifier = preg_replace('/Finisher$/', '', $finisherIdentifier);
        $translationFile = $renderingOptions['translationFile'];
        if (isset($renderingOptions['translatePropertyValueIfEmpty'])) {
            $translatePropertyValueIfEmpty = (bool)$renderingOptions['translatePropertyValueIfEmpty'];
        } else {
            $translatePropertyValueIfEmpty = true;
        }

        if (empty($optionValue) && !$translatePropertyValueIfEmpty) {
            return $optionValue;
        }

        $language = null;
        if (isset($renderingOptions['language'])) {
            $language = $renderingOptions['language'];
        }

        $translationKeyChain = [
            sprintf('%s:%s.finisher.%s.%s', $translationFile, $formRuntime->getIdentifier(), $finisherIdentifier, $optionKey),
            sprintf('%s:finisher.%s.%s', $translationFile, $finisherIdentifier, $optionKey)
        ];
        $translatedValue = $this->processTranslationChain($translationKeyChain, $language);
        $translatedValue = (empty($translatedValue)) ? $optionValue : $translatedValue;

        return $translatedValue;
    }

    /**
     * @param RootRenderableInterface $element
     * @param string $property
     * @param FormRuntime $formRuntime
     * @return string|array
     * @throws \InvalidArgumentException
     * @internal
     */
    public function translateFormElementValue(
        RootRenderableInterface $element,
        string $property,
        FormRuntime $formRuntime
    ) {
        if (empty($property)) {
            throw new \InvalidArgumentException('The argument "property" is empty', 1476216007);
        }

        $propertyType = 'properties';
        $renderingOptions = $element->getRenderingOptions();

        if ($property === 'label') {
            $defaultValue = $element->getLabel();
        } else {
            if ($element instanceof FormElementInterface) {
                $defaultValue = ArrayUtility::getValueByPath($element->getProperties(), $property);
            } else {
                $propertyType = 'renderingOptions';
                $defaultValue = ArrayUtility::getValueByPath($renderingOptions, $property);
            }
        }

        if (isset($renderingOptions['translation']['translatePropertyValueIfEmpty'])) {
            $translatePropertyValueIfEmpty = $renderingOptions['translation']['translatePropertyValueIfEmpty'];
        } else {
            $translatePropertyValueIfEmpty = true;
        }

        if (empty($defaultValue) && !$translatePropertyValueIfEmpty) {
            return $defaultValue;
        }

        $defaultValue = empty($defaultValue) ? '' : $defaultValue;
        $translationFile = $renderingOptions['translation']['translationFile'];

        $language = null;
        if (isset($renderingOptions['translation']['language'])) {
            $language = $renderingOptions['translation']['language'];
        }
        $translationKeyChain = [];
        if ($property === 'options' && is_array($defaultValue)) {
            foreach ($defaultValue as $optionValue => &$optionLabel) {
                $translationKeyChain = [
                    sprintf('%s:%s.element.%s.%s.%s.%s', $translationFile, $formRuntime->getIdentifier(), $element->getIdentifier(), $propertyType, $property, $optionValue),
                    sprintf('%s:element.%s.%s.%s.%s', $translationFile, $element->getIdentifier(), $propertyType, $property, $optionValue)
                ];
                $translatedValue = $this->processTranslationChain($translationKeyChain, $language);
                $optionLabel = (empty($translatedValue)) ? $optionLabel : $translatedValue;
            }
            $translatedValue = $defaultValue;
        } else {
            $translationKeyChain = [
                sprintf('%s:%s.element.%s.%s.%s', $translationFile, $formRuntime->getIdentifier(), $element->getIdentifier(), $propertyType, $property),
                sprintf('%s:element.%s.%s.%s', $translationFile, $element->getIdentifier(), $propertyType, $property),
                sprintf('%s:element.%s.%s.%s', $translationFile, $element->getType(), $propertyType, $property),
            ];
            $translatedValue = $this->processTranslationChain($translationKeyChain, $language);
            $translatedValue = (empty($translatedValue)) ? $defaultValue : $translatedValue;
        }

        return $translatedValue;
    }

    /**
     * @param string $languageKey
     * @internal
     */
    public function setLanguage(string $languageKey)
    {
        $this->languageKey = $languageKey;
    }

    /**
     * @return string
     * @internal
     */
    public function getLanguage(): string
    {
        return $this->languageKey;
    }

    /**
     * @param array $translationKeyChain
     * @param string $language
     * @return string|null
     */
    protected function processTranslationChain(array $translationKeyChain, string $language = null)
    {
        $translatedValue = null;
        foreach ($translationKeyChain as $translationKey) {
            $translatedValue = $this->translate($translationKey, null, null, $language);
            if (!empty($translatedValue)) {
                break;
            }
        }
        return $translatedValue;
    }

    /**
     * @param string $locallangPathAndFilename
     * @return void
     */
    protected function initializeLocalization(string $locallangPathAndFilename)
    {
        if (empty($this->languageKey)) {
            $this->setLanguageKeys();
        }

        if (!empty($locallangPathAndFilename)) {
            /** @var $languageFactory LocalizationFactory */
            $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);

            $this->LOCAL_LANG = $languageFactory->getParsedData($locallangPathAndFilename, $this->languageKey, 'utf-8');

            foreach ($this->alternativeLanguageKeys as $language) {
                $tempLL = $languageFactory->getParsedData($locallangPathAndFilename, $language, 'utf-8');
                if ($this->languageKey !== 'default' && isset($tempLL[$language])) {
                    $this->LOCAL_LANG[$language] = $tempLL[$language];
                }
            }
        }
        $this->loadTypoScriptLabels();
    }

    /**
     * Sets the currently active language/language_alt keys.
     * Default values are "default" for language key and "" for language_alt key.
     *
     * @return void
     */
    protected function setLanguageKeys()
    {
        $this->languageKey = 'default';

        $this->alternativeLanguageKeys = [];
        if (TYPO3_MODE === 'FE') {
            if (isset($this->getTypoScriptFrontendController()->config['config']['language'])) {
                $this->languageKey = $this->getTypoScriptFrontendController()->config['config']['language'];
                if (isset($this->getTypoScriptFrontendController()->config['config']['language_alt'])) {
                    $this->alternativeLanguageKeys[] = $this->getTypoScriptFrontendController()->config['config']['language_alt'];
                } else {
                    /** @var $locales \TYPO3\CMS\Core\Localization\Locales */
                    $locales = GeneralUtility::makeInstance(Locales::class);
                    if (in_array($this->languageKey, $locales->getLocales(), true)) {
                        foreach ($locales->getLocaleDependencies($this->languageKey) as $language) {
                            $this->alternativeLanguageKeys[] = $language;
                        }
                    }
                }
            }
        } elseif (!empty($GLOBALS['BE_USER']->uc['lang'])) {
            $this->languageKey = $GLOBALS['BE_USER']->uc['lang'];
        } elseif (!empty($this->getLanguageService()->lang)) {
            $this->languageKey = $this->getLanguageService()->lang;
        }
    }

    /**
     * Overwrites labels that are set via TypoScript.
     * TS locallang labels have to be configured like:
     * plugin.tx_form._LOCAL_LANG.languageKey.key = value
     *
     * @return void
     */
    protected function loadTypoScriptLabels()
    {
        $frameworkConfiguration = $this->getConfigurationManager()
            ->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'form');

        if (!is_array($frameworkConfiguration['_LOCAL_LANG'])) {
            return;
        }
        $this->LOCAL_LANG_UNSET = [];
        foreach ($frameworkConfiguration['_LOCAL_LANG'] as $languageKey => $labels) {
            if (!(is_array($labels) && isset($this->LOCAL_LANG[$languageKey]))) {
                continue;
            }
            foreach ($labels as $labelKey => $labelValue) {
                if (is_string($labelValue)) {
                    $this->LOCAL_LANG[$languageKey][$labelKey][0]['target'] = $labelValue;
                    if ($labelValue === '') {
                        $this->LOCAL_LANG_UNSET[$languageKey][$labelKey] = '';
                    }
                } elseif (is_array($labelValue)) {
                    $labelValue = $this->flattenTypoScriptLabelArray($labelValue, $labelKey);
                    foreach ($labelValue as $key => $value) {
                        $this->LOCAL_LANG[$languageKey][$key][0]['target'] = $value;
                        if ($value === '') {
                            $this->LOCAL_LANG_UNSET[$languageKey][$key] = '';
                        }
                    }
                }
            }
        }
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
    protected function flattenTypoScriptLabelArray(array $labelValues, string $parentKey = ''): array
    {
        $result = [];
        foreach ($labelValues as $key => $labelValue) {
            if (!empty($parentKey)) {
                $key = $parentKey . '.' . $key;
            }
            if (is_array($labelValue)) {
                $labelValue = $this->flattenTypoScriptLabelArray($labelValue, $key);
                $result = array_merge($result, $labelValue);
            } else {
                $result[$key] = $labelValue;
            }
        }
        return $result;
    }

    /**
     * Returns instance of the configuration manager
     *
     * @return ConfigurationManagerInterface
     */
    protected function getConfigurationManager(): ConfigurationManagerInterface
    {
        if ($this->configurationManager !== null) {
            return $this->configurationManager;
        }

        $this->configurationManager = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ConfigurationManagerInterface::class);
        return $this->configurationManager;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
