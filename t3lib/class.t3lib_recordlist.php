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
 * Library with a single function addElement that returns tablerows based on some input.
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   78: class t3lib_recordList 
 *  119:     function addElement($h,$icon,$data,$tdParams='',$lMargin='',$altLine='')	
 *  193:     function writeTop()	
 *  201:     function writeBottom()	
 *  220:     function fwd_rwd_nav($table='')	
 *  253:     function fwd_rwd_HTML($type,$pointer,$table='')	
 *  276:     function listURL()	
 *  285:     function CBfunctions()	
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
















/**
 * This class is the base for listing of database records and files in the modules Web>List and File>Filelist
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see typo3/db_list.php, typo3/file_list.php
 */
class t3lib_recordList {

		// Used in this class:
	var $iLimit = 10;						// default Max items shown 
	var $leftMargin = 0;					// OBSOLETE - NOT USED ANYMORE. leftMargin
	var $showIcon = 1;
	var $no_noWrap = 0;
	var $oddColumnsTDParams ='';			// If set this is <td>-params for odd columns in addElement. Used with db_layout / pages section
	var $backPath='';	
	var $fieldArray = Array();				// Decides the columns shown. Filled with values that refers to the keys of the data-array. $this->fieldArray[0] is the title column.
	var $addElement_tdParams=array();		// Keys are fieldnames and values are td-parameters to add in addElement();

		// Not used in this class - but maybe extension classes...
	var $fixedL = 50;						// Max length of strings
	var $script = '';
	var $thumbScript = 'thumbs.php';
	var $setLMargin=1;						// Set to zero, if you don't want a left-margin with addElement function	

	var $counter=0;							// Counter increased for each element. Used to index elements for the JavaScript-code that transfers to the clipboard
	var $totalItems = '';					// This could be set to the total number of items. Used by the fwd_rew_navigation...

