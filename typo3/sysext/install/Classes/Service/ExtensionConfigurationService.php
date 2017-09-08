<?php
declare(strict_types=1);
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

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility for dealing with ext_conf_template settings and their instance
 * specific LocalConfiguration settings. This class is @internal and only
 * used by extension manager and install tool itself.
 *
 * Extension authors should use TYPO3\CMS\Core\Configuration\ExtensionConfiguration
 * class to get() and set() extension configuration settings.
 *
 * @internal
 */
class ExtensionConfigurationService
{
    /**
     * TypoScript hierarchy being build.
     * Used parsing ext_conf_template.txt
     *
     * @var array
     */
    protected $setup = [];

    /**
     * Raw data, the input string exploded by LF.
     * Used parsing ext_conf_template.txt
     *
     * @var array
     */
    protected $raw;

    /**
     * Pointer to entry in raw data array.
     * Used parsing ext_conf_template.txt
     *
     * @var int
     */
    protected $rawPointer = 0;

    /**
     * Holding the value of the last comment
     * Used parsing ext_conf_template.txt
     *
     * @var string
     */
    protected $lastComment = '';

    /**
     * Internal flag to create a multi-line comment (one of those like /* ... * /)
     * Used parsing ext_conf_template.txt
     *
     * @var bool
     */
    protected $commentSet = false;

    /**
     * Internally set, when in brace. Counter.
     * Used parsing ext_conf_template.txt
     *
     * @var int
     */
    protected $inBrace = 0;

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
     * If there are new config settings in ext_conf_template of an extenison,
     * they are found here and synchronized to LocalConfiguration['EXTENSIONS'].
     *
     * Used when entering the install tool and during installation.
     */
    public function synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions()
    {
        $activePackages = GeneralUtility::makeInstance(PackageManager::class)->getActivePackages();
        foreach ($activePackages as $package) {
            $this->synchronizeExtConfTemplateWithLocalConfiguration($package->getPackageKey());
        }
    }

    /**
     * Read values from ext_conf_template, verify if they are in LocalConfiguration.php
     * already and if not, add them.
     *
     * Used public by extension manager when updating extension
     *
     * @param string $extensionKey The extension to sync
     */
    public function synchronizeExtConfTemplateWithLocalConfiguration(string $extensionKey)
    {
        $package = GeneralUtility::makeInstance(PackageManager::class)->getPackage($extensionKey);
        if (!@is_file($package->getPackagePath() . 'ext_conf_template.txt')) {
            return;
        }
        $extensionConfiguration = new ExtensionConfiguration();
        try {
            $currentLocalConfiguration = $extensionConfiguration->get($extensionKey);
        } catch (ExtensionConfigurationExtensionNotConfiguredException $e) {
            $currentLocalConfiguration = [];
        }
        $extConfTemplateConfiguration = $this->getExtConfTablesWithoutCommentsAsNestedArrayWithoutDots($extensionKey);
        ArrayUtility::mergeRecursiveWithOverrule($extConfTemplateConfiguration, $currentLocalConfiguration);
        $extensionConfiguration->set($extensionKey, '', $extConfTemplateConfiguration);
    }

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
     * Poor man version of getDefaultConfigurationFromExtConfTemplateAsValuedArray() which ignores
     * comments and returns ext_conf_template as array where nested keys have no dots.
     */
    protected function getExtConfTablesWithoutCommentsAsNestedArrayWithoutDots(string $extensionKey)
    {
        $rawConfigurationString = $this->getDefaultConfigurationRawString($extensionKey);
        $configuration = [];
        if ((string)$rawConfigurationString !== '') {
            $this->raw = explode(LF, $rawConfigurationString);
            $this->rawPointer = 0;
            $this->setup = [];
            $this->parseSub($this->setup);
            if ($this->inBrace) {
                throw new \RuntimeException(
                    'Line ' . ($this->rawPointer - 1) . ': The script is short of ' . $this->inBrace . ' end brace(s)',
                    1507645349
                );
            }
            $configuration = $this->removeCommentsAndDotsRecursive($this->setup);
        }
        return $configuration;
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
        $rawConfigurationString = $this->getDefaultConfigurationRawString($extensionKey);
        $theConstants = [];
        if ((string)$rawConfigurationString !== '') {
            $this->raw = explode(LF, $rawConfigurationString);
            $this->rawPointer = 0;
            $this->setup = [];
            $this->parseSub($this->setup);
            if ($this->inBrace) {
                throw new \RuntimeException(
                    'Line ' . ($this->rawPointer - 1) . ': The script is short of ' . $this->inBrace . ' end brace(s)',
                    1507645348
                );
            }
            $parsedConstants = $this->setup;
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
        }
        return $theConstants;
    }

