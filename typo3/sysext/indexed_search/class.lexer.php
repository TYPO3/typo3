<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 *   91: class tx_indexedsearch_lexer
 *  105:     function tx_indexedsearch_lexer()
 *  117:     function split2Words($wordString)
 *
 *              SECTION: Helper functions
 *  176:     function utf8_ord(&$str, &$len, $pos=0, $hex=false)
 *  201:     function utf8_is_letter(&$str, &$len, $pos=0, $scan=false)
 *  284:     function get_word($charset, &$str, $pos=0)
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



/*

DESCRIPTION OF (CJK) ALGORITHM

  Continuous letters and numbers make up words.  Spaces and symbols
  separate letters and numbers into words.  This is sufficient for
  all western text.

  CJK doesn't use spaces or separators to separate words, so the only
  way to really find out what constitutes a word would be to have a
  dictionary and advanced heuristics.  Instead, we form pairs from
  consecutive characters, in such a way that searches will find only
  characters that appear more-or-less the right sequence.  For example:

    ABCDE => AB BC CD DE

  This works okay since both the index and the search query is split
  in the same manner, and since the set of characters is huge so the
  extra matches are not significant.

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

	var $debug = FALSE;
	var $debugString = '';

	var $csObj;		// Charset class object , t3lib_cs



	/**
	 * Constructor: Initializes the charset class, t3lib_cs
	 *
	 * @return	void
	 */
	function tx_indexedsearch_lexer() {
		$this->csObj = &t3lib_div::makeInstance('t3lib_cs');
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
		$wordString = $this->csObj->conv_case('utf-8', $wordString, 'toLower');

			// Now, splitting words:
		$len = 0;
		$start = 0;
		$pos = 0;
		$words = array();
		$this->debugString = '';

		while(1)	{
			list($start,$len) = $this->get_word('utf-8', $wordString, $pos);
			if ($len)	{
				$words[] = substr($wordString,$start,$len);

				if ($this->debug)	{
					$this->debugString.= '<span style="color:red">'.htmlspecialchars(substr($wordString,$pos,$start-$pos)).'</span>'.htmlspecialchars(substr($wordString,$start,$len));
				}

				$pos = $start+$len;
			} else break;
		}

		return $words;
	}














	/************************************
	 *
	 * Helper functions
	 *
	 ************************************/

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

	/**
	 * See if a character is a letter (or a string of letters or non-letters).
	 *
	 * @param	string		Input string (reference)
	 * @param	integer		Byte-length of character sequence (reference, return value)
	 * @param	integer		Starting position in input string
	 * @param	boolean		If set will scan for a whole sequence of characters
	 * @return	boolean		letter (or word) found
	 */
	function utf8_is_letter(&$str, &$len, $pos=0, $scan=false)	{
		global $cs;

		$len = 0;
		$bc = 0;
		$found = false; // found a letter
		$letter = true; // looking for a letter?

		if ($str{$pos} == '')	return false;

		while(1) {
			if ($len)	{
				if ($scan)	{
					if ($letter && !$found)	{	// end of word reached
						return true;
					}
					elseif (!$letter && $found)	{	// end of non-word reached
						return false;
					}
				}
				else	{
					return $found;	// report single letter status
				}
			}
			$len += $bc;	// add byte-length of last found character
			$found = false;

			if ($str{$pos} == '')	return $letter;	// end of string

			$cp = $this->utf8_ord($str,$bc,$pos);
			$pos += $bc;

			if ($cp >= 0x41 && $cp <= 0x5A ||	// Basic Latin: capital letters
			    $cp >= 0x30 && $cp <= 0x39 ||	// Numbers
				$cp >= 0x61 && $cp <= 0x7A)	{	//		small letters
				$found = true;
				continue;
			}

			if ($cp >= 0xC0 && $cp <= 0xFF)	{	// Latin-1 Supplement (0x80-0xFF)
				// 0x80-0x9F are unassigned
				// 0xA0-0xBF are non-letters

				if ($cp != 0xD7 && $cp != 0xF7)	{	// multiplication and division sign
					$found = true;
					continue;
				}
			} elseif ($cp >= 0x100 && $cp < 0x280)	{	// Latin Extended-A and -B
				$found = true;
				continue;
			} elseif ($cp >= 0x370 && $cp < 0x400)	{	// Greek and Coptic
				$found = true;
				continue;
			} elseif ($cp >= 0x400 && $cp < 0x530)	{	// Cyrillic and Cyrillic Supplement
				$found = true;
				continue;
			} elseif ($cp >= 0x590 && $cp < 0x600)	{	// Hebrew
				$found = true;
				continue;
			} elseif ($cp >= 0x600 && $cp < 0x700)	{	// Arabic
				$found = true;
				continue;
			}
				// I dont't think we need to support these:
				//  Latin Extended Additional
				//  Greek Extended
				//  Alphabetic Presentation Forms
				//  Arabic Presentation Forms-A
				//  Arabic Presentation Forms-B

			if (!$len)	$letter = false;
		}

		return false;
	}

	/**
	 * Get the first word in a given string (initial non-letters will be skipped)
	 *
	 * @param	string		The charset
	 * @param	string		Input string (reference)
	 * @param	integer		Starting position in input string
	 * @return	array		0: start, 1: len or false if no word has been found
	 */
	function get_word($charset, &$str, $pos=0)	{
		if ($charset == 'utf-8')	{
			$letters = $this->utf8_is_letter($str, $len, $pos, true);
			if ($letters)	return array($pos,$len);	// word found

			$pos += $len;
			if ($str{$pos} == '')	return false;	// end of string

			$this->utf8_is_letter($str, $len, $pos, true);
			return array($pos,$len);
		}

		return false;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.lexer.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.lexer.php']);
}
?>