<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains a class for various syntax highlighting.
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   84: class t3lib_syntaxhl
 *
 *			  SECTION: Markup of Data Structure, <T3DataStructure>
 *  156:	 function highLight_DS($str)
 *  183:	 function highLight_DS_markUpRecursively($struct,$parent='',$app='')
 *
 *			  SECTION: Markup of Data Structure, <T3FlexForms>
 *  268:	 function highLight_FF($str)
 *  295:	 function highLight_FF_markUpRecursively($struct,$parent='',$app='')
 *
 *			  SECTION: Various
 *  376:	 function getAllTags($str)
 *  407:	 function splitXMLbyTags($tagList,$str)
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Syntax Highlighting class.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_syntaxhl {

		// Internal, dynamic:
	var $htmlParse; // Parse object.

		// External, static:
	var $DS_wrapTags = array(
		'T3DataStructure' => array('<span style="font-weight: bold;">', '</span>'),
		'type' => array('<span style="font-weight: bold; color: #000080;">', '</span>'),
		'section' => array('<span style="font-weight: bold; color: #000080;">', '</span>'),
		'el' => array('<span style="font-weight: bold; color: #800000;">', '</span>'),
		'meta' => array('<span style="font-weight: bold; color: #800080;">', '</span>'),
		'_unknown' => array('<span style="font-style: italic; color: #666666;">', '</span>'),

		'_applicationTag' => array('<span style="font-weight: bold; color: #FF6600;">', '</span>'),
		'_applicationContents' => array('<span style="font-style: italic; color: #C29336;">', '</span>'),

		'sheets' => array('<span style="font-weight: bold; color: #008000;">', '</span>'),
		'parent:sheets' => array('<span style="color: #008000;">', '</span>'),

		'ROOT' => array('<span style="font-weight: bold; color: #008080;">', '</span>'),
		'parent:el' => array('<span style="font-weight: bold; color: #008080;">', '</span>'),

		'langDisable' => array('<span style="color: #000080;">', '</span>'),
		'langChildren' => array('<span style="color: #000080;">', '</span>'),
	);

	var $FF_wrapTags = array(
		'T3FlexForms' => array('<span style="font-weight: bold;">', '</span>'),
		'meta' => array('<span style="font-weight: bold; color: #800080;">', '</span>'),
		'data' => array('<span style="font-weight: bold; color: #800080;">', '</span>'),
		'el' => array('<span style="font-weight: bold; color: #80a000;">', '</span>'),
		'itemType' => array('<span style="font-weight: bold; color: #804000;">', '</span>'),
		'section' => array('<span style="font-weight: bold; color: #604080;">', '</span>'),
		'numIndex' => array('<span style="color: #333333;">', '</span>'),
		'_unknown' => array('<span style="font-style: italic; color: #666666;">', '</span>'),


		'sDEF' => array('<span style="font-weight: bold; color: #008000;">', '</span>'),
		'level:sheet' => array('<span style="font-weight: bold; color: #008000;">', '</span>'),

		'lDEF' => array('<span style="font-weight: bold; color: #000080;">', '</span>'),
		'level:language' => array('<span style="font-weight: bold; color: #000080;">', '</span>'),

		'level:fieldname' => array('<span style="font-weight: bold; color: #666666;">', '</span>'),

		'vDEF' => array('<span style="font-weight: bold; color: #008080;">', '</span>'),
		'level:value' => array('<span style="font-weight: bold; color: #008080;">', '</span>'),

		'currentSheetId' => array('<span style="color: #000080;">', '</span>'),
		'currentLangId' => array('<span style="color: #000080;">', '</span>'),
	);


	/*************************************
	 *
	 * Markup of Data Structure, <T3DataStructure>
	 *
	 *************************************/

	/**
	 * Makes syntax highlighting of a Data Structure, <T3DataStructure>
	 *
	 * @param	string		Data Structure XML, must be valid since it's parsed.
	 * @return	string		HTML code with highlighted content. Must be wrapped in <PRE> tags
	 */
	function highLight_DS($str) {

			// Parse DS to verify that it is valid:
		$DS = t3lib_div::xml2array($str);
		if (is_array($DS)) {
			$completeTagList = array_unique($this->getAllTags($str)); // Complete list of tags in DS

				// Highlighting source:
			$this->htmlParse = t3lib_div::makeInstance('t3lib_parsehtml'); // Init parser object
			$struct = $this->splitXMLbyTags(implode(',', $completeTagList), $str); // Split the XML by the found tags, recursively into LARGE array.
			$markUp = $this->highLight_DS_markUpRecursively($struct); // Perform color-markup on the parsed content. Markup preserves the LINE formatting of the XML.

				// Return content:
			return $markUp;
		} else {
			$error = 'ERROR: The input content failed XML parsing: ' . $DS;
		}
		return $error;
	}

	/**
	 * Making syntax highlighting of the parsed Data Structure XML.
	 * Called recursively.
	 *
	 * @param	array		The structure, see splitXMLbyTags()
	 * @param	string		Parent tag.
	 * @param	string		"Application" - used to denote if we are 'inside' a section
	 * @return	string		HTML
	 */
	function highLight_DS_markUpRecursively($struct, $parent = '', $app = '') {
		$output = '';
		foreach ($struct as $k => $v) {
			if ($k % 2) {
				$nextApp = $app;
				$wrap = array('', '');

				switch ($app) {
					case 'TCEforms':
					case 'tx_templavoila':
						$wrap = $this->DS_wrapTags['_applicationContents'];
					break;
					case 'el':
					default:
						if ($parent == 'el') {
							$wrap = $this->DS_wrapTags['parent:el'];
							$nextApp = 'el';
						} elseif ($parent == 'sheets') {
							$wrap = $this->DS_wrapTags['parent:sheets'];
						} else {
							$wrap = $this->DS_wrapTags[$v['tagName']];
							$nextApp = '';
						}

							// If no wrap defined, us "unknown" definition
						if (!is_array($wrap)) {
							$wrap = $this->DS_wrapTags['_unknown'];
						}

							// Check for application sections in the XML:
						if ($app == 'el' || $parent == 'ROOT') {
							switch ($v['tagName']) {
								case 'TCEforms':
								case 'tx_templavoila':
									$nextApp = $v['tagName'];
									$wrap = $this->DS_wrapTags['_applicationTag'];
								break;
							}
						}
					break;
				}

				$output .= $wrap[0] . htmlspecialchars($v['tag']) . $wrap[1];
				$output .= $this->highLight_DS_markUpRecursively($v['sub'], $v['tagName'], $nextApp);
				$output .= $wrap[0] . htmlspecialchars('</' . $v['tagName'] . '>') . $wrap[1];
			} else {
				$output .= htmlspecialchars($v);
			}
		}

		return $output;
	}


	/*************************************
	 *
	 * Markup of Data Structure, <T3FlexForms>
	 *
	 *************************************/

	/**
	 * Makes syntax highlighting of a FlexForm Data, <T3FlexForms>
	 *
	 * @param	string		Data Structure XML, must be valid since it's parsed.
	 * @return	string		HTML code with highlighted content. Must be wrapped in <PRE> tags
	 */
	function highLight_FF($str) {

			// Parse DS to verify that it is valid:
		$DS = t3lib_div::xml2array($str);
		if (is_array($DS)) {
			$completeTagList = array_unique($this->getAllTags($str)); // Complete list of tags in DS

				// Highlighting source:
			$this->htmlParse = t3lib_div::makeInstance('t3lib_parsehtml'); // Init parser object
			$struct = $this->splitXMLbyTags(implode(',', $completeTagList), $str); // Split the XML by the found tags, recursively into LARGE array.
			$markUp = $this->highLight_FF_markUpRecursively($struct); // Perform color-markup on the parsed content. Markup preserves the LINE formatting of the XML.

				// Return content:
			return $markUp;
		} else {
			$error = 'ERROR: The input content failed XML parsing: ' . $DS;
		}
		return $error;
	}

	/**
	 * Making syntax highlighting of the parsed FlexForm XML.
	 * Called recursively.
	 *
	 * @param	array		The structure, see splitXMLbyTags()
	 * @param	string		Parent tag.
	 * @param	string		"Application" - used to denote if we are 'inside' a section
	 * @return	string		HTML
	 */
	function highLight_FF_markUpRecursively($struct, $parent = '', $app = '') {
		$output = '';

			// Setting levels:
		if ($parent == 'data') {
			$app = 'sheet';
		} elseif ($app == 'sheet') {
			$app = 'language';
		} elseif ($app == 'language') {
			$app = 'fieldname';
		} elseif ($app == 'fieldname') {
			$app = 'value';
		} elseif ($app == 'el' || $app == 'numIndex') {
			$app = 'fieldname';
		}

			// Traverse structure:
		foreach ($struct as $k => $v) {
			if ($k % 2) {
				$wrap = array('', '');

				if ($v['tagName'] == 'numIndex') {
					$app = 'numIndex';
				}

					// Default wrap:
				$wrap = $this->FF_wrapTags[$v['tagName']];

					// If no wrap defined, us "unknown" definition
				if (!is_array($wrap)) {
					switch ($app) {
						case 'sheet':
						case 'language':
						case 'fieldname':
						case 'value':
							$wrap = $this->FF_wrapTags['level:' . $app];
						break;
						default:
							$wrap = $this->FF_wrapTags['_unknown'];
						break;
					}
				}

				if ($v['tagName'] == 'el') {
					$app = 'el';
				}

				$output .= $wrap[0] . htmlspecialchars($v['tag']) . $wrap[1];
				$output .= $this->highLight_FF_markUpRecursively($v['sub'], $v['tagName'], $app);
				$output .= $wrap[0] . htmlspecialchars('</' . $v['tagName'] . '>') . $wrap[1];
			} else {
				$output .= htmlspecialchars($v);
			}
		}

		return $output;
	}


	/*************************************
	 *
	 * Various
	 *
	 *************************************/

	/**
	 * Returning all tag names found in XML/HTML input string
	 *
	 * @param	string		HTML/XML input
	 * @return	array		Array with all found tags (starttags only)
	 */
	function getAllTags($str) {

			// Init:
		$tags = array();
		$token = md5(microtime());

			// Markup all tag names with token.
		$markUpStr = preg_replace('/<([[:alnum:]_]+)[^>]*>/', $token . '${1}' . $token, $str);

			// Splitting by token:
		$parts = explode($token, $markUpStr);

			// Traversing parts:
		foreach ($parts as $k => $v) {
			if ($k % 2) {
				$tags[] = $v;
			}
		}

			// Returning tags:
		return $tags;
	}

	/**
	 * Splitting the input source by the tags listing in $tagList.
	 * Called recursively.
	 *
	 * @param	string		Commalist of tags to split source by (into blocks, ALL being block-tags!)
	 * @param	string		Input string.
	 * @return	array		Array with the content arranged hierarchically.
	 */
	function splitXMLbyTags($tagList, $str) {
		$struct = $this->htmlParse->splitIntoBlock($tagList, $str);

			// Traverse level:
		foreach ($struct as $k => $v) {
			if ($k % 2) {
				$tag = $this->htmlParse->getFirstTag($v);
				$tagName = $this->htmlParse->getFirstTagName($tag, TRUE);
				$struct[$k] = array(
					'tag' => $tag,
					'tagName' => $tagName,
					'sub' => $this->splitXMLbyTags($tagList, $this->htmlParse->removeFirstAndLastTag($struct[$k]))
				);
			}
		}

		return $struct;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_syntaxhl.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_syntaxhl.php']);
}

?>