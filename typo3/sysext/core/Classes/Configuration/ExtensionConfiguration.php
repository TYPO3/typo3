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
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * API to get() and set() instance specific extension configuration options.
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
 */
class ExtensionConfiguration
{
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
     * // in ext_conf_template.txt "FE.forceSalted = defaultValue"
     * ->get('myExtension', 'FE/forceSalted')
     *
     * Notes:
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
     * @api
     */
    public function get(string $extension, string $path = '')
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension]) || !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension])) {
            throw new ExtensionConfigurationExtensionNotConfiguredException(
                'No extension configuration for extension ' . $extension . ' found. Either this extension'
                . ' has no extension configuration or the configuration is not up to date. Execute the'
                . ' install tool to update configuration.',
                1509654728
            );
        }
        if (empty($path)) {
            return $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extension];
        }
        if (!ArrayUtility::isValidPath($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'], $extension . '/' . $path)) {
            throw new ExtensionConfigurationPathDoesNotExistException(
                'Path ' . $path . ' does not exist in extension configuration',
                1509977699
            );
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
     * // Enable a single feature
     * ->set('myExtension', 'myFeature', true)
     *
     * // Set a full extension configuration ($value could be a nested array, too)
     * ->set('myExtension', '', ['aFeature' => 'true', 'aCustomClass' => 'css-foo'])
     *
     * // Set a full sub path
     * ->set('myExtension', 'myFeatureCategory', ['aFeature' => 'true', 'aCustomClass' => 'css-foo'])
     *
     * // Unset a whole extension configuration
     * ->set('myExtension')
     *
     * // Unset a single value or sub path
     * ->set('myExtension', 'myFeature')
     *
     * Notes:
     * - $path is NOT validated. It is up to an ext author to also define them in
     *   ext_conf_template.txt to have an interface in install tool reflecting these settings
     * - If $path is currently an array, $value overrides the whole thing. Merging existing values
     *   is up to the extension author
     * - Values are not type safe, if the install tool wrote them,
     *   boolean true could become string 1 on ->get()
     * - It is not possible to store 'null' as value, giving $value=null
     *   or no value at all will unset the path
     * - Setting a value and calling ->get() afterwards will still return the old (!) value, the
     *   new value is only available in ->get() with next request. This is to have consistent
     *   values if the setting is possibly overwritten in AdditionalConfiguration again, which
     *   this API does not know and is only evaluated early during bootstrap.
     * - Warning on AdditionalConfiguration.php: If this file overwrites settings, it spoils the
     *   ->set() call and values may not finally end up as expected. Avoid using AdditionalConfiguration.php
     *   in general ...
     *
     * @param string $extension Extension name
     * @param string $path Configuration path to set - eg. "featureCategory/coolThingIsEnabled"
     * @param null $value The value. If null, unset the path
     * @api
     */
    public function set(string $extension, string $path = '', $value = null)
    {
        if (empty($extension)) {
            throw new \RuntimeException('extension name must not be empty', 1509715852);
        }
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        if ($path === '' && $value === null) {
            // Remove whole extension config
            $configurationManager->removeLocalConfigurationKeysByPath(['EXTENSIONS/' . $extension]);
        } elseif ($path !== '' && $value === null) {
            // Remove a single value or sub path
            $configurationManager->removeLocalConfigurationKeysByPath(['EXTENSIONS/' . $extension . '/' . $path]);
        } elseif ($path === '' && $value !== null) {
            // Set full extension config
            $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS/' . $extension, $value);
        } else {
            // Set single path
            $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS/' . $extension . '/' . $path, $value);
        }

        // After TYPO3_CONF_VARS['EXTENSIONS'] has been written, update legacy layer TYPO3_CONF_VARS['EXTENSIONS']['extConf']
        // @deprecated since TYPO3 v9, will be removed in v10 with removal of old serialized 'extConf' layer
        $extensionsConfigs = $configurationManager->getConfigurationValueByPath('EXTENSIONS');
        foreach ($extensionsConfigs as $extensionName => $extensionConfig) {
            $extensionConfig = $this->addDotsToArrayKeysRecursiveForLegacyExtConf($extensionConfig);
            $configurationManager->setLocalConfigurationValueByPath('EXT/extConf/' . $extensionName, serialize($extensionConfig));
        }
    }

    /**
     * The old EXT/extConf layer had '.' (dots) at the end of all nested array keys. This is created here
     * to keep EXT/extConf format compatible with old not yet adapted extensions.
     * Most prominent usage is ext:saltedpasswords which uses sub keys like FE.forceSalted and BE.forceSalted,
     * but extensions may rely on ending dots if using legacy unserialize() on their extensions, too.
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
     * @deprecated since TYPO3 v9, will be removed in v10 with removal of old serialized 'extConf' layer
     */
    private function addDotsToArrayKeysRecursiveForLegacyExtConf(array $extensionConfig)
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
}
