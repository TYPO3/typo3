<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * Document for viewing the online help texts, also known as TCA_DESCR.
 * See Inside TYPO3 for details.
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML-trans compliant
 */


require ('init.php');
require ('template.php');
include ('sysext/lang/locallang_view_help.php');



// ***************************
// Script Class
// ***************************
class SC_view_help {
	var $allowedHTML = '<strong><em><b><i>';

	var $content;	// Content accumulation.

		// For these vars, see init()
	var $limitAccess;	// If set access to fields and tables is checked. Should be done for true database tables.
	var $table;			// The "table" key
	var $field;			// The "field" key
	
	/**
	 * Initialize
	 */
	function init()	{
		global $LANG;

			// Set internal table/field to the parts of "tfID" incoming var.
		list($this->table,$this->field)=explode('.',t3lib_div::GPvar('tfID'));

			// Load descriptions for table $this->table
		$LANG->loadSingleTableDescription($this->table);
		
			// limitAccess is checked if the $this->table really IS a table.
		$this->limitAccess = !isset($TCA[$this->table]) ? 0 : 1;
	}
	
	/**
	 * Main 
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		global $TBE_TEMPLATE;
		
			// Start HTML output accumulation:
		$TBE_TEMPLATE->docType='xhtml_trans';
		$TBE_TEMPLATE->divClass='typo3-view-help';
		$this->content.=$TBE_TEMPLATE->startPage($LANG->getLL('title'));

			// If ALL fields is supposed to be shown:		
		if ($this->field=='*')	{
				// Load table TCA
			t3lib_div::loadTCA($this->table);

			if (is_array($TCA_DESCR[$this->table]['columns']) && (!$this->limitAccess || $BE_USER->check('tables_select',$this->table)))	{
					// Traverse table columns as listed in TCA_DESCR
				$parts=array();
				$parts[0]='';	// Reserved for header of table
				reset($TCA_DESCR[$this->table]['columns']);
				while(list($this->field)=each($TCA_DESCR[$this->table]['columns']))	{
					$fieldValue = isset($TCA[$this->table]) && strcmp($this->field,"") ? $TCA[$this->table]['columns'][$this->field] : array();

					if (is_array($fieldValue) && (!$this->limitAccess || !$fieldValue['exclude'] || $BE_USER->check('non_exclude_fields',$this->table.':'.$this->field)))	{
						if (!$this->field)	{
							$parts[0]=$this->printItem($this->table,'',1);
						} else {
							$parts[]=$this->printItem($this->table,$this->field,1);
						}
					}
				}
				
				if (!strcmp($parts,""))	unset($parts[0]);
				$this->content.= implode('<br />',$parts);
			}
		} else {	
				// ... otherwise show only single field:
			$this->content.=$this->printItem($this->table,$this->field);

				// Link to Full table description:
			$onClick = 'vHWin=window.open(\'view_help.php?tfID='.rawurlencode($this->table.'.*').'&ONLY='.$LANG->lang.'\',\'fullHelpWindow\',\'width=600,scrollbars=1,status=1,menubar=1,location=1,resizable=1,toolbar=1\');vHWin.focus();return false;';
			$this->content.='<br /><p><a href="#" onclick="'.htmlspecialchars($onClick).'">'.htmlspecialchars($LANG->getLL('fullDescription')).'</a></p>';
		}

			// Print close-button:
		$this->content.='<br /><form action=""><input type="submit" value="'.htmlspecialchars($LANG->getLL('close')).'" onclick="self.close(); return false;" /></form>';

			// End page:
		$this->content.=$TBE_TEMPLATE->endPage();
	}
	
	/**
	 * Print output
	 */
	function printContent()	{
		echo $this->content;
	}
	
	/**
	 * Make seeAlso links from $value
	 * If $anchorTable is set to a tablename, then references to this table will be made as anchors, not URLs.
	 */
	function make_seeAlso($value,$anchorTable='')	{
		global $TCA,$BE_USER;
			// Split references by comma, vert.line or linebreak
		$items = split(',|'.chr(10),$value);
		reset($items);
		$lines=array();
		while(list(,$val)=each($items))	{
			$val = trim($val);
			if ($val)	{
				$iP = explode(':',$val);
				$iPUrl = t3lib_div::trimExplode('|',$val);
					// URL reference:
				if (substr($iPUrl[1],0,4)=='http')	{
					$lines[]='<a href="'.htmlspecialchars($iPUrl[1]).'" target="_blank"><em>'.htmlspecialchars($iPUrl[0]).'</em></a>';
				} else {
					// "table" reference	
					t3lib_div::loadTCA($iP[0]);
					if (!isset($TCA[$iP[0]]) || (is_array($TCA[$iP[0]]['columns'][$iP[1]]) && (!$this->limitAccess || ($BE_USER->check('tables_select',$iP[0]) && (!$TCA[$iP[0]]['columns'][$iP[1]]['exclude'] || $BE_USER->check('non_exclude_fields',$iP[0].':'.$iP[1]))))))	{	// Checking read access:
						list($tableName,$fieldName) = $this->getTableFieldNames($iP[0],$iP[1]);

							// Make see-also link:
						$href = ($anchorTable&&$iP[0]==$anchorTable ? '#'.implode('.',$iP) : 'view_help.php?tfID='.rawurlencode(implode('.',$iP)).'&back='.t3lib_div::GPvar('tfID'));
						$label = $GLOBALS['LANG']->sL($tableName).($iP[1]?' / '.ereg_replace(':$','',$GLOBALS['LANG']->sL($fieldName)):'');
						$lines[]='<a href="'.htmlspecialchars($href).'">'.htmlspecialchars($label).'</a>';
					}
				}
			}
		}
		return implode('<br />',$lines);
	}
	
