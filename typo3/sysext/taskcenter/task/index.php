<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
* @author Kasper Skårhøj <kasperYYYY@typo3.com>
* @author Christian Jul Jensen <christian(at)jul(dot)net>
*     Revision for TYPO3 3.8.0 / Native Workflow System
*/

unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:taskcenter/task/locallang.php');
require_once('class.mod_user_task.php');

$BE_USER->modAccess($MCONF, 1);


// ***************************
// Script Classes
// ***************************
class SC_mod_user_task_index extends t3lib_SCbase {
	var $allExtClassConf = array();
	var $backPath;

	/**
	 * BE user
	 *
	 * @var t3lib_beUserAuth
	 */
	var $BE_USER;

	/**
	 * document template object
	 *
	 * @var noDoc
	 */
	var $doc;

	/**
	 * This makes sure that all classes of task-center related extensions are included
	 * Further it registers the classes in the variable $this->allExtClassConf
	 *
	 * @return	void
	 */
	function includeAllClasses() {
		foreach($this->MOD_MENU['function'] as $key => $name) {
			$curExtClassConf = $this->getExternalItemConfig($this->MCONF['name'], 'function', $key);
			if (is_array($curExtClassConf) && $curExtClassConf['path']) {
				$this->allExtClassConf[] = $curExtClassConf;
				$this->include_once[] = $curExtClassConf['path'];
			}
		}
	}

	/**
	 * This is the main function called by the TYPO3 framework
	 *
	 * @return	string		The conntent of the module (HTML)
	 */
	function main() {
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;

		/* Setup document template */
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->divClass = '';
		$this->doc->form = '<form action="index.php" method="post" name="editform">';
		$this->backPath = $this->doc->backPath = $BACK_PATH;
		$this->doc->getPageRenderer()->loadPrototype();
		$this->doc->JScode = '  <script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL) {
				window.location.href = URL;
			}
			'.(is_object($this->extObj)?$this->extObj->JScode():"").'
			</script>
			';
		$this->doc->JScode .= $this->doc->getDynTabMenuJScode();
		$this->doc->JScode .= '<script language="javascript" type="text/javascript">
		function resizeIframe(frame,max) {
			var parent = $("list_frame").up("body");
			var parentHeight = $(parent).getHeight();
			$("list_frame").setStyle({height: parentHeight+"px"});

		}
		// event crashes IE6 so he is excluded first
		//TODO: use a central handler instead of multiple single ones
		var version = parseFloat(navigator.appVersion.split(\';\')[1].strip().split(\' \')[1]);
		if (!(Prototype.Browser.IE && version == 6)) {
			Event.observe(window, "resize", resizeIframe, false);
		}
</script>';

		/* call getMainContent first, because what happens here might affect leftContent */
		$mainContent = $this->getMainContent();

		/* content... */
		$this->content = '';
		$this->content .= $this->doc->startPage($this->MOD_MENU['function'][$this->MOD_SETTINGS['function']]);
		$this->content .= '<table style="width: 98%;"><tr>';
		$this->content .= '<td valign="top" style="width: 20%;">'.$this->getleftHeader().$this->getDynTabMenu().'</td>';
		$this->content .= '<td valign="top" style="height:100%">'.$mainContent.'</td>';
		$this->content .= '</tr></table>';
	}

	/** Generate the dynamic tab menu in the left side by iterating
	 * over all submodules and creating configurations.
	 *
	 * @return string  the code for the dynamic tab menu (HTML)
	 */
	function getDynTabMenu() {
		//walk through registered submodules and generate configuration
		//for tabmenu
		$parts = Array();
		foreach($this->allExtClassConf as $conf) {
			$extObj = t3lib_div::makeInstance($conf['name']);
			/* call init to make sure the LOCAL_LANG is included for all listed
			* extensions. If they OVERRIDE each other there is trouble! */
			$extObj->init($this, $conf);
			$extObj->backPath = $this->backPath;
			$extObj->mod_user_task_init($GLOBALS['BE_USER']);
			$part = $extObj->overview_main();
			if (is_array($part)) {
				$parts[] = $part;
			}
		}
		return $this->doc->getDynTabMenu($parts, 'tx_taskcenter', 1, true);
	}

	/**
	 * Generate the header of the left column
	 *
	 * @return	string		header in the left side (HTML)
	 */
	function getleftHeader() {
		$name = $GLOBALS['BE_USER']->user['realName'] ? $GLOBALS['BE_USER']->user['realName'] : $GLOBALS['BE_USER']->user['username'];
		return '<h1>TYPO3 taskcenter <br />' . htmlspecialchars($name) . '</h1>';
	}

	/**
	 * Get the main content for the module by initiating the external object (if any) and calling it's main function.
	 *
	 * @return	string		main content (HTML)
	 */
	function getMainContent() {
		if (is_object($this->extObj)) {
			$this->extObj->backPath = $this->backPath;
			$this->extObj->mod_user_task_init($GLOBALS['BE_USER']);
			return $this->extObj->main();
		}
	}

	/**
	 * Output the content of the object to the browser
	 *
	 * @return	void
	 */
	function printContent() {
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/taskcenter/task/index.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/taskcenter/task/index.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_user_task_index');
$SOBE->init();
$SOBE->includeAllClasses();

// Include files?
foreach($SOBE->include_once as $INC_FILE) include_once($INC_FILE);
$SOBE->checkExtObj(); // Checking for first level external objects

$SOBE->main();
$SOBE->printContent();

?>