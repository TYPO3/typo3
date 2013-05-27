<?php
namespace TYPO3\CMS\Extbase\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Utilities to manage plugins and  modules of an extension. Also useful to auto-generate the autoloader registry
 * file ext_autoload.php.
 */
class ExtensionUtility {

	const PLUGIN_TYPE_PLUGIN = 'list_type';
	const PLUGIN_TYPE_CONTENT_ELEMENT = 'CType';

	/**
	 * Add auto-generated TypoScript to configure the Extbase Dispatcher.
	 *
	 * When adding a frontend plugin you will have to add both an entry to the TCA definition
	 * of tt_content table AND to the TypoScript template which must initiate the rendering.
	 * Since the static template with uid 43 is the "content.default" and practically always
	 * used for rendering the content elements it's very useful to have this function automatically
	 * adding the necessary TypoScript for calling the appropriate controller and action of your plugin.
	 * It will also work for the extension "css_styled_content"
	 * FOR USE IN ext_localconf.php FILES
	 * Usage: 2
	 *
	 * @param string $extensionName The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
	 * @param string $pluginName must be a unique id for your plugin in UpperCamelCase (the string length of the extension key added to the length of the plugin name should be less than 32!)
	 * @param array $controllerActions is an array of allowed combinations of controller and action stored in an array (controller name as key and a comma separated list of action names as value, the first controller and its first action is chosen as default)
	 * @param array $nonCacheableControllerActions is an optional array of controller name and  action names which should not be cached (array as defined in $controllerActions)
	 * @param string $pluginType either \TYPO3\CMS\Extbase\Utility\ExtensionUtility::TYPE_PLUGIN (default) or \TYPO3\CMS\Extbase\Utility\ExtensionUtility::TYPE_CONTENT_ELEMENT
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	static public function configurePlugin($extensionName, $pluginName, array $controllerActions, array $nonCacheableControllerActions = array(), $pluginType = self::PLUGIN_TYPE_PLUGIN) {
		if (empty($pluginName)) {
			throw new \InvalidArgumentException('The plugin name must not be empty', 1239891987);
		}
		if (empty($extensionName)) {
			throw new \InvalidArgumentException('The extension name was invalid (must not be empty and must match /[A-Za-z][_A-Za-z0-9]/)', 1239891989);
		}
		// Check if vendor name is prepended to extensionName in the format {vendorName}.{extensionName}
		$vendorName = NULL;
		$delimiterPosition = strrpos($extensionName, '.');
		if ($delimiterPosition !== FALSE) {
			$vendorName = str_replace('.', '\\', substr($extensionName, 0, $delimiterPosition));
			$extensionName = substr($extensionName, $delimiterPosition + 1);
		}
		$extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
		$pluginSignature = strtolower($extensionName) . '_' . strtolower($pluginName);
		if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName])) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName] = array();
		}
		foreach ($controllerActions as $controllerName => $actionsList) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'][$controllerName] = array('actions' => \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $actionsList));
			if (!empty($nonCacheableControllerActions[$controllerName])) {
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'][$controllerName]['nonCacheableActions'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $nonCacheableControllerActions[$controllerName]);
			}
		}
		$pluginTemplate = 'plugin.tx_' . strtolower($extensionName) . ' {
	settings {
	}
	persistence {
		storagePid =
		classes {
		}
	}
	view {
		templateRootPath =
		layoutRootPath =
		partialRootPath =
		 # with defaultPid you can specify the default page uid of this plugin. If you set this to the string "auto" the target page will be determined automatically. Defaults to an empty string that expects the target page to be the current page.
		defaultPid =
	}
}';
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($extensionName, 'setup', '
# Setting ' . $extensionName . ' plugin TypoScript
' . $pluginTemplate);
		switch ($pluginType) {
			case self::PLUGIN_TYPE_PLUGIN:
				$pluginContent = trim('
tt_content.list.20.' . $pluginSignature . ' = USER
tt_content.list.20.' . $pluginSignature . ' {
	userFunc = TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run
	extensionName = ' . $extensionName . '
	pluginName = ' . $pluginName . (NULL !== $vendorName ? ("\n\t" . 'vendorName = ' . $vendorName) : '') . '
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
		pluginName = ' . $pluginName . (NULL !== $vendorName ? ("\n\t\t" . 'vendorName = ' . $vendorName) : '') . '
	}
}');
				break;
			default:
				throw new \InvalidArgumentException('The pluginType "' . $pluginType . '" is not suported', 1289858856);
		}
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['pluginType'] = $pluginType;
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($extensionName, 'setup', '
# Setting ' . $extensionName . ' plugin TypoScript
' . $pluginContent, 43);
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
	static public function registerPlugin($extensionName, $pluginName, $pluginTitle, $pluginIconPathAndFilename = NULL) {
		if (empty($pluginName)) {
			throw new \InvalidArgumentException('The plugin name must not be empty', 1239891987);
		}
		if (empty($extensionName)) {
			throw new \InvalidArgumentException('The extension name was invalid (must not be empty and must match /[A-Za-z][_A-Za-z0-9]/)', 1239891989);
		}
		$delimiterPosition = strrpos($extensionName, '.');
		if ($delimiterPosition !== FALSE) {
			$extensionName = substr($extensionName, $delimiterPosition + 1);
		}
		$extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
		$pluginSignature = strtolower($extensionName) . '_' . strtolower($pluginName);
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array($pluginTitle, $pluginSignature, $pluginIconPathAndFilename), $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['pluginType']);
	}

	/**
	 * This method is called from \TYPO3\CMS\Backend\Module\ModuleLoader::checkMod
	 * and it replaces old conf.php.
	 *
	 * @param string $moduleSignature The module name
	 * @param string $modulePath Absolute path to module (not used by Extbase currently)
	 * @return array Configuration of the module
	 */
	static public function configureModule($moduleSignature, $modulePath) {
		$moduleConfiguration = $GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature];
		$iconPathAndFilename = $moduleConfiguration['icon'];
		if (substr($iconPathAndFilename, 0, 4) === 'EXT:') {
			list($extensionKey, $relativePath) = explode('/', substr($iconPathAndFilename, 4), 2);
			$iconPathAndFilename = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . $relativePath;
		}
		// TODO: skin support
		$moduleLabels = array(
			'tabs_images' => array(
				'tab' => $iconPathAndFilename
			),
			'labels' => array(
				'tablabel' => $GLOBALS['LANG']->sL($moduleConfiguration['labels'] . ':mlang_labels_tablabel'),
				'tabdescr' => $GLOBALS['LANG']->sL($moduleConfiguration['labels'] . ':mlang_labels_tabdescr')
			),
			'tabs' => array(
				'tab' => $GLOBALS['LANG']->sL($moduleConfiguration['labels'] . ':mlang_tabs_tab')
			)
		);
		$GLOBALS['LANG']->addModuleLabels($moduleLabels, $moduleSignature . '_');
		return $moduleConfiguration;
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
	 * @param array $moduleConfiguration The configuration options of the module (icon, locallang.xml file)
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	static public function registerModule($extensionName, $mainModuleName = '', $subModuleName = '', $position = '', array $controllerActions, array $moduleConfiguration = array()) {
		if (empty($extensionName)) {
			throw new \InvalidArgumentException('The extension name must not be empty', 1239891989);
		}
		// Check if vendor name is prepended to extensionName in the format {vendorName}.{extensionName}
		$vendorName = NULL;
		if (FALSE !== $delimiterPosition = strrpos($extensionName, '.')) {
			$vendorName = str_replace('.', '\\', substr($extensionName, 0, $delimiterPosition));
			$extensionName = substr($extensionName, $delimiterPosition + 1);
		}
		$extensionKey = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName);
		$extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
		$defaultModuleConfiguration = array(
			'access' => 'admin',
			'icon' => 'EXT:extbase/ext_icon.gif',
			'labels' => '',
			'extRelPath' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($extensionKey) . 'Classes/'
		);
		if (strlen($mainModuleName) > 0 && !array_key_exists($mainModuleName, $GLOBALS['TBE_MODULES'])) {
			$mainModuleName = $extensionName . \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($mainModuleName);
		} else {
			$mainModuleName = strlen($mainModuleName) > 0 ? $mainModuleName : 'web';
		}
		// add mandatory parameter to use new pagetree
		if ($mainModuleName === 'web') {
			$defaultModuleConfiguration['navigationComponentId'] = 'typo3-pagetree';
		}
		$moduleConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($defaultModuleConfiguration, $moduleConfiguration);
		$moduleSignature = $mainModuleName;
		if (strlen($subModuleName) > 0) {
			$subModuleName = $extensionName . \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($subModuleName);
			$moduleSignature .= '_' . $subModuleName;
		}
		$moduleConfiguration['name'] = $moduleSignature;
		$moduleConfiguration['script'] = 'mod.php?M=' . rawurlencode($moduleSignature);
		if (NULL !== $vendorName) {
			$moduleConfiguration['vendorName'] = $vendorName;
		}
		$moduleConfiguration['extensionName'] = $extensionName;
		$moduleConfiguration['configureModuleFunction'] = array('TYPO3\\CMS\\Extbase\\Utility\\ExtensionUtility', 'configureModule');
		$GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature] = $moduleConfiguration;
		if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$moduleSignature])) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$moduleSignature] = array();
		}
		foreach ($controllerActions as $controllerName => $actions) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$moduleSignature]['controllers'][$controllerName] = array(
				'actions' => \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $actions)
			);
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
	static public function registerTypeConverter($typeConverterClassName) {
		if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'])) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'] = array();
		}
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'][] = $typeConverterClassName;
	}
}

?>