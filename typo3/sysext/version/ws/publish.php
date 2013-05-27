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
 * Module: Workspace publisher
 *
 * $Id: publish.php 8742 2010-08-30 18:55:32Z baschny $
 *
 * @author	Dmitry Dulepov <typo3@accio.lv>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   70: class SC_mod_user_ws_publish extends t3lib_SCbase
 *   83:     function init()
 *   95:     function closeAndReload()
 *  106:     function nextPortion(val)
 *  127:     function main()
 *  142:     function printContent()
 *  151:     function getContent()
 *  227:     function getRecords()
 *  243:     function formatProgressBlock($messageLabel)
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


// Initialize module:
unset($MCONF);
require('conf.php');
require($BACK_PATH . 'init.php');
require($BACK_PATH . 'template.php');
$BE_USER->modAccess($MCONF, 1);

// Include libraries of various kinds used inside:
$LANG->includeLLFile('EXT:lang/locallang_mod_user_ws.xml');
require_once('class.wslib.php');

define('MAX_RECORDS_TO_PUBLISH', 30);

class SC_mod_user_ws_publish extends t3lib_SCbase {

	var	$isSwap;
	var	$title;
	var	$nextRecordNumber;
	var	$publishData;
	var	$recordCount;

	/**
	 * Document Template Object
	 *
	 * @var mediumDoc
	 */
	var $doc;