    /**
     * Return content of an extensions ext_conf_template.txt file if
     * the file exists, empty string if file does not exist.
     *
     * @param string $extensionKey Extension key
     * @return string
     */
    protected function getDefaultConfigurationRawString(string $extensionKey): string
    {
        $rawString = '';
        $extConfTemplateFileLocation = GeneralUtility::getFileAbsFileName(
            'EXT:' . $extensionKey . '/ext_conf_template.txt'
        );
        if (file_exists($extConfTemplateFileLocation)) {
            $rawString = file_get_contents($extConfTemplateFileLocation);
        }
        return $rawString;
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
     * Parsing the $this->raw TypoScript lines from pointer, $this->rawP
     *
     * @param array $setup Reference to the setup array in which to accumulate the values.
     */
    protected function parseSub(array &$setup)
    {
        while (isset($this->raw[$this->rawPointer])) {
            $line = ltrim($this->raw[$this->rawPointer]);
            $this->rawPointer++;
            // Set comment flag?
            if (strpos($line, '/*') === 0) {
                $this->commentSet = 1;
            }
            if (!$this->commentSet && ($line)) {
                if ($line[0] !== '}' && $line[0] !== '#' && $line[0] !== '/') {
                    // If not brace-end or comment
                    // Find object name string until we meet an operator
                    $varL = strcspn($line, TAB . ' {=<>(');
                    // check for special ":=" operator
                    if ($varL > 0 && substr($line, $varL-1, 2) === ':=') {
                        --$varL;
                    }
                    // also remove tabs after the object string name
                    $objStrName = substr($line, 0, $varL);
                    if ($objStrName !== '') {
                        $r = [];
                        if (preg_match('/[^[:alnum:]_\\\\\\.:-]/i', $objStrName, $r)) {
                            throw new \RuntimeException(
                                'Line ' . ($this->rawPointer - 1) . ': Object Name String, "' . htmlspecialchars($objStrName) . '" contains invalid character "' . $r[0] . '". Must be alphanumeric or one of: "_:-\\."',
                                1507645381
                            );
                        }
                        $line = ltrim(substr($line, $varL));
                        if ($line === '') {
                            throw new \RuntimeException(
                                    'Line ' . ($this->rawPointer - 1) . ': Object Name String, "' . htmlspecialchars($objStrName) . '" was not followed by any operator, =<>({',
                                    1507645417
                                );
                        }
                        switch ($line[0]) {
                                    case '=':
                                        if (strpos($objStrName, '.') !== false) {
                                            $value = [];
                                            $value[0] = trim(substr($line, 1));
                                            $this->setVal($objStrName, $setup, $value);
                                        } else {
                                            $setup[$objStrName] = trim(substr($line, 1));
                                            if ($this->lastComment) {
                                                // Setting comment..
                                                $setup[$objStrName . '..'] .= $this->lastComment;
                                            }
                                        }
                                        break;
                                    case '{':
                                        $this->inBrace++;
                                        if (strpos($objStrName, '.') !== false) {
                                            $this->rollParseSub($objStrName, $setup);
                                        } else {
                                            if (!isset($setup[$objStrName . '.'])) {
                                                $setup[$objStrName . '.'] = [];
                                            }
                                            $this->parseSub($setup[$objStrName . '.']);
                                        }
                                        break;
                                    default:
                                        throw new \RuntimeException(
                                            'Line ' . ($this->rawPointer - 1) . ': Object Name String, "' . htmlspecialchars($objStrName) . '" was not followed by any operator, =<>({',
                                            1507645445
                                        );
                                }

                        $this->lastComment = '';
                    }
                } elseif ($line[0] === '}') {
                    $this->inBrace--;
                    $this->lastComment = '';
                    if ($this->inBrace < 0) {
                        throw new \RuntimeException(
                            'Line ' . ($this->rawPointer - 1) . ': An end brace is in excess.',
                            1507645489
                        );
                    }
                    break;
                } else {
                    $this->lastComment .= rtrim($line) . LF;
                }
            }
            // Unset comment
            if ($this->commentSet) {
                if (strpos($line, '*/') === 0) {
                    $this->commentSet = 0;
                }
            }
        }
    }

    /**
     * Parsing of TypoScript keys inside a curly brace where the key is composite of at least two keys,
     * thus having to recursively call itself to get the value
     *
     * @param string $string The object sub-path, eg "thisprop.another_prot
     * @param array $setup The local setup array from the function calling this function
     */
    protected function rollParseSub($string, array &$setup)
    {
        if ((string)$string === '') {
            return;
        }
        list($key, $remainingKey) = $this->parseNextKeySegment($string);
        $key .= '.';
        if (!isset($setup[$key])) {
            $setup[$key] = [];
        }
        $remainingKey === ''
            ? $this->parseSub($setup[$key])
            : $this->rollParseSub($remainingKey, $setup[$key]);
    }

    /**
     * Setting a value/property of an object string in the setup array.
     *
     * @param string $string The object sub-path, eg "thisprop.another_prot
     * @param array $setup The local setup array from the function calling this function.
     * @param void
     */
    protected function setVal($string, array &$setup, $value)
    {
        if ((string)$string === '') {
            return;
        }

        list($key, $remainingKey) = $this->parseNextKeySegment($string);
        $subKey = $key . '.';
        if ($remainingKey === '') {
            if (isset($value[0])) {
                $setup[$key] = $value[0];
            }
            if (isset($value[1])) {
                $setup[$subKey] = $value[1];
            }
            if ($this->lastComment) {
                $setup[$key . '..'] .= $this->lastComment;
            }
        } else {
            if (!isset($setup[$subKey])) {
                $setup[$subKey] = [];
            }
            $this->setVal($remainingKey, $setup[$subKey], $value);
        }
    }

    /**
     * Determines the first key segment of a TypoScript key by searching for the first
     * unescaped dot in the given key string.
     *
     * Since the escape characters are only needed to correctly determine the key
     * segment any escape characters before the first unescaped dot are
     * stripped from the key.
     *
     * @param string $key The key, possibly consisting of multiple key segments separated by unescaped dots
     * @return array Array with key segment and remaining part of $key
     */
    protected function parseNextKeySegment($key)
    {
        // if no dot is in the key, nothing to do
        $dotPosition = strpos($key, '.');
        if ($dotPosition === false) {
            return [$key, ''];
        }

        if (strpos($key, '\\') !== false) {
            // backslashes are in the key, so we do further parsing
            while ($dotPosition !== false) {
                if ($dotPosition > 0 && $key[$dotPosition - 1] !== '\\' || $dotPosition > 1 && $key[$dotPosition - 2] === '\\') {
                    break;
                }
                // escaped dot found, continue
                $dotPosition = strpos($key, '.', $dotPosition + 1);
            }

            if ($dotPosition === false) {
                // no regular dot found
                $keySegment = $key;
                $remainingKey = '';
            } else {
                if ($dotPosition > 1 && $key[$dotPosition - 2] === '\\' && $key[$dotPosition - 1] === '\\') {
                    $keySegment = substr($key, 0, $dotPosition - 1);
                } else {
                    $keySegment = substr($key, 0, $dotPosition);
                }
                $remainingKey = substr($key, $dotPosition + 1);
            }

            // fix key segment by removing escape sequences
            $keySegment = str_replace('\\.', '.', $keySegment);
        } else {
            // no backslash in the key, we're fine off
            list($keySegment, $remainingKey) = explode('.', $key, 2);
        }
        return [$keySegment, $remainingKey];
    }

    /**
     * "Comments" from the "TypoScript" parser below are identified by two (!) dots at the end of array keys
     * and all array keys have a single dot at the end, if they have sub arrays. This is cleaned here.
     *
     * Incoming array:
     * [
     *  'automaticInstallation' => '1',
     *  'automaticInstallation..' => '# cat=basic/enabled; ...'
     *  'FE.' => [
     *      'enabled' = '1',
     *      'enabled..' => '# cat=basic/enabled; ...'
     *  ]
     * ]
     *
     * Output array:
     * [
     *  'automaticInstallation' => '1',
     *  'FE' => [
     *      'enabled' => '1',
     * ]
     */
    protected function removeCommentsAndDotsRecursive(array $config): array
    {
        $cleanedConfig = [];
        foreach ($config as $key => $value) {
            if (substr($key, -2) === '..') {
                continue;
            }
            if (substr($key, -1) === '.') {
                $cleanedConfig[rtrim($key, '.')] = $this->removeCommentsAndDotsRecursive($value);
            } else {
                $cleanedConfig[$key] = $value;
            }
        }
        return $cleanedConfig;
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
