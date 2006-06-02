<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * HTML mail class
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  193: class t3lib_htmlmail
 *  261:     function t3lib_htmlmail ()
 *  268:     function start ()
 *  305:     function useQuotedPrintable()
 *  315:     function useBase64()
 *  326:     function use8Bit()
 *  338:     function encodeMsg($content)
 *  348:     function addPlain ($content)
 *  360:     function addAttachment($file)
 *  378:     function addHTML ($file)
 *  401:     function extractHtmlInit($html,$url)
 *  412:     function send($recipient)
 *
 *              SECTION: Main functions
 *  441:     function setHeaders()
 *  500:     function setRecipient ($recip)
 *  518:     function getHTMLContentType()
 *  527:     function setContent()
 *  554:     function constructMixed ($boundary)
 *  593:     function constructHTML ($boundary)
 *  617:     function constructAlternative($boundary)
 *  638:     function constructHTML_media ($boundary)
 *  691:     function sendTheMail ()
 *  757:     function getBoundary()
 *  769:     function setPlain ($content)
 *  780:     function setHtml ($content)
 *  791:     function add_header($header)
 *  812:     function add_message($string)
 *  823:     function getContent($type)
 *  832:     function preview()
 *
 *              SECTION: Functions for acquiring attachments, HTML, analyzing and so on  **
 *  860:     function fetchHTML($file)
 *  878:     function fetchHTMLMedia()
 *  899:     function extractMediaLinks()
 *  976:     function extractHyperLinks()
 * 1025:     function extractFramesInfo()
 * 1051:     function substMediaNamesInHTML($absolute)
 * 1078:     function substHREFsInHTML()
 * 1106:     function substHTTPurlsInPlainText($content)
 * 1142:     function fixRollOvers()
 *
 *              SECTION: File and URL-functions
 * 1189:     function makeBase64($inputstr)
 * 1200:     function getExtendedURL($url)
 * 1222:     function addUserPass($url)
 * 1238:     function getURL($url)
 * 1250:     function getStrippedURL($url)
 * 1271:     function getMimeType($url)
 * 1300:     function absRef($ref)
 * 1320:     function split_fileref($fileref)
 * 1347:     function extParseUrl($path)
 * 1362:     function tag_regex($tagArray)
 * 1384:     function get_tag_attributes($tag)
 * 1426:     function quoted_printable($string)
 * 1437:     function convertName($name)
 *
 * TOTAL FUNCTIONS: 49
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */









/**
 * NOTES on MIME mail structures:
 *
 * Plain + HTML
 * 	multipart/alternative	(text, html)
 * 	multipart/alternative	(text, html)
 *
 * Plain + HTML + image
 * 	multipart/related (m/a, cids)
 * 		multipart/alternative (text, html)
 *
 * 	multipart/related  (m/a, cids)
 * 		multipart/alternative	(text, html)
 *
 * plain + attachment
 * 	multipart/mixed
 *
 * HTML + Attachment:
 * 	multipart/mixed		(text/html , attachments)
 *
 * Plain + HTML + Attachments:
 * 	multipart/mixed		(m/a, attachments)
 * 		multipart/alternative	(text, html)
 *
 * Plain + HTML + image + attachment
 *
 * 		Outlook expr.
 * 	multipart/mixed (m/r, attachments)
 * 		multipart/related  (m/a, cids)
 * 			multipart/alternative	(text, html)
 *
 *
 *
 * FROM RFC 1521:
 *
 * 5.1 Quoted-Printable Content-Transfer-Encoding
 * The Quoted-Printable encoding is intended to represent data that largely consists of octets that correspond to printable characters in the ASCII character set. It encodes the data in such a way that the resulting octets are unlikely to be modified by mail transport. If the data being encoded are mostly ASCII text, the encoded form of the data remains largely recognizable by humans. A body which is entirely ASCII may also be encoded in Quoted-Printable to ensure the integrity of the data should the message pass through a character- translating, and/or line-wrapping gateway.
 *
 * In this encoding, octets are to be represented as determined by the following rules:
 * Rule #1: (General 8-bit representation) Any octet, except those indicating a line break according to the newline convention of the canonical (standard) form of the data being encoded, may be represented by an "=" followed by a two digit hexadecimal representation of the octet's value. The digits of the hexadecimal alphabet, for this purpose, are "0123456789ABCDEF". Uppercase letters must be used when sending hexadecimal data, though a robust implementation may choose to recognize lowercase letters on receipt. Thus, for example, the value 12 (ASCII form feed) can be represented by "=0C", and the value 61 (ASCII EQUAL SIGN) can be represented by "=3D". Except when the following rules allow an alternative encoding, this rule is mandatory.
 * Rule #2: (Literal representation) Octets with decimal values of 33 through 60 inclusive, and 62 through 126, inclusive, MAY be represented as the ASCII characters which correspond to those octets (EXCLAMATION POINT through LESS THAN, and GREATER THAN through TILDE, respectively).
 * Rule #3: (White Space): Octets with values of 9 and 32 MAY be represented as ASCII TAB (HT) and SPACE characters, respectively, but MUST NOT be so represented at the end of an encoded line. Any TAB (HT) or SPACE characters on an encoded line MUST thus be followed on that line by a printable character. In particular, an
 * "=" at the end of an encoded line, indicating a soft line break (see rule #5) may follow one or more TAB (HT) or SPACE characters. It follows that an octet with value 9 or 32 appearing at the end of an encoded line must be represented according to Rule #1. This rule is necessary because some MTAs (Message Transport Agents, programs which transport messages from one user to another, or perform a part of such transfers) are known to pad lines of text with SPACEs, and others are known to remove "white space" characters from the end of a line. Therefore, when decoding a Quoted-Printable body, any trailing white space on a line must be deleted, as it will necessarily have been added by intermediate transport agents.
 * Rule #4 (Line Breaks): A line break in a text body, independent of what its representation is following the canonical representation of the data being encoded, must be represented by a (RFC 822) line break, which is a CRLF sequence, in the Quoted-Printable encoding. Since the canonical representation of types other than text do not generally include the representation of line breaks, no hard line breaks (i.e. line breaks that are intended to be meaningful and to be displayed to the user) should occur in the quoted-printable encoding of such types. Of course, occurrences of "=0D", "=0A", "0A=0D" and "=0D=0A" will eventually be encountered. In general, however, base64 is preferred over quoted-printable for binary data.
 * Note that many implementations may elect to encode the local representation of various content types directly, as described in Appendix G. In particular, this may apply to plain text material on systems that use newline conventions other than CRLF delimiters. Such an implementation is permissible, but the generation of line breaks must be generalized to account for the case where alternate representations of newline sequences are used.
 * Rule #5 (Soft Line Breaks): The Quoted-Printable encoding REQUIRES that encoded lines be no more than 76 characters long. If longer lines are to be encoded with the Quoted-Printable encoding, 'soft' line breaks must be used. An equal sign as the last character on a encoded line indicates such a non-significant ('soft') line break in the encoded text. Thus if the "raw" form of the line is a single unencoded line that says:
 * Now's the time for all folk to come to the aid of their country.
 *
 * This can be represented, in the Quoted-Printable encoding, as
 *
 * Now's the time =
 * for all folk to come=
 * to the aid of their country.
 *
 * This provides a mechanism with which long lines are encoded in such a way as to be restored by the user agent. The 76 character limit does not count the trailing CRLF, but counts all other characters, including any equal signs.
 * Since the hyphen character ("-") is represented as itself in the Quoted-Printable encoding, care must be taken, when encapsulating a quoted-printable encoded body in a multipart entity, to ensure that the encapsulation boundary does not appear anywhere in the encoded body. (A good strategy is to choose a boundary that includes a character sequence such as "=_" which can never appear in a quoted- printable body. See the definition of multipart messages later in this document.)
 * NOTE: The quoted-printable encoding represents something of a compromise between readability and reliability in transport. Bodies encoded with the quoted-printable encoding will work reliably over most mail gateways, but may not work perfectly over a few gateways, notably those involving translation into EBCDIC. (In theory, an EBCDIC gateway could decode a quoted-printable body and re-encode it using base64, but such gateways do not yet exist.) A higher level of confidence is offered by the base64 Content-Transfer-Encoding. A way to get reasonably reliable transport through EBCDIC gateways is to also quote the ASCII characters
 * !"#$@[\]^`{|}~
 * according to rule #1. See Appendix B for more information.
 * Because quoted-printable data is generally assumed to be line- oriented, it is to be expected that the representation of the breaks between the lines of quoted printable data may be altered in transport, in the same manner that plain text mail has always been altered in Internet mail when passing between systems with differing newline conventions. If such alterations are likely to constitute a corruption of the data, it is probably more sensible to use the base64 encoding rather than the quoted-printable encoding.
 * WARNING TO IMPLEMENTORS: If binary data are encoded in quoted- printable, care must be taken to encode CR and LF characters as "=0D" and "=0A", respectively. In particular, a CRLF sequence in binary data should be encoded as "=0D=0A". Otherwise, if CRLF were represented as a hard line break, it might be incorrectly decoded on
 * platforms with different line break conventions.
 * For formalists, the syntax of quoted-printable data is described by the following grammar:
 *
 *    quoted-printable := ([*(ptext / SPACE / TAB) ptext] ["="] CRLF)
 *         ; Maximum line length of 76 characters excluding CRLF
 *
 *    ptext := octet /<any ASCII character except "=", SPACE, or TAB>
 *         ; characters not listed as "mail-safe" in Appendix B
 *         ; are also not recommended.
 *
 *    octet := "=" 2(DIGIT / "A" / "B" / "C" / "D" / "E" / "F")
 *         ; octet must be used for characters > 127, =, SPACE, or TAB,
 *         ; and is recommended for any characters not listed in
 *         ; Appendix B as "mail-safe".
 */

