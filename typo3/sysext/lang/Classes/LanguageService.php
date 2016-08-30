<?php
namespace TYPO3\CMS\Lang;

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

use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains the TYPO3 Backend Language class
 * For detailed information about how localization is handled,
 * please refer to the 'Inside TYPO3' document which describes this.
 * This class is normally instantiated as the global variable $GLOBALS['LANG']
 * It's only available in the backend and under certain circumstances in the frontend
 * @see \TYPO3\CMS\Backend\Template\DocumentTemplate
 */
class LanguageService
{
    /**
     * This is set to the language that is currently running for the user
     *
     * @var string
     */
    public $lang = 'default';

    /**
     * Default charset in backend
     *
     * @var string
     */
    public $charSet = 'utf-8';

    /**
     * If TRUE, will show the key/location of labels in the backend.
     *
     * @var bool
     */
    public $debugKey = false;

    /**
     * Can contain labels and image references from the backend modules.
     * Relies on \TYPO3\CMS\Backend\Module\ModuleLoader to initialize modules after a global instance of $LANG has been created.
     *
     * @var array
     */
    public $moduleLabels = [];

    /**
     * Internal cache for read LL-files
     *
     * @var array
     */
    public $LL_files_cache = [];

    /**
     * Internal cache for ll-labels (filled as labels are requested)
     *
     * @var array
     */
    public $LL_labels_cache = [];

    /**
     * instance of the "\TYPO3\CMS\Core\Charset\CharsetConverter" class. May be used by any application.
     *
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    public $csConvObj;

    /**
     * instance of the parser factory
     *
     * @var LocalizationFactory
     */
    public $parserFactory;

    /**
     * List of language dependencies for actual language. This is used for local variants of a language
     * that depend on their "main" language, like Brazilian Portuguese or Canadian French.
     *
     * @var array
     */
    protected $languageDependencies = [];

