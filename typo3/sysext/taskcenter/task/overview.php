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
class SC_mod_user_task_overview extends t3lib_SCbase {
	var $allExtClassConf=array();
	var $backPath;
	var $BE_USER;

	/**
	 * This makes sure that all classes of task-center related extensions are included
	 * Further it registers the classes in the variable $this->allExtClassConf
	 */
	function includeAllClasses()	{
		reset($this->MOD_MENU["function"]);
		while(list($k)=each($this->MOD_MENU["function"]))	{
			$curExtClassConf = $this->getExternalItemConfig($this->MCONF["name"],"function",$k);
			if (is_array($curExtClassConf) && $curExtClassConf["path"])	{
				$this->allExtClassConf[]=$curExtClassConf;
				$this->include_once[]=$curExtClassConf["path"];
			}
		}
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->doc = t3lib_div::makeInstance("mediumDoc");
		$this->doc->form='<form action="" method="post">';
		$this->backPath = $this->doc->backPath = $BACK_PATH;

				// JavaScript
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL)	{
				document.location = URL;
			}
		</script>
		';

		$this->doc->inDocStylesArray[] = '
			BODY#ext-taskcenter-task-overview-php DIV.typo3-mediumDoc { width: 99%; }
		';

		$this->content="";
		$this->content.=$this->doc->startPage($LANG->getLL("title"));
		$this->content.=$this->doc->header($LANG->getLL("title"));
		$this->content.=$this->doc->spacer(5);

//debug($this->allExtClassConf);
		reset($this->allExtClassConf);
		while(list(,$conf)=each($this->allExtClassConf))	{
			$extObj = t3lib_div::makeInstance($conf["name"]);
			$extObj->init($this,$conf);	// THis is just to make sure the LOCAL_LANG is included for all listed extensions. If they OVERRIDE each other there are trouble! By this initialization the parsetime is approx. double, but still acceptable.
			$extObj->backPath = $this->backPath;
			$extObj->mod_user_task_init($BE_USER);
			$this->content.=$extObj->overview_main($this);
		}

		$this->content.='
			<br />
			<p class="c-refresh">
				<a href="'.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')).'">'.
				'<img'.t3lib_iconWorks::skinImg('', $this->backPath.'gfx/refresh_n.gif','width="14" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refreshList',1).'" alt="" />'.
				$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refreshList',1).'</a>
			</p>
			<br />';
	}
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/taskcenter/task/overview.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/taskcenter/task/overview.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_mod_user_task_overview");
$SOBE->init();
$SOBE->includeAllClasses();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();
?>