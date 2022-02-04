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

namespace TYPO3\CMS\Extbase\Utility;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchControllerException;

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
     * FOR USE IN ext_localconf.php FILES
     * Usage: 2
     *
     * @param string $extensionName The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
     * @param string $pluginName must be a unique id for your plugin in UpperCamelCase (the string length of the extension key added to the length of the plugin name should be less than 32!)
     * @param array $controllerActions is an array of allowed combinations of controller and action stored in an array (controller name as key and a comma separated list of action names as value, the first controller and its first action is chosen as default)
     * @param array $nonCacheableControllerActions is an optional array of controller name and  action names which should not be cached (array as defined in $controllerActions)
     * @param string $pluginType either \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_PLUGIN (default) or \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
     * @throws \InvalidArgumentException
     */
    public static function configurePlugin($extensionName, $pluginName, array $controllerActions, array $nonCacheableControllerActions = [], $pluginType = self::PLUGIN_TYPE_PLUGIN)
    {
        self::checkPluginNameFormat($pluginName);
        self::checkExtensionNameFormat($extensionName);

        // Check if vendor name is prepended to extensionName in the format {vendorName}.{extensionName}
        $delimiterPosition = strrpos($extensionName, '.');
        if ($delimiterPosition !== false) {
            $vendorName = str_replace('.', '\\', substr($extensionName, 0, $delimiterPosition));
            trigger_error(
                'Calling method ' . __METHOD__ . ' with argument $extensionName ("' . $extensionName . '") containing the vendor name ("' . $vendorName . '") is deprecated and will stop working in TYPO3 11.0.',
                E_USER_DEPRECATED
            );
            $extensionName = substr($extensionName, $delimiterPosition + 1);

            if (!empty($vendorName)) {
                self::checkVendorNameFormat($vendorName, $extensionName);
            }
        }
        $extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));

        $pluginSignature = strtolower($extensionName . '_' . $pluginName);
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName] ?? false)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName] = [];
        }
        foreach ($controllerActions as $controllerClassName => $actionsList) {
            $controllerAlias = self::resolveControllerAliasFromControllerClassName($controllerClassName);
            $vendorName = self::resolveVendorFromExtensionAndControllerClassName($extensionName, $controllerClassName);
            if (!empty($vendorName)) {
                self::checkVendorNameFormat($vendorName, $extensionName);
            }

            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'][$controllerClassName] = [
                'className' => $controllerClassName,
                'alias' => $controllerAlias,
                'actions' => GeneralUtility::trimExplode(',', $actionsList),
            ];

            if (isset($nonCacheableControllerActions[$controllerClassName]) && !empty($nonCacheableControllerActions[$controllerClassName])) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'][$controllerClassName]['nonCacheableActions'] = GeneralUtility::trimExplode(
                    ',',
                    $nonCacheableControllerActions[$controllerClassName]
                );
            }
        }

        switch ($pluginType) {
            case self::PLUGIN_TYPE_PLUGIN:
                $pluginContent = trim('
tt_content.list.20.' . $pluginSignature . ' = USER
tt_content.list.20.' . $pluginSignature . ' {
	userFunc = TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run
	extensionName = ' . $extensionName . '
	pluginName = ' . $pluginName . '
}');
                break;
            case self::PLUGIN_TYPE_CONTENT_ELEMENT:
                $pluginContent = trim('
tt_content.' . $pluginSignature . ' =< lib.contentElement
tt_content.' . $pluginSignature . ' {
    templateName = Generic
    20 = USER
    20 {
        userFunc = TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run
        extensionName = ' . $extensionName . '
        pluginName = ' . $pluginName . '
    }
}');
                break;
            default:
                throw new \InvalidArgumentException('The pluginType "' . $pluginType . '" is not supported', 1289858856);
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['pluginType'] = $pluginType;
        ExtensionManagementUtility::addTypoScript($extensionName, 'setup', '
# Setting ' . $extensionName . ' plugin TypoScript
' . $pluginContent, 'defaultContentRendering');
    }

    /**
     * Register an Extbase PlugIn into backend's list of plugins
     * FOR USE IN Configuration/TCA/Overrides/tt_content.php
     *
     * @param string $extensionName The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
     * @param string $pluginName must be a unique id for your plugin in UpperCamelCase (the string length of the extension key added to the length of the plugin name should be less than 32!)
     * @param string $pluginTitle is a speaking title of the plugin that will be displayed in the drop down menu in the backend
     * @param string $pluginIcon is an icon identifier or file path prepended with "EXT:", that will be displayed in the drop down menu in the backend (optional)
     * @param string $group add this plugin to a plugin group, should be something like "news" or the like, "default" as regular
     * @throws \InvalidArgumentException
     */
    public static function registerPlugin($extensionName, $pluginName, $pluginTitle, $pluginIcon = null, $group = 'default')
    {
        self::checkPluginNameFormat($pluginName);
        self::checkExtensionNameFormat($extensionName);

        $delimiterPosition = strrpos($extensionName, '.');
        if ($delimiterPosition !== false) {
            $vendorName = str_replace('.', '\\', substr($extensionName, 0, $delimiterPosition));
            trigger_error(
                'Calling method ' . __METHOD__ . ' with argument $extensionName ("' . $extensionName . '") containing the vendor name ("' . $vendorName . '") is deprecated and will stop working in TYPO3 11.0.',
                E_USER_DEPRECATED
            );
            $extensionName = substr($extensionName, $delimiterPosition + 1);
        }
        $extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
        $pluginSignature = strtolower($extensionName) . '_' . strtolower($pluginName);

        // At this point $extensionName is normalized, no matter which format the method was fed with.
        // Calculate the original extensionKey from this again.
        $extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName);

        // pluginType is usually defined by configurePlugin() in the global array. Use this or fall back to default "list_type".
        $pluginType = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['pluginType'] ?? 'list_type';

        $itemArray = [$pluginTitle, $pluginSignature, $pluginIcon];
        if ($group) {
            $itemArray[3] = $group;
        }
        ExtensionManagementUtility::addPlugin(
            $itemArray,
            $pluginType,
            $extensionKey
        );
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
     */
    public static function registerModule($extensionName, $mainModuleName = '', $subModuleName = '', $position = '', array $controllerActions = [], array $moduleConfiguration = [])
    {
        self::checkExtensionNameFormat($extensionName);

        // Check if vendor name is prepended to extensionName in the format {vendorName}.{extensionName}
        if (false !== $delimiterPosition = strrpos($extensionName, '.')) {
            trigger_error(
                'Calling method ' . __METHOD__ . ' with argument $extensionName containing the vendor name is deprecated and will stop working in TYPO3 11.0.',
                E_USER_DEPRECATED
            );
            $vendorName = str_replace('.', '\\', substr($extensionName, 0, $delimiterPosition));
            $extensionName = substr($extensionName, $delimiterPosition + 1);

            if (!empty($vendorName)) {
                self::checkVendorNameFormat($vendorName, $extensionName);
            }
        }

        $extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
        $defaultModuleConfiguration = [
            'access' => 'admin',
            'icon' => 'EXT:extbase/Resources/Public/Icons/Extension.svg',
            'labels' => '',
        ];
        if ($mainModuleName !== '' && !array_key_exists($mainModuleName, $GLOBALS['TBE_MODULES'])) {
            $mainModuleName = $extensionName . GeneralUtility::underscoredToUpperCamelCase($mainModuleName);
        } else {
            $mainModuleName = $mainModuleName !== '' ? $mainModuleName : 'web';
        }
        // add mandatory parameter to use new pagetree
        if ($mainModuleName === 'web') {
            $defaultModuleConfiguration['navigationComponentId'] = 'TYPO3/CMS/Backend/PageTree/PageTreeElement';
        }
        ArrayUtility::mergeRecursiveWithOverrule($defaultModuleConfiguration, $moduleConfiguration);
        $moduleConfiguration = $defaultModuleConfiguration;
        $moduleSignature = $mainModuleName;
        if ($subModuleName !== '') {
            $subModuleName = $extensionName . GeneralUtility::underscoredToUpperCamelCase($subModuleName);
            $moduleSignature .= '_' . $subModuleName;
        }
        $moduleConfiguration['name'] = $moduleSignature;
        $moduleConfiguration['extensionName'] = $extensionName;
        $moduleConfiguration['routeTarget'] = Bootstrap::class . '::handleBackendRequest';
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$moduleSignature] ?? false)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$moduleSignature] = [];
        }
        foreach ($controllerActions as $controllerClassName => $actionsList) {
            $controllerAlias = self::resolveControllerAliasFromControllerClassName($controllerClassName);
            $vendorName = self::resolveVendorFromExtensionAndControllerClassName($extensionName, $controllerClassName);
            if (!empty($vendorName)) {
                self::checkVendorNameFormat($vendorName, $extensionName);
            }

            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$moduleSignature]['controllers'][$controllerClassName] = [
                'className' => $controllerClassName,
                'alias' => $controllerAlias,
                'actions' => GeneralUtility::trimExplode(',', $actionsList),
            ];
        }
        ExtensionManagementUtility::addModule($mainModuleName, $subModuleName, $position, null, $moduleConfiguration);
    }

    /**
     * Returns the object name of the controller defined by the extension name and
     * controller name
     *
     * @param string $vendor
     * @param string $extensionKey
     * @param string $subPackageKey
     * @param string $controllerAlias
     * @return string The controller's Object Name
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchControllerException if the controller does not exist
     */
    public static function getControllerClassName(
        string $vendor,
        string $extensionKey,
        string $subPackageKey,
        string $controllerAlias
    ): string {
        $objectName = str_replace(
            [
                '@extension',
                '@subpackage',
                '@controller',
                '@vendor',
                '\\\\',
            ],
            [
                $extensionKey,
                $subPackageKey,
                $controllerAlias,
                $vendor,
                '\\',
            ],
            '@vendor\@extension\@subpackage\Controller\@controllerController'
        );

        if ($objectName === false) {
            throw new NoSuchControllerException('The controller object "' . $objectName . '" does not exist.', 1220884009);
        }
        return trim($objectName, '\\');
    }

    /**
     * @param string $controllerClassName
     * @return string
     */
    public static function resolveControllerAliasFromControllerClassName(string $controllerClassName): string
    {
        // This method has been adjusted for TYPO3 10.3 to mitigate the issue that controller aliases
        // could not longer be calculated from controller classes when calling
        // \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin().
        //
        // The idea for version 11 is to let the user choose a controller alias and to check for its
        // uniqueness per plugin. That way, the core does no longer rely on the namespace of
        // controller classes to be in a specific format.
        //
        // todo: Change the way plugins are registered and enforce a controller alias to be set by
        //       the user to also free the core from guessing a simple alias by looking at the
        //       class name. This makes it possible to choose controller class names without a
        //       controller suffix.

        $strLen = strlen('Controller');

        if (!str_ends_with($controllerClassName, 'Controller')) {
            return '';
        }

        $controllerClassNameWithoutControllerSuffix = substr($controllerClassName, 0, -$strLen);

        if (strrpos($controllerClassNameWithoutControllerSuffix, 'Controller\\') === false) {
            $positionOfLastSlash = (int)strrpos($controllerClassNameWithoutControllerSuffix, '\\');
            $positionOfLastSlash += $positionOfLastSlash === 0 ? 0 : 1;

            return substr($controllerClassNameWithoutControllerSuffix, $positionOfLastSlash);
        }

        $positionOfControllerNamespacePart = (int)strrpos(
            $controllerClassNameWithoutControllerSuffix,
            'Controller\\'
        );

        return substr(
            $controllerClassNameWithoutControllerSuffix,
            $positionOfControllerNamespacePart + $strLen + 1
        );
    }

    /**
     * @param string $extensionName
     * @param string $controllerClassName
     * @return string
     */
    public static function resolveVendorFromExtensionAndControllerClassName(string $extensionName, string $controllerClassName): string
    {
        if (!str_contains($controllerClassName, '\\')) {
            // Does not work with non namespaced classes
            return '';
        }

        if (false === $extensionNamePosition = strpos($controllerClassName, $extensionName)) {
            // Does not work for classes that do not include the extension name as namespace part
            return '';
        }

        if (--$extensionNamePosition < 0) {
            return '';
        }

        return substr(
            $controllerClassName,
            0,
            $extensionNamePosition
        );
    }

    /**
     * Register a type converter by class name.
     *
     * @param string $typeConverterClassName
     */
    public static function registerTypeConverter($typeConverterClassName)
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters']) ||
            !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'])
        ) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'] = [];
        }
        if (!in_array($typeConverterClassName, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'][] = $typeConverterClassName;
        }
    }

    /**
     * Check a given vendor name for CGL compliance.
     * Log a deprecation message if it is not.
     *
     * @param string $vendorName The vendor name to check
     * @param string $extensionName The extension name that is affected
     */
    protected static function checkVendorNameFormat($vendorName, $extensionName)
    {
        if (preg_match('/^[A-Z]/', $vendorName) !== 1) {
            trigger_error('The vendor name from tx_' . $extensionName . ' must begin with a capital letter.', E_USER_DEPRECATED);
        }
    }

    /**
     * Check a given extension name for validity.
     *
     * @param string $extensionName The name of the extension
     * @throws \InvalidArgumentException
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
     */
    protected static function checkPluginNameFormat($pluginName)
    {
        if (empty($pluginName)) {
            throw new \InvalidArgumentException('The plugin name must not be empty', 1239891988);
        }
    }
}
