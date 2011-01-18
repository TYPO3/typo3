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
 * Shows information about a database or file item
 *
 * $Id$
 * Revised for TYPO3 3.7 May/2004 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   84: class transferData extends t3lib_transferData
 *  101:     function regItem($table, $id, $field, $content)
 *
 *
 *  135: class SC_show_item
 *  160:     function init()
 *  225:     function main()
 *  273:     function renderDBInfo()
 *  327:     function renderFileInfo($returnLinkTag)
 *  449:     function printContent()
 *  462:     function makeRef($table,$ref)
 *  524:     function makeRefFrom($table,$ref)
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


$BACK_PATH = '';
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');












/**
 * Extension of transfer data class
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class transferData extends t3lib_transferData	{

	var $formname = 'loadform';
	var $loading = 1;

		// Extra for show_item.php:
	var $theRecord = Array();

	/**
	 * Register item function.
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @param	string		Field name
	 * @param	string		Content string.
	 * @return	void
	 */
	function regItem($table, $id, $field, $content)	{
		t3lib_div::loadTCA($table);
		$config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
		switch($config['type'])	{
			case 'input':
				if (isset($config['checkbox']) && $content==$config['checkbox'])	{$content=''; break;}
				if (t3lib_div::inList($config['eval'],'date'))	{$content = Date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],$content); }
			break;
			case 'group':
			break;
			case 'select':
			break;
		}
		$this->theRecord[$field]=$content;
	}
}











