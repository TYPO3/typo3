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
 * File Abtraction Layer TCEForms wizard
 *
 * @todo Andy Grunwald, 01.12.2010, matching the class nam econvention? new name tx_fal_TCEForms_Wizard ?
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
class tx_fal_tceforms_wizard {

	/**
	 * DESCRIPTION
	 *
	 * @todo I will make this A LOT cooler than now
	 *
	 * @param	[to be defined]		$params		DESCRIPTION
	 * @param	[to be defined]		$pObj		DESCRIPTION
	 * @return	[to be defined]					DESCRIPTION
	 */
	public function tx_fal_fieldwizard($params, $pObj) {
		$content .= t3lib_BEfunc::thumbCode($params['row'], $params['table'], $params['field']);

		return $content;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/tceforms_wizard.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/tceforms_wizard.php']);
}
?>