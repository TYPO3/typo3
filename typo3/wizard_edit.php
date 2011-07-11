<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Wizard to edit records from group/select lists in TCEforms
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */



$BACK_PATH='';
require ('init.php');
require ('template.php');
$LANG->includeLLFile('EXT:lang/locallang_wizards.xml');


/**
 * Script Class for redirecting a backend user to the editing form when an "Edit wizard" link was clicked in TCEforms somewhere
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_wizard_edit {

		// Internal, static: GPvars
	var $P;						// Wizard parameters, coming from TCEforms linking to the wizard.
	var $doClose;				// Boolean; if set, the window will be closed by JavaScript




	/**
	 * Initialization of the script
	 *
	 * @return	void
	 */
	function init()	{
		$this->P = t3lib_div::_GP('P');
		$this->doClose = t3lib_div::_GP('doClose');		// Used for the return URL to alt_doc.php so that we can close the window.
	}

	/**
	 * Main function
	 * Makes a header-location redirect to an edit form IF POSSIBLE from the passed data - otherwise the window will just close.
	 *
	 * @return	void
	 */
	function main()	{
		if ($this->doClose)	{
			$this->closeWindow();
		} else {

				// Initialize:
			$table = $this->P['table'];
			$field = $this->P['field'];
			t3lib_div::loadTCA($table);
			$config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
			$fTable = $this->P['currentValue']<0 ? $config['neg_foreign_table'] : $config['foreign_table'];

				// Detecting the various allowed field type setups and acting accordingly.
			if (is_array($config) && $config['type']=='select' && !$config['MM'] && $config['maxitems']<=1 && t3lib_utility_Math::canBeInterpretedAsInteger($this->P['currentValue']) && $this->P['currentValue'] && $fTable)	{	// SINGLE value:
				$redirectUrl = 'alt_doc.php?returnUrl=' . rawurlencode('wizard_edit.php?doClose=1') . '&edit[' . $fTable . '][' . $this->P['currentValue'] . ']=edit';
				t3lib_utility_Http::redirect($redirectUrl);
			} elseif (is_array($config) && $this->P['currentSelectedValues'] && (($config['type']=='select' && $config['foreign_table']) || ($config['type']=='group' && $config['internal_type']=='db')))	{	// MULTIPLE VALUES:

					// Init settings:
				$allowedTables = $config['type']=='group' ? $config['allowed'] : $config['foreign_table'].','.$config['neg_foreign_table'];
				$prependName=1;
				$params='';

					// Selecting selected values into an array:
				$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
				$dbAnalysis->start($this->P['currentSelectedValues'],$allowedTables);
				$value = $dbAnalysis->getValueArray($prependName);

					// Traverse that array and make parameters for alt_doc.php:
				foreach($value as $rec)	{
					$recTableUidParts = t3lib_div::revExplode('_',$rec,2);
					$params.='&edit['.$recTableUidParts[0].']['.$recTableUidParts[1].']=edit';
				}

					// Redirect to alt_doc.php:
				t3lib_utility_Http::redirect('alt_doc.php?returnUrl=' . rawurlencode('wizard_edit.php?doClose=1') . $params);
			} else {
				$this->closeWindow();
			}
		}
	}

	/**
	 * Printing a little JavaScript to close the open window.
	 *
	 * @return	void
	 */
	function closeWindow()	{
		echo '<script language="javascript" type="text/javascript">close();</script>';
		exit;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/wizard_edit.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/wizard_edit.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_wizard_edit');
$SOBE->init();
$SOBE->main();

?>