/**
 * Script Class for showing information about an item.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_show_item {

		// GET vars:
	var $table;			// Record table (or filename)
	var $uid;			// Record uid  (or '' when filename)

		// Internal, static:
	var $perms_clause;	// Page select clause
	var $access;		// If true, access to element is granted
	var $type;			// Which type of element: "file" or "db"
	var $doc;			// Document Template Object

		// Internal, dynamic:
	var $content;		// Content Accumulation
	var $file;			// For type "file": Filename
	var $pageinfo;		// For type "db": Set to page record of the parent page of the item set (if type="db")
	var $row;			// For type "db": The database record row.


	/**
	 * Initialization of the class
	 * Will determine if table/uid GET vars are database record or a file and if the user has access to view information about the item.
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$BACK_PATH,$TCA;

			// Setting input variables.
		$this->table = t3lib_div::_GET('table');
		$this->uid = t3lib_div::_GET('uid');

			// Initialize:
		$this->perms_clause = $BE_USER->getPagePermsClause(1);
		$this->access = 0;	// Set to true if there is access to the record / file.
		$this->type = '';	// Sets the type, "db" or "file". If blank, nothing can be shown.

			// Checking if the $table value is really a table and if the user has access to it.
		if (isset($TCA[$this->table]))	{
			t3lib_div::loadTCA($this->table);
			$this->type = 'db';
			$this->uid = intval($this->uid);

				// Check permissions and uid value:
			if ($this->uid && $BE_USER->check('tables_select',$this->table))	{
				if ((string)$this->table=='pages')	{
					$this->pageinfo = t3lib_BEfunc::readPageAccess($this->uid,$this->perms_clause);
					$this->access = is_array($this->pageinfo) ? 1 : 0;
					$this->row = $this->pageinfo;
				} else {
					$this->row = t3lib_BEfunc::getRecordWSOL($this->table, $this->uid);
					if ($this->row)	{
						$this->pageinfo = t3lib_BEfunc::readPageAccess($this->row['pid'],$this->perms_clause);
						$this->access = is_array($this->pageinfo) ? 1 : 0;
					}
				}

				$treatData = t3lib_div::makeInstance('t3lib_transferData');
				$treatData->renderRecord($this->table, $this->uid, 0, $this->row);
				$cRow = $treatData->theRecord;
			}
		} else	{
			// if the filereference $this->file is relative, we correct the path
			if (substr($this->table,0,3)=='../')	{
				$this->file = PATH_site.preg_replace('/^\.\.\//','',$this->table);
			} else {
				$this->file = $this->table;
			}
			if (@is_file($this->file) && t3lib_div::isAllowedAbsPath($this->file))	{
				$this->type = 'file';
				$this->access = 1;
			}
		}

			// Initialize document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;

			// Starting the page by creating page header stuff:
		$this->content.=$this->doc->startPage($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.viewItem'));
		$this->content.='<h3 class="t3-row-header">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.viewItem') . '</h3>';
		$this->content.=$this->doc->spacer(5);
	}

	/**
	 * Main function. Will generate the information to display for the item set internally.
	 *
	 * @return	void
	 */
	function main()	{

		if ($this->access)	{
			$returnLink =  t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));
			$returnLinkTag = $returnLink ? '<a href="' . $returnLink . '" class="typo3-goBack">' : '<a href="#" onclick="window.close();">';

				// render type by user func
			$typeRendered = false;
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'] as $classRef) {
					$typeRenderObj = t3lib_div::getUserObj($classRef);
					if(is_object($typeRenderObj) && method_exists($typeRenderObj, 'isValid') && method_exists($typeRenderObj, 'render'))	{
						if ($typeRenderObj->isValid($this->type, $this)) {
							$this->content .=  $typeRenderObj->render($this->type, $this);
							$typeRendered = true;
							break;
						}
					}
				}
			}

				// if type was not rendered use default rendering functions
			if(!$typeRendered) {
					// Branch out based on type:
				switch($this->type)	{
					case 'db':
						$this->renderDBInfo();
					break;
					case 'file':
						$this->renderFileInfo($returnLinkTag);
					break;
				}
			}

				// If return Url is set, output link to go back:
			if (t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl')))	{
				$this->content = $this->doc->section('',$returnLinkTag.'<strong>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.goBack',1).'</strong></a><br /><br />').$this->content;

				$this->content .= $this->doc->section('','<br />'.$returnLinkTag.'<strong>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.goBack',1).'</strong></a>');
			}
		}
	}

	/**
	 * Main function. Will generate the information to display for the item set internally.
	 *
	 * @return	void
	 */
	function renderDBInfo()	{
		global $TCA;

			// Print header, path etc:
		$code = $this->doc->getHeader($this->table,$this->row,$this->pageinfo['_thePath'],1).'<br />';
		$this->content.= $this->doc->section('',$code);

			// Initialize variables:
		$tableRows = Array();
		$i = 0;

			// Traverse the list of fields to display for the record:
		$fieldList = t3lib_div::trimExplode(',', $TCA[$this->table]['interface']['showRecordFieldList'], 1);
		foreach ($fieldList as $name) {
			$name = trim($name);
			if ($TCA[$this->table]['columns'][$name])	{
				if (!$TCA[$this->table]['columns'][$name]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $name)) {
					$i++;
					$tableRows[] = '
						<tr>
							<td class="t3-col-header">' . $GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($this->table, $name), 1) . '</td>
							<td>' . htmlspecialchars(t3lib_BEfunc::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $this->row['uid'])) . '</td>
						</tr>';
				}
			}
		}

			// Create table from the information:
		$tableCode = '
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-showitem" class="t3-table-info">
						'.implode('',$tableRows).'
					</table>';
		$this->content.=$this->doc->section('',$tableCode);

			// Add path and table information in the bottom:
		$code = '';
		$code.= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'],-48).'<br />';
		$code.= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.table').': '.$GLOBALS['LANG']->sL($TCA[$this->table]['ctrl']['title']).' ('.$this->table.') - UID: '.$this->uid.'<br />';
		$this->content.= $this->doc->section('', $code);

			// References:
		$this->content.= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.referencesToThisItem'),$this->makeRef($this->table,$this->row['uid']));

			// References:
		$this->content.= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.referencesFromThisItem'),$this->makeRefFrom($this->table,$this->row['uid']));
	}

	/**
	 * Main function. Will generate the information to display for the item set internally.
	 *
	 * @param	string		<a> tag closing/returning.
	 * @return	void
	 */
	function renderFileInfo($returnLinkTag)	{

			// Initialize object to work on the image:
		$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
		$imgObj->init();
		$imgObj->mayScaleUp = 0;
		$imgObj->absPrefix = PATH_site;

			// Read Image Dimensions (returns false if file was not an image type, otherwise dimensions in an array)
		$imgInfo = '';
		$imgInfo = $imgObj->getImageDimensions($this->file);

			// File information
		$fI = t3lib_div::split_fileref($this->file);
		$ext = $fI['fileext'];

		$code = '';

			// Setting header:
		$fileName = t3lib_iconWorks::getSpriteIconForFile($ext) . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.file', TRUE) . ':</strong> ' . $fI['file'];
		if (t3lib_div::isFirstPartOfStr($this->file,PATH_site))	{
			$code.= '<a href="../'.substr($this->file,strlen(PATH_site)).'" target="_blank">'.$fileName.'</a>';
		} else {
			$code.= $fileName;
		}
		$code.=' &nbsp;&nbsp;<strong>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.filesize').':</strong> '.t3lib_div::formatSize(@filesize($this->file)).'<br />
			';
		if (is_array($imgInfo))	{
			$code.= '<strong>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.dimensions').':</strong> '.$imgInfo[0].'x'.$imgInfo[1].' '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.pixels');
		}
		$this->content.=$this->doc->section('',$code);
		$this->content.=$this->doc->divider(2);

			// If the file was an image...:
		if (is_array($imgInfo))	{

			$imgInfo = $imgObj->imageMagickConvert($this->file,'web','346','200m','','','',1);
			$imgInfo[3] = '../'.substr($imgInfo[3],strlen(PATH_site));
			$code = '<br />
				<div align="center">'.$returnLinkTag.$imgObj->imgTag($imgInfo).'</a></div>';
			$this->content.= $this->doc->section('', $code);
		} else {
			$this->content.= $this->doc->spacer(10);
			$lowerFilename = strtolower($this->file);

				// Archive files:
			if (TYPO3_OS!='WIN' && !$GLOBALS['TYPO3_CONF_VARS']['BE']['disable_exec_function'])	{
				if ($ext=='zip')	{
					$code = '';
					$t = array();
					t3lib_utility_Command::exec('unzip -l ' . $this->file, $t);
					if (is_array($t))	{
						reset($t);
						next($t);
						next($t);
						next($t);
						while(list(,$val)=each($t))	{
							$parts = explode(' ',trim($val),7);
							$code.= '
								'.$parts[6].'<br />';
						}
						$code = '
							<span class="nobr">'.$code.'
							</span>
							<br /><br />';
					}
					$this->content.= $this->doc->section('', $code);
				} elseif($ext=='tar' || $ext=='tgz' || substr($lowerFilename,-6)=='tar.gz' || substr($lowerFilename,-5)=='tar.z')	{
					$code = '';
					if ($ext=='tar')	{
						$compr = '';
					} else {
						$compr = 'z';
					}
					$t = array();
					t3lib_utility_Command::exec('tar t' . $compr . 'f ' . $this->file, $t);
					if (is_array($t))	{
						foreach($t as $val)	{
							$code.='
								'.$val.'<br />';
						}

						$code.='
								 -------<br/>
								 '.count($t).' '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.files');

						$code = '
							<span class="nobr">'.$code.'
							</span>
							<br /><br />';
					}
					$this->content.= $this->doc->section('',$code);
				}
			} elseif ($GLOBALS['TYPO3_CONF_VARS']['BE']['disable_exec_function']) {
				$this->content.= $this->doc->section('',$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.cannotDisplayArchive'));
			}

				// Font files:
			if ($ext=='ttf')	{
				$thumbScript = 'thumbs.php';
				$check = basename($this->file).':'.filemtime($this->file).':'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
				$params = '&file='.rawurlencode($this->file);
				$params.= '&md5sum='.t3lib_div::shortMD5($check);
				$url = $thumbScript.'?&dummy='.$GLOBALS['EXEC_TIME'].$params;
				$thumb = '<br />
					<div align="center">'.$returnLinkTag.'<img src="'.htmlspecialchars($url).'" border="0" title="'.htmlspecialchars(trim($this->file)).'" alt="" /></a></div>';
				$this->content.= $this->doc->section('',$thumb);
			}
		}


			// References:
		$this->content.= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.referencesToThisItem'),$this->makeRef('_FILE',$this->file));
	}

	/**
	 * End page and print content
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Make reference display
	 *
	 * @param	string		Table name
	 * @param	string		Filename or uid
	 * @return	string		HTML
	 */
	function makeRef($table,$ref)	{

		if ($table==='_FILE')	{
				// First, fit path to match what is stored in the refindex:
			$fullIdent = $ref;

			if (t3lib_div::isFirstPartOfStr($fullIdent,PATH_site))	{
				$fullIdent = substr($fullIdent,strlen(PATH_site));
			}

				// Look up the path:
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'sys_refindex',
				'ref_table='.$GLOBALS['TYPO3_DB']->fullQuoteStr('_FILE','sys_refindex').
					' AND ref_string='.$GLOBALS['TYPO3_DB']->fullQuoteStr($fullIdent,'sys_refindex').
					' AND deleted=0'
			);
		} else {
				// Look up the path:
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'sys_refindex',
				'ref_table='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table,'sys_refindex').
					' AND ref_uid='.intval($ref).
					' AND deleted=0'
			);
		}

			// Compile information for title tag:
		$infoData = array();
		if (count($rows))	{
			$infoData[] = '<tr class="t3-row-header">' .
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.table').'</td>' .
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.uid').'</td>' .
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.field').'</td>'.
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.flexpointer').'</td>'.
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.softrefKey').'</td>'.
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.sorting').'</td>'.
					'</tr>';
		}
		foreach($rows as $row)	{
			$infoData[] = '<tr class="bgColor4"">' .
					'<td>'.$row['tablename'].'</td>' .
					'<td>'.$row['recuid'].'</td>' .
					'<td>'.$row['field'].'</td>'.
					'<td>'.$row['flexpointer'].'</td>'.
					'<td>'.$row['softref_key'].'</td>'.
					'<td>'.$row['sorting'].'</td>'.
					'</tr>';
		}

		return count($infoData) ? '<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">' . implode('', $infoData) . '</table>' : '';
	}

	/**
	 * Make reference display (what this elements points to)
	 *
	 * @param	string		Table name
	 * @param	string		Filename or uid
	 * @return	string		HTML
	 */
	function makeRefFrom($table,$ref)	{

			// Look up the path:
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table,'sys_refindex').
				' AND recuid='.intval($ref)
		);

			// Compile information for title tag:
		$infoData = array();
		if (count($rows))	{
			$infoData[] = '<tr class="t3-row-header">' .
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.field').'</td>'.
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.flexpointer').'</td>'.
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.softrefKey').'</td>'.
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.sorting').'</td>'.
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.refTable').'</td>' .
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.refUid').'</td>' .
					'<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.refString').'</td>' .
					'</tr>';
		}
		foreach($rows as $row)	{
			$infoData[] = '<tr class="bgColor4"">' .
					'<td>'.$row['field'].'</td>'.
					'<td>'.$row['flexpointer'].'</td>'.
					'<td>'.$row['softref_key'].'</td>'.
					'<td>'.$row['sorting'].'</td>'.
					'<td>'.$row['ref_table'].'</td>' .
					'<td>'.$row['ref_uid'].'</td>' .
					'<td>'.$row['ref_string'].'</td>' .
					'</tr>';
		}

		return count($infoData) ? '<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">' . implode('', $infoData) . '</table>' : '';
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/show_item.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/show_item.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_show_item');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
