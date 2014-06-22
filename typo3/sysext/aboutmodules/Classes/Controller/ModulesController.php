<?php
namespace TYPO3\CMS\Aboutmodules\Controller;

/**
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
 * 'About modules' script - the default start-up module.
 * Will display the list of main- and sub-modules available to the user.
 * Each module will be show with description and a link to the module.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class ModulesController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * Show general information and the installed modules
	 *
	 * @return void
	 */
	public function indexAction() {
		$warnings = array();
		// Hook for additional warnings
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'] as $classRef) {
				$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (method_exists($hookObj, 'displayWarningMessages_postProcess')) {
					$hookObj->displayWarningMessages_postProcess($warnings);
				}
			}
		}
		if (count($warnings)) {
			if (count($warnings) > 1) {
				$securityWarnings = '<ul><li>' . implode('</li><li>', $warnings) . '</li></ul>';
			} else {
				$securityWarnings = '<p>' . implode('', $warnings) . '</p>';
			}
			$securityMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$securityWarnings,
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.header'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			);
			$contentWarnings = '<div style="margin: 20px 0px;">' . $securityMessage->render() . '</div>';
			unset($warnings);
		}

		$this->view->assignMultiple(
			array(
				'TYPO3Version' => TYPO3_version,
				'copyRightNotice' => \TYPO3\CMS\Backend\Utility\BackendUtility::TYPO3_copyRightNotice(),
				'warningMessages' => $contentWarnings,
				'modules' => $this->getModulesData()
			)
		);
	}

	/**
	 * Create array with data of all main modules (Web, File, ...)
	 * and its nested sub modules
	 *
	 * @return array
	 */
	protected function getModulesData() {
		/** @var $loadedModules \TYPO3\CMS\Backend\Module\ModuleLoader */
		$loadedModules = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Module\\ModuleLoader');
		$loadedModules->observeWorkspaces = TRUE;
		$loadedModules->load($GLOBALS['TBE_MODULES']);
		$mainModulesData = array();
		foreach ($loadedModules->modules as $moduleName => $moduleInfo) {
			$mainModuleData = array();
			$moduleKey = $moduleName . '_tab';
			$mainModuleData['name'] = $moduleName;
			$mainModuleData['label'] = $GLOBALS['LANG']->moduleLabels['tabs'][$moduleKey];
			if (is_array($moduleInfo['sub']) && count($moduleInfo['sub']) > 0) {
				$mainModuleData['subModules'] = $this->getSubModuleData($moduleName, $moduleInfo['sub']);
			}
			$mainModulesData[] = $mainModuleData;
		}
		return $mainModulesData;
	}

	/**
	 * Create array with data of all subModules of a specific main module
	 *
	 * @param string $moduleName Name of the main module
	 * @param array $subModulesInfo Sub module information
	 * @return array
	 */
	protected function getSubModuleData($moduleName, array $subModulesInfo = array()) {
		$subModulesData = array();
		foreach ($subModulesInfo as $subModuleName => $subModuleInfo) {
			$subModuleKey = $moduleName . '_' . $subModuleName . '_tab';
			$subModuleData = array();
			$subModuleData['name'] = $subModuleName;
			$subModuleData['icon'] = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($GLOBALS['LANG']->moduleLabels['tabs_images'][$subModuleKey]);
			$subModuleData['label'] = $GLOBALS['LANG']->moduleLabels['tabs'][$subModuleKey];
			$subModuleData['shortDescription'] = $GLOBALS['LANG']->moduleLabels['labels'][$subModuleKey . 'label'];
			$subModuleData['longDescription'] = $GLOBALS['LANG']->moduleLabels['labels'][$subModuleKey . 'descr'];
			$subModulesData[] = $subModuleData;
		}
		return $subModulesData;
	}

}
