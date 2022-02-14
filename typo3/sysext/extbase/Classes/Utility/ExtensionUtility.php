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

use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utilities to manage plugins and  modules of an extension. Also useful to auto-generate the autoloader registry
 * file ext_autoload.php.
 */
class ExtensionUtility
{
    public const PLUGIN_TYPE_PLUGIN = 'list_type';
    public const PLUGIN_TYPE_CONTENT_ELEMENT = 'CType';

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

        $extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));

        $pluginSignature = strtolower($extensionName . '_' . $pluginName);

        $controllerActions = self::actionCommaListToArray($controllerActions);
        $nonCacheableControllerActions = self::actionCommaListToArray($nonCacheableControllerActions);
        self::registerControllerActions($extensionName, $pluginName, $controllerActions, $nonCacheableControllerActions);

        switch ($pluginType) {
            case self::PLUGIN_TYPE_PLUGIN:
                $pluginContent = trim('
tt_content.list.20.' . $pluginSignature . ' = EXTBASEPLUGIN
tt_content.list.20.' . $pluginSignature . ' {
	extensionName = ' . $extensionName . '
	pluginName = ' . $pluginName . '
}');
                break;
            case self::PLUGIN_TYPE_CONTENT_ELEMENT:
                $pluginContent = trim('
tt_content.' . $pluginSignature . ' =< lib.contentElement
tt_content.' . $pluginSignature . ' {
    templateName = Generic
    20 = EXTBASEPLUGIN
    20 {
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
     * @param array<string, string[]> $controllerActions
     * @param array<string, string[]> $nonCacheableControllerActions
     * @internal
     */
    public static function registerControllerActions(string $extensionName, string $pluginName, array $controllerActions, array $nonCacheableControllerActions): void
    {
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName] ?? false)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName] = [];
        }
        foreach ($controllerActions as $controllerClassName => $actionsList) {
            $controllerAlias = self::resolveControllerAliasFromControllerClassName($controllerClassName);

            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'][$controllerClassName] = [
                'className' => $controllerClassName,
                'alias' => $controllerAlias,
                'actions' => $actionsList,
            ];

            if (!empty($nonCacheableControllerActions[$controllerClassName])) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'][$controllerClassName]['nonCacheableActions']
                    = $nonCacheableControllerActions[$controllerClassName];
            }
        }
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
     * @param string $pluginDescription additional description
     * @throws \InvalidArgumentException
     */
    public static function registerPlugin($extensionName, $pluginName, $pluginTitle, $pluginIcon = null, $group = 'default', string $pluginDescription = ''): string
    {
        self::checkPluginNameFormat($pluginName);
        self::checkExtensionNameFormat($extensionName);

        $extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
        $pluginSignature = strtolower($extensionName) . '_' . strtolower($pluginName);

        // At this point $extensionName is normalized, no matter which format the method was fed with.
        // Calculate the original extensionKey from this again.
        $extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName);

        // pluginType is usually defined by configurePlugin() in the global array. Use this or fall back to default "list_type".
        $pluginType = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['pluginType'] ?? 'list_type';

        $selectItem = new SelectItem(
            'select',
            // set pluginName as default pluginTitle
            $pluginTitle ?: $pluginName,
            $pluginSignature,
            $pluginIcon,
            $group,
            $pluginDescription,
        );
        ExtensionManagementUtility::addPlugin(
            $selectItem,
            $pluginType,
            $extensionKey
        );
        return $pluginSignature;
    }

    /**
     * To allow extension authors to support multiple versions, this method is kept until
     * TYPO3 v13, but is no longer used nor evaluated from TYPO3 v12.0. To register modules,
     * place the configuration in your extensions' Configuration/Backend/Modules.php file.
     *
     * The method deliberately does not throw a deprecation warning in order to keep the noise
     * of deprecation warnings small.
     *
     * @deprecated The functionality has been removed in v12. The method will be removed in TYPO3 v13.
     */
    public static function registerModule($extensionName, $mainModuleName = '', $subModuleName = '', $position = '', array $controllerActions = [], array $moduleConfiguration = []) {}

    /**
     * @internal only used for TYPO3 Core
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
     * @param array<string, string|string[]> $controllerActions
     * @return array<string, string[]>
     */
    protected static function actionCommaListToArray(array $controllerActions): array
    {
        foreach ($controllerActions as $controllerClassName => $actionsList) {
            if (is_array($actionsList)) {
                continue;
            }
            $actionsListArray = GeneralUtility::trimExplode(',', (string)$actionsList);
            $controllerActions[$controllerClassName] = $actionsListArray;
        }
        return $controllerActions;
    }

    /**
     * Register a type converter by class name.
     *
     * @param string $typeConverterClassName
     * @deprecated will be removed in TYPO3 v13.0. Register type converters via Services.yaml in your extension(s).
     */
    public static function registerTypeConverter($typeConverterClassName)
    {
        trigger_error(
            'Method ' . __METHOD__ . ' does no longer has any effect and will completely be removed with TYPO3 v13. Register type converters via Services.yaml instead.',
            E_USER_DEPRECATED
        );
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
