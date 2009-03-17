<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Configuration source based on TS settings
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TX_EXTMVC_Configuration_Source_TS implements TX_EXTMVC_Configuration_SourceInterface {

	/**
	 * Loads the specified TypoScript configuration file and returns its content in a
	 * configuration container. If the file does not exist or could not be loaded,
	 * the empty configuration container is returned.
	 *
	 * @param string $extensionKey The extension key
	 * @return TX_EXTMVC_Configuration_Container
	 */
	 public function load($extensionKey) {
	 	// SK: same as with dispatcher. strtolower($extensionKey) is wrong; example: tt_news -> tx_ttnews
		$settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_' . strtolower($extensionKey) . '.'];
		$c = t3lib_div::makeInstance('TX_EXTMVC_Configuration_Container');
		if (is_array($settings)) {
			// SK: IMHO we do not need the instance of TX_EXTMVC_Configuration_Container here. Why not return the TypoScript directly?
			$c = t3lib_div::makeInstance('TX_EXTMVC_Configuration_Container');
			$c->mergeWithTS($settings);
		}
		return $c->getAsArray();
	}

}
?>