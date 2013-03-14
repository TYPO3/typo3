<?php
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
 * Functions used to read email content
 * The class is still just a bunch of miscellaneous functions used to read content out of emails
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @deprecated since 6.0, will be removed with 6.2
 */
class t3lib_readmail {

	/**
	 * @todo Define visibility
	 */
	public $dateAbbrevs = array(
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

	// = +0100 (CET)
	/**
	 * @todo Define visibility
	 */
	public $serverGMToffsetMinutes = 60;

	/**
	 * Deprecation constructor
	 */
	public function __construct() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('Class t3lib_readmail is deprecated and unused since TYPO3 6.0. It will be removed with version 6.2.');
	}


	/*******************************
	 *
	 * General
	 *
	 ********************************/
	/**
	 * Returns the text content of a mail which has previously been parsed by eg. extractMailHeader()
	 * Probably obsolete since the function fullParse() is more advanced and safer to use.
	 *
	 * @param array $mailParts Output from extractMailHeader()
	 * @return string The content.
	 * @todo Define visibility
	 */
	public function getMessage($mailParts) {
		if ($mailParts['content-type']) {
			$CType = $this->getCType($mailParts['content-type']);
			if ($CType['boundary']) {
				$parts = $this->getMailBoundaryParts($CType['boundary'], $mailParts['CONTENT']);
				$c = $this->getTextContent($parts[0]);
			} else {
				$c = $this->getTextContent('Content-Type: ' . $mailParts['content-type'] . '
					' . $mailParts['CONTENT']);
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
	 * @param string $content Raw mail content
	 * @return string Body of message
	 * @todo Define visibility
	 */
	public function getTextContent($content) {
		$p = $this->extractMailHeader($content);
		// Here some decoding might be needed...
		// However we just return what is believed to be the proper notification:
		return $p['CONTENT'];
	}

	/**
	 * Splits the body of a mail into parts based on the boundary string given.
	 * Obsolete, use fullParse()
	 *
	 * @param string $boundary Boundary string used to split the content.
	 * @param string $content BODY section of a mail
	 * @return array Parts of the mail based on this
	 * @todo Define visibility
	 */
	public function getMailBoundaryParts($boundary, $content) {
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
	 * @param string $str "ContentType" string with more
	 * @return array Parts in key/value pairs
	 * @ignore
	 * @todo Define visibility
	 */
	public function getCType($str) {
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
	 * @param string $c Message body/text
	 * @return array Key/value pairs with analysis result. Eg. "reason", "content", "reason_text", "mailserver" etc.
	 * @todo Define visibility
	 */
	public function analyseReturnError($c) {
		$cp = array();
		// QMAIL
		if (strstr($c, '--- Below this line is a copy of the message.')) {
			// Splits by the QMAIL divider
			list($c) = explode('--- Below this line is a copy of the message.', $c);
			$cp['content'] = trim($c);
			$parts = explode('>:', $c, 2);
			$cp['reason_text'] = trim($parts[1]);
			$cp['mailserver'] = 'Qmail';
			if (preg_match('/550|no mailbox|account does not exist/i', $cp['reason_text'])) {
				// 550 Invalid recipient
				$cp['reason'] = 550;
			} elseif (stristr($cp['reason_text'], 'couldn\'t find any host named')) {
				// Bad host
				$cp['reason'] = 2;
			} elseif (preg_match('/Error in Header|invalid Message-ID header/i', $cp['reason_text'])) {
				$cp['reason'] = 554;
			} else {
				$cp['reason'] = -1;
			}
		} elseif (strstr($c, 'The Postfix program')) {
			// Postfix
			$cp['content'] = trim($c);
			$parts = explode('>:', $c, 2);
			$cp['reason_text'] = trim($parts[1]);
			$cp['mailserver'] = 'Postfix';
			if (stristr($cp['reason_text'], '550')) {
				// 550 Invalid recipient, User unknown
				$cp['reason'] = 550;
			} elseif (stristr($cp['reason_text'], '553')) {
				// No such user
				$cp['reason'] = 553;
			} elseif (stristr($cp['reason_text'], '551')) {
				// Mailbox full
				$cp['reason'] = 551;
			} else {
				$cp['reason'] = -1;
			}
		} else {
			// No-named:
			$cp['content'] = trim($c);
			$cp['reason_text'] = trim(substr($c, 0, 1000));
			$cp['mailserver'] = 'unknown';
			if (preg_match('/Unknown Recipient|Delivery failed 550|Receiver not found|User not listed|recipient problem|Delivery to the following recipients failed|User unknown|recipient name is not recognized/i', $cp['reason_text'])) {
				// 550 Invalid recipient, User unknown
				$cp['reason'] = 550;
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
	 * @param string $str A string (encoded or not) from a mail header, like sender name etc.
	 * @return string The input string, but with the parts in =?....?= decoded.
	 * @todo Define visibility
	 */
	public function decodeHeaderString($str) {
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
			// Calls decodeHeaderString recursively for any subsequent encoded section.
			$parts[1] = $encContent . $this->decodeHeaderString($subparts[1]);
		}
		return implode('', $parts);
	}

	/**
	 * Extracts name/email parts from a header field (like 'To:' or 'From:' with name/email mixed up.
	 *
	 * @param string $str Value from a header field containing name/email values.
	 * @return array Array with the name and email in. Email is validated, otherwise not set.
	 * @todo Define visibility
	 */
	public function extractNameEmail($str) {
		$outArr = array();
		// Email:
		$reg = '';
		preg_match('/<([^>]*)>/', $str, $reg);
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($str)) {
			$outArr['email'] = $str;
		} elseif ($reg[1] && \TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($reg[1])) {
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
	 * @param string $contentTypeStr "Content-type-string
	 * @return array key/value pairs with the result.
	 * @todo Define visibility
	 */
	public function getContentTypeData($contentTypeStr) {
		$outValue = array();
		$cTypeParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $contentTypeStr, 1);
		// Content type, first value is supposed to be the mime-type, whatever after the first is something else.
		$outValue['_MIME_TYPE'] = $cTypeParts[0];
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
	 * @param string $dateStr String with a timestamp according to email standards.
	 * @return integer The timestamp converted to unix-time in seconds and compensated for GMT/CET ($this->serverGMToffsetMinutes);
	 * @todo Define visibility
	 */
	public function makeUnixDate($dateStr) {
		$dateParts = explode(',', $dateStr);
		$dateStr = count($dateParts) > 1 ? $dateParts[1] : $dateParts[0];
		$spaceParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $dateStr, 1);
		$spaceParts[1] = $this->dateAbbrevs[strtoupper($spaceParts[1])];
		$timeParts = explode(':', $spaceParts[3]);
		$timeStamp = mktime($timeParts[0], $timeParts[1], $timeParts[2], $spaceParts[1], $spaceParts[0], $spaceParts[2]);
		$offset = $this->getGMToffset($spaceParts[4]);
		// Compensates for GMT by subtracting the number of seconds which the date is offset from serverTime
		$timeStamp -= $offset * 60;
		return $timeStamp;
	}

	/**
	 * Parsing the GMT offset value from a mail timestamp.
	 *
	 * @param string $GMT A string like "+0100" or so.
	 * @return integer Minutes to offset the timestamp
	 * @access private
	 * @todo Define visibility
	 */
	public function getGMToffset($GMT) {
		$GMToffset = substr($GMT, 1, 2) * 60 + substr($GMT, 3, 2);
		$GMToffset *= substr($GMT, 0, 1) == '+' ? 1 : -1;
		$GMToffset -= $this->serverGMToffsetMinutes;
		return $GMToffset;
	}

	/**
	 * This returns the mail header items in an array with associative keys and the mail body part in another CONTENT field
	 *
	 * @param string $content Raw mail content
	 * @param integer $limit A safety limit that will put a upper length to how many header chars will be processed. Set to zero means that there is no limit. (Uses a simple substr() to limit the amount of mail data to process to avoid run-away)
	 * @return array An array where each key/value pair is a header-key/value pair. The mail BODY is returned in the key 'CONTENT' if $limit is not set!
	 * @todo Define visibility
	 */
	public function extractMailHeader($content, $limit = 0) {
		if ($limit) {
			$content = substr($content, 0, $limit);
		}
		$lines = explode(LF, ltrim($content));
		$headers = array();
		$p = '';
		foreach ($lines as $k => $str) {
			if (!trim($str)) {
				break;
			}
			// Header finished
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
	 * @param string $content Raw email input.
	 * @return array Multidimensional array with all parts of the message organized nicely. Use t3lib_utility_Debug::debug() to analyse it visually.
	 * @todo Define visibility
	 */
	public function fullParse($content) {
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
		// Only looking for 'multipart' in string.
		$cType = substr($cType, 0, 9);
		switch ($cType) {
		case 'multipart':
			if ($mailParts['_CONTENT_TYPE_DAT']['boundary']) {
				$contentSectionParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('--' . $mailParts['_CONTENT_TYPE_DAT']['boundary'], $mailParts['CONTENT'], 1);
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

?>