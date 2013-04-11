<?php
namespace TYPO3\CMS\IndexedSearch;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2001-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Lexer for indexed_search
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Lexer class for indexed_search
 * A lexer splits the text into words
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class Lexer {

	// Debugging options:
	/**
	 * @todo Define visibility
	 */
	public $debug = FALSE;

	// If set, the debugString is filled with HTML output highlighting search / non-search words (for backend display)
	/**
	 * @todo Define visibility
	 */
	public $debugString = '';

	/**
	 * Charset class object
	 *
	 * @var \TYPO3\CMS\Core\Charset\CharsetConverter
	 * @todo Define visibility
	 */
	public $csObj;

	// Configuration of the lexer:
	/**
	 * @todo Define visibility
	 */
	public $lexerConf = array(
		'printjoins' => array(46, 45, 95, 58, 47, 39),
		'casesensitive' => FALSE,
		// Set, if case sensitive indexing is wanted.
		'removeChars' => array(45)
	);

	/**
	 * Constructor: Initializes the charset class
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function __construct() {
		$this->csObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
	}

	/**
	 * Splitting string into words.
	 * Used for indexing, can also be used to find words in query.
	 *
	 * @param 	string		String with UTF-8 content to process.
	 * @return 	array		Array of words in utf-8
	 * @todo Define visibility
	 */
	public function split2Words($wordString) {
		// Reset debug string:
		$this->debugString = '';
		// Then convert the string to lowercase:
		if (!$this->lexerConf['casesensitive']) {
			$wordString = $this->csObj->conv_case('utf-8', $wordString, 'toLower');
		}
		// Now, splitting words:
		$len = 0;
		$start = 0;
		$pos = 0;
		$words = array();
		$this->debugString = '';
		while (1) {
			list($start, $len) = $this->get_word($wordString, $pos);
			if ($len) {
				$this->addWords($words, $wordString, $start, $len);
				if ($this->debug) {
					$this->debugString .= '<span style="color:red">' . htmlspecialchars(substr($wordString, $pos, ($start - $pos))) . '</span>' . htmlspecialchars(substr($wordString, $start, $len));
				}
				$pos = $start + $len;
			} else {
				break;
			}
		}
		return $words;
	}

	/**********************************
	 *
	 * Helper functions
	 *
	 ********************************/
	/**
	 * Add word to word-array
	 * This function should be used to make sure CJK sequences are split up in the right way
	 *
	 * @param 	array		Array of accumulated words
	 * @param 	string		Complete Input string from where to extract word
	 * @param 	integer		Start position of word in input string
	 * @param 	integer		The Length of the word string from start position
	 * @return 	void
	 * @todo Define visibility
	 */
	public function addWords(&$words, &$wordString, $start, $len) {
		// Get word out of string:
		$theWord = substr($wordString, $start, $len);
		// Get next chars unicode number and find type:
		$bc = 0;
		$cp = $this->utf8_ord($theWord, $bc);
		list($cType) = $this->charType($cp);
		// If string is a CJK sequence we follow this algorithm:
		/*
		DESCRIPTION OF (CJK) ALGORITHMContinuous letters and numbers make up words. Spaces and symbols
		separate letters and numbers into words. This is sufficient for
		all western text.CJK doesn't use spaces or separators to separate words, so the only
		way to really find out what constitutes a word would be to have a
		dictionary and advanced heuristics. Instead, we form pairs from
		consecutive characters, in such a way that searches will find only
		characters that appear more-or-less the right sequence. For example:ABCDE => AB BC CD DEThis works okay since both the index and the search query is split
		in the same manner, and since the set of characters is huge so the
		extra matches are not significant.(Hint taken from ZOPEs chinese user group)[Kasper: As far as I can see this will only work well with or-searches!]
		 */
		if ($cType == 'cjk') {
			// Find total string length:
			$strlen = $this->csObj->utf8_strlen($theWord);
			// Traverse string length and add words as pairs of two chars:
			for ($a = 0; $a < $strlen; $a++) {
				if ($strlen == 1 || $a < $strlen - 1) {
					$words[] = $this->csObj->utf8_substr($theWord, $a, 2);
				}
			}
		} else {
			// Normal "single-byte" chars:
			// Remove chars:
			foreach ($this->lexerConf['removeChars'] as $skipJoin) {
				$theWord = str_replace($this->csObj->UnumberToChar($skipJoin), '', $theWord);
			}
			// Add word:
			$words[] = $theWord;
		}
	}

	/**
	 * Get the first word in a given utf-8 string (initial non-letters will be skipped)
	 *
	 * @param 	string		Input string (reference)
	 * @param 	integer		Starting position in input string
	 * @return 	array		0: start, 1: len or FALSE if no word has been found
	 * @todo Define visibility
	 */
	public function get_word(&$str, $pos = 0) {
		$len = 0;
		// If return is TRUE, a word was found starting at this position, so returning position and length:
		if ($this->utf8_is_letter($str, $len, $pos)) {
			return array($pos, $len);
		}
		// If the return value was FALSE it means a sequence of non-word chars were found (or blank string) - so we will start another search for the word:
		$pos += $len;
		if ($str[$pos] == '') {
			// Check end of string before looking for word of course.
			return FALSE;
		}
		$this->utf8_is_letter($str, $len, $pos);
		return array($pos, $len);
	}

	/**
	 * See if a character is a letter (or a string of letters or non-letters).
	 *
	 * @param 	string		Input string (reference)
	 * @param 	integer		Byte-length of character sequence (reference, return value)
	 * @param 	integer		Starting position in input string
	 * @return 	boolean		letter (or word) found
	 * @todo Define visibility
	 */
	public function utf8_is_letter(&$str, &$len, $pos = 0) {
		global $cs;
		$len = 0;
		$bc = 0;
		$cType = ($cType_prev = FALSE);
		// Letter type
		$letter = TRUE;
		// looking for a letter?
		if ($str[$pos] == '') {
			// Return FALSE on end-of-string at this stage
			return FALSE;
		}
		while (1) {
			// If characters has been obtained we will know whether the string starts as a sequence of letters or not:
			if ($len) {
				if ($letter) {
					// We are in a sequence of words
					if (!$cType || $cType_prev == 'cjk' && \TYPO3\CMS\Core\Utility\GeneralUtility::inList('num,alpha', $cType) || $cType == 'cjk' && \TYPO3\CMS\Core\Utility\GeneralUtility::inList('num,alpha', $cType_prev)) {
						// Check if the non-letter char is NOT a print-join char because then it signifies the end of the word.
						if (!in_array($cp, $this->lexerConf['printjoins'])) {
							// If a printjoin start length has been record, set that back now so the length is right (filtering out multiple end chars)
							if ($printJoinLgd) {
								$len = $printJoinLgd;
							}
							return TRUE;
						} else {
							// If a printJoin char is found, record the length if it has not been recorded already:
							if (!$printJoinLgd) {
								$printJoinLgd = $len;
							}
						}
					} else {
						// When a true letter is found, reset printJoinLgd counter:
						$printJoinLgd = 0;
					}
				} elseif (!$letter && $cType) {
					// end of non-word reached
					return FALSE;
				}
			}
			$len += $bc;
			// add byte-length of last found character
			if ($str[$pos] == '') {
				// End of string; return status of string till now
				return $letter;
			}
			// Get next chars unicode number:
			$cp = $this->utf8_ord($str, $bc, $pos);
			$pos += $bc;
			// Determine the type:
			$cType_prev = $cType;
			list($cType) = $this->charType($cp);
			if ($cType) {
				continue;
			}
			// Setting letter to FALSE if the first char was not a letter!
			if (!$len) {
				$letter = FALSE;
			}
		}
		return FALSE;
	}

	/**
	 * Determine the type of character
	 *
	 * @param 	integer		Unicode number to evaluate
	 * @return 	array		Type of char; index-0: the main type: num, alpha or CJK (Chinese / Japanese / Korean)
	 * @todo Define visibility
	 */
	public function charType($cp) {
		// Numeric?
		if ($cp >= 48 && $cp <= 57) {
			return array('num');
		}
		// LOOKING for Alpha chars (Latin, Cyrillic, Greek, Hebrew and Arabic):
		if ($cp >= 65 && $cp <= 90 || $cp >= 97 && $cp <= 122 || $cp >= 192 && $cp <= 255 && $cp != 215 && $cp != 247 || $cp >= 256 && $cp < 640 || ($cp == 902 || $cp >= 904 && $cp < 1024) || ($cp >= 1024 && $cp < 1154 || $cp >= 1162 && $cp < 1328) || ($cp >= 1424 && $cp < 1456 || $cp >= 1488 && $cp < 1523) || ($cp >= 1569 && $cp <= 1624 || $cp >= 1646 && $cp <= 1747) || $cp >= 7680 && $cp < 8192) {
			return array('alpha');
		}
		// Looking for CJK (Chinese / Japanese / Korean)
		// Ranges are not certain - deducted from the translation tables in t3lib/csconvtbl/
		// Verified with http://www.unicode.org/charts/ (16/2) - may still not be complete.
		if ($cp >= 12352 && $cp <= 12543 || $cp >= 12592 && $cp <= 12687 || $cp >= 13312 && $cp <= 19903 || $cp >= 19968 && $cp <= 40879 || $cp >= 44032 && $cp <= 55215 || $cp >= 131072 && $cp <= 195103) {
			return array('cjk');
		}
	}

	/**
	 * Converts a UTF-8 multibyte character to a UNICODE codepoint
	 *
	 * @param 	string		UTF-8 multibyte character string (reference)
	 * @param 	integer		The length of the character (reference, return value)
	 * @param 	integer		Starting position in input string
	 * @param 	boolean		If set, then a hex. number is returned
	 * @return 	integer		UNICODE codepoint
	 * @todo Define visibility
	 */
	public function utf8_ord(&$str, &$len, $pos = 0, $hex = FALSE) {
		$ord = ord($str[$pos]);
		$len = 1;
		if ($ord > 128) {
			for ($bc = -1, $mbs = $ord; $mbs & 128; $mbs = $mbs << 1) {
				// calculate number of extra bytes
				$bc++;
			}
			$len += $bc;
			$ord = $ord & (1 << 6 - $bc) - 1;
			// mask utf-8 lead-in bytes
			// "bring in" data bytes
			for ($i = $pos + 1; $bc; $bc--, $i++) {
				$ord = $ord << 6 | ord($str[$i]) & 63;
			}
		}
		return $hex ? 'x' . dechex($ord) : $ord;
	}

}


?>