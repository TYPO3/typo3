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
 * products_mail.inc
 * 
 * Class for sending HTML-email order confirmations
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */


require_once (PATH_t3lib."class.t3lib_htmlmail.php");

class tt_products_htmlmail extends t3lib_htmlmail {
	
	function start($recipient,$subject,$plain,$html,$V)	{
		if ($recipient)	{
				// Sets the message id
			$this->messageid = md5(uniqid(""));
		
			$this->subject = $subject;
			
			$this->from_email = ($V["from_email"]) ? $V["from_email"] : (($V["email"])?$V["email"]:'');
			$this->from_name = ($V["from_name"]) ? $V["from_name"] : (($V["name"])?$V["name"]:'');
			$this->replyto_email = ($V["replyto_email"]) ? $V["replyto_email"] : $this->from_email;
			$this->replyto_name = ($V["replyto_name"]) ? $V["replyto_name"] : $this->from_name;
			$this->organisation = ($V["organisation"]) ? $V["organisation"] : '';
			$this->priority = ($V["priority"]) ? intInRange($V["priority"],1,5) : 3;

			if ($html)	{
				$this->theParts["html"]["content"] = $html;	// Fetches the content of the page
				$this->theParts["html"]["path"] = "";
				
				$this->extractMediaLinks();
				$this->extractHyperLinks();
				$this->fetchHTMLMedia();
				$this->substMediaNamesInHTML(0);	// 0 = relative
				$this->substHREFsInHTML();	
				$this->setHTML($this->encodeMsg($this->theParts["html"]["content"]));
			}
			$this->addPlain($plain);
	
			$this->setHeaders();
			$this->setContent();
			$this->setRecipient($recipient);
		}
	}	
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/products_mail.inc"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/products_mail.inc"]);
}

?>