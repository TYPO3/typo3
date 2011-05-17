<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Contains the update class to re-create the cache db tables with correct layout.
 *
 * @author Björn Pedersen <bjoern.pedersen@frm2.tum.de>
 */
class tx_coreupdates_cachetables extends Tx_Install_Updates_Base {

	protected $title = '(Re-)create cache tables';


	/**
	 * Checks if an update is needed
	 *
	 * @param	string		&$description: The description for the update
	 * @return	boolean		TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
		$description = 'Since TYPO3 4.6 the caching framework cache tables are auto-created. This function ensures they exist and have the correct format.';

		$result = TRUE;
		return $result;
	}

	/**
	 * Performs the database update.
	 *
	 * @param	array		&$dbQueries: queries done in this update
	 * @param	mixed		&$customMessages: custom messages
	 * @return	boolean		TRUE on success, FALSE on error
	 */
	public function performUpdate(&$dbQueries, &$customMessages) {
		$result = TRUE;
		$GLOBALS['typo3CacheManager']->flushCaches();
		return $result;
	}
}
?>