    /**
     * Initializes the backend language.
     * This is for example done in \TYPO3\CMS\Backend\Template\DocumentTemplate with lines like these:
     * $LANG = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Lang\LanguageService::class);
     * $LANG->init($GLOBALS['BE_USER']->uc['lang']);
     *
     * @throws \RuntimeException
     * @param string $lang The language key (two character string from backend users profile)
     * @return void
     */
    public function init($lang)
    {
        // Initialize the conversion object:
        $this->csConvObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
        // Initialize the parser factory object
        $this->parserFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
        // Find the requested language in this list based
        // on the $lang key being inputted to this function.
        /** @var $locales \TYPO3\CMS\Core\Localization\Locales */
        $locales = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\Locales::class);
        // Language is found. Configure it:
        if (in_array($lang, $locales->getLocales())) {
            // The current language key
            $this->lang = $lang;
            $this->languageDependencies[] = $this->lang;
            foreach ($locales->getLocaleDependencies($this->lang) as $language) {
                $this->languageDependencies[] = $language;
            }
        }
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['lang']['debug']) {
            $this->debugKey = true;
        }
    }

    /**
     * Gets the parser factory.
     *
     * @return LocalizationFactory
     */
    public function getParserFactory()
    {
        return $this->parserFactory;
    }

    /**
     * Adds labels and image references from the backend modules to the internal moduleLabels array
     *
     * @param array $arr Array with references to module labels, keys: ['labels']['table'],
     * @param string $prefix Module name prefix
     * @return void
     * @see \TYPO3\CMS\Backend\Module\ModuleLoader
     */
    public function addModuleLabels($arr, $prefix)
    {
        if (is_array($arr)) {
            foreach ($arr as $k => $larr) {
                if (!isset($this->moduleLabels[$k])) {
                    $this->moduleLabels[$k] = [];
                }
                if (is_array($larr)) {
                    foreach ($larr as $l => $v) {
                        $this->moduleLabels[$k][$prefix . $l] = $v;
                    }
                }
            }
        }
    }

    /**
     * Will convert the input strings special chars (all above 127) to entities.
     * The string is expected to be encoded in UTF-8
     * This function is used to create strings that can be used in the Click Menu
     * (Context Sensitive Menus). The reason is that the values that are dynamically
     * written into the <div> layer is decoded as iso-8859-1 no matter what charset
     * is used in the document otherwise (only MSIE, Mozilla is OK).
     * So by converting we by-pass this problem.
     *
     * @param string $str Input string
     * @return string Output string
     */
    public function makeEntities($str)
    {
        // Convert string back again, but using the full entity conversion:
        return $this->csConvObj->utf8_to_entities($str);
    }

    /**
     * Debugs localization key.
     *
     * @param string $value value to debug
     * @return string
     */
    public function debugLL($value)
    {
        return $this->debugKey ? '[' . $value . ']' : '';
    }

    /**
     * Returns the label with key $index from the globally loaded $LOCAL_LANG array.
     * Mostly used from modules with only one LOCAL_LANG file loaded into the global space.
     *
     * @param string $index Label key
     * @param bool $hsc If set, the return value is htmlspecialchar'ed
     * @return string
     */
    public function getLL($index, $hsc = false)
    {
        return $this->getLLL($index, $GLOBALS['LOCAL_LANG'], $hsc);
    }

    /**
     * Returns the label with key $index from the $LOCAL_LANG array used as the second argument
     *
     * @param string $index Label key
     * @param array $localLanguage $LOCAL_LANG array to get label key from
     * @param bool $hsc If set, the return value is htmlspecialchar'ed
     * @return string
     */
    public function getLLL($index, $localLanguage, $hsc = false)
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
        if ($hsc) {
            $value = htmlspecialchars($value);
        }
        return $value . $this->debugLL($index);
    }

    /**
     * splitLabel function
     *
     * All translations are based on $LOCAL_LANG variables.
     * 'language-splitted' labels can therefore refer to a local-lang file + index.
     * Refer to 'Inside TYPO3' for more details
     *
     * @param string $input Label key/reference
     * @param bool $hsc If set, the return value is htmlspecialchar'ed
     * @return string
     */
    public function sL($input, $hsc = false)
    {
        $identifier = $input . '_' . (int)$hsc . '_' . (int)$this->debugKey;
        if (isset($this->LL_labels_cache[$this->lang][$identifier])) {
            return $this->LL_labels_cache[$this->lang][$identifier];
        }
        if (strpos($input, 'LLL:') === 0) {
            $restStr = trim(substr($input, 4));
            $extPrfx = '';
                // ll-file referred to is found in an extension.
            if (strpos($restStr, 'EXT:') === 0) {
                $restStr = trim(substr($restStr, 4));
                $extPrfx = 'EXT:';
            }
            $parts = explode(':', $restStr);
            $parts[0] = $extPrfx . $parts[0];
                // Getting data if not cached
            if (!isset($this->LL_files_cache[$parts[0]])) {
                $this->LL_files_cache[$parts[0]] = $this->readLLfile($parts[0]);
                    // If the current language is found in another file, load that as well:
                $lFileRef = $this->localizedFileRef($parts[0]);
                if ($lFileRef && $this->LL_files_cache[$parts[0]][$this->lang] === 'EXT') {
                    $tempLL = $this->readLLfile($lFileRef);
                    $this->LL_files_cache[$parts[0]][$this->lang] = $tempLL[$this->lang];
                }
            }
            $output = $this->getLLL($parts[1], $this->LL_files_cache[$parts[0]]);
        } else {
            // Use a constant non-localizable label
            $output = $input;
        }
        if ($hsc) {
            $output = htmlspecialchars($output, ENT_COMPAT, 'UTF-8', false);
        }
        $output .= $this->debugLL($input);
        $this->LL_labels_cache[$this->lang][$identifier] = $output;
        return $output;
    }

    /**
     * Loading $TCA_DESCR[$table]['columns'] with content from locallang files
     * as defined in $TCA_DESCR[$table]['refs']
     * $TCA_DESCR is a global var
     *
     * @param string $table Table name found as key in global array $TCA_DESCR
     * @return void
     */
    public function loadSingleTableDescription($table)
    {
        // First the 'table' cannot already be loaded in [columns]
            // and secondly there must be a references to locallang files available in [refs]
        if (is_array($GLOBALS['TCA_DESCR'][$table]) && !isset($GLOBALS['TCA_DESCR'][$table]['columns']) && is_array($GLOBALS['TCA_DESCR'][$table]['refs'])) {
            // Init $TCA_DESCR for $table-key
            $GLOBALS['TCA_DESCR'][$table]['columns'] = [];
                // Get local-lang for each file in $TCA_DESCR[$table]['refs'] as they are ordered.
            foreach ($GLOBALS['TCA_DESCR'][$table]['refs'] as $llfile) {
                $localLanguage = $this->includeLLFile($llfile, 0, 1);
                    // Traverse all keys
                if (is_array($localLanguage['default'])) {
                    foreach ($localLanguage['default'] as $lkey => $lVal) {
                        // Exploding by '.':
                            // 0-n => fieldname,
                            // n+1 => type from (alttitle, description, details, syntax, image_descr,image,seeAlso),
                            // n+2 => special instruction, if any
                        $keyParts = explode('.', $lkey);
                        $keyPartsCount = count($keyParts);
                            // Check if last part is special instruction
                            // Only "+" is currently supported
                        $specialInstruction = $keyParts[$keyPartsCount - 1] === '+';
                        if ($specialInstruction) {
                            array_pop($keyParts);
                        }
                            // If there are more than 2 parts, get the type from the last part
                            // and merge back the other parts with a dot (.)
                            // Otherwise just get type and field name straightaway
                        if ($keyPartsCount > 2) {
                            $type = array_pop($keyParts);
                            $fieldName = implode('.', $keyParts);
                        } else {
                            $fieldName = $keyParts[0];
                            $type = $keyParts[1];
                        }
                            // Detecting 'hidden' labels, converting to normal fieldname
                        if ($fieldName === '_') {
                            $fieldName = '';
                        }
                        if ($fieldName !== '' && $fieldName[0] === '_') {
                            $fieldName = substr($fieldName, 1);
                        }
                            // Append label
                        $label = $lVal[0]['target'] ? :
                            $lVal[0]['source'];
                        if ($specialInstruction) {
                            $GLOBALS['TCA_DESCR'][$table]['columns'][$fieldName][$type] .= LF . $label;
                        } else {
                            // Substitute label
                            $GLOBALS['TCA_DESCR'][$table]['columns'][$fieldName][$type] = $label;
                        }
                    }
                }
            }
        }
    }

    /**
     * Includes locallang file (and possibly additional localized version if configured for)
     * Read language labels will be merged with $LOCAL_LANG (if $setGlobal = TRUE).
     *
     * @param string $fileRef $fileRef is a file-reference
     * @param bool $setGlobal Setting in global variable $LOCAL_LANG (or returning the variable)
     * @param bool $mergeLocalOntoDefault
     * @return mixed if $setGlobal===TRUE, LL-files set $LOCAL_LANG in global scope, or array is returned from function
     */
    public function includeLLFile($fileRef, $setGlobal = true, $mergeLocalOntoDefault = false)
    {
        $globalLanguage = [];
            // Get default file
        $localLanguage = $this->readLLfile($fileRef);
        if (is_array($localLanguage) && !empty($localLanguage)) {
            // it depends on, whether we should return the result or set it in the global $LOCAL_LANG array
            if ($setGlobal) {
                $globalLanguage = (array)$GLOBALS['LOCAL_LANG'];
                ArrayUtility::mergeRecursiveWithOverrule($globalLanguage, $localLanguage);
            } else {
                $globalLanguage = $localLanguage;
            }
                // Localized addition?
            $lFileRef = $this->localizedFileRef($fileRef);
            if ($lFileRef && (string)$globalLanguage[$this->lang] === 'EXT') {
                $localLanguage = $this->readLLfile($lFileRef);
                ArrayUtility::mergeRecursiveWithOverrule($globalLanguage, $localLanguage);
            }
                // Merge local onto default
            if ($mergeLocalOntoDefault && $this->lang !== 'default' && is_array($globalLanguage[$this->lang]) && is_array($globalLanguage['default'])) {
                // array_merge can be used so far the keys are not
                    // numeric - which we assume they are not...
                $globalLanguage['default'] = array_merge($globalLanguage['default'], $globalLanguage[$this->lang]);
                unset($globalLanguage[$this->lang]);
            }
        }
            // Return value if not global is set.
        if (!$setGlobal) {
            return $globalLanguage;
        } else {
            $GLOBALS['LOCAL_LANG'] = $globalLanguage;
            return null;
        }
    }

    /**
     * Includes a locallang file and returns the $LOCAL_LANG array found inside.
     *
     * @param string $fileRef Input is a file-reference to be a 'local_lang' file containing a $LOCAL_LANG array
     * @return array value of $LOCAL_LANG found in the included file, empty if non found
     */
    protected function readLLfile($fileRef)
    {
        // @todo: Usually, an instance of the LocalizationFactory is found in $this->parserFactory.
        // @todo: This is not the case if $GLOBALS['LANG'] is not used to get hold of this object,
        // @todo: but the objectManager instead. If then init() is not called, this will fatal ...
        // @todo: To be sure, we always create an instance here for now.
        /** @var $languageFactory LocalizationFactory */
        $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);

        if ($this->lang !== 'default') {
            $languages = array_reverse($this->languageDependencies);
        } else {
            $languages = ['default'];
        }
        $localLanguage = [];
        foreach ($languages as $language) {
            $tempLL = $languageFactory->getParsedData($fileRef, $language, $this->charSet);
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
        return $localLanguage;
    }

    /**
     * Returns localized fileRef (.[langkey].php)
     *
     * @param string $fileRef Filename/path of a 'locallang.php' file
     * @return string Input filename with a '.[lang-key].php' ending added if $this->lang is not 'default'
     * @deprecated since TYPO3 CMS 7, this method will be removed in CMS 8. Please use XLF files for translation handling.
     */
    protected function localizedFileRef($fileRef)
    {
        if ($this->lang !== 'default' && substr($fileRef, -4) === '.php') {
            GeneralUtility::logDeprecatedFunction();
            return substr($fileRef, 0, -4) . '.' . $this->lang . '.php';
        } else {
            return null;
        }
    }

    /**
     * Overrides a label.
     *
     * @param string $index
     * @param string $value
     * @param bool $overrideDefault Overrides default language
     * @return void
     */
    public function overrideLL($index, $value, $overrideDefault = true)
    {
        if (!isset($GLOBALS['LOCAL_LANG'])) {
            $GLOBALS['LOCAL_LANG'] = [];
        }
        $GLOBALS['LOCAL_LANG'][$this->lang][$index][0]['target'] = $value;
        if ($overrideDefault) {
            $GLOBALS['LOCAL_LANG']['default'][$index][0]['target'] = $value;
        }
    }

    /**
     * Gets labels with a specific fetched from the current locallang file.
     * This is useful for e.g gathering javascript labels.
     *
     * @param string $prefix Prefix to select the correct labels
     * @param string $strip Sub-prefix to be removed from label names in the result
     * @return array Processed labels
     */
    public function getLabelsWithPrefix($prefix, $strip = '')
    {
        $extraction = [];
        $labels = array_merge((array)$GLOBALS['LOCAL_LANG']['default'], (array)$GLOBALS['LOCAL_LANG'][$GLOBALS['LANG']->lang]);
        // Regular expression to strip the selection prefix and possibly something from the label name:
        $labelPattern = '#^' . preg_quote($prefix, '#') . '(' . preg_quote($strip, '#') . ')?#';
        // Iterate through all locallang labels:
        foreach ($labels as $label => $value) {
            if (strpos($label, $prefix) === 0) {
                $key = preg_replace($labelPattern, '', $label);
                $extraction[$key] = $value;
            }
        }
        return $extraction;
    }
}
