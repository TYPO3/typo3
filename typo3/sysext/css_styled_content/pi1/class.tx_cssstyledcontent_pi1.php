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
 * Plugin 'Content rendering' for the 'css_styled_content' extension.
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   67: class tx_cssstyledcontent_pi1 extends tslib_pibase
 *
 *              SECTION: Rendering of Content Elements:
 *   95:     function render_bullets($content,$conf)
 *  140:     function render_table($content,$conf)
 *  207:     function render_uploads($content,$conf)
 *
 *              SECTION: Helper functions
 *  335:     function getTableAttributes($conf,$type)
 *  364:     function &hookRequest($functionName)
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_tslib.'class.tslib_pibase.php');



/**
 * Plugin class - instantiated from TypoScript.
 * Rendering some content elements from tt_content table.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_cssstyledcontent
 */
class tx_cssstyledcontent_pi1 extends tslib_pibase {

		// Default plugin variables:
	var $prefixId = 'tx_cssstyledcontent_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_cssstyledcontent_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'css_styled_content';		// The extension key.
	var $conf = array();







	/***********************************
	 *
	 * Rendering of Content Elements:
	 *
	 ***********************************/

	/**
	 * Rendering the "Bulletlist" type content element, called from TypoScript (tt_content.bullets.20)
	 *
	 * @param	string		Content input. Not used, ignore.
	 * @param	array		TypoScript configuration
	 * @return	string		HTML output.
	 * @access private
	 */
	function render_bullets($content,$conf)	{

			// Look for hook before running default code for function
		if ($hookObj = &$this->hookRequest('render_bullets'))	{
			return $hookObj->render_bullets($content,$conf);
		} else {

				// Get bodytext field content, returning blank if empty:
			$content = trim($this->cObj->data['bodytext']);
			if (!strcmp($content,''))	return '';

				// Split into single lines:
			$lines = t3lib_div::trimExplode(chr(10),$content);
			while(list($k)=each($lines))	{
				$lines[$k]='
					<li>'.$this->cObj->stdWrap($lines[$k],$conf['innerStdWrap.']).'</li>';
			}

				// Set header type:
			$type = intval($this->cObj->data['layout']);

				// Compile list:
			$out = '
				<ul class="csc-bulletlist csc-bulletlist-'.$type.'">'.
					implode('',$lines).'
				</ul>';

				// Calling stdWrap:
			if ($conf['stdWrap.']) {
				$out = $this->cObj->stdWrap($out, $conf['stdWrap.']);
			}

				// Return value
			return $out;
		}
	}

