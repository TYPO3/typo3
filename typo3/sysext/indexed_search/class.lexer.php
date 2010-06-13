<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * Parts provided by Martin Kutschker <Martin.T.Kutschker@blackbox.net>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   73: class tx_indexedsearch_lexer
 *  105:     function tx_indexedsearch_lexer()
 *  116:     function split2Words($wordString)
 *
 *              SECTION: Helper functions
 *  178:     function addWords(&$words, &$wordString, $start, $len)
 *  239:     function get_word(&$str, $pos=0)
 *  264:     function utf8_is_letter(&$str, &$len, $pos=0)
 *  329:     function charType($cp)
 *  383:     function utf8_ord(&$str, &$len, $pos=0, $hex=false)
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */












/**
 * Lexer class for indexed_search
 * A lexer splits the text into words
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_indexedsearch
 */
class tx_indexedsearch_lexer {

		// Debugging options:
	var $debug = FALSE;		// If set, the debugString is filled with HTML output highlighting search / non-search words (for backend display)
	var $debugString = '';

	/**
	 * Charset class object
	 *
	 * @var t3lib_cs
	 */
	var $csObj;


		// Configuration of the lexer:
	var $lexerConf = array(
		'printjoins' => array(	// This is the Unicode numbers of chars that are allowed INSIDE a sequence of letter chars (alphanum + CJK)
			0x2e,	// "."
			0x2d,	// "-"
			0x5f,	// "_"
			0x3a,	// ":"
			0x2f,	// "/"
			0x27,	// "'"
			// 0x615,	// ARABIC SMALL HIGH TAH
		),
		'casesensitive' => FALSE,	// Set, if case sensitive indexing is wanted.
		'removeChars' => array(		// List of unicode numbers of chars that will be removed before words are returned (eg. "-")
			0x2d	// "-"
		)
	);


	/**
	 * Constructor: Initializes the charset class, t3lib_cs
	 *
	 * @return	void
	 */
	function tx_indexedsearch_lexer() {
		$this->csObj = t3lib_div::makeInstance('t3lib_cs');
	}

