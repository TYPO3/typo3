<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Include file extending recordList which extended t3lib_recordList
 * Used specifically for the Web>List module (db_list.php)
 *
 * $Id$
 * Revised for TYPO3 3.6 December/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   89: class localRecordList extends recordList
 *  121:     function writeTop($row,$path)
 *  295:     function getTable($table,$id,$rowlist)
 *  476:     function renderListRow($table,$row,$cc,$titleCol,$thumbsCol)
 *  539:     function renderListHeader($table,$currentIdList)
 *
 *              SECTION: Rendering of various elements
 *  678:     function makeControl($table,$row)
 *  852:     function makeClip($table,$row)
 *  921:     function fieldSelectBox($table,$formFields=1)
 *
 *              SECTION: Helper functions
 * 1000:     function linkClipboardHeaderIcon($string,$table,$cmd,$warning='')
 * 1011:     function clipNumPane()
 * 1025:     function addSortLink($code,$field,$table)
 * 1050:     function recPath($pid)
 * 1063:     function showNewRecLink($table)
 * 1073:     function makeReturnUrl()
 *
 *              SECTION: CSV related functions
 * 1097:     function initCSV()
 * 1119:     function addToCSV($row)
 * 1141:     function setCsvRow($csvRow)
 * 1152:     function outputCSV($prefix)
 *
 * TOTAL FUNCTIONS: 17
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */











