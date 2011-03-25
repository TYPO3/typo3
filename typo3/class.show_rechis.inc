<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skårhøj (kasperYYYY@typo3.com)
*  (c) 2006-2009 Sebastian Kurfürst (sebastian@garbage-group.de)
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
 * Class for the record history display script (show_rechis.php)
 *
 * XHTML Compliant
 *
 * @author	Sebastian Kurfürst <sebastian@garbage-group.de>
 */


/**
 * Class for the record history display script (show_rechis.php)
 *
 * @author	Sebastian Kurfürst <sebastian@garbage-group.de>
 * @package TYPO3
 * @subpackage core
 */
class recordHistory {
		// External, static:
	var $maxSteps=20;		// Maximum number of sys_history steps to show.
	var $showDiff=1;		// display diff or not (0-no diff, 1-inline)
	var $showSubElements=1;		// on a pages table - show sub elements as well.
	var $showInsertDelete=1;		// show inserts and deletes as well

		// Internal, GPvars
	var $element;			// Element reference, syntax [tablename]:[uid]
	var $lastSyslogId;		// syslog ID which is not shown anymore
	var $returnUrl;

		// Internal
	var $changeLog;
	var $showMarked=FALSE;
	/**
	 * Constructor for the class
	 *
	 * @return	void
	 */
	function recordHistory()	{
			// GPvars:
		$this->element = t3lib_div::_GP('element');
		$this->returnUrl = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));
		$this->lastSyslogId = t3lib_div::_GP('diff');
		$this->rollbackFields = t3lib_div::_GP('rollbackFields');
			// resolve sh_uid if set
		$this->resolveShUid();
	}

	/**
	 * Main function for the listing of history.
	 * It detects incoming variables like element reference, history element uid etc. and renders the correct screen.
	 *
	 * @return	HTML		content for the module
	 */
	function main()	{
		$content = '';

			// single-click rollback
		if (t3lib_div::_GP('revert') && t3lib_div::_GP('sumUp'))	{
			$this->rollbackFields = t3lib_div::_GP('revert');
			$this->showInsertDelete = 0;
			$this->showSubElements = 0;

			$element = explode(':',$this->element);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','sys_history', 'tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($element[0], 'sys_history').' AND recuid='.intval($element[1]), '', 'uid DESC', '1');
			$record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$this->lastSyslogId = $record['sys_log_uid'];

			$this->createChangeLog();
			$completeDiff = $this->createMultipleDiff();
			$this->performRollback($completeDiff);
			t3lib_utility_Http::redirect($this->returnUrl);
		}

			// save snapshot
		if (t3lib_div::_GP('highlight') && !t3lib_div::_GP('settings'))	{
			$this->toggleHighlight(t3lib_div::_GP('highlight'));
		}

		$content .= $this->displaySettings();
		if ($this->createChangeLog())	{
			if ($this->rollbackFields)	{
				$completeDiff = $this->createMultipleDiff();
				$content .= $this->performRollback($completeDiff);

			}
			if ($this->lastSyslogId)	{
				$completeDiff = $this->createMultipleDiff();
				$content .= $this->displayMultipleDiff($completeDiff);
			}
			if ($this->element)	{
				$content .= $this->displayHistory();
			}
		}
		return $content;
	}

	/*******************************
	 *
	 * database actions
	 *
	 *******************************/

	/**
	 * toggles highlight state of record
	 *
	 * @param	integer		uid of sys_history entry
	 * @return	[type]		...
	 */
	function toggleHighlight($uid)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('snapshot','sys_history','uid='.intval($uid));
		$tmp = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if ($tmp['snapshot'])	{
			$tmp = 0;
		} else {
			$tmp = 1;
		}
		$updateFields = array('snapshot' => $tmp);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_history','uid='.intval($uid),$updateFields);
	}

	/**
	 * perform rollback
	 *
	 * @param	array		diff array to rollback
	 * @return	void
	 * @access private
	 */
	function performRollback($diff)	{
		if (!$this->rollbackFields)	{
			return 0;
		}

		$reloadPageFrame=0;
		$rollbackData = explode(':',$this->rollbackFields);

			// PROCESS INSERTS AND DELETES
			// rewrite inserts and deletes
		$cmdmapArray = array();
		if ($diff['insertsDeletes'])	{

			switch (count($rollbackData))	{
				case 1:	// all tables
					$data = $diff['insertsDeletes'];
					break;
				case 2:	// one record
					if ($diff['insertsDeletes'][$this->rollbackFields]) {
						$data[$this->rollbackFields] = $diff['insertsDeletes'][$this->rollbackFields];
					}
					break;
				case 3:	// one field in one record -- ignore!
					break;
			}
			if ($data)	{
				foreach ($data as $key => $action)	{
					$elParts = explode(':',$key);
					if ($action == 1)	{	// inserted records should be deleted
						$cmdmapArray[$elParts[0]][$elParts[1]]['delete'] = 1;
							// when the record is deleted, the contents of the record do not need to be updated
						unset($diff['oldData'][$key]);
						unset($diff['newData'][$key]);
					} elseif ($action == -1) {	// deleted records should be inserted again
						$cmdmapArray[$elParts[0]][$elParts[1]]['undelete'] = 1;
					}
				}
			}
		}
			// Writes the data:
		if ($cmdmapArray)	{
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values=0;
			$tce->debug=0;
			$tce->dontProcessTransformations=1;
			$tce->start(array(),$cmdmapArray);
			$tce->process_cmdmap();
			unset($tce);
			if (isset($cmdmapArray['pages']))	{
				$reloadPageFrame=1;
			}
		}

			// PROCESS CHANGES
			// create an array for process_datamap
		$diff_modified = array();
		foreach ($diff['oldData'] as $key => $value)	{
			$splitKey = explode(':',$key);
			$diff_modified[$splitKey[0]][$splitKey[1]] = $value;
		}
		switch (count($rollbackData))	{
			case 1:	// all tables
				$data = $diff_modified;
				break;
			case 2:	// one record
				$data[$rollbackData[0]][$rollbackData[1]] = $diff_modified[$rollbackData[0]][$rollbackData[1]];
				break;
			case 3:	// one field in one record
				$data[$rollbackData[0]][$rollbackData[1]][$rollbackData[2]] = $diff_modified[$rollbackData[0]][$rollbackData[1]][$rollbackData[2]];
				break;
		}
			// Removing fields:
		$data = $this->removeFilefields($rollbackData[0],$data);

			// Writes the data:
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;
		$tce->debug=0;
		$tce->dontProcessTransformations=1;
		$tce->start($data,array());
		$tce->process_datamap();
		unset($tce);
		if (isset($data['pages']))	{
			$reloadPageFrame=1;
		}

			// return to normal operation
		$this->lastSyslogId = FALSE;
		$this->rollbackFields = FALSE;
		$this->createChangeLog();

			// reload page frame if necessary
		if ($reloadPageFrame)	{
			return '<script type="text/javascript">
			/*<![CDATA[*/
			if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)	{
				top.content.nav_frame.refresh_nav();
			}
			/*]]>*/
			</script>';
		}
	}

	/*******************************
	 *
	 * Display functions
	 *
	 *******************************/

	/**
	 * Displays settings
	 *
	 * @return	string		HTML code to modify settings
	 */
	function displaySettings()	{
		global $BE_USER, $LANG, $SOBE;
			// get current selection from UC, merge data, write it back to UC
		$currentSelection = is_array($BE_USER->uc['moduleData']['history']) ? $BE_USER->uc['moduleData']['history'] : array('maxSteps' => '', 'showDiff' => 1, 'showSubElements' => 1, 'showInsertDelete' => 1);

		$currentSelectionOverride = t3lib_div::_GP('settings');
		if ($currentSelectionOverride)	{
			$currentSelection = array_merge($currentSelection,$currentSelectionOverride);
			$BE_USER->uc['moduleData']['history'] = $currentSelection;
			$BE_USER->writeUC($BE_USER->uc);
		}

			// display selector for number of history entries
		$selector['maxSteps'] = array(
			10 => 10,
			20 => 20,
			50 => 50,
			100 => 100,
			'' => 'maxSteps_all',
			'marked' => 'maxSteps_marked'
		);
		$selector['showDiff'] = array(
			0 => 'showDiff_no',
			1 => 'showDiff_inline'
		);
		$selector['showSubElements'] = array(
			0 => 'no',
			1 => 'yes',
		);
		$selector['showInsertDelete'] = array(
			0 => 'no',
			1 => 'yes',
		);
			// render selectors
		$displayCode = '';
		foreach ($selector as $key => $values)	{
			$displayCode .= '<tr><td>'.$LANG->getLL($key,1).'</td><td><select name="settings['.$key.']" onChange="document.settings.submit()" style="width:100px">';
			foreach ($values as $singleKey => $singleVal) {
				$caption = $LANG->getLL($singleVal,1)?$LANG->getLL($singleVal,1):$singleVal;
				$displayCode .= '<option value="'.$singleKey.'" '.(($singleKey ==  $currentSelection[$key])?'selected':'').'> '.$caption.'</option>';
			}
			$displayCode .= '</select></td></tr>';
		}
			// set values correctly
		if ($currentSelection['maxSteps'] != 'marked')	{
			$this->maxSteps = $currentSelection['maxSteps']?intval($currentSelection['maxSteps']):'';
		} else {
			$this->showMarked = TRUE;
			$this->maxSteps = FALSE;
		}
		$this->showDiff = intval($currentSelection['showDiff']);
		$this->showSubElements = intval($currentSelection['showSubElements']);
		$this->showInsertDelete = intval($currentSelection['showInsertDelete']);

		$content = '';
			// get link to page history if the element history is shown
		$elParts = explode(':',$this->element);
		if ($elParts[0] != 'pages')	{
			$content .= '<strong>'.$LANG->getLL('elementHistory',1).'</strong><br />';
			$pid = t3lib_BEfunc::getRecordRaw($elParts[0],'uid='.intval($elParts[1]));
			$content .= $this->linkPage($LANG->getLL('elementHistory_link',1),array('element' => 'pages:'.$pid['pid']));
		}
		$content .= '<form name="settings" action="'.t3lib_div::getIndpEnv('TYPO3_REQUEST_URL').'" method="post"><table>'.$displayCode.'</table></form>';
		return $SOBE->doc->section($LANG->getLL('settings',1),$content,0,1,0,0);

	}

	/**
	 * Shows the full change log
	 *
	 * @return	string		HTML for list, wrapped in a table.
	 */
	function displayHistory()	{
		global $LANG;
		global $SOBE;
		global $TCA;

		$lines=array();

			// Initialize:
		$lines[] = '<tr class="t3-row-header">
				<td> </td>
				<td>'.$LANG->getLL('time',1).'</td>
				<td>'.$LANG->getLL('age',1).'</td>
				<td>'.$LANG->getLL('user',1).'</td>
				<td>'.$LANG->getLL('tableUid',1).'</td>
				<td>'.$LANG->getLL('differences',1).'</td>
				<td>&nbsp;</td>
			</tr>';

			// get default page TSconfig expiration time
		$elParts = explode(':',$this->element);
		if ($elParts[0] != 'pages')	{
			$tmp = t3lib_BEfunc::getRecordRaw($elParts[0],'uid='.intval($elParts[1]));
			$pid = $tmp['pid'];
		} else {
			$pid = $elParts[1];
		}
		$tmpTsConfig = $GLOBALS['BE_USER']->getTSConfig('TCEMAIN',t3lib_BEfunc::getPagesTSconfig($pid));
		$expirationTime = isset($tmpTsConfig['properties']['default.']['history.']['maxAgeDays']) ? $tmpTsConfig['properties']['default.']['history.']['maxAgeDays'] : 30;

		$expirationTimestamp = $expirationTime ? ($GLOBALS['EXEC_TIME'] - 60 * 60 * 24 * $expirationTime) : 0;
		$expirationWarning = 0;

		$be_user_array = t3lib_BEfunc::getUserNames();

			// Traverse changelog array:
		if (!$this->changeLog)	{
			return 0;
		}
		$i = 0;
		foreach ($this->changeLog as $sysLogUid => $entry)	{
				// stop after maxSteps
			if ($i > $this->maxSteps && $this->maxSteps)	{
				break;
			}

				// display inconsistency warning
			if ($entry['tstamp'] < $expirationTimestamp && !$expirationWarning)	{
				$expirationWarning = 1;

				$lines[] = '
				<tr class="c-headLine">
					<td colspan="7"><strong>'.$LANG->getLL('consistenceWarning',1).'</strong></td>
				</tr>';
			}

				// show only marked states
			if (!$entry['snapshot'] && $this->showMarked)	{
				continue;
			}
			$i++;
				// get user names
			$userName = ($entry['user']?$be_user_array[$entry['user']]['username']:$LANG->getLL('externalChange',1));

				// build up single line
			$singleLine = array();

				// diff link
			$image = t3lib_iconWorks::getSpriteIcon('actions-view-go-forward', array('title' => $LANG->getLL('sumUpChanges', TRUE)));
			$singleLine[] = '<span>'.$this->linkPage($image,array('diff' => $sysLogUid)).'</span>';	// remove first link

			$singleLine[] = htmlspecialchars(t3lib_BEfunc::datetime($entry['tstamp']));	// add time
			$singleLine[] = htmlspecialchars(t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME'] - $entry['tstamp'], $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')));	// add age
			$singleLine[] = htmlspecialchars($userName);	// add user name
			$singleLine[] = $this->linkPage($this->generateTitle($entry['tablename'],$entry['recuid']),array('element' => $entry['tablename'].':'.$entry['recuid']),'',$LANG->getLL('linkRecordHistory',1));	// add record UID

				// show insert/delete/diff/changed field names
			if ($entry['action'])	{	// insert or delete of element
				$singleLine[] = '<strong>'.htmlspecialchars($LANG->getLL($entry['action'],1)).'</strong>';
			} else {
				if (!$this->showDiff)	{	// display field names instead of full diff
					// re-write field names with labels
				$tmpFieldList = explode(',',$entry['fieldlist']);
				foreach ($tmpFieldList as $key => $value)	{
					$tmp = str_replace(':','',$LANG->sl(t3lib_BEfunc::getItemLabel($entry['tablename'],$value),1));
					if($tmp)	$tmpFieldList[$key] = $tmp;
					else	unset($tmpFieldList[$key]);	// remove fields if no label available
				}
				$singleLine[] = htmlspecialchars(implode(',',$tmpFieldList));
				} else {	// display diff
					$diff = $this->renderDiff($entry,$entry['tablename']);
					$singleLine[] = $diff;
				}
			}
				// show link to mark/unmark state
			if (!$entry['action'])	{
				if ($entry['snapshot'])	{
					$image = '<img'.t3lib_iconWorks::skinImg('','gfx/unmarkstate.gif').' align="top" alt="'.$LANG->getLL('unmarkState',1).'" title="'.$LANG->getLL('unmarkState',1).'" />';
				} else {
					$image = '<img'.t3lib_iconWorks::skinImg('','gfx/markstate.gif').' align="top" alt="'.$LANG->getLL('markState',1).'" title="'.$LANG->getLL('markState',1).'" />';
				}
				$singleLine[] = $this->linkPage($image,array('highlight' => $entry['uid']));
			} else {
				$singleLine[] = '';
			}

				// put line together
			$lines[] = '
				<tr class="db_list_normal">
					<td>' . implode('</td><td>', $singleLine) . '</td>
				</tr>';
		}

			// Finally, put it all together:
		$theCode = '
			<!--
				History (list):
			-->
			<table class="typo3-dblist" border="0" cellpadding="0" cellspacing="0" id="typo3-history">
				' . implode('', $lines) . '
			</table>';

		if ($this->lastSyslogId)	{
			$theCode .= '<br />' .  $this->linkPage(t3lib_iconWorks::getSpriteIcon('actions-move-to-bottom', array('title' => $LANG->getLL('fullView', TRUE))), array('diff' => ''));
		}

			// Add message about the difference view.
		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$GLOBALS['LANG']->getLL('differenceMsg'),
			'',
			t3lib_FlashMessage::INFO
		);

		$theCode .= '<br /><br />' . $flashMessage->render() . '<br />';

			// Add CSH:
		$theCode .= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'history_'.($this->sumUp ? 'sum' : 'log'), $GLOBALS['BACK_PATH'],'');

			// Add the whole content as a module section:
		return $SOBE->doc->section($LANG->getLL('changes'),$theCode,0,1);
	}

	/**
	 * Displays a diff over multiple fields including rollback links
	 *
	 * @param	array		difference array
	 * @return	string		HTML output
	 */
	function displayMultipleDiff($diff)	{
		global $SOBE, $LANG;
		$content = '';

			// get all array keys needed
		$arrayKeys = array_merge(array_keys($diff['newData']),array_keys($diff['insertsDeletes']),array_keys($diff['oldData']));
		$arrayKeys = array_unique($arrayKeys);

		if ($arrayKeys)	{
			foreach ($arrayKeys as $key)	{
				$record = '';
				$elParts = explode(':',$key);
					// turn around diff because it should be a "rollback preview"
				if ($diff['insertsDeletes'][$key] == 1)	{	// insert
					$record .= '<strong>'.$LANG->getLL('delete',1).'</strong>';
					$record .= '<br />';
				} elseif ($diff['insertsDeletes'][$key] == -1)	{
					$record .= '<strong>'.$LANG->getLL('insert',1).'</strong>';
					$record .= '<br />';
				}
					// build up temporary diff array
					// turn around diff because it should be a "rollback preview"
				if ($diff['newData'][$key])	{
					$tmpArr['newRecord'] = $diff['oldData'][$key];
					$tmpArr['oldRecord'] = $diff['newData'][$key];
					$record .= $this->renderDiff($tmpArr, $elParts[0],$elParts[1]);
				}

				$elParts = explode(':',$key);
				$titleLine = $this->createRollbackLink($key, $LANG->getLL('revertRecord',1),1) . $this->generateTitle($elParts[0],$elParts[1]);
				$record = '<div style="margin-left:10px;padding-left:5px;border-left:1px solid black;border-bottom:1px dotted black;padding-bottom:2px;">'.$record.'</div>';

				$content .= $SOBE->doc->section($titleLine,$record,0,0,0,1);
			}
			$content = $this->createRollbackLink('ALL', $LANG->getLL('revertAll',1),0)  . '<div style="margin-left:10px;padding-left:5px;border-left:1px solid black;border-bottom:1px dotted black;padding-bottom:2px;">'.$content.'</div>';
		} else {
			$content = $LANG->getLL('noDifferences',1);
		}
		return $SOBE->doc->section($LANG->getLL('mergedDifferences',1),$content,0,1,0,1);
	}

	/**
	 * Renders HTML table-rows with the comparison information of an sys_history entry record
	 *
	 * @param	array		sys_history entry record.
	 * @param	string		The table name
	 * @param	integer		If set to UID of record, display rollback links
	 * @return	string		HTML table
	 * @access private
	 */
	function renderDiff($entry,$table,$rollbackUid=0)	{
		global $SOBE, $LANG, $TCA;
		$lines=array();
 		if (is_array($entry['newRecord']))	{

			$t3lib_diff_Obj = t3lib_div::makeInstance('t3lib_diff');

			$fieldsToDisplay = array_keys($entry['newRecord']);
			foreach($fieldsToDisplay as $fN)	{
				t3lib_div::loadTCA($table);
				if (is_array($TCA[$table]['columns'][$fN]) && $TCA[$table]['columns'][$fN]['config']['type']!='passthrough')	{

						// Create diff-result:
					$diffres = $t3lib_diff_Obj->makeDiffDisplay(
						t3lib_BEfunc::getProcessedValue($table,$fN,$entry['oldRecord'][$fN],0,1),
						t3lib_BEfunc::getProcessedValue($table,$fN,$entry['newRecord'][$fN],0,1)
					);
					$lines[]='
						<tr class="bgColor4">
						'.($rollbackUid?'<td style="width:33px">'.$this->createRollbackLink($table.':'.$rollbackUid.':'.$fN, $LANG->getLL('revertField',1),2).'</td>':'').'
							<td style="width:90px"><em>'.$LANG->sl(t3lib_BEfunc::getItemLabel($table,$fN),1).'</em></td>
							<td style="width:300px">'.nl2br($diffres).'</td>
						</tr>';
				}
			}
		}
		if ($lines)	{
			$content = '<table border="0" cellpadding="2" cellspacing="2" id="typo3-history-item">
					'.implode('',$lines).'
				</table>';
			return $content;
		}
		return NULL;	// error fallback
	}

	/*******************************
	 *
	 * build up history
	 *
	 *******************************/

	/**
	 * Creates a diff between the current version of the records and the selected version
	 *
	 * @return	array		diff for many elements
	 */
	function createMultipleDiff()	{
		$insertsDeletes = array();
		$newArr = array();
		$differences = array();
		if (!$this->changeLog)	{
			return 0;
		}

			// traverse changelog array
		foreach ($this->changeLog as $key => $value)	{
			$field = $value['tablename'].':'.$value['recuid'];
				// inserts / deletes
			if ($value['action'])	{
				if (!$insertsDeletes[$field])	{
					$insertsDeletes[$field] = 0;
				}
				if ($value['action'] == 'insert')	{
					$insertsDeletes[$field]++;
				} else {
					$insertsDeletes[$field]--;
				}
					// unset not needed fields
				if ($insertsDeletes[$field] == 0)	{
					unset($insertsDeletes[$field]);
				}
			} else {
					// update fields
				if (!isset($newArr[$field]))	{	// first row of field
					$newArr[$field] = $value['newRecord'];
					$differences[$field] = $value['oldRecord'];
				} else { // standard
					$differences[$field] = array_merge($differences[$field],$value['oldRecord']);
				}
			}
		}

			// remove entries where there were no changes effectively
		foreach ($newArr as $record => $value)	{
			foreach ($value as $key => $innerVal)	{
				if ($newArr[$record][$key] == $differences[$record][$key])	{
					unset($newArr[$record][$key]);
					unset($differences[$record][$key]);
				}
			}
			if (empty($newArr[$record]) && empty($differences[$record]))	{
				unset($newArr[$record]);
				unset($differences[$record]);
			}
		}
		return array(
			'newData' => $newArr,
			'oldData' => $differences,
			'insertsDeletes' => $insertsDeletes
		);
	}

	/**
	 * Creates change log including sub-elements, filling $this->changeLog
	 *
	 * @return	[type]		...
	 */
	function createChangeLog()	{

		global $TCA;
		$elParts = explode(':',$this->element);
		$changeLog = $this->getHistoryData($elParts[0],$elParts[1]);

			// get history of tables of this page and merge it into changelog
		if ($elParts[0] == 'pages' && $this->showSubElements)	{
			foreach ($TCA as $tablename => $value)	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid',$tablename,'pid='.intval($elParts[1]));	// check if there are records on the page
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					if ($newChangeLog = $this->getHistoryData($tablename, $row['uid']))	{	// if there is history data available, merge it into changelog
						foreach ($newChangeLog as $key => $value)	{
							$changeLog[$key] = $value;
						}
					}
				}
			}
		}
		if(!$changeLog)	{
			return 0;
		}

		krsort($changeLog);
		$this->changeLog = $changeLog;

		return 1;
	}

	/**
	 * Gets history and delete/insert data from sys_log and sys_history
	 *
	 * @param	string		DB table name
	 * @param	integer		UID of record
	 * @return	array		history data of the record
	 */
	function getHistoryData($table,$uid)	{
		global $TCA;
		$uid = $this->resolveElement($table,$uid);
			// If table is found in $TCA:
		if ($TCA[$table])	{
				// Selecting the $this->maxSteps most recent states:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'sys_history.*,sys_log.userid',
						'sys_history,sys_log',
						'sys_history.sys_log_uid=sys_log.uid
							AND sys_history.tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_history').'
							AND sys_history.recuid='.intval($uid),
						'',
						'sys_log.uid DESC',
						$this->maxSteps
					);

				// Traversing the result, building up changesArray / changeLog:
			#$changesArray=array();	// used temporarily to track intermedia changes
			$changeLog=array();
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					// only history until a certain syslog ID needed
				if ($row['sys_log_uid'] < $this->lastSyslogId && $this->lastSyslogId)	{
					continue;
				}
				$hisDat = unserialize($row['history_data']);
				if (is_array($hisDat['newRecord']) && is_array($hisDat['oldRecord']))	{

						// Add hisDat to the changeLog
					$hisDat['uid']=$row['uid'];
					$hisDat['tstamp']=$row['tstamp'];
					$hisDat['user']=$row['userid'];
					$hisDat['snapshot']=$row['snapshot'];
					$hisDat['fieldlist']=$row['fieldlist'];
					$hisDat['tablename']=$row['tablename'];
					$hisDat['recuid']=$row['recuid'];

					$changeLog[$row['sys_log_uid']]=$hisDat;

						// Update change array
						// This is used to detect if any intermedia changes have been made.
					#$changesArray = array_merge($changesArray,$hisDat['oldRecord']);
				} else {
					debug('ERROR: [getHistoryData]');
					return 0;	// error fallback
				}
			}
				// SELECT INSERTS/DELETES
			if ($this->showInsertDelete)	{
					// Select most recent inserts and deletes // WITHOUT snapshots
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid,userid,action,tstamp',
						'sys_log',
						'type=1
							AND ( action=1 OR action=3 )
							AND tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_log').'
							AND recuid='.intval($uid),
						'',
						'uid DESC',
						$this->maxSteps
					);
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{

					if ($row['uid'] < $this->lastSyslogId && $this->lastSyslogId)	{
						continue;
					}
					$hisDat = array();

					switch ($row['action'])	{
						case 1:	// Insert
							$hisDat['action'] = 'insert';
							break;
						case 3:	// Delete
							$hisDat['action'] = 'delete';
							break;
					}
					$hisDat['tstamp']=$row['tstamp'];
					$hisDat['user']=$row['userid'];
					$hisDat['tablename'] = $table;
					$hisDat['recuid'] = $uid;
					$changeLog[$row['uid']] = $hisDat;
				}
			}
			return $changeLog;
		}
		return 0;	// error fallback
	}


	/*******************************
	 *
	 * Various helper functions
	 *
	 *******************************/

	/**
	 * generates the title and puts the record title behind
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function generateTitle($table, $uid)	{
		global $TCA;

		$out = $table.':'.$uid;
		if ($labelField = $TCA[$table]['ctrl']['label'])	{
			$record = t3lib_BEfunc::getRecordRaw($table, 'uid='.intval($uid));
			$out .= ' ('.t3lib_BEfunc::getRecordTitle($table, $record, TRUE).')';
		}
		return $out;
	}
	/**
	 * creates a link for the rollback
	 *
	 * @param	sting		parameter which is set to rollbackFields
	 * @param	string		optional, alternative label and title tag of image
	 * @param	integer		optional, type of rollback: 0 - ALL; 1 - element; 2 - field
	 * @return	string		HTML output
	 */
	function createRollbackLink($key, $alt='', $type=0)	{
		 global $LANG;

		 return $this->linkPage('<img '.t3lib_iconWorks::skinImg('','gfx/revert_'.$type.'.gif','width="33" height="33"').' alt="'.$alt.'" title="'.$alt.'" align="middle" />',array('rollbackFields'=>$key));
	 }

	/**
	 * Creates a link to the same page.
	 *
	 * @param	string		String to wrap in <a> tags (must be htmlspecialchars()'ed prior to calling function)
	 * @param	array		Array of key/value pairs to override the default values with.
	 * @param	string		Possible anchor value.
	 * @param	string		Possible title.
	 * @return	string		Link.
	 * @access private
	 */
	function linkPage($str,$inparams=array(),$anchor='',$title='')	{

			// Setting default values based on GET parameters:
		$params['element']=$this->element;
		$params['returnUrl']=$this->returnUrl;
		$params['diff']=$this->lastSyslogId;
			// Mergin overriding values:
		$params = array_merge($params,$inparams);

			// Make the link:
		$Ahref = 'show_rechis.php?'.t3lib_div::implodeArrayForUrl('',$params).($anchor?'#'.$anchor:'');
		$link = '<a href="'.htmlspecialchars($Ahref).'"'.($title?' title="'.$title.'"':'').'>'.$str.'</a>';

			// Return link:
		return $link;
	}

	/**
	 * Will traverse the field names in $dataArray and look in $TCA if the fields are of types which cannot be handled by the sys_history (that is currently group types with internal_type set to "file")
	 *
	 * @param	string		Table name
	 * @param	array		The data array
	 * @return	array		The modified data array
	 * @access private
	 */
	function removeFilefields($table,$dataArray)	{
		global $TCA;

		if ($TCA[$table])	{
			t3lib_div::loadTCA($table);

			foreach($TCA[$table]['columns'] as $field => $config)	{
				if ($config['config']['type']=='group' && $config['config']['internal_type']=='file')	{
					unset($dataArray[$field]);
				}
			}
		}
		return $dataArray;
	}

	/**
	 * Convert input element reference to workspace version if any.
	 *
	 * @param	string		table of input element
	 * @param	integer		UID of record
	 * @return	integer		converted UID of record
	 */
	function resolveElement($table,$uid)	{
		if (isset($GLOBALS['TCA'][$table]))	{
			if ($workspaceVersion = t3lib_BEfunc::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $table, $uid, 'uid'))	{
				$uid = $workspaceVersion['uid'];
			}
		}
		return $uid;
	}

	/**
	 * resolve sh_uid (used from log)
	 *
	 * @return	[type]		...
	 */
	function resolveShUid()	{
		if (t3lib_div::_GP('sh_uid'))	{
			$sh_uid = t3lib_div::_GP('sh_uid');
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','sys_history', 'uid='.intval($sh_uid));
			$record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$this->element = $record['tablename'].':'.$record['recuid'];
			$this->lastSyslogId = $record['sys_log_uid']-1;
		}
	}
}
?>