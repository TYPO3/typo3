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
 * board_submit.inc
 * 
 * See TSref document: boardLib.inc / FEDATA section for details on how to use this script.
 * The static template 'plugin.tt_board' provides a working example of configuration.
 * 
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */
 

if (is_object($this))	{
	if (is_array($this->newData["tt_board"]["NEW"]))	{
			// Plain insert of record:
//		$this->newData["tt_board"]["NEW"]["datetime"]=time();
		$query = $this->getNEWinsert("tt_board", $this->newData["tt_board"]["NEW"]);
//debug($query);
		$res = mysql(TYPO3_db,$query);
		$newId = mysql_insert_id();
		echo mysql_error();
		$this->clear_cacheCmd(intval($this->newData["tt_board"]["NEW"]["pid"]));

		$conf = $this->getConf("tt_board");
			// Clear specific cache:
		if ($conf["clearCacheForPids"])	{
			$ccPids=t3lib_div::intExplode(",",$conf["clearCacheForPids"]);
			reset($ccPids);
			while(list(,$pid)=each($ccPids))	{
				if ($pid > 0)	{$this->clear_cacheCmd($pid);}
			}
		}
		
//debug($conf);

			// Send post to Mailing list ...
		if ($conf["sendToMailingList"] && $conf["sendToMailingList."]["email"])	{
/*
	TypoScript for this section (was used for the Typo3 mailing list.

    sendToMailingList=1
    sendToMailingList {
      email = typo3@netfielders.de
      reply = submitmail@typo3.com
      namePrefix = Typo3Forum/
      altSubject = Post from www.typo3.com
    }
*/
			$mConf = $conf["sendToMailingList."];
			$maillist_data = $this->newData["tt_board"]["NEW"];
			$maillist_recip = $mConf["email"];

			$maillist_header='From: '.$mConf["namePrefix"].$maillist_data["author"].' <'.$mConf["reply"].'>'.chr(10);
			$maillist_header.='Reply-To: '.$mConf["reply"];


				//  Subject
			if ($maillist_data["parent"])	{	// RE:
				$query = "SELECT * FROM tt_board WHERE uid=".intval($maillist_data["parent"]);
				$res = mysql(TYPO3_db,$query);
				$row = mysql_fetch_assoc($res);
				$maillist_subject = "Re: ".$row["subject"]." [#".$maillist_data["parent"]."]";
			} else {	// New:
				$maillist_subject =  (trim($maillist_data["subject"]) ? trim(stripslashes($maillist_data["subject"])) : $mConf["altSubject"])." [#".$newId."]";
			}
		
				// Message
			$maillist_msg = stripslashes($maillist_data["subject"]).chr(10).chr(10).stripslashes($maillist_data["message"])."
--
".$maillist_data["author"];
				// Send
				
			if ($conf["debug"])	{
				debug($maillist_recip,1);
				debug($maillist_subject,1);
				echo nl2br($maillist_msg.chr(10));
				debug($maillist_header,1);
			} else {
				mail ($maillist_recip, $maillist_subject, $maillist_msg, $maillist_header);
			}
		}
		
		
		// Notify me...
		if (t3lib_div::GPvar("notify_me") && $conf["notify"])	{
			$thescript = parse_url($GLOBALS["HTTP_SERVER_VARS"]["REQUEST_URI"]);
			$notifyMe = t3lib_div::uniqueList(str_replace(",".$maillist_data["email"].",", ",", ",".t3lib_div::GPvar("notify_me").","));
			
			$markersArray=array();
			$markersArray["###AUTHOR###"] = trim(stripslashes($this->newData["tt_board"]["NEW"][author]));
			$markersArray["###AUTHOR_EMAIL###"] = trim(stripslashes($this->newData["tt_board"]["NEW"][email]));
			$markersArray["###HOST###"] = t3lib_div::getIndpEnv("HTTP_HOST");
			$markersArray["###URL###"] = 'http://'.t3lib_div::getIndpEnv("HTTP_HOST").$thescript["path"].'?id='.$GLOBALS["TSFE"]->id.'&type='.$GLOBALS["TSFE"]->type.'&no_cache=1&tt_board_uid='.$newId;
			
			if ($maillist_data["parent"])	{		// If reply and not new thread:
				$msg = t3lib_div::getUrl($GLOBALS["TSFE"]->tmpl->getFileName($conf["newReply."]["msg"]));
				$markersArray["###DID_WHAT###"]= $conf["newReply."]["didWhat"];
				$markersArray["###SUBJECT_PREFIX###"]=$conf["newReply."]["subjectPrefix"];
			} else {	// If new thread:
				$msg = t3lib_div::getUrl($GLOBALS["TSFE"]->tmpl->getFileName($conf["newThread."]["msg"]));
				$markersArray["###DID_WHAT###"]= $conf["newThread."]["didWhat"];
				$markersArray["###SUBJECT_PREFIX###"]=$conf["newThread."]["subjectPrefix"];
			}
			$markersArray["###SUBJECT###"] = stripslashes(strtoupper($this->newData["tt_board"]["NEW"][subject]));
			$markersArray["###BODY###"] = t3lib_div::fixed_lgd(stripslashes($this->newData["tt_board"]["NEW"][message]),1000);
			
			reset($markersArray);
			while(list($marker,$markContent)=each($markersArray))	{
				$msg=str_replace($marker,$markContent,$msg);
			}

			$headers=array();
			if ($conf["notify_from"])	{$headers[]="FROM: ".$conf["notify_from"];}

			$msgParts = split(chr(10),$msg,2);
			if ($conf["debug"])	{
				debug($notifyMe,1);
				debug($headers,1);
				debug($msgParts);
			} else {
				mail ($notifyMe, $msgParts[0], $msgParts[1], implode($headers,chr(10)));
			}
		}
	}
}
		
?>	




