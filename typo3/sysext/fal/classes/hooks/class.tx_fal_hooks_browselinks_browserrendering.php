<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * Hook into SC_browse_links::main
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id:  $
 */

class tx_fal_hooks_browseLinks_browserRendering {

	/**
	 * DESCRIPTION
	 *
	 * @param	string	$mode
	 * @param	object/SC_browse_links $parentObject the parent object
	 * @return	boolean
	 */
	public function isValid($mode, SC_browse_links $parentObject) {
		$result = FALSE;

		switch($mode) {
			case 'sys_files':
				$result = TRUE;
				break;
		}

		return $result;
	}

	/**
	 * DESCRIPTION
	 *
	 * @param	string	$mode
	 * @param	object/SC_browse_links $parentObject the parent object
	 * @return	string	a string which describes very diplomatically which element browser modes are missing ;-)
	 */
	public function render($mode, SC_browse_links $parentObject) {
		$content = 'Give me the fuckin element browser for mode ' . $mode .
			'<br />Have a look at ' . __FILE__ . ' to implement me ;(';

		return $content;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tx_fal_hooks_browselinks_browserrendering.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tx_fal_hooks_browselinks_browserrendering.php']);
}
?>