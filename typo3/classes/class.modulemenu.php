<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Ingo Renner <ingo@typo3.org>
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


if(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
	$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xml');
}

/**
 * class to render the TYPO3 backend menu for the modules
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage core
 */
class ModuleMenu {

	/**
	 * module loading object
	 *
	 * @var t3lib_loadModules
	 */
	protected $moduleLoader;

	protected $backPath;
	protected $linkModules;
	protected $loadedModules;
	protected $fsMod; //TODO find a more descriptive name, left over from alt_menu_functions

	/**
	 * constructor, initializes several variables
	 *
	 * @return	void
	 */
	public function __construct() {

		$this->backPath    = '';
		$this->fsMod       = array();
		$this->linkModules = true;

			// Loads the backend modules available for the logged in user.
		$this->moduleLoader = t3lib_div::makeInstance('t3lib_loadModules');
		$this->moduleLoader->observeWorkspaces = true;
		$this->moduleLoader->load($GLOBALS['TBE_MODULES']);
		$this->loadedModules = $this->moduleLoader->modules;

	}

	/**
	 * sets the path back to /typo3/
	 *
	 * @param	string	path back to /typo3/
	 * @return	void
	 */
	public function setBackPath($backPath) {
		if(!is_string($backPath)) {
			throw new InvalidArgumentException('parameter $backPath must be of type string', 1193315266);
		}

		$this->backPath = $backPath;
	}

	/**
	 * loads the collapse states for the main modules from user's configuration (uc)
	 *
	 * @return	array		collapse states
	 */
	protected function getCollapsedStates() {

		$collapsedStates = array();
		if($GLOBALS['BE_USER']->uc['moduleData']['moduleMenu']) {
			$collapsedStates = $GLOBALS['BE_USER']->uc['moduleData']['moduleMenu'];
		}

		return $collapsedStates;
	}

	/**
	 * returns the loaded modules
	 *
	 * @return	array	array of loaded modules
	 */
	public function getLoadedModules() {
		return $this->loadedModules;
	}

	/**
	 * saves the menu's toggle state in the backend user's uc
	 *
	 * @param	array		array of parameters from the AJAX interface, currently unused
	 * @param	TYPO3AJAX	object of type TYPO3AJAX
	 * @return	void
	 */
	public function saveMenuState($params, $ajaxObj) {
		$menuItem = t3lib_div::_POST('menuid');
		$state    = t3lib_div::_POST('state') === 'true' ? 1 : 0;

		$GLOBALS['BE_USER']->uc['moduleData']['menuState'][$menuItem] = $state;
		$GLOBALS['BE_USER']->writeUC();
	}

	/**
	 * renders the backend menu as unordered list
	 *
	 * @param	boolean		optional parameter used to switch wrapping the menu in ul tags off for AJAX calls
	 * @return	string		menu html code to use in the backend
	 */
	public function render($wrapInUl = true) {
		$menu    = '';
		$onBlur  = $GLOBALS['CLIENT']['FORMSTYLE'] ? 'this.blur();' : '';

		$tsConfiguration = $GLOBALS['BE_USER']->getTSConfig('options.moduleMenuCollapsable');
		$collapsable = (isset($tsConfiguration['value']) && $tsConfiguration['value'] == 0) ? 0 : 1;

		$rawModuleData = $this->getRawModuleData();

		foreach($rawModuleData as $moduleKey => $moduleData) {
			if($moduleData['link'] != 'dummy.php' || ($moduleData['link'] == 'dummy.php' && is_array($moduleData['subitems'])) ) {
				$menuState   = $GLOBALS['BE_USER']->uc['moduleData']['menuState'][$moduleKey];
				$moduleLabel = $moduleData['title'];

				if($moduleData['link'] && $this->linkModules) {
					$moduleLabel = '<a href="#" onclick="top.goToModule(\'' . $moduleData['name'] . '\');'.$onBlur . 'return false;">' . $moduleLabel . '</a>';
				}

				$menu .= '<li id="modmenu_' . $moduleData['name'] . '" '.
					($collapsable ? 'class="menuSection"' : '') .
					' title="' . $moduleData['description'] . '">
					<div class="' . ($menuState ? 'collapsed' : 'expanded') . '">' .
					$moduleData['icon']['html'] . ' ' . $moduleLabel . '</div>';

					// traverse submodules
				if (is_array($moduleData['subitems'])) {
					$menu .= $this->renderSubModules($moduleData['subitems'], $menuState);
				}

				$menu .= '</li>' . LF;
			}
		}

		return ($wrapInUl ? '<ul id="typo3-menu">' . LF.$menu.'</ul>' . LF : $menu);
	}

