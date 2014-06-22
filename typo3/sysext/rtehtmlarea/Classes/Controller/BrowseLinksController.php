<?php
namespace TYPO3\CMS\Rtehtmlarea\Controller;

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
 * Script class for the Element Browser window.
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class BrowseLinksController {

	public $mode = 'rte';

	public $button = 'link';

	protected $content = '';

	/**
	 * Initialize language files
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile('EXT:rtehtmlarea/mod3/locallang.xlf');
		$GLOBALS['LANG']->includeLLFile('EXT:rtehtmlarea/htmlarea/locallang_dialogs.xlf');
	}
	/**
	 * Main function, rendering the element browser in RTE mode.
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function main() {
		// Setting alternative web browsing mounts (ONLY local to browse_links.php this script so they stay "read-only")
		$altMountPoints = trim($GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.altElementBrowserMountPoints'));
		// Clear temporary DB mounts
		$tmpMount = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('setTempDBmount');
		if (isset($tmpMount)) {
			$GLOBALS['BE_USER']->setAndSaveSessionData('pageTree_temporaryMountPoint', (int)$tmpMount);
		}
		// Set temporary DB mounts
		$tempDBmount = (int)$GLOBALS['BE_USER']->getSessionData('pageTree_temporaryMountPoint');
		if ($tempDBmount) {
			$altMountPoints = $tempDBmount;
		}
		if ($altMountPoints) {
			$GLOBALS['BE_USER']->groupData['webmounts'] = implode(',', array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $altMountPoints)));
			$GLOBALS['WEBMOUNTS'] = $GLOBALS['BE_USER']->returnWebmounts();
		}
		// Setting alternative file browsing mounts (ONLY local to browse_links.php this script so they stay "read-only")
		$altMountPoints = trim($GLOBALS['BE_USER']->getTSConfigVal('options.folderTree.altElementBrowserMountPoints'));
		if ($altMountPoints) {
			$altMountPoints = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $altMountPoints);
			foreach ($altMountPoints as $filePathRelativeToFileadmindir) {
				// @todo: add this feature for FAL and TYPO3 6.2
			}
		}
		// Render type by user function
		$browserRendered = FALSE;
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering'] as $classRef) {
				$browserRenderObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if (is_object($browserRenderObj) && method_exists($browserRenderObj, 'isValid') && method_exists($browserRenderObj, 'render')) {
					if ($browserRenderObj->isValid($this->mode, $this)) {
						$this->content .= $browserRenderObj->render($this->mode, $this);
						$browserRendered = TRUE;
						break;
					}
				}
			}
		}
		// If type was not rendered, use default rendering functions
		if (!$browserRendered) {
			$GLOBALS['SOBE']->browser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Rtehtmlarea\\BrowseLinks');
			$GLOBALS['SOBE']->browser->init();
			$modData = $GLOBALS['BE_USER']->getModuleData('browse_links.php', 'ses');
			list($modData, $store) = $GLOBALS['SOBE']->browser->processSessionData($modData);
			$GLOBALS['BE_USER']->pushModuleData('browse_links.php', $modData);
			$this->content = $GLOBALS['SOBE']->browser->main_rte();
		}
	}

	/**
	 * Print module content
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

}
