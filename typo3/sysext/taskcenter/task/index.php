<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * User Task Center
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
$LANG->includeLLFile("EXT:taskcenter/task/locallang.php");
require_once(PATH_t3lib."class.t3lib_scbase.php");
require_once("class.mod_user_task.php");

$BE_USER->modAccess($MCONF,1);


// ***************************
// Script Classes
// ***************************
class SC_mod_user_task_index extends t3lib_SCbase {
	var $BE_USER;
	
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->doc = t3lib_div::makeInstance("mediumDoc");
		$this->doc->form='<form action="index.php" method="POST" name="editform">';
		$this->backPath = $this->doc->backPath = $BACK_PATH;

		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL)	{
				document.location = URL;
			}
			'.(is_object($this->extObj)?$this->extObj->JScode():"").'
		</script>
		';
		
		$this->content="";
		$this->content.=$this->doc->startPage($this->MOD_MENU["function"][$this->MOD_SETTINGS["function"]]);
		$this->content.=$this->doc->header($this->MOD_MENU["function"][$this->MOD_SETTINGS["function"]]);
		$this->content.=$this->doc->spacer(5);


		/*
		if (!$BE_USER->isAdmin())	{
					// This is used to test with other users. Development ONLY!
				$BE_USER = t3lib_div::makeInstance("t3lib_beUserAuth");	// New backend user object
				$BE_USER->OS = TYPO3_OS;
				$BE_USER->setBeUserByUid(4);
				$BE_USER->fetchGroupData();
		}
		*/

		if (is_object($this->extObj))	{
			$this->extObj->backPath = $this->backPath;
			$this->extObj->mod_user_task_init($BE_USER);
			$this->content.=$this->extObj->main();
		}
	}
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/taskcenter/task/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/taskcenter/task/index.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_mod_user_task_index");
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
$SOBE->checkExtObj();	// Checking for first level external objects

$SOBE->main();
$SOBE->printContent();
?>