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
 * Contains a class for formmail
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   69: class t3lib_formmail extends t3lib_htmlmail
 *   95:	 function start($V,$base64=false)
 *  172:	 function addAttachment($file, $filename)
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Formmail class, used by the TYPO3 "cms" extension (default frontend) to send email forms.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see tslib_fe::sendFormmail(), t3lib/formmail.php
 */
class t3lib_formmail extends t3lib_htmlmail {
	protected $reserved_names = 'recipient,recipient_copy,auto_respond_msg,auto_respond_checksum,redirect,subject,attachment,from_email,from_name,replyto_email,replyto_name,organisation,priority,html_enabled,quoted_printable,submit_x,submit_y';
	var $dirtyHeaders = array(); // collection of suspicious header data, used for logging


	/**
	 * Start function
	 * This class is able to generate a mail in formmail-style from the data in $V
	 * Fields:
	 *
	 * [recipient]:			email-adress of the one to receive the mail. If array, then all values are expected to be recipients
	 * [attachment]:		....
	 *
	 * [subject]:			The subject of the mail
	 * [from_email]:		Sender email. If not set, [email] is used
	 * [from_name]:			Sender name. If not set, [name] is used
	 * [replyto_email]:		Reply-to email. If not set [from_email] is used
	 * [replyto_name]:		Reply-to name. If not set [from_name] is used
	 * [organisation]:		Organization (header)
	 * [priority]:			Priority, 1-5, default 3
	 * [html_enabled]:		If mail is sent as html
	 * [use_base64]:		If set, base64 encoding will be used instead of quoted-printable
	 *
	 * @param	array		Contains values for the field names listed above (with slashes removed if from POST input)
	 * @param	boolean		Whether to base64 encode the mail content
	 * @return	void
	 */
	function start($V, $base64 = false) {
		$convCharset = FALSE; // do we need to convert form data?

		if ($GLOBALS['TSFE']->config['config']['formMailCharset']) { // Respect formMailCharset if it was set
			$this->charset = $GLOBALS['TSFE']->csConvObj->parse_charset($GLOBALS['TSFE']->config['config']['formMailCharset']);
			$convCharset = TRUE;

		} elseif ($GLOBALS['TSFE']->metaCharset != $GLOBALS['TSFE']->renderCharset) { // Use metaCharset for mail if different from renderCharset
			$this->charset = $GLOBALS['TSFE']->metaCharset;
			$convCharset = TRUE;
		}

		parent::start();

		if ($base64 || $V['use_base64']) {
			$this->useBase64();
		}

		if (isset($V['recipient'])) {
				// convert form data from renderCharset to mail charset
			$val = ($V['subject']) ? $V['subject'] : 'Formmail on ' . t3lib_div::getIndpEnv('HTTP_HOST');
			$this->subject = ($convCharset && strlen($val)) ? $GLOBALS['TSFE']->csConvObj->conv($val, $GLOBALS['TSFE']->renderCharset, $this->charset) : $val;
			$this->subject = $this->sanitizeHeaderString($this->subject);
			$val = ($V['from_name']) ? $V['from_name'] : (($V['name']) ? $V['name'] : ''); // Be careful when changing $val! It is used again as the fallback value for replyto_name
			$this->from_name = ($convCharset && strlen($val)) ? $GLOBALS['TSFE']->csConvObj->conv($val, $GLOBALS['TSFE']->renderCharset, $this->charset) : $val;
			$this->from_name = $this->sanitizeHeaderString($this->from_name);
			$this->from_name = preg_match('/\s|,/', $this->from_name) >= 1 ? '"' . $this->from_name . '"' : $this->from_name;
			$val = ($V['replyto_name']) ? $V['replyto_name'] : $val;
			$this->replyto_name = ($convCharset && strlen($val)) ? $GLOBALS['TSFE']->csConvObj->conv($val, $GLOBALS['TSFE']->renderCharset, $this->charset) : $val;
			$this->replyto_name = $this->sanitizeHeaderString($this->replyto_name);
			$this->replyto_name = preg_match('/\s|,/', $this->replyto_name) >= 1 ? '"' . $this->replyto_name . '"' : $this->replyto_name;
			$val = ($V['organisation']) ? $V['organisation'] : '';
			$this->organisation = ($convCharset && strlen($val)) ? $GLOBALS['TSFE']->csConvObj->conv($val, $GLOBALS['TSFE']->renderCharset, $this->charset) : $val;
			$this->organisation = $this->sanitizeHeaderString($this->organisation);

			$this->from_email = ($V['from_email']) ? $V['from_email'] : (($V['email']) ? $V['email'] : '');
			$this->from_email = t3lib_div::validEmail($this->from_email) ? $this->from_email : '';
			$this->replyto_email = ($V['replyto_email']) ? $V['replyto_email'] : $this->from_email;
			$this->replyto_email = t3lib_div::validEmail($this->replyto_email) ? $this->replyto_email : '';
			$this->priority = ($V['priority']) ? t3lib_div::intInRange($V['priority'], 1, 5) : 3;

				// auto responder
			$this->auto_respond_msg = (trim($V['auto_respond_msg']) && $this->from_email) ? trim($V['auto_respond_msg']) : '';

			if ($this->auto_respond_msg !== '') {
					// Check if the value of the auto responder message has been modified with evil intentions
				$autoRespondChecksum = $V['auto_respond_checksum'];
				$correctHmacChecksum = t3lib_div::hmac($this->auto_respond_msg);
				if ($autoRespondChecksum !== $correctHmacChecksum) {
					t3lib_div::sysLog('Possible misuse of t3lib_formmail auto respond method. Subject: ' . $V['subject'], 'Core', 3);
					return;
				} else {
					$this->auto_respond_msg = $this->sanitizeHeaderString($this->auto_respond_msg);
				}
			}

			$Plain_content = '';
			$HTML_content = '<table border="0" cellpadding="2" cellspacing="2">';

				// Runs through $V and generates the mail
			if (is_array($V)) {
				foreach ($V as $key => $val) {
					if (!t3lib_div::inList($this->reserved_names, $key)) {
						$space = (strlen($val) > 60) ? LF : '';
						$val = (is_array($val) ? implode($val, LF) : $val);

							// convert form data from renderCharset to mail charset (HTML may use entities)
						$Plain_val = ($convCharset && strlen($val)) ? $GLOBALS['TSFE']->csConvObj->conv($val, $GLOBALS['TSFE']->renderCharset, $this->charset, 0) : $val;
						$HTML_val = ($convCharset && strlen($val)) ? $GLOBALS['TSFE']->csConvObj->conv(htmlspecialchars($val), $GLOBALS['TSFE']->renderCharset, $this->charset, 1) : htmlspecialchars($val);

						$Plain_content .= strtoupper($key) . ':  ' . $space . $Plain_val . LF . $space;
						$HTML_content .= '<tr><td bgcolor="#eeeeee"><font face="Verdana" size="1"><strong>' . strtoupper($key) . '</strong></font></td><td bgcolor="#eeeeee"><font face="Verdana" size="1">' . nl2br($HTML_val) . '&nbsp;</font></td></tr>';
					}
				}
			}
			$HTML_content .= '</table>';

			if ($V['html_enabled']) {
				$this->setHTML($this->encodeMsg($HTML_content));
			}
			$this->addPlain($Plain_content);

			for ($a = 0; $a < 10; $a++) {
				$varname = 'attachment' . (($a) ? $a : '');
				if (!isset($_FILES[$varname])) {
					continue;
				}
				if (!is_uploaded_file($_FILES[$varname]['tmp_name'])) {
					t3lib_div::sysLog('Possible abuse of t3lib_formmail: temporary file "' . $_FILES[$varname]['tmp_name'] . '" ("' . $_FILES[$varname]['name'] . '") was not an uploaded file.', 'Core', 3);
				}
				if ($_FILES[$varname]['tmp_name']['error'] !== UPLOAD_ERR_OK) {
					t3lib_div::sysLog('Error in uploaded file in t3lib_formmail: temporary file "' . $_FILES[$varname]['tmp_name'] . '" ("' . $_FILES[$varname]['name'] . '") Error code: ' . $_FILES[$varname]['tmp_name']['error'], 'Core', 3);
				}
				$theFile = t3lib_div::upload_to_tempfile($_FILES[$varname]['tmp_name']);
				$theName = $_FILES[$varname]['name'];

				if ($theFile && file_exists($theFile)) {
					if (filesize($theFile) < $GLOBALS['TYPO3_CONF_VARS']['FE']['formmailMaxAttachmentSize']) {
						$this->addAttachment($theFile, $theName);
					}
				}
				t3lib_div::unlink_tempfile($theFile);
			}

			$this->setHeaders();
			$this->setContent();
			$this->setRecipient($V['recipient']);
			if ($V['recipient_copy']) {
				$this->recipient_copy = trim($V['recipient_copy']);
			}
				// log dirty header lines
			if ($this->dirtyHeaders) {
				t3lib_div::sysLog('Possible misuse of t3lib_formmail: see TYPO3 devLog', 'Core', 3);
				if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']) {
					t3lib_div::devLog('t3lib_formmail: ' . t3lib_div::arrayToLogString($this->dirtyHeaders, '', 200), 'Core', 3);
				}
			}
		}
	}

	/**
	 * Adds an attachment to the mail
	 *
	 * @param	string		The absolute path to the file to add as attachment
	 * @param	string		The files original filename (not necessarily the same as the current since this could be uploaded files...)
	 * @return	boolean		True if the file existed and was added.
	 * @access private
	 */
	function addAttachment($file, $filename) {
		$content = $this->getURL($file); // We fetch the content and the mime-type
		$fileInfo = $this->split_fileref($filename);
		if ($fileInfo['fileext'] == 'gif') {
			$content_type = 'image/gif';
		}
		if ($fileInfo['fileext'] == 'bmp') {
			$content_type = 'image/bmp';
		}
		if ($fileInfo['fileext'] == 'jpg' || $fileInfo['fileext'] == 'jpeg') {
			$content_type = 'image/jpeg';
		}
		if ($fileInfo['fileext'] == 'html' || $fileInfo['fileext'] == 'htm') {
			$content_type = 'text/html';
		}
		if (!$content_type) {
			$content_type = 'application/octet-stream';
		}

		if ($content) {
			$theArr['content_type'] = $content_type;
			$theArr['content'] = $content;
			$theArr['filename'] = $filename;
			$this->theParts['attach'][] = $theArr;
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	 * Checks string for suspicious characters
	 *
	 * @param	string	String to check
	 * @return	string	Valid or empty string
	 */
	function sanitizeHeaderString($string) {
		$pattern = '/[\r\n\f\e]/';
		if (preg_match($pattern, $string) > 0) {
			$this->dirtyHeaders[] = $string;
			$string = '';
		}
		return $string;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_formmail.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_formmail.php']);
}

?>