	/**
	 * renders the backend menu as unordered list as an AJAX response without
	 * the wrapping ul tags
	 *
	 * @param	array		array of parameters from the AJAX interface, currently unused
	 * @param	TYPO3AJAX	object of type TYPO3AJAX
	 * @return	void
	 */
	public function renderAjax($params = array(), TYPO3AJAX &$ajaxObj = null) {
		$menu       = $this->render(false);
		$menuSwitch = $this->getGotoModuleJavascript();

			// JS rocks: we can just overwrite a function with a new definition.
			// and yes, we actually do that =)
		$menuSwitchUpdate = '
		<script type="text/javascript">
			top.goToModule = '.$menuSwitch.';
		</script>';

		$ajaxObj->addContent('typo3-menu', $menu.$menuSwitchUpdate);
	}

	/**
	 * renders submodules
	 *
	 * @param	array		array of (sub)module data
	 * @param	boolean		collapse state of menu item, defaults to false
	 * @return	string		(sub)module html code
	 */
	public function renderSubModules($modules, $menuState=false) {
		$moduleMenu = '';
		$onBlur     = $GLOBALS['CLIENT']['FORMSTYLE'] ? 'this.blur();' : '';

		foreach($modules as $moduleKey => $moduleData) {
				// Setting additional JavaScript
			$additionalJavascript = '';
			if($moduleData['parentNavigationFrameScript']) {
				$parentModuleName     = substr($moduleData['name'], 0, strpos($moduleData['name'], '_'));
				$additionalJavascript = "+'&id='+top.rawurlencodeAndRemoveSiteUrl(top.fsMod.recentIds['" . $parentModuleName . "'])";
			}

			if($moduleData['link'] && $this->linkModules) {

				$onClickString = htmlspecialchars('top.goToModule(\''.$moduleData['name'].'\');'.$onBlur.'return false;');
				$submoduleLink = '<a href="#" onclick="'.$onClickString.'" title="'.$moduleData['description'].'">'
						//TODO make icon a background image using css
					.'<span class="submodule-icon">'.$moduleData['icon']['html'].'</span>'
					.'<span>'.htmlspecialchars($moduleData['title']).'</span>'
					.'</a>';
			}

			$moduleMenu .= '<li id="modmenu_' . $moduleData['name'] . '">' . $submoduleLink . '</li>' . LF;
		}

		return '<ul'.($menuState ? ' style="display:none;"' : '').'>'.LF.$moduleMenu.'</ul>'.LF;
	}