	/**
	 * Initializes the module. See <code>t3lib_SCbase::init()</code> for more information.
	 *
	 * @return	void
	 */
	function init()	{
		// Setting module configuration:
		$this->MCONF = $GLOBALS['MCONF'];

		$this->isSwap = t3lib_div::_GP('swap');
		$this->nextRecordNumber = t3lib_div::_GP('continue_publish');

		// Initialize Document Template object:
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->JScode = '<script type="text/javascript">/*<![CDATA[*/
			function closeAndReload() {
				//window.opener.location.reload(); window.close();
				window.location.href = \'index.php\';
			}

			function nextPortion(val) {
				setTimeout(\'window.location.href = "publish.php?continue_publish=\' + val + \'&swap=' . ($this->isSwap ? 1 : 0) . '"\', 750);
			}
		/*]]>*/</script>
		';
		$this->doc->inDocStyles = '
		#progress-block { width: 450px; margin: 50px auto; text-align: center; }
		H3 { margin-bottom: 20px; }
		P, IMG { margin-bottom: 20px; }
		#progress-block A { text-decoration: underline; }
';

		// Parent initialization:
		t3lib_SCbase::init();
	}

	/**
	 * Creates module content.
	 *
	 * @return	void
	 */
	function main()	{
		$this->title = $GLOBALS['LANG']->getLL($this->isSwap ? 'swap_title' : 'publish_title');

		$content = $this->getContent(); // sets body parts to doc!

		$this->content .= $this->doc->startPage($this->title);
		$this->content .= $content;
		$this->content .= $this->doc->endPage();
	}

	/**
	 * Outputs content.
	 *
	 * @return	void
	 */
	function printContent() {
		echo $this->content;
	}

	/**
	 * Performs action and generates content.
	 *
	 * @return	string		Generated content
	 */
	function getContent() {
		$content = '';
		if ($this->nextRecordNumber) {
			// Prepare limited set of records
			$this->publishData = $GLOBALS['BE_USER']->getSessionData('workspacePublisher');
			$this->recordCount = $GLOBALS['BE_USER']->getSessionData('workspacePublisher_count');
			$limitedCmd = array(); $numRecs = 0;
			foreach ($this->publishData as $table => $recs) {
				foreach ($recs as $key => $value) {
					$numRecs++;
					$limitedCmd[$table][$key] = $value;
					//$this->content .= $table.':'.$key.'<br />';
					if ($numRecs == MAX_RECORDS_TO_PUBLISH) {
						break;
					}
				}
				if ($numRecs == MAX_RECORDS_TO_PUBLISH) {
					break;
				}
			}

			if ($numRecs == 0) {
				// All done
				$GLOBALS['BE_USER']->setAndSaveSessionData('workspacePublisher', null);
				$GLOBALS['BE_USER']->setAndSaveSessionData('workspacePublisher_count', 0);
				$content .= '<div id="progress-block"><h3>' . $this->title . '</h3><p>';
				$content .= $GLOBALS['LANG']->getLL($this->isSwap ? 'workspace_swapped' : 'workspace_published');
				$content .= '</p><p><a href="index.php">' . $GLOBALS['LANG']->getLL('return_to_index') . '</a>';
				$content .= '</p></div>';
			}
			else {
				// Execute the commands:
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values = 0;
				$tce->start(array(), $limitedCmd);
				$tce->process_cmdmap();

				$errors = $tce->errorLog;
				if (count($errors) > 0) {
					$content .= '<h3>' . $GLOBALS['LANG']->getLL('label_errors') . '</h3><br />' . implode('<br />', $errors);
					$content .= '<br /><br /><a href="index.php">' . $GLOBALS['LANG']->getLL('return_to_index') . '</a>';
				}
				else {

					// Unset processed records
					foreach ($limitedCmd as $table => $recs) {
						foreach ($recs as $key => $value) {
							unset($this->publishData[$table][$key]);
						}
					}
					$GLOBALS['BE_USER']->setAndSaveSessionData('workspacePublisher', $this->publishData);
					$content .= $this->formatProgressBlock($this->isSwap ? 'swap_status' : 'publish_status');
					$this->doc->bodyTagAdditions = 'onload="nextPortion(' . ($this->nextRecordNumber + MAX_RECORDS_TO_PUBLISH) . ')"';
				}
			}
		}
		else {
			$this->getRecords();
			if ($this->recordCount > 0) {
				$GLOBALS['BE_USER']->setAndSaveSessionData('workspacePublisher', $this->publishData);
				$GLOBALS['BE_USER']->setAndSaveSessionData('workspacePublisher_count', $this->recordCount);
				$content .= $this->formatProgressBlock($this->isSwap ? 'swap_prepare' : 'publish_prepare');
				$this->doc->bodyTagAdditions = 'onload="nextPortion(1)"';
			}
			else {
				$this->doc->bodyTagAdditions = 'onload="closeAndReload()"';
			}
		}
		return $content;
	}

	/**
	 * Fetches command array for publishing and calculates number of records in it. Sets class members accordingly.
	 *
	 * @return	void
	 */
	function getRecords() {
		$wslibObj = t3lib_div::makeInstance('wslib');
		$this->publishData = $wslibObj->getCmdArrayForPublishWS($GLOBALS['BE_USER']->workspace, $this->isSwap);

		$this->recordCount = 0;
		foreach ($this->publishData as $table => $recs) {
			$this->recordCount += count($recs);
		}
	}

	/**
	 * Creates block with progress bar
	 *
	 * @param	string		$messageLabel Message label to display
	 * @return	string		Generated content
	 */
	function formatProgressBlock($messageLabel) {
		return '<div id="progress-block"><h3>' . $this->title . '</h3><p>' .
				sprintf($GLOBALS['LANG']->getLL($messageLabel),
				$this->nextRecordNumber,
				min($this->recordCount, $this->nextRecordNumber - 1 + MAX_RECORDS_TO_PUBLISH),
				$this->recordCount) . '<br />' .
				$GLOBALS['LANG']->getLL('please_wait') .
				'</p><img src="progress.gif" width="225" height="20" alt="" />' .
				'<p>' .
				$GLOBALS['LANG']->getLL('do_not_interrupt_publishing_1') .
				'<br />' .
				$GLOBALS['LANG']->getLL('do_not_interrupt_publishing_2') .
				'</p></div>';
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/publish.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/publish.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_user_ws_publish');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>