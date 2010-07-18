<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Workspace preview module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   62: class wsol_preview
 *   71:     function main()
 *  133:     function generateUrls()
 *  164:     function printFrameset()
 *  206:     function isBeLogin()
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

define('TYPO3_PROCEED_IF_NO_USER', '1');

unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require_once('class.wslib.php');



/**
 * Workspace dual preview
 * NOTICE: In this module you HAVE to check if a backend user is actually logged in if you perform operations that require a login! See function ->isBeLogin()
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class wsol_preview {

	var $workspace = 0;		// Which workspace to preview!

	/**
	 * Main function of class
	 *
	 * @return	void
	 */
	function main()	{

		if ($this->isBeLogin())	{
			$this->workspace = $GLOBALS['BE_USER']->workspace;
		}

		if ($header = t3lib_div::_GP('header'))	{
			if ($header!=='live')	{
				$headerText = 'Workspace Version ('.$this->workspace.'):';
				$color = 'green';
			} else {
				$headerText = 'Live Version:';
				$color = 'red';
			}

			$output =  '
				<html>
					<head>
						<title>Header</title>
					</head>
					<body bgcolor="'.$color.'">
						<strong style="font-family:Verdana, Arial;font-size:80%;color:white;">'.$headerText.'</strong>
					</body>
				</html>';
		} elseif ($msg = t3lib_div::_GP('msg'))	{
			switch($msg)	{
				case 'branchpoint':
					$message = '<strong>No live page available!</strong><br /><br />
					The previewed page was inside a "Branch" type version and has no traceable counterpart in the live workspace.';
				break;
				case 'newpage':
					$message = '<strong>New page!</strong><br /><br />
					The previewed page is created in the workspace and has no counterpart in the live workspace.';
				break;
				default:
					$message = 'Unknown message code "' . htmlspecialchars($msg) . '"';
				break;
			}

			$output =  '
				<html>
					<head>
						<title>Message</title>
					</head>
					<body bgcolor="#eeeeee">
						<div width="100%" height="100%" style="text-align: center; align: center;"><br /><br /><br /><br /><font face="verdana,arial" size="2" color="#666666">' . $message . '</font></div>
					</body>
				</html>';

		} else {
			$this->generateUrls();
			$output = $this->printFrameset();
		}

		echo $output;
	}

	/**
	 * URLs generated in $this->URL array
	 *
	 * @return	void
	 */
	function generateUrls()	{
			// Live URL:
		$pageId = intval(t3lib_div::_GP('id'));
		$language = intval(t3lib_div::_GP('L'));

		$this->URL = array(
			'liveHeader' => 'wsol_preview.php?header=live',
			'draftHeader' => 'wsol_preview.php?header=draft',
			'live' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').'index.php?id='.$pageId.'&L='.$language.'&ADMCMD_noBeUser=1',
			'draft' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').'index.php?id='.$pageId.'&L='.$language.'&ADMCMD_view=1&ADMCMD_editIcons=1&ADMCMD_previewWS='.$this->workspace,
			'versionMod' => '../../../sysext/version/cm1/index.php?id='.intval(t3lib_div::_GP('id')).'&diffOnly=1'
		);

		if ($this->isBeLogin())	{
				// Branchpoint; display error message then:
			if (t3lib_BEfunc::isPidInVersionizedBranch($pageId)=='branchpoint')	{
				$this->URL['live'] = 'wsol_preview.php?msg=branchpoint';
			}

			$rec = t3lib_BEfunc::getRecord('pages',$pageId,'t3ver_state');
			if ((int)$rec['t3ver_state']===1)	{
				$this->URL['live'] = 'wsol_preview.php?msg=newpage';
			}
		}
	}

	/**
	 * Outputting frameset HTML code
	 *
	 * @return	void
	 */
	function printFrameset()	{
		if ($this->isBeLogin())	{
			return '
			<html>
				<head>
					<title>Preview and compare workspace version with live version</title>
				</head>
				<frameset cols="60%,40%" framespacing="3" frameborder="3" border="3">
					<frameset rows="20,*,20,*" framespacing="3" frameborder="3" border="3">
						<frame name="frame_liveh" src="'.htmlspecialchars($this->URL['liveHeader']).'" marginwidth="0" marginheight="0" frameborder="1" scrolling="auto">
						<frame name="frame_live" src="'.htmlspecialchars($this->URL['live']).'" marginwidth="0" marginheight="0" frameborder="1" scrolling="auto">
						<frame name="frame_drafth" src="'.htmlspecialchars($this->URL['draftHeader']).'" marginwidth="0" marginheight="0" frameborder="1" scrolling="auto">
						<frame name="frame_draft" src="'.htmlspecialchars($this->URL['draft']).'" marginwidth="0" marginheight="0" frameborder="1" scrolling="auto">
					</frameset>
					<frame name="be" src="'.htmlspecialchars($this->URL['versionMod']).'" marginwidth="0" marginheight="0" frameborder="1" scrolling="auto">
				</frameset>
			</html>';
		} else {
			return '
			<html>
				<head>
					<title>Preview and compare workspace version with live version</title>
				</head>
				<frameset cols="*,*" framespacing="3" frameborder="3" border="3">
					<frameset rows="20,*" framespacing="3" frameborder="3" border="3">
						<frame name="frame_liveh" src="'.htmlspecialchars($this->URL['liveHeader']).'" marginwidth="0" marginheight="0" frameborder="1" scrolling="auto">
						<frame name="frame_live" src="'.htmlspecialchars($this->URL['live']).'" marginwidth="0" marginheight="0" frameborder="1" scrolling="auto">
					</frameset>
					<frameset rows="20,*" framespacing="3" frameborder="3" border="3">
						<frame name="frame_drafth" src="'.htmlspecialchars($this->URL['draftHeader']).'" marginwidth="0" marginheight="0" frameborder="1" scrolling="auto">
						<frame name="frame_draft" src="'.htmlspecialchars($this->URL['draft']).'" marginwidth="0" marginheight="0" frameborder="1" scrolling="auto">
					</frameset>
				</frameset>
			</html>';
		}
	}

	/**
	 * Checks if a backend user is logged in. Due to the line "define('TYPO3_PROCEED_IF_NO_USER', '1');" the backend is initialized even if no backend user was authenticated. This is in order to allow previews through this module of yet not-logged in users.
	 *
	 * @return	boolean		True, if there is a logged in backend user.
	 */
	function isBeLogin()	{
		return is_array($GLOBALS['BE_USER']->user);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/wsol_preview.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/wsol_preview.php']);
}

$previewObject = t3lib_div::makeInstance('wsol_preview');
$previewObject->main();
?>