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
 * Class for the record history display script (show_rechis.php)
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
 *   83: class recordHistory
 *  106:     function recordHistory()
 *  124:     function main()
 *  155:     function displaySysHistoryEntry($sh_uid)
 *  219:     function revertToPreviousValues($element,$field)
 *  285:     function saveState($element,$sumUp)
 *  339:     function displayHistory($element)
 *
 *              SECTION: Various helper functions
 *  540:     function nextHisUid($element,$hisUid)
 *  586:     function compareChangesWithCurrent($element,$changeRec)
 *  631:     function readFieldTypes($table,$id)
 *  662:     function cmp($changeStatus,$oldRecord)
 *  685:     function removeFilefields($table,$dataArray)
 *  708:     function renderEntry($entry,$table)
 *  769:     function listHeader()
 *  813:     function linkPage($str,$inparams=array(),$anchor='')
 *  841:     function getChangesSinceRecord($element,$hisUid=0,$hisUid_Stop=0)
 *
 * TOTAL FUNCTIONS: 15
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */












/**
 * Class for the record history display script (show_rechis.php)
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class recordHistory {

		// External, static:
	var $maxSteps=20;		// Maximum number of sys_history steps to show.

		// Internal, dynamic:
	var $listType = 0;		// This value determines the kind of list build. The variable is used as a parameter from some functions.

		// Internal, static. GPvars:
	var $sh_uid;			// sh_uid is the id-number of the sys_history log item to SHOW
	var $element;			// Element reference, syntax [tablename]:[uid]
	var $saveState;			// Saving states: Points to a sys_history UID which should be saved.
	var $returnUrl;			// Return URL - kept in links, used to link back to calling module.
	var $revert;			// String identifying mode of reverting: Either all fields or only a single field to revert. See function revertToPreviousValues()
	var $sumUp;			// Generally used as a pointer to a sys_history uid as a state.
	var $doReturn;			// If set, function revertToPreviousValues() will perform a redirect to returnUrl


	/**
	 * Constructor for the class
	 *
	 * @return	void
	 */
	function recordHistory()	{

			// GPvars:
		$this->sh_uid = t3lib_div::_GP('sh_uid');
		$this->element = t3lib_div::_GP('element');
		$this->saveState = t3lib_div::_GP('saveState');
		$this->returnUrl = t3lib_div::_GP('returnUrl');
		$this->revert = t3lib_div::_GP('revert');
		$this->sumUp = t3lib_div::_GP('sumUp');
		$this->doReturn = t3lib_div::_GP('doReturn');
	}

	/**
	 * Main function for the listing of history.
	 * It detects incoming variables like element reference, history element uid etc. and renders the correct screen.
	 *
	 * @return	void
	 */
	function main()	{

		$content='';

			// If link from sys log:
			// sh_uid is the id-number of the sys_history log item
		if ($this->sh_uid)	{
			$content.=$this->displaySysHistoryEntry($this->sh_uid);
		}

			// If link to element:
		if ($this->element)	{
			if ($this->revert && $this->sumUp)	{
				$content.=$this->revertToPreviousValues($this->element,$this->revert);
			}
			if ($this->saveState)	{
				$content.=$this->saveState($this->element,$this->saveState);
			}
			$content.=$this->displayHistory($this->element);
		}

			// Return content variable:
		return $content;
	}

	/**
	 * Displays a specific entry from the sys_history table
	 *
	 * @param	integer		UID of sys_history table entry
	 * @return	string		HTML content
	 */
	function displaySysHistoryEntry($sh_uid)	{
		global $SOBE, $LANG, $TCA;

			// Select the entry from the table:
 		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_history', 'uid='.intval($sh_uid));
		$newRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

			// If an entry was found:
		if (is_array($newRow))	{

				// Init:
			$this->listType=0;
			$lines=array();

				// Create header:
			$recIdentString = $LANG->sL($TCA[$newRow['tablename']]['ctrl']['title']).'/'.$newRow['recuid'];
			$recIdentString = $this->linkPage(htmlspecialchars($recIdentString),array('sh_uid'=>'','element'=>$newRow['tablename'].':'.$newRow['recuid']),'uid_'.$sh_uid);
			$theTime = t3lib_BEfunc::datetime($newRow['tstamp']).', '.t3lib_BEfunc::calcAge(time()-$newRow['tstamp'],$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears'));
			$lines[]='
				<tr class="bgColor5">
					<td colspan="4">
						<strong>'.$LANG->getLL('tableUid',1).':</strong> '.$recIdentString.'<br />
						<strong>'.$LANG->getLL('time',1).':</strong> '.htmlspecialchars($theTime).'<br />
					</td>
				</tr>';

				// Add header to accumulation:
			$lines[]=$this->listHeader();

				// Get the entry data and add it:
			$historyData = unserialize($newRow['history_data']);
			$lines = array_merge($lines,$this->renderEntry($historyData,$newRow['tablename']));

				// Combine all content into a table for layout:
			$theCode='

				<!--
					History for item:
				-->
				<table border="0" cellpadding="2" cellspacing="2" id="typo3-history-item">
					'.implode('',$lines).'
				</table>';

			$theCode.='
				<br /><img'.t3lib_iconWorks::skinImg('','gfx/icon_note.gif','width="18" height="16"').' align="top" alt="" />'.$LANG->getLL('differenceMsg').'<br /><br />';

				// Add CSH:
			$theCode.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'history_entry', $GLOBALS['BACK_PATH'],'');

				// Create the module section:
			$content.=$SOBE->doc->section($LANG->getLL('changes'),$theCode,0,1);
		}

			// Return content:
		return $content;
	}

	/**
	 * Return to previous values for element
	 *
	 * @param	string		Element reference, syntax "[table]:[uid]"
	 * @param	string		Tells which field to restore. A single field (eg named "myField") is defined as "field:myField" while ALL fields is indicated by the string "ALL_FIELDS"
	 * @return	void		The function writes through tceMain and ends with a header-location, if instructed to.
	 */
	function revertToPreviousValues($element,$field)	{
		$sumUp = $this->sumUp;	// sys_history uid from which to get previous values

		$elParts = explode(':',$element);
		$redirect = intval($this->doReturn);
		if ($sumUp==-1)	{	// Undo/Redo
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid',
						'sys_history',
						'sys_history.tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($elParts[0], 'sys_history').'
							AND sys_history.recuid='.intval($elParts[1]),
						'',
						'uid DESC',
						'1'
					);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$sumUp=$row['uid'];
			}
			$redirect = 1;
		}
		if ($sumUp!=-1)	{
			$changeRec=$this->compareChangesWithCurrent($element,$this->getChangesSinceRecord($element,$sumUp));

			$data =array();
			if (t3lib_BEfunc::getRecord($elParts[0],$elParts[1]))	{

					// Fields field(s) to restore:
				if ($field=='ALL_FIELDS')	{
					$data=$changeRec['oldRecord'];
				} elseif(substr($field,0,6)=='field:') {
					$data[substr($field,6)]=$changeRec['oldRecord'][substr($field,6)];
				}
					// Removing fields:
				$data = $this->removeFilefields($elParts[0],$data);

					// If there are fields to write:
				if (count($data))	{

						// Setting data right:
					$inData=array();
					$inData[$elParts[0]][$elParts[1]]=$data;

						// Writes the data:
					$tce = t3lib_div::makeInstance('t3lib_TCEmain');
					$tce->stripslashes_values=0;
					$tce->debug=0;
					$tce->dontProcessTransformations=1;
					$tce->start($inData,array());
					$tce->process_datamap();
				}
			}
		}

		if ($redirect)	{
			Header ('Location: '.t3lib_div::locationHeaderUrl($this->returnUrl));
			exit;
		}
	}

	/**
	 * Will save state uid $sumUp of element
	 *
	 * @param	string		Element reference, syntax "[table]:[uid]"
	 * @param	integer		sys_history uid from which to get previous values
	 * @return	void
	 */
	function saveState($element,$sumUp)	{
		$elParts = explode(':',$element);

			// Find the changes since $sumUp sys_history uid
		$changeRec = $this->getChangesSinceRecord($element,$sumUp);

			// Select most recent sys_history record for the element:
		$lastestData = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'history_data',
					'sys_history',
					'sys_history.tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($elParts[0], 'sys_history').'
						AND sys_history.recuid='.intval($elParts[1]),
					'',
					'uid DESC',
					'1'
				);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$lastestData = unserialize($row['history_data']);
		}

			// Create forged history data from the most recent state and the previous state to save:
		$historyRecords=array();
		$historyRecords['oldRecord'] = $changeRec['changes'];
		$historyRecords['newRecord'] = array();

		reset($historyRecords['oldRecord']);
		while(list($kk)=each($historyRecords['oldRecord']))	{
			$historyRecords['newRecord'][$kk]=$lastestData['newRecord'][$kk];
		}

			// Update log:
		$updateID = $GLOBALS['BE_USER']->writelog(3,1,0,1,'Saved state','');

			// Create query for inserting into sys_history table:
		$fields_values = array(
			'history_data' => serialize($historyRecords),
			'fieldlist' => implode(',',array_keys($historyRecords['oldRecord'])),
			'tstamp' => time(),
			'tablename' => $elParts[0],
			'recuid' => $elParts[1],
			'sys_log_uid' => $updateID,
			'snapshot' => 1
		);
			// Save state by executing this query:
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_history', $fields_values);
	}

	/**
	 * Displays the history states of an element
	 *
	 * @param	string		Element reference, syntax "[table]:[uid]"
	 * @return	string		HTML for list, wrapped in a table.
	 */
	function displayHistory($element)	{
		global $SOBE, $LANG, $TCA;

			// Initialize:
		$elParts = explode(':',$element);
		$table = $elParts[0];

			// If table is found in $TCA:
		if ($TCA[$table])	{

				// Counting number of states:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'COUNT(*)',
						'sys_history,sys_log',
						'sys_history.sys_log_uid=sys_log.uid
							AND sys_history.tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_history').'
							AND sys_history.recuid='.intval($elParts[1])
					);
			list($Rcount) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

				// Selecting the $this->maxSteps most recent states:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'sys_history.*,sys_log.userid',
						'sys_history,sys_log',
						'sys_history.sys_log_uid=sys_log.uid
							AND sys_history.tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_history').'
							AND sys_history.recuid='.intval($elParts[1]),
						'',
						'sys_log.uid',
						t3lib_div::intInRange($Rcount-$this->maxSteps,0).','.$this->maxSteps
					);

				// Traversing the result, building up changesArray / changeLog:
			$changesArray=array();
			$changeLog=array();
			while ($newRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$hisDat = unserialize($newRow['history_data']);
				if (is_array($hisDat['newRecord']) && is_array($hisDat['oldRecord']))	{
						// If intermedia changes:
					$intermediaChanges = $this->cmp($changesArray,$hisDat['oldRecord']);
					if (count($intermediaChanges) && !$newRow['snapshot'])	{
						$changeLog[]=$intermediaChanges;
					}

						// Add hisDat to the changeLog
					$hisDat['uid']=$newRow['uid'];
					$hisDat['tstamp']=$newRow['tstamp'];
					$hisDat['user']=$newRow['userid'];
					$hisDat['snapshot']=$newRow['snapshot'];
					$changeLog[]=$hisDat;

						// Update change array
						// This is used to detect if any intermedia changes has been made.
					$changesArray = array_merge($changesArray,$hisDat['newRecord']);
				} else {
					debug('ERROR: [displayHistory]');
				}
			}


			$lines=array();
			$darkerBgColor_interM = '#cccccc';
			if ($this->sumUp)	{	// Show details for a single point in the list:

					// Initialize:
				$changeLog=array();		// array is reset here because we want to show only one item (and therefore we will build it all over again...)
				$changeLog[]=$this->compareChangesWithCurrent($element,$this->getChangesSinceRecord($element,$this->sumUp));
				$this->listType=2;
				$lines[]=$this->listHeader();
				$be_users = t3lib_BEfunc::getUserNames();

					// Get the previous/next uids:
				list($prevHisUid,$nextHisUid) = $this->nextHisUid($element,$this->sumUp);

					// Create the set of navigation links:
				$linkPack =
					($prevHisUid ? $this->linkPage('<img'.t3lib_iconWorks::skinImg('','gfx/pilup.gif','width="14" height="14"').' title="'.$LANG->getLL('prev',1).'" alt="" />', array('sumUp'=>$prevHisUid)) : '').	// previous
					($nextHisUid ? $this->linkPage('<img'.t3lib_iconWorks::skinImg('','gfx/pildown.gif','width="14" height="14"').' title="'.$LANG->getLL('next',1).'" alt="" />', array('sumUp'=>$nextHisUid)) : '').	// next
					'<br />'.$this->linkPage('<img'.t3lib_iconWorks::skinImg('','gfx/history2.gif','width="13" height="12"').' title="'.$LANG->getLL('historyList',1).'" alt="" />', array('sumUp'=>''), 'uid_'.$this->sumUp).	// back to list
					$this->linkPage('<img'.t3lib_iconWorks::skinImg('','gfx/savesnapshot.gif','width="17" height="12"').' title="'.$LANG->getLL('saveState',1).'" alt="" />', array('saveState'=>$this->sumUp,'sumUp'=>''), 'latest');	// save state

					// Traverse changelog array:
				foreach($changeLog as $entry)	{

						// Set user-names:
					if (!is_array($entry['userList']))	$entry['userList']=array();
					foreach($entry['userList'] as $uLk => $uV)	{
						$entry['userList'][$uLk]=$be_users[$uV]['username'];
					}

						// Add the header:
					$theTime = t3lib_BEfunc::datetime($entry['tstamp']).', '.t3lib_BEfunc::calcAge(time()-$entry['tstamp'],$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears'));
					$lines[]='
						<tr class="bgColor4-20">
							<td valign="top">'.$linkPack.'</td>
							<td colspan="4"><b>'.$LANG->getLL('time',1).':</b> '.htmlspecialchars($theTime).' &nbsp; - &nbsp; <b>'.$LANG->getLL('changeCount',1).':</b> '.$entry['counter'].'<br />
								<b>'.$LANG->getLL('users',1).':</b> '.implode(', ',$entry['userList']).'
							</td>
						</tr>';

						// Add content:
					if (isset($entry['oldRecord']) && isset($entry['newRecord']))	{	// If there ARE differences to show, then add lines for each changed field:
						$lines = array_merge($lines,$this->renderEntry($entry,$table));
					} else {	// Otherwise, if no changes - show a message about that!
						$lines[]='
							<tr class="bgColor4">
								<td colspan="5" align="center"><br /><b>'.$LANG->getLL('similar',1).'</b><br /><br /></td>
							</tr>';
					}
				}
			} else {	// Show the full change Log:

					// Initialize:
				$this->listType=1;
				$be_users = t3lib_BEfunc::getUserNames();
				$lines[]=$this->listHeader();

					// Traverse changelog array:
				foreach($changeLog as $c => $entry)	{

						// Add spacer line:
					$lines[]='
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>';

						// Anchor to latest entry:
					$lastAnchor = ($c+1==count($changeLog)?'<a name="latest"></a>':'');

						// Render state header:
					if ($entry['uid'])	{	// This state was made by the backend:
						$theTime = $this->linkPage(t3lib_BEfunc::datetime($entry['tstamp']),array('sh_uid'=>$entry['uid'],'element'=>''));
						$theAge = ', '.t3lib_BEfunc::calcAge(time()-$entry['tstamp'],$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears'));
						$bgColorClass = $entry['snapshot'] ? 'bgColor2' : 'bgColor4-20';
						$lines[]='
							<tr class="'.$bgColorClass.'">
								<td colspan="2">'.
									$lastAnchor.
									'<a name="uid_'.$entry['uid'].'"></a>'.
									($entry['snapshot'] ? '<img'.t3lib_iconWorks::skinImg('','gfx/snapshot.gif','width="12" height="12"').' alt="" />':'').
									'<b>'.$LANG->getLL('time',1).':</b> '.$theTime.htmlspecialchars($theAge).' &nbsp; - &nbsp; <b>'.$LANG->getLL('user',1).':</b> '.$be_users[$entry['user']]['username'].
								'</td>
								<td>'.
									$this->linkPage('<img'.t3lib_iconWorks::skinImg('','gfx/history.gif','width="13" height="12"').' title="'.$LANG->getLL('revertAllFields',1).'" alt="" />', array('revert'=>'ALL_FIELDS','sumUp'=>$entry['uid'],'doReturn'=>1)).
									$this->linkPage('<img'.t3lib_iconWorks::skinImg('','gfx/history_details.gif','width="12" height="12"').' title="'.$LANG->getLL('sumUpChanges',1).'" alt="" />', array('sumUp'=>$entry['uid'])).
								'</td>
							</tr>';
					} else {	// This state must have been some external change:
						$lines[]='
							<tr bgcolor="'.$darkerBgColor_interM.'">
								<td colspan="3"><strong>'.$LANG->getLL('externalChange',1).'</strong></td>
							</tr>';
					}
						// Merge state header with all entries in the state:
					$lines = array_merge($lines,$this->renderEntry($entry,$table));
				}
			}

				// Finally, put it all together:
			$theCode='

				<!--
					Item history (either list or single):
				-->
				<table border="0" cellpadding="2" cellspacing="2" id="typo3-history">
					'.implode('',$lines).'
				</table>';

				// Add message about the difference view.
			$theCode.= '<br /><img'.t3lib_iconWorks::skinImg('','gfx/icon_note.gif','width="18" height="16"').' align="top" alt="" />'.$LANG->getLL('differenceMsg').'<br /><br />';

				// Add CSH:
			$theCode.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'history_'.($this->sumUp ? 'sum' : 'log'), $GLOBALS['BACK_PATH'],'');

				// Add the whole content as a module section:
			return $SOBE->doc->section($LANG->getLL('changes'),$theCode,0,1);
		}
	}









	/*******************************
	 *
	 * Various helper functions
	 *
	 *******************************/

	/**
	 * Based on the uid of a sys_history record (a state) this method will find the uids of the previous and next state (if any)
	 *
	 * @param	string		Element reference, syntax "[table]:[uid]"
	 * @param	integer		Current state uid
	 * @return	array		Array with previous and next uid as key 0 / 1
	 * @access private
	 */
	function nextHisUid($element,$hisUid)	{
		$elParts = explode(':',$element);

			// Prev:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid',
					'sys_history',
					'tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($elParts[0], 'sys_history').'
						AND recuid='.intval($elParts[1]).'
						AND uid<'.intval($hisUid),
					'',
					'uid DESC',
					'1'
				);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$prevUid = $row['uid'];
		}

			// Next:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid',
					'sys_history',
					'tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($elParts[0], 'sys_history').'
						AND recuid='.intval($elParts[1]).'
						AND uid>'.intval($hisUid),
					'',
					'uid',
					'1'
				);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$nextUid = $row['uid'];
		}

			// Return next and previous ids:
		return array($prevUid,$nextUid);
	}

	/**
	 * This compares a certain sys_history state (given by the $changeRec array) with the current values of the element refered to by $element.
	 *
	 * @param	string		Element reference, syntax "[table]:[uid]"
	 * @param	array		Array with the state information from a certain state. This kind of array is produced by getChangesSinceRecord()
	 * @return	array		Array with the changes registered in.
	 * @access private
	 * @see getChangesSinceRecord()
	 */
	function compareChangesWithCurrent($element,$changeRec)	{
		global $TCA;

			// Initialize:
		$sumChangesArray=array();
		$elParts = explode(':',$element);
		$newChangeRec=array();

			// If tablename is found in $TCA:
		if ($TCA[$elParts[0]])	{

				// Select current record content of element:
			$currentRecord = t3lib_BEfunc::getRecord($elParts[0],$elParts[1]);

				// If that is found and the "changes" entry of the $changeRec is an array, then proceed:
			if (is_array($currentRecord) && is_array($changeRec['changes']))	{

					// For each entry in "changes" we compare the field content with the current and if there is a difference, it is tracked in the array $newChangeRec
				foreach($changeRec['changes'] as $fN => $fV)	{
					if (strcmp($fV,$currentRecord[$fN]))	{
						$newChangeRec['oldRecord'][$fN]=$fV;
						$newChangeRec['newRecord'][$fN]=$currentRecord[$fN];
					}
				}
					// Finally, setting some general information fields:
				$newChangeRec['tstamp']=min($changeRec['tstamp']);
				$newChangeRec['counter']=$changeRec['counter'];
				$newChangeRec['userList']=array_unique($changeRec['userList']);
			} else {
				return false;	// No arrays, possibly no record
			}
		}

			// Returns the array of changes detected:
		return $newChangeRec;
	}

	/**
	 * Returns the record of $table/$id along with the sql field types for each field
	 *
	 * @param	string		The table name
	 * @param	integer		The uid of the record
	 * @return	array		An array with two num keys; in 0 is the current record, in 1 is the field types for each field.
	 * @access private
	 */
	function readFieldTypes($table,$id)	{

			// Select record:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid='.intval($id));

			// Fetch the types of the fields.
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
			$currentRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$c=0;
			$cRecTypes=array();
			foreach($currentRecord as $col => $val)	{
				//$cRecTypes[$col] = $GLOBALS['TYPO3_DB']->sql_field_type($table,$col);
// DBAL
				$cRecTypes[$col] = $GLOBALS['TYPO3_DB']->sql_field_type($res,$c);
				$c++;
			}
		}

		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return array($currentRecord,$cRecTypes);
	}

	/**
	 * Compares the old record with the changed fields.
	 *
	 * @param	array		Record with field/value pairs (what has changed)
	 * @param	array		Record with field/value pairs
	 * @return	array		Comparison result.
	 * @access private
	 */
	function cmp($changeStatus,$oldRecord)	{

			// Initialize:
		$changes=array();

			// Traverse $oldRecord
		foreach($oldRecord as $fN => $fV)	{
			if (isset($changeStatus[$fN]) && strcmp($fV,$changeStatus[$fN]))	{
				$changes['oldRecord'][$fN]=$changeStatus[$fN];
				$changes['newRecord'][$fN]=$fV;
			}
		}
		return $changes;
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
	 * Renders HTML table-rows with the comparison information of an sys_history entry record
	 *
	 * @param	array		sys_history entry record.
	 * @param	string		The table name
	 * @return	array		HTML table rows in an array
	 * @access private
	 */
	function renderEntry($entry,$table)	{
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

						// Depending on list type, we make the row:
					switch($this->listType)	{
						case 1:
							$lines[]='
								<tr class="bgColor4">
									<td><em>'.$LANG->sl(t3lib_BEfunc::getItemLabel($table,$fN),1).'</em></td>
									<td>'.nl2br($diffres).'</td>
									<td>&nbsp;</td>
								</tr>';
						break;
						case 2:
							$lines[]='
								<tr class="bgColor4">
									<td><em>'.$LANG->sl(t3lib_BEfunc::getItemLabel($table,$fN)).'</em></td>
									<td>'.htmlspecialchars(t3lib_BEfunc::getProcessedValue($table,$fN,$entry['oldRecord'][$fN])).'</td>
									<td>'.$this->linkPage('<img'.t3lib_iconWorks::skinImg('','gfx/history.gif','width="13" height="12"').' title="'.$LANG->getLL('revertField',1).'" alt="" />', array('revert'=>'field:'.$fN)).'</td>
									<td>'.htmlspecialchars(t3lib_BEfunc::getProcessedValue($table,$fN,$entry['newRecord'][$fN])).'</td>
									<td>'.nl2br($diffres).'</td>
								</tr>';
						break;
						default:
							$lines[]='
								<tr class="bgColor4">
									<td><em>'.$LANG->sl(t3lib_BEfunc::getItemLabel($table,$fN)).'</em></td>
									<td>'.htmlspecialchars(t3lib_BEfunc::getProcessedValue($table,$fN,$entry['oldRecord'][$fN])).'</td>
									<td>'.htmlspecialchars(t3lib_BEfunc::getProcessedValue($table,$fN,$entry['newRecord'][$fN])).'</td>
									<td>'.nl2br($diffres).'</td>
								</tr>';
						break;
					}
				}
			}
		}
		return $lines;
	}

	/**
	 * Creates a header row based on the value of $this->listType
	 *
	 * @return	string		HTML table header row
	 * @access private
	 */
	function listHeader()	{
		global $SOBE, $LANG;

		switch($this->listType)	{
			case 1:
				$out='
					<tr class="bgColor5 c-head">
						<td>'.$LANG->getLL('fieldName',1).':</td>
						<td>'.$LANG->getLL('difference',1).':</td>
						<td>&nbsp;</td>
					</tr>';
			break;
			case 2:
				$out='
					<tr class="bgColor5 c-head">
						<td>'.$LANG->getLL('fieldName',1).':</td>
						<td>'.$LANG->getLL('oldValue',1).':</td>
						<td>'.$this->linkPage('<img'.t3lib_iconWorks::skinImg('','gfx/history.gif','width="13" height="12"').' title="'.$LANG->getLL('revertAllFields',1).'" alt="" />', array('revert'=>'ALL_FIELDS')).'</td>
						<td>'.$LANG->getLL('currentValue',1).':</td>
						<td>'.$LANG->getLL('difference',1).':</td>
					</tr>';
			break;
			default:
				$out='
					<tr class="bgColor5 c-head">
						<td>'.$LANG->getLL('fieldName',1).':</td>
						<td>'.$LANG->getLL('oldValue',1).':</td>
						<td>'.$LANG->getLL('newValue',1).':</td>
						<td>'.$LANG->getLL('difference',1).':</td>
					</tr>';
			break;
		}
		return $out;
	}

	/**
	 * Creates a link to the same page.
	 *
	 * @param	string		String to wrap in <a> tags (must be htmlspecialchars()'ed prior to calling function)
	 * @param	array		Array of key/value pairs to override the default values with.
	 * @param	string		Possible anchor value.
	 * @return	string		Link.
	 * @access private
	 */
	function linkPage($str,$inparams=array(),$anchor='')	{

			// Setting default values based on GET parameters:
		$params['sh_uid']=$this->sh_uid;
		$params['sumUp']=$this->sumUp;
		$params['element']=$this->element;
		$params['returnUrl']=$this->returnUrl;

			// Mergin overriding values:
		$params = array_merge($params,$inparams);

			// Make the link:
		$Ahref = 'show_rechis.php?'.t3lib_div::implodeArrayForUrl('',$params).($anchor?'#'.$anchor:'');
		$link = '<a href="'.htmlspecialchars($Ahref).'">'.$str.'</a>';

			// Return link:
		return $link;
	}

	/**
	 * This creates an array with the sum of differences between two points in the sys_history
	 *
	 * @param	string		Element reference, syntax "[table]:[uid]"
	 * @param	integer		sys_history uid from which to start the selection process
	 * @param	integer		optional sys_history uid at which to stop the selection (thus applying an upper limit)
	 * @return	array		Array with difference information
	 * @access private
	 */
	function getChangesSinceRecord($element,$hisUid=0,$hisUid_Stop=0)	{
		global $TCA;

			// Init:
		$sumChangesArray=array();
		$sumChangesArray['changes']=array();
		$elParts = explode(':',$element);

			// If there is a table by the requested name:
		if ($TCA[$elParts[0]])	{
			$times = array();

				// Create query for selecting sys_history records in time interval:
			$addWhere = ' AND sys_history.uid>='.$hisUid;
			if ($hisUid_Stop)	$addWhere.= ' AND sys_history.uid<='.$hisUid_Stop;
				// Make query:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'sys_history.*,sys_log.userid',
							'sys_history,sys_log',
							'sys_history.sys_log_uid=sys_log.uid
								AND sys_history.tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($elParts[0], 'sys_history').'
								AND sys_history.recuid='.intval($elParts[1]).$addWhere,
							'',
							'uid DESC'
						);

				// Travese results:
			while ($newRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$hisDat = unserialize($newRow['history_data']);
				if (is_array($hisDat['newRecord']) && is_array($hisDat['oldRecord']))	{
					$sumChangesArray['changes']=array_merge($sumChangesArray['changes'],$hisDat['oldRecord']);
					$sumChangesArray['counter']++;
					$sumChangesArray['tstamp'][]=$newRow['tstamp'];
					$sumChangesArray['userList'][]=$newRow['userid'];
				} else {
					debug('ERROR!');
				}

			}
		}
		return $sumChangesArray;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.show_rechis.inc'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.show_rechis.inc']);
}
?>