	/**
	 * gets the raw module data
	 *
	 * @return	array		multi dimension array with module data
	 */
	public function getRawModuleData() {
		$modules = array();

			// Remove the 'doc' module?
		if($GLOBALS['BE_USER']->getTSConfigVal('options.disableDocModuleInAB'))	{
			unset($this->loadedModules['doc']);
		}

		foreach($this->loadedModules as $moduleName => $moduleData) {
			$moduleNavigationFramePrefix = $this->getNavigationFramePrefix($moduleData);

			if($moduleNavigationFramePrefix) {
				$this->fsMod[$moduleName] = 'fsMod.recentIds["'.$moduleName.'"]="";';
			}

			$moduleLink = '';
			if(!is_array($moduleData['sub'])) {
				$moduleLink = $moduleData['script'];
			}
			$moduleLink = t3lib_div::resolveBackPath($moduleLink);

			$moduleKey   = 'modmenu_' . $moduleName;
			$moduleIcon  = $this->getModuleIcon($moduleKey);

			if($moduleLink && $moduleNavigationFramePrefix) {
				$moduleLink = $moduleNavigationFramePrefix.rawurlencode($moduleLink);
			}

			$modules[$moduleKey] = array(
				'name'        => $moduleName,
				'title'       => $GLOBALS['LANG']->moduleLabels['tabs'][$moduleName . '_tab'],
				'onclick'     => 'top.goToModule(\''.$moduleName.'\');',
				'icon'        => $moduleIcon,
				'link'        => $moduleLink,
				'prefix'      => $moduleNavigationFramePrefix,
				'description' => $GLOBALS['LANG']->moduleLabels['labels'][$moduleKey.'label']
			);

			if(is_array($moduleData['sub'])) {

				foreach($moduleData['sub'] as $submoduleName => $submoduleData) {
					$submoduleLink = t3lib_div::resolveBackPath($submoduleData['script']);
					$submoduleNavigationFramePrefix = $this->getNavigationFramePrefix($moduleData, $submoduleData);

					$submoduleKey         = $moduleName.'_'.$submoduleName.'_tab';
					$submoduleIcon        = $this->getModuleIcon($submoduleKey);
					$submoduleDescription = $GLOBALS['LANG']->moduleLabels['labels'][$submoduleKey.'label'];

					$originalLink = $submoduleLink;
					if($submoduleLink && $submoduleNavigationFramePrefix) {
						$submoduleLink = $submoduleNavigationFramePrefix.rawurlencode($submoduleLink);
					}

					$modules[$moduleKey]['subitems'][$submoduleKey] = array(
						'name'         => $moduleName.'_'.$submoduleName,
						'title'        => $GLOBALS['LANG']->moduleLabels['tabs'][$submoduleKey],
						'onclick'      => 'top.goToModule(\''.$moduleName.'_'.$submoduleName.'\');',
						'icon'         => $submoduleIcon,
						'link'         => $submoduleLink,
						'originalLink' => $originalLink,
						'prefix'       => $submoduleNavigationFramePrefix,
						'description'  => $submoduleDescription,
						'navigationFrameScript' => $submoduleData['navFrameScript'],
						'navigationFrameScriptParam' => $submoduleData['navFrameScriptParam']
					);

					if($moduleData['navFrameScript']) {
						$modules[$moduleKey]['subitems'][$submoduleKey]['parentNavigationFrameScript'] = $moduleData['navFrameScript'];
					}
				}
			}
		}

		return $modules;
	}

	/**
	 * gets the module icon and its size
	 *
	 * @param	string		module key
	 * @return	array		icon data array with 'filename', 'size', and 'html'
	 */
	protected function getModuleIcon($moduleKey) {
		$icon = array(
			'filename' => '',
			'size' => '',
			'title' => '',
			'html' => ''
		);

		$iconFileRelative = $this->getModuleIconRelative($GLOBALS['LANG']->moduleLabels['tabs_images'][$moduleKey]);
		$iconFileAbsolute = $this->getModuleIconAbsolute($GLOBALS['LANG']->moduleLabels['tabs_images'][$moduleKey]);
		$iconSizes        = @getimagesize($iconFileAbsolute);
		$iconTitle        = $GLOBALS['LANG']->moduleLabels['tabs'][$moduleKey];

		if(!empty($iconFileRelative)) {
			$icon['filename'] = $iconFileRelative;
			$icon['size']     = $iconSizes[3];
			$icon['title']    = htmlspecialchars($iconTitle);
			$icon['html']     = '<img src="'.$iconFileRelative.'" '.$iconSizes[3].' title="'.htmlspecialchars($iconTitle).'" alt="'.htmlspecialchars($iconTitle).'" />';
		}

		return $icon;
	}

	/**
	 * Returns the filename readable for the script from PATH_typo3.
	 * That means absolute names are just returned while relative names are
	 * prepended with the path pointing back to typo3/ dir
	 *
	 * @param	string		icon filename
	 * @return	string		icon filename with absolute path
	 * @see getModuleIconRelative()
	 */
	protected function getModuleIconAbsolute($iconFilename) {

		if(!t3lib_div::isAbsPath($iconFilename))	{
			$iconFilename = $this->backPath . $iconFilename;
		}

		return $iconFilename;
	}

	/**
	 * Returns relative path to the icon filename for use in img-tags
	 *
	 * @param	string		icon filename
	 * @return	string		icon filename with relative path
	 * @see getModuleIconAbsolute()
	 */
	protected function getModuleIconRelative($iconFilename) {
		if (t3lib_div::isAbsPath($iconFilename)) {
			$iconFilename = '../' . substr($iconFilename, strlen(PATH_site));
		}
		return $this->backPath.$iconFilename;
	}

