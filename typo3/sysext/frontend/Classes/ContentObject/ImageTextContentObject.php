<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2013 Steffen Kamper <steffen@typo3.org>
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
 * Contains IMAGE class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class ImageTextContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, IMAGE
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		$content = '';
		if (isset($conf['text.'])) {
			$text = $this->cObj->cObjGet($conf['text.'], 'text.');
			// this gets the surrounding content
			$content .= $this->cObj->stdWrap($text, $conf['text.']);
		}
		$imgList = isset($conf['imgList.']) ? trim($this->cObj->stdWrap($conf['imgList'], $conf['imgList.'])) : trim($conf['imgList']);
		if ($imgList) {
			$imgs = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $imgList);
			$imgStart = isset($conf['imgStart.']) ? intval($this->cObj->stdWrap($conf['imgStart'], $conf['imgStart.'])) : intval($conf['imgStart']);
			$imgCount = count($imgs) - $imgStart;
			$imgMax = isset($conf['imgMax.']) ? intval($this->cObj->stdWrap($conf['imgMax'], $conf['imgMax.'])) : intval($conf['imgMax']);
			if ($imgMax) {
				// Reduces the number of images.
				$imgCount = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($imgCount, 0, $imgMax);
			}
			$imgPath = isset($conf['imgPath.']) ? $this->cObj->stdWrap($conf['imgPath'], $conf['imgPath.']) : $conf['imgPath'];
			// initialisation
			$caption = '';
			$captionArray = array();
			if (!$conf['captionSplit'] && !$conf['imageTextSplit'] && isset($conf['caption.'])) {
				$caption = $this->cObj->cObjGet($conf['caption.'], 'caption.');
				// Global caption, no splitting
				$caption = $this->cObj->stdWrap($caption, $conf['caption.']);
			}
			if ($conf['captionSplit'] && $conf['captionSplit.']['cObject']) {
				$legacyCaptionSplit = 1;
				$capSplit = isset($conf['captionSplit.']['token.']) ? $this->cObj->stdWrap($conf['captionSplit.']['token'], $conf['captionSplit.']['token.']) : $conf['captionSplit.']['token'];
				if (!$capSplit) {
					$capSplit = LF;
				}
				$captionArray = explode($capSplit, $this->cObj->cObjGetSingle($conf['captionSplit.']['cObject'], $conf['captionSplit.']['cObject.'], 'captionSplit.cObject'));
				foreach ($captionArray as $ca_key => $ca_val) {
					$captionArray[$ca_key] = isset($conf['captionSplit.']['stdWrap.']) ? $this->cObj->stdWrap(trim($captionArray[$ca_key]), $conf['captionSplit.']['stdWrap.']) : trim($captionArray[$ca_key]);
				}
			}
			$tablecode = '';
			$position = isset($conf['textPos.']) ? $this->cObj->stdWrap($conf['textPos'], $conf['textPos.']) : $conf['textPos'];
			$tmppos = $position & 7;
			$contentPosition = $position & 24;
			$align = $this->cObj->align[$tmppos];
			$cap = $caption ? 1 : 0;
			$txtMarg = isset($conf['textMargin.']) ? intval($this->cObj->stdWrap($conf['textMargin'], $conf['textMargin.'])) : intval($conf['textMargin']);
			if (!$conf['textMargin_outOfText'] && $contentPosition < 16) {
				$txtMarg = 0;
			}
			$cols = isset($conf['cols.']) ? intval($this->cObj->stdWrap($conf['cols'], $conf['cols.'])) : intval($conf['cols']);
			$rows = isset($conf['rows.']) ? intval($this->cObj->stdWrap($conf['rows'], $conf['rows.'])) : intval($conf['rows']);
			$colspacing = isset($conf['colSpace.']) ? intval($this->cObj->stdWrap($conf['colSpace'], $conf['colSpace.'])) : intval($conf['colSpace']);
			$rowspacing = isset($conf['rowSpace.']) ? intval($this->cObj->stdWrap($conf['rowSpace'], $conf['rowSpace.'])) : intval($conf['rowSpace']);
			$border = isset($conf['border.']) ? intval($this->cObj->stdWrap($conf['border'], $conf['border.'])) : intval($conf['border']);
			$border = $border ? 1 : 0;
			if ($border) {
				$borderColor = isset($conf['borderCol.']) ? $this->cObj->stdWrap($conf['borderCol'], $conf['borderCol.']) : $conf['borderCol'];
				if (!$borderColor) {
					$borderColor = 'black';
				}
				$borderThickness = isset($conf['borderThick.']) ? intval($this->cObj->stdWrap($conf['borderThick'], $conf['borderThick.'])) : intval($conf['borderThick']);
				if (!$borderThickness) {
					$borderThickness = 'black';
				}
			}
			$caption_align = isset($conf['captionAlign.']) ? $this->cObj->stdWrap($conf['captionAlign'], $conf['captionAlign.']) : $conf['captionAlign'];
			if (!$caption_align) {
				$caption_align = $align;
			}
			// Generate cols
			$colCount = $cols > 1 ? $cols : 1;
			if ($colCount > $imgCount) {
				$colCount = $imgCount;
			}
			$rowCount = $colCount > 1 ? ceil($imgCount / $colCount) : $imgCount;
			// Generate rows
			if ($rows > 1) {
				$rowCount = $rows;
				if ($rowCount > $imgCount) {
					$rowCount = $imgCount;
				}
				$colCount = $rowCount > 1 ? ceil($imgCount / $rowCount) : $imgCount;
			}
			// Max Width
			$colRelations = isset($conf['colRelations.']) ? trim($this->cObj->stdWrap($conf['colRelations'], $conf['colRelations.'])) : trim($conf['colRelations']);
			$maxW = isset($conf['maxW.']) ? intval($this->cObj->stdWrap($conf['maxW'], $conf['maxW.'])) : intval($conf['maxW']);
			$maxWInText = isset($conf['maxWInText.']) ? intval($this->cObj->stdWrap($conf['maxWInText'], $conf['maxWInText.'])) : intval($conf['maxWInText']);
			// If maxWInText is not set, it's calculated to the 50 % of the max...
			if (!$maxWInText) {
				$maxWInText = round($maxW / 2);
			}
			// inText
			if ($maxWInText && $contentPosition >= 16) {
				$maxW = $maxWInText;
			}
			// If there is a max width and if colCount is greater than  column
			if ($maxW && $colCount > 0) {
				$maxW = ceil(($maxW - $colspacing * ($colCount - 1) - $colCount * $border * $borderThickness * 2) / $colCount);
			}
			// Create the relation between rows
			$colMaxW = array();
			if ($colRelations) {
				$rel_parts = explode(':', $colRelations);
				$rel_total = 0;
				for ($a = 0; $a < $colCount; $a++) {
					$rel_parts[$a] = intval($rel_parts[$a]);
					$rel_total += $rel_parts[$a];
				}
				if ($rel_total) {
					for ($a = 0; $a < $colCount; $a++) {
						$colMaxW[$a] = round($maxW * $colCount / $rel_total * $rel_parts[$a]);
					}
					// The difference in size between the largest and smalles must be within a factor of ten.
					if (min($colMaxW) <= 0 || max($rel_parts) / min($rel_parts) > 10) {
						$colMaxW = array();
					}
				}
			}
			$image_compression = isset($conf['image_compression.']) ? intval($this->cObj->stdWrap($conf['image_compression'], $conf['image_compression.'])) : intval($conf['image_compression']);
			$image_effects = isset($conf['image_effects.']) ? intval($this->cObj->stdWrap($conf['image_effects'], $conf['image_effects.'])) : intval($conf['image_effects']);
			$image_frames = isset($conf['image_frames.']['key.']) ? intval($this->cObj->stdWrap($conf['image_frames.']['key'], $conf['image_frames.']['key.'])) : intval($conf['image_frames.']['key']);
			// Fetches pictures
			$splitArr = array();
			$splitArr['imgObjNum'] = $conf['imgObjNum'];
			$splitArr = $GLOBALS['TSFE']->tmpl->splitConfArray($splitArr, $imgCount);
			// EqualHeight
			$equalHeight = isset($conf['equalH.']) ? intval($this->cObj->stdWrap($conf['equalH'], $conf['equalH.'])) : intval($conf['equalH']);
			// Initiate gifbuilder object in order to get dimensions AND calculate the imageWidth's
			if ($equalHeight) {
				$gifCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
				$gifCreator->init();
				$relations = array();
				$relations_cols = array();
				$totalMaxW = $maxW * $colCount;
				for ($a = 0; $a < $imgCount; $a++) {
					$imgKey = $a + $imgStart;
					$imgInfo = $gifCreator->getImageDimensions($imgPath . $imgs[$imgKey]);
					// relationship between the original height and the wished height
					$relations[$a] = $imgInfo[1] / $equalHeight;
					// if relations is zero, then the addition of this value is omitted as
					// the image is not expected to display because of some error.
					if ($relations[$a]) {
						// Counts the total width of the row with the new height taken into consideration.
						$relations_cols[floor($a / $colCount)] += $imgInfo[0] / $relations[$a];
					}
				}
			}
			// Contains the width of every image row
			$imageRowsFinalWidths = array();
			$imageRowsMaxHeights = array();
			$imgsTag = array();
			$origImages = array();
			for ($a = 0; $a < $imgCount; $a++) {
				$GLOBALS['TSFE']->register['IMAGE_NUM'] = $a;
				$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $a;
				$imgKey = $a + $imgStart;
				$totalImagePath = $imgPath . $imgs[$imgKey];
				$this->cObj->data[$this->cObj->currentValKey] = $totalImagePath;
				$imgObjNum = intval($splitArr[$a]['imgObjNum']);
				$imgConf = $conf[$imgObjNum . '.'];
				if ($equalHeight) {
					$scale = 1;
					if ($totalMaxW) {
						$rowTotalMaxW = $relations_cols[floor($a / $colCount)];
						if ($rowTotalMaxW > $totalMaxW) {
							$scale = $rowTotalMaxW / $totalMaxW;
						}
					}
					// Transfer info to the imageObject. Please note, that
					$imgConf['file.']['height'] = round($equalHeight / $scale);
					unset($imgConf['file.']['width'], $imgConf['file.']['maxW'], $imgConf['file.']['maxH'], $imgConf['file.']['minW'], $imgConf['file.']['minH'], $imgConf['file.']['width.'], $imgConf['file.']['maxW.'], $imgConf['file.']['maxH.'], $imgConf['file.']['minW.'], $imgConf['file.']['minH.']);
					// Setting this to zero, so that it doesn't disturb
					$maxW = 0;
				}
				if ($maxW) {
					if (count($colMaxW)) {
						$imgConf['file.']['maxW'] = $colMaxW[$a % $colCount];
					} else {
						$imgConf['file.']['maxW'] = $maxW;
					}
				}
				// Image Object supplied:
				if (is_array($imgConf)) {
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
					// "alt", "title" and "longdesc" attributes:
					if (!strlen($imgConf['altText']) && !is_array($imgConf['altText.'])) {
						$imgConf['altText'] = $conf['altText'];
						$imgConf['altText.'] = $conf['altText.'];
					}
					if (!strlen($imgConf['titleText']) && !is_array($imgConf['titleText.'])) {
						$imgConf['titleText'] = $conf['titleText'];
						$imgConf['titleText.'] = $conf['titleText.'];
					}
					if (!strlen($imgConf['longdescURL']) && !is_array($imgConf['longdescURL.'])) {
						$imgConf['longdescURL'] = $conf['longdescURL'];
						$imgConf['longdescURL.'] = $conf['longdescURL.'];
					}
				} else {
					$imgConf = array(
						'altText' => $conf['altText'],
						'titleText' => $conf['titleText'],
						'longdescURL' => $conf['longdescURL'],
						'file' => $totalImagePath
					);
				}
				$imgsTag[$imgKey] = $this->cObj->IMAGE($imgConf);
				// Store the original filepath
				$origImages[$imgKey] = $GLOBALS['TSFE']->lastImageInfo;
				$imageRowsFinalWidths[floor($a / $colCount)] += $GLOBALS['TSFE']->lastImageInfo[0];
				if ($GLOBALS['TSFE']->lastImageInfo[1] > $imageRowsMaxHeights[floor($a / $colCount)]) {
					$imageRowsMaxHeights[floor($a / $colCount)] = $GLOBALS['TSFE']->lastImageInfo[1];
				}
			}
			// Calculating the tableWidth:
			// TableWidth problems: It creates problems if the pictures are NOT as wide as the tableWidth.
			$tableWidth = max($imageRowsFinalWidths) + $colspacing * ($colCount - 1) + $colCount * $border * $borderThickness * 2;
			// Make table for pictures
			$index = ($imgIndex = $imgStart);
			$noRows = isset($conf['noRows.']) ? $this->cObj->stdWrap($conf['noRows'], $conf['noRows.']) : $conf['noRows'];
			$noCols = isset($conf['noCols.']) ? $this->cObj->stdWrap($conf['noCols'], $conf['noCols.']) : $conf['noCols'];
			if ($noRows) {
				$noCols = 0;
			}
			// noRows overrides noCols. They cannot exist at the same time.
			if ($equalHeight) {
				$noCols = 1;
				$noRows = 0;
			}
			$rowCount_temp = 1;
			$colCount_temp = $colCount;
			if ($noRows) {
				$rowCount_temp = $rowCount;
				$rowCount = 1;
			}
			if ($noCols) {
				$colCount = 1;
			}
			// col- and rowspans calculated
			$colspan = $colspacing ? $colCount * 2 - 1 : $colCount;
			$rowspan = ($rowspacing ? $rowCount * 2 - 1 : $rowCount) + $cap;
			// Edit icons:
			if (!is_array($conf['editIcons.'])) {
				$conf['editIcons.'] = array();
			}
			$editIconsHTML = $conf['editIcons'] && $GLOBALS['TSFE']->beUserLogin ? $this->cObj->editIcons('', $conf['editIcons'], $conf['editIcons.']) : '';
			// Strech out table:
			$tablecode = '';
			$flag = 0;
			$noStretchAndMarginCells = isset($conf['noStretchAndMarginCells.']) ? $this->cObj->stdWrap($conf['noStretchAndMarginCells'], $conf['noStretchAndMarginCells.']) : $conf['noStretchAndMarginCells'];
			if ($noStretchAndMarginCells != 1) {
				$tablecode .= '<tr>';
				if ($txtMarg && $align == 'right') {
					// If right aligned, the textborder is added on the right side
					$tablecode .= '<td rowspan="' . ($rowspan + 1) . '" valign="top"><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . $txtMarg . '" height="1" alt="" title="" />' . ($editIconsHTML ? '<br />' . $editIconsHTML : '') . '</td>';
					$editIconsHTML = '';
					$flag = 1;
				}
				$tablecode .= '<td colspan="' . $colspan . '"><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . $tableWidth . '" height="1" alt="" /></td>';
				if ($txtMarg && $align == 'left') {
					// If left aligned, the textborder is added on the left side
					$tablecode .= '<td rowspan="' . ($rowspan + 1) . '" valign="top"><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . $txtMarg . '" height="1" alt="" title="" />' . ($editIconsHTML ? '<br />' . $editIconsHTML : '') . '</td>';
					$editIconsHTML = '';
					$flag = 1;
				}
				if ($flag) {
					$tableWidth += $txtMarg + 1;
				}
				$tablecode .= '</tr>';
			}
			// draw table
			// Looping through rows. If 'noRows' is set, this is '1 time', but $rowCount_temp will hold the actual number of rows!
			for ($c = 0; $c < $rowCount; $c++) {
				// If this is NOT the first time in the loop AND if space is required, a row-spacer is added. In case of "noRows" rowspacing is done further down.
				if ($c && $rowspacing) {
					$tablecode .= '<tr><td colspan="' . $colspan . '"><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="1" height="' . $rowspacing . '"' . $this->cObj->getBorderAttr(' border="0"') . ' alt="" title="" /></td></tr>';
				}
				// starting row
				$tablecode .= '<tr>';
				// Looping through the columns
				for ($b = 0; $b < $colCount_temp; $b++) {
					// If this is NOT the first iteration AND if column space is required. In case of "noCols", the space is done without a separate cell.
					if ($b && $colspacing) {
						if (!$noCols) {
							$tablecode .= '<td><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . $colspacing . '" height="1"' . $this->cObj->getBorderAttr(' border="0"') . ' alt="" title="" /></td>';
						} else {
							$colSpacer = '<img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . ($border ? $colspacing - 6 : $colspacing) . '" height="' . ($imageRowsMaxHeights[$c] + ($border ? $borderThickness * 2 : 0)) . '"' . $this->cObj->getBorderAttr(' border="0"') . ' align="' . ($border ? 'left' : 'top') . '" alt="" title="" />';
							$colSpacer = '<td valign="top">' . $colSpacer . '</td>';
							// added 160301, needed for the new "noCols"-table...
							$tablecode .= $colSpacer;
						}
					}
					if (!$noCols || $noCols && !$b) {
						// starting the cell. If "noCols" this cell will hold all images in the row, otherwise only a single image.
						$tablecode .= '<td valign="top">';
						if ($noCols) {
							$tablecode .= '<table width="' . $imageRowsFinalWidths[$c] . '" border="0" cellpadding="0" cellspacing="0"><tr>';
						}
					}
					// Looping through the rows IF "noRows" is set. "noRows"  means that the rows of images is not rendered
					// by physical table rows but images are all in one column and spaced apart with clear-gifs. This loop is
					// only one time if "noRows" is not set.
					for ($a = 0; $a < $rowCount_temp; $a++) {
						// register previous imgIndex
						$GLOBALS['TSFE']->register['IMAGE_NUM'] = $imgIndex;
						$imgIndex = $index + $a * $colCount_temp;
						$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $imgIndex;
						if ($imgsTag[$imgIndex]) {
							// Puts distance between the images IF "noRows" is set and this is the first iteration of the loop
							if ($rowspacing && $noRows && $a) {
								$tablecode .= '<img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="1" height="' . $rowspacing . '" alt="" title="" /><br />';
							}
							if ($legacyCaptionSplit) {
								$thisCaption = $captionArray[$imgIndex];
							} elseif (($conf['captionSplit'] || $conf['imageTextSplit']) && isset($conf['caption.'])) {
								$thisCaption = $this->cObj->cObjGet($conf['caption.'], 'caption.');
								$thisCaption = $this->cObj->stdWrap($thisCaption, $conf['caption.']);
							}
							$imageHTML = $imgsTag[$imgIndex] . '<br />';
							// this is necessary if the tablerows are supposed to space properly together! "noRows" is excluded because else the images "layer" together.
							$Talign = !trim($thisCaption) && !$noRows ? ' align="left"' : '';
							if ($border) {
								$imageHTML = '<table border="0" cellpadding="' . $borderThickness . '" cellspacing="0" bgcolor="' . $borderColor . '"' . $Talign . '><tr><td>' . $imageHTML . '</td></tr></table>';
							}
							$imageHTML .= $editIconsHTML;
							$editIconsHTML = '';
							// Adds caption.
							$imageHTML .= $thisCaption;
							if ($noCols) {
								$imageHTML = '<td valign="top">' . $imageHTML . '</td>';
							}
							// If noCols, put in table cell.
							$tablecode .= $imageHTML;
						}
					}
					$index++;
					if (!$noCols || $noCols && $b + 1 == $colCount_temp) {
						if ($noCols) {
							$tablecode .= '</tr></table>';
						}
						// In case of "noCols" we must finish the table that surrounds the images in the row.
						$tablecode .= '</td>';
					}
				}
				// ending row
				$tablecode .= '</tr>';
			}
			if ($c) {
				switch ($contentPosition) {
				case '0':

				case '8':
					// below
					switch ($align) {
					case 'center':
						$table_align = 'margin-left: auto; margin-right: auto';
						break;
					case 'right':
						$table_align = 'margin-left: auto; margin-right: 0px';
						break;
					default:
						// Most of all: left
						$table_align = 'margin-left: 0px; margin-right: auto';
					}
					$table_align = 'style="' . $table_align . '"';
					break;
				case '16':
					// in text
					$table_align = 'align="' . $align . '"';
					break;
				default:
					$table_align = '';
				}
				// Table-tag is inserted
				$tablecode = '<table' . ($tableWidth ? ' width="' . $tableWidth . '"' : '') . ' border="0" cellspacing="0" cellpadding="0" ' . $table_align . ' class="imgtext-table">' . $tablecode;
				// If this value is not long since reset.
				if ($editIconsHTML) {
					$tablecode .= '<tr><td colspan="' . $colspan . '">' . $editIconsHTML . '</td></tr>';
					$editIconsHTML = '';
				}
				if ($cap) {
					$tablecode .= '<tr><td colspan="' . $colspan . '" align="' . $caption_align . '">' . $caption . '</td></tr>';
				}
				$tablecode .= '</table>';
				if (isset($conf['tableStdWrap.'])) {
					$tablecode = $this->cObj->stdWrap($tablecode, $conf['tableStdWrap.']);
				}
			}
			$spaceBelowAbove = isset($conf['spaceBelowAbove.']) ? intval($this->cObj->stdWrap($conf['spaceBelowAbove'], $conf['spaceBelowAbove.'])) : intval($conf['spaceBelowAbove']);
			switch ($contentPosition) {
			case '0':
				// above
				$output = '<div style="text-align:' . $align . ';">' . $tablecode . '</div>' . $this->cObj->wrapSpace($content, ($spaceBelowAbove . '|0'));
				break;
			case '8':
				// below
				$output = $this->cObj->wrapSpace($content, ('0|' . $spaceBelowAbove)) . '<div style="text-align:' . $align . ';">' . $tablecode . '</div>';
				break;
			case '16':
				// in text
				$output = $tablecode . $content;
				break;
			case '24':
				// in text, no wrap
				$theResult = '';
				$theResult .= '<table border="0" cellspacing="0" cellpadding="0" class="imgtext-nowrap"><tr>';
				if ($align == 'right') {
					$theResult .= '<td valign="top">' . $content . '</td><td valign="top">' . $tablecode . '</td>';
				} else {
					$theResult .= '<td valign="top">' . $tablecode . '</td><td valign="top">' . $content . '</td>';
				}
				$theResult .= '</tr></table>';
				$output = $theResult;
				break;
			}
		} else {
			$output = $content;
		}
		if (isset($conf['stdWrap.'])) {
			$output = $this->cObj->stdWrap($output, $conf['stdWrap.']);
		}
		return $output;
	}

}


?>