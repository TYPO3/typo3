<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Kay Strobach (typo3@kay-strobach.de)
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

class tx_indexed_search_pi_wizicon {

	/**
	 * Adds the indexed_search pi1 wizard icon
	 *
	 * @param	array	Input array with wizard items for plugins
	 * @return	array	Modified input array, having the item for indexed_search
	 * pi1 added.
	 */
	function proc($wizardItems) {

		$locallang = $this->includeLocalLang();

		$wizardItems['plugins_tx_indexed_search'] = array(
			'icon'        => t3lib_extMgm::extRelPath('indexed_search').'pi/ce_wiz.png',
			'title'       => $GLOBALS['LANG']->getLLL('pi_wizard_title',$locallang),
			'description' => $GLOBALS['LANG']->getLLL('pi_wizard_description',$locallang),
			'params'      => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=indexed_search'
		);

		return $wizardItems;
	}

	/**
	 * Includes the locallang file for the 'indexed_search' extension
	 *
	 * @return	array	The LOCAL_LANG array
	 */
	function includeLocalLang() {
		$locallangFile = t3lib_extMgm::extPath('indexed_search') . 'pi/locallang.xlf';
		$LOCAL_LANG = t3lib_div::readLLXMLfile($locallangFile, $GLOBALS['LANG']->lang);

		return $LOCAL_LANG;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/pi/class.tx_indexed_search_pi_wizicon.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/pi/class.tx_indexed_search_pi_wizicon.php']);
}

?>