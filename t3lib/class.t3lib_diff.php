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
 * Contains class which has functions that generates a difference output of a content string
 *
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML Compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   66: class t3lib_diff
 *   86:	 function makeDiffDisplay($str1,$str2,$wrapTag='span')
 *  163:	 function getDiff($str1,$str2)
 *  189:	 function addClearBuffer($clearBuffer,$last=0)
 *  205:	 function explodeStringIntoWords($str)
 *  226:	 function tagSpace($str,$rev=0)
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * This class has functions which generates a difference output of a content string
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_diff {

		// External, static:
	var $stripTags = 0; // If set, the HTML tags are stripped from the input strings first.
	var $diffOptions = ''; // Diff options. eg "--unified=3"

		// Internal, dynamic:
	var $clearBufferIdx = 0; // This indicates the number of times the function addClearBuffer has been called - and used to detect the very first call...
	var $differenceLgd = 0;


	/**
	 * This will produce a color-marked-up diff output in HTML from the input strings.
	 *
	 * @param	string		String 1
	 * @param	string		String 2
	 * @param	string		Setting the wrapping tag name
	 * @return	string		Formatted output.
	 */
	function makeDiffDisplay($str1, $str2, $wrapTag = 'span') {
		if ($this->stripTags) {
			$str1 = strip_tags($str1);
			$str2 = strip_tags($str2);
		} else {
			$str1 = $this->tagSpace($str1);
			$str2 = $this->tagSpace($str2);
		}
		$str1Lines = $this->explodeStringIntoWords($str1);
		$str2Lines = $this->explodeStringIntoWords($str2);

		$diffRes = $this->getDiff(implode(LF, $str1Lines) . LF, implode(LF, $str2Lines) . LF);

		if (is_array($diffRes)) {
			$c = 0;
			$diffResArray = array();
			$differenceStr = '';
			foreach ($diffRes as $lValue) {
				if (intval($lValue)) {
					$c = intval($lValue);
					$diffResArray[$c]['changeInfo'] = $lValue;
				}
				if (substr($lValue, 0, 1) == '<') {
					$differenceStr .= $diffResArray[$c]['old'][] = substr($lValue, 2);
				}
				if (substr($lValue, 0, 1) == '>') {
					$differenceStr .= $diffResArray[$c]['new'][] = substr($lValue, 2);
				}
			}

			$this->differenceLgd = strlen($differenceStr);

			$outString = '';
			$clearBuffer = '';
			for ($a = -1; $a < count($str1Lines); $a++) {
				if (is_array($diffResArray[$a + 1])) {
					if (strstr($diffResArray[$a + 1]['changeInfo'], 'a')) { // a=Add, c=change, d=delete: If a, then the content is Added after the entry and we must insert the line content as well.
						$clearBuffer .= htmlspecialchars($str1Lines[$a]) . ' ';
					}

					$outString .= $this->addClearBuffer($clearBuffer);
					$clearBuffer = '';
					if (is_array($diffResArray[$a + 1]['old'])) {
						$outString .= '<' . $wrapTag . ' class="diff-r">' . htmlspecialchars(implode(' ', $diffResArray[$a + 1]['old'])) . '</' . $wrapTag . '> ';
					}
					if (is_array($diffResArray[$a + 1]['new'])) {
						$outString .= '<' . $wrapTag . ' class="diff-g">' . htmlspecialchars(implode(' ', $diffResArray[$a + 1]['new'])) . '</' . $wrapTag . '> ';
					}
					$chInfParts = explode(',', $diffResArray[$a + 1]['changeInfo']);
					if (!strcmp($chInfParts[0], $a + 1)) {
						$newLine = intval($chInfParts[1]) - 1;
						if ($newLine > $a) {
							$a = $newLine;
						} // Security that $a is not set lower than current for some reason...
					}
				} else {
					$clearBuffer .= htmlspecialchars($str1Lines[$a]) . ' ';
				}
			}
			$outString .= $this->addClearBuffer($clearBuffer, 1);

			$outString = str_replace('  ', LF, $outString);
			if (!$this->stripTags) {
				$outString = $this->tagSpace($outString, 1);
			}
			return $outString;
		}
	}

	/**
	 * Produce a diff (using the "diff" application) between two strings
	 * The function will write the two input strings to temporary files, then execute the diff program, delete the temp files and return the result.
	 *
	 * @param	string		String 1
	 * @param	string		String 2
	 * @return	array		The result from the exec() function call.
	 * @access private
	 */
	function getDiff($str1, $str2) {
			// Create file 1 and write string
		$file1 = t3lib_div::tempnam('diff1_');
		t3lib_div::writeFile($file1, $str1);
			// Create file 2 and write string
		$file2 = t3lib_div::tempnam('diff2_');
		t3lib_div::writeFile($file2, $str2);
			// Perform diff.
		$cmd = $GLOBALS['TYPO3_CONF_VARS']['BE']['diff_path'] . ' ' . $this->diffOptions . ' ' . $file1 . ' ' . $file2;
		$res = array();
		t3lib_utility_Command::exec($cmd, $res);

		unlink($file1);
		unlink($file2);

		return $res;
	}

	/**
	 * Will bring down the length of strings to < 150 chars if they were longer than 200 chars. This done by preserving the 70 first and last chars and concatenate those strings with "..." and a number indicating the string length
	 *
	 * @param	string		The input string.
	 * @param	boolean		If set, it indicates that the string should just end with ... (thus no "complete" ending)
	 * @return	string		Processed string.
	 * @access private
	 */
	function addClearBuffer($clearBuffer, $last = 0) {
		if (strlen($clearBuffer) > 200) {
			$clearBuffer = ($this->clearBufferIdx ? t3lib_div::fixed_lgd_cs($clearBuffer, 70) : '') . '[' . strlen($clearBuffer) . ']' . (!$last ? t3lib_div::fixed_lgd_cs($clearBuffer, -70) : '');
		}
		$this->clearBufferIdx++;
		return $clearBuffer;
	}

	/**
	 * Explodes the input string into words.
	 * This is done by splitting first by lines, then by space char. Each word will be in stored as a value in an array. Lines will be indicated by two subsequent empty values.
	 *
	 * @param	string		The string input
	 * @return	array		Array with words.
	 * @access private
	 */
	function explodeStringIntoWords($str) {
		$strArr = t3lib_div::trimExplode(LF, $str);
		$outArray = array();
		foreach ($strArr as $lineOfWords) {
			$allWords = t3lib_div::trimExplode(' ', $lineOfWords, 1);
			$outArray = array_merge($outArray, $allWords);
			$outArray[] = '';
			$outArray[] = '';
		}
		return $outArray;
	}

	/**
	 * Adds a space character before and after HTML tags (more precisely any found < or >)
	 *
	 * @param	string		String to process
	 * @param	boolean		If set, the < > searched for will be &lt; and &gt;
	 * @return	string		Processed string
	 * @access private
	 */
	function tagSpace($str, $rev = 0) {
		if ($rev) {
			return str_replace(' &lt;', '&lt;', str_replace('&gt; ', '&gt;', $str));
		} else {
			return str_replace('<', ' <', str_replace('>', '> ', $str));
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_diff.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_diff.php']);
}
?>