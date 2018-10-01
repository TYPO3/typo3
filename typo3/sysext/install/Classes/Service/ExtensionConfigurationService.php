<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Service;

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

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service to prepare extension configuration settings from ext_conf_template.txt
 * to be viewed in the install tool. The class basically adds display related
 * stuff on top of ext:core ExtensionConfiguration.
 *
 * Extension authors should use TYPO3\CMS\Core\Configuration\ExtensionConfiguration
 * class to get() extension configuration settings.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class ExtensionConfigurationService
{
    /**
     * This will be filled with the available categories of the current template.
     * Used parsing ext_conf_template.txt
     *
     * @var array
     */
    protected $subCategories = [
        // Standard categories:
        'enable' => ['Enable features', 'a'],
        'dims' => ['Dimensions, widths, heights, pixels', 'b'],
        'file' => ['Files', 'c'],
        'typo' => ['Typography', 'd'],
        'color' => ['Colors', 'e'],
        'links' => ['Links and targets', 'f'],
        'language' => ['Language specific constants', 'g'],
        // subcategories based on the default content elements
        'cheader' => ['Content: \'Header\'', 'ma'],
        'cheader_g' => ['Content: \'Header\', Graphical', 'ma'],
        'ctext' => ['Content: \'Text\'', 'mb'],
        'cimage' => ['Content: \'Image\'', 'md'],
        'ctextmedia' => ['Content: \'Textmedia\'', 'ml'],
        'cbullets' => ['Content: \'Bullet list\'', 'me'],
        'ctable' => ['Content: \'Table\'', 'mf'],
        'cuploads' => ['Content: \'Filelinks\'', 'mg'],
        'cmultimedia' => ['Content: \'Multimedia\'', 'mh'],
        'cmedia' => ['Content: \'Media\'', 'mr'],
        'cmailform' => ['Content: \'Form\'', 'mi'],
        'csearch' => ['Content: \'Search\'', 'mj'],
        'clogin' => ['Content: \'Login\'', 'mk'],
        'cmenu' => ['Content: \'Menu/Sitemap\'', 'mm'],
        'cshortcut' => ['Content: \'Insert records\'', 'mn'],
        'clist' => ['Content: \'List of records\'', 'mo'],
        'chtml' => ['Content: \'HTML\'', 'mq']
    ];

    /**
     * Compiles ext_conf_template file and merges it with values from LocalConfiguration['EXTENSIONS'].
     * Returns a funny array used to display the configuration form in the install tool.
     *
     * @param string $extensionKey Extension key
     * @return array
     */
    public function getConfigurationPreparedForView(string $extensionKey): array
    {
        $package = GeneralUtility::makeInstance(PackageManager::class)->getPackage($extensionKey);
        if (!@is_file($package->getPackagePath() . 'ext_conf_template.txt')) {
            return [];
        }
        $extensionConfiguration = new ExtensionConfiguration();
        $configuration = $this->getDefaultConfigurationFromExtConfTemplateAsValuedArray($extensionKey);
        foreach ($configuration as $configurationPath => &$details) {
            try {
                $valueFromLocalConfiguration = $extensionConfiguration->get($extensionKey, str_replace('.', '/', $configurationPath));
                $details['value'] = $valueFromLocalConfiguration;
            } catch (ExtensionConfigurationPathDoesNotExistException $e) {
                // Deliberately empty - it can happen at runtime that a written config does not return
                // back all values (eg. saltedpassword with its userFuncs), which then miss in the written
                // configuration and are only synced after next install tool run. This edge case is
                // taken care off here.
            }
        }
        $resultArray = [];
        if (!empty($configuration)) {
            $hierarchicConfiguration = [];
            foreach ($configuration as $configurationOption) {
                $originalConfiguration = $this->buildConfigurationArray($configurationOption);
                ArrayUtility::mergeRecursiveWithOverrule($originalConfiguration, $hierarchicConfiguration);
                $hierarchicConfiguration = $originalConfiguration;
            }
            // Flip category array as it was merged the other way around
            $hierarchicConfiguration = array_reverse($hierarchicConfiguration);
            // Sort configurations of each subcategory
            foreach ($hierarchicConfiguration as &$catConfigurationArray) {
                foreach ($catConfigurationArray as &$subcatConfigurationArray) {
                    uasort($subcatConfigurationArray, function ($a, $b) {
                        return strnatcmp($a['subcat'], $b['subcat']);
                    });
                }
                unset($subcatConfigurationArray);
            }
            unset($tempConfiguration);
            $resultArray = $hierarchicConfiguration;
        }
        return $resultArray;
    }

    /**
     * Builds a configuration array from each line (option) of the config file.
     * Helper method for getConfigurationPreparedForView()
     *
     * @param array $configurationOption config file line representing one setting
     * @return array
     */
    protected function buildConfigurationArray(array $configurationOption): array
    {
        $hierarchicConfiguration = [];
        if (GeneralUtility::isFirstPartOfStr($configurationOption['type'], 'user')) {
            $configurationOption = $this->extractInformationForConfigFieldsOfTypeUser($configurationOption);
        } elseif (GeneralUtility::isFirstPartOfStr($configurationOption['type'], 'options')) {
            $configurationOption = $this->extractInformationForConfigFieldsOfTypeOptions($configurationOption);
        }
        $languageService = $this->getLanguageService();
        if (is_string($configurationOption['label'])) {
            $translatedLabel = $languageService->sL($configurationOption['label']);
            if ($translatedLabel) {
                $configurationOption['label'] = $translatedLabel;
            }
        }
        $configurationOption['labels'] = GeneralUtility::trimExplode(':', $configurationOption['label'], false, 2);
        $configurationOption['subcat_name'] = $configurationOption['subcat_name'] ?: '__default';
        $hierarchicConfiguration[$configurationOption['cat']][$configurationOption['subcat_name']][$configurationOption['name']] = $configurationOption;
        return $hierarchicConfiguration;
    }

    /**
     * Extracts additional information for fields of type "options"
     * Extracts "type", "label" and values information
     *
     * @param array $configurationOption
     * @return array
     */
    protected function extractInformationForConfigFieldsOfTypeOptions(array $configurationOption): array
    {
        preg_match('/options\[(.*)\]/is', $configurationOption['type'], $typeMatches);
        $optionItems = GeneralUtility::trimExplode(',', $typeMatches[1]);
        foreach ($optionItems as $optionItem) {
            $optionPair = GeneralUtility::trimExplode('=', $optionItem);
            if (count($optionPair) === 2) {
                $configurationOption['generic'][$optionPair[0]] = $optionPair[1];
            } else {
                $configurationOption['generic'][$optionPair[0]] = $optionPair[0];
            }
        }
        $configurationOption['type'] = 'options';
        return $configurationOption;
    }

    /**
     * Extract additional information for fields of type "user"
     * Extracts "type" and the function to be called
     *
     * @param array $configurationOption
     * @return array
     */
    protected function extractInformationForConfigFieldsOfTypeUser(array $configurationOption): array
    {
        preg_match('/user\\[(.*)\\]/is', $configurationOption['type'], $matches);
        $configurationOption['generic'] = $matches[1];
        $configurationOption['type'] = 'user';
        return $configurationOption;
    }

    /**
     * Create a flat array of configuration options from
     * ext_conf_template.txt of an extension using core's typoscript parser.
     *
     * Result is an array, with configuration item as array keys,
     * and item properties as key-value sub-array:
     *
     * array(
     *   'fooOption' => array(
     *     'type' => 'string',
     *     'value' => 'foo',
     *     ...
     *   ),
     *   'barOption' => array(
     *     'type' => boolean,
     *     'default_value' => 0,
     *     ...
     *   ),
     *   ...
     * )
     *
     * @param string $extensionKey Extension key
     * @return array
     */
    protected function getDefaultConfigurationFromExtConfTemplateAsValuedArray(string $extensionKey): array
    {
        $parsedConstants = (new ExtensionConfiguration())->getParsedExtConfTemplate($extensionKey);
        $flatSetup = $this->flattenSetup($parsedConstants);
        $theConstants = $this->parseComments($flatSetup);

        // Loop through configuration items, see if it is assigned to a sub category
        // and add the sub category label to the item property if so.
        foreach ($theConstants as $configurationOptionName => $configurationOption) {
            if (
                array_key_exists('subcat_name', $configurationOption)
                && isset($this->subCategories[$configurationOption['subcat_name']])
                && isset($this->subCategories[$configurationOption['subcat_name']][0])
            ) {
                $theConstants[$configurationOptionName]['subcat_label'] = $this->subCategories[$configurationOption['subcat_name']][0];
            }
        }
        return $theConstants;
    }

    /**
     * This function compares the flattened constants (default and all).
     * Returns an array with the constants from the whole template which may be edited by the module.
     *
     * @param array $flatSetup
     * @return array
     */
    protected function parseComments($flatSetup)
    {
        $categoryLabels = [];
        $editableComments = [];
        $counter = 0;
        foreach ($flatSetup as $const => $value) {
            if (substr($const, -2) === '..' || !isset($flatSetup[$const . '..'])) {
                continue;
            }
            $counter++;
            $comment = trim($flatSetup[$const . '..']);
            $c_arr = explode(LF, $comment);
            foreach ($c_arr as $k => $v) {
                $line = trim(preg_replace('/^[#\\/]*/', '', $v));
                if (!$line) {
                    continue;
                }
                $parts = explode(';', $line);
                foreach ($parts as $par) {
                    if (strstr($par, '=')) {
                        $keyValPair = explode('=', $par, 2);
                        switch (trim(strtolower($keyValPair[0]))) {
                            case 'type':
                                // Type:
                                $editableComments[$const]['type'] = trim($keyValPair[1]);
                                break;
                            case 'cat':
                                // List of categories.
                                $catSplit = explode('/', strtolower($keyValPair[1]));
                                $catSplit[0] = trim($catSplit[0]);
                                if (isset($categoryLabels[$catSplit[0]])) {
                                    $catSplit[0] = $categoryLabels[$catSplit[0]];
                                }
                                $editableComments[$const]['cat'] = $catSplit[0];
                                // This is the subcategory. Must be a key in $this->subCategories[].
                                // catSplit[2] represents the search-order within the subcat.
                                $catSplit[1] = !empty($catSplit[1]) ? trim($catSplit[1]) : '';
                                if ($catSplit[1] && isset($this->subCategories[$catSplit[1]])) {
                                    $editableComments[$const]['subcat_name'] = $catSplit[1];
                                    $orderIdentifier = isset($catSplit[2]) ? trim($catSplit[2]) : $counter;
                                    $editableComments[$const]['subcat'] = $this->subCategories[$catSplit[1]][1]
                                        . '/' . $catSplit[1] . '/' . $orderIdentifier . 'z';
                                } elseif (isset($catSplit[2])) {
                                    $editableComments[$const]['subcat'] = 'x' . '/' . trim($catSplit[2]) . 'z';
                                } else {
                                    $editableComments[$const]['subcat'] = 'x' . '/' . $counter . 'z';
                                }
                                break;
                            case 'label':
                                // Label
                                $editableComments[$const]['label'] = trim($keyValPair[1]);
                                break;
                            case 'customcategory':
                                // Custom category label
                                $customCategory = explode('=', $keyValPair[1], 2);
                                if (trim($customCategory[0])) {
                                    $categoryKey = strtolower($customCategory[0]);
                                    $categoryLabels[$categoryKey] = $this->getLanguageService()->sL($customCategory[1]);
                                }
                                break;
                            case 'customsubcategory':
                                // Custom subCategory label
                                $customSubcategory = explode('=', $keyValPair[1], 2);
                                if (trim($customSubcategory[0])) {
                                    $subCategoryKey = strtolower($customSubcategory[0]);
                                    $this->subCategories[$subCategoryKey][0] = $this->getLanguageService()->sL($customSubcategory[1]);
                                }
                                break;
                        }
                    }
                }
            }
            if (isset($editableComments[$const])) {
                $editableComments[$const]['name'] = $const;
                $editableComments[$const]['value'] = trim($value);
                $editableComments[$const]['default_value'] = trim($value);
            }
        }
        return $editableComments;
    }

    /**
     * This flattens a hierarchical TypoScript array to a dotted notation
     *
     * @param array $setupArray TypoScript array
     * @param string $prefix Prefix to the object path. Used for recursive calls to this function.
     * @return array
     */
    protected function flattenSetup($setupArray, $prefix = '')
    {
        $flatSetup = [];
        if (is_array($setupArray)) {
            foreach ($setupArray as $key => $val) {
                if (is_array($val)) {
                    $flatSetup = array_merge($flatSetup, $this->flattenSetup($val, $prefix . $key));
                } else {
                    $flatSetup[$prefix . $key] = $val;
                }
            }
        }
        return $flatSetup;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
