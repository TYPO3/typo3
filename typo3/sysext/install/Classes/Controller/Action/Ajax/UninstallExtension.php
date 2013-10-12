<?php
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Susanne Moog <typo3@susanne-moog.de>
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

use TYPO3\CMS\Core\Utility;

/**
 * Uninstall Extensions
 *
 * Used for uninstalling an extension (or multiple) via an ajax request.
 * Warning! No dependency checking is done here, the extension is just removed
 * from the extension list.
 *
 * If you use this class you have to take care of clearing the cache afterwards,
 * it's not done here because for fully clearing the cache you need a reload
 * to take care of changed cache configurations due to no longer installed extensions.
 * Use the clearCache ajax action afterwards.
 */
class UninstallExtension extends AbstractAjaxAction {

	/**
	 * Uninstall one or multiple extensions
	 * Extension keys are read from get vars, more than one extension has to be comma separated
	 * Cache is cleared "hard" after uninstalling
	 *
	 * @return string "OK"
	 */
	protected function executeAction() {
		$getVars = Utility\GeneralUtility::_GET('install');
		if (isset($getVars['uninstallExtension']) && isset($getVars['uninstallExtension']['extensions'])) {
			$extensionsToUninstall = Utility\GeneralUtility::trimExplode(',', $getVars['uninstallExtension']['extensions']);
			foreach ($extensionsToUninstall as $extension) {
				if (Utility\ExtensionManagementUtility::isLoaded($extension)) {
					Utility\ExtensionManagementUtility::unloadExtension($extension);
				}
			}
		}
		return 'OK';
	}

}