	/**
	 * Rendering the "Table" type content element, called from TypoScript (tt_content.table.20)
	 *
	 * @param	string		Content input. Not used, ignore.
	 * @param	array		TypoScript configuration
	 * @return	string		HTML output.
	 * @access private
	 */
	function render_table($content,$conf)	{

			// Look for hook before running default code for function
		if ($hookObj = &$this->hookRequest('render_table'))	{
			return $hookObj->render_table($content,$conf);
		} else {
				// Init FlexForm configuration
			$this->pi_initPIflexForm();

				// Get bodytext field content
			$content = trim($this->cObj->data['bodytext']);
			if (!strcmp($content,''))	return '';

				// get flexform values
			$caption = trim(htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_caption')));
			$summary = trim(htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_summary')));
			$useTfoot = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_tfoot'));
			$headerPos = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_headerpos');
			$noStyles = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_nostyles');
			$tableClass = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_tableclass');

			$delimiter = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tableparsing_delimiter','s_parsing'));
			if ($delimiter)	{
				$delimiter = chr(intval($delimiter));
			} else {
				$delimiter = '|';
			}
			$quotedInput = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tableparsing_quote','s_parsing'));
			if ($quotedInput)	{
				$quotedInput = chr(intval($quotedInput));
			} else {
				$quotedInput = '';
			}

				// Split into single lines (will become table-rows):
			$rows = t3lib_div::trimExplode(chr(10),$content);

				// Find number of columns to render:
			$cols = t3lib_div::intInRange($this->cObj->data['cols']?$this->cObj->data['cols']:count(explode($delimiter,current($rows))),0,100);

				// Traverse rows (rendering the table here)
			$rCount = count($rows);
			foreach($rows as $k => $v)	{
				$cells = explode($delimiter,$v);
				$newCells=array();
				for($a=0;$a<$cols;$a++)	{
						// remove quotes if needed
					if ($quotedInput && substr($cells[$a],0,1) == $quotedInput && substr($cells[$a],-1,1) == $quotedInput)	{
						$cells[$a] = substr($cells[$a],1,-1);
					}

					if (!strcmp(trim($cells[$a]),''))	$cells[$a]='&nbsp;';
					$cellAttribs = ($noStyles?'':($a>0 && ($cols-1)==$a) ? ' class="td-last"' : ' class="td-'.$a.'"');
					if (($headerPos == 'top' && !$k) || (!$a && $headerPos == 'left'))	{
						$scope = ' scope="'.($headerPos == 'top'?'col':'row').'"';
						$newCells[$a] = '
							<th'.$cellAttribs.$scope.'>'.$this->cObj->stdWrap($cells[$a],$conf['innerStdWrap.']).'</th>';
					} else {
						$newCells[$a] = '
							<td'.$cellAttribs.'>'.$this->cObj->stdWrap($cells[$a],$conf['innerStdWrap.']).'</td>';
					}
				}
				if (!$noStyles)	{
					$oddEven = $k%2 ? 'tr-odd' : 'tr-even';
					$rowAttribs =  ($k>0 && ($rCount-1)==$k) ? ' class="'.$oddEven.' tr-last"' : ' class="'.$oddEven.' tr-'.$k.'"';
				}
				$rows[$k]='
					<tr'.$rowAttribs.'>'.implode('',$newCells).'
					</tr>';
			}

			$addTbody = 0;
			$tableContents = '';
			if ($caption)	{
				$tableContents .= '
					<caption>'.$caption.'</caption>';
			}
			if ($headerPos == 'top' && $rows[0])	{
				$tableContents .= '<thead>'. $rows[0] .'
					</thead>';
				unset($rows[0]);
				$addTbody = 1;
			}
			if ($useTfoot)	{
				$tableContents .= '
					<tfoot>'.$rows[$rCount-1].'</tfoot>';
				unset($rows[$rCount-1]);
				$addTbody = 1;
			}
			$tmpTable = implode('',$rows);
			if ($addTbody)	{
				$tmpTable = '<tbody>'.$tmpTable.'</tbody>';
			}
			$tableContents .= $tmpTable;

				// Set header type:
			$type = intval($this->cObj->data['layout']);

				// Table tag params.
			$tableTagParams = $this->getTableAttributes($conf,$type);
			if (!$noStyles)	{
				$tableTagParams['class'] = 'contenttable contenttable-'.$type;
			} elseif ($tableClass) {
				$tableTagParams['class'] = $tableClass;
			}


				// Compile table output:
			$out = '
				<table '.t3lib_div::implodeAttributes($tableTagParams).($summary?' summary="'.$summary.'"':'').'>'.	// Omitted xhtmlSafe argument TRUE - none of the values will be needed to be converted anyways, no need to spend processing time on that.
				$tableContents.'
				</table>';

				// Calling stdWrap:
			if ($conf['stdWrap.']) {
				$out = $this->cObj->stdWrap($out, $conf['stdWrap.']);
			}

				// Return value
			return $out;
		}
	}

	/**
	 * Rendering the "Filelinks" type content element, called from TypoScript (tt_content.uploads.20)
	 *
	 * @param	string		Content input. Not used, ignore.
	 * @param	array		TypoScript configuration
	 * @return	string		HTML output.
	 * @access private
	 */
	function render_uploads($content,$conf)	{

			// Look for hook before running default code for function
		if ($hookObj = &$this->hookRequest('render_uploads'))	{
			return $hookObj->render_uploads($content,$conf);
		} else {

			$out = '';

				// Set layout type:
			$type = intval($this->cObj->data['layout']);

				// Get the list of files (using stdWrap function since that is easiest)
			$lConf = array();
			$lConf['override.']['filelist.']['field'] = 'select_key';
			$fileList = $this->cObj->stdWrap($this->cObj->data['media'],$lConf);

				// Explode into an array:
			$fileArray = t3lib_div::trimExplode(',',$fileList,1);

				// If there were files to list...:
			if (count($fileArray))	{

					// Get the path from which the images came:
				$selectKeyValues = explode('|',$this->cObj->data['select_key']);
				$path = trim($selectKeyValues[0]) ? trim($selectKeyValues[0]) : 'uploads/media/';

					// Get the descriptions for the files (if any):
				$descriptions = t3lib_div::trimExplode(chr(10),$this->cObj->data['imagecaption']);

					// Adding hardcoded TS to linkProc configuration:
				$conf['linkProc.']['path.']['current'] = 1;
				$conf['linkProc.']['icon'] = 1;	// Always render icon - is inserted by PHP if needed.
				$conf['linkProc.']['icon.']['wrap'] = ' | //**//';	// Temporary, internal split-token!
				$conf['linkProc.']['icon_link'] = 1;	// ALways link the icon
				$conf['linkProc.']['icon_image_ext_list'] = ($type==2 || $type==3) ? $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] : '';	// If the layout is type 2 or 3 we will render an image based icon if possible.

					// Traverse the files found:
				$filesData = array();
				foreach($fileArray as $key => $fileName)	{
					$absPath = t3lib_div::getFileAbsFileName($path.$fileName);
					if (@is_file($absPath))	{
						$fI = pathinfo($fileName);
						$filesData[$key] = array();

						$filesData[$key]['filename'] = $fileName;
						$filesData[$key]['path'] = $path;
						$filesData[$key]['filesize'] = filesize($absPath);
						$filesData[$key]['fileextension'] = strtolower($fI['extension']);
						$filesData[$key]['description'] = trim($descriptions[$key]);

						$this->cObj->setCurrentVal($path);
						$GLOBALS['TSFE']->register['ICON_REL_PATH'] = $path.$fileName;
						$filesData[$key]['linkedFilenameParts'] = explode('//**//',$this->cObj->filelink($fileName, $conf['linkProc.']));
					}
				}

					// Now, lets render the list!
				$tRows = array();
				foreach($filesData as $key => $fileD)	{

						// Setting class of table row for odd/even rows:
					$oddEven = $key%2 ? 'tr-odd' : 'tr-even';

						// Render row, based on the "layout" setting
					$tRows[]='
					<tr class="'.$oddEven.'">'.($type>0 ? '
						<td class="csc-uploads-icon">
							'.$fileD['linkedFilenameParts'][0].'
						</td>' : '').'
						<td class="csc-uploads-fileName">
							<p>'.$fileD['linkedFilenameParts'][1].'</p>'.
							($fileD['description'] ? '
							<p class="csc-uploads-description">'.htmlspecialchars($fileD['description']).'</p>' : '').'
						</td>'.($this->cObj->data['filelink_size'] ? '
						<td class="csc-uploads-fileSize">
							<p>'.t3lib_div::formatSize($fileD['filesize']).'</p>
						</td>' : '').'
					</tr>';
				}

					// Table tag params.
				$tableTagParams = $this->getTableAttributes($conf,$type);
				$tableTagParams['class'] = 'csc-uploads csc-uploads-'.$type;


					// Compile it all into table tags:
				$out = '
				<table '.t3lib_div::implodeAttributes($tableTagParams).'>
					'.implode('',$tRows).'
				</table>';
			}

				// Calling stdWrap:
			if ($conf['stdWrap.']) {
				$out = $this->cObj->stdWrap($out, $conf['stdWrap.']);
			}

				// Return value
			return $out;
		}
	}













	/************************************
	 *
	 * Helper functions
	 *
	 ************************************/

	/**
	 * Returns table attributes for uploads / tables.
	 *
	 * @param	array		TypoScript configuration array
	 * @param	integer		The "layout" type
	 * @return	array		Array with attributes inside.
	 */
	function getTableAttributes($conf,$type)	{

			// Initializing:
		$tableTagParams_conf = $conf['tableParams_'.$type.'.'];

		$conf['color.'][200] = '';
		$conf['color.'][240] = 'black';
		$conf['color.'][241] = 'white';
		$conf['color.'][242] = '#333333';
		$conf['color.'][243] = 'gray';
		$conf['color.'][244] = 'silver';

			// Create table attributes array:
		$tableTagParams = array();
		$tableTagParams['border'] =  $this->cObj->data['table_border'] ? intval($this->cObj->data['table_border']) : $tableTagParams_conf['border'];
		$tableTagParams['cellspacing'] =  $this->cObj->data['table_cellspacing'] ? intval($this->cObj->data['table_cellspacing']) : $tableTagParams_conf['cellspacing'];
		$tableTagParams['cellpadding'] =  $this->cObj->data['table_cellpadding'] ? intval($this->cObj->data['table_cellpadding']) : $tableTagParams_conf['cellpadding'];
		$tableTagParams['bgcolor'] =  isset($conf['color.'][$this->cObj->data['table_bgColor']]) ? $conf['color.'][$this->cObj->data['table_bgColor']] : $conf['color.']['default'];

			// Return result:
		return $tableTagParams;
	}

	/**
	 * Returns an object reference to the hook object if any
	 *
	 * @param	string		Name of the function you want to call / hook key
	 * @return	object		Hook object, if any. Otherwise null.
	 */
	function &hookRequest($functionName)	{
		global $TYPO3_CONF_VARS;

			// Hook: menuConfig_preProcessModMenu
		if ($TYPO3_CONF_VARS['EXTCONF']['css_styled_content']['pi1_hooks'][$functionName]) {
			$hookObj = &t3lib_div::getUserObj($TYPO3_CONF_VARS['EXTCONF']['css_styled_content']['pi1_hooks'][$functionName]);
			if (method_exists ($hookObj, $functionName)) {
				$hookObj->pObj = &$this;
				return $hookObj;
			}
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/css_styled_content/pi1/class.tx_cssstyledcontent_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/css_styled_content/pi1/class.tx_cssstyledcontent_pi1.php']);
}
?>