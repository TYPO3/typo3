<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   69: class t3lib_formmail extends t3lib_htmlmail 
 *   95:     function start($V,$base64=1)	
 *  169:     function addAttachment($file, $filename)	
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */













/**
 * Formmail class
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see tslib_fe::sendFormmail(), t3lib/formmail.php
 */
class t3lib_formmail extends t3lib_htmlmail {
	var $reserved_names = 'recipient,recipient_copy,auto_respond_msg,redirect,subject,attachment,from_email,from_name,replyto_email,replyto_name,organisation,priority,html_enabled,quoted_printable,submit_x,submit_y';


	/**
	 * Start function
	 * This class is able to generate a mail in formmail-style from the data in $V
	 * Fields:
	 * 
	 * [recipient]:		email-adress of the one to receive the mail. If array, then all values are expected to be recipients
	 * [attachment]:		....
	 * 
	 * [subject]:			The subject of the mail
	 * [from_email]:		Sender email. If not set, [email] is used
	 * [from_name]:		Sender name. If not set, [name] is used
	 * [replyto_email]:	Reply-to email. If not set [from_email] is used
	 * [replyto_name]:		Reply-to name. If not set [from_name] is used
	 * [organisation]:		Organisation (header)
	 * [priority]:			Priority, 1-5, default 3
	 * [html_enabled]:		If mail is sent as html
	 * [quoted_printable]:	if set, quoted-printable will be used instead of base 64
	 * 
	 * @param	array		Contains values for the field names listed above
	 * @param	boolean		Whether to base64 encode the mail content
	 * @return	void		
	 */
	function start($V,$base64=1)	{
		if ($base64 && !$V['quoted_printable'])	{$this->useBase64();}
		if (is_array($V))	{
			t3lib_div::stripSlashesOnArray($V);
		}
		
		if (isset($V['recipient']))	{
				// Sets the message id
			$this->messageid = '<'.md5(microtime()).'@domain.tld>';
		
			$this->subject = ($V['subject']) ? $V['subject'] : 'Formmail on '.t3lib_div::getIndpEnv('HTTP_HOST');
			$this->from_email = ($V['from_email']) ? $V['from_email'] : (($V['email'])?$V['email']:'');
			$this->from_name = ($V['from_name']) ? $V['from_name'] : (($V['name'])?$V['name']:'');
			$this->replyto_email = ($V['replyto_email']) ? $V['replyto_email'] : $this->from_email;
			$this->replyto_name = ($V['replyto_name']) ? $V['replyto_name'] : $this->from_name;
			$this->organisation = ($V['organisation']) ? $V['organisation'] : '';
			$this->priority = ($V['priority']) ? t3lib_div::intInRange($V['priority'],1,5) : 3;

				// Auto responder.
			$this->auto_respond_msg = (trim($V['auto_respond_msg']) && $this->from_email) ? trim($V['auto_respond_msg']) : '';
			
			$Plain_content = '';
			$HTML_content = '<table border="0" cellpadding="2" cellspacing="2">';
			
				// Runs through $V and generates the mail
			if (is_array($V))	{
				reset($V);
				while (list($key,$val)=each($V))	{
					if (!t3lib_div::inList($this->reserved_names,$key))	{
 						$space = (strlen($val)>60)?chr(10):'';
						$val = (is_array($val) ? implode($val,chr(10)) : $val);
						$Plain_content.= strtoupper($key).':  '.$space.$val."\n".$space;
						$HTML_content.='<tr><td bgcolor="#eeeeee"><font face="Verdana" size="1"><b>'.strtoupper($key).'</b></font></td><td bgcolor="#eeeeee"><font face="Verdana" size="1">'.nl2br(HTMLSpecialChars($val)).'&nbsp</font></td></tr>';
					}
				}
			}
			$HTML_content.= '</table>';


			if ($V['html_enabled'])	{
				$this->setHTML($this->encodeMsg($HTML_content));
			}
			$this->addPlain($Plain_content);

			for ($a=0;$a<10;$a++)	{
				$varname = 'attachment'.(($a)?$a:'');
				$theFile = $GLOBALS['HTTP_POST_FILES'][$varname]['tmp_name'];
				$theName = $GLOBALS['HTTP_POST_FILES'][$varname]['name'];
				
				if ($theFile && @file_exists($theFile))	{
					if (filesize($theFile) < 250000)	{
						$this->addAttachment($theFile, $theName);
					}
//					unlink($theFile);
				}
			}
			
			$this->setHeaders();
			$this->setContent();
			$this->setRecipient($V['recipient']);
			if ($V['recipient_copy'])	{
				$this->recipient_copy = trim($V['recipient_copy']);
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
	function addAttachment($file, $filename)	{
		$content = $this->getURL($file);		// We fetch the content and the mime-type
		$fileInfo = $this->split_fileref($filename);
		if ($fileInfo['fileext'] == 'gif')	{$content_type = 'image/gif';}
		if ($fileInfo['fileext'] == 'bmp')	{$content_type = 'image/bmp';}
		if ($fileInfo['fileext'] == 'jpg' || $fileInfo['fileext'] == 'jpeg')	{$content_type = 'image/jpeg';}
		if ($fileInfo['fileext'] == 'html' || $fileInfo['fileext'] == 'htm')	{$content_type = 'text/html';}
		if (!$content_type) {$content_type = 'application/octet-stream';}
		
		if ($content)	{
			$theArr['content_type']= $content_type;
			$theArr['content']= $content;
			$theArr['filename']= $filename;
			$this->theParts['attach'][]=$theArr;
			return true;
		} else { return false;}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_formmail.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_formmail.php']);
}
?>