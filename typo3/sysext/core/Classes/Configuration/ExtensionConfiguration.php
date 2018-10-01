<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Configuration;

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
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * API to get() instance specific extension configuration options.
 *
 * Extension authors are encouraged to use this API - it is currently a simple
 * wrapper to access TYPO3_CONF_VARS['EXTENSIONS'] but could later become something
 * different in case core decides to store extension configuration elsewhere.
 *
 * Extension authors must not access TYPO3_CONF_VARS['EXTENSIONS'] on their own.
 *
 * Extension configurations are often 'feature flags' currently defined by
 * ext_conf_template.txt files. The core (more specifically the install tool)
 * takes care default values and overridden values are properly prepared upon
 * loading or updating an extension.
 *
 * Note only ->get() is official API and other public methods are low level
 * core internal API that is usually only used by extension manager and install tool.
 */
class ExtensionConfiguration
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
     * Get a single configuration value, a sub array or the whole configuration.
     *
     * Examples:
     * // Simple and typical usage: Get a single config value, or an array if the key is a "TypoScript"
     * // a-like sub-path in ext_conf_template.txt "foo.bar = defaultValue"
     * ->get('myExtension', 'aConfigKey');
     *
     * // Get all current configuration values, always an array
     * ->get('myExtension');
     *
     * // Get a nested config value if the path is a "TypoScript" a-like sub-path
     * // in ext_conf_template.txt "topLevelKey.subLevelKey = defaultValue"
     * ->get('myExtension', 'topLevelKey/subLevelKey')
     *
     * Notes:
     * - If a configuration or configuration path of an extension is not found, the
     *   code tries to synchronize configuration with ext_conf_template.txt first, only
     *   if still not found, it will throw exceptions.
     * - Return values are NOT type safe: A boolean false could be returned as string 0.
     *   Cast accordingly.
     * - This API throws exceptions if the path does not exist or the extension
     *   configuration is not available. The install tool takes care any new
     *   ext_conf_template.txt values are available TYPO3_CONF_VARS['EXTENSIONS'],
     *   a thrown exception indicates a programming error on developer side
     *   and should not be caught.
     * - It is not checked if the extension in question is loaded at all,
     *   it's just checked the extension configuration path exists.
     * - Extensions should typically not get configuration of a different extension.
     *
     * @param string $extension Extension name
     * @param string $path Configuration path - eg. "featureCategory/coolThingIsEnabled"
     * @return mixed The value. Can be a sub array or a single value.
     * @throws ExtensionConfigurationExtensionNotConfiguredException If ext configuration does no exist
     * @throws ExtensionConfigurationPathDoesNotExistException If a requested extension path does not exist
     */
    public function get(string $extension, string $path = '')
    {
        $hasBeenSynchronized = false;
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension]) || !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension])) {
            // This if() should not be hit at "casual" runtime, but only in early setup phases
            $this->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions();
            $hasBeenSynchronized = true;
            if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension]) || !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension])) {
                // If there is still no such entry, even after sync -> throw
                throw new ExtensionConfigurationExtensionNotConfiguredException(
                    'No extension configuration for extension ' . $extension . ' found. Either this extension'
                    . ' has no extension configuration or the configuration is not up to date. Execute the'
                    . ' install tool to update configuration.',
                    1509654728
                );
            }
        }
        if (empty($path)) {
            return $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension];
        }
        if (!ArrayUtility::isValidPath($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'], $extension . '/' . $path)) {
            // This if() should not be hit at "casual" runtime, but only in early setup phases
            if (!$hasBeenSynchronized) {
                $this->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions();
            }
            // If there is still no such entry, even after sync -> throw
            if (!ArrayUtility::isValidPath($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'], $extension . '/' . $path)) {
                throw new ExtensionConfigurationPathDoesNotExistException(
                    'Path ' . $path . ' does not exist in extension configuration',
                    1509977699
                );
            }
        }
        return ArrayUtility::getValueByPath($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'], $extension . '/' . $path);
    }

    /**
     * Store a new or overwrite an existing configuration value.
     *
     * This is typically used by core internal low level tasks like the install
     * tool but may become handy if an extension needs to update extension configuration
     * on the fly for whatever reason.
     *
     * Examples:
     * // Set a full extension configuration ($value could be a nested array, too)
     * ->set('myExtension', ['aFeature' => 'true', 'aCustomClass' => 'css-foo'])
     *
     * // Unset a whole extension configuration
     * ->set('myExtension')
     *
     * Notes:
     * - Do NOT call this at arbitrary places during runtime (eg. NOT in ext_localconf.php or
     *   similar). ->set() is not supposed to be called each request since it writes LocalConfiguration
     *   each time. This API is however OK to be called from extension manager hooks.
     * - Values are not type safe, if the install tool wrote them,
     *   boolean true could become string 1 on ->get()
     * - It is not possible to store 'null' as value, giving $value=null
     *   or no value at all will unset the path
     * - Setting a value and calling ->get() afterwards will still return the new value.
     * - Warning on AdditionalConfiguration.php: If this file overwrites settings, it spoils the
     *   ->set() call and values may not end up as expected.
     *
     * @param string $extension Extension name
     * @param string $path Configuration path to set - eg. "featureCategory/coolThingIsEnabled"
     * @param null $value The value. If null, unset the path
     * @internal
     */
    public function set(string $extension, string $path = '', $value = null)
    {
        if (empty($extension)) {
            throw new \RuntimeException('extension name must not be empty', 1509715852);
        }
        if (!empty($path)) {
            // @todo: this functionality can be removed once EXT:bootstrap_package is adapted to the new API.
            $extensionConfiguration = $this->get($extension);
            $value = ArrayUtility::setValueByPath($extensionConfiguration, $path, $value);
        }
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        if ($value === null) {
            // Remove whole extension config
            $configurationManager->removeLocalConfigurationKeysByPath(['EXTENSIONS/' . $extension]);
            if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension])) {
                unset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension]);
            }
        } else {
            // Set full extension config
            $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS/' . $extension, $value);
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension] = $value;
        }

        // After TYPO3_CONF_VARS['EXTENSIONS'] has been written, update legacy layer TYPO3_CONF_VARS['EXTENSIONS']['extConf']
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 with removal of old serialized 'extConf' layer
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'])) {
            $extConfArray = [];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] as $extensionName => $extensionConfig) {
                $extConfArray[$extensionName] = serialize($this->addDotsToArrayKeysRecursiveForLegacyExtConf($extensionConfig));
            }
            $configurationManager->setLocalConfigurationValueByPath('EXT/extConf', $extConfArray);
            $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'] = $extConfArray;
        }
    }

    /**
     * Set new configuration of all extensions and reload TYPO3_CONF_VARS.
     * This is a "do all" variant of set() for all extensions that prevents
     * writing and loading LocalConfiguration many times.
     *
     * @param array $configuration Configuration of all extensions
     * @internal
     */
    public function setAll(array $configuration)
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS', $configuration);
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] = $configuration;

        // After TYPO3_CONF_VARS['EXTENSIONS'] has been written, update legacy layer TYPO3_CONF_VARS['EXTENSIONS']['extConf']
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 with removal of old serialized 'extConf' layer
        $extConfArray = [];
        foreach ($configuration as $extensionName => $extensionConfig) {
            $extConfArray[$extensionName] = serialize($this->addDotsToArrayKeysRecursiveForLegacyExtConf($extensionConfig));
        }
        $configurationManager->setLocalConfigurationValueByPath('EXT/extConf', $extConfArray);
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'] = $extConfArray;
    }

    /**
     * If there are new config settings in ext_conf_template of an extension,
     * they are found here and synchronized to LocalConfiguration['EXTENSIONS'].
     *
     * Used when entering the install tool, during installation and if calling ->get()
     * with an extension or path that is not yet found in LocalConfiguration
     *
     * @internal
     */
    public function synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions()
    {
        $activePackages = GeneralUtility::makeInstance(PackageManager::class)->getActivePackages();
        $fullConfiguration = [];
        $currentLocalConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] ?? [];
        foreach ($activePackages as $package) {
            if (!@is_file($package->getPackagePath() . 'ext_conf_template.txt')) {
                continue;
            }
            $extensionKey = $package->getPackageKey();
            $currentExtensionConfig = $currentLocalConfiguration[$extensionKey] ?? [];
            $extConfTemplateConfiguration = $this->getExtConfTablesWithoutCommentsAsNestedArrayWithoutDots($extensionKey);
            ArrayUtility::mergeRecursiveWithOverrule($extConfTemplateConfiguration, $currentExtensionConfig);
            if (!empty($extConfTemplateConfiguration)) {
                $fullConfiguration[$extensionKey] = $extConfTemplateConfiguration;
            }
        }
        // Write new config if changed. Loose array comparison to not write if only array key order is different
        if ($fullConfiguration != $currentLocalConfiguration) {
            $this->setAll($fullConfiguration);
        }
    }

    /**
     * Read values from ext_conf_template, verify if they are in LocalConfiguration.php
     * already and if not, add them.
     *
     * Used public by extension manager when updating extension
     *
     * @param string $extensionKey The extension to sync
     * @internal
     */
    public function synchronizeExtConfTemplateWithLocalConfiguration(string $extensionKey)
    {
        $package = GeneralUtility::makeInstance(PackageManager::class)->getPackage($extensionKey);
        if (!@is_file($package->getPackagePath() . 'ext_conf_template.txt')) {
            return;
        }
        $currentLocalConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extensionKey] ?? [];
        $extConfTemplateConfiguration = $this->getExtConfTablesWithoutCommentsAsNestedArrayWithoutDots($extensionKey);
        ArrayUtility::mergeRecursiveWithOverrule($extConfTemplateConfiguration, $currentLocalConfiguration);
        // Write new config if changed. Loose array comparison to not write if only array key order is different
        if ($extConfTemplateConfiguration != $currentLocalConfiguration) {
            $this->set($extensionKey, '', $extConfTemplateConfiguration);
        }
    }

    /**
     * The old EXT/extConf layer had '.' (dots) at the end of all nested array keys. This is created here
     * to keep EXT/extConf format compatible with old not yet adapted extensions.
     * But extensions may rely on ending dots if using legacy unserialize() on their extensions, too.
     *
     * A EXTENSIONS array like:
     * TYPO3_CONF_VARS['EXTENSIONS']['someExtension'] => [
     *      'someKey' => [
     *          'someSubKey' => [
     *              'someSubSubKey' => 'someValue',
     *          ],
     *      ],
     * ]
     * becomes (serialized) in old EXT/extConf (mind the dots and end of array keys for sub arrays):
     * TYPO3_CONF_VARS['EXTENSIONS']['someExtension'] => [
     *      'someKey.' => [
     *          'someSubKey.' => [
     *              'someSubSubKey' => 'someValue',
     *          ],
     *      ],
     * ]
     *
     * @param array $extensionConfig
     * @return array
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 with removal of old serialized 'extConf' layer
     */
    private function addDotsToArrayKeysRecursiveForLegacyExtConf(array $extensionConfig): array
    {
        $newArray = [];
        foreach ($extensionConfig as $key => $value) {
            if (is_array($value)) {
                $newArray[$key . '.'] = $this->addDotsToArrayKeysRecursiveForLegacyExtConf($value);
            } else {
                $newArray[$key] = $value;
            }
        }
        return $newArray;
    }

    /**
     * Helper method of ext_conf_template.txt parsing.
     *
     * Poor man version of getDefaultConfigurationFromExtConfTemplateAsValuedArray() which ignores
     * comments and returns ext_conf_template as array where nested keys have no dots.
     *
     * @param string $extensionKey
     * @return array
     */
    protected function getExtConfTablesWithoutCommentsAsNestedArrayWithoutDots(string $extensionKey): array
    {
        $configuration = $this->getParsedExtConfTemplate($extensionKey);
        return $this->removeCommentsAndDotsRecursive($configuration);
    }

    /**
     * Trigger main ext_conf_template.txt parsing logic.
     * Needs to be public as it is used by install tool ExtensionConfigurationService
     * which adds the comment parsing on top for display of options in install tool.
     *
     * @param string $extensionKey
     * @return array
     * @internal
     */
    public function getParsedExtConfTemplate(string $extensionKey): array
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
            $configuration = $this->setup;
        }
        return $configuration;
    }

    /**
     * Helper method of ext_conf_template.txt parsing.
     *
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
     * Helper method of ext_conf_template.txt parsing.
     *
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
     *
     * @param array $config Incoming configuration
     * @return array
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
     * Helper method of ext_conf_template.txt parsing.
     *
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
            if (!$this->commentSet && $line) {
                if ($line[0] !== '}' && $line[0] !== '#' && $line[0] !== '/') {
                    // If not brace-end or comment
                    // Find object name string until we meet an operator
                    $varL = strcspn($line, "\t" . ' {=<>(');
                    // check for special ":=" operator
                    if ($varL > 0 && substr($line, $varL - 1, 2) === ':=') {
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
     * Helper method of ext_conf_template.txt parsing.
     *
     * Parsing of TypoScript keys inside a curly brace where the key is composite of at least two keys,
     * thus having to recursively call itself to get the value.
     *
     * @param string $string The object sub-path, eg "thisprop.another_prot"
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
     * Helper method of ext_conf_template.txt parsing.
     *
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
     * Helper method of ext_conf_template.txt parsing.
     *
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
    protected function parseNextKeySegment($key): array
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
}
