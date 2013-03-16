<?php
namespace TYPO3\CMS\Core\Utility;

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
/**
 * Contains class which has functions that generates a difference output of a content string
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML Compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * This class has functions which generates a difference output of a content string
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class DiffUtility {

	// External, static
	// If set, the HTML tags are stripped from the input strings first.
	/**
	 * @todo Define visibility
	 */
	public $stripTags = 0;

	// Diff options. eg "--unified=3"
	/**
	 * @todo Define visibility
	 */
	public $diffOptions = '';

	// Internal, dynamic
	// This indicates the number of times the function addClearBuffer has been called - and used to detect the very first call...
	/**
	 * @todo Define visibility
	 */
	public $clearBufferIdx = 0;

	/**
	 * @todo Define visibility
	 */
	public $differenceLgd = 0;

	/**
	 * This will produce a color-marked-up diff output in HTML from the input strings.
	 *
	 * @param string $str1 String 1
	 * @param string $str2 String 2
	 * @param string $wrapTag Setting the wrapping tag name
	 * @return string Formatted output.
	 * @todo Define visibility
	 */
	public function makeDiffDisplay($str1, $str2, $wrapTag = 'span') {
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
					$differenceStr .= ($diffResArray[$c]['old'][] = substr($lValue, 2));
				}
				if (substr($lValue, 0, 1) == '>') {
					$differenceStr .= ($diffResArray[$c]['new'][] = substr($lValue, 2));
				}
			}
			$this->differenceLgd = strlen($differenceStr);
			$outString = '';
			$clearBuffer = '';
			$str1LinesCount = count($str1Lines);
			for ($a = -1; $a < $str1LinesCount; $a++) {
				if (is_array($diffResArray[$a + 1])) {
					// a=Add, c=change, d=delete: If a, then the content is Added after the entry and we must insert the line content as well.
					if (strstr($diffResArray[$a + 1]['changeInfo'], 'a')) {
						$clearBuffer .= htmlspecialchars($str1Lines[$a]) . ' ';
					}
					$outString .= $this->addClearBuffer($clearBuffer);
					$clearBuffer = '';
					if (is_array($diffResArray[$a + 1]['old'])) {
						$outString .= '<' . $wrapTag . ' class="diff-r">' . htmlspecialchars(implode(' ', $diffResArray[($a + 1)]['old'])) . '</' . $wrapTag . '> ';
					}
					if (is_array($diffResArray[$a + 1]['new'])) {
						$outString .= '<' . $wrapTag . ' class="diff-g">' . htmlspecialchars(implode(' ', $diffResArray[($a + 1)]['new'])) . '</' . $wrapTag . '> ';
					}
					$chInfParts = explode(',', $diffResArray[$a + 1]['changeInfo']);
					if (!strcmp($chInfParts[0], ($a + 1))) {
						$newLine = intval($chInfParts[1]) - 1;
						if ($newLine > $a) {
							$a = $newLine;
						}
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
	 * @param string $str1 String 1
	 * @param string $str2 String 2
	 * @return array The result from the exec() function call.
	 * @access private
	 * @todo Define visibility
	 */
	public function getDiff($str1, $str2) {
		// Create file 1 and write string
		$file1 = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('diff1_');
		\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($file1, $str1);
		// Create file 2 and write string
		$file2 = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('diff2_');
		\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($file2, $str2);
		// Perform diff.
		$cmd = $GLOBALS['TYPO3_CONF_VARS']['BE']['diff_path'] . ' ' . $this->diffOptions . ' ' . $file1 . ' ' . $file2;
		$res = array();
		\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd, $res);
		unlink($file1);
		unlink($file2);
		return $res;
	}

	/**
	 * Will bring down the length of strings to < 150 chars if they were longer than 200 chars. This done by preserving the 70 first and last chars and concatenate those strings with "..." and a number indicating the string length
	 *
	 * @param string $clearBuffer The input string.
	 * @param boolean $last If set, it indicates that the string should just end with ... (thus no "complete" ending)
	 * @return string Processed string.
	 * @access private
	 * @todo Define visibility
	 */
	public function addClearBuffer($clearBuffer, $last = 0) {
		if (strlen($clearBuffer) > 200) {
			$clearBuffer = ($this->clearBufferIdx ? \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($clearBuffer, 70) : '') . '[' . strlen($clearBuffer) . ']' . (!$last ? \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($clearBuffer, -70) : '');
		}
		$this->clearBufferIdx++;
		return $clearBuffer;
	}

	/**
	 * Explodes the input string into words.
	 * This is done by splitting first by lines, then by space char. Each word will be in stored as a value in an array. Lines will be indicated by two subsequent empty values.
	 *
	 * @param string $str The string input
	 * @return array Array with words.
	 * @access private
	 * @todo Define visibility
	 */
	public function explodeStringIntoWords($str) {
		$strArr = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(LF, $str);
		$outArray = array();
		foreach ($strArr as $lineOfWords) {
			$allWords = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $lineOfWords, 1);
			$outArray = array_merge($outArray, $allWords);
			$outArray[] = '';
			$outArray[] = '';
		}
		return $outArray;
	}

	/**
	 * Adds a space character before and after HTML tags (more precisely any found < or >)
	 *
	 * @param string $str String to process
	 * @param boolean $rev If set, the < > searched for will be &lt; and &gt;
	 * @return string Processed string
	 * @access private
	 * @todo Define visibility
	 */
	public function tagSpace($str, $rev = 0) {
		if ($rev) {
			return str_replace(' &lt;', '&lt;', str_replace('&gt; ', '&gt;', $str));
		} else {
			return str_replace('<', ' <', str_replace('>', '> ', $str));
		}
	}

}


?>