/**
 * HTML mail class
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_htmlmail {
		// Headerinfo:
	var $recipient = "recipient@whatever.com";
	var $recipient_copy = "";		// This recipient (or list of...) will also receive the mail. Regard it as a copy.
	var $subject = "This is the subject";
	var $from_email = "sender@php-mailer.com";
	var $from_name = "Mr. Sender";
	var $replyto_email = "reply@mailer.com";
	var $replyto_name = "Mr. Reply";
	var $organisation = "Your Company";
	var $priority = 3;   // 1 = highest, 5 = lowest, 3 = normal
	var $mailer = "PHP mailer";	// X-mailer
	var $alt_base64=0;
	var $alt_8bit=0;
	var $jumperURL_prefix ="";		// This is a prefix that will be added to all links in the mail. Example: 'http://www.mydomain.com/jump?userid=###FIELD_uid###&url='. if used, anything after url= is urlencoded.
	var $jumperURL_useId=0;			// If set, then the array-key of the urls are inserted instead of the url itself. Smart in order to reduce link-length
	var $mediaList="";				// If set, this is a list of the media-files (index-keys to the array) that should be represented in the html-mail
	var $http_password="";
	var $http_username="";
	var $postfix_version1=false;

	// Internal

/*		This is how the $theParts-array is normally looking
	var $theParts = Array(
		"plain" => Array (
			"content"=> ""
		),
		"html" => Array (
			"content"=> "",
			"path" => "",
			"media" => Array(),
			"hrefs" => Array()
		),
		"attach" => Array ()
	);
*/
	var $theParts = Array();

	var $messageid = "";
	var $returnPath = "";
	var $Xid = "";
	var $dontEncodeHeader = false;		// If set, the header will not be encoded

	var $headers = "";
	var $message = "";
	var $part=0;
	var $image_fullpath_list = "";
	var $href_fullpath_list = "";

	var $plain_text_header = '';
	var $html_text_header = '';
	var $charset = '';
	var $defaultCharset = 'iso-8859-1';








	/**
	 * Constructor. If the configuration variable forceReturnPath is set, calls to mail will be called with a 5th parameter.
	 * See function sendTheMail for more info
	 *
	 * @return	[type]		...
	 */
	function t3lib_htmlmail () {
		$this->forceReturnPath = $GLOBALS['TYPO3_CONF_VARS']['SYS']['forceReturnPath'];
	}

	/**
	 * @return	[type]		...
	 */
	function start ()	{
		global $TYPO3_CONF_VARS;

			// Sets the message id
		$host = t3lib_div::getHostname();
		if (!$host || $host == '127.0.0.1' || $host == 'localhost' || $host == 'localhost.localdomain') {
			$host = ($TYPO3_CONF_VARS['SYS']['sitename'] ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $TYPO3_CONF_VARS['SYS']['sitename']) : 'localhost') . '.TYPO3';
		}
		$this->messageid = md5(microtime()) . '@' . $host;

			// Default line break for Unix systems.
		$this->linebreak = chr(10);
			// Line break for Windows. This is needed because PHP on Windows systems send mails via SMTP instead of using sendmail, and thus the linebreak needs to be \r\n.
		if (TYPO3_OS=='WIN')	{
			$this->linebreak = chr(13).chr(10);
		}

		$charset = $this->defaultCharset;
		if (is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->config['metaCharset'])	{
			$charset = $GLOBALS['TSFE']->config['metaCharset'];
		} elseif ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'])	{
			$charset = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'];
		}
		$this->charset = $charset;

			// Use quoted-printable headers by default
		$this->useQuotedPrintable();
	}

	/**
	 * [Describe function...]
	 *
	 * @return	void
	 */
	function useQuotedPrintable()	{
		$this->plain_text_header = 'Content-Type: text/plain; charset='.$this->charset.$this->linebreak.'Content-Transfer-Encoding: quoted-printable';
		$this->html_text_header = 'Content-Type: text/html; charset='.$this->charset.$this->linebreak.'Content-Transfer-Encoding: quoted-printable';
	}

	/**
	 * [Describe function...]
	 *
	 * @return	void
	 */
	function useBase64()	{
		$this->plain_text_header = 'Content-Type: text/plain; charset='.$this->charset.$this->linebreak.'Content-Transfer-Encoding: base64';
		$this->html_text_header = 'Content-Type: text/html; charset='.$this->charset.$this->linebreak.'Content-Transfer-Encoding: base64';
		$this->alt_base64=1;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	void
	 */
	function use8Bit()	{
		$this->plain_text_header = 'Content-Type: text/plain; charset='.$this->charset.$this->linebreak.'Content-Transfer-Encoding: 8bit';
		$this->html_text_header = 'Content-Type: text/html; charset='.$this->charset.$this->linebreak.'Content-Transfer-Encoding: 8bit';
		$this->alt_8bit=1;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function encodeMsg($content)	{
		return $this->alt_base64 ? $this->makeBase64($content) : ($this->alt_8bit ? $content : t3lib_div::quoted_printable($content));
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function addPlain ($content)	{
			// Adds plain-text and qp-encodes it
		$content=$this->substHTTPurlsInPlainText($content);
		$this->setPlain($this->encodeMsg($content));
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$file: ...
	 * @return	[type]		...
	 */
	function addAttachment($file)	{
			// Adds an attachment to the mail
		$theArr = $this->getExtendedURL($file);		// We fetch the content and the mime-type
		if ($theArr)	{
			if (!$theArr["content_type"]){$theArr["content_type"]="application/octet-stream";}
			$temp = $this->split_fileref($file);
			$theArr["filename"]= (($temp["file"])?$temp["file"]:(strpos(" ".$theArr["content_type"],"htm")?"index.html":"unknown"));
			$this->theParts["attach"][]=$theArr;
			return true;
		} else { return false;}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$file: ...
	 * @return	[type]		...
	 */
	function addHTML ($file)	{
			// Adds HTML and media, encodes it from a URL or file
		$status = $this->fetchHTML($file);
//		debug(md5($status));
		if (!$status)	{return false;}
		if ($this->extractFramesInfo())	{
			return "Document was a frameset. Stopped";
		}
		$this->extractMediaLinks();
		$this->extractHyperLinks();
		$this->fetchHTMLMedia();
		$this->substMediaNamesInHTML(0);	// 0 = relative
		$this->substHREFsInHTML();
		$this->setHTML($this->encodeMsg($this->theParts["html"]["content"]));
	}

	/**
	 * External used to extract HTML-parts
	 *
	 * @param	[type]		$html: ...
	 * @param	[type]		$url: ...
	 * @return	[type]		...
	 */
	function extractHtmlInit($html,$url)	{
		$this->theParts["html"]["content"]=$html;
		$this->theParts["html"]["path"]=$url;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$recipient: ...
	 * @return	[type]		...
	 */
	function send($recipient)	{
			// This function sends the mail to one $recipient
		if ($recipient) {$this->recipient = $recipient;}
		$this->setHeaders();
		$this->setContent();
		$this->sendTheMail();
	}













	/*****************************************
	 *
	 * Main functions
	 *
	 *****************************************/

	/**
	 * @return	[type]		...
	 */
	function setHeaders()	{
			// Clears the header-string and sets the headers based on object-vars.
		$this->headers = "";
			// Message_id
		$this->add_header("Message-ID: <".$this->messageid.">");
			// Return path
		if ($this->returnPath)	{
			$this->add_header("Return-Path: ".$this->returnPath);
			$this->add_header("Errors-To: ".$this->returnPath);
		}
			// X-id
		if ($this->Xid)	{
			$this->add_header("X-Typo3MID: ".$this->Xid);
		}

			// From
		if ($this->from_email)	{
			if ($this->from_name)	{
				$this->add_header('From: '.$this->from_name.' <'.$this->from_email.'>');
			} else {
				$this->add_header('From: '.$this->from_email);
			}
		}
			// Reply
		if ($this->replyto_email)	{
			if ($this->replyto_name)	{
				$this->add_header('Reply-To: '.$this->replyto_name.' <'.$this->replyto_email.'>');
			} else {
				$this->add_header('Reply-To: '.$this->replyto_email);
			}
		}
			// Organisation
		if ($this->organisation)	{
			$this->add_header('Organisation: '.$this->organisation);
		}
			// mailer
		if ($this->mailer)	{
			$this->add_header("X-Mailer: $this->mailer");
		}
			// priority
		if ($this->priority)	{
			$this->add_header("X-Priority: $this->priority");
		}
		$this->add_header("Mime-Version: 1.0");

		if (!$this->dontEncodeHeader)	{
			$enc = $this->alt_base64 ? 'base64' : 'quoted_printable';	// Header must be ASCII, therefore only base64 or quoted_printable are allowed!
				// Quote recipient and subject
			$this->recipient = t3lib_div::encodeHeader($this->recipient,$enc,$this->charset);
			$this->subject = t3lib_div::encodeHeader($this->subject,$enc,$this->charset);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$recip: ...
	 * @return	[type]		...
	 */
	function setRecipient ($recip)	{
		// Sets the recipient(s). If you supply a string, you set one recipient. If you supply an array, every value is added as a recipient.
		if (is_array($recip))	{
			$this->recipient = "";
			while (list($key,) = each($recip)) {
				$this->recipient .= $recip[$key].",";
			}
			$this->recipient = ereg_replace(",$","",$this->recipient);
		} else {
			$this->recipient = $recip;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getHTMLContentType()	{
		return count($this->theParts["html"]["media"]) ? 'multipart/related;' : 'multipart/alternative;';
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function setContent()	{
			// Begins building the message-body
		$this->message = "";
		$boundary = $this->getBoundary();
		// Setting up headers
		if (count($this->theParts["attach"]))	{
			$this->add_header('Content-Type: multipart/mixed;');
			$this->add_header(' boundary="'.$boundary.'"');
			$this->add_message("This is a multi-part message in MIME format.\n");
			$this->constructMixed($boundary);	// Generate (plain/HTML) / attachments
		} elseif ($this->theParts["html"]["content"]) {
			$this->add_header('Content-Type: '.$this->getHTMLContentType());
			$this->add_header(' boundary="'.$boundary.'"');
			$this->add_message("This is a multi-part message in MIME format.\n");
			$this->constructHTML($boundary);		// Generate plain/HTML mail
		} else {
			$this->add_header($this->plain_text_header);
			$this->add_message($this->getContent("plain"));	// Generate plain only
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$boundary: ...
	 * @return	[type]		...
	 */
	function constructMixed ($boundary)	{
			// Here (plain/HTML) is combined with the attachments
		$this->add_message("--".$boundary);
			// (plain/HTML) is added
		if ($this->theParts["html"]["content"])	{
				// HTML and plain
			$newBoundary = $this->getBoundary();
			$this->add_message("Content-Type: ".$this->getHTMLContentType());
			$this->add_message(' boundary="'.$newBoundary.'"');
			$this->add_message('');
			$this->constructHTML($newBoundary);
		} else {	// Purely plain
			$this->add_message($this->plain_text_header);
			$this->add_message('');
			$this->add_message($this->getContent("plain"));
		}
			// attachments are added
		if (is_array($this->theParts["attach"]))	{
			reset($this->theParts["attach"]);
			while(list(,$media)=each($this->theParts["attach"]))	{
				$this->add_message("--".$boundary);
				$this->add_message("Content-Type: ".$media["content_type"]);
				$this->add_message(' name="'.$media["filename"].'"');
				$this->add_message("Content-Transfer-Encoding: base64");
				$this->add_message("Content-Disposition: attachment;");
				$this->add_message(' filename="'.$media["filename"].'"');
				$this->add_message('');
				$this->add_message($this->makeBase64($media["content"]));
			}
		}
		$this->add_message("--".$boundary."--\n");
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$boundary: ...
	 * @return	[type]		...
	 */
	function constructHTML ($boundary)	{
		if (count($this->theParts["html"]["media"]))	{	// If media, then we know, the multipart/related content-type has been set before this function call...
			$this->add_message("--".$boundary);
				// HTML has media
			$newBoundary = $this->getBoundary();
			$this->add_message("Content-Type: multipart/alternative;");
			$this->add_message(' boundary="'.$newBoundary.'"');
			$this->add_message('Content-Transfer-Encoding: 7bit');
			$this->add_message('');

			$this->constructAlternative($newBoundary);	// Adding the plaintext/html mix

			$this->constructHTML_media($boundary);
		} else {
			$this->constructAlternative($boundary);	// Adding the plaintext/html mix, and if no media, then use $boundary instead of $newBoundary
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$boundary: ...
	 * @return	[type]		...
	 */
	function constructAlternative($boundary)	{
			// Here plain is combined with HTML
		$this->add_message("--".$boundary);
			// plain is added
		$this->add_message($this->plain_text_header);
		$this->add_message('');
		$this->add_message($this->getContent("plain"));
		$this->add_message("--".$boundary);
			// html is added
		$this->add_message($this->html_text_header);
		$this->add_message('');
		$this->add_message($this->getContent("html"));
		$this->add_message("--".$boundary."--\n");
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$boundary: ...
	 * @return	[type]		...
	 */
	function constructHTML_media ($boundary)	{
/*			// Constructs the HTML-part of message if the HTML contains media
		$this->add_message("--".$boundary);
			// htmlcode is added
		$this->add_message($this->html_text_header);
		$this->add_message('');
		$this->add_message($this->getContent("html"));

		OLD stuf...

		*/
			// media is added
		if (is_array($this->theParts["html"]["media"]))	{
			reset($this->theParts["html"]["media"]);
			while(list($key,$media)=each($this->theParts["html"]["media"]))	{
				if (!$this->mediaList || t3lib_div::inList($this->mediaList,$key))	{
					$this->add_message("--".$boundary);
					$this->add_message("Content-Type: ".$media["ctype"]);
					$this->add_message("Content-ID: <part".$key.".".$this->messageid.">");
					$this->add_message("Content-Transfer-Encoding: base64");
					$this->add_message('');
					$this->add_message($this->makeBase64($media["content"]));
				}
			}
		}
		$this->add_message("--".$boundary."--\n");
	}

	/**
	 * Sends the mail by calling the mail() function in php. On Linux systems this will invoke the MTA
	 * defined in php.ini (sendmail -t -i by default), on Windows a SMTP must be specified in the sys.ini.
	 * Most common MTA's on Linux has a Sendmail interface, including Postfix and Exim.
	 * For setting the return-path correctly, the parameter -f has to be added to the system call to sendmail.
	 * This obviously does not have any effect on Windows, but on Sendmail compliant systems this works. If safe mode
	 * is enabled, then extra parameters is not allowed, so a safe mode check is made before the mail() command is
	 * invoked. When using the -f parameter, some MTA's will put an X-AUTHENTICATION-WARNING saying that
	 * the return path was modified manually with the -f flag. To disable this warning make sure that the user running
	 * Apache is in the /etc/mail/trusted-users table.
	 *
	 * POSTFIX: With postfix version below 2.0 there is a problem that the -f parameter can not be used in conjunction
	 * with -t. Postfix will give an error in the maillog:
	 *
	 *  cannot handle command-line recipients with -t
	 *
	 * The -f parameter is only enabled if the parameter forceReturnPath is enabled in the install tool.
	 *
	 * This whole problem of return-path turns out to be quite tricky. If you have a solution that works better, on all
	 * standard MTA's then we are very open for suggestions.
	 *
	 * With time this function should be made such that several ways of sending the mail is possible (local MTA, smtp other).
	 *
	 * @return	[type]		...
	 */
	function sendTheMail () {
#debug(array($this->recipient,$this->subject,$this->message,$this->headers));
			// Sends the mail, requires the recipient, message and headers to be set.
		if (trim($this->recipient) && trim($this->message))	{	//  && trim($this->headers)
			$returnPath = (strlen($this->returnPath)>0)?"-f".$this->returnPath:'';
				//On windows the -f flag is not used (specific for Sendmail and Postfix), but instead the php.ini parameter sendmail_from is used.
			if($this->returnPath) {
				ini_set(sendmail_from, $this->returnPath);
			}
				//If safe mode is on, the fifth parameter to mail is not allowed, so the fix wont work on unix with safe_mode=On
			if(!ini_get('safe_mode') && $this->forceReturnPath) {
				mail($this->recipient,
					  $this->subject,
					  $this->message,
					  $this->headers,
					  $returnPath);
			} else {
				mail($this->recipient,
					  $this->subject,
					  $this->message,
					  $this->headers);
			}
				// Sending copy:
			if ($this->recipient_copy)	{
				if(!ini_get('safe_mode') && $this->forceReturnPath) {
					mail( 	$this->recipient_copy,
								$this->subject,
								$this->message,
								$this->headers,
								$returnPath);
				} else {
					mail( 	$this->recipient_copy,
								$this->subject,
								$this->message,
								$this->headers	);
				}
			}
				// Auto response
			if ($this->auto_respond_msg)	{
				$theParts = explode('/',$this->auto_respond_msg,2);
				$theParts[1] = str_replace("/",chr(10),$theParts[1]);
				if(!ini_get('safe_mode') && $this->forceReturnPath) {
					mail( 	$this->from_email,
								$theParts[0],
								$theParts[1],
								"From: ".$this->recipient,
								$returnPath);
				} else {
					mail( 	$this->from_email,
								$theParts[0],
								$theParts[1],
								"From: ".$this->recipient);
				}
			}
			if($this->returnPath) {
				ini_restore(sendmail_from);
			}
			return true;
		} else {return false;}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getBoundary()	{
			// Returns boundaries
		$this->part++;
		return 	"----------".uniqid("part_".$this->part."_");
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function setPlain ($content)	{
			// Sets the plain-text part. No processing done.
		$this->theParts["plain"]["content"] = $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function setHtml ($content)	{
			// Sets the HTML-part. No processing done.
		$this->theParts["html"]["content"] = $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$header: ...
	 * @return	[type]		...
	 */
	function add_header($header)	{
		if (!$this->dontEncodeHeader && !stristr($header,'Content-Type') && !stristr($header,'Content-Transfer-Encoding'))	{
				// Mail headers must be ASCII, therefore we convert the whole header to either base64 or quoted_printable
			$parts = explode(': ',$header,2);	// Field tags must not be encoded
			if (count($parts)==2)	{
				$enc = $this->alt_base64 ? 'base64' : 'quoted_printable';
				$parts[1] = t3lib_div::encodeHeader($parts[1],$enc,$this->charset);
				$header = implode(': ',$parts);
			}
		}

			// Adds a header to the mail. Use this AFTER the setHeaders()-function
		$this->headers.=$header."\n";
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$string: ...
	 * @return	[type]		...
	 */
	function add_message($string)	{
			// Adds a line of text to the mail-body. Is normally use internally
		$this->message.=$string."\n";
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$type: ...
	 * @return	[type]		...
	 */
	function getContent($type)	{
		return $this->theParts[$type]["content"];
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function preview()	{
		echo nl2br(HTMLSpecialChars($this->headers));
		echo "<BR>";
		echo nl2br(HTMLSpecialChars($this->message));
	}













	/****************************************************
	 *
	 * Functions for acquiring attachments, HTML, analyzing and so on  **
	 *
	 ***************************************************/

	/**
	 * @param	[type]		$file: ...
	 * @return	[type]		...
	 */
	function fetchHTML($file)	{
			// Fetches the HTML-content from either url og local serverfile
		$this->theParts["html"]["content"] = $this->getURL($file);	// Fetches the content of the page
		if ($this->theParts["html"]["content"])	{
			$addr = $this->extParseUrl($file);
 			$path = ($addr['scheme']) ? $addr['scheme'].'://'.$addr['host'].(($addr['port'])?':'.$addr['port']:'').(($addr['filepath'])?$addr['filepath']:'/') : $addr['filepath'];
			$this->theParts["html"]["path"] = $path;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function fetchHTMLMedia()	{
			// Fetches the mediafiles which are found by extractMediaLinks()
		if (is_array($this->theParts["html"]["media"]))	{
			reset ($this->theParts["html"]["media"]);
			if (count($this->theParts["html"]["media"]) > 0)	{
				while (list($key,$media) = each ($this->theParts["html"]["media"]))	{
					$picdata = $this->getExtendedURL($this->theParts["html"]["media"][$key]["absRef"]);		// We fetch the content and the mime-type
					if (is_array($picdata))	{
						$this->theParts["html"]["media"][$key]["content"] = $picdata["content"];
						$this->theParts["html"]["media"][$key]["ctype"] = $picdata["content_type"];
					}
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function extractMediaLinks()	{
			// extracts all media-links from $this->theParts["html"]["content"]
		$html_code = $this->theParts["html"]["content"];
		$attribRegex = $this->tag_regex(Array("img","table","td","tr","body","iframe","script","input","embed"));
		$codepieces = split($attribRegex, $html_code);	// Splits the document by the beginning of the above tags
		$len=strlen($codepieces[0]);
		$pieces = count($codepieces);
		$reg = array();
		for($i=1; $i < $pieces; $i++)	{
			$tag = strtolower(strtok(substr($html_code,$len+1,10)," "));
			$len+=strlen($tag)+strlen($codepieces[$i])+2;
			$dummy = eregi("[^>]*", $codepieces[$i], $reg);
			$attributes = $this->get_tag_attributes($reg[0]);	// Fetches the attributes for the tag
			$imageData=array();
			$imageData["ref"]=($attributes["src"]) ? $attributes["src"] : $attributes["background"];	// Finds the src or background attribute
			if ($imageData["ref"])	{
				$imageData["quotes"]=(substr($codepieces[$i],strpos($codepieces[$i], $imageData["ref"])-1,1)=='"')?'"':'';		// Finds out if the value had quotes around it
				$imageData["subst_str"] = $imageData["quotes"].$imageData["ref"].$imageData["quotes"];	// subst_str is the string to look for, when substituting lateron
				if ($imageData["ref"] && !strstr($this->image_fullpath_list,"|".$imageData["subst_str"]."|"))	{
					$this->image_fullpath_list.="|".$imageData["subst_str"]."|";
					$imageData["absRef"] = $this->absRef($imageData["ref"]);
					$imageData["tag"]=$tag;
					$imageData["use_jumpurl"]=$attributes["dmailerping"]?1:0;
					$this->theParts["html"]["media"][]=$imageData;
				}
			}
		}
			// Extracts stylesheets
		$attribRegex = $this->tag_regex(Array("link"));
		$codepieces = split($attribRegex, $html_code);	// Splits the document by the beginning of the above tags
		$pieces = count($codepieces);
		for($i=1; $i < $pieces; $i++)	{
			$dummy = eregi("[^>]*", $codepieces[$i], $reg);
			$attributes = $this->get_tag_attributes($reg[0]);	// Fetches the attributes for the tag
			$imageData=array();
			if (strtolower($attributes["rel"])=="stylesheet" && $attributes["href"])	{
				$imageData["ref"]=$attributes["href"];	// Finds the src or background attribute
				$imageData["quotes"]=(substr($codepieces[$i],strpos($codepieces[$i], $imageData["ref"])-1,1)=='"')?'"':'';		// Finds out if the value had quotes around it
				$imageData["subst_str"] = $imageData["quotes"].$imageData["ref"].$imageData["quotes"];	// subst_str is the string to look for, when substituting lateron
				if ($imageData["ref"] && !strstr($this->image_fullpath_list,"|".$imageData["subst_str"]."|"))	{
					$this->image_fullpath_list.="|".$imageData["subst_str"]."|";
					$imageData["absRef"] = $this->absRef($imageData["ref"]);
					$this->theParts["html"]["media"][]=$imageData;
				}
			}
		}
			// fixes javascript rollovers
		$codepieces = split(quotemeta(".src"), $html_code);
		$pieces = count($codepieces);
		$expr = "^[^".quotemeta("\"").quotemeta("'")."]*";
		for($i=1; $i < $pieces; $i++)	{
			$temp = $codepieces[$i];
			$temp = trim(ereg_replace("=","",trim($temp)));
			ereg ($expr,substr($temp,1,strlen($temp)),$reg);
			$imageData["ref"] = $reg[0];
			$imageData["quotes"] = substr($temp,0,1);
			$imageData["subst_str"] = $imageData["quotes"].$imageData["ref"].$imageData["quotes"];	// subst_str is the string to look for, when substituting lateron
			$theInfo = $this->split_fileref($imageData["ref"]);
			switch ($theInfo["fileext"])	{
				case "gif":
				case "jpeg":
				case "jpg":
					if ($imageData["ref"] && !strstr($this->image_fullpath_list,"|".$imageData["subst_str"]."|"))	{
						$this->image_fullpath_list.="|".$imageData["subst_str"]."|";
						$imageData["absRef"] = $this->absRef($imageData["ref"]);
						$this->theParts["html"]["media"][]=$imageData;
					}
				break;
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function extractHyperLinks()	{
			// extracts all hyper-links from $this->theParts["html"]["content"]
		$html_code = $this->theParts["html"]["content"];
		$attribRegex = $this->tag_regex(Array("a","form","area"));
		$codepieces = split($attribRegex, $html_code);	// Splits the document by the beginning of the above tags
		$len=strlen($codepieces[0]);
		$pieces = count($codepieces);
		for($i=1; $i < $pieces; $i++)	{
			$tag = strtolower(strtok(substr($html_code,$len+1,10)," "));
			$len+=strlen($tag)+strlen($codepieces[$i])+2;

			$dummy = eregi("[^>]*", $codepieces[$i], $reg);
			$attributes = $this->get_tag_attributes($reg[0]);	// Fetches the attributes for the tag
			$hrefData="";
			if ($attributes["href"]) {$hrefData["ref"]=$attributes["href"];} else {$hrefData["ref"]=$attributes["action"];}
			if ($hrefData["ref"])	{
				$hrefData["quotes"]=(substr($codepieces[$i],strpos($codepieces[$i], $hrefData["ref"])-1,1)=='"')?'"':'';		// Finds out if the value had quotes around it
				$hrefData["subst_str"] = $hrefData["quotes"].$hrefData["ref"].$hrefData["quotes"];	// subst_str is the string to look for, when substituting lateron
				if ($hrefData["ref"] && substr(trim($hrefData["ref"]),0,1)!="#" && !strstr($this->href_fullpath_list,"|".$hrefData["subst_str"]."|"))	{
					$this->href_fullpath_list.="|".$hrefData["subst_str"]."|";
					$hrefData["absRef"] = $this->absRef($hrefData["ref"]);
					$hrefData["tag"]=$tag;
					$this->theParts["html"]["hrefs"][]=$hrefData;
				}
			}
		}
			// Extracts TYPO3 specific links made by the openPic() JS function
		$codepieces = explode("onClick=\"openPic('", $html_code);
		$pieces = count($codepieces);
		for($i=1; $i < $pieces; $i++)	{
			$showpic_linkArr = explode("'",$codepieces[$i]);
			$hrefData["ref"]=$showpic_linkArr[0];
			if ($hrefData["ref"])	{
				$hrefData["quotes"]="'";		// Finds out if the value had quotes around it
				$hrefData["subst_str"] = $hrefData["quotes"].$hrefData["ref"].$hrefData["quotes"];	// subst_str is the string to look for, when substituting lateron
				if ($hrefData["ref"] && !strstr($this->href_fullpath_list,"|".$hrefData["subst_str"]."|"))	{
					$this->href_fullpath_list.="|".$hrefData["subst_str"]."|";
					$hrefData["absRef"] = $this->absRef($hrefData["ref"]);
					$this->theParts["html"]["hrefs"][]=$hrefData;
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function extractFramesInfo()	{
			// extracts all media-links from $this->theParts["html"]["content"]
		$html_code = $this->theParts["html"]["content"];
		if (strpos(" ".$html_code,"<frame "))	{
			$attribRegex = $this->tag_regex("frame");
			$codepieces = split($attribRegex, $html_code, 1000000 );	// Splits the document by the beginning of the above tags
			$pieces = count($codepieces);
			for($i=1; $i < $pieces; $i++)	{
				$dummy = eregi("[^>]*", $codepieces[$i], $reg);
				$attributes = $this->get_tag_attributes($reg[0]);	// Fetches the attributes for the tag
				$frame="";
				$frame["src"]=$attributes["src"];
				$frame["name"]=$attributes["name"];
				$frame["absRef"] = $this->absRef($frame["src"]);
				$theInfo[] = $frame;
			}
			return $theInfo;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$absolute: ...
	 * @return	[type]		...
	 */
	function substMediaNamesInHTML($absolute)	{
			// This substitutes the media-references in $this->theParts["html"]["content"]
			// If $absolute is true, then the refs are substituted with http:// ref's indstead of Content-ID's (cid).
		if (is_array($this->theParts["html"]["media"]))	{
			reset ($this->theParts["html"]["media"]);
			while (list($key,$val) = each ($this->theParts["html"]["media"]))	{
				if ($val["use_jumpurl"] && $this->jumperURL_prefix)	{
					$theSubstVal = $this->jumperURL_prefix.t3lib_div::rawUrlEncodeFP($val['absRef']);
				} else {
					$theSubstVal = ($absolute) ? $val["absRef"] : "cid:part".$key.".".$this->messageid;
				}
				$this->theParts["html"]["content"] = str_replace(
						$val["subst_str"],
						$val["quotes"].$theSubstVal.$val["quotes"],
						$this->theParts["html"]["content"]	);
			}
		}
		if (!$absolute)	{
			$this->fixRollOvers();
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function substHREFsInHTML()	{
			// This substitutes the hrefs in $this->theParts["html"]["content"]
		if (is_array($this->theParts["html"]["hrefs"]))	{
			reset ($this->theParts["html"]["hrefs"]);
			while (list($key,$val) = each ($this->theParts["html"]["hrefs"]))	{
				if ($this->jumperURL_prefix && $val["tag"]!="form")	{	// Form elements cannot use jumpurl!
					if ($this->jumperURL_useId)	{
						$theSubstVal = $this->jumperURL_prefix.$key;
					} else {
						$theSubstVal = $this->jumperURL_prefix.t3lib_div::rawUrlEncodeFP($val['absRef']);
					}
				} else {
					$theSubstVal = $val["absRef"];
				}
				$this->theParts["html"]["content"] = str_replace(
						$val["subst_str"],
						$val["quotes"].$theSubstVal.$val["quotes"],
						$this->theParts["html"]["content"]	);
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function substHTTPurlsInPlainText($content)	{
			// This substitutes the http:// urls in plain text with links
		if ($this->jumperURL_prefix)	{
			$textpieces = explode("http://", $content);
			$pieces = count($textpieces);
			$textstr = $textpieces[0];
			for($i=1; $i<$pieces; $i++)	{
				$len=strcspn($textpieces[$i],chr(32).chr(9).chr(13).chr(10));
				if (trim(substr($textstr,-1))=="" && $len)	{
					$lastChar=substr($textpieces[$i],$len-1,1);
					if (!ereg("[A-Za-z0-9\/#]",$lastChar)) {$len--;}		// Included "\/" 3/12

					$parts[0]="http://".substr($textpieces[$i],0,$len);
					$parts[1]=substr($textpieces[$i],$len);

					if ($this->jumperURL_useId)	{
						$this->theParts["plain"]["link_ids"][$i]=$parts[0];
						$parts[0] = $this->jumperURL_prefix."-".$i;
					} else {
						$parts[0] = $this->jumperURL_prefix.t3lib_div::rawUrlEncodeFP($parts[0]);
					}
					$textstr.=$parts[0].$parts[1];
				} else {
					$textstr.='http://'.$textpieces[$i];
				}
			}
			$content = $textstr;
		}
		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function fixRollOvers()	{
			// JavaScript rollOvers cannot support graphics inside of mail. If these exists we must let them refer to the absolute url. By the way: Roll-overs seems to work only on some mail-readers and so far I've seen it work on Netscape 4 message-center (but not 4.5!!)
		$theNewContent = "";
		$theSplit = explode(".src",$this->theParts["html"]["content"]);
		if (count($theSplit)>1)	{
			while(list($key,$part)=each($theSplit))	{
				$sub = substr($part,0,200);
				if (ereg("cid:part[^ \"']*",$sub,$reg))	{
					$thePos = strpos($part,$reg[0]);	// The position of the string
					ereg("cid:part([^\.]*).*",$sub,$reg2);		// Finds the id of the media...
	 				$theSubStr = $this->theParts["html"]["media"][intval($reg2[1])]["absRef"];
					if ($thePos && $theSubStr)	{		// ... and substitutes the javaScript rollover image with this instead
						if (!strpos(" ".$theSubStr,"http://")) {$theSubStr = "http://";}		// If the path is NOT and url, the reference is set to nothing
						$part = substr($part,0,$thePos).$theSubStr.substr($part,$thePos+strlen($reg[0]),strlen($part));
					}
				}
				$theNewContent.= $part.((($key+1)!=count($theSplit))? ".src" : ""  );
			}
			$this->theParts["html"]["content"]=$theNewContent;
		}
	}
















	/*******************************************
	 *
	 * File and URL-functions
	 *
	 *******************************************/

	/**
	 * @param	[type]		$inputstr: ...
	 * @return	[type]		...
	 */
	function makeBase64($inputstr)	{
			// Returns base64-encoded content, which is broken every 76 character
		return chunk_split(base64_encode($inputstr));
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$url: ...
	 * @return	[type]		...
	 */
	function getExtendedURL($url)	{
			// reads the URL or file and determines the Content-type by either guessing or opening a connection to the host
		$res["content"] = $this->getURL($url);
		if (!$res["content"])	{return false;}
		$pathInfo = parse_url($url);
		$fileInfo = $this->split_fileref($pathInfo["path"]);
		if ($fileInfo["fileext"] == "gif")	{$res["content_type"] = "image/gif";}
		if ($fileInfo["fileext"] == "jpg" || $fileInfo["fileext"] == "jpeg")	{$res["content_type"] = "image/jpeg";}
		if ($fileInfo['fileext'] == 'png') {$res['content_type'] = 'image/png';}
		if ($fileInfo["fileext"] == "html" || $fileInfo["fileext"] == "htm")	{$res["content_type"] = "text/html";}
		if ($fileInfo['fileext'] == 'css') {$res['content_type'] = 'text/css';}
		if ($fileInfo["fileext"] == "swf")	{$res["content_type"] = "application/x-shockwave-flash";}
		if (!$res["content_type"])	{$res["content_type"] = $this->getMimeType($url);}
		return $res;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$url: ...
	 * @return	[type]		...
	 */
	function addUserPass($url)	{
		$user=$this->http_username;
		$pass=$this->http_password;
		$matches = array();
		if ($user && $pass && preg_match('/^(https?:\/\/)/', $url, $matches)) {
			$url = $matches[1].$user.':'.$pass.'@'.substr($url,strlen($matches[1]));
		}
		return $url;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$url: ...
	 * @return	[type]		...
	 */
	function getURL($url)	{
		$url = $this->addUserPass($url);
			// reads a url or file
		return t3lib_div::getURL($url);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$url: ...
	 * @return	[type]		...
	 */
	function getStrippedURL($url)	{
			// reads a url or file and strips the HTML-tags AND removes all empty lines. This is used to read plain-text out of a HTML-page
		if($fd = fopen($url,"rb"))	{
			$content = "";
			while (!feof($fd))	{
				$line = fgetss($fd, 5000);
				if (trim($line))	{
					$content.=trim($line)."\n";
				}
			}
			fclose( $fd );
			return $content;
		}
	}

	/**
	 * This function returns the mime type of the file specified by the url
	 *
	 * @param	string		$url: the url
	 * @return	string		$mimeType: the mime type found in the header
	 */
	function getMimeType($url) {
		$mimeType = '';
		$headers = trim(t3lib_div::getURL($url, 2));
		if ($headers) {
			$matches = array();
			if (preg_match('/(Content-Type:[\s]*)([a-zA-Z_0-9\/\-\.\+]*)([\s]|$)/', $headers, $matches)) {
				$mimeType = trim($matches[2]);
			}
		}
		return $mimeType;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$ref: ...
	 * @return	[type]		...
	 */
	function absRef($ref)	{
			// Returns the absolute address of a link. This is based on $this->theParts["html"]["path"] being the root-address
		$ref = trim($ref);
		$urlINFO = parse_url($ref);
		if ($urlINFO["scheme"])	{
			return $ref;
		} elseif (eregi("^/",$ref)){
			$addr = parse_url($this->theParts["html"]["path"]);
			return  $addr['scheme'].'://'.$addr['host'].($addr['port']?':'.$addr['port']:'').$ref;
		} else {
			return $this->theParts["html"]["path"].$ref;	// If the reference is relative, the path is added, in order for us to fetch the content
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$fileref: ...
	 * @return	[type]		...
	 */
	function split_fileref($fileref)	{
			// Returns an array with path, filename, filebody, fileext.
		if (	ereg("(.*/)(.*)$",$fileref,$reg)	)	{
			$info["path"] = $reg[1];
			$info["file"] = $reg[2];
		} else {
			$info["path"] = "";
			$info["file"] = $fileref;
		}
		$reg="";
		if (	ereg("(.*)\.([^\.]*$)",$info["file"],$reg)	)	{
			$info["filebody"] = $reg[1];
			$info["fileext"] = strtolower($reg[2]);
			$info["realFileext"] = $reg[2];
		} else {
			$info["filebody"] = $info["file"];
			$info["fileext"] = "";
		}
		return $info;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$path: ...
	 * @return	[type]		...
	 */
	function extParseUrl($path)	{
			// Returns an array with file or url-information
		$res = parse_url($path);
		ereg("(.*/)([^/]*)$",$res["path"],$reg);
		$res["filepath"]=$reg[1];
		$res["filename"]=$reg[2];
		return $res;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$tagArray: ...
	 * @return	[type]		...
	 */
	function tag_regex($tagArray)	{
		if (!is_array($tagArray))	{
			$tagArray=Array($tagArray);
		}
		$theRegex = "";
		$c=count($tagArray);
		while(list(,$tag)=each($tagArray))	{
			$c--;
			$theRegex.="<".sql_regcase($tag)."[[:space:]]".(($c)?"|":"");
		}
		return $theRegex;
	}

	/**
	 * analyses a HTML-tag
	 * $tag is either like this "<TAG OPTION ATTRIB=VALUE>" or this " OPTION ATTRIB=VALUE>" which means you can omit the tag-name
	 * returns an array with the attributes as keys in lower-case
	 * If an attribute is empty (like OPTION) the value of that key is just empty. Check it with is_set();
	 *
	 * @param	[type]		$tag: ...
	 * @return	[type]		...
	 */
	function get_tag_attributes($tag)	{
		$attributes = Array();
		$tag = ltrim(eregi_replace ("^<[^ ]*","",trim($tag)));
		$tagLen = strlen($tag);
		$safetyCounter = 100;
			// Find attribute
		while ($tag)	{
			$value = "";
			$reg = split("[[:space:]=>]",$tag,2);
			$attrib = $reg[0];

			$tag = ltrim(substr($tag,strlen($attrib),$tagLen));
			if (substr($tag,0,1)=="=")	{
				$tag = ltrim(substr($tag,1,$tagLen));
				if (substr($tag,0,1)=='"')	{	// Quotes around the value
					$reg = explode('"',substr($tag,1,$tagLen),2);
					$tag = ltrim($reg[1]);
					$value = $reg[0];
				} else {	// No qoutes around value
					ereg("^([^[:space:]>]*)(.*)",$tag,$reg);
					$value = trim($reg[1]);
					$tag = ltrim($reg[2]);
					if (substr($tag,0,1)==">")	{
						$tag ="";
					}
				}
			}
			$attributes[strtolower($attrib)]=$value;
			$safetyCounter--;
			if ($safetyCounter<0)	{break;}
		}
		return $attributes;
	}

	/**
	 * Implementation of quoted-printable encode.
	 * This function was a duplicate of t3lib_div::quoted_printable, thus it's going to be removed.
	 *
	 * @param	string		Content to encode
	 * @return	string		The QP encoded string
	 * @obsolete
	 */
	function quoted_printable($string)	{
		return t3lib_div::quoted_printable($string, 76);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$name: ...
	 * @return	[type]		...
	 * @deprecated
	 */
	function convertName($name)	{
		return $name;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_htmlmail.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_htmlmail.php']);
}
?>