	/**
	 * Returns a prefix used to call the navigation frame with parameters which then will call the scripts defined in the modules info array.
	 *
	 * @param	array		module data array
	 * @param	array		submodule data array
	 * @return	string		result URL string
	 */
	protected function getNavigationFramePrefix($moduleData, $subModuleData = array()) {
		$prefix = '';

		$navigationFrameScript = $moduleData['navFrameScript'];
		if($subModuleData['navFrameScript']) {
			$navigationFrameScript = $subModuleData['navFrameScript'];
		}

		$navigationFrameParameter = $moduleData['navFrameScriptParam'];
		if($subModuleData['navFrameScriptParam']) {
			$navigationFrameParameter = $subModuleData['navFrameScriptParam'];
		}

		if($navigationFrameScript) {
			$navigationFrameScript = t3lib_div::resolveBackPath($navigationFrameScript);
			$navigationFrameScript = $this->appendQuestionmarkToLink($navigationFrameScript);

			if($GLOBALS['BE_USER']->uc['condensedMode']) {
				$prefix = $navigationFrameScript.$navigationFrameParameter.'&currentSubScript=';
			} else {
				$prefix = 'alt_mod_frameset.php?'
						 .'fW="+top.TS.navFrameWidth+"'
						 .'&nav="+top.TS.PATH_typo3+"'
						 .rawurlencode($navigationFrameScript.$navigationFrameParameter)
						 .'&script=';
			}
		}

		return $prefix;
	}

	/**
	 * generates javascript code to switch between modules
	 *
	 * @return	string		javascript code snippet to switch modules
	 */
	public function getGotoModuleJavascript() {

		$moduleJavascriptCommands = array();
		$rawModuleData            = $this->getRawModuleData();
		$navFrameScripts          = array();

		foreach($rawModuleData as $mainModuleKey => $mainModuleData) {
			if ($mainModuleData['subitems']) {
				foreach ($mainModuleData['subitems'] as $subModuleKey => $subModuleData) {

					$parentModuleName  = substr($subModuleData['name'], 0, strpos($subModuleData['name'], '_'));
					$javascriptCommand = '';

						// Setting additional JavaScript if frameset script:
					$additionalJavascript = '';
					if($subModuleData['parentNavigationFrameScript']) {
						$additionalJavascript = "+'&id='+top.rawurlencodeAndRemoveSiteUrl(top.fsMod.recentIds['" . $parentModuleName . "'])";
					}

					if ($subModuleData['link'] && $this->linkModules) {
							// For condensed mode, send &cMR parameter to frameset script.
						if ($additionalJavascript && $GLOBALS['BE_USER']->uc['condensedMode']) {
							$additionalJavascript .= "+(cMR ? '&cMR=1' : '')";
						}

						$javascriptCommand = '
				modScriptURL = "'.$this->appendQuestionmarkToLink($subModuleData['link']).'"'.$additionalJavascript.';';

						if ($subModuleData['navFrameScript']) {
							$javascriptCommand .= '
				top.currentSubScript="'.$subModuleData['originalLink'].'";';
						}

						if (!$GLOBALS['BE_USER']->uc['condensedMode'] && $subModuleData['parentNavigationFrameScript']) {
							$additionalJavascript = "+'&id='+top.rawurlencodeAndRemoveSiteUrl(top.fsMod.recentIds['" . $parentModuleName . "'])";

							$submoduleNavigationFrameScript = $subModuleData['navigationFrameScript'] ? $subModuleData['navigationFrameScript'] : $subModuleData['parentNavigationFrameScript'];
							$submoduleNavigationFrameScript = t3lib_div::resolveBackPath($submoduleNavigationFrameScript);

								// Add navigation script parameters if module requires them
							if ($subModuleData['navigationFrameScriptParam']) {
								$submoduleNavigationFrameScript = $this->appendQuestionmarkToLink($submoduleNavigationFrameScript) . $subModuleData['navigationFrameScriptParam'];
							}

							$navFrameScripts[$parentModuleName] = $submoduleNavigationFrameScript;

							$javascriptCommand = '
				top.currentSubScript = "'.$subModuleData['originalLink'].'";
				if (top.content.list_frame && top.fsMod.currentMainLoaded == mainModName) {
					modScriptURL = "'.$this->appendQuestionmarkToLink($subModuleData['originalLink']).'"'.$additionalJavascript.';
					';
								// Change link to navigation frame if submodule has it's own navigation
							if ($submoduleNavigationFrameScript) {
								$javascriptCommand .= 'navFrames["' . $parentModuleName . '"] = "'. $submoduleNavigationFrameScript . '";';
							}
							$javascriptCommand .= '
				} else if (top.nextLoadModuleUrl) {
					modScriptURL = "'.($subModuleData['prefix'] ? $this->appendQuestionmarkToLink($subModuleData['link']) . '&exScript=' : '') . 'listframe_loader.php";
				} else {
					modScriptURL = "'.$this->appendQuestionmarkToLink($subModuleData['link']).'"'.$additionalJavascript.' + additionalGetVariables;
				}';
						}
					}
				$moduleJavascriptCommands[] = "
			case '".$subModuleData['name']."':".$javascriptCommand."
			break;";
				}
			} elseif(!$mainModuleData['subitems'] && !empty($mainModuleData['link'])) {
					// main module has no sub modules but instead is linked itself (doc module f.e.)
				$javascriptCommand = '
				modScriptURL = "'.$this->appendQuestionmarkToLink($mainModuleData['link']).'";';
				$moduleJavascriptCommands[] = "
			case '".$mainModuleData['name']."':".$javascriptCommand."
			break;";
			}
		}

		$javascriptCode = 'function(modName, cMR_flag, addGetVars) {
		var useCondensedMode = '.($GLOBALS['BE_USER']->uc['condensedMode'] ? 'true' : 'false').';
		var mainModName = (modName.slice(0, modName.indexOf("_")) || modName);

		var additionalGetVariables = "";
		if (addGetVars)	{
			additionalGetVariables = addGetVars;
		}';

		$javascriptCode .= '
		var navFrames = {};';
		foreach ($navFrameScripts as $mainMod => $frameScript) {
			$javascriptCode .= '
				navFrames["'.$mainMod.'"] = "'.$frameScript.'";';
		}

		$javascriptCode .= '

		var cMR = (cMR_flag ? 1 : 0);
		var modScriptURL = "";

		switch(modName)	{'
			.LF.implode(LF, $moduleJavascriptCommands).LF.'
		}
		';

		$javascriptCode .= '

		if (!useCondensedMode && navFrames[mainModName]) {
			if (top.content.list_frame && top.fsMod.currentMainLoaded == mainModName) {
				top.content.list_frame.location = top.getModuleUrl(top.TS.PATH_typo3 + modScriptURL + additionalGetVariables);
				if (top.currentSubNavScript != navFrames[mainModName]) {
					top.currentSubNavScript = navFrames[mainModName];
					top.content.nav_frame.location = top.getModuleUrl(top.TS.PATH_typo3 + navFrames[mainModName]);
				}
			} else {
				TYPO3.Backend.loadModule(mainModName, modName, modScriptURL + additionalGetVariables);
			}
		} else if (modScriptURL) {
			TYPO3.Backend.loadModule(mainModName, modName, top.getModuleUrl(modScriptURL + additionalGetVariables));
		}
		currentModuleLoaded = modName;
		top.fsMod.currentMainLoaded = mainModName;
		TYPO3ModuleMenu.highlightModule("modmenu_" + modName, (modName == mainModName ? 1 : 0));
	}';

