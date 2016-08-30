<?php
namespace TYPO3\CMS\Extbase\Utility;

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

/**
 * Utilities to manage plugins and  modules of an extension. Also useful to auto-generate the autoloader registry
 * file ext_autoload.php.
 */
class ExtensionUtility
{
    const PLUGIN_TYPE_PLUGIN = 'list_type';
    const PLUGIN_TYPE_CONTENT_ELEMENT = 'CType';

    /**
     * Add auto-generated TypoScript to configure the Extbase Dispatcher.
     *
     * When adding a frontend plugin you will have to add both an entry to the TCA definition
     * of tt_content table AND to the TypoScript template which must initiate the rendering.
     * Including the plugin code after "defaultContentRendering" adds the necessary TypoScript
     * for calling the appropriate controller and action of your plugin.
     * This means, it will also work for the extension "css_styled_content"
     * FOR USE IN ext_localconf.php FILES
     * Usage: 2
     *
     * @param string $extensionName The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
     * @param string $pluginName must be a unique id for your plugin in UpperCamelCase (the string length of the extension key added to the length of the plugin name should be less than 32!)
     * @param array $controllerActions is an array of allowed combinations of controller and action stored in an array (controller name as key and a comma separated list of action names as value, the first controller and its first action is chosen as default)
     * @param array $nonCacheableControllerActions is an optional array of controller name and  action names which should not be cached (array as defined in $controllerActions)
     * @param string $pluginType either \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN (default) or \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
     * @throws \InvalidArgumentException
     * @return void
     */
    public static function configurePlugin($extensionName, $pluginName, array $controllerActions, array $nonCacheableControllerActions = [], $pluginType = self::PLUGIN_TYPE_PLUGIN)
    {
        self::checkPluginNameFormat($pluginName);
        self::checkExtensionNameFormat($extensionName);

        // Check if vendor name is prepended to extensionName in the format {vendorName}.{extensionName}
        $vendorName = null;
        $delimiterPosition = strrpos($extensionName, '.');
        if ($delimiterPosition !== false) {
            $vendorName = str_replace('.', '\\', substr($extensionName, 0, $delimiterPosition));
            $extensionName = substr($extensionName, $delimiterPosition + 1);

            if (!empty($vendorName)) {
                self::checkVendorNameFormat($vendorName, $extensionName);
            }
        }
        $extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));

        $pluginSignature = strtolower($extensionName . '_' . $pluginName);
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName] = [];
        }
        foreach ($controllerActions as $controllerName => $actionsList) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'][$controllerName] = ['actions' => \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $actionsList)];
            if (!empty($nonCacheableControllerActions[$controllerName])) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'][$controllerName]['nonCacheableActions'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $nonCacheableControllerActions[$controllerName]);
            }
        }

        switch ($pluginType) {
            case self::PLUGIN_TYPE_PLUGIN:
                $pluginContent = trim('
tt_content.list.20.' . $pluginSignature . ' = USER
tt_content.list.20.' . $pluginSignature . ' {
	userFunc = TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run
	extensionName = ' . $extensionName . '
	pluginName = ' . $pluginName . (null !== $vendorName ? ("\n\t" . 'vendorName = ' . $vendorName) : '') . '
}');
                break;
            case self::PLUGIN_TYPE_CONTENT_ELEMENT:
                $pluginContent = trim('
tt_content.' . $pluginSignature . ' = COA
tt_content.' . $pluginSignature . ' {
	10 = < lib.stdheader
	20 = USER
	20 {
		userFunc = TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run
		extensionName = ' . $extensionName . '
		pluginName = ' . $pluginName . (null !== $vendorName ? ("\n\t\t" . 'vendorName = ' . $vendorName) : '') . '
	}
}');
                break;
            default:
                throw new \InvalidArgumentException('The pluginType "' . $pluginType . '" is not suported', 1289858856);
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['pluginType'] = $pluginType;
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($extensionName, 'setup', '
# Setting ' . $extensionName . ' plugin TypoScript
' . $pluginContent, 'defaultContentRendering');
    }

    /**
     * Register an Extbase PlugIn into backend's list of plugins
     * FOR USE IN ext_tables.php FILES
     *
     * @param string $extensionName The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
     * @param string $pluginName must be a unique id for your plugin in UpperCamelCase (the string length of the extension key added to the length of the plugin name should be less than 32!)
     * @param string $pluginTitle is a speaking title of the plugin that will be displayed in the drop down menu in the backend
     * @param string $pluginIconPathAndFilename is a path to an icon file (relative to TYPO3_mainDir), that will be displayed in the drop down menu in the backend (optional)
     * @throws \InvalidArgumentException
     * @return void
     */
    public static function registerPlugin($extensionName, $pluginName, $pluginTitle, $pluginIconPathAndFilename = null)
    {
        self::checkPluginNameFormat($pluginName);
        self::checkExtensionNameFormat($extensionName);

        $delimiterPosition = strrpos($extensionName, '.');
        if ($delimiterPosition !== false) {
            $extensionName = substr($extensionName, $delimiterPosition + 1);
        }
        $extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
        $pluginSignature = strtolower($extensionName) . '_' . strtolower($pluginName);

        // At this point $extensionName is normalized, no matter which format the method was feeded with.
        // Calculate the original extensionKey from this again.
        $extensionKey = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName);

        // pluginType is usually defined by configurePlugin() in the global array. Use this or fall back to default "list_type".
        $pluginType = isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['pluginType'])
            ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['pluginType']
            : 'list_type';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
            [$pluginTitle, $pluginSignature, $pluginIconPathAndFilename],
            $pluginType,
            $extensionKey
        );
    }

    /**
     * This method is called from \TYPO3\CMS\Backend\Module\ModuleLoader::checkMod
     * and it replaces old conf.php.
     *
     * @param string $moduleSignature The module name
     * @param string $modulePath Absolute path to module (not used by Extbase currently)
     * @return array Configuration of the module
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, please use the according method in \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::configureModule
     */
    public static function configureModule($moduleSignature, $modulePath)
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
        return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::configureModule($moduleSignature, $modulePath);
    }

    /**
     * Registers an Extbase module (main or sub) to the backend interface.
     * FOR USE IN ext_tables.php FILES
     *
     * @param string $extensionName The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
     * @param string $mainModuleName The main module key. So $main would be an index in the $TBE_MODULES array and $sub could be an element in the lists there. If $subModuleName is not set a blank $extensionName module is created
     * @param string $subModuleName The submodule key.
     * @param string $position This can be used to set the position of the $sub module within the list of existing submodules for the main module. $position has this syntax: [cmd]:[submodule-key]. cmd can be "after", "before" or "top" (or blank which is default). If "after"/"before" then submodule will be inserted after/before the existing submodule with [submodule-key] if found. If not found, the bottom of list. If "top" the module is inserted in the top of the submodule list.
     * @param array $controllerActions is an array of allowed combinations of controller and action stored in an array (controller name as key and a comma separated list of action names as value, the first controller and its first action is chosen as default)
     * @param array $moduleConfiguration The configuration options of the module (icon, locallang.xlf file)
     * @throws \InvalidArgumentException
     * @return void
     */
    public static function registerModule($extensionName, $mainModuleName = '', $subModuleName = '', $position = '', array $controllerActions = [], array $moduleConfiguration = [])
    {
        self::checkExtensionNameFormat($extensionName);

        // Check if vendor name is prepended to extensionName in the format {vendorName}.{extensionName}
        $vendorName = null;
        if (false !== $delimiterPosition = strrpos($extensionName, '.')) {
            $vendorName = str_replace('.', '\\', substr($extensionName, 0, $delimiterPosition));
            $extensionName = substr($extensionName, $delimiterPosition + 1);

            if (!empty($vendorName)) {
                self::checkVendorNameFormat($vendorName, $extensionName);
            }
        }
        $extensionKey = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName);
        $extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
        $defaultModuleConfiguration = [
            'access' => 'admin',
            'icon' => 'EXT:extbase/ext_icon.png',
            'labels' => '',
            'extRelPath' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($extensionKey) . 'Classes/'
        ];
        if ($mainModuleName !== '' && !array_key_exists($mainModuleName, $GLOBALS['TBE_MODULES'])) {
            $mainModuleName = $extensionName . \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($mainModuleName);
        } else {
            $mainModuleName = $mainModuleName !== '' ? $mainModuleName : 'web';
        }
        // add mandatory parameter to use new pagetree
        if ($mainModuleName === 'web') {
            $defaultModuleConfiguration['navigationComponentId'] = 'typo3-pagetree';
        }
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($defaultModuleConfiguration, $moduleConfiguration);
        $moduleConfiguration = $defaultModuleConfiguration;
        $moduleSignature = $mainModuleName;
        if ($subModuleName !== '') {
            $subModuleName = $extensionName . \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($subModuleName);
            $moduleSignature .= '_' . $subModuleName;
        }
        $moduleConfiguration['name'] = $moduleSignature;
        if (null !== $vendorName) {
            $moduleConfiguration['vendorName'] = $vendorName;
        }
        $moduleConfiguration['extensionName'] = $extensionName;
        $moduleConfiguration['configureModuleFunction'] = [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class, 'configureModule'];
        $GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature] = $moduleConfiguration;
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$moduleSignature])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$moduleSignature] = [];
        }
        foreach ($controllerActions as $controllerName => $actions) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$moduleSignature]['controllers'][$controllerName] = [
                'actions' => \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $actions)
            ];
        }
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule($mainModuleName, $subModuleName, $position);
    }

    /**
     * Register a type converter by class name.
     *
     * @param string $typeConverterClassName
     * @return void
     * @api
     */
    public static function registerTypeConverter($typeConverterClassName)
    {
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'] = [];
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'][] = $typeConverterClassName;
    }

    /**
     * Check a given vendor name for CGL compliance.
     * Log a deprecation message if it is not.
     *
     * @param string $vendorName The vendor name to check
     * @param string $extensionName The extension name that is affected
     * @return void
     */
    protected static function checkVendorNameFormat($vendorName, $extensionName)
    {
        if (preg_match('/^[A-Z]/', $vendorName) !== 1) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('The vendor name from tx_' . $extensionName . ' must begin with a capital letter.');
        }
    }

    /**
     * Check a given extension name for validity.
     *
     * @param string $extensionName The name of the extension
     * @throws \InvalidArgumentException
     * @return void
     */
    protected static function checkExtensionNameFormat($extensionName)
    {
        if (empty($extensionName)) {
            throw new \InvalidArgumentException('The extension name must not be empty', 1239891990);
        }
    }

    /**
     * Check a given plugin name for validity.
     *
     * @param string $pluginName The name of the plugin
     * @throws \InvalidArgumentException
     * @return void
     */
    protected static function checkPluginNameFormat($pluginName)
    {
        if (empty($pluginName)) {
            throw new \InvalidArgumentException('The plugin name must not be empty', 1239891988);
        }
    }
}
