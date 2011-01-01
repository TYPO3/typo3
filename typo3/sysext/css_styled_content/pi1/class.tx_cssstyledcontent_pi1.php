<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 *   68: class tx_cssstyledcontent_pi1 extends tslib_pibase
 *
 *              SECTION: Rendering of Content Elements:
 *   96:     function render_bullets($content,$conf)
 *  141:     function render_table($content,$conf)
 *  283:     function render_uploads($content,$conf)
 *  395:     function render_textpic($content, $conf)
 *
 *              SECTION: Helper functions
 *  832:     function getTableAttributes($conf,$type)
 *  861:     function &hookRequest($functionName)
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
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
		if ($hookObj = $this->hookRequest('render_bullets')) {
			return $hookObj->render_bullets($content,$conf);
		} else {

				// Get bodytext field content, returning blank if empty:
			$field = (isset($conf['field']) && trim($conf['field']) ? trim($conf['field']) : 'bodytext');
			$content = trim($this->cObj->data[$field]);
			if (!strcmp($content,''))	return '';

				// Split into single lines:
			$lines = t3lib_div::trimExplode(LF,$content);
			foreach($lines as &$val)	{
				$val = '<li>'.$this->cObj->stdWrap($val,$conf['innerStdWrap.']).'</li>';
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
		if ($hookObj = $this->hookRequest('render_table')) {
			return $hookObj->render_table($content,$conf);
		} else {
				// Init FlexForm configuration
			$this->pi_initPIflexForm();

				// Get bodytext field content
			$field = (isset($conf['field']) && trim($conf['field']) ? trim($conf['field']) : 'bodytext');
			$content = trim($this->cObj->data[$field]);
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

				// generate id prefix for accessible header
			$headerScope = ($headerPos=='top'?'col':'row');
			$headerIdPrefix = $headerScope.$this->cObj->data['uid'].'-';

				// Split into single lines (will become table-rows):
			$rows = t3lib_div::trimExplode(LF,$content);
			reset($rows);

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
					$cellAttribs = ($noStyles?'':($a>0 && ($cols-1)==$a) ? ' class="td-last td-'.$a.'"' : ' class="td-'.$a.'"');
					if (($headerPos == 'top' && !$k) || ($headerPos == 'left' && !$a))	{
						$scope = ' scope="'.$headerScope.'"';
						$scope .= ' id="'.$headerIdPrefix.(($headerScope=='col')?$a:$k).'"';

						$newCells[$a] = '
							<th'.$cellAttribs.$scope.'>'.$this->cObj->stdWrap($cells[$a],$conf['innerStdWrap.']).'</th>';
					} else {
						if (empty($headerPos))	{
							$accessibleHeader = '';
						} else {
							$accessibleHeader = ' headers="'.$headerIdPrefix.(($headerScope=='col')?$a:$k).'"';
						}
						$newCells[$a] = '
							<td'.$cellAttribs.$accessibleHeader.'>'.$this->cObj->stdWrap($cells[$a],$conf['innerStdWrap.']).'</td>';
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
				$tableTagParams['class'] = 'contenttable contenttable-'.$type.($tableClass?' '.$tableClass:'');
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
		if ($hookObj = $this->hookRequest('render_uploads')) {
			return $hookObj->render_uploads($content,$conf);
		} else {

			$out = '';

				// Set layout type:
			$type = intval($this->cObj->data['layout']);

				// see if the file path variable is set, this takes precedence
			$filePathConf = $this->cObj->stdWrap($conf['filePath'], $conf['filePath.']);
			if ($filePathConf) {
				$fileList = $this->cObj->filelist($filePathConf);
				list($path) = explode('|', $filePathConf);
			} else {
					// Get the list of files from the field
				$field = (trim($conf['field']) ? trim($conf['field']) : 'media');
				$fileList = $this->cObj->data[$field];
				t3lib_div::loadTCA('tt_content');
				$path = 'uploads/media/';
				if (is_array($GLOBALS['TCA']['tt_content']['columns'][$field]) && !empty($GLOBALS['TCA']['tt_content']['columns'][$field]['config']['uploadfolder'])) {
					// in TCA-Array folders are saved without trailing slash, so $path.$fileName won't work
				    $path = $GLOBALS['TCA']['tt_content']['columns'][$field]['config']['uploadfolder'] .'/';
				}
			}
			$path = trim($path);

				// Explode into an array:
			$fileArray = t3lib_div::trimExplode(',',$fileList,1);

				// If there were files to list...:
			if (count($fileArray))	{

					// Get the descriptions for the files (if any):
				$descriptions = t3lib_div::trimExplode(LF,$this->cObj->data['imagecaption']);

					// Adding hardcoded TS to linkProc configuration:
				$conf['linkProc.']['path.']['current'] = 1;
				$conf['linkProc.']['icon'] = 1;	// Always render icon - is inserted by PHP if needed.
				$conf['linkProc.']['icon.']['wrap'] = ' | //**//';	// Temporary, internal split-token!
				$conf['linkProc.']['icon_link'] = 1;	// ALways link the icon
				$conf['linkProc.']['icon_image_ext_list'] = ($type==2 || $type==3) ? $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] : '';	// If the layout is type 2 or 3 we will render an image based icon if possible.
				if ($conf['labelStdWrap.']) {
					$conf['linkProc.']['labelStdWrap.'] = $conf['labelStdWrap.'];
				}

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
						$GLOBALS['TSFE']->register['filename'] = $filesData[$key]['filename'];
						$GLOBALS['TSFE']->register['path'] = $filesData[$key]['path'];
						$GLOBALS['TSFE']->register['fileSize'] = $filesData[$key]['filesize'];
						$GLOBALS['TSFE']->register['fileExtension'] = $filesData[$key]['fileextension'];
						$GLOBALS['TSFE']->register['description'] = $filesData[$key]['description'];
						$filesData[$key]['linkedFilenameParts'] = explode('//**//',$this->cObj->filelink($fileName, $conf['linkProc.']));
					}
				}

					// optionSplit applied to conf to allow differnt settings per file
				$splitConf = $GLOBALS['TSFE']->tmpl->splitConfArray($conf, count($filesData));

					// Now, lets render the list!
				$outputEntries = array();
				foreach($filesData as $key => $fileData)	{
					$GLOBALS['TSFE']->register['linkedIcon'] = $fileData['linkedFilenameParts'][0];
					$GLOBALS['TSFE']->register['linkedLabel'] = $fileData['linkedFilenameParts'][1];
					$GLOBALS['TSFE']->register['filename'] = $fileData['filename'];
					$GLOBALS['TSFE']->register['path'] = $fileData['path'];
					$GLOBALS['TSFE']->register['description'] = $fileData['description'];
					$GLOBALS['TSFE']->register['fileSize'] = $fileData['filesize'];
					$GLOBALS['TSFE']->register['fileExtension'] = $fileData['fileextension'];
					$outputEntries[] = $this->cObj->cObjGetSingle($splitConf[$key]['itemRendering'], $splitConf[$key]['itemRendering.']);
				}

				if (isset($conf['outerWrap']))	{
						// Wrap around the whole content
					$outerWrap = $conf['outerWrap'];
				} else	{
						// Table tag params
					$tableTagParams = $this->getTableAttributes($conf,$type);
					$tableTagParams['class'] = 'csc-uploads csc-uploads-'.$type;
					$outerWrap = '<table ' . t3lib_div::implodeAttributes($tableTagParams) . '>|</table>';
				}

					// Compile it all into table tags:
				$out = $this->cObj->wrap(implode('', $outputEntries), $outerWrap);
			}

				// Calling stdWrap:
			if ($conf['stdWrap.']) {
				$out = $this->cObj->stdWrap($out, $conf['stdWrap.']);
			}

				// Return value
			return $out;
		}
	}

	/**
	 * returns an array containing width relations for $colCount columns.
	 * 
	 * tries to use "colRelations" setting given by TS.
	 * uses "1:1" column relations by default. 
	 *
	 * @param array $conf TS configuration for img
	 * @param int $colCount number of columns
	 * @return array
	 */
	protected function getImgColumnRelations($conf, $colCount) {
		$relations = array();
		$equalRelations= array_fill(0, $colCount, 1);
		$colRelationsTypoScript = trim($this->cObj->stdWrap($conf['colRelations'], $conf['colRelations.']));

		if ($colRelationsTypoScript) {
				// try to use column width relations given by TS
			$relationParts = explode(':', $colRelationsTypoScript);
				// enough columns defined?
			if (count($relationParts) >= $colCount) {
				$out = array();
				for ($a = 0; $a < $colCount; $a++) {
					$currentRelationValue = intval($relationParts[$a]);
					if ($currentRelationValue >= 1) {
						$out[$a] = $currentRelationValue;
					} else {
						t3lib_div::devLog('colRelations used with a value smaller than 1 therefore colRelations setting is ignored.', $this->extKey, 2);
						unset($out);
						break;
					}
				}
				if (max($out) / min($out) <= 10) {
					$relations = $out;
				} else {
					t3lib_div::devLog('The difference in size between the largest and smallest colRelation was not within a factor of ten therefore colRelations setting is ignored..', $this->extKey, 2);
				}
			}
		}
		return $relations ? $relations : $equalRelations;
	}
	
	/**
	 * returns an array containing the image widths for an image row with $colCount columns.
	 *
	 * @param array $conf TS configuration of img
	 * @param int $colCount number of columns
	 * @param int $netW max usable width for images (without spaces and borders)
	 * @return array
	 */
	protected function getImgColumnWidths($conf, $colCount, $netW) {
		$columnWidths = array();
		$colRelations = $this->getImgColumnRelations($conf, $colCount);
		
		$accumWidth = 0;
		$accumDesiredWidth = 0;
		$relUnitCount = array_sum($colRelations);
		
		for ($a = 0; $a < $colCount; $a++)	{
			$availableWidth = $netW - $accumWidth; // this much width is available for the remaining images in this row (int)
			$desiredWidth = $netW / $relUnitCount * $colRelations[$a]; // theoretical width of resized image. (float)
			$accumDesiredWidth += $desiredWidth; // add this width. $accumDesiredWidth becomes the desired horizontal position 
				// calculate width by comparing actual and desired horizontal position.
				// this evenly distributes rounding errors across all images in this row. 
			$suggestedWidth = round($accumDesiredWidth - $accumWidth);
			$finalImgWidth = (int) min($availableWidth, $suggestedWidth); // finalImgWidth may not exceed $availableWidth
			$accumWidth += $finalImgWidth;
			$columnWidths[$a] = $finalImgWidth;
		}
		return $columnWidths;
	}
	
	/**
	 * Rendering the IMGTEXT content element, called from TypoScript (tt_content.textpic.20)
	 *
	 * @param	string		Content input. Not used, ignore.
	 * @param	array		TypoScript configuration. See TSRef "IMGTEXT". This function aims to be compatible.
	 * @return	string		HTML output.
	 * @access private
	 * @coauthor	Ernesto Baschny <ernst@cron-it.de>
	 */
	 function render_textpic($content, $conf)	{
			// Look for hook before running default code for function
		if (method_exists($this, 'hookRequest') && $hookObj = $this->hookRequest('render_textpic')) {
			return $hookObj->render_textpic($content,$conf);
		}

		$renderMethod = $this->cObj->stdWrap($conf['renderMethod'], $conf['renderMethod.']);

			// Render using the default IMGTEXT code (table-based)
		if (!$renderMethod || $renderMethod == 'table')	{
			return $this->cObj->IMGTEXT($conf);
		}

			// Specific configuration for the chosen rendering method
		if (is_array($conf['rendering.'][$renderMethod . '.']))	{
			$conf = $this->cObj->joinTSarrays($conf, $conf['rendering.'][$renderMethod . '.']);
		}

			// Image or Text with Image?
		if (is_array($conf['text.']))	{
			$content = $this->cObj->stdWrap($this->cObj->cObjGet($conf['text.'], 'text.'), $conf['text.']);
		}

		$imgList = trim($this->cObj->stdWrap($conf['imgList'], $conf['imgList.']));

		if (!$imgList)	{
				// No images, that's easy
			if (is_array($conf['stdWrap.']))	{
				return $this->cObj->stdWrap($content, $conf['stdWrap.']);
			}
			return $content;
		}

		$imgs = t3lib_div::trimExplode(',', $imgList);
		$imgStart = intval($this->cObj->stdWrap($conf['imgStart'], $conf['imgStart.']));
		$imgCount = count($imgs) - $imgStart;
		$imgMax = intval($this->cObj->stdWrap($conf['imgMax'], $conf['imgMax.']));
		if ($imgMax)	{
			$imgCount = t3lib_div::intInRange($imgCount, 0, $imgMax);	// reduce the number of images.
		}

		$imgPath = $this->cObj->stdWrap($conf['imgPath'], $conf['imgPath.']);

			// Does we need to render a "global caption" (below the whole image block)?
		$renderGlobalCaption = !$conf['captionSplit'] && !$conf['imageTextSplit'] && is_array($conf['caption.']);
		if ($imgCount == 1) {
				// If we just have one image, the caption relates to the image, so it is not "global"
			$renderGlobalCaption = false;
		}

			// Use the calculated information (amount of images, if global caption is wanted) to choose a different rendering method for the images-block
		$GLOBALS['TSFE']->register['imageCount'] = $imgCount;
		$GLOBALS['TSFE']->register['renderGlobalCaption'] = $renderGlobalCaption;
		$fallbackRenderMethod = $this->cObj->cObjGetSingle($conf['fallbackRendering'], $conf['fallbackRendering.']);
		if ($fallbackRenderMethod && is_array($conf['rendering.'][$fallbackRenderMethod . '.']))	{
			$conf = $this->cObj->joinTSarrays($conf, $conf['rendering.'][$fallbackRenderMethod . '.']);
		}

			// Global caption
		$globalCaption = '';
		if ($renderGlobalCaption)	{
			$globalCaption = $this->cObj->stdWrap($this->cObj->cObjGet($conf['caption.'], 'caption.'), $conf['caption.']);
		}

			// Positioning
		$position = $this->cObj->stdWrap($conf['textPos'], $conf['textPos.']);

		$imagePosition = $position&7;	// 0,1,2 = center,right,left
		$contentPosition = $position&24;	// 0,8,16,24 (above,below,intext,intext-wrap)
		$align = $this->cObj->align[$imagePosition];
		$textMargin = intval($this->cObj->stdWrap($conf['textMargin'],$conf['textMargin.']));
		if (!$conf['textMargin_outOfText'] && $contentPosition < 16)	{
			$textMargin = 0;
		}

		$colspacing = intval($this->cObj->stdWrap($conf['colSpace'], $conf['colSpace.']));
		$rowspacing = intval($this->cObj->stdWrap($conf['rowSpace'], $conf['rowSpace.']));

		$border = intval($this->cObj->stdWrap($conf['border'], $conf['border.'])) ? 1:0;
		$borderColor = $this->cObj->stdWrap($conf['borderCol'], $conf['borderCol.']);
		$borderThickness = intval($this->cObj->stdWrap($conf['borderThick'], $conf['borderThick.']));

		$borderColor = $borderColor?$borderColor:'black';
		$borderThickness = $borderThickness?$borderThickness:1;
		$borderSpace = (($conf['borderSpace']&&$border) ? intval($conf['borderSpace']) : 0);

			// Generate cols
		$cols = intval($this->cObj->stdWrap($conf['cols'],$conf['cols.']));
		$colCount = ($cols > 1) ? $cols : 1;
		if ($colCount > $imgCount)	{$colCount = $imgCount;}
		$rowCount = ceil($imgCount / $colCount);

			// Generate rows
		$rows = intval($this->cObj->stdWrap($conf['rows'],$conf['rows.']));
		if ($rows>1)	{
			$rowCount = $rows;
			if ($rowCount > $imgCount)	{$rowCount = $imgCount;}
			$colCount = ($rowCount>1) ? ceil($imgCount / $rowCount) : $imgCount;
		}

			// Max Width
		$maxW = intval($this->cObj->stdWrap($conf['maxW'], $conf['maxW.']));

		if ($contentPosition>=16)	{	// in Text
			$maxWInText = intval($this->cObj->stdWrap($conf['maxWInText'],$conf['maxWInText.']));
			if (!$maxWInText)	{
					// If maxWInText is not set, it's calculated to the 50% of the max
				$maxW = round($maxW/100*50);
			} else {
				$maxW = $maxWInText;
			}
		}

			// max usuable width for images (without spacers and borders)
		$netW = $maxW - $colspacing * ($colCount - 1) - $colCount * $border * ($borderThickness + $borderSpace) * 2;

			// Specify the maximum width for each column
		$columnWidths = $this->getImgColumnWidths($conf, $colCount, $netW);
		
		$image_compression = intval($this->cObj->stdWrap($conf['image_compression'],$conf['image_compression.']));
		$image_effects = intval($this->cObj->stdWrap($conf['image_effects'],$conf['image_effects.']));
		$image_frames = intval($this->cObj->stdWrap($conf['image_frames.']['key'],$conf['image_frames.']['key.']));

			// EqualHeight
		$equalHeight = intval($this->cObj->stdWrap($conf['equalH'],$conf['equalH.']));
		if ($equalHeight)	{
				// Initiate gifbuilder object in order to get dimensions AND calculate the imageWidth's
			$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
			$gifCreator->init();
			$relations_cols = Array();
			$imgWidths = array(); // contains the individual width of all images after scaling to $equalHeight
			for ($a=0; $a<$imgCount; $a++)	{
				$imgKey = $a+$imgStart;
				$imgInfo = $gifCreator->getImageDimensions($imgPath.$imgs[$imgKey]);
				$rel = $imgInfo[1] / $equalHeight;	// relationship between the original height and the wished height
				if ($rel)	{	// if relations is zero, then the addition of this value is omitted as the image is not expected to display because of some error.
					$imgWidths[$a] = $imgInfo[0] / $rel;
					$relations_cols[floor($a/$colCount)] += $imgWidths[$a];	// counts the total width of the row with the new height taken into consideration.
				}
			}
		}

			// Fetches pictures
		$splitArr = array();
		$splitArr['imgObjNum'] = $conf['imgObjNum'];
		$splitArr = $GLOBALS['TSFE']->tmpl->splitConfArray($splitArr, $imgCount);

		$imageRowsFinalWidths = Array();	// contains the width of every image row
		$imgsTag = array();		// array index of $imgsTag will be the same as in $imgs, but $imgsTag only contains the images that are actually shown
		$origImages = array();
		$rowIdx = 0;
		for ($a=0; $a<$imgCount; $a++)	{
			$imgKey = $a+$imgStart;
			$totalImagePath = $imgPath.$imgs[$imgKey];

			$GLOBALS['TSFE']->register['IMAGE_NUM'] = $imgKey;	// register IMG_NUM is kept for backwards compatibility
			$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $imgKey;
			$GLOBALS['TSFE']->register['ORIG_FILENAME'] = $totalImagePath;

			$this->cObj->data[$this->cObj->currentValKey] = $totalImagePath;
			$imgObjNum = intval($splitArr[$a]['imgObjNum']);
			$imgConf = $conf[$imgObjNum.'.'];

			if ($equalHeight)	{
				
				if ($a % $colCount == 0) {
						// a new row startsS
					$accumWidth = 0; // reset accumulated net width
					$accumDesiredWidth = 0; // reset accumulated desired width
					$rowTotalMaxW = $relations_cols[$rowIdx];
					if ($rowTotalMaxW > $netW)	{
						$scale = $rowTotalMaxW / $netW;
					} else {
						$scale = 1;
					}
					$desiredHeight = $equalHeight / $scale;
					$rowIdx++;
				}
				
				$availableWidth= $netW - $accumWidth; // this much width is available for the remaining images in this row (int)
				$desiredWidth= $imgWidths[$a] / $scale; // theoretical width of resized image. (float)
				$accumDesiredWidth += $desiredWidth; // add this width. $accumDesiredWidth becomes the desired horizontal position 
					// calculate width by comparing actual and desired horizontal position.
					// this evenly distributes rounding errors across all images in this row. 
				$suggestedWidth = round($accumDesiredWidth - $accumWidth);
				$finalImgWidth = (int) min($availableWidth, $suggestedWidth); // finalImgWidth may not exceed $availableWidth
				$accumWidth += $finalImgWidth;
				$imgConf['file.']['width'] = $finalImgWidth;
				$imgConf['file.']['height'] = round($desiredHeight);

					// other stuff will be calculated accordingly:
				unset($imgConf['file.']['maxW']);
				unset($imgConf['file.']['maxH']);
				unset($imgConf['file.']['minW']);
				unset($imgConf['file.']['minH']);
				unset($imgConf['file.']['width.']);
				unset($imgConf['file.']['maxW.']);
				unset($imgConf['file.']['maxH.']);
				unset($imgConf['file.']['minW.']);
				unset($imgConf['file.']['minH.']);
			} else {
				$imgConf['file.']['maxW'] = $columnWidths[($a%$colCount)];
			}

			$titleInLink = $this->cObj->stdWrap($imgConf['titleInLink'], $imgConf['titleInLink.']);
			$titleInLinkAndImg = $this->cObj->stdWrap($imgConf['titleInLinkAndImg'], $imgConf['titleInLinkAndImg.']);
			$oldATagParms = $GLOBALS['TSFE']->ATagParams;
			if ($titleInLink)	{
					// Title in A-tag instead of IMG-tag
				$titleText = trim($this->cObj->stdWrap($imgConf['titleText'], $imgConf['titleText.']));
				if ($titleText)	{
						// This will be used by the IMAGE call later:
					$GLOBALS['TSFE']->ATagParams .= ' title="'. $titleText .'"';
				}
			}

			if ($imgConf || $imgConf['file'])	{
				if ($this->cObj->image_effects[$image_effects])	{
					$imgConf['file.']['params'] .= ' '.$this->cObj->image_effects[$image_effects];
				}
				if ($image_frames)	{
					if (is_array($conf['image_frames.'][$image_frames.'.']))	{
						$imgConf['file.']['m.'] = $conf['image_frames.'][$image_frames.'.'];
					}
				}
				if ($image_compression && $imgConf['file'] != 'GIFBUILDER')	{
					if ($image_compression == 1)	{
						$tempImport = $imgConf['file.']['import'];
						$tempImport_dot = $imgConf['file.']['import.'];
						unset($imgConf['file.']);
						$imgConf['file.']['import'] = $tempImport;
						$imgConf['file.']['import.'] = $tempImport_dot;
					} elseif (isset($this->cObj->image_compression[$image_compression])) {
						$imgConf['file.']['params'] .= ' '.$this->cObj->image_compression[$image_compression]['params'];
						$imgConf['file.']['ext'] = $this->cObj->image_compression[$image_compression]['ext'];
						unset($imgConf['file.']['ext.']);
					}
				}
				if ($titleInLink && ! $titleInLinkAndImg)	{
						// Check if the image will be linked
					$link = $this->cObj->imageLinkWrap('', $totalImagePath, $imgConf['imageLinkWrap.']);
					if ($link)	{
							// Title in A-tag only (set above: ATagParams), not in IMG-tag
						unset($imgConf['titleText']);
						unset($imgConf['titleText.']);
						$imgConf['emptyTitleHandling'] = 'removeAttr';
					}
				}
				$imgsTag[$imgKey] = $this->cObj->IMAGE($imgConf);
			} else {
				$imgsTag[$imgKey] = $this->cObj->IMAGE(Array('file' => $totalImagePath)); 	// currentValKey !!!
			}
				// Restore our ATagParams
			$GLOBALS['TSFE']->ATagParams = $oldATagParms;
				// Store the original filepath
			$origImages[$imgKey] = $GLOBALS['TSFE']->lastImageInfo;

			if ($GLOBALS['TSFE']->lastImageInfo[0]==0) {
				$imageRowsFinalWidths[floor($a/$colCount)] += $this->cObj->data['imagewidth'];
			} else {
				$imageRowsFinalWidths[floor($a/$colCount)] += $GLOBALS['TSFE']->lastImageInfo[0];
 			}

		}
			// How much space will the image-block occupy?
		$imageBlockWidth = max($imageRowsFinalWidths)+ $colspacing*($colCount-1) + $colCount*$border*($borderSpace+$borderThickness)*2;
		$GLOBALS['TSFE']->register['rowwidth'] = $imageBlockWidth;
		$GLOBALS['TSFE']->register['rowWidthPlusTextMargin'] = $imageBlockWidth + $textMargin;

			// noRows is in fact just one ROW, with the amount of columns specified, where the images are placed in.
			// noCols is just one COLUMN, each images placed side by side on each row
		$noRows = $this->cObj->stdWrap($conf['noRows'],$conf['noRows.']);
		$noCols = $this->cObj->stdWrap($conf['noCols'],$conf['noCols.']);
		if ($noRows) {$noCols=0;}	// noRows overrides noCols. They cannot exist at the same time.

		$rowCount_temp = 1;
		$colCount_temp = $colCount;
		if ($noRows)	{
			$rowCount_temp = $rowCount;
			$rowCount = 1;
		}
		if ($noCols)	{
			$colCount = 1;
			$columnWidths = array();
		}

			// Edit icons:
		if (!is_array($conf['editIcons.'])) {
			$conf['editIcons.'] = array();
		}
		$editIconsHTML = $conf['editIcons']&&$GLOBALS['TSFE']->beUserLogin ? $this->cObj->editIcons('',$conf['editIcons'],$conf['editIcons.']) : '';

			// If noRows, we need multiple imagecolumn wraps
		$imageWrapCols = 1;
		if ($noRows)	{ $imageWrapCols = $colCount; }

			// User wants to separate the rows, but only do that if we do have rows
		$separateRows = $this->cObj->stdWrap($conf['separateRows'], $conf['separateRows.']);
		if ($noRows)	{ $separateRows = 0; }
		if ($rowCount == 1)	{ $separateRows = 0; }

			// Apply optionSplit to the list of classes that we want to add to each image
		$addClassesImage = $conf['addClassesImage'];
		if ($conf['addClassesImage.'])	{
			$addClassesImage = $this->cObj->stdWrap($addClassesImage, $conf['addClassesImage.']);
		}
		$addClassesImageConf = $GLOBALS['TSFE']->tmpl->splitConfArray(array('addClassesImage' => $addClassesImage), $colCount);

			// Render the images
		$images = '';
		for ($c = 0; $c < $imageWrapCols; $c++)	{
			$tmpColspacing = $colspacing;
			if (($c==$imageWrapCols-1 && $imagePosition==2) || ($c==0 && ($imagePosition==1||$imagePosition==0))) {
					// Do not add spacing after column if we are first column (left) or last column (center/right)
				$tmpColspacing = 0;
			}

			$thisImages = '';
			$allRows = '';
			$maxImageSpace = 0;
			for ($i = $c; $i<count($imgsTag); $i=$i+$imageWrapCols)	{
				$imgKey = $i+$imgStart;
				$colPos = $i%$colCount;
				if ($separateRows && $colPos == 0) {
					$thisRow = '';
				}

					// Render one image
				if($origImages[$imgKey][0]==0) {
					$imageSpace=$this->cObj->data['imagewidth'] + $border*($borderSpace+$borderThickness)*2;
				} else {
					$imageSpace = $origImages[$imgKey][0] + $border*($borderSpace+$borderThickness)*2;
				}

				$GLOBALS['TSFE']->register['IMAGE_NUM'] = $imgKey;
				$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $imgKey;
				$GLOBALS['TSFE']->register['ORIG_FILENAME'] = $origImages[$imgKey]['origFile'];
				$GLOBALS['TSFE']->register['imagewidth'] = $origImages[$imgKey][0];
				$GLOBALS['TSFE']->register['imagespace'] = $imageSpace;
				$GLOBALS['TSFE']->register['imageheight'] = $origImages[$imgKey][1];
				if ($imageSpace > $maxImageSpace)	{
					$maxImageSpace = $imageSpace;
				}
				$thisImage = '';
				$thisImage .= $this->cObj->stdWrap($imgsTag[$imgKey], $conf['imgTagStdWrap.']);

				if (!$renderGlobalCaption)	{
					$thisImage .= $this->cObj->stdWrap($this->cObj->cObjGet($conf['caption.'], 'caption.'), $conf['caption.']);
				}
				if ($editIconsHTML)	{
					$thisImage .= $this->cObj->stdWrap($editIconsHTML, $conf['editIconsStdWrap.']);
				}
				$thisImage = $this->cObj->stdWrap($thisImage, $conf['oneImageStdWrap.']);
				$classes = '';
				if ($addClassesImageConf[$colPos]['addClassesImage'])	{
					$classes = ' ' . $addClassesImageConf[$colPos]['addClassesImage'];
				}
				$thisImage = str_replace('###CLASSES###', $classes, $thisImage);

				if ($separateRows)	{
					$thisRow .= $thisImage;
				} else {
					$allRows .= $thisImage;
				}
				$GLOBALS['TSFE']->register['columnwidth'] = $maxImageSpace + $tmpColspacing;


					// Close this row at the end (colCount), or the last row at the final end
				if ($separateRows && ($i+1 == count($imgsTag)))	{
						// Close the very last row with either normal configuration or lastRow stdWrap
					$allRows .= $this->cObj->stdWrap($thisRow, (is_array($conf['imageLastRowStdWrap.']) ? $conf['imageLastRowStdWrap.'] : $conf['imageRowStdWrap.']));
				} elseif ($separateRows && $colPos == $colCount-1)	{
					$allRows .= $this->cObj->stdWrap($thisRow, $conf['imageRowStdWrap.']);
				}
			}
			if ($separateRows)	{
				$thisImages .= $allRows;
			} else {
				$thisImages .= $this->cObj->stdWrap($allRows, $conf['noRowsStdWrap.']);
			}
			if ($noRows)	{
					// Only needed to make columns, rather than rows:
				$images .= $this->cObj->stdWrap($thisImages, $conf['imageColumnStdWrap.']);
			} else {
				$images .= $thisImages;
			}
		}

			// Add the global caption, if not split
		if ($globalCaption)	{
			$images .= $globalCaption;
		}

			// CSS-classes
		$captionClass = '';
		$classCaptionAlign = array(
			'center' => 'csc-textpic-caption-c',
			'right' => 'csc-textpic-caption-r',
			'left' => 'csc-textpic-caption-l',
		);
		$captionAlign = $this->cObj->stdWrap($conf['captionAlign'], $conf['captionAlign.']);
		if ($captionAlign)	{
			$captionClass = $classCaptionAlign[$captionAlign];
		}
		$borderClass = '';
		if ($border)	{
			$borderClass = $conf['borderClass'] ? $conf['borderClass'] : 'csc-textpic-border';
		}

			// Multiple classes with all properties, to be styled in CSS
		$class = '';
		$class .= ($borderClass? ' '.$borderClass:'');
		$class .= ($captionClass? ' '.$captionClass:'');
		$class .= ($equalHeight? ' csc-textpic-equalheight':'');
		$addClasses = $this->cObj->stdWrap($conf['addClasses'], $conf['addClasses.']);
		$class .= ($addClasses ? ' '.$addClasses:'');

			// Do we need a width in our wrap around images?
		$imgWrapWidth = '';
		if ($position == 0 || $position == 8)	{
				// For 'center' we always need a width: without one, the margin:auto trick won't work
			$imgWrapWidth = $imageBlockWidth;
		}
		if ($rowCount > 1)	{
				// For multiple rows we also need a width, so that the images will wrap
			$imgWrapWidth = $imageBlockWidth;
		}
		if ($caption)	{
				// If we have a global caption, we need the width so that the caption will wrap
			$imgWrapWidth = $imageBlockWidth;
		}

			// Wrap around the whole image block
		$GLOBALS['TSFE']->register['totalwidth'] = $imgWrapWidth;
		if ($imgWrapWidth)	{
			$images = $this->cObj->stdWrap($images, $conf['imageStdWrap.']);
		} else {
			$images = $this->cObj->stdWrap($images, $conf['imageStdWrapNoWidth.']);
		}

		$output = $this->cObj->cObjGetSingle($conf['layout'], $conf['layout.']);
		$output = str_replace('###TEXT###', $content, $output);
		$output = str_replace('###IMAGES###', $images, $output);
		$output = str_replace('###CLASSES###', $class, $output);

		if ($conf['stdWrap.'])	{
			$output = $this->cObj->stdWrap($output, $conf['stdWrap.']);
		}

		return $output;
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
	function hookRequest($functionName) {
		global $TYPO3_CONF_VARS;

			// Hook: menuConfig_preProcessModMenu
		if ($TYPO3_CONF_VARS['EXTCONF']['css_styled_content']['pi1_hooks'][$functionName]) {
			$hookObj = t3lib_div::getUserObj($TYPO3_CONF_VARS['EXTCONF']['css_styled_content']['pi1_hooks'][$functionName]);
			if (method_exists ($hookObj, $functionName)) {
				$hookObj->pObj = $this;
				return $hookObj;
			}
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/css_styled_content/pi1/class.tx_cssstyledcontent_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/css_styled_content/pi1/class.tx_cssstyledcontent_pi1.php']);
}

?>