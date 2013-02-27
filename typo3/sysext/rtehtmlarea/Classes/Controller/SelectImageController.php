<?php
namespace TYPO3\CMS\Rtehtmlarea\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasper@typo3.com)
 *  (c) 2004-2013 Stanislas Rolland <typo3(arobas)jbr.ca>
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
 * Script class for the Element Browser window.
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class SelectImageController {

	public $mode = 'rte';

	public $button = 'image';

	protected $content = '';

	/**
	 * Main function, rendering the element browser in RTE mode.
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function main() {
		// Setting alternative browsing mounts (ONLY local to browse_links.php this script so they stay "read-only")
		$altMountPoints = trim($GLOBALS['BE_USER']->getTSConfigVal('options.folderTree.altElementBrowserMountPoints'));
		if ($altMountPoints) {
			$altMountPoints = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $altMountPoints);
			foreach ($altMountPoints as $filePathRelativeToFileadmindir) {
				$GLOBALS['BE_USER']->addFileMount('', $filePathRelativeToFileadmindir, $filePathRelativeToFileadmindir, 1, 'readonly');
			}
			$GLOBALS['BE_USER']->getFileStorages();
			$GLOBALS['FILEMOUNTS'] = $GLOBALS['BE_USER']->groupData['filemounts'];
		}
		// Rendering type by user function
		$browserRendered = FALSE;
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/TYPO3\\CMS\\Recordlist\\Browser\\ElementBrowser.php']['browserRendering'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/TYPO3\\CMS\\Recordlist\\Browser\\ElementBrowser.php']['browserRendering'] as $classRef) {
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
			$GLOBALS['SOBE']->browser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Rtehtmlarea\\SelectImage');
			$GLOBALS['SOBE']->browser->init();
			$modData = $GLOBALS['BE_USER']->getModuleData('select_image.php', 'ses');
			list($modData, $store) = $GLOBALS['SOBE']->browser->processSessionData($modData);
			$GLOBALS['BE_USER']->pushModuleData('select_image.php', $modData);
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


?>
