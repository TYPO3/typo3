<?php
namespace TYPO3\CMS\Backend\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Ingo Renner <ingo@typo3.org>
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
 * class to render the TYPO3 backend menu for the modules
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class ModuleMenuView {

	/**
	 * Module loading object
	 *
	 * @var \TYPO3\CMS\Backend\Module\ModuleLoader
	 */
	protected $moduleLoader;

	protected $backPath;

	protected $linkModules;

	protected $loadedModules;

	/**
	 * Constructor, initializes several variables
	 */
	public function __construct() {
		$this->backPath = '';
		$this->linkModules = TRUE;
		// Loads the backend modules available for the logged in user.
		$this->moduleLoader = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Module\\ModuleLoader');
		$this->moduleLoader->observeWorkspaces = TRUE;
		$this->moduleLoader->load($GLOBALS['TBE_MODULES']);
		$this->loadedModules = $this->moduleLoader->modules;
	}

	/**
	 * sets the path back to /typo3/
	 *
	 * @param string $backPath Path back to /typo3/
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	public function setBackPath($backPath) {
		if (!is_string($backPath)) {
			throw new \InvalidArgumentException('parameter $backPath must be of type string', 1193315266);
		}
		$this->backPath = $backPath;
	}

	/**
	 * loads the collapse states for the main modules from user's configuration (uc)
	 *
	 * @return array Collapse states
	 */
	protected function getCollapsedStates() {
		$collapsedStates = array();
		if ($GLOBALS['BE_USER']->uc['moduleData']['moduleMenu']) {
			$collapsedStates = $GLOBALS['BE_USER']->uc['moduleData']['moduleMenu'];
		}
		return $collapsedStates;
	}

	/**
	 * ModuleMenu Store loading data
	 *
	 * @param array $params
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj
	 * @return array
	 */
	public function getModuleData($params, $ajaxObj) {
		$data = array('success' => TRUE, 'root' => array());
		$rawModuleData = $this->getRawModuleData();
		$index = 0;
		foreach ($rawModuleData as $moduleKey => $moduleData) {
			$key = substr($moduleKey, 8);
			$num = count($data['root']);
			if ($moduleData['link'] != 'dummy.php' || $moduleData['link'] == 'dummy.php' && is_array($moduleData['subitems'])) {
				$data['root'][$num]['key'] = $key;
				$data['root'][$num]['menuState'] = $GLOBALS['BE_USER']->uc['moduleData']['menuState'][$moduleKey];
				$data['root'][$num]['label'] = $moduleData['title'];
				$data['root'][$num]['subitems'] = is_array($moduleData['subitems']) ? count($moduleData['subitems']) : 0;
				if ($moduleData['link'] && $this->linkModules) {
					$data['root'][$num]['link'] = 'top.goToModule(\'' . $moduleData['name'] . '\')';
				}
				// Traverse submodules
				if (is_array($moduleData['subitems'])) {
					foreach ($moduleData['subitems'] as $subKey => $subData) {
						$data['root'][$num]['sub'][] = array(
							'name' => $subData['name'],
							'description' => $subData['description'],
							'label' => $subData['title'],
							'icon' => $subData['icon']['filename'],
							'navframe' => $subData['parentNavigationFrameScript'],
							'link' => $subData['link'],
							'originalLink' => $subData['originalLink'],
							'index' => $index++,
							'navigationFrameScript' => $subData['navigationFrameScript'],
							'navigationFrameScriptParam' => $subData['navigationFrameScriptParam'],
							'navigationComponentId' => $subData['navigationComponentId']
						);
					}
				}
			}
		}
		if ($ajaxObj) {
			$ajaxObj->setContent($data);
			$ajaxObj->setContentFormat('jsonbody');
		} else {
			return $data;
		}
	}

	/**
	 * Returns the loaded modules
	 *
	 * @return array Array of loaded modules
	 */
	public function getLoadedModules() {
		return $this->loadedModules;
	}

	/**
	 * saves the menu's toggle state in the backend user's uc
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type TYPO3AJAX
	 * @return void
	 */
	public function saveMenuState($params, $ajaxObj) {
		$menuItem = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('menuid');
		$state = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('state') === 'true' ? 1 : 0;
		$GLOBALS['BE_USER']->uc['moduleData']['menuState'][$menuItem] = $state;
		$GLOBALS['BE_USER']->writeUC();
	}

	/**
	 * gets the raw module data
	 *
	 * @return array Multi dimension array with module data
	 */
	public function getRawModuleData() {
		$modules = array();
		// Remove the 'doc' module?
		if ($GLOBALS['BE_USER']->getTSConfigVal('options.disableDocModuleInAB')) {
			unset($this->loadedModules['doc']);
		}
		foreach ($this->loadedModules as $moduleName => $moduleData) {
			$moduleLink = '';
			if (!is_array($moduleData['sub'])) {
				$moduleLink = $moduleData['script'];
			}
			$moduleLink = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($moduleLink);
			$moduleKey = 'modmenu_' . $moduleName;
			$moduleIcon = $this->getModuleIcon($moduleKey);
			$modules[$moduleKey] = array(
				'name' => $moduleName,
				'title' => $GLOBALS['LANG']->moduleLabels['tabs'][$moduleName . '_tab'],
				'onclick' => 'top.goToModule(\'' . $moduleName . '\');',
				'icon' => $moduleIcon,
				'link' => $moduleLink,
				'description' => $GLOBALS['LANG']->moduleLabels['labels'][$moduleKey . 'label']
			);
			if (!is_array($moduleData['sub']) && $moduleData['script'] != 'dummy.php') {
				// Work around for modules with own main entry, but being self the only submodule
				$modules[$moduleKey]['subitems'][$moduleKey] = array(
					'name' => $moduleName,
					'title' => $GLOBALS['LANG']->moduleLabels['tabs'][$moduleName . '_tab'],
					'onclick' => 'top.goToModule(\'' . $moduleName . '\');',
					'icon' => $this->getModuleIcon($moduleName . '_tab'),
					'link' => $moduleLink,
					'originalLink' => $moduleLink,
					'description' => $GLOBALS['LANG']->moduleLabels['labels'][$moduleKey . 'label'],
					'navigationFrameScript' => NULL,
					'navigationFrameScriptParam' => NULL,
					'navigationComponentId' => NULL
				);
			} elseif (is_array($moduleData['sub'])) {
				foreach ($moduleData['sub'] as $submoduleName => $submoduleData) {
					$submoduleLink = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($submoduleData['script']);
					$submoduleKey = $moduleName . '_' . $submoduleName . '_tab';
					$submoduleIcon = $this->getModuleIcon($submoduleKey);
					$submoduleDescription = $GLOBALS['LANG']->moduleLabels['labels'][$submoduleKey . 'label'];
					$originalLink = $submoduleLink;
					$modules[$moduleKey]['subitems'][$submoduleKey] = array(
						'name' => $moduleName . '_' . $submoduleName,
						'title' => $GLOBALS['LANG']->moduleLabels['tabs'][$submoduleKey],
						'onclick' => 'top.goToModule(\'' . $moduleName . '_' . $submoduleName . '\');',
						'icon' => $submoduleIcon,
						'link' => $submoduleLink,
						'originalLink' => $originalLink,
						'description' => $submoduleDescription,
						'navigationFrameScript' => $submoduleData['navFrameScript'],
						'navigationFrameScriptParam' => $submoduleData['navFrameScriptParam'],
						'navigationComponentId' => $submoduleData['navigationComponentId']
					);
					if ($moduleData['navFrameScript']) {
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
	 * @param string $moduleKey Module key
	 * @return array Icon data array with 'filename', 'size', and 'html'
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
		$iconSizes = @getimagesize($iconFileAbsolute);
		$iconTitle = $GLOBALS['LANG']->moduleLabels['tabs'][$moduleKey];
		if (!empty($iconFileRelative)) {
			$icon['filename'] = $iconFileRelative;
			$icon['size'] = $iconSizes[3];
			$icon['title'] = htmlspecialchars($iconTitle);
			$icon['html'] = '<img src="' . $iconFileRelative . '" ' . $iconSizes[3] . ' title="' . htmlspecialchars($iconTitle) . '" alt="' . htmlspecialchars($iconTitle) . '" />';
		}
		return $icon;
	}

	/**
	 * Returns the filename readable for the script from PATH_typo3.
	 * That means absolute names are just returned while relative names are
	 * prepended with the path pointing back to typo3/ dir
	 *
	 * @param string $iconFilename Icon filename
	 * @return string Icon filename with absolute path
	 * @see getModuleIconRelative()
	 */
	protected function getModuleIconAbsolute($iconFilename) {
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($iconFilename)) {
			$iconFilename = $this->backPath . $iconFilename;
		}
		return $iconFilename;
	}

	/**
	 * Returns relative path to the icon filename for use in img-tags
	 *
	 * @param string $iconFilename Icon filename
	 * @return string Icon filename with relative path
	 * @see getModuleIconAbsolute()
	 */
	protected function getModuleIconRelative($iconFilename) {
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($iconFilename)) {
			$iconFilename = '../' . substr($iconFilename, strlen(PATH_site));
		}
		return $this->backPath . $iconFilename;
	}

	/**
	 * Appends a '?' if there is none in the string already
	 *
	 * @param string $link Link URL
	 * @return string Link URl appended with ? if there wasn't one
	 */
	protected function appendQuestionmarkToLink($link) {
		if (!strstr($link, '?')) {
			$link .= '?';
		}
		return $link;
	}

	/**
	 * renders the logout button form
	 *
	 * @return string Html code snippet displaying the logout button
	 */
	public function renderLogoutButton() {
		$buttonLabel = $GLOBALS['BE_USER']->user['ses_backuserid'] ? 'LLL:EXT:lang/locallang_core.xlf:buttons.exit' : 'LLL:EXT:lang/locallang_core.xlf:buttons.logout';
		$buttonForm = '
		<form action="logout.php" target="_top">
			<input type="submit" value="&nbsp;' . $GLOBALS['LANG']->sL($buttonLabel, 1) . '&nbsp;" />
		</form>';
		return $buttonForm;
	}

	/**
	 * turns linking of modules on or off
	 *
	 * @param boolean $linkModules Status for linking modules with a-tags, set to FALSE to turn lining off
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	public function setLinkModules($linkModules) {
		if (!is_bool($linkModules)) {
			throw new \InvalidArgumentException('parameter $linkModules must be of type bool', 1193326558);
		}
		$this->linkModules = $linkModules;
	}

}


?>