<?php
namespace TYPO3\CMS\CssStyledContent\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin class - instantiated from TypoScript.
 * Rendering some content elements from tt_content table.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class CssStyledContentController extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

	/**
	 * Same as class name
	 *
	 * @todo Define visibility
	 */
	public $prefixId = 'tx_cssstyledcontent_pi1';

	/**
	 * Path to this script relative to the extension dir.
	 *
	 * @todo Define visibility
	 */
	public $scriptRelPath = 'pi1/class.tx_cssstyledcontent_pi1.php';

	/**
	 * The extension key
	 *
	 * @todo Define visibility
	 */
	public $extKey = 'css_styled_content';

	/**
	 * @todo Define visibility
	 */
	public $conf = array();

	/***********************************
	 * Rendering of Content Elements:
	 ***********************************/

	/**
	 * Rendering the "Bulletlist" type content element, called from TypoScript (tt_content.bullets.20)
	 *
	 * @param string $content Content input. Not used, ignore.
	 * @param array $conf TypoScript configuration
	 * @return string HTML output.
	 */
	public function render_bullets($content, $conf) {
		// Look for hook before running default code for function
		if ($hookObj = $this->hookRequest('render_bullets')) {
			return $hookObj->render_bullets($content, $conf);
		} else {
			// Get bodytext field content, returning blank if empty:
			$field = isset($conf['field']) && trim($conf['field']) ? trim($conf['field']) : 'bodytext';
			$content = trim($this->cObj->data[$field]);
			if (!strcmp($content, '')) {
				return '';
			}
			// Split into single lines:
			$lines = GeneralUtility::trimExplode(LF, $content);
			foreach ($lines as &$val) {
				$val = '<li>' . $this->cObj->stdWrap($val, $conf['innerStdWrap.']) . '</li>';
			}
			unset($val);
			// Set header type:
			$type = intval($this->cObj->data['layout']);
			// Compile list:
			$out = '
				<ul class="csc-bulletlist csc-bulletlist-' . $type . '">' . implode('', $lines) . '
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
	 * @param string $content Content input. Not used, ignore.
	 * @param array $conf TypoScript configuration
	 * @return string HTML output.
	 */
	public function render_table($content, $conf) {
		// Look for hook before running default code for function
		if ($hookObj = $this->hookRequest('render_table')) {
			return $hookObj->render_table($content, $conf);
		} else {
			// Init FlexForm configuration
			$this->pi_initPIflexForm();
			// Get bodytext field content
			$field = isset($conf['field']) && trim($conf['field']) ? trim($conf['field']) : 'bodytext';
			$content = trim($this->cObj->data[$field]);
			if (!strcmp($content, '')) {
				return '';
			}
			// get flexform values
			$caption = trim(htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_caption')));
			$useTfoot = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_tfoot'));
			$headerPos = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_headerpos');
			$noStyles = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_nostyles');
			$tableClass = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_tableclass');
			$delimiter = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tableparsing_delimiter', 's_parsing'));
			if ($delimiter) {
				$delimiter = chr(intval($delimiter));
			} else {
				$delimiter = '|';
			}
			$quotedInput = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tableparsing_quote', 's_parsing'));
			if ($quotedInput) {
				$quotedInput = chr(intval($quotedInput));
			} else {
				$quotedInput = '';
			}
			// Generate id prefix for accessible header
			$headerScope = $headerPos == 'top' ? 'col' : 'row';
			$headerIdPrefix = $headerScope . $this->cObj->data['uid'] . '-';
			// Split into single lines (will become table-rows):
			$rows = GeneralUtility::trimExplode(LF, $content);
			reset($rows);
			// Find number of columns to render:
			$cols = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(
				$this->cObj->data['cols'] ? $this->cObj->data['cols'] : count(explode($delimiter, current($rows))),
				0,
				100
			);
			// Traverse rows (rendering the table here)
			$rCount = count($rows);
			foreach ($rows as $k => $v) {
				$cells = explode($delimiter, $v);
				$newCells = array();
				for ($a = 0; $a < $cols; $a++) {
					// Remove quotes if needed
					if ($quotedInput && substr($cells[$a], 0, 1) == $quotedInput && substr($cells[$a], -1, 1) == $quotedInput) {
						$cells[$a] = substr($cells[$a], 1, -1);
					}
					if (!strcmp(trim($cells[$a]), '')) {
						$cells[$a] = '&nbsp;';
					}
					$cellAttribs = $noStyles ? '' : ($a > 0 && $cols - 1 == $a ? ' class="td-last td-' . $a . '"' : ' class="td-' . $a . '"');
					if ($headerPos == 'top' && !$k || $headerPos == 'left' && !$a) {
						$scope = ' scope="' . $headerScope . '"';
						$scope .= ' id="' . $headerIdPrefix . ($headerScope == 'col' ? $a : $k) . '"';
						$newCells[$a] = '
							<th' . $cellAttribs . $scope . '>' . $this->cObj->stdWrap($cells[$a], $conf['innerStdWrap.']) . '</th>';
					} else {
						if (empty($headerPos)) {
							$accessibleHeader = '';
						} else {
							$accessibleHeader = ' headers="' . $headerIdPrefix . ($headerScope == 'col' ? $a : $k) . '"';
						}
						$newCells[$a] = '
							<td' . $cellAttribs . $accessibleHeader . '>' . $this->cObj->stdWrap($cells[$a], $conf['innerStdWrap.']) . '</td>';
					}
				}
				if (!$noStyles) {
					$oddEven = $k % 2 ? 'tr-odd' : 'tr-even';
					$rowAttribs = $k > 0 && $rCount - 1 == $k ? ' class="' . $oddEven . ' tr-last"' : ' class="' . $oddEven . ' tr-' . $k . '"';
				}
				$rows[$k] = '
					<tr' . $rowAttribs . '>' . implode('', $newCells) . '
					</tr>';
			}
			$addTbody = 0;
			$tableContents = '';
			if ($caption) {
				$tableContents .= '
					<caption>' . $caption . '</caption>';
			}
			if ($headerPos == 'top' && $rows[0]) {
				$tableContents .= '<thead>' . $rows[0] . '
					</thead>';
				unset($rows[0]);
				$addTbody = 1;
			}
			if ($useTfoot) {
				$tableContents .= '
					<tfoot>' . $rows[($rCount - 1)] . '</tfoot>';
				unset($rows[$rCount - 1]);
				$addTbody = 1;
			}
			$tmpTable = implode('', $rows);
			if ($addTbody) {
				$tmpTable = '<tbody>' . $tmpTable . '</tbody>';
			}
			$tableContents .= $tmpTable;
			// Set header type:
			$type = intval($this->cObj->data['layout']);
			// Table tag params.
			$tableTagParams = $this->getTableAttributes($conf, $type);
			if (!$noStyles) {
				$tableTagParams['class'] = 'contenttable contenttable-' . $type . ($tableClass ? ' ' . $tableClass : '') . $tableTagParams['class'];
			} elseif ($tableClass) {
				$tableTagParams['class'] = $tableClass;
			}
			// Compile table output:
			$out = '
				<table ' . GeneralUtility::implodeAttributes($tableTagParams) . '>' . $tableContents . '
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
	 * @param string $content Content input. Not used, ignore.
	 * @param array $conf TypoScript configuration
	 * @return string HTML output.
	 */
	public function render_uploads($content, $conf) {
		// Look for hook before running default code for function
		if ($hookObj = $this->hookRequest('render_uploads')) {
			return $hookObj->render_uploads($content, $conf);
		} else {
			// Loading language-labels
			$this->pi_loadLL();
			$out = '';
			// Set layout type:
			$type = intval($this->cObj->data['layout']);
			// See if the file path variable is set, this takes precedence
			$filePathConf = $this->cObj->stdWrap($conf['filePath'], $conf['filePath.']);
			if ($filePathConf) {
				$fileList = $this->cObj->filelist($filePathConf);
				list($path) = explode('|', $filePathConf);
			} else {
				// Get the list of files from the field
				$field = trim($conf['field']) ? trim($conf['field']) : 'media';
				$fileList = $this->cObj->data[$field];
				$path = 'uploads/media/';
				if (
					is_array($GLOBALS['TCA']['tt_content']['columns'][$field]) &&
					!empty($GLOBALS['TCA']['tt_content']['columns'][$field]['config']['uploadfolder'])
				) {
					// In TCA-Array folders are saved without trailing slash, so $path.$fileName won't work
					$path = $GLOBALS['TCA']['tt_content']['columns'][$field]['config']['uploadfolder'] . '/';
				}
			}
			$path = trim($path);
			// Explode into an array:
			$fileArray = GeneralUtility::trimExplode(',', $fileList, 1);
			// If there were files to list...:
			if (count($fileArray)) {
				// Get the descriptions for the files (if any):
				$descriptions = GeneralUtility::trimExplode(LF, $this->cObj->data['imagecaption']);
				// Get the titles for the files (if any)
				$titles = GeneralUtility::trimExplode(LF, $this->cObj->data['titleText']);
				// Get the alternative text for icons/thumbnails
				$altTexts = GeneralUtility::trimExplode(LF, $this->cObj->data['altText']);
				// Add the target to linkProc when explicitly set
				if ($this->cObj->data['target']) {
					$conf['linkProc.']['target'] = $this->cObj->data['target'];
					unset($conf['linkProc.']['target.']);
				}
				// Adding hardcoded TS to linkProc configuration:
				$conf['linkProc.']['path.']['current'] = 1;
				if ($conf['linkProc.']['combinedLink']) {
					$conf['linkProc.']['icon'] = $type > 0 ? 1 : 0;
				} else {
					// Always render icon - is inserted by PHP if needed.
					$conf['linkProc.']['icon'] = 1;
					// Temporary, internal split-token!
					$conf['linkProc.']['icon.']['wrap'] = ' | //**//';
					// ALways link the icon
					$conf['linkProc.']['icon_link'] = 1;
				}
				$conf['linkProc.']['icon_image_ext_list'] = $type == 2 || $type == 3 ? $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] : '';
				// If the layout is type 2 or 3 we will render an image based icon if possible.
				if ($conf['labelStdWrap.']) {
					$conf['linkProc.']['labelStdWrap.'] = $conf['labelStdWrap.'];
				}
				if ($conf['useSpacesInLinkText'] || $conf['stripFileExtensionFromLinkText']) {
					$conf['linkProc.']['removePrependedNumbers'] = 0;
				}
				// Traverse the files found:
				$filesData = array();
				foreach ($fileArray as $key => $fileName) {
					$absPath = GeneralUtility::getFileAbsFileName(GeneralUtility::resolveBackPath($path . $fileName));
					if (@is_file($absPath)) {
						$fI = pathinfo($fileName);
						$filesData[$key] = array();
						$currentPath = $path;
						if (GeneralUtility::isFirstPartOfStr($fileName, '../../')) {
							$currentPath = '';
							$fileName = substr($fileName, 6);
						}
						$filesData[$key]['filename'] = $fileName;
						$filesData[$key]['path'] = $currentPath;
						$filesData[$key]['filesize'] = filesize($absPath);
						$filesData[$key]['fileextension'] = strtolower($fI['extension']);
						$filesData[$key]['description'] = trim($descriptions[$key]);
						$conf['linkProc.']['title'] = trim($titles[$key]);
						if (isset($altTexts[$key]) && !empty($altTexts[$key])) {
							$altText = trim($altTexts[$key]);
						} else {
							$altText = sprintf($this->pi_getLL('uploads.icon'), $fileName);
						}
						$conf['linkProc.']['altText'] = ($conf['linkProc.']['iconCObject.']['altText'] = $altText);
						$this->cObj->setCurrentVal($currentPath);
						$GLOBALS['TSFE']->register['ICON_REL_PATH'] = $currentPath . $fileName;
						$GLOBALS['TSFE']->register['filename'] = $filesData[$key]['filename'];
						$GLOBALS['TSFE']->register['path'] = $filesData[$key]['path'];
						$GLOBALS['TSFE']->register['fileSize'] = $filesData[$key]['filesize'];
						$GLOBALS['TSFE']->register['fileExtension'] = $filesData[$key]['fileextension'];
						$GLOBALS['TSFE']->register['description'] = $filesData[$key]['description'];
						$filesData[$key]['linkedFilenameParts'] = $this->beautifyFileLink(
							explode('//**//', $this->cObj->filelink($fileName, $conf['linkProc.'])),
							$fileName,
							$conf['useSpacesInLinkText'],
							$conf['stripFileExtensionFromLinkText']
						);
					}
				}
				// optionSplit applied to conf to allow differnt settings per file
				$splitConf = $GLOBALS['TSFE']->tmpl->splitConfArray($conf, count($filesData));
				// Now, lets render the list!
				$outputEntries = array();
				foreach ($filesData as $key => $fileData) {
					$GLOBALS['TSFE']->register['linkedIcon'] = $fileData['linkedFilenameParts'][0];
					$GLOBALS['TSFE']->register['linkedLabel'] = $fileData['linkedFilenameParts'][1];
					$GLOBALS['TSFE']->register['filename'] = $fileData['filename'];
					$GLOBALS['TSFE']->register['path'] = $fileData['path'];
					$GLOBALS['TSFE']->register['description'] = $fileData['description'];
					$GLOBALS['TSFE']->register['fileSize'] = $fileData['filesize'];
					$GLOBALS['TSFE']->register['fileExtension'] = $fileData['fileextension'];
					$outputEntries[] = $this->cObj->cObjGetSingle($splitConf[$key]['itemRendering'], $splitConf[$key]['itemRendering.']);
				}
				if (isset($conf['outerWrap'])) {
					// Wrap around the whole content
					$outerWrap = $this->cObj->stdWrap($conf['outerWrap'], $conf['outerWrap.']);
				} else {
					// Table tag params
					$tableTagParams = $this->getTableAttributes($conf, $type);
					$tableTagParams['class'] = 'csc-uploads csc-uploads-' . $type;
					$outerWrap = '<table ' . GeneralUtility::implodeAttributes($tableTagParams) . '>|</table>';
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
	 * Returns an array containing width relations for $colCount columns.
	 *
	 * Tries to use "colRelations" setting given by TS.
	 * uses "1:1" column relations by default.
	 *
	 * @param array $conf TS configuration for img
	 * @param integer $colCount number of columns
	 * @return array
	 */
	protected function getImgColumnRelations($conf, $colCount) {
		$relations = array();
		$equalRelations = array_fill(0, $colCount, 1);
		$colRelationsTypoScript = trim($this->cObj->stdWrap($conf['colRelations'], $conf['colRelations.']));
		if ($colRelationsTypoScript) {
			// Try to use column width relations given by TS
			$relationParts = explode(':', $colRelationsTypoScript);
			// Enough columns defined?
			if (count($relationParts) >= $colCount) {
				$out = array();
				for ($a = 0; $a < $colCount; $a++) {
					$currentRelationValue = intval($relationParts[$a]);
					if ($currentRelationValue >= 1) {
						$out[$a] = $currentRelationValue;
					} else {
						GeneralUtility::devLog('colRelations used with a value smaller than 1 therefore colRelations setting is ignored.', $this->extKey, 2);
						unset($out);
						break;
					}
				}
				if (max($out) / min($out) <= 10) {
					$relations = $out;
				} else {
					GeneralUtility::devLog(
						'The difference in size between the largest and smallest colRelation was not within' .
						' a factor of ten therefore colRelations setting is ignored..',
						$this->extKey,
						2
					);
				}
			}
		}
		return $relations ? $relations : $equalRelations;
	}

	/**
	 * Returns an array containing the image widths for an image row with $colCount columns.
	 *
	 * @param array $conf TS configuration of img
	 * @param integer $colCount number of columns
	 * @param integer $netW max usable width for images (without spaces and borders)
	 * @return array
	 */
	protected function getImgColumnWidths($conf, $colCount, $netW) {
		$columnWidths = array();
		$colRelations = $this->getImgColumnRelations($conf, $colCount);
		$accumWidth = 0;
		$accumDesiredWidth = 0;
		$relUnitCount = array_sum($colRelations);
		for ($a = 0; $a < $colCount; $a++) {
			// This much width is available for the remaining images in this row (int)
			$availableWidth = $netW - $accumWidth;
			// Theoretical width of resized image. (float)
			$desiredWidth = $netW / $relUnitCount * $colRelations[$a];
			// Add this width. $accumDesiredWidth becomes the desired horizontal position
			$accumDesiredWidth += $desiredWidth;
			// Calculate width by comparing actual and desired horizontal position.
			// this evenly distributes rounding errors across all images in this row.
			$suggestedWidth = round($accumDesiredWidth - $accumWidth);
			// finalImgWidth may not exceed $availableWidth
			$finalImgWidth = (int) min($availableWidth, $suggestedWidth);
			$accumWidth += $finalImgWidth;
			$columnWidths[$a] = $finalImgWidth;
		}
		return $columnWidths;
	}

	/**
	 * Rendering the IMGTEXT content element, called from TypoScript (tt_content.textpic.20)
	 *
	 * @param string $content Content input. Not used, ignore.
	 * @param array $conf TypoScript configuration. See TSRef "IMGTEXT". This function aims to be compatible.
	 * @return string HTML output.
	 * @coauthor Ernesto Baschny <ernst@cron-it.de>
	 * @coauthor Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function render_textpic($content, $conf) {
		// Look for hook before running default code for function
		if (method_exists($this, 'hookRequest') && ($hookObj = $this->hookRequest('render_textpic'))) {
			return $hookObj->render_textpic($content, $conf);
		}
		$renderMethod = $this->cObj->stdWrap($conf['renderMethod'], $conf['renderMethod.']);
		// Render using the default IMGTEXT code (table-based)
		if (!$renderMethod || $renderMethod == 'table') {
			return $this->cObj->IMGTEXT($conf);
		}
		// Specific configuration for the chosen rendering method
		if (is_array($conf['rendering.'][$renderMethod . '.'])) {
			$conf = $this->cObj->joinTSarrays($conf, $conf['rendering.'][$renderMethod . '.']);
		}
		// Image or Text with Image?
		if (is_array($conf['text.'])) {
			$content = $this->cObj->stdWrap($this->cObj->cObjGet($conf['text.'], 'text.'), $conf['text.']);
		}
		$imgList = trim($this->cObj->stdWrap($conf['imgList'], $conf['imgList.']));
		if (!$imgList) {
			// No images, that's easy
			if (is_array($conf['stdWrap.'])) {
				return $this->cObj->stdWrap($content, $conf['stdWrap.']);
			}
			return $content;
		}
		$imgs = GeneralUtility::trimExplode(',', $imgList);
		$imgStart = intval($this->cObj->stdWrap($conf['imgStart'], $conf['imgStart.']));
		$imgCount = count($imgs) - $imgStart;
		$imgMax = intval($this->cObj->stdWrap($conf['imgMax'], $conf['imgMax.']));
		if ($imgMax) {
			$imgCount = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($imgCount, 0, $imgMax);
		}
		$imgPath = $this->cObj->stdWrap($conf['imgPath'], $conf['imgPath.']);
		// Does we need to render a "global caption" (below the whole image block)?
		$renderGlobalCaption = !$conf['captionSplit'] && !$conf['imageTextSplit'] && is_array($conf['caption.']);
		if ($imgCount == 1) {
			// If we just have one image, the caption relates to the image, so it is not "global"
			$renderGlobalCaption = FALSE;
		}
		// Use the calculated information (amount of images, if global caption is wanted) to choose a different rendering method for the images-block
		$GLOBALS['TSFE']->register['imageCount'] = $imgCount;
		$GLOBALS['TSFE']->register['renderGlobalCaption'] = $renderGlobalCaption;
		if ($conf['fallbackRendering']) {
			$fallbackRenderMethod = $this->cObj->cObjGetSingle($conf['fallbackRendering'], $conf['fallbackRendering.']);
		}
		if ($fallbackRenderMethod && is_array($conf['rendering.'][$fallbackRenderMethod . '.'])) {
			$conf = $this->cObj->joinTSarrays($conf, $conf['rendering.'][$fallbackRenderMethod . '.']);
		}
		// Set the accessibility mode which uses a different type of markup, used 4.7+
		$accessibilityMode = FALSE;
		if (strpos(strtolower($renderMethod), 'caption') || strpos(strtolower($fallbackRenderMethod), 'caption')) {
			$accessibilityMode = TRUE;
		}
		// Global caption
		$globalCaption = '';
		if ($renderGlobalCaption) {
			$globalCaption = $this->cObj->stdWrap($this->cObj->cObjGet($conf['caption.'], 'caption.'), $conf['caption.']);
		}
		// Positioning
		$position = $this->cObj->stdWrap($conf['textPos'], $conf['textPos.']);
		// 0,1,2 = center,right,left
		$imagePosition = $position & 7;
		// 0,8,16,24 (above,below,intext,intext-wrap)
		$contentPosition = $position & 24;
		$textMargin = intval($this->cObj->stdWrap($conf['textMargin'], $conf['textMargin.']));
		if (!$conf['textMargin_outOfText'] && $contentPosition < 16) {
			$textMargin = 0;
		}
		$colspacing = intval($this->cObj->stdWrap($conf['colSpace'], $conf['colSpace.']));
		$border = intval($this->cObj->stdWrap($conf['border'], $conf['border.'])) ? 1 : 0;
		$borderThickness = intval($this->cObj->stdWrap($conf['borderThick'], $conf['borderThick.']));
		$borderThickness = $borderThickness ? $borderThickness : 1;
		$borderSpace = $conf['borderSpace'] && $border ? intval($conf['borderSpace']) : 0;
		// Generate cols
		$cols = intval($this->cObj->stdWrap($conf['cols'], $conf['cols.']));
		$colCount = $cols > 1 ? $cols : 1;
		if ($colCount > $imgCount) {
			$colCount = $imgCount;
		}
		$rowCount = ceil($imgCount / $colCount);
		// Generate rows
		$rows = intval($this->cObj->stdWrap($conf['rows'], $conf['rows.']));
		if ($rows > 1) {
			$rowCount = $rows;
			if ($rowCount > $imgCount) {
				$rowCount = $imgCount;
			}
			$colCount = $rowCount > 1 ? ceil($imgCount / $rowCount) : $imgCount;
		}
		// Max Width
		$maxW = intval($this->cObj->stdWrap($conf['maxW'], $conf['maxW.']));
		$maxWInText = intval($this->cObj->stdWrap($conf['maxWInText'], $conf['maxWInText.']));
		$fiftyPercentWidthInText = round($maxW / 100 * 50);
		// in Text
		if ($contentPosition >= 16) {
			if (!$maxWInText) {
				// If maxWInText is not set, it's calculated to the 50% of the max
				$maxW = $fiftyPercentWidthInText;
			} else {
				$maxW = $maxWInText;
			}
		}
		// max usuable width for images (without spacers and borders)
		$netW = $maxW - $colspacing * ($colCount - 1) - $colCount * $border * ($borderThickness + $borderSpace) * 2;
		// Specify the maximum width for each column
		$columnWidths = $this->getImgColumnWidths($conf, $colCount, $netW);
		$image_compression = intval($this->cObj->stdWrap($conf['image_compression'], $conf['image_compression.']));
		$image_effects = intval($this->cObj->stdWrap($conf['image_effects'], $conf['image_effects.']));
		$image_frames = intval($this->cObj->stdWrap($conf['image_frames.']['key'], $conf['image_frames.']['key.']));
		// EqualHeight
		$equalHeight = intval($this->cObj->stdWrap($conf['equalH'], $conf['equalH.']));
		if ($equalHeight) {
			// Initiate gifbuilder object in order to get dimensions AND calculate the imageWidth's
			$gifCreator = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
			$gifCreator->init();
			$relations_cols = array();
			// contains the individual width of all images after scaling to $equalHeight
			$imgWidths = array();
			for ($a = 0; $a < $imgCount; $a++) {
				$imgKey = $a + $imgStart;
				$imgInfo = $gifCreator->getImageDimensions($imgPath . $imgs[$imgKey]);
				// relationship between the original height and the wished height
				$rel = $imgInfo[1] / $equalHeight;
				// if relations is zero, then the addition of this value is omitted as the image is not expected to display because of some error.
				if ($rel) {
					$imgWidths[$a] = $imgInfo[0] / $rel;
					// counts the total width of the row with the new height taken into consideration.
					$relations_cols[floor($a / $colCount)] += $imgWidths[$a];
				}
			}
		}
		// Fetches pictures
		$splitArr = array();
		$splitArr['imgObjNum'] = $conf['imgObjNum'];
		$splitArr = $GLOBALS['TSFE']->tmpl->splitConfArray($splitArr, $imgCount);
		// Contains the width of every image row
		$imageRowsFinalWidths = array();
		// Array index of $imgsTag will be the same as in $imgs, but $imgsTag only contains the images that are actually shown
		$imgsTag = array();
		$origImages = array();
		$rowIdx = 0;
		for ($a = 0; $a < $imgCount; $a++) {
			$imgKey = $a + $imgStart;
			// if the image cannot be interpreted as integer (therefore filename and no FAL id), add the image path
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($imgs[$imgKey])) {
				$totalImagePath = intval($imgs[$imgKey]);
			} else {
				$totalImagePath = $imgPath . $imgs[$imgKey];
			}
			// register IMG_NUM is kept for backwards compatibility
			$GLOBALS['TSFE']->register['IMAGE_NUM'] = $imgKey;
			$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $imgKey;
			$GLOBALS['TSFE']->register['ORIG_FILENAME'] = $totalImagePath;
			$this->cObj->data[$this->cObj->currentValKey] = $totalImagePath;
			$imgObjNum = intval($splitArr[$a]['imgObjNum']);
			$imgConf = $conf[$imgObjNum . '.'];
			if ($equalHeight) {
				if ($a % $colCount == 0) {
					// A new row starts
					// Reset accumulated net width
					$accumWidth = 0;
					// Reset accumulated desired width
					$accumDesiredWidth = 0;
					$rowTotalMaxW = $relations_cols[$rowIdx];
					if ($rowTotalMaxW > $netW && $netW > 0) {
						$scale = $rowTotalMaxW / $netW;
					} else {
						$scale = 1;
					}
					$desiredHeight = $equalHeight / $scale;
					$rowIdx++;
				}
				// This much width is available for the remaining images in this row (int)
				$availableWidth = $netW - $accumWidth;
				// Theoretical width of resized image. (float)
				$desiredWidth = $imgWidths[$a] / $scale;
				// Add this width. $accumDesiredWidth becomes the desired horizontal position
				$accumDesiredWidth += $desiredWidth;
				// Calculate width by comparing actual and desired horizontal position.
				// this evenly distributes rounding errors across all images in this row.
				$suggestedWidth = round($accumDesiredWidth - $accumWidth);
				// finalImgWidth may not exceed $availableWidth
				$finalImgWidth = (int) min($availableWidth, $suggestedWidth);
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
				$imgConf['file.']['maxW'] = $columnWidths[$a % $colCount];
			}
			$titleInLink = $this->cObj->stdWrap($imgConf['titleInLink'], $imgConf['titleInLink.']);
			$titleInLinkAndImg = $this->cObj->stdWrap($imgConf['titleInLinkAndImg'], $imgConf['titleInLinkAndImg.']);
			$oldATagParms = $GLOBALS['TSFE']->ATagParams;
			if ($titleInLink) {
				// Title in A-tag instead of IMG-tag
				$titleText = trim($this->cObj->stdWrap($imgConf['titleText'], $imgConf['titleText.']));
				if ($titleText) {
					// This will be used by the IMAGE call later:
					$GLOBALS['TSFE']->ATagParams .= ' title="' . $titleText . '"';
				}
			}

			// hook to allow custom rendering of a single element
			// This hook is needed to render alternative content which is not just a plain image,
			// like showing other FAL content, like videos, things which need to be embedded as JS, ...
			$customRendering = '';
			if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['css_styled_content']['pi1_hooks']['render_singleMediaElement'])
				&& is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['css_styled_content']['pi1_hooks']['render_singleMediaElement'])) {
				$hookParameters = array(
					'file' => $totalImagePath,
					'imageConfiguration' => $imgConf
				);

				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['css_styled_content']['pi1_hooks']['render_singleMediaElement'] as $reference) {
					$customRendering = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($reference, $hookParameters, $this);
					// if there is a renderer found, don't run through the other renderers
					if (!empty($customRendering)) {
						break;
					}
				}
			}

			if (!empty($customRendering)) {
				$imgsTag[$imgKey] = $customRendering;
			} elseif ($imgConf || $imgConf['file']) {
				if ($this->cObj->image_effects[$image_effects]) {
					$imgConf['file.']['params'] .= ' ' . $this->cObj->image_effects[$image_effects];
				}
				if ($image_frames) {
					if (is_array($conf['image_frames.'][$image_frames . '.'])) {
						$imgConf['file.']['m.'] = $conf['image_frames.'][$image_frames . '.'];
					}
				}
				if ($image_compression && $imgConf['file'] != 'GIFBUILDER') {
					if ($image_compression == 1) {
						$tempImport = $imgConf['file.']['import'];
						$tempImport_dot = $imgConf['file.']['import.'];
						unset($imgConf['file.']);
						$imgConf['file.']['import'] = $tempImport;
						$imgConf['file.']['import.'] = $tempImport_dot;
					} elseif (isset($this->cObj->image_compression[$image_compression])) {
						$imgConf['file.']['params'] .= ' ' . $this->cObj->image_compression[$image_compression]['params'];
						$imgConf['file.']['ext'] = $this->cObj->image_compression[$image_compression]['ext'];
						unset($imgConf['file.']['ext.']);
					}
				}
				if ($titleInLink && !$titleInLinkAndImg) {
					// Check if the image will be linked
					$link = $this->cObj->imageLinkWrap('', $totalImagePath, $imgConf['imageLinkWrap.']);
					if ($link) {
						// Title in A-tag only (set above: ATagParams), not in IMG-tag
						unset($imgConf['titleText']);
						unset($imgConf['titleText.']);
						$imgConf['emptyTitleHandling'] = 'removeAttr';
					}
				}
				$imgsTag[$imgKey] = $this->cObj->IMAGE($imgConf);
			} else {
				// currentValKey !!!
				$imgsTag[$imgKey] = $this->cObj->IMAGE(array('file' => $totalImagePath));
			}
			// Restore our ATagParams
			$GLOBALS['TSFE']->ATagParams = $oldATagParms;
			// Store the original filepath
			$origImages[$imgKey] = $GLOBALS['TSFE']->lastImageInfo;
			if ($GLOBALS['TSFE']->lastImageInfo[0] == 0) {
				$imageRowsFinalWidths[floor($a / $colCount)] += $this->cObj->data['imagewidth'];
			} else {
				$imageRowsFinalWidths[floor($a / $colCount)] += $GLOBALS['TSFE']->lastImageInfo[0];
			}
		}
		// How much space will the image-block occupy?
		$imageBlockWidth = max($imageRowsFinalWidths) + $colspacing * ($colCount - 1) + $colCount * $border * ($borderSpace + $borderThickness) * 2;
		$GLOBALS['TSFE']->register['rowwidth'] = $imageBlockWidth;
		$GLOBALS['TSFE']->register['rowWidthPlusTextMargin'] = $imageBlockWidth + $textMargin;
		// noRows is in fact just one ROW, with the amount of columns specified, where the images are placed in.
		// noCols is just one COLUMN, each images placed side by side on each row
		$noRows = $this->cObj->stdWrap($conf['noRows'], $conf['noRows.']);
		$noCols = $this->cObj->stdWrap($conf['noCols'], $conf['noCols.']);
		// noRows overrides noCols. They cannot exist at the same time.
		if ($noRows) {
			$noCols = 0;
			$rowCount = 1;
		}
		if ($noCols) {
			$colCount = 1;
		}
		// Edit icons:
		if (!is_array($conf['editIcons.'])) {
			$conf['editIcons.'] = array();
		}
		$editIconsHTML = $conf['editIcons'] && $GLOBALS['TSFE']->beUserLogin ? $this->cObj->editIcons('', $conf['editIcons'], $conf['editIcons.']) : '';
		// If noRows, we need multiple imagecolumn wraps
		$imageWrapCols = 1;
		if ($noRows) {
			$imageWrapCols = $colCount;
		}
		// User wants to separate the rows, but only do that if we do have rows
		$separateRows = $this->cObj->stdWrap($conf['separateRows'], $conf['separateRows.']);
		if ($noRows) {
			$separateRows = 0;
		}
		if ($rowCount == 1) {
			$separateRows = 0;
		}
		if ($accessibilityMode) {
			$imagesInColumns = round($imgCount / ($rowCount * $colCount), 0, PHP_ROUND_HALF_UP);
			// Apply optionSplit to the list of classes that we want to add to each column
			$addClassesCol = $conf['addClassesCol'];
			if (isset($conf['addClassesCol.'])) {
				$addClassesCol = $this->cObj->stdWrap($addClassesCol, $conf['addClassesCol.']);
			}
			$addClassesColConf = $GLOBALS['TSFE']->tmpl->splitConfArray(array('addClassesCol' => $addClassesCol), $colCount);
			// Apply optionSplit to the list of classes that we want to add to each image
			$addClassesImage = $conf['addClassesImage'];
			if (isset($conf['addClassesImage.'])) {
				$addClassesImage = $this->cObj->stdWrap($addClassesImage, $conf['addClassesImage.']);
			}
			$addClassesImageConf = $GLOBALS['TSFE']->tmpl->splitConfArray(array('addClassesImage' => $addClassesImage), $imagesInColumns);
			$rows = array();
			$currentImage = 0;
			// Set the class for the caption (split or global)
			$classCaptionAlign = array(
				'center' => 'csc-textpic-caption-c',
				'right' => 'csc-textpic-caption-r',
				'left' => 'csc-textpic-caption-l'
			);
			$captionAlign = $this->cObj->stdWrap($conf['captionAlign'], $conf['captionAlign.']);
			// Iterate over the rows
			for ($rowCounter = 1; $rowCounter <= $rowCount; $rowCounter++) {
				$rowColumns = array();
				// Iterate over the columns
				for ($columnCounter = 1; $columnCounter <= $colCount; $columnCounter++) {
					$columnImages = array();
					// Iterate over the amount of images allowed in a column
					for ($imagesCounter = 1; $imagesCounter <= $imagesInColumns; $imagesCounter++) {
						$image = NULL;
						$splitCaption = NULL;
						$imageMarkers = ($captionMarkers = array());
						$single = '&nbsp;';
						// Set the key of the current image
						$imageKey = $currentImage + $imgStart;
						// Register IMAGE_NUM_CURRENT for the caption
						$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $imageKey;
						$this->cObj->data[$this->cObj->currentValKey] = $origImages[$imageKey]['origFile'];
						// Get the image if not an empty cell
						if (isset($imgsTag[$imageKey])) {
							$image = $this->cObj->stdWrap($imgsTag[$imageKey], $conf['imgTagStdWrap.']);
							// Add the edit icons
							if ($editIconsHTML) {
								$image .= $this->cObj->stdWrap($editIconsHTML, $conf['editIconsStdWrap.']);
							}
							// Wrap the single image
							$single = $this->cObj->stdWrap($image, $conf['singleStdWrap.']);
							// Get the caption
							if (!$renderGlobalCaption) {
								$imageMarkers['caption'] = $this->cObj->stdWrap($this->cObj->cObjGet($conf['caption.'], 'caption.'), $conf['caption.']);
								if ($captionAlign) {
									$captionMarkers['classes'] = ' ' . $classCaptionAlign[$captionAlign];
								}
								$imageMarkers['caption'] = $this->cObj->substituteMarkerArray($imageMarkers['caption'], $captionMarkers, '###|###', 1, 1);
							}
							if ($addClassesImageConf[$imagesCounter - 1]['addClassesImage']) {
								$imageMarkers['classes'] = ' ' . $addClassesImageConf[($imagesCounter - 1)]['addClassesImage'];
							}
						}
						$columnImages[] = $this->cObj->substituteMarkerArray($single, $imageMarkers, '###|###', 1, 1);
						$currentImage++;
					}
					$rowColumn = $this->cObj->stdWrap(implode(LF, $columnImages), $conf['columnStdWrap.']);
					// Start filling the markers for columnStdWrap
					$columnMarkers = array();
					if ($addClassesColConf[$columnCounter - 1]['addClassesCol']) {
						$columnMarkers['classes'] = ' ' . $addClassesColConf[($columnCounter - 1)]['addClassesCol'];
					}
					$rowColumns[] = $this->cObj->substituteMarkerArray($rowColumn, $columnMarkers, '###|###', 1, 1);
				}
				if ($noRows) {
					$rowConfiguration = $conf['noRowsStdWrap.'];
				} elseif ($rowCounter == $rowCount) {
					$rowConfiguration = $conf['lastRowStdWrap.'];
				} else {
					$rowConfiguration = $conf['rowStdWrap.'];
				}
				$row = $this->cObj->stdWrap(implode(LF, $rowColumns), $rowConfiguration);
				// Start filling the markers for columnStdWrap
				$rowMarkers = array();
				$rows[] = $this->cObj->substituteMarkerArray($row, $rowMarkers, '###|###', 1, 1);
			}
			$images = $this->cObj->stdWrap(implode(LF, $rows), $conf['allStdWrap.']);
			// Start filling the markers for allStdWrap
			$allMarkers = array();
			$classes = array();
			// Add the global caption to the allStdWrap marker array if set
			if ($globalCaption) {
				$allMarkers['caption'] = $globalCaption;
				if ($captionAlign) {
					$classes[] = $classCaptionAlign[$captionAlign];
				}
			}
			// Set the margin for image + text, no wrap always to avoid multiple stylesheets
			$noWrapMargin = (int) (($maxWInText ? $maxWInText : $fiftyPercentWidthInText) + intval(
				$this->cObj->stdWrap($conf['textMargin'], $conf['textMargin.'])
			));
			$this->addPageStyle('.csc-textpic-intext-right-nowrap .csc-textpic-text', 'margin-right: ' . $noWrapMargin . 'px;');
			$this->addPageStyle('.csc-textpic-intext-left-nowrap .csc-textpic-text', 'margin-left: ' . $noWrapMargin . 'px;');
			// Beside Text where the image block width is not equal to maxW
			if ($contentPosition == 24 && $maxW != $imageBlockWidth) {
				$noWrapMargin = $imageBlockWidth + $textMargin;
				// Beside Text, Right
				if ($imagePosition == 1) {
					$this->addPageStyle('.csc-textpic-intext-right-nowrap-' . $noWrapMargin . ' .csc-textpic-text', 'margin-right: ' . $noWrapMargin . 'px;');
					$classes[] = 'csc-textpic-intext-right-nowrap-' . $noWrapMargin;
				} elseif ($imagePosition == 2) {
					$this->addPageStyle('.csc-textpic-intext-left-nowrap-' . $noWrapMargin . ' .csc-textpic-text', 'margin-left: ' . $noWrapMargin . 'px;');
					$classes[] = 'csc-textpic-intext-left-nowrap-' . $noWrapMargin;
				}
			}
			// Add the border class if needed
			if ($border) {
				$classes[] = $conf['borderClass'] ? $conf['borderClass'] : 'csc-textpic-border';
			}
			// Add the class for equal height if needed
			if ($equalHeight) {
				$classes[] = 'csc-textpic-equalheight';
			}
			$addClasses = $this->cObj->stdWrap($conf['addClasses'], $conf['addClasses.']);
			if ($addClasses) {
				$classes[] = $addClasses;
			}
			if ($classes) {
				$class = ' ' . implode(' ', $classes);
			}
			// Fill the markers for the allStdWrap
			$images = $this->cObj->substituteMarkerArray($images, $allMarkers, '###|###', 1, 1);
		} else {
			// Apply optionSplit to the list of classes that we want to add to each image
			$addClassesImage = $conf['addClassesImage'];
			if (isset($conf['addClassesImage.'])) {
				$addClassesImage = $this->cObj->stdWrap($addClassesImage, $conf['addClassesImage.']);
			}
			$addClassesImageConf = $GLOBALS['TSFE']->tmpl->splitConfArray(array('addClassesImage' => $addClassesImage), $colCount);
			// Render the images
			$images = '';
			for ($c = 0; $c < $imageWrapCols; $c++) {
				$tmpColspacing = $colspacing;
				if ($c == $imageWrapCols - 1 && $imagePosition == 2 || $c == 0 && ($imagePosition == 1 || $imagePosition == 0)) {
					// Do not add spacing after column if we are first column (left) or last column (center/right)
					$tmpColspacing = 0;
				}
				$thisImages = '';
				$allRows = '';
				$maxImageSpace = 0;
				$imgsTagCount = count($imgsTag);
				for ($i = $c; $i < $imgsTagCount; $i = $i + $imageWrapCols) {
					$imgKey = $i + $imgStart;
					$colPos = $i % $colCount;
					if ($separateRows && $colPos == 0) {
						$thisRow = '';
					}
					// Render one image
					if ($origImages[$imgKey][0] == 0) {
						$imageSpace = $this->cObj->data['imagewidth'] + $border * ($borderSpace + $borderThickness) * 2;
					} else {
						$imageSpace = $origImages[$imgKey][0] + $border * ($borderSpace + $borderThickness) * 2;
					}
					$GLOBALS['TSFE']->register['IMAGE_NUM'] = $imgKey;
					$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $imgKey;
					$GLOBALS['TSFE']->register['ORIG_FILENAME'] = $origImages[$imgKey]['origFile'];
					$GLOBALS['TSFE']->register['imagewidth'] = $origImages[$imgKey][0];
					$GLOBALS['TSFE']->register['imagespace'] = $imageSpace;
					$GLOBALS['TSFE']->register['imageheight'] = $origImages[$imgKey][1];
					if ($imageSpace > $maxImageSpace) {
						$maxImageSpace = $imageSpace;
					}
					$thisImage = '';
					$thisImage .= $this->cObj->stdWrap($imgsTag[$imgKey], $conf['imgTagStdWrap.']);
					if (!$renderGlobalCaption) {
						$thisImage .= $this->cObj->stdWrap($this->cObj->cObjGet($conf['caption.'], 'caption.'), $conf['caption.']);
					}
					if ($editIconsHTML) {
						$thisImage .= $this->cObj->stdWrap($editIconsHTML, $conf['editIconsStdWrap.']);
					}
					$thisImage = $this->cObj->stdWrap($thisImage, $conf['oneImageStdWrap.']);
					$classes = '';
					if ($addClassesImageConf[$colPos]['addClassesImage']) {
						$classes = ' ' . $addClassesImageConf[$colPos]['addClassesImage'];
					}
					$thisImage = str_replace('###CLASSES###', $classes, $thisImage);
					if ($separateRows) {
						$thisRow .= $thisImage;
					} else {
						$allRows .= $thisImage;
					}
					$GLOBALS['TSFE']->register['columnwidth'] = $maxImageSpace + $tmpColspacing;
					// Close this row at the end (colCount), or the last row at the final end
					if ($separateRows && $i + 1 == count($imgsTag)) {
						// Close the very last row with either normal configuration or lastRow stdWrap
						$allRows .= $this->cObj->stdWrap(
							$thisRow,
							is_array($conf['imageLastRowStdWrap.']) ? $conf['imageLastRowStdWrap.'] : $conf['imageRowStdWrap.']
						);
					} elseif ($separateRows && $colPos == $colCount - 1) {
						$allRows .= $this->cObj->stdWrap($thisRow, $conf['imageRowStdWrap.']);
					}
				}
				if ($separateRows) {
					$thisImages .= $allRows;
				} else {
					$thisImages .= $this->cObj->stdWrap($allRows, $conf['noRowsStdWrap.']);
				}
				if ($noRows) {
					// Only needed to make columns, rather than rows:
					$images .= $this->cObj->stdWrap($thisImages, $conf['imageColumnStdWrap.']);
				} else {
					$images .= $thisImages;
				}
			}
			// Add the global caption, if not split
			if ($globalCaption) {
				$images .= $globalCaption;
			}
			// CSS-classes
			$captionClass = '';
			$classCaptionAlign = array(
				'center' => 'csc-textpic-caption-c',
				'right' => 'csc-textpic-caption-r',
				'left' => 'csc-textpic-caption-l'
			);
			$captionAlign = $this->cObj->stdWrap($conf['captionAlign'], $conf['captionAlign.']);
			if ($captionAlign) {
				$captionClass = $classCaptionAlign[$captionAlign];
			}
			$borderClass = '';
			if ($border) {
				$borderClass = $conf['borderClass'] ? $conf['borderClass'] : 'csc-textpic-border';
			}
			// Multiple classes with all properties, to be styled in CSS
			$class = '';
			$class .= $borderClass ? ' ' . $borderClass : '';
			$class .= $captionClass ? ' ' . $captionClass : '';
			$class .= $equalHeight ? ' csc-textpic-equalheight' : '';
			$addClasses = $this->cObj->stdWrap($conf['addClasses'], $conf['addClasses.']);
			$class .= $addClasses ? ' ' . $addClasses : '';
			// Do we need a width in our wrap around images?
			$imgWrapWidth = '';
			if ($position == 0 || $position == 8) {
				// For 'center' we always need a width: without one, the margin:auto trick won't work
				$imgWrapWidth = $imageBlockWidth;
			}
			if ($rowCount > 1) {
				// For multiple rows we also need a width, so that the images will wrap
				$imgWrapWidth = $imageBlockWidth;
			}
			if ($globalCaption) {
				// If we have a global caption, we need the width so that the caption will wrap
				$imgWrapWidth = $imageBlockWidth;
			}
			// Wrap around the whole image block
			$GLOBALS['TSFE']->register['totalwidth'] = $imgWrapWidth;
			if ($imgWrapWidth) {
				$images = $this->cObj->stdWrap($images, $conf['imageStdWrap.']);
			} else {
				$images = $this->cObj->stdWrap($images, $conf['imageStdWrapNoWidth.']);
			}
		}
		$output = $this->cObj->cObjGetSingle($conf['layout'], $conf['layout.']);
		$output = str_replace('###TEXT###', $content, $output);
		$output = str_replace('###IMAGES###', $images, $output);
		$output = str_replace('###CLASSES###', $class, $output);
		if ($conf['stdWrap.']) {
			$output = $this->cObj->stdWrap($output, $conf['stdWrap.']);
		}
		return $output;
	}

	/***********************************
	 * Rendering of Content Element properties
	 ***********************************/

	/**
	 * Add top or bottom margin to the content element
	 *
	 * Constructs and adds a class to the content element. This class selector
	 * and its declaration are added to the specific page styles.
	 *
	 * @param string $content Content input. Not used, ignore.
	 * @param array $configuration TypoScript configuration
	 * @return string The class name
	 */
	public function renderSpace($content, array $configuration) {
		// Look for hook before running default code for function
		if (method_exists($this, 'hookRequest') && ($hookObject = $this->hookRequest('renderSpace'))) {
			return $hookObject->renderSpace($content, $configuration);
		}
		if (isset($configuration['space']) && in_array($configuration['space'], array('before', 'after'))) {
			$constant = (int) $configuration['constant'];
			if ($configuration['space'] === 'before') {
				$value = $constant + $this->cObj->data['spaceBefore'];
				$declaration = 'margin-top: ' . $value . 'px;';
			} else {
				$value = $constant + $this->cObj->data['spaceAfter'];
				$declaration = 'margin-bottom: ' . $value . 'px;';
			}
			if (!empty($value)) {
				if ($configuration['classStdWrap.']) {
					$className = $this->cObj->stdWrap($value, $configuration['classStdWrap.']);
				} else {
					$className = $value;
				}
				$selector = '.' . trim($className);
				$this->addPageStyle($selector, $declaration);
				return $className;
			}
		}
	}

	/************************************
	 * Helper functions
	 ************************************/

	/**
	 * Returns a link text string which replaces underscores in filename with
	 * blanks.
	 *
	 * Has the possibility to cut off FileType.
	 *
	 * @param array $links
	 * @param string $fileName
	 * @param boolean $useSpaces
	 * @param boolean $cutFileExtension
	 * @return array modified array with new link text
	 */
	protected function beautifyFileLink(array $links, $fileName, $useSpaces = FALSE, $cutFileExtension = FALSE) {
		$linkText = $fileName;
		if ($useSpaces) {
			$linkText = str_replace('_', ' ', $linkText);
		}
		if ($cutFileExtension) {
			$pos = strrpos($linkText, '.');
			$linkText = substr($linkText, 0, $pos);
		}
		$links[1] = str_replace('>' . $fileName . '<', '>' . htmlspecialchars($linkText) . '<', $links[1]);
		return $links;
	}

	/**
	 * Returns table attributes for uploads / tables.
	 *
	 * @param array $conf TypoScript configuration array
	 * @param integer $type The "layout" type
	 * @return array Array with attributes inside.
	 * @todo Define visibility
	 */
	public function getTableAttributes($conf, $type) {
		// Initializing:
		$tableTagParams_conf = $conf['tableParams_' . $type . '.'];
		$border = $this->cObj->data['table_border'] ? intval($this->cObj->data['table_border']) : $tableTagParams_conf['border'];
		$cellSpacing = $this->cObj->data['table_cellspacing'] ? intval($this->cObj->data['table_cellspacing']) : $tableTagParams_conf['cellspacing'];
		$cellPadding = $this->cObj->data['table_cellpadding'] ? intval($this->cObj->data['table_cellpadding']) : $tableTagParams_conf['cellpadding'];
		$summary = trim(htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_summary')));
		// Create table attributes and classes array:
		$tableTagParams = ($classes = array());
		// Table attributes for all doctypes except HTML5
		if ($GLOBALS['TSFE']->config['config']['doctype'] !== 'html5') {
			$tableTagParams['border'] = $border;
			$tableTagParams['cellspacing'] = $cellSpacing;
			$tableTagParams['cellpadding'] = $cellPadding;
			if ($summary) {
				$tableTagParams['summary'] = $summary;
			}
		} else {
			if ($border) {
				// Border property has changed, now with class
				$borderClass = 'contenttable-border-' . $border;
				$borderDeclaration = 'border-width: ' . $border . 'px; border-style: solid;';
				$this->addPageStyle('.' . $borderClass, $borderDeclaration);
				$classes[] = $borderClass;
			}
			if ($cellSpacing) {
				// Border attribute for HTML5 is 1 when there is cell spacing
				$tableTagParams['border'] = 1;
				// Use CSS3 border-spacing in class to have cell spacing
				$cellSpacingClass = 'contenttable-cellspacing-' . $cellSpacing;
				$cellSpacingDeclaration = 'border-spacing: ' . $cellSpacing . 'px;';
				$this->addPageStyle('.' . $cellSpacingClass, $cellSpacingDeclaration);
				$classes[] = $cellSpacingClass;
			}
			if ($cellPadding) {
				// Cell padding property has changed, now with class
				$cellPaddingClass = 'contenttable-cellpadding-' . $cellPadding;
				$cellSpacingSelector = '.' . $cellPaddingClass . ' td, .' . $cellPaddingClass . ' th';
				$cellPaddingDeclaration = 'padding: ' . $cellPadding . 'px;';
				$this->addPageStyle($cellSpacingSelector, $cellPaddingDeclaration);
				$classes[] = $cellPaddingClass;
			}
		}
		// Background color is class
		if (isset($conf['color.'][$this->cObj->data['table_bgColor']]) && !empty($conf['color.'][$this->cObj->data['table_bgColor']])) {
			$classes[] = 'contenttable-color-' . $this->cObj->data['table_bgColor'];
		}
		if (!empty($classes)) {
			$tableTagParams['class'] = ' ' . implode(' ', $classes);
		}
		// Return result:
		return $tableTagParams;
	}

	/**
	 * Add a style to the page, specific for this page
	 *
	 * The selector can be a contextual selector, like '#id .class p'
	 * The presence of the selector is checked to avoid multiple entries of the
	 * same selector.
	 *
	 * @param string $selector The selector
	 * @param string $declaration The declaration
	 * @return void
	 */
	protected function addPageStyle($selector, $declaration) {
		if (!isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_cssstyledcontent.']['_CSS_PAGE_STYLE'])) {
			$GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_cssstyledcontent.']['_CSS_PAGE_STYLE'] = array();
		}
		if (!isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_cssstyledcontent.']['_CSS_PAGE_STYLE'][$selector])) {
			$GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_cssstyledcontent.']['_CSS_PAGE_STYLE'][$selector] = TAB . $selector . ' { ' . $declaration . ' }';
		}
	}

	/**
	 * Returns an object reference to the hook object if any
	 *
	 * @param string $functionName Name of the function you want to call / hook key
	 * @return object Hook object, if any. Otherwise NULL.
	 * @todo Define visibility
	 */
	public function hookRequest($functionName) {
		global $TYPO3_CONF_VARS;
		// Hook: menuConfig_preProcessModMenu
		if ($TYPO3_CONF_VARS['EXTCONF']['css_styled_content']['pi1_hooks'][$functionName]) {
			$hookObj = GeneralUtility::getUserObj($TYPO3_CONF_VARS['EXTCONF']['css_styled_content']['pi1_hooks'][$functionName]);
			if (method_exists($hookObj, $functionName)) {
				$hookObj->pObj = $this;
				return $hookObj;
			}
		}
	}

}
?>
