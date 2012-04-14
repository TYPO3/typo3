<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog <susanne.moog@typo3.org>
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
 * Extension Manager Cache Utility
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @package Extension Manager
 * @subpackage Utility
 */
class Tx_Extensionmanager_Utility_Cache implements t3lib_Singleton {

	/**
	 * Refreshes the global extension list
	 *
	 * @return void
	 */
	public function refreshGlobalExtList() {
		$GLOBALS['TYPO3_LOADED_EXT'] = t3lib_extMgm::typo3_loadExtensions();
		if ($GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE']) {
			require(PATH_typo3conf . $GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'] . '_ext_localconf.php');
		} else {
			$tempTypo3LoadedExt = $GLOBALS['TYPO3_LOADED_EXT'];
			foreach ($tempTypo3LoadedExt as $_EXTKEY => $tempTypo3LoadedExtensionData) {
				if (is_array($tempTypo3LoadedExtensionData) && $tempTypo3LoadedExtensionData['ext_localconf.php']) {
						// Make sure $TYPO3_CONF_VARS is also available within the included files
					global $TYPO3_CONF_VARS;
					$_EXTCONF = $TYPO3_CONF_VARS['EXT']['extConf'][$_EXTKEY];
					require($tempTypo3LoadedExtensionData['ext_localconf.php']);
				}
			}
		}
	}
}

?>
