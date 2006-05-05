<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
*  (c) 2004-2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * Displays image selector for the RTE
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @author	Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 *
 * $Id$  *
 */

error_reporting (E_ALL ^ E_NOTICE);
unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');
require_once('class.tx_rtehtmlarea_select_image.php');
$LANG->includeLLFile('EXT:lang/locallang_browse_links.xml');
$LANG->includeLLFile('EXT:rtehtmlarea/mod4/locallang.xml');
$LANG->includeLLFile('EXT:rtehtmlarea/htmlarea/locallang_dialogs.xml');

/**
 * Script class for the Element Browser window.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class tx_rtehtmlarea_SC_select_image {
	var $mode;
	var $button = 'image';

	/**
	 * Main function, detecting the current mode of the element browser and branching out to internal methods.
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER, $SOBE;
		
				
		$this->mode = t3lib_div::_GP('mode');
		if (!$this->mode)	{
			$this->mode = 'rte';
		}
		
		$this->content = '';
		
			// render type by user func
		$browserRendered = false;
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering'] as $classRef) {
				$browserRenderObj = t3lib_div::getUserObj($classRef);
				if(is_object($browserRenderObj) && method_exists($browserRenderObj, 'isValid') && method_exists($browserRenderObj, 'render'))	{
					if ($browserRenderObj->isValid($this->mode, $this)) {
						$this->content .=  $browserRenderObj->render($this->mode, $this);
						$browserRendered = true;
						break;
					}
				}
			}
		}

			// if type was not rendered use default rendering functions
		if(!$browserRendered) {

			$SOBE->browser = t3lib_div::makeInstance('tx_rtehtmlarea_select_image');
			$SOBE->browser->init();
			
			$modData = $BE_USER->getModuleData('select_image.php','ses');
			list($modData, $store) = $SOBE->browser->processSessionData($modData);
			$BE_USER->pushModuleData('select_image.php',$modData);
			
			$this->content = $SOBE->browser->main_rte();
		}
	}
	
	/**
	 * Print module content
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod4/select_image.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod4/select_image.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_rtehtmlarea_SC_select_image');
$SOBE->main();
$SOBE->printContent();

?>