		// Internal (used in this class.)
	var $firstElementNumber=0;
	var $eCounter=0;
	var $HTMLcode='';			// String with accumulated HTML content



	
	/**
	 * Returns a table-row with the content from the fields in the input data array.
	 * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
	 * 
	 * @param	integer		$h is an integer >=0 and denotes how tall a element is. Set to '0' makes a halv line, -1 = full line, set to 1 makes a 'join' and above makes 'line'
	 * @param	string		$icon is the <img>+<a> of the record. If not supplied the first 'join'-icon will be a 'line' instead
	 * @param	array		$data is the dataarray, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
	 * @param	string		$tdParams is insert in the <td>-tags. Must carry a ' ' as first character
	 * @param	integer		OBSOLETE - NOT USED ANYMORE. $lMargin is the leftMargin (integer)
	 * @param	string		$altLine is the HTML <img>-tag for an alternative 'gfx/ol/line.gif'-icon (used in the top)
	 * @return	string		HTML content for the table row
	 */
	function addElement($h,$icon,$data,$tdParams='',$lMargin='',$altLine='')	{
		$noWrap = ($this->no_noWrap) ? '' : ' nowrap="nowrap"';

			// Start up:		
		$out='
		<!-- Element, begin: -->
		<tr>';
			// Show icon and lines
		if ($this->showIcon)	{
			$out.='
			<td nowrap="nowrap"'.$tdParams.'>';
			
			if (!$h)	{
				$out.='<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/halfline.gif','width="18" height="8"').' alt="" />';
			} else {
				for ($a=0;$a<$h;$a++)	{
					if (!$a)	{
						$out.= $altLine ? $altLine : '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/line.gif','width="18" height="16"').' alt="" />';
						if ($icon)	$out.= $icon;
					} else {
						$out.= $altLine ? $altLine :'<br /><img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/line.gif','width="18" height="16"').' alt="" />';
					}
				}
			}
			$out.='</td>
			';
		}

			// Init rendering.		
		$colsp='';
		$lastKey='';
		$c=0;
		$ccount=0;
		$tdP[0]= $this->oddColumnsTDParams ? $this->oddColumnsTDParams : $tdParams;
		$tdP[1]=$tdParams;

			// Traverse field array which contains the data to present:
		reset($this->fieldArray);
		while(list(,$vKey)=each($this->fieldArray))	{
			if (isset($data[$vKey]))	{
				if ($lastKey)	{	
					$out.='
						<td'.
						$noWrap.
						$tdP[($ccount%2)].
						$colsp.
						$this->addElement_tdParams[$lastKey].
						'>'.$data[$lastKey].'</td>';	
				}
				$lastKey=$vKey;
				$c=1;
				$ccount++;
			} else {
				if (!$lastKey) {$lastKey=$vKey;}
				$c++;
			}
			if ($c>1)	{$colsp=' colspan="'.$c.'"';} else {$colsp='';}
		}
		if ($lastKey)	{	$out.='
						<td'.$noWrap.$tdP[($ccount%2)].$colsp.$this->addElement_tdParams[$lastKey].'>'.$data[$lastKey].'</td>';	}

			// End row
		$out.='
		</tr>';

			// Return row.
		return $out;
	}
	
	/**
	 * Dummy function, used to write the top of a table listing.
	 * 
	 * @return	void		
	 */
	function writeTop()	{
	}
	
	/**
	 * Finishes the list with the "stopper"-gif, adding the HTML code for that item to the internal ->HTMLcode string
	 * 
	 * @return	void		
	 */
	function writeBottom()	{
		$this->HTMLcode.='
		
		<!--
			End of list table:
		-->
		<table border="0" cellpadding="0" cellspacing="0">';
		$theIcon='<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/stopper.gif','width="18" height="16"').' alt="" />';
		$this->HTMLcode.=$this->addElement(1,'','','',$this->leftMargin,$theIcon);
		$this->HTMLcode.='
		</table>';	
	}
	
	/**
	 * Creates a forward/reverse button based on the status of ->eCounter, ->firstElementNumber, ->iLimit
	 * 
	 * @param	string		Table name
	 * @return	array		array([boolean], [HTML]) where [boolean] is 1 for reverse element, [HTML] is the table-row code for the element
	 */
	function fwd_rwd_nav($table='')	{
		$code='';
		if ($this->eCounter >= $this->firstElementNumber   &&   $this->eCounter < $this->firstElementNumber+$this->iLimit)	{
			if ($this->firstElementNumber && $this->eCounter==$this->firstElementNumber)	{
					// 	reverse
				$theData = Array();
				$titleCol=$this->fieldArray[0];
				$theData[$titleCol] = $this->fwd_rwd_HTML('fwd',$this->eCounter,$table);
				$code=$this->addElement(1,'',$theData);
			}
			return Array(1,$code);
		} else {
			if ($this->eCounter==$this->firstElementNumber+$this->iLimit)	{
					// 	forward
				$theData = Array();
				$titleCol=$this->fieldArray[0];
				$theData[$titleCol] = $this->fwd_rwd_HTML('rwd',$this->eCounter,$table);
				$code=$this->addElement(1,'',$theData);
			}
			return Array(0,$code);
		}
		
	}
	
	/**
	 * Creates the button with link to either forward or reverse
	 * 
	 * @param	string		Type: "fwd" or "rwd"
	 * @param	integer		Pointer
	 * @param	string		Table name
	 * @return	string		
	 * @access private
	 */
	function fwd_rwd_HTML($type,$pointer,$table='')	{
		$tParam = $table ? '&table='.rawurlencode($table) : '';
		switch($type)	{
			case 'fwd':
				$href = $this->listURL().'&pointer='.($pointer-$this->iLimit).$tParam;
				return '<a href="'.htmlspecialchars($href).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/pilup.gif','width="14" height="14"').' alt="" />'.
						'</a> <i>[1 - '.$pointer.']</i>';
			break;
			case 'rwd':
				$href = $this->listURL().'&pointer='.$pointer.$tParam;
				return '<a href="'.htmlspecialchars($href).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/pildown.gif','width="14" height="14"').' alt="" />'.
						'</a> <i>['.($pointer+1).' - '.$this->totalItems.']</i>';
			break;
		}
	}
	
	/**
	 * Returning "listURL" - the script with parameters to which forward/backward requests are sent
	 * 
	 * @return	string		
	 */
	function listURL()	{
		return 'dummy.php?';
	}
	
	/**
	 * Returning JavaScript for ClipBoard functionality.
	 * 
	 * @return	string		
	 */
	function CBfunctions()	{
		return '
		// checkOffCB()
	function checkOffCB(listOfCBnames)	{	//
		var notChecked=0;
		var total=0;
		
			// Checking how many is checked, how many is not
		var pointer=0;
		var pos = listOfCBnames.indexOf(",");
		while (pos!=-1)	{
			if (!cbValue(listOfCBnames.substr(pointer,pos-pointer))) notChecked++;
			total++;
			pointer=pos+1;
			pos = listOfCBnames.indexOf(",",pointer);
		}
		if (!cbValue(listOfCBnames.substr(pointer))) notChecked++;
		total++;

			// Setting the status...		
		var flag = notChecked*2>total;
		pointer=0;
		pos = listOfCBnames.indexOf(",");
		while (pos!=-1)	{
			setcbValue(listOfCBnames.substr(pointer,pos-pointer),flag);

			pointer=pos+1;
			pos = listOfCBnames.indexOf(",",pointer);
		}
		setcbValue(listOfCBnames.substr(pointer),flag);
	}
		// cbValue()
	function cbValue(CBname)	{	//
		var CBfullName = "CBC["+CBname+"]";
		return (document.dblistForm[CBfullName] && document.dblistForm[CBfullName].checked ? 1 : 0);
	}
		// setcbValue()
	function setcbValue(CBname,flag)	{	//
		CBfullName = "CBC["+CBname+"]";
		document.dblistForm[CBfullName].checked = flag ? "on" : 0;
	}
		
		';	
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_recordlist.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_recordlist.php']);
}
?>