		return $javascriptCode;
	}

	/**
	 * Appends a '?' if there is none in the string already
	 *
	 * @param	string		Link URL
	 * @return	string		link URl appended with ? if there wasn't one
	 */
	protected function appendQuestionmarkToLink($link)	{
		if(!strstr($link, '?')) {
			$link .= '?';
		}

		return $link;
	}

	/**
	 * renders the logout button form
	 *
	 * @return	string		html code snippet displaying the logout button
	 */
	public function renderLogoutButton()	{
		$buttonLabel      = $GLOBALS['BE_USER']->user['ses_backuserid'] ? 'LLL:EXT:lang/locallang_core.php:buttons.exit' : 'LLL:EXT:lang/locallang_core.php:buttons.logout';

		$buttonForm = '
		<form action="logout.php" target="_top">
			<input type="submit" value="&nbsp;' . $GLOBALS['LANG']->sL($buttonLabel, 1) . '&nbsp;" />
		</form>';

		return $buttonForm;
	}

	/**
	 * turns linking of modules on or off
	 *
	 * @param	boolean		status for linking modules with a-tags, set to false to turn lining off
	 */
	public function setLinkModules($linkModules) {
		if(!is_bool($linkModules)) {
			throw new InvalidArgumentException('parameter $linkModules must be of type bool', 1193326558);
		}

		$this->linkModules = $linkModules;
	}

	/**
	 * gets the frameset (leftover) helper
	 *
	 * @return	array	array of javascript snippets
	 */
	public function getFsMod() {
		return $this->fsMod;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.modulemenu.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.modulemenu.php']);
}

?>
