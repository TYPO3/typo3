<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * formmail.php
 *
 * This file is a substitute for a formmail.pl script from Matt's script-archive
 * You should utilize this script by creating a symlink to it like this:
 * ln -s t3lib/formmail.php formmail.php
 *
 * Reserved fields:
 *		[recipient]:		(required) email-adress of the one to receive the mail. If array, then all values are expected to be recipients
 *		[recipient_copy]:		(required) email-adress of the one to receive the mail (exact copy). If array, then all values are expected to be recipients
 *		[auto_respond_msg]	If set (and if from_email) this is a auto-responder message.
 *		[subject]:			The subject of the mail
 *		[from_email]:		Sender email. If not set, [email] is used
 *		[from_name]:		Sender name. If not set, [name] is used
 *		[replyto_email]:	Reply-to email. If not set [from_email] is used
 *		[replyto_name]:		Reply-to name. If not set [from_name] is used
 *		[organisation]:		Organisation (header)
 *		[priority]:			Priority, 1-5, default 3
 *		[html_enabled]:		If mail is sent as html
 *
 *
 *		[redirect]:			URL to redirect to.
 *
 *		all other fields:	Content....
 *		[attachment] + [attachment1-attachment10]:		....
 *
 *
 * The script is not used ANYWHERE in the TYPO3 system - it is a leftover from previous times.
 * The formmail functionality build-in in TYPO3
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
die();

define('PATH_t3lib', './');	// t3lib-lib path
require_once (PATH_t3lib.'class.t3lib_div.php');
require_once (PATH_t3lib.'class.t3lib_htmlmail.php');
require_once (PATH_t3lib.'class.t3lib_formmail.php');

$formmail = t3lib_div::makeInstance('t3lib_formmail');
$formmail->start($HTTP_GET_VARS);
$formmail->sendtheMail();

header ('Location: '.t3lib_div::locationHeaderUrl(t3lib_div::GPvar('redirect')));
?>