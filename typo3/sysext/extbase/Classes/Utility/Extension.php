<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 *
 * @package Extbase
 * @subpackage Utility
 * @version $ID:$
 */
class Tx_Extbase_Utility_Extension {

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
	 * @param string $controllerActions is an array of allowed combinations of controller and action stored in an array (controller name as key and a comma separated list of action names as value, the first controller and its first action is chosen as default)
	 * @param string $nonCachableControllerActions is an optional array of controller name and  action names which should not be cached (array as defined in $controllerActions)
	 * @param string $defaultControllerAction is an optional array controller name (as array key) and action name (as array value) that should be called as default
	 * @return void
	 */
	public static function configurePlugin($extensionName, $pluginName, array $controllerActions, array $nonCachableControllerActions = array()) {
		if (empty($pluginName)) {
			throw new InvalidArgumentException('The plugin name must not be empty', 1239891987);
		}
		if (empty($extensionName)) {
			throw new InvalidArgumentException('The extension name was invalid (must not be empty and must match /[A-Za-z][_A-Za-z0-9]/)', 1239891989);
		}
		$extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
		$pluginSignature = strtolower($extensionName) . '_' . strtolower($pluginName);

		$controllerCounter = 1;
		$hasMultipleActionsCounter = 0;
		$controllers = '';
		foreach ($controllerActions as $controller => $actionsList) {
			$controllers .= '
		' . $controllerCounter . '.controller = ' . $controller . '
		' . $controllerCounter . '.actions = ' . $actionsList;
			$controllerCounter++;
			if (strpos($actionsList, ',') !== FALSE) {
				$hasMultipleActionsCounter++;
			}
		}

		$switchableControllerActions = '';
		if ($controllerCounter > 1 || $hasMultipleActionsCounter > 0) {
				$switchableControllerActions = '
	switchableControllerActions {' . $controllers . '
	}';
		}

		reset($controllerActions);
		$defaultController = key($controllerActions);
		$controller = '
	controller = ' . $defaultController;
		$defaultAction = array_shift(t3lib_div::trimExplode(',', current($controllerActions)));
		$action = '
	action = ' . $defaultAction;

		$nonCachableActions = array();
		if (!empty($nonCachableControllerActions[$defaultController])) {
			$nonCachableActions = t3lib_div::trimExplode(',', $nonCachableControllerActions[$defaultController]);
		}
		$cachableActions = array_diff(t3lib_div::trimExplode(',', $controllerActions[$defaultController]), $nonCachableActions);

		$contentObjectType = in_array($defaultAction, $nonCachableActions) ? 'USER_INT' : 'USER';

		$conditions = '';
		foreach ($controllerActions as $controllerName => $actionsList) {
			if (!empty($nonCachableControllerActions[$controllerName])) {
				$nonCachableActions = t3lib_div::trimExplode(',', $nonCachableControllerActions[$controllerName]);
				$cachableActions = array_diff(t3lib_div::trimExplode(',', $controllerActions[$controllerName]), $nonCachableActions);
				if (($contentObjectType == 'USER' && count($nonCachableActions) > 0)
					|| ($contentObjectType == 'USER_INT' && count($cachableActions) > 0)) {

					$conditions .= '
[globalString = GP:tx_' . $pluginSignature . '|controller = ' . $controllerName . '] && [globalString = GP:tx_' . $pluginSignature . '|action = /' . implode('|', $contentObjectType === 'USER' ? $nonCachableActions : $cachableActions) . '/]
tt_content.list.20.' . $pluginSignature . ' = ' . ($contentObjectType === 'USER' ? 'USER_INT' : 'USER') . '
[global]
';
				}
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
	}
}';
		t3lib_extMgm::addTypoScript($extensionName, 'setup', '
# Setting ' . $extensionName . ' plugin TypoScript
' . $pluginTemplate);

		$pluginContent = trim('
tt_content.list.20.' . $pluginSignature . ' = ' . $contentObjectType . '
tt_content.list.20.' . $pluginSignature . ' {
	userFunc = tx_extbase_dispatcher->dispatch
	pluginName = ' . $pluginName . '
	extensionName = ' . $extensionName . '
	' . $controller .
	$action .
	$switchableControllerActions . '

	settings =< plugin.tx_' . strtolower($extensionName) . '.settings
	persistence =< plugin.tx_' . strtolower($extensionName) . '.persistence
	view =< plugin.tx_' . strtolower($extensionName) . '.view
	_LOCAL_LANG =< plugin.tx_' . strtolower($extensionName) . '._LOCAL_LANG
}
' . $conditions);

		t3lib_extMgm::addTypoScript($extensionName, 'setup', '
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
	 * @return void
	 */
	public static function registerPlugin($extensionName, $pluginName, $pluginTitle) {
		if (empty($pluginName)) {
			throw new InvalidArgumentException('The plugin name must not be empty', 1239891987);
		}
		if (empty($extensionName)) {
			throw new InvalidArgumentException('The extension name was invalid (must not be empty and must match /[A-Za-z][_A-Za-z0-9]/)', 1239891989);
		}
		$extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
		$pluginSignature = strtolower($extensionName) . '_' . strtolower($pluginName);

		t3lib_extMgm::addPlugin(array($pluginTitle, $pluginSignature), 'list_type');
	}

	/**
	 * This method is called from t3lib_loadModules::checkMod and it replaces old conf.php.
	 *
	 * @param string $key The module name
	 * @param string $fullpath	Absolute path to module
	 * @param array $MCONF Reference to the array holding the configuration of the module
	 * @param array $MLANG Reference to the array holding the localized module labels
	 * @return array Configuration of the module
	 */
	public function configureModule($key, $fullpath, array $MCONF = array(), array $MLANG = array()) {
		$path = preg_replace('/\/[^\/.]+\/\.\.\//', '/', $fullpath); // because 'path/../path' does not work
		$config = $GLOBALS['TBE_MODULES']['_configuration'][$key]['config'];
		define('TYPO3_MOD_PATH', $config['extRelPath']);

			// Fill $MCONF
		$MCONF['name'] = $key;
		$MCONF['access'] = $config['access'];
		$MCONF['script'] = '_DISPATCH';

		if (substr($config['icon'], 0, 4) === 'EXT:') {
			list($extKey, $local) = explode('/', substr($config['icon'], 4), 2);
			$config['icon'] = t3lib_extMgm::extRelPath($extKey) . $local;
		}

			// Initialize search for alternative icon:
		$altIconKey = 'MOD:' . $key . '/' . $config['icon'];		// Alternative icon key (might have an alternative set in $TBE_STYLES['skinImg']
		$altIconAbsPath = is_array($GLOBALS['TBE_STYLES']['skinImg'][$altIconKey]) ? t3lib_div::resolveBackPath(PATH_typo3.$GLOBALS['TBE_STYLES']['skinImg'][$altIconKey][0]) : '';

			// Set icon, either default or alternative:
		if ($altIconAbsPath && @is_file($altIconAbsPath)) {
			$tabImage = $altIconAbsPath;
		} else {
				// Setting default icon:
			$tabImage = $config['icon'];
		}

			// Fill $MLANG
		$MLANG['default']['ll_ref'] = $config['labels'];

			// Finally, set the icon with correct path:
		if (substr($tabImage, 0 ,3) === '../') {
			$MLANG['default']['tabs_images']['tab'] = PATH_site . substr($tabImage, 3);
		} else {
			$MLANG['default']['tabs_images']['tab'] = PATH_typo3 . $tabImage;
		}

			// If LOCAL_LANG references are used for labels of the module:
		if ($MLANG['default']['ll_ref']) {
				// Now the 'default' key is loaded with the CURRENT language - not the english translation...
			$MLANG['default']['labels']['tablabel'] = $GLOBALS['LANG']->sL($MLANG['default']['ll_ref'] . ':mlang_labels_tablabel');
			$MLANG['default']['labels']['tabdescr'] = $GLOBALS['LANG']->sL($MLANG['default']['ll_ref'] . ':mlang_labels_tabdescr');
			$MLANG['default']['tabs']['tab'] = $GLOBALS['LANG']->sL($MLANG['default']['ll_ref'] . ':mlang_tabs_tab');
			$GLOBALS['LANG']->addModuleLabels($MLANG['default'], $key . '_');
		} else {	// ... otherwise use the old way:
			$GLOBALS['LANG']->addModuleLabels($MLANG['default'], $key . '_');
			$GLOBALS['LANG']->addModuleLabels($MLANG[$GLOBALS['LANG']->lang], $key . '_');
		}

			// Fill $modconf
		$modconf['script'] = 'mod.php?M=' . rawurlencode($key);
		$modconf['name'] = $key;

				// Default tab setting
		if ($MCONF['defaultMod']) {
			$modconf['defaultMod'] = $MCONF['defaultMod'];
		}
			// Navigation Frame Script (GET params could be added)
		if ($MCONF['navFrameScript']) {
			$navFrameScript = explode('?', $MCONF['navFrameScript']);
			$navFrameScript = $navFrameScript[0];
			if (file_exists($path . '/' . $navFrameScript)) {
				$modconf['navFrameScript'] = $this->getRelativePath(PATH_typo3, $fullpath . '/' . $MCONF['navFrameScript']);
			}
		}

			// Additional params for Navigation Frame Script: "&anyParam=value&moreParam=1"
		if ($MCONF['navFrameScriptParam']) {
			$modconf['navFrameScriptParam'] = $MCONF['navFrameScriptParam'];
		}

		return $modconf;
	}

	/**
	 * Registers an Extbase module (main or sub) to the backend interface.
	 * FOR USE IN ext_tables.php FILES
	 *
	 * @param string $extensionName The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
	 * @param string $main The main module key, $sub is the submodule key. So $main would be an index in the $TBE_MODULES array and $sub could be an element in the lists there. If $main is not set a blank $extensionName module is created
	 * @param string $sub The submodule key. If $sub is not set a blank $main module is created
	 * @param string $position This can be used to set the position of the $sub module within the list of existing submodules for the main module. $position has this syntax: [cmd]:[submodule-key]. cmd can be "after", "before" or "top" (or blank which is default). If "after"/"before" then submodule will be inserted after/before the existing submodule with [submodule-key] if found. If not found, the bottom of list. If "top" the module is inserted in the top of the submodule list.
	 * @param array $controllerActions is an array of allowed combinations of controller and action stored in an array (controller name as key and a comma separated list of action names as value, the first controller and its first action is chosen as default)
	 * @param array $config The configuration options of the module (icon, locallang.xml file)
	 * @return void
	 */
	public static function registerModule($extensionName, $main = '', $sub = '', $position = '', array $controllerActions, $config = array()) {
		if (empty($extensionName)) {
			throw new InvalidArgumentException('The extension name was invalid (must not be empty and must match /[A-Za-z][_A-Za-z0-9]/)', 1239891989);
		}
		$extensionKey = $extensionName; // FIXME This will break if the $extensionName is given as BlogExample
		$extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));

		$path = t3lib_extMgm::extPath($extensionKey, 'Classes/');
		$relPath = t3lib_extMgm::extRelPath($extensionKey) . 'Classes/';

		if (!is_array($config) || count($config) == 0) {
			$config['access'] = 'admin';
			$config['icon'] = '';
			$config['labels'] = '';
			$config['extRelPath'] = $relPath;
		}

		if ((strlen($main) > 0) && !array_key_exists($main, $GLOBALS['TBE_MODULES'])) {
			$main = $extensionName . self::convertLowerUnderscoreToUpperCamelCase($main);
		} else {
			$main = (strlen($main) > 0) ? $main : 'web'; // TODO By now, $main must default to 'web'
		}

		if ((strlen($sub) > 0)) {
			$sub = $extensionName . self::convertLowerUnderscoreToUpperCamelCase($sub);
			$key = $main . '_' . $sub;
		} else {
			$key = $main;
		}

		$moduleConfig = array(
			'name' => $key,
			'extensionKey' => $extensionKey,
			'extensionName' => $extensionName,
			'controllerActions' => $controllerActions,
			'config' => $config,
		);
		$GLOBALS['TBE_MODULES']['_configuration'][$key] = $moduleConfig;
		$GLOBALS['TBE_MODULES']['_configuration'][$key]['configureModuleFunction'] = array('Tx_Extbase_Utility_Extension', 'configureModule');

		t3lib_extMgm::addModule($main, $sub, $position);
	}

	// TODO PHPdoc
	public static function convertCamelCaseToLowerCaseUnderscored($string) {
		// FIXME The cache doesn't work IMO as it is static (did I really implemented this? ;-))
		static $conversionMap = array();
		if (!isset($conversionMap[$string])) {
			$conversionMap[$string] = strtolower(preg_replace('/(?<=\w)([A-Z])/', '_\\1', $string));
		}
		return $conversionMap[$string];
	}

	public static function convertUnderscoredToLowerCamelCase($string) {
		$string = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($string))));
		$string[0] = strtolower($string[0]);
		return $string;
	}

	public static function convertLowerUnderscoreToUpperCamelCase($camelCasedString) {
		return t3lib_div::underscoredToUpperCamelCase($camelCasedString);
	}

		/**
	 * Build the autoload registry for a given extension and place it ext_autoload.php.
	 *
	 * @param	string	$extensionKey	Key of the extension
	 * @param	string	$extensionPath	full path of the extension
	 * @return	string	HTML string which should be outputted
	 */
	public function createAutoloadRegistryForExtension($extensionKey, $extensionPath) {
		$classNameToFileMapping = array();
		$extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionKey)));
		$errors = $this->buildAutoloadRegistryForSinglePath($classNameToFileMapping, $extensionPath . 'Classes/', '.*tslib.*', '$extensionClassesPath . \'|\'');
		if ($errors) {
			return $errors;
		}
		$globalPrefix = '$extensionClassesPath = t3lib_extMgm::extPath(\'' . $extensionKey . '\') . \'Classes/\';';

		$errors = array();
		foreach ($classNameToFileMapping as $className => $fileName) {
			if (!(strpos($className, 'tx_' . strtolower($extensionName)) === 0)) {
				$errors[] = $className . ' does not start with Tx_' . $extensionName . ' and was not added to the autoloader registry.';
				unset($classNameToFileMapping[$className]);
			}
		}
		$autoloadFileString = $this->generateAutoloadPHPFileData($classNameToFileMapping, $globalPrefix);
		if (!@file_put_contents($extensionPath . 'ext_autoload.php', $autoloadFileString)) {
			$errors[] = '<b>' . $extensionPath . 'ext_autoload.php could not be written!</b>';
		}
		$errors[] = 'Wrote the following data: <pre>' . htmlspecialchars($autoloadFileString) . '</pre>';
		return implode('<br />', $errors);
	}

	/**
	 * Generate autoload PHP file data. Takes an associative array with class name to file mapping, and outputs it as PHP.
	 * Does NOT escape the values in the associative array. Includes the <?php ... ?> syntax and an optional global prefix.
	 *
	 * @param	array	$classNameToFileMapping class name to file mapping
	 * @param	string	$globalPrefix	Global prefix which is prepended to all code.
	 * @return	string	The full PHP string
	 */
	protected function generateAutoloadPHPFileData($classNameToFileMapping, $globalPrefix = '') {
		$output = '<?php' . PHP_EOL;
		$output .= '// DO NOT CHANGE THIS FILE! It is automatically generated by Tx_Extbase_Utility_Extension::createAutoloadRegistryForExtension.' . PHP_EOL;
		$output .= '// This file was generated on ' . date('Y-m-d H:i') . PHP_EOL;
		$output .= PHP_EOL;
		$output .= $globalPrefix . PHP_EOL;
		$output .= 'return array(' . PHP_EOL;
		foreach ($classNameToFileMapping as $className => $quotedFileName) {
			$output .= '	\'' . $className . '\' => ' . $quotedFileName . ',' . PHP_EOL;
		}
		$output .= ');' . PHP_EOL;
		$output .= '?>';
		return $output;
	}

	/**
	 * Generate the $classNameToFileMapping for a given filePath.
	 *
	 * @param	array	$classNameToFileMapping	(Reference to array) All values are appended to this array.
	 * @param	string	$path	Path which should be crawled
	 * @param	string	$excludeRegularExpression	Exclude regular expression, to exclude certain files from being processed
	 * @param	string	$valueWrap	Wrap for the file name
	 * @return void
	 */
	protected function buildAutoloadRegistryForSinglePath(&$classNameToFileMapping, $path, $excludeRegularExpression = '', $valueWrap = '\'|\'') {
//		if (file_exists($path . 'Classes/')) {
//			return "<b>This appears to be a new-style extension which has its PHP classes inside the Classes/ subdirectory. It is not needed to generate the autoload registry for these extensions.</b>";
//		}
		$extensionFileNames = t3lib_div::removePrefixPathFromList(t3lib_div::getAllFilesAndFoldersInPath(array(), $path, 'php', FALSE, 99, $excludeRegularExpression), $path);

		foreach ($extensionFileNames as $extensionFileName) {
			$classNamesInFile = $this->extractClassNames($path . $extensionFileName);
			if (!count($classNamesInFile)) continue;
			foreach ($classNamesInFile as $className) {
				$classNameToFileMapping[strtolower($className)] = str_replace('|', $extensionFileName, $valueWrap);
			}
		}
	}

	/**
	 * Extracts class names from the given file.
	 *
	 * @param	string	$filePath	File path (absolute)
	 * @return	array	Class names
	 */
	protected function extractClassNames($filePath) {
		$fileContent = php_strip_whitespace($filePath);
		$classNames = array();
		if (function_exists('token_get_all')) {
			$tokens = token_get_all($fileContent);
			while(1) {
				// look for "class" or "interface"
				$token = $this->findToken($tokens, array(T_ABSTRACT, T_CLASS, T_INTERFACE));
				// fetch "class" token if "abstract" was found
				if ($token === 'abstract') {
					$token = $this->findToken($tokens, array(T_CLASS));
				}
				if ($token === false) {
					// end of file
					break;
				}
				// look for the name (a string) skipping only whitespace and comments
				$token = $this->findToken($tokens, array(T_STRING), array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT));
				if ($token === false) {
					// unexpected end of file or token: remove found names because of parse error
					t3lib_div::sysLog('Parse error in "' . $filePath. '".', 'Core', 2);
					$classNames = array();
					break;
				}
				$token = t3lib_div::strtolower($token);
				// exclude XLASS classes
				if (strncmp($token, 'ux_', 3)) {
					$classNames[] = $token;
				}
			}
		} else {
			// TODO: parse PHP - skip coments and strings, apply regexp only on the remaining PHP code
			$matches = array();
			preg_match_all('/^[ \t]*(?:(?:abstract|final)?[ \t]*(?:class|interface))[ \t\n\r]+([a-zA-Z][a-zA-Z_0-9]*)/mS', $fileContent, $matches);
			$classNames = array_map('t3lib_div::strtolower', $matches[1]);
		}
		return $classNames;
	}

	/**
	 * Find tokens in the tokenList
	 *
	 * @param	array	$tokenList	list of tokens as returned by token_get_all()
	 * @param	array	$wantedToken	the tokens to be found
	 * @param	array	$intermediateTokens	optional: list of tokens that are allowed to skip when looking for the wanted token
	 * @return	mixed
	 */
	protected function findToken(array &$tokenList, array $wantedTokens, array $intermediateTokens = array()) {
		$skipAllTokens = count($intermediateTokens) ? false : true;

		$returnValue = false;
		// Iterate with while since we need the current array position:
		foreach ($tokenList as $token) {
			// parse token (see http://www.php.net/manual/en/function.token-get-all.php for format of token list)
			if (is_array($token)) {
				list($id, $text) = $token;
			} else {
				$id = $text = $token;
			}
			if (in_array($id, $wantedTokens)) {
				$returnValue = $text;
				break;
			}
			// look for another token
			if ($skipAllTokens || in_array($id, $intermediateTokens)) {
				continue;
			}
			break;
		}
		return $returnValue;
	}

}
?>
