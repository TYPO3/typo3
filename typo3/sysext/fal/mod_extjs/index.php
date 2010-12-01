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

$LANG->includeLLFile('EXT:fal/mod_extjs/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');

	// This checks permissions and exits if the users has no permission for entry.
$BE_USER->modAccess($MCONF,1);

/**
 * Module 'ExtJS filelist' for the File Abstraction Layer.
 *
 * @todo Andy Grunwald, 01.12.2010, Rename class to match naming conventions? new name tx_fal_mod_extjs_Registry?
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
class  tx_fal_list_modextjs extends t3lib_SCbase {

	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		parent::init();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

			// initialize doc
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $BACK_PATH;

		$pageRenderer = &$this->doc->getPageRenderer();
		$pageRenderer->enableDebugMode();
		$pageRenderer->loadExtJS();

			// quickfix for selected rows
		$pageRenderer->addCssInlineBlock('fallist', '
.x-grid3-row {
	background-color: transparent !important;
}

.x-grid3-row-selected {
	background-color: #e6e6e6 !important;
}
		');
		tx_fal_list_Registry::addModExtDirectNamespacesToPage($pageRenderer);
		tx_fal_list_Registry::addModJsComponentsToPage($pageRenderer);

			// Build the <body> for the module
		$this->content = $this->doc->startPage($LANG->getLL('title'));
		$this->content.=$this->doc->endPage();

		echo $this->content;
	}

	/**
	 * DESCRIPTION
	 *
	 * @param	[to be defined]		$fileName	DESCRIPTION
	 * @param	[to be defined]		$type		DESCRIPTION
	 * @param	boolean				$compress	DESCRIPTION
	 */
	protected function addJsFile($fileName, $type = NULL, $compress = FALSE){
		$this->doc->getPageRenderer()->addJsFile($fileName, $type, $compress);
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/mod_extjs/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/mod_extjs/index.php']);
}

	// Make instance:
$SOBE = t3lib_div::makeInstance('tx_fal_list_modextjs');
$SOBE->init();

	// Include files?
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

$SOBE->main();
?>