	/**
	 * Splitting string into words.
	 * Used for indexing, can also be used to find words in query.
	 *
	 * @param	string		String with UTF-8 content to process.
	 * @return	array		Array of words in utf-8
	 */
	function split2Words($wordString)	{

			// Reset debug string:
		$this->debugString = '';

			// Then convert the string to lowercase:
		if (!$this->lexerConf['casesensitive'])	{
			$wordString = $this->csObj->conv_case('utf-8', $wordString, 'toLower');
		}

			// Now, splitting words:
		$len = 0;
		$start = 0;
		$pos = 0;
		$words = array();
		$this->debugString = '';

		while(1)	{
			list($start,$len) = $this->get_word($wordString, $pos);
			if ($len)	{

				$this->addWords($words, $wordString,$start,$len);

				if ($this->debug)	{
					$this->debugString.= '<span style="color:red">'.htmlspecialchars(substr($wordString,$pos,$start-$pos)).'</span>'.
										htmlspecialchars(substr($wordString,$start,$len));
				}

				$pos = $start+$len;
			} else break;
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
	 * @param	array		Array of accumulated words
	 * @param	string		Complete Input string from where to extract word
	 * @param	integer		Start position of word in input string
	 * @param	integer		The Length of the word string from start position
	 * @return	void
	 */
	function addWords(&$words, &$wordString, $start, $len)	{

			// Get word out of string:
		$theWord = substr($wordString,$start,$len);

			// Get next chars unicode number and find type:
		$bc = 0;
		$cp = $this->utf8_ord($theWord, $bc);
		list($cType) = $this->charType($cp);

			// If string is a CJK sequence we follow this algorithm:
			/*
				DESCRIPTION OF (CJK) ALGORITHM

				Continuous letters and numbers make up words. Spaces and symbols
				separate letters and numbers into words. This is sufficient for
				all western text.

				CJK doesn't use spaces or separators to separate words, so the only
				way to really find out what constitutes a word would be to have a
				dictionary and advanced heuristics. Instead, we form pairs from
				consecutive characters, in such a way that searches will find only
				characters that appear more-or-less the right sequence. For example:

					ABCDE => AB BC CD DE

				This works okay since both the index and the search query is split
				in the same manner, and since the set of characters is huge so the
				extra matches are not significant.

				(Hint taken from ZOPEs chinese user group)

				[Kasper: As far as I can see this will only work well with or-searches!]
			*/
		if ($cType == 'cjk')	{
				// Find total string length:
			$strlen = $this->csObj->utf8_strlen($theWord);

				// Traverse string length and add words as pairs of two chars:
			for ($a=0; $a<$strlen; $a++)	{
				if ($strlen==1 || $a<$strlen-1)	{
					$words[] = $this->csObj->utf8_substr($theWord, $a, 2);
				}
			}
		} else {	// Normal "single-byte" chars:
				// Remove chars:
			foreach($this->lexerConf['removeChars'] as $skipJoin)	{
				$theWord = str_replace($this->csObj->UnumberToChar($skipJoin),'',$theWord);
			}
				// Add word:
			$words[] = $theWord;
		}
	}

	/**
	 * Get the first word in a given utf-8 string (initial non-letters will be skipped)
	 *
	 * @param	string		Input string (reference)
	 * @param	integer		Starting position in input string
	 * @return	array		0: start, 1: len or false if no word has been found
	 */
	function get_word(&$str, $pos=0)	{

		$len=0;

			// If return is true, a word was found starting at this position, so returning position and length:
		if ($this->utf8_is_letter($str, $len, $pos))	{
			return array($pos,$len);
		}

			// If the return value was false it means a sequence of non-word chars were found (or blank string) - so we will start another search for the word:
		$pos += $len;
		if ($str{$pos} == '')	return false;	// check end of string before looking for word of course.

		$this->utf8_is_letter($str, $len, $pos);
		return array($pos,$len);
	}

	/**
	 * See if a character is a letter (or a string of letters or non-letters).
	 *
	 * @param	string		Input string (reference)
	 * @param	integer		Byte-length of character sequence (reference, return value)
	 * @param	integer		Starting position in input string
	 * @return	boolean		letter (or word) found
	 */
	function utf8_is_letter(&$str, &$len, $pos=0)	{
		global $cs;

		$len = 0;
		$bc = 0;
		$cType = $cType_prev = false; // Letter type
		$letter = true; // looking for a letter?

		if ($str{$pos} == '')	return false;	// Return false on end-of-string at this stage

		while(1) {

				// If characters has been obtained we will know whether the string starts as a sequence of letters or not:
			if ($len)	{
				if ($letter)	{	// We are in a sequence of words
					if (!$cType 	// The char was NOT a letter
							|| ($cType_prev=='cjk' && t3lib_div::inList('num,alpha',$cType)) || ($cType=='cjk' && t3lib_div::inList('num,alpha',$cType_prev))	// ... or the previous and current char are from single-byte sets vs. asian CJK sets
							)	{
							// Check if the non-letter char is NOT a print-join char because then it signifies the end of the word.
						if (!in_array($cp,$this->lexerConf['printjoins']))	{
								// If a printjoin start length has been record, set that back now so the length is right (filtering out multiple end chars)
							if ($printJoinLgd)	{
								$len = $printJoinLgd;
							}
							#debug($cp);
							return true;
						} else {	// If a printJoin char is found, record the length if it has not been recorded already:
							if (!$printJoinLgd)	$printJoinLgd = $len;
						}
					} else {	// When a true letter is found, reset printJoinLgd counter:
						$printJoinLgd = 0;
					}
				}
				elseif (!$letter && $cType)	{	// end of non-word reached
					return false;
				}
			}
			$len += $bc;	// add byte-length of last found character

			if ($str{$pos} == '')	return $letter;	// end of string; return status of string till now

				// Get next chars unicode number:
			$cp = $this->utf8_ord($str,$bc,$pos);
			$pos += $bc;

				// Determine the type:
			$cType_prev = $cType;
			list($cType) = $this->charType($cp);
			if ($cType)	{
				continue;
			}

				// Setting letter to false if the first char was not a letter!
			if (!$len)	$letter = false;
		}

		return false;
	}

	/**
	 * Determine the type of character
	 *
	 * @param	integer		Unicode number to evaluate
	 * @return	array		Type of char; index-0: the main type: num, alpha or CJK (Chinese / Japanese / Korean)
	 */
	function charType($cp)	{

			// Numeric?
		if (
				($cp >= 0x30 && $cp <= 0x39)		// Arabic
/*
				($cp >= 0x660 && $cp <= 0x669) ||	// Arabic-Indic
				($cp >= 0x6F0 && $cp <= 0x6F9) ||	// Arabic-Indic (Iran, Pakistan, and India)
				($cp >= 0x3021 && $cp <= 0x3029) ||	// Hangzhou
*/
			)	{
			return array('num');
		}

			// LOOKING for Alpha chars (Latin, Cyrillic, Greek, Hebrew and Arabic):
		if (
				($cp >= 0x41 && $cp <= 0x5A) ||		// Basic Latin: capital letters
				($cp >= 0x61 && $cp <= 0x7A) ||		// Basic Latin: small letters
				($cp >= 0xC0 && $cp <= 0xFF && $cp != 0xD7 && $cp != 0xF7) || 			// Latin-1 Supplement (0x80-0xFF) excluding multiplication and division sign
				($cp >= 0x100 && $cp < 0x280) ||	// Latin Extended-A and -B
				($cp == 0x386 || ($cp >= 0x388 && $cp < 0x400)) || // Greek and Coptic excluding non-letters
				(($cp >= 0x400 && $cp < 0x482) || ($cp >= 0x48A && $cp < 0x530)) ||		// Cyrillic and Cyrillic Supplement excluding historic miscellaneous
				(($cp >= 0x590 && $cp < 0x5B0) || ($cp >= 0x5D0 && $cp < 0x5F3)) || 	// Hebrew: only accents and letters
				(($cp >= 0x621 && $cp <= 0x658) || ($cp >= 0x66E &&  $cp <= 0x6D3)) || 	// Arabic: only letters (there are more letters in the range, we can add them if there is a demand)
				($cp >= 0x1E00 && $cp < 0x2000)		// Latin Extended Additional and Greek Extended
			)	{
			return array('alpha');
		}

			// Looking for CJK (Chinese / Japanese / Korean)
			// Ranges are not certain - deducted from the translation tables in t3lib/csconvtbl/
			// Verified with http://www.unicode.org/charts/ (16/2) - may still not be complete.
		if (
				($cp >= 0x3040 && $cp <= 0x30FF) ||		// HIRAGANA and KATAKANA letters
				($cp >= 0x3130 && $cp <= 0x318F) ||		// Hangul Compatibility Jamo
				($cp >= 0x3400 && $cp <= 0x4DBF) ||		// CJK Unified Ideographs Extension A
				($cp >= 0x4E00 && $cp <= 0x9FAF) ||		// CJK Unified Ideographs
				($cp >= 0xAC00 && $cp <= 0xD7AF) ||		// Hangul Syllables
				($cp >= 0x20000 && $cp <= 0x2FA1F)		// CJK Unified Ideographs Extension B and CJK Compatibility Ideographs Supplement
														// also include CJK and Kangxi radicals or Bopomofo letter?
			)	{
			return array('cjk');
		}
	}

	/**
	 * Converts a UTF-8 multibyte character to a UNICODE codepoint
	 *
	 * @param	string		UTF-8 multibyte character string (reference)
	 * @param	integer		The length of the character (reference, return value)
	 * @param	integer		Starting position in input string
	 * @param	boolean		If set, then a hex. number is returned
	 * @return	integer		UNICODE codepoint
	 */
	function utf8_ord(&$str, &$len, $pos=0, $hex=false)	{
		$ord = ord($str{$pos});
		$len = 1;

		if ($ord > 0x80)	{
			for ($bc=-1, $mbs=$ord; $mbs & 0x80; $mbs = $mbs << 1)	$bc++;	// calculate number of extra bytes
			$len += $bc;

			$ord = $ord & ((1 << (6-$bc)) - 1);	// mask utf-8 lead-in bytes
			for ($i=$pos+1; $bc; $bc--, $i++)	// "bring in" data bytes
				$ord = ($ord << 6) | (ord($str{$i}) & 0x3F);
		}

		return $hex ? 'x'.dechex($ord) : $ord;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.lexer.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.lexer.php']);
}
?>