/**
 * Class for rendering of Web>List module
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class localRecordList extends recordList {

		// External:
	var $alternateBgColors=FALSE;			// If true, table rows in the list will alternate in background colors (and have background colors at all!)
	var $allowedNewTables=array();			// Used to indicate which tables (values in the array) that can have a create-new-record link. If the array is empty, all tables are allowed.
	var $newWizards=FALSE;					// If true, the control panel will contain links to the create-new wizards for pages and tt_content elements (normally, the link goes to just creating a new element without the wizards!).

	var $dontShowClipControlPanels=FALSE;	// If true, will disable the rendering of clipboard + control panels.
	var $showClipboard=FALSE;				// If true, will show the clipboard in the field list.
 	var $noControlPanels = FALSE;			// If true, will DISABLE all control panels in lists. (Takes precedence)




		// Internal:
	var $pageRow=array();					// Set to the page record (see writeTop())

	var $csvLines=array();					// Used to accumulate CSV lines in for CSV export.
	var $csvOutput=FALSE;					// If set, the listing is returned as CSV instead.

	var $clipObj;							// Clipboard object
	var $CBnames=array();					// Tracking names of elements (for clipboard use)
	var $duplicateStack=array();			// Used to track which elements has duplicates and how many


	/**
	 * Writes the top of the full listing
	 *
	 * @param	array		Current page record
	 * @return	void		(Adds content to internal variable, $this->HTMLcode)
	 */
	function writeTop($row)	{
		global $LANG;

			// Makes the code for the pageicon in the top
		$this->pageRow=$row;
		$this->counter++;
		$alttext = t3lib_BEfunc::getRecordIconAltText($row,'pages');
		$iconImg = t3lib_iconWorks::getIconImage('pages',$row,$this->backPath,'title="'.htmlspecialchars($alttext).'"');
		$titleCol='test';	// pseudo title column name
		$this->fieldArray = Array($titleCol,'up');		// Setting the fields to display in the list (this is of course "pseudo fields" since this is the top!)


			// Filling in the pseudo data array:
		$theData = Array();
		$theData[$titleCol] = $this->widthGif;

			// Get users permissions for this row:
		$localCalcPerms = $GLOBALS['BE_USER']->calcPerms($row);

		$theData['up']=array();

			// Initialize control panel for currect page ($this->id):
			// Some of the controls are added only if $this->id is set - since they make sense only on a real page, not root level.
		$theCtrlPanel =array();

			// "View page" icon is added:
		$theCtrlPanel[]='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($this->id,'',t3lib_BEfunc::BEgetRootLine($this->id))).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom.gif','width="12" height="12"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.showPage',1).'" alt="" />'.
						'</a>';

			// If edit permissions are set (see class.t3lib_userauthgroup.php)
		if ($localCalcPerms&2)	{

				// Adding "Edit page" icon:
			if ($this->id)	{
				$params='&edit[pages]['.$row['uid'].']=edit';
				$theCtrlPanel[]='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,'',-1)).'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2.gif','width="11" height="12"').' title="'.$LANG->getLL('editPage',1).'" alt="" />'.
								'</a>';
			}

				// Adding "New record" icon:
			if (!$GLOBALS['SOBE']->modTSconfig['properties']['noCreateRecordsLink']) 	{
				$theCtrlPanel[]='<a href="#" onclick="'.htmlspecialchars('return jumpExt(\'db_new.php?id='.$this->id.'\');').'">'.
								'<img'.t3lib_iconWorks::skinImg('','gfx/new_el.gif','width="11" height="12"').' title="'.$LANG->getLL('newRecordGeneral',1).'" alt="" />'.
								'</a>';
			}

				// Adding "Hide/Unhide" icon:
			if ($this->id)	{
				if ($row['hidden'])	{
					$params='&data[pages]['.$row['uid'].'][hidden]=0';
					$theCtrlPanel[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params,-1).'\');').'">'.
									'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_unhide.gif','width="11" height="10"').' title="'.$LANG->getLL('unHidePage',1).'" alt="" />'.
									'</a>';
				} else {
					$params='&data[pages]['.$row['uid'].'][hidden]=1';
					$theCtrlPanel[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params,-1).'\');').'">'.
									'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_hide.gif','width="11" height="10"').' title="'.$LANG->getLL('hidePage',1).'" alt="" />'.
									'</a>';
				}
			}

				// Adding "move page" button:
			if ($this->id)	{
				$theCtrlPanel[]='<a href="#" onclick="'.htmlspecialchars('return jumpExt(\'move_el.php?table=pages&uid='.$row['uid'].'\');').'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/move_'.($table=='tt_content'?'record':'page').'.gif','width="11" height="12"').' title="'.$LANG->getLL('move_page',1).'" alt="" />'.
								'</a>';
			}
		}

			// "Paste into page" link:
		if (($localCalcPerms&8) || ($localCalcPerms&16))	{
			$elFromTable = $this->clipObj->elFromTable('');
			if (count($elFromTable))	{
				$theCtrlPanel[]='<a href="'.htmlspecialchars($this->clipObj->pasteUrl('',$this->id)).'" onclick="'.htmlspecialchars('return '.$this->clipObj->confirmMsg('pages',$this->pageRow,'into',$elFromTable)).'">'.
								'<img'.t3lib_iconWorks::skinImg('','gfx/clip_pasteafter.gif','width="12" height="12"').' title="'.$LANG->getLL('clip_paste',1).'" alt="" />'.
								'</a>';
			}
		}

			// Finally, compile all elements of the control panel into table cells:
		if (count($theCtrlPanel))	{
			$theData['up'][]='

				<!--
					Control panel for page
				-->
				<table border="0" cellpadding="0" cellspacing="0" class="bgColor4" id="typo3-dblist-ctrltop">
					<tr>
						<td>'.implode('</td>
						<td>',$theCtrlPanel).'</td>
					</tr>
				</table>';
		}


			// Add "clear-cache" link:
		$theData['up'][]='<a href="'.htmlspecialchars($this->listURL().'&clear_cache=1').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/clear_cache.gif','width="14" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.clear_cache',1).'" alt="" />'.
						'</a>';

			// Add "CSV" link, if a specific table is shown:
		if ($this->table)	{
			$theData['up'][]='<a href="'.htmlspecialchars($this->listURL().'&csv=1').'">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/csv.gif','width="27" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.csv',1).'" alt="" />'.
							'</a>';
		}

			// Add "refresh" link:
		$theData['up'][]='<a href="'.htmlspecialchars($this->listURL()).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/refresh_n.gif','width="14" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.reload',1).'" alt="" />'.
						'</a>';


			// Add icon with clickmenu, etc:
		if ($this->id)	{	// If there IS a real page...:

				// Setting title of page + the "Go up" link:
			$theData[$titleCol].='<br /><span title="'.htmlspecialchars($row['_thePathFull']).'">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($row['_thePath'],-$this->fixedL)).'</span>';
			$theData['up'][]='<a href="'.htmlspecialchars($this->listURL($row['pid'])).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/i/pages_up.gif','width="18" height="16"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel',1).'" alt="" />'.
							'</a>';

				// Make Icon:
			$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg,'pages',$this->id);
		} else {	// On root-level of page tree:

				// Setting title of root (sitename):
			$theData[$titleCol].='<br />'.htmlspecialchars(t3lib_div::fixed_lgd_cs($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],-$this->fixedL));

				// Make Icon:
			$theIcon='<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/i/_icon_website.gif','width="18" height="16"').' alt="" />';
		}

			// If there is a returnUrl given, add a back-link:
		if ($this->returnUrl)	{
			$theData['up'][]='<a href="'.htmlspecialchars(t3lib_div::linkThisUrl($this->returnUrl,array('id'=>$this->id))).'" class="typo3-goBack">'.
							'<img'.t3lib_iconWorks::skinImg($GLOBALS["BACK_PATH"],'gfx/goback.gif','width="14" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.goBack',1).'" alt="" />'.
							'</a>';
		}

			// Finally, the "up" pseudo field is compiled into a table - has been accumulated in an array:
		$theData['up']='
			<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>'.implode('</td>
					<td>',$theData['up']).'</td>
				</tr>
			</table>';

			// ... and the element row is created:
		$out.=$this->addelement(1,'',$theData,'',$this->leftMargin,$theIcon);

			// ... and wrapped into a table and added to the internal ->HTMLcode variable:
		$this->HTMLcode.='


		<!--
			Page header for db_list:
		-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-dblist-top">
				'.$out.'
			</table>';
	}

	/**
	 * Creates the listing of records from a single table
	 *
	 * @param	string		Table name
	 * @param	integer		Page id
	 * @param	string		List of fields to show in the listing. Pseudo fields will be added including the record header.
	 * @return	string		HTML table with the listing for the record.
	 */
	function getTable($table,$id,$rowlist)	{
		global $TCA;

			// Loading all TCA details for this table:
		t3lib_div::loadTCA($table);

			// Init
		$titleCol = $TCA[$table]['ctrl']['label'];
		$thumbsCol = $TCA[$table]['ctrl']['thumbnail'];

			// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
		$this->fieldArray=array();
		$this->fieldArray[] = $titleCol;	// Add title column
		if (!t3lib_div::inList($rowlist,'_CONTROL_'))	{
			$this->fieldArray[] = '_CONTROL_';
		}
		if ($this->showClipboard)	{
			$this->fieldArray[] = '_CLIPBOARD_';
		}
		if ($this->searchLevels)	{
			$this->fieldArray[]='_PATH_';
		}
			// Cleaning up:
		$this->fieldArray=array_unique(array_merge($this->fieldArray,t3lib_div::trimExplode(',',$rowlist,1)));
		if ($this->noControlPanels)	{
			$tempArray = array_flip($this->fieldArray);
			unset($tempArray['_CONTROL_']);
			unset($tempArray['_CLIPBOARD_']);
			$this->fieldArray = array_keys($tempArray);
		}

			// Creating the list of fields to include in the SQL query:
		$selectFields = $this->fieldArray;
		$selectFields[] = 'uid';
		$selectFields[] = 'pid';
		if ($thumbsCol)	$selectFields[] = $thumbsCol;	// adding column for thumbnails
		if ($table=='pages')	{
			if (t3lib_extMgm::isLoaded('cms'))	{
				$selectFields[] = 'module';
				$selectFields[] = 'extendToSubpages';
			}
			$selectFields[] = 'doktype';
		}
		if (is_array($TCA[$table]['ctrl']['enablecolumns']))	{
			$selectFields = array_merge($selectFields,$TCA[$table]['ctrl']['enablecolumns']);
		}
		if ($TCA[$table]['ctrl']['type'])	{
			$selectFields[] = $TCA[$table]['ctrl']['type'];
		}
		if ($TCA[$table]['ctrl']['typeicon_column'])	{
			$selectFields[] = $TCA[$table]['ctrl']['typeicon_column'];
		}
		if ($TCA[$table]['ctrl']['label_alt'])	{
			$selectFields = array_merge($selectFields,t3lib_div::trimExplode(',',$TCA[$table]['ctrl']['label_alt'],1));
		}
		$selectFields = array_unique($selectFields);		// Unique list!
		$selectFields = array_intersect($selectFields,$this->makeFieldList($table,1));		// Making sure that the fields in the field-list ARE in the field-list from TCA!
		$selFieldList = implode(',',$selectFields);		// implode it into a list of fields for the SQL-statement.

			// Create the SQL query for selecting the elements in the listing:
		$queryParts = $this->makeQueryArray($table, $id,'',$selFieldList);	// (API function from class.db_list.inc)
		$this->setTotalItems($queryParts);		// Finding the total amount of records on the page (API function from class.db_list.inc)

			// Init:
		$dbCount = 0;
		$out = '';

			// If the count query returned any number of records, we perform the real query, selecting records.
		if ($this->totalItems)	{
			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
			$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
		}

		$LOISmode = $this->listOnlyInSingleTableMode && !$this->table;

			// If any records was selected, render the list:
		if ($dbCount)	{

				// Half line is drawn between tables:
			if (!$LOISmode)	{
				$theData = Array();
				if (!$this->table && !$rowlist)	{
					$theData[$titleCol] = '<img src="clear.gif" width="'.($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel']?'230':'350').'" height="1" alt="" />';
					if (in_array('_CONTROL_',$this->fieldArray))	$theData['_CONTROL_']='';
					if (in_array('_CLIPBOARD_',$this->fieldArray))	$theData['_CLIPBOARD_']='';
				}
				$out.=$this->addelement(0,'',$theData,'',$this->leftMargin);
			}

				// Header line is drawn
			$theData = Array();
			if ($this->disableSingleTableView)	{
				$theData[$titleCol] = '<span class="c-table">'.$GLOBALS['LANG']->sL($TCA[$table]['ctrl']['title'],1).'</span> ('.$this->totalItems.')';
			} else {
				$theData[$titleCol] = $this->linkWrapTable($table,'<span class="c-table">'.$GLOBALS['LANG']->sL($TCA[$table]['ctrl']['title'],1).'</span> ('.$this->totalItems.') <img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/'.($this->table?'minus':'plus').'bullet_list.gif','width="18" height="12"').' hspace="10" class="absmiddle" title="'.$GLOBALS['LANG']->getLL(!$this->table?'expandView':'contractView',1).'" alt="" />');
			}

			if ($LOISmode)	{
				$out.='
					<tr>
						<td class="c-headLineTable" style="width:95%;">'.$theData[$titleCol].'</td>
					</tr>';

				if ($GLOBALS['BE_USER']->uc["edit_showFieldHelp"])	{
					$GLOBALS['LANG']->loadSingleTableDescription($table);
					if (isset($GLOBALS['TCA_DESCR'][$table]['columns']['']))	{
						$onClick = 'vHWin=window.open(\'view_help.php?tfID='.$table.'.\',\'viewFieldHelp\',\'height=300,width=250,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
						$out.='
					<tr>
						<td class="c-tableDescription">'.t3lib_BEfunc::helpTextIcon($table,'',$this->backPath,TRUE).$GLOBALS['TCA_DESCR'][$table]['columns']['']['description'].'</td>
					</tr>';
					}
				}
			} else {
				$theUpIcon = ($table=='pages'&&$this->id&&isset($this->pageRow['pid'])) ? '<a href="'.htmlspecialchars($this->listURL($this->pageRow['pid'])).'"><img'.t3lib_iconWorks::skinImg('','gfx/i/pages_up.gif','width="18" height="16"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.upOneLevel',1).'" alt="" /></a>':'';
				$out.=$this->addelement(1,$theUpIcon,$theData,' class="c-headLineTable"','');
			}

			If (!$LOISmode)	{
					// Fixing a order table for sortby tables
				$this->currentTable = array();
				$currentIdList = array();
				$doSort = ($TCA[$table]['ctrl']['sortby'] && !$this->sortField);

				$prevUid = 0;
				$prevPrevUid = 0;
				$accRows = array();	// Accumulate rows here
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{
					$accRows[] = $row;
					$currentIdList[] = $row['uid'];
					if ($doSort)	{
						if ($prevUid)	{
							$this->currentTable['prev'][$row['uid']] = $prevPrevUid;
							$this->currentTable['next'][$prevUid] = '-'.$row['uid'];
							$this->currentTable['prevUid'][$row['uid']] = $prevUid;
						}
						$prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
						$prevUid=$row['uid'];
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($result);

					// CSV initiated
				if ($this->csvOutput) $this->initCSV();

					// Render items:
				$this->CBnames=array();
				$this->duplicateStack=array();
				$this->eCounter=$this->firstElementNumber;

				$iOut = '';
				$cc = 0;
				foreach($accRows as $row)	{

						// Forward/Backwards navigation links:
					list($flag,$code) = $this->fwd_rwd_nav($table);
					$iOut.=$code;

						// If render item, increment counter and call function
					if ($flag)	{
						$cc++;
						$iOut.=$this->renderListRow($table,$row,$cc,$titleCol,$thumbsCol);
					}

						// Counter of total rows incremented:
					$this->eCounter++;
				}

					// The header row for the table is now created:
				$out.=$this->renderListHeader($table,$currentIdList);
			}

				// The list of records is added after the header:
			$out.=$iOut;

				// ... and it is all wrapped in a table:
			$out='



			<!--
				DB listing of elements:	"'.htmlspecialchars($table).'"
			-->
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist'.($LOISmode?' typo3-dblist-overview':'').'">
					'.$out.'
				</table>';

				// Output csv if...
			if ($this->csvOutput)	$this->outputCSV($table);	// This ends the page with exit.
		}

			// Return content:
		return $out;
	}

	/**
	 * Rendering a single row for the list
	 *
	 * @param	string		Table name
	 * @param	array		Current record
	 * @param	integer		Counter, counting for each time an element is rendered (used for alternating colors)
	 * @param	string		Table field (column) where header value is found
	 * @param	string		Table field (column) where (possible) thumbnails can be found
	 * @return	string		Table row for the element
	 * @access private
	 * @see getTable()
	 */
	function renderListRow($table,$row,$cc,$titleCol,$thumbsCol)	{
		$iOut = '';

			// Background color, if any:
		$row_bgColor=
			$this->alternateBgColors ?
			(($cc%2)?'' :' bgcolor="'.t3lib_div::modifyHTMLColor($GLOBALS['SOBE']->doc->bgColor4,+10,+10,+10).'"') :
			'';

			// Initialization
		$alttext = t3lib_BEfunc::getRecordIconAltText($row,$table);
		$recTitle = t3lib_BEfunc::getRecordTitle($table,$row);

			// Incr. counter.
		$this->counter++;

			// The icon with link
		$iconImg = t3lib_iconWorks::getIconImage($table,$row,$this->backPath,'title="'.htmlspecialchars($alttext).'"');
		$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg,$table,$row['uid']);

			// Preparing and getting the data-array
		$theData = Array();
		foreach($this->fieldArray as $fCol)	{
			if ($fCol==$titleCol)	{
				$theData[$fCol] = $this->linkWrapItems($table,$row['uid'],$recTitle,$row);
			} elseif ($fCol=='pid') {
				$theData[$fCol]=$row[$fCol];
			} elseif ($fCol=='_PATH_') {
				$theData[$fCol]=$this->recPath($row['pid']);
			} elseif ($fCol=='_CONTROL_') {
				$theData[$fCol]=$this->makeControl($table,$row);
			} elseif ($fCol=='_CLIPBOARD_') {
				$theData[$fCol]=$this->makeClip($table,$row);
			} else {
				$theData[$fCol]=htmlspecialchars(t3lib_BEfunc::getProcessedValueExtra($table,$fCol,$row[$fCol],100));
			}
		}

			// Add row to CSV list:
		if ($this->csvOutput) $this->addToCSV($row);

			// Create element in table cells:
		$iOut.=$this->addelement(1,$theIcon,$theData,$row_bgColor);

			// Render thumbsnails if a thumbnail column exists and there is content in it:
		if ($this->thumbs && trim($row[$thumbsCol]))	{
			$iOut.=$this->addelement(4,'', Array($titleCol=>$this->thumbCode($row,$table,$thumbsCol)),$row_bgColor);
		}

			// Finally, return table row element:
		return $iOut;
	}

	/**
	 * Rendering the header row for a table
	 *
	 * @param	string		Table name
	 * @param	array		Array of the currectly displayed uids of the table
	 * @return	string		Header table row
	 * @access private
	 * @see getTable()
	 */
	function renderListHeader($table,$currentIdList)	{
		global $TCA, $LANG;

			// Init:
		$theData = Array();

			// Traverse the fields:
		foreach($this->fieldArray as $fCol)	{

				// Calculate users permissions to edit records in the table:
			$permsEdit = $this->calcPerms & ($table=='pages'?2:16);

			switch((string)$fCol)	{
				case '_PATH_':			// Path
					$theData[$fCol]='<i>['.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels._PATH_',1).']</i>';
				break;
				case '_CLIPBOARD_':		// Clipboard:
					$cells=array();

						// If there are elements on the clipboard for this table, then display the "paste into" icon:
					$elFromTable = $this->clipObj->elFromTable($table);
					if (count($elFromTable))	{
						$cells[]='<a href="'.htmlspecialchars($this->clipObj->pasteUrl($table,$this->id)).'" onclick="'.htmlspecialchars('return '.$this->clipObj->confirmMsg('pages',$this->pageRow,'into',$elFromTable)).'">'.
								'<img'.t3lib_iconWorks::skinImg('','gfx/clip_pasteafter.gif','width="12" height="12"').' title="'.$LANG->getLL('clip_paste',1).'" alt="" />'.
								'</a>';
					}

						// If the numeric clipboard pads are enabled, display the control icons for that:
					if ($this->clipObj->current!='normal')	{

							// The "select" link:
						$cells[]=$this->linkClipboardHeaderIcon('<img'.t3lib_iconWorks::skinImg('','gfx/clip_copy.gif','width="12" height="12"').' title="'.$LANG->getLL('clip_selectMarked',1).'" alt="" />',$table,'setCB');

							// The "edit marked" link:
						$editIdList = implode(',',$currentIdList);
						$editIdList = "'+editList('".$table."','".$editIdList."')+'";
						$params='&edit['.$table.']['.$editIdList.']=edit&disHelp=1';
						$cells[]='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,'',-1)).'">'.
								'<img'.t3lib_iconWorks::skinImg('','gfx/edit2.gif','width="11" height="12"').' title="'.$LANG->getLL('clip_editMarked',1).'" alt="" />'.
								'</a>';

							// The "Delete marked" link:
						$cells[]=$this->linkClipboardHeaderIcon('<img'.t3lib_iconWorks::skinImg('','gfx/garbage.gif','width="11" height="12"').' title="'.$LANG->getLL('clip_deleteMarked',1).'" alt="" />',$table,'delete',sprintf($LANG->getLL('clip_deleteMarkedWarning'),$LANG->sL($TCA[$table]['ctrl']['title'])));

							// The "Select all" link:
						$cells[]='<a href="#" onclick="'.htmlspecialchars('checkOffCB(\''.implode(',',$this->CBnames).'\'); return false;').'">'.
								'<img'.t3lib_iconWorks::skinImg('','gfx/clip_select.gif','width="12" height="12"').' title="'.$LANG->getLL('clip_markRecords',1).'" alt="" />'.
								'</a>';
					} else {
						$cells[]='';
					}
					$theData[$fCol]=implode('',$cells);
				break;
				case '_CONTROL_':		// Control panel:
					if (!$TCA[$table]['ctrl']['readOnly'])	{

							// If new records can be created on this page, add links:
						if ($this->calcPerms&($table=='pages'?8:16) && $this->showNewRecLink($table))	{
							if ($table=="tt_content" && $this->newWizards)	{
									//  If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's create new content wizard instead:
								$tmpTSc = t3lib_BEfunc::getModTSconfig($this->pageinfo['uid'],'mod.web_list');
								$tmpTSc = $tmpTSc ['properties']['newContentWiz.']['overrideWithExtension'];
								$newContentWizScriptPath = t3lib_extMgm::isLoaded($tmpTSc) ? (t3lib_extMgm::extRelPath($tmpTSc).'mod1/db_new_content_el.php') : 'sysext/cms/layout/db_new_content_el.php';

								$theData[$fCol]='<a href="#" onclick="'.htmlspecialchars('return jumpExt(\''.$newContentWizScriptPath.'?id='.$this->id.'\');').'">'.
												'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/new_'.($table=='pages'?'page':'el').'.gif','width="'.($table=='pages'?13:11).'" height="12"').' title="'.$LANG->getLL('new',1).'" alt="" />'.
												'</a>';
							} elseif ($table=='pages' && $this->newWizards)	{
								$theData[$fCol]='<a href="'.htmlspecialchars('db_new.php?id='.$this->id.'&pagesOnly=1returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.
												'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/new_'.($table=='pages'?'page':'el').'.gif','width="'.($table=='pages'?13:11).'" height="12"').' title="'.$LANG->getLL('new',1).'" alt="" />'.
												'</a>';
							} else {
								$params='&edit['.$table.']['.$this->id.']=new';
								$theData[$fCol]='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,'',-1)).'">'.
												'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/new_'.($table=='pages'?'page':'el').'.gif','width="'.($table=='pages'?13:11).'" height="12"').' title="'.$LANG->getLL('new',1).'" alt="" />'.
												'</a>';
							}
						}

							// If the table can be edited, add link for editing ALL SHOWN fields for all listed records:
						if ($permsEdit && $this->table && is_array($currentIdList))	{
							$editIdList = implode(',',$currentIdList);
							if ($this->clipNumPane()) $editIdList = "'+editList('".$table."','".$editIdList."')+'";
							$params='&edit['.$table.']['.$editIdList.']=edit&columnsOnly='.implode(',',$this->fieldArray).'&disHelp=1';
							$theData[$fCol].='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,'',-1)).'">'.
											'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2.gif','width="11" height="12"').' title="'.$LANG->getLL('editShownColumns',1).'" alt="" />'.
											'</a>';
						}
					}
				break;
				default:			// Regular fields header:
					$theData[$fCol]='';
					if ($this->table && is_array($currentIdList))	{

							// If the numeric clipboard pads are selected, show duplicate sorting link:
						if ($this->clipNumPane()) {
							$theData[$fCol].='<a href="'.htmlspecialchars($this->listURL('',-1).'&duplicateField='.$fCol).'">'.
											'<img'.t3lib_iconWorks::skinImg('','gfx/select_duplicates.gif','width="11" height="11"').' title="'.$LANG->getLL('clip_duplicates',1).'" alt="" />'.
											'</a>';
						}

							// If the table can be edited, add link for editing THIS field for all listed records:
						if (!$TCA[$table]['ctrl']['readOnly'] && $permsEdit && $TCA[$table]['columns'][$fCol])	{
							$editIdList = implode(',',$currentIdList);
							if ($this->clipNumPane()) $editIdList = "'+editList('".$table."','".$editIdList."')+'";
							$params='&edit['.$table.']['.$editIdList.']=edit&columnsOnly='.$fCol.'&disHelp=1';
							$iTitle = sprintf($LANG->getLL('editThisColumn'),ereg_replace(':$','',trim($LANG->sL(t3lib_BEfunc::getItemLabel($table,$fCol)))));
							$theData[$fCol].='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,'',-1)).'">'.
											'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2.gif','width="11" height="12"').' title="'.htmlspecialchars($iTitle).'" alt="" />'.
											'</a>';
						}
					}
					$theData[$fCol].=$this->addSortLink($LANG->sL(t3lib_BEfunc::getItemLabel($table,$fCol,'<i>[|]</i>')),$fCol,$table);
				break;
			}
		}

			// Create and return header table row:
		return $this->addelement(1,'',$theData,' class="c-headLine"','');
	}






	/*********************************
	 *
	 * Rendering of various elements
	 *
	 *********************************/

	/**
	 * Creates the control panel for a single record in the listing.
	 *
	 * @param	string		The table
	 * @param	array		The record for which to make the control panel.
	 * @return	string		HTML table with the control panel (unless disabled)
	 */
	function makeControl($table,$row)	{
		global $TCA, $LANG, $SOBE;

			// Return blank, if disabled:
		if ($this->dontShowClipControlPanels)	return '';

			// Initialize:
		t3lib_div::loadTCA($table);
		$cells=array();

			// If the listed table is 'pages' we have to request the permission settings for each page:
		if ($table=='pages')	{
			$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$row['uid']));
		}

			// This expresses the edit permissions for this particular element:
		$permsEdit = ($table=='pages' && ($localCalcPerms&2)) || ($table!='pages' && ($this->calcPerms&16));

			// "Show" link (only pages and tt_content elements)
		if ($table=='pages' || $table=='tt_content')	{
			$params='&edit['.$table.']['.$row['uid'].']=edit';
			$cells[]='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($table=='tt_content'?$this->id.'#'.$row['uid']:$row['uid'])).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom.gif','width="12" height="12"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.showPage',1).'" alt="" />'.
					'</a>';
		}

			// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		if ($permsEdit)	{
			$params='&edit['.$table.']['.$row['uid'].']=edit';
			$cells[]='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,'',-1)).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/edit2'.(!$TCA[$table]['ctrl']['readOnly']?'':'_d').'.gif','width="11" height="12"').' title="'.$LANG->getLL('edit',1).'" alt="" />'.
					'</a>';
		}

			// "Move" wizard link for pages/tt_content elements:
		if (($table=="tt_content" && $permsEdit) || ($table=='pages'))	{
			$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpExt(\'move_el.php?table='.$table.'&uid='.$row['uid'].'\');').'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/move_'.($table=='tt_content'?'record':'page').'.gif','width="11" height="12"').' title="'.$LANG->getLL('move_'.($table=='tt_content'?'record':'page'),1).'" alt="" />'.
					'</a>';
		}

			// If the extended control panel is enabled OR if we are seeing a single table:
		if ($SOBE->MOD_SETTINGS['bigControlPanel'] || $this->table)	{

				// "Info": (All records)
			$cells[]='<a href="#" onclick="'.htmlspecialchars('top.launchView(\''.$table.'\', \''.$row['uid'].'\'); return false;').'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom2.gif','width="12" height="12"').' title="'.$LANG->getLL('showInfo',1).'" alt="" />'.
					'</a>';

				// If the table is NOT a read-only table, then show these links:
			if (!$TCA[$table]['ctrl']['readOnly'])	{

					// "Revert" link (history/undo)
				$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpExt(\'show_rechis.php?element='.rawurlencode($table.':'.$row['uid']).'\',\'#latest\');').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/history2.gif','width="13" height="12"').' title="'.$LANG->getLL('history',1).'" alt="" />'.
						'</a>';

					// "Edit Perms" link:
				if ($table=='pages' && $GLOBALS['BE_USER']->check('modules','web_perm'))	{
					$cells[]='<a href="'.htmlspecialchars('mod/web/perm/index.php?id='.$row['uid'].'&return_id='.$row['uid'].'&edit=1').'">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/perm.gif','width="7" height="12"').' title="'.$LANG->getLL('permissions',1).'" alt="" />'.
							'</a>';
				}

					// "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row or if default values can depend on previous record):
				if ($TCA[$table]['ctrl']['sortby'] || $TCA[$table]['ctrl']['useColumnsForDefaultValues'])	{
					if (
						($table!='pages' && ($this->calcPerms&16)) || 	// For NON-pages, must have permission to edit content on this parent page
						($table=='pages' && ($this->calcPerms&8))		// For pages, must have permission to create new pages here.
						)	{
						if ($this->showNewRecLink($table))	{
							$params='&edit['.$table.']['.(-$row['uid']).']=new';
							$cells[]='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,'',-1)).'">'.
									'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/new_'.($table=='pages'?'page':'el').'.gif','width="'.($table=='pages'?13:11).'" height="12"').' title="'.$LANG->getLL('new'.($table=='pages'?'Page':'Record'),1).'" alt="" />'.
									'</a>';
						}
					}
				}

					// "Up/Down" links
				if ($permsEdit && $TCA[$table]['ctrl']['sortby']  && !$this->sortField && !$this->searchLevels)	{
					if (isset($this->currentTable['prev'][$row['uid']]))	{	// Up
						$params='&cmd['.$table.']['.$row['uid'].'][move]='.$this->currentTable['prev'][$row['uid']];
						$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$SOBE->doc->issueCommand($params,-1).'\');').'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_up.gif','width="11" height="10"').' title="'.$LANG->getLL('moveUp',1).'" alt="" />'.
								'</a>';
					} else {
						$cells[]='<img src="clear.gif" '.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_up.gif','width="11" height="10"',2).' alt="" />';
					}
					if ($this->currentTable['next'][$row['uid']])	{	// Down
						$params='&cmd['.$table.']['.$row['uid'].'][move]='.$this->currentTable['next'][$row['uid']];
						$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$SOBE->doc->issueCommand($params,-1).'\');').'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_down.gif','width="11" height="10"').' title="'.$LANG->getLL('moveDown',1).'" alt="" />'.
								'</a>';
					} else {
						$cells[]='<img src="clear.gif" '.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_down.gif','width="11" height="10"',2).' alt="" />';
					}
				}

					// "Hide/Unhide" links:
				$hiddenField = $TCA[$table]['ctrl']['enablecolumns']['disabled'];
				if ($permsEdit && $hiddenField && $TCA[$table]['columns'][$hiddenField] && (!$TCA[$table]['columns'][$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields',$table.':'.$hiddenField)))	{
					if ($row[$hiddenField])	{
						$params='&data['.$table.']['.$row['uid'].']['.$hiddenField.']=0';
						$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$SOBE->doc->issueCommand($params,-1).'\');').'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_unhide.gif','width="11" height="10"').' title="'.$LANG->getLL('unHide'.($table=='pages'?'Page':''),1).'" alt="" />'.
								'</a>';
					} else {
						$params='&data['.$table.']['.$row['uid'].']['.$hiddenField.']=1';
						$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$SOBE->doc->issueCommand($params,-1).'\');').'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_hide.gif','width="11" height="10"').' title="'.$LANG->getLL('hide'.($table=='pages'?'Page':''),1).'" alt="" />'.
								'</a>';
					}
				}

					// "Delete" link:
				if (
					($table=='pages' && ($localCalcPerms&4)) || ($table!='pages' && ($this->calcPerms&16))
					)	{
					$params='&cmd['.$table.']['.$row['uid'].'][delete]=1';
					$cells[]='<a href="#" onclick="'.htmlspecialchars('if (confirm('.$LANG->JScharCode($LANG->getLL('deleteWarning')).')) {jumpToUrl(\''.$SOBE->doc->issueCommand($params,-1).'\');} return false;').'">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$LANG->getLL('delete',1).'" alt="" />'.
							'</a>';
				}

					// "Levels" links: Moving pages into new levels...
				if ($permsEdit && $table=='pages' && !$this->searchLevels)	{

						// Up (Paste as the page right after the current parent page)
					if ($this->calcPerms&8)	{
						$params='&cmd['.$table.']['.$row['uid'].'][move]='.-$this->id;
						$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$SOBE->doc->issueCommand($params,-1).'\');').'">'.
								'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_left.gif','width="11" height="10"').' title="'.$LANG->getLL('prevLevel',1).'" alt="" />'.
								'</a>';
					}
						// Down (Paste as subpage to the page right above)
					if ($this->currentTable['prevUid'][$row['uid']])	{
						$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$this->currentTable['prevUid'][$row['uid']]));
						if ($localCalcPerms&8)	{
							$params='&cmd['.$table.']['.$row['uid'].'][move]='.$this->currentTable['prevUid'][$row['uid']];
							$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$SOBE->doc->issueCommand($params,-1).'\');').'">'.
									'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_right.gif','width="11" height="10"').' title="'.$LANG->getLL('nextLevel',1).'" alt="" />'.
									'</a>';
						} else {
							$cells[]='<img src="clear.gif" '.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_right.gif','width="11" height="10"',2).' alt="" />';
						}
					} else {
						$cells[]='<img src="clear.gif" '.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_right.gif','width="11" height="10"',2).' alt="" />';
					}
				}
			}
		}

			// If the record is edit-locked	by another user, we will show a little warning sign:
		if ($lockInfo=t3lib_BEfunc::isRecordLocked($table,$row['uid']))	{
			$cells[]='<a href="#" onclick="'.htmlspecialchars('alert('.$LANG->JScharCode($lockInfo['msg']).');return false;').'">'.
					'<img'.t3lib_iconWorks::skinImg('','gfx/recordlock_warning3.gif','width="17" height="12"').' title="'.htmlspecialchars($lockInfo['msg']).'" alt="" />'.
					'</a>';
		}


			// Compile items into a DIV-element:
		return '
											<!-- CONTROL PANEL: '.$table.':'.$row['uid'].' -->
											<div class="typo3-DBctrl">'.implode('',$cells).'</div>';
	}

	/**
	 * Creates the clipboard panel for a single record in the listing.
	 *
	 * @param	string		The table
	 * @param	array		The record for which to make the clipboard panel.
	 * @return	string		HTML table with the clipboard panel (unless disabled)
	 */
	function makeClip($table,$row)	{
		global $TCA, $LANG;

			// Return blank, if disabled:
		if ($this->dontShowClipControlPanels)	return '';
		$cells=array();

			// Whether a numeric clipboard pad is active or the normal pad we will see different content of the panel:
		if ($this->clipObj->current=='normal')	{	// For the "Normal" pad:

				// Show copy/cut icons:
			$isSel = (string)$this->clipObj->isSelected($table,$row['uid']);
			$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpSelf(\''.$this->clipObj->selUrlDB($table,$row['uid'],1,($isSel=='copy'),array('returnUrl'=>'')).'\');').'">'.
					'<img'.t3lib_iconWorks::skinImg('','gfx/clip_copy'.($isSel=='copy'?'_h':'').'.gif','width="12" height="12"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:cm.copy',1).'" alt="" />'.
					'</a>';
			$cells[]='<a href="#" onclick="'.htmlspecialchars('return jumpSelf(\''.$this->clipObj->selUrlDB($table,$row['uid'],0,($isSel=='cut'),array('returnUrl'=>'')).'\');').'">'.
					'<img'.t3lib_iconWorks::skinImg('','gfx/clip_cut'.($isSel=='cut'?'_h':'').'.gif','width="12" height="12"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:cm.cut',1).'" alt="" />'.
					'</a>';

		} else {	// For the numeric clipboard pads (showing checkboxes where one can select elements on/off)

				// Setting name of the element in ->CBnames array:
			$n=$table.'|'.$row['uid'];
			$this->CBnames[]=$n;

				// Check if the current element is selected and if so, prepare to set the checkbox as selected:
			$checked = ($this->clipObj->isSelected($table,$row['uid'])?' checked="checked"':'');

				// If the "duplicateField" value is set then select all elements which are duplicates...
			if ($this->duplicateField && isset($row[$this->duplicateField]))	{
				$checked='';
				if (in_array($row[$this->duplicateField], $this->duplicateStack))	{
					$checked=' checked="checked"';
				}
				$this->duplicateStack[] = $row[$this->duplicateField];
			}

				// Adding the checkbox to the panel:
			$cells[]='<input type="hidden" name="CBH['.$n.']" value="0" /><input type="checkbox" name="CBC['.$n.']" value="1" class="smallCheckboxes"'.$checked.' />';
		}

			// Now, looking for selected elements from the current table:
		$elFromTable = $this->clipObj->elFromTable($table);
		if (count($elFromTable) && $TCA[$table]['ctrl']['sortby'])	{	// IF elements are found and they can be individually ordered, then add a "paste after" icon:
			$cells[]='<a href="'.htmlspecialchars($this->clipObj->pasteUrl($table,-$row['uid'])).'" onclick="'.htmlspecialchars('return '.$this->clipObj->confirmMsg($table,$row,'after',$elFromTable)).'">'.
					'<img'.t3lib_iconWorks::skinImg('','gfx/clip_pasteafter.gif','width="12" height="12"').' title="'.$LANG->getLL('clip_pasteAfter',1).'" alt="" />'.
					'</a>';
		}

			// Now, looking for elements in general:
		$elFromTable = $this->clipObj->elFromTable('');
		if ($table=='pages' && count($elFromTable))	{
			$cells[]='<a href="'.htmlspecialchars($this->clipObj->pasteUrl('',$row['uid'])).'" onclick="'.htmlspecialchars('return '.$this->clipObj->confirmMsg($table,$row,'into',$elFromTable)).'">'.
					'<img'.t3lib_iconWorks::skinImg('','gfx/clip_pasteinto.gif','width="12" height="12"').' title="'.$LANG->getLL('clip_pasteInto',1).'" alt="" />'.
					'</a>';
		}

			// Compile items into a DIV-element:
		return '							<!-- CLIPBOARD PANEL: '.$table.':'.$row['uid'].' -->
											<div class="typo3-clipCtrl">'.implode('',$cells).'</div>';
	}

	/**
	 * Create the selector box for selecting fields to display from a table:
	 *
	 * @param	string		Table name
	 * @param	boolean		If true, form-fields will be wrapped around the table.
	 * @return	string		HTML table with the selector box (name: displayFields['.$table.'][])
	 */
	function fieldSelectBox($table,$formFields=1)	{
		global $TCA, $LANG;

			// Init:
		t3lib_div::loadTCA($table);
		$formElements=array('','');
		if ($formFields)	{
			$formElements=array('<form action="'.htmlspecialchars($this->listURL()).'" method="post">','</form>');
		}

			// Load already selected fields, if any:
		$setFields=is_array($this->setFields[$table]) ? $this->setFields[$table] : array();

			// Request fields from table:
		$fields = $this->makeFieldList($table);

			// Add pseudo "control" fields
		$fields[]='_PATH_';
		$fields[]='_CONTROL_';
		$fields[]='_CLIPBOARD_';

			// Create an option for each field:
		$opt=array();
		$opt[] = '<option value=""></option>';
		foreach($fields as $fN)	{
			$fL = is_array($TCA[$table]['columns'][$fN]) ? ereg_replace(':$','',$LANG->sL($TCA[$table]['columns'][$fN]['label'])) : '['.$fN.']';	// Field label
			$opt[] = '
											<option value="'.$fN.'"'.(in_array($fN,$setFields)?' selected="selected"':'').'>'.htmlspecialchars($fL).'</option>';
		}

			// Compile the options into a multiple selector box:
		$lMenu = '
										<select size="'.t3lib_div::intInRange(count($fields)+1,3,20).'" multiple="multiple" name="displayFields['.$table.'][]">'.implode('',$opt).'
										</select>
				';

			// Table with the field selector::
		$content.= '
			'.$formElements[0].'

				<!--
					Field selector for extended table view:
				-->
				<table border="0" cellpadding="0" cellspacing="0" class="bgColor4" id="typo3-dblist-fieldSelect">
					<tr>
						<td>'.$lMenu.'</td>
						<td><input type="submit" name="search" value="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.setFields',1).'" /></td>
					</tr>
				</table>
			'.$formElements[1];
		return $content;
	}











	/*********************************
	 *
	 * Helper functions
	 *
	 *********************************/

	/**
	 * Creates a link around $string. The link contains an onclick action which submits the script with some clipboard action.
	 * Currently, this is used for setting elements / delete elements.
	 *
	 * @param	string		The HTML content to link (image/text)
	 * @param	string		Table name
	 * @param	string		Clipboard command (eg. "setCB" or "delete")
	 * @param	string		Warning text, if any ("delete" uses this for confirmation)
	 * @return	string		<a> tag wrapped link.
	 */
	function linkClipboardHeaderIcon($string,$table,$cmd,$warning='')	{
		$onClickEvent = 'document.dblistForm.cmd.value=\''.$cmd.'\';document.dblistForm.cmd_table.value=\''.$table.'\';document.dblistForm.submit();';
		if ($warning)	$onClickEvent = 'if (confirm('.$GLOBALS['LANG']->JScharCode($warning).')){'.$onClickEvent.'}';
		return '<a href="#" onclick="'.htmlspecialchars($onClickEvent.'return false;').'">'.$string.'</a>';
	}

	/**
	 * Returns true if a numeric clipboard pad is selected/active
	 *
	 * @return	boolean
	 */
	function clipNumPane()	{
		return in_Array('_CLIPBOARD_',$this->fieldArray) && $this->clipObj->current!='normal';
	}

	/**
	 * Creates a sort-by link on the input string ($code).
	 * It will automatically detect if sorting should be ascending or descending depending on $this->sortRev.
	 * Also some fields will not be possible to sort (including if single-table-view is disabled).
	 *
	 * @param	string		The string to link (text)
	 * @param	string		The fieldname represented by the title ($code)
	 * @param	string		Table name
	 * @return	string		Linked $code variable
	 */
	function addSortLink($code,$field,$table)	{

			// Certain circumstances just return string right away (no links):
		if ($field=='_CONTROL_' || $field=='_CLIPBOARD_' || $this->disableSingleTableView)	return $code;

			// If "_PATH_" (showing record path) is selected, force sorting by pid field (will at least group the records!)
		if ($field=='_PATH_')	$field=pid;

			//	 Create the sort link:
		$sortUrl = $this->listURL('',-1,'sortField,sortRev,table').'&table='.$table.'&sortField='.$field.'&sortRev='.($this->sortRev || ($this->sortField!=$field)?0:1);
		$sortArrow = ($this->sortField==$field?'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/red'.($this->sortRev?'up':'down').'.gif','width="7" height="4"').' alt="" />':'');

			// Return linked field:
		return '<a href="'.htmlspecialchars($sortUrl).'">'.$code.
				$sortArrow.
				'</a>';
	}

	/**
	 * Returns the path for a certain pid
	 * The result is cached internally for the session, thus you can call this function as much as you like without performance problems.
	 *
	 * @param	integer		The page id for which to get the path
	 * @return	string		The path.
	 */
	function recPath($pid)	{
		if (!isset($this->recPath_cache[$pid]))	{
			$this->recPath_cache[$pid] = t3lib_BEfunc::getRecordPath($pid,$this->perms_clause,20);
		}
		return $this->recPath_cache[$pid];
	}

	/**
	 * Returns true if a link for creating new records should be displayed for $table
	 *
	 * @param	string		Table name
	 * @return	boolean
	 */
	function showNewRecLink($table)	{
		return !count($this->allowedNewTables) || in_array($table,$this->allowedNewTables);
	}

	/**
	 * Creates the "&returnUrl" parameter for links - this is used when the script links to other scripts and passes its own URL with the link so other scripts can return to the listing again.
	 * Uses REQUEST_URI as value.
	 *
	 * @return	string
	 */
	function makeReturnUrl()	{
		return '&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
	}










	/************************************
	 *
	 * CSV related functions
	 *
	 ************************************/

	/**
	 * Initializes internal csvLines array with the header of field names
	 *
	 * @return	void
	 */
	function initCSV()	{

			// Reset:
		$this->csvLines=array();

			// Getting header line with field names:
		$csvRow=array();
		foreach($this->fieldArray as $fN)	{
			$csvRow[]=$fN;
		}

			// Set the header + an empty row:
		$this->setCsvRow($csvRow);
		$this->csvLines[]='';
	}

	/**
	 * Adds the content of input array $row to the CSV list:
	 *
	 * @param	array		Record array, from which the values of fields found in $this->fieldArray will be listed in the CSV output.
	 * @return	void
	 */
	function addToCSV($row)	{

			// Traversing fields, adding values from $row:
		$csvRow=array();
		foreach($this->fieldArray as $fN)	{
			if ($fN=='_PATH_')	{
				$csvRow[]=$this->recPath($row['pid']);
			} else {
				$csvRow[]=$row[$fN];
			}
		}

			// Set the values in the CSV list
		$this->setCsvRow($csvRow);
	}

	/**
	 * Adds input row of values to the internal csvLines array as a CSV formatted line
	 *
	 * @param	array		Array with values to be listed.
	 * @return	void
	 */
	function setCsvRow($csvRow)	{
		$this->csvLines[] = t3lib_div::csvValues($csvRow);
	}

	/**
	 * Compiles the internal csvLines array to a csv-string and outputs it to the browser.
	 * This function exits!
	 *
	 * @param	string		Filename prefix:
	 * @return	void		EXITS php execusion!
	 */
	function outputCSV($prefix)	{

			// Setting filename:
		$filename=$prefix.'_'.date('dmy-Hi').'.csv';

			// Creating output header:
		$mimeType = 'application/octet-stream';
		Header('Content-Type: '.$mimeType);
		Header('Content-Disposition: attachment; filename='.$filename);

			// Printing the content of the CSV lines:
		echo implode(chr(13).chr(10),$this->csvLines);

			// Exits:
		exit;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.db_list_extra.inc'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.db_list_extra.inc']);
}
?>
