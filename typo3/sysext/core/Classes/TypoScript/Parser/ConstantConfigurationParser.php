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

namespace TYPO3\CMS\Core\TypoScript\Parser;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Parser for TypoScript constant configuration lines and values
 * like "# cat=content/cText/1; type=; label= Bodytext font: This is the font face used for text!"
 * for display of the constant editor and extension settings configuration
 *
 * Basic TypoScript parsing is delegated to the TypoScriptParser which returns with comments intact.
 * These comments are then parsed by this class to prepare/set display related options. The Constant
 * Editor renders fields itself whereas the extension settings are rendered by fluid.
 */
class ConstantConfigurationParser
{
    /**
     * This will be filled with the available categories of the current template.
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
        'chtml' => ['Content: \'HTML\'', 'mq'],
    ];

    /**
     * Converts the configuration array to an hierarchical category array
     * for use in fluid templates.
     *
     * @param array $configuration
     * @return array
     */
    public function prepareConfigurationForView(array $configuration): array
    {
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
                    uasort($subcatConfigurationArray, static function ($a, $b) {
                        return strnatcmp($a['subcat'], $b['subcat']);
                    });
                }
                unset($subcatConfigurationArray);
            }
            unset($catConfigurationArray);
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
        if (str_starts_with((string)$configurationOption['type'], 'user')) {
            $configurationOption = $this->extractInformationForConfigFieldsOfTypeUser($configurationOption);
        } elseif (str_starts_with((string)$configurationOption['type'], 'options')) {
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
        $configurationOption['subcat_name'] = ($configurationOption['subcat_name'] ?? false) ?: '__default';
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
        /** @noinspection NotOptimalRegularExpressionsInspection */
        preg_match('/options\[(.*)\]/is', $configurationOption['type'], $typeMatches);
        foreach (GeneralUtility::trimExplode(',', $typeMatches[1]) as $optionItem) {
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
        /** @noinspection RegExpRedundantEscape */
        preg_match('/user\\[(.*)\\]/is', $configurationOption['type'], $matches);
        $configurationOption['generic'] = $matches[1];
        $configurationOption['type'] = 'user';
        return $configurationOption;
    }

    /**
     * Create a flat array of configuration options from
     * raw constants string.
     *
     * Result is an array, with configuration item as array keys,
     * and item properties as key-value sub-array:
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
     * Conditions are currently not supported in this context.
     *
     * @param string $rawConfiguration
     * @return array
     */
    public function getConfigurationAsValuedArray(string $rawConfiguration): array
    {
        $typoScriptParser = new TypoScriptParser();
        $typoScriptParser->regComments = true;
        $typoScriptParser->parse($rawConfiguration);
        $flatSetup = ArrayUtility::flatten($typoScriptParser->setup, '', true);
        $theConstants = $this->parseComments($flatSetup);

        // Loop through configuration items, see if it is assigned to a sub category
        // and add the sub category label to the item property if so.
        foreach ($theConstants as $configurationOptionName => $configurationOption) {
            if (
                array_key_exists('subcat_name', $configurationOption) && isset($this->subCategories[$configurationOption['subcat_name']][0])
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
     * @param array|null $default
     * @return array
     */
    public function parseComments($flatSetup, $default = null): array
    {
        $default = $default ?? $flatSetup;
        $categoryLabels = [];
        $editableComments = [];
        $counter = 0;
        foreach ($flatSetup as $const => $value) {
            $key = $const . '..';
            if (substr($const, -2) === '..' || !isset($flatSetup[$key])) {
                continue;
            }
            $counter++;
            $comment = trim($flatSetup[$key]);
            foreach (explode(LF, $comment) as $k => $v) {
                $line = trim(preg_replace('/^[#\\/]*/', '', $v) ?? '');
                if (!$line) {
                    continue;
                }
                foreach (explode(';', $line) as $par) {
                    if (str_contains($par, '=')) {
                        $keyValPair = explode('=', $par, 2);
                        switch (strtolower(trim($keyValPair[0]))) {
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
                                    $editableComments[$const]['subcat'] = ($this->subCategories[$catSplit[1]][1] ?? '')
                                                                          . '/' . $catSplit[1] . '/' . $orderIdentifier . 'z';
                                } elseif (isset($catSplit[2])) {
                                    $editableComments[$const]['subcat'] = 'x/' . trim($catSplit[2]) . 'z';
                                } else {
                                    $editableComments[$const]['subcat'] = 'x/' . $counter . 'z';
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
                $editableComments[$const]['default_value'] = trim((string)($default[$const] ?? ''));
                // If type was not provided, initialize with default value "string".
                $editableComments[$const]['type'] ??= 'string';
            }
        }
        return $editableComments;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Provide a public getter as the constant editor directly
     * accesses this array to render the fields.
     *
     * Can be removed once the constant editor uses fluid for
     * rendering (see prepareConfigurationForView).
     *
     * @see \TYPO3\CMS\Core\TypoScript\Parser\ConstantConfigurationParser::prepareConfigurationForView()
     * @see \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService::ext_printFields
     * @return array
     */
    public function getSubCategories(): array
    {
        return $this->subCategories;
    }
}
