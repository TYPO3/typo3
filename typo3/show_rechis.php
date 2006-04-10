<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Shows information about a database or file item
 *
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skaarhoj
 * XHTML Compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   76: class SC_show_rechis
 *   87:     function init()
 *  105:     function main()
 *  131:     function printContent()
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


$BACK_PATH='';
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
require_once (PATH_t3lib.'class.t3lib_diff.php');
require_once (PATH_t3lib.'class.t3lib_tcemain.php');
$LANG->includeLLFile('EXT:lang/locallang_show_rechis.xml');
require_once ('class.show_rechis.inc');









/**
 * Script Class for showing the history module of TYPO3s backend
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 * @see class.show_rechis.inc
 */
class SC_show_rechis {

		// Internal:
	var $content;
	var $doc;

	/**
	 * Initialize the module output
	 *
	 * @return	void
	 */
	function init()	{
		global $LANG;

			// Create internal template object:
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->docType = 'xhtml_trans';

			// Start the page header:
		$this->content.=$this->doc->startPage($LANG->getLL('title'));
		$this->content.=$this->doc->header($LANG->getLL('title'));
		$this->content.=$this->doc->spacer(5);
	}

	/**
	 * Generate module output
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG;

			// Start history object
		$historyObj = t3lib_div::makeInstance('recordHistory');

			// Return link:
		if ($historyObj->returnUrl)	{
			$this->content .= '<a href="'.htmlspecialchars($historyObj->returnUrl).'" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg('','gfx/goback.gif','width="14" height="14"').' alt="" />'.$LANG->getLL('returnLink',1).'</a>';
		}

			// Get content:
		$this->content .= $historyObj->main();

			// Return link:
		if ($historyObj->returnUrl)	{
			$link = '<a href="'.htmlspecialchars($historyObj->returnUrl).'" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg('','gfx/goback.gif','width="14" height="14"').' alt="" />'.$LANG->getLL('returnLink',1).'</a>';
			$this->content .= $this->doc->section($LANG->getLL('return'),$link,0,1);
		}
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.=$this->doc->spacer(8);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/show_rechis.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/show_rechis.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_show_rechis');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>