<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains a class with functions used to read email content
 *
 * Revised for TYPO3 3.6 May 2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


/**
 * Functions used to read email content
 * The class is still just a bunch of miscellaneous functions used to read content out of emails
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_readmail {
	var $dateAbbrevs = array(
		'JAN' => 1,
		'FEB' => 2,
		'MAR' => 3,
		'APR' => 4,
		'MAY' => 5,
		'JUN' => 6,
		'JUL' => 7,
		'AUG' => 8,
		'SEP' => 9,
		'OCT' => 10,
		'NOV' => 11,
		'DEC' => 12
	);
	var $serverGMToffsetMinutes = 60; // = +0100 (CET)

	/*******************************
	 *
	 * General
	 *
	 ********************************/

	/**
	 * Returns the text content of a mail which has previously been parsed by eg. extractMailHeader()
	 * Probably obsolete since the function fullParse() is more advanced and safer to use.
	 *
	 * @param	array		Output from extractMailHeader()
	 * @return	string		The content.
	 */
	function getMessage($mailParts) {
		if ($mailParts['content-type']) {
			$CType = $this->getCType($mailParts['content-type']);
			if ($CType['boundary']) {
				$parts = $this->getMailBoundaryParts($CType['boundary'], $mailParts['CONTENT']);
				$c = $this->getTextContent($parts[0]);
			} else {
				$c = $this->getTextContent(
					'Content-Type: ' . $mailParts['content-type'] . '
					' . $mailParts['CONTENT']
				);
			}
		} else {
			$c = $mailParts['CONTENT'];
		}

		return $c;
	}

	/**
	 * Returns the body part of a raw mail message (including headers)
	 * Probably obsolete since the function fullParse() is more advanced and safer to use.
	 *
	 * @param	string		Raw mail content
	 * @return	string		Body of message
	 */
	function getTextContent($content) {
		$p = $this->extractMailHeader($content);

			// Here some decoding might be needed...
			// However we just return what is believed to be the proper notification:
		return $p['CONTENT'];
	}

	/**
	 * Splits the body of a mail into parts based on the boundary string given.
	 * Obsolete, use fullParse()
	 *
	 * @param	string		Boundary string used to split the content.
	 * @param	string		BODY section of a mail
	 * @return	array		Parts of the mail based on this
	 */
	function getMailBoundaryParts($boundary, $content) {
		$mParts = explode('--' . $boundary, $content);
		unset($mParts[0]);
		$new = array();
		foreach ($mParts as $val) {
			if (trim($val) == '--') {
				break;
			}
			$new[] = ltrim($val);
		}

		return $new;
	}

	/**
	 * Returns Content Type plus more.
	 * Obsolete, use fullParse()
	 *
	 * @param	string		"ContentType" string with more
	 * @return	array		parts in key/value pairs
	 * @ignore
	 */
	function getCType($str) {
		$parts = explode(';', $str);
		$cTypes = array();
		$cTypes['ContentType'] = $parts[0];
		next($parts);
		while (list(, $ppstr) = each($parts)) {
			$mparts = explode('=', $ppstr, 2);
			if (count($mparts) > 1) {
				$cTypes[strtolower(trim($mparts[0]))] = preg_replace('/^"/', '', trim(preg_replace('/"$/', '', trim($mparts[1]))));
			} else {
				$cTypes[] = $ppstr;
			}
		}

		return $cTypes;
	}

	/**
	 * Analyses the return-mail content for the Dmailer module - used to find what reason there was for rejecting the mail
	 * Used by the Dmailer, but not exclusively.
	 *
	 * @param	string		message body/text
	 * @return	array		key/value pairs with analysis result. Eg. "reason", "content", "reason_text", "mailserver" etc.
	 */
	function analyseReturnError($c) {
		$cp = array();
		if (strstr($c, '--- Below this line is a copy of the message.')) { // QMAIL
			list($c) = explode('--- Below this line is a copy of the message.', $c); // Splits by the QMAIL divider
			$cp['content'] = trim($c);
			$parts = explode('>:', $c, 2);
			$cp['reason_text'] = trim($parts[1]);
			$cp['mailserver'] = 'Qmail';
			if (preg_match('/550|no mailbox|account does not exist/i', $cp['reason_text'])) {
				$cp['reason'] = 550; // 550 Invalid recipient
			} elseif (stristr($cp['reason_text'], 'couldn\'t find any host named')) {
				$cp['reason'] = 2; // Bad host
			} elseif (preg_match('/Error in Header|invalid Message-ID header/i', $cp['reason_text'])) {
				$cp['reason'] = 554;
			} else {
				$cp['reason'] = -1;
			}
		} elseif (strstr($c, 'The Postfix program')) { // Postfix
			$cp['content'] = trim($c);
			$parts = explode('>:', $c, 2);
			$cp['reason_text'] = trim($parts[1]);
			$cp['mailserver'] = 'Postfix';
			if (stristr($cp['reason_text'], '550')) {
				$cp['reason'] = 550; // 550 Invalid recipient, User unknown
			} elseif (stristr($cp['reason_text'], '553')) {
				$cp['reason'] = 553; // No such user
			} elseif (stristr($cp['reason_text'], '551')) {
				$cp['reason'] = 551; // Mailbox full
			} else {
				$cp['reason'] = -1;
			}
		} else { // No-named:
			$cp['content'] = trim($c);
			$cp['reason_text'] = trim(substr($c, 0, 1000));
			$cp['mailserver'] = 'unknown';
			if (preg_match('/Unknown Recipient|Delivery failed 550|Receiver not found|User not listed|recipient problem|Delivery to the following recipients failed|User unknown|recipient name is not recognized/i', $cp['reason_text'])) {
				$cp['reason'] = 550; // 550 Invalid recipient, User unknown
			} elseif (preg_match('/over quota|mailbox full/i', $cp['reason_text'])) {
				$cp['reason'] = 551;
			} elseif (preg_match('/Error in Header/i', $cp['reason_text'])) {
				$cp['reason'] = 554;
			} else {
				$cp['reason'] = -1;
			}
		}

		return $cp;
	}

	/**
	 * Decodes a header-string with the =?....?= syntax including base64/quoted-printable encoding.
	 *
	 * @param	string		A string (encoded or not) from a mail header, like sender name etc.
	 * @return	string		The input string, but with the parts in =?....?= decoded.
	 */
	function decodeHeaderString($str) {
		$parts = explode('=?', $str, 2);
		if (count($parts) == 2) {
			list($charset, $encType, $encContent) = explode('?', $parts[1], 3);
			$subparts = explode('?=', $encContent, 2);
			$encContent = $subparts[0];

			switch (strtolower($encType)) {
				case 'q':
					$encContent = quoted_printable_decode($encContent);
					$encContent = str_replace('_', ' ', $encContent);
				break;
				case 'b':
					$encContent = base64_decode($encContent);
				break;
			}

			$parts[1] = $encContent . $this->decodeHeaderString($subparts[1]); // Calls decodeHeaderString recursively for any subsequent encoded section.
		}

		return implode('', $parts);
	}

	/**
	 * Extracts name/email parts from a header field (like 'To:' or 'From:' with name/email mixed up.
	 *
	 * @param	string		Value from a header field containing name/email values.
	 * @return	array		Array with the name and email in. Email is validated, otherwise not set.
	 */
	function extractNameEmail($str) {
		$outArr = array();

			// Email:
		$reg = '';
		preg_match('/<([^>]*)>/', $str, $reg);
		if (t3lib_div::validEmail($str)) {
			$outArr['email'] = $str;
		} elseif ($reg[1] && t3lib_div::validEmail($reg[1])) {
			$outArr['email'] = $reg[1];
				// Find name:
			list($namePart) = explode($reg[0], $str);
			if (trim($namePart)) {
				$reg = '';
				preg_match('/"([^"]*)"/', $str, $reg);
				if (trim($reg[1])) {
					$outArr['name'] = trim($reg[1]);
				} else {
					$outArr['name'] = trim($namePart);
				}
			}
		}

		return $outArr;
	}

	/**
	 * Returns the data from the 'content-type' field. That is the boundary, charset and mime-type
	 *
	 * @param	string		"Content-type-string"
	 * @return	array		key/value pairs with the result.
	 */
	function getContentTypeData($contentTypeStr) {
		$outValue = array();
		$cTypeParts = t3lib_div::trimExplode(';', $contentTypeStr, 1);
		$outValue['_MIME_TYPE'] = $cTypeParts[0]; // content type, first value is supposed to be the mime-type, whatever after the first is something else.

		reset($cTypeParts);
		next($cTypeParts);
		while (list(, $v) = Each($cTypeParts)) {
			$reg = '';
			preg_match('/([^=]*)="(.*)"/i', $v, $reg);
			if (trim($reg[1]) && trim($reg[2])) {
				$outValue[strtolower($reg[1])] = $reg[2];
			}
		}

		return $outValue;
	}

	/**
	 * Makes a UNIX-date based on the timestamp in the 'Date' header field.
	 *
	 * @param	string		String with a timestamp according to email standards.
	 * @return	integer		The timestamp converted to unix-time in seconds and compensated for GMT/CET ($this->serverGMToffsetMinutes);
	 */
	function makeUnixDate($dateStr) {
		$dateParts = explode(',', $dateStr);
		$dateStr = count($dateParts) > 1 ? $dateParts[1] : $dateParts[0];

		$spaceParts = t3lib_div::trimExplode(' ', $dateStr, 1);

		$spaceParts[1] = $this->dateAbbrevs[strtoupper($spaceParts[1])];
		$timeParts = explode(':', $spaceParts[3]);
		$timeStamp = mktime($timeParts[0], $timeParts[1], $timeParts[2], $spaceParts[1], $spaceParts[0], $spaceParts[2]);

		$offset = $this->getGMToffset($spaceParts[4]);
		$timeStamp -= ($offset * 60); // Compensates for GMT by subtracting the number of seconds which the date is offset from serverTime

		return $timeStamp;
	}

	/**
	 * Parsing the GMT offset value from a mail timestamp.
	 *
	 * @param	string		A string like "+0100" or so.
	 * @return	integer		Minutes to offset the timestamp
	 * @access private
	 */
	function getGMToffset($GMT) {
		$GMToffset = substr($GMT, 1, 2) * 60 + substr($GMT, 3, 2);
		$GMToffset *= substr($GMT, 0, 1) == '+' ? 1 : -1;
		$GMToffset -= $this->serverGMToffsetMinutes;

		return $GMToffset;
	}

	/**
	 * This returns the mail header items in an array with associative keys and the mail body part in another CONTENT field
	 *
	 * @param	string		Raw mail content
	 * @param	integer		A safety limit that will put a upper length to how many header chars will be processed. Set to zero means that there is no limit. (Uses a simple substr() to limit the amount of mail data to process to avoid run-away)
	 * @return	array		An array where each key/value pair is a header-key/value pair. The mail BODY is returned in the key 'CONTENT' if $limit is not set!
	 */
	function extractMailHeader($content, $limit = 0) {
		if ($limit) {
			$content = substr($content, 0, $limit);
		}

		$lines = explode(LF, ltrim($content));
		$headers = array();
		$p = '';
		foreach ($lines as $k => $str) {
			if (!trim($str)) {
				break;
			} // header finished
			$parts = explode(' ', $str, 2);
			if ($parts[0] && substr($parts[0], -1) == ':') {
				$p = strtolower(substr($parts[0], 0, -1));
				if (isset($headers[$p])) {
					$headers[$p . '.'][] = $headers[$p];
					$headers[$p] = '';
				}
				$headers[$p] = trim($parts[1]);
			} else {
				$headers[$p] .= ' ' . trim($str);
			}
			unset($lines[$k]);
		}
		if (!$limit) {
			$headers['CONTENT'] = ltrim(implode(LF, $lines));
		}

		return $headers;
	}

	/**
	 * The extended version of the extractMailHeader() which will also parse all the content body into an array and further process the header fields and decode content etc. Returns every part of the mail ready to go.
	 *
	 * @param	string		Raw email input.
	 * @return	array		Multidimensional array with all parts of the message organized nicely. Use t3lib_utility_Debug::debug() to analyse it visually.
	 */
	function fullParse($content) {
		// *************************
		// PROCESSING the HEADER part of the mail
		// *************************

			// Splitting header and body of mail:
		$mailParts = $this->extractMailHeader($content);

			// Decoding header values which potentially can be encoded by =?...?=
		$list = explode(',', 'subject,thread-topic,from,to');
		foreach ($list as $headerType) {
			if (isset($mailParts[$headerType])) {
				$mailParts[$headerType] = $this->decodeHeaderString($mailParts[$headerType]);
			}
		}
			// Separating email/names from header fields which can contain email addresses.
		$list = explode(',', 'from,to,reply-to,sender,return-path');
		foreach ($list as $headerType) {
			if (isset($mailParts[$headerType])) {
				$mailParts['_' . strtoupper($headerType)] = $this->extractNameEmail($mailParts[$headerType]);
			}
		}
			// Decode date from human-readable format to unix-time (includes compensation for GMT CET)
		$mailParts['_DATE'] = $this->makeUnixDate($mailParts['date']);

			// Transfer encodings of body content
		switch (strtolower($mailParts['content-transfer-encoding'])) {
			case 'quoted-printable':
				$mailParts['CONTENT'] = quoted_printable_decode($mailParts['CONTENT']);
			break;
			case 'base64':
				$mailParts['CONTENT'] = base64_decode($mailParts['CONTENT']);
			break;
		}

			// Content types
		$mailParts['_CONTENT_TYPE_DAT'] = $this->getContentTypeData($mailParts['content-type']);


		// *************************
		// PROCESSING the CONTENT part of the mail (the body)
		// *************************

		$cType = strtolower($mailParts['_CONTENT_TYPE_DAT']['_MIME_TYPE']);
		$cType = substr($cType, 0, 9); // only looking for 'multipart' in string.
		switch ($cType) {
			case 'multipart':
				if ($mailParts['_CONTENT_TYPE_DAT']['boundary']) {
					$contentSectionParts = t3lib_div::trimExplode('--' . $mailParts['_CONTENT_TYPE_DAT']['boundary'], $mailParts['CONTENT'], 1);
					$contentSectionParts_proc = array();

					foreach ($contentSectionParts as $k => $v) {
						if (substr($v, 0, 2) == '--') {
							break;
						}
						$contentSectionParts_proc[$k] = $this->fullParse($v);
					}
					$mailParts['CONTENT'] = $contentSectionParts_proc;
				} else {
					$mailParts['CONTENT'] = 'ERROR: No boundary found.';
				}
			break;
			default:
				if (strtolower($mailParts['_CONTENT_TYPE_DAT']['charset']) == 'utf-8') {
					$mailParts['CONTENT'] = utf8_decode($mailParts['CONTENT']);
				}
			break;
		}

		return $mailParts;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_readmail.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_readmail.php']);
}
?>