	/**
	 * Will return an image tag with description in italics.
	 */
	function printImage($image,$descr)	{
		$absImagePath = t3lib_div::getFileAbsFileName($image,1,1);
		if ($absImagePath && @is_file($absImagePath))	{
			$imgFile = substr($absImagePath,strlen(PATH_site));
			$imgInfo=@getimagesize($absImagePath);
			if (is_array($imgInfo))	{
				$code = '<br /><img src="../'.$imgFile.'" '.$imgInfo[3].' alt="" /><br />
				';
				$code.= '<p><em>'.$GLOBALS['LANG']->hscAndCharConv($descr,0).'</em></p>
				';
				return $code;
			}
		}
	}

	/**
	 * Returns header
	 */
	function headerLine($str,$type=0)	{
		switch($type)	{
			case 1:
				$str='<h3>'.htmlspecialchars($str).'</h3>
				';
			break;
			case 0:
				$str='<h4>'.htmlspecialchars(t3lib_div::danish_strtoupper($str)).'</h4>
				';
			break;
		}
		
		return $str;
	}
	
	/**
	 * Returns prepared content
	 */
	function prepareContent($str)	{
		$str = $GLOBALS['LANG']->hscAndCharConv($str,0);
		return '<p>'.nl2br(trim(strip_tags($str,$this->allowedHTML))).'</p>
		';
	}
	
	/**
	 * Prints a single $table/$field information piece
	 * If $anchors is set, then seeAlso references to the same table will be page-anchors, not links.
	 */
	function printItem($table,$field,$anchors=0)	{
		global $TCA_DESCR, $LANG, $TCA, $BE_USER;

			// Load full table definition in $TCA
		t3lib_div::loadTCA($table);

		if ($table && (!$field || is_array($TCA_DESCR[$table]['columns'][$field])))	{
				// Make seeAlso references.
			$seeAlsoRes = $this->make_seeAlso($TCA_DESCR[$table]['columns'][$field]['seeAlso'],$anchors?$table:'');
			
				// Get Human Readable table and field labels
			list($tableName,$fieldName) = $this->getTableFieldNames($table,$field);

				// Making item:
			$out= '<a name="'.$table.'.'.$field.'"></a>
					'.
					$this->headerLine($LANG->sL($tableName).': '.($field?ereg_replace(':$','',stripslashes(trim($LANG->sL($fieldName)))):''),1).
					$this->prepareContent($TCA_DESCR[$table]['columns'][$field]['description']).
					($TCA_DESCR[$table]['columns'][$field]['details'] ? $this->headerLine($LANG->getLL('details').':').$this->prepareContent($TCA_DESCR[$table]['columns'][$field]['details']) : '').
					($TCA_DESCR[$table]['columns'][$field]['syntax'] ? $this->headerLine($LANG->getLL('syntax').':').$this->prepareContent($TCA_DESCR[$table]['columns'][$field]['syntax']) : '').
					($TCA_DESCR[$table]['columns'][$field]['image'] ? $this->printImage($TCA_DESCR[$table]['columns'][$field]['image'],$TCA_DESCR[$table]['columns'][$field]['image_descr']) : '').
					($TCA_DESCR[$table]['columns'][$field]['seeAlso'] && $seeAlsoRes ? $this->headerLine($LANG->getLL('seeAlso').':').'<p>'.$seeAlsoRes.'</p>' : '').
					(t3lib_div::GPvar('back') ? '<br /><p><a href="'.htmlspecialchars('view_help.php?tfID='.rawurlencode(t3lib_div::GPvar('back'))).'" class="typo3-goBack">'.htmlspecialchars($LANG->getLL('goBack')).'</a></p>' : '').
			'<br />';
		}
		return $out;
	}
	
	/**
	 * Returns labels for $table and $field.
	 * If $table is "_MOD_" prefixed, the part after "_MOD_" is returned (non-tables, fx. modules)
	 */
	function getTableFieldNames($table,$field)	{
		global $TCA, $TCA_DESCR;
			$tableName = is_array($TCA_DESCR[$table]['columns']['']) && $TCA_DESCR[$table]['columns']['']['alttitle'] ? 
							$TCA_DESCR[$table]['columns']['']['alttitle'] : 
							(isset($TCA[$table]) ? $TCA[$table]['ctrl']['title'] : ereg_replace('^_MOD_','',$table));
			$fieldName = is_array($TCA_DESCR[$table]['columns'][$field]) && $TCA_DESCR[$table]['columns'][$field]['alttitle'] ? 
							$TCA_DESCR[$table]['columns'][$field]['alttitle'] : 
							(isset($TCA[$table])&&isset($TCA[$table]['columns'][$field]) ? $TCA[$table]['columns'][$field]['label'] : $field);
		return array($tableName,$fieldName);
	}
}


// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/view_help.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/view_help.php']);
}






// Make instance:
$SOBE = t3lib_div::makeInstance('SC_view_help');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>