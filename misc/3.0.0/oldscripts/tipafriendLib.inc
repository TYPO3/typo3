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
 * tipafriendLib.inc
 * 
 * Creates a Tip-a-Friend form.
 * 
 * TypoScript config:
 * - See static_template "plugin.tipafriend"
 * - See TS_ref.pdf
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */

class user_tipafriend {
	var $cObj;		// The backReference to the mother cObj object set at call time

	/**
	 * Main news function.
	 * Call it from a USER cObject with 'userFunc = user_guest->main_tipafriend'
	 */
	function main_tipafriend($content,$conf)	{
		$this->conf = $conf;
		$this->config["code"] = $this->cObj->stdWrap($this->conf["code"],$this->conf["code."]);
		
			// template is read.
		$this->templateCode = $this->cObj->fileResource($this->conf["templateFile"]);

			// globally substituted markers, fonts and colors.	
		$splitMark = md5(microtime());
		$globalMarkerArray=array();
		list($globalMarkerArray["###GW1B###"],$globalMarkerArray["###GW1E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$conf["wrap1."]));
		list($globalMarkerArray["###GW2B###"],$globalMarkerArray["###GW2E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$conf["wrap2."]));
		$globalMarkerArray["###GC1###"] = $this->cObj->stdWrap($conf["color1"],$conf["color1."]);
		$globalMarkerArray["###GC2###"] = $this->cObj->stdWrap($conf["color2"],$conf["color2."]);
		$globalMarkerArray["###GC3###"] = $this->cObj->stdWrap($conf["color3"],$conf["color3."]);

			// Substitute Global Marker Array
		$this->templateCode= $this->cObj->substituteMarkerArray($this->templateCode, $globalMarkerArray);

			// TYpoLink
		$this->typolink_conf = $this->conf["typolink."];
		$this->typolink_conf["additionalParams"] = $this->cObj->stdWrap($this->typolink_conf["additionalParams"],$this->typolink_conf["additionalParams."]);
		unset($this->typolink_conf["additionalParams."]);

		$codes=t3lib_div::trimExplode(",", $this->config["code"]?$this->config["code"]:$this->conf["defaultCode"],1);
		if (!count($codes))	$codes=array("");
		while(list(,$theCode)=each($codes))	{
			$theCode = (string)strtoupper(trim($theCode));
			$this->theCode = $theCode;
			switch($theCode)	{
				case "TIPFORM":
					$content=$this->tipform();
				break;
				case "TIPLINK":
					$content=$this->tiplink();
				break;
				default:
					$langKey = strtoupper($GLOBALS["TSFE"]->config["config"]["language"]);
					$helpTemplate = $this->cObj->fileResource("media/scripts/tipafriend_help.tmpl");

						// Get language version
					$helpTemplate_lang="";
					if ($langKey)	{$helpTemplate_lang = $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_".$langKey."###");}
					$helpTemplate = $helpTemplate_lang ? $helpTemplate_lang : $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_DEFAULT###");

						// Markers and substitution:
					$markerArray["###CODE###"] = $this->theCode;
					$content.=$this->cObj->substituteMarkerArray($helpTemplate,$markerArray);
				break;
			}
		}
		return $content;
	}
	function tipform()	{
		$GLOBALS["TSFE"]->set_no_cache();

		$tipData = t3lib_div::slashArray(t3lib_div::GPvar("TIPFORM"),"strip");
		$tipData["recipient"] = $this->getRecipients($tipData["recipient"]);
		list($tipData["email"]) = explode(",",$this->getRecipients($tipData["email"]));
		$url = t3lib_div::GPvar("tipUrl");

			// Preparing markers
		$wrappedSubpartArray=array();
		$subpartArray=array();
		
		$markerArray=array();
		$markerArray["###FORM_URL###"]=t3lib_div::getIndpEnv("REQUEST_URI");
		$markerArray["###URL###"]=$url;
		$markerArray["###URL_ENCODED###"]=rawurlencode($url);
		$markerArray["###URL_SPECIALCHARS###"]=htmlspecialchars($url);
		
		$wrappedSubpartArray["###LINK###"]=array('<a href="'.$url.'">','</a>');

			// validation
		$error=0;
		$sent=0;
		if (t3lib_div::GPvar("sendTip"))	{
			if ($this->validate($tipData))	{
				$this->sendTip($tipData,$url);
				$sent=1;
			} else {
				$error=1;
			}
		}

			// Display form
		if ($sent)	{
			$subpart = $this->cObj->getSubpart($this->templateCode,"###TEMPLATE_TIPFORM_SENT###");

			$markerArray["###RECIPIENT###"]=htmlspecialchars($tipData["recipient"]);

			$content= $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,$wrappedSubpartArray);
		} else {
			$subpart = $this->cObj->getSubpart($this->templateCode,"###TEMPLATE_TIPFORM###");
	
			$markerArray["###MESSAGE###"]=htmlspecialchars($tipData["message"]);
			$markerArray["###RECIPIENT###"]=htmlspecialchars($tipData["recipient"]);
			$markerArray["###YOUR_EMAIL###"]=htmlspecialchars($tipData["email"]);
			$markerArray["###YOUR_NAME###"]=htmlspecialchars($tipData["name"]);
			$markerArray["###HTML_MESSAGE###"]=$tipData["html_message"] ? "checked" : "";
	
			if (!$error)	{
				$subpartArray["###ERROR_MSG###"]="";
			}
		
//			debug($markerArray);
	
				// Substitute
			$content= $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,$wrappedSubpartArray);
		}
		return $content;
	}
	function validate($tipData)	{
		if (	
			trim($tipData["name"]) &&
			$tipData["email"] &&
			$tipData["recipient"]
			) {
				return 1;
			}
	}
	function getRecipients($emails)	{
		$emailArr = split("[, ;]",$emails);
		reset($emailArr);
		$listArr=array();
		while(list(,$email)=each($emailArr))	{
			$email = trim($email);
			if ($email && t3lib_div::validEmail($email))	{
				$listArr[] = $email;
			}
		}
		return implode(",",$listArr);
	}
	function sendTip($tipData,$url)	{
			// Get template
		$subpart = $this->cObj->getSubpart($this->templateCode,"###TEMPLATE_EMAIL###");

			// Set markers
		$markerArray["###MESSAGE###"]=htmlspecialchars($tipData["message"]);
		$markerArray["###RECIPIENT###"]=htmlspecialchars($tipData["recipient"]);
		$markerArray["###YOUR_EMAIL###"]=htmlspecialchars($tipData["email"]);
		$markerArray["###YOUR_NAME###"]=htmlspecialchars($tipData["name"]);
		$markerArray["###URL###"]=$url;

			// Substitute in template
		$content= $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,$subpartArray,$wrappedSubpartArray);
		
			// Set subject, conten and headers
		$headers=array();
		$headers[]="FROM: ".$tipData["name"]." <".$tipData["email"].">";
		list($subject,$plain_message) = explode(chr(10),trim($content),2);
	

			// HTML
		$cls=t3lib_div::makeInstanceClassName("t3lib_htmlmail");

		if (class_exists($cls) && $this->conf["htmlmail"] && $tipData["html_message"])	{	// If htmlmail lib is included, then generate a nice HTML-email
			$Typo3_htmlmail = t3lib_div::makeInstance("t3lib_htmlmail");
			$Typo3_htmlmail->start();
			$Typo3_htmlmail->useBase64();

			$Typo3_htmlmail->subject = $subject;
			$Typo3_htmlmail->from_email = $tipData["email"];
			$Typo3_htmlmail->from_name = $tipData["name"];
			$Typo3_htmlmail->replyto_email = $tipData["email"];
			$Typo3_htmlmail->replyto_name = $tipData["name"];
			$Typo3_htmlmail->organisation = "";
			$Typo3_htmlmail->priority = 3;

	//		debug($url);
		
				// this will fail if the url is password protected!
			$Typo3_htmlmail->addHTML($url);
			$Typo3_htmlmail->addPlain($plain_message);
	
			$Typo3_htmlmail->setHeaders();
			$Typo3_htmlmail->setContent();
			$Typo3_htmlmail->setRecipient($tipData["recipient"]);

//			debug($Typo3_htmlmail->theParts);
			$Typo3_htmlmail->sendtheMail();
		} else { // Plain mail:
				// Sending mail:		
			mail ($tipData["recipient"], $subject, $plain_message, implode($headers,chr(10)));
		}
	}
	function tiplink()	{
		$url="http://".t3lib_div::getIndpEnv("HTTP_HOST").t3lib_div::getIndpEnv("REQUEST_URI");

		$subpart = $this->cObj->getSubpart($this->templateCode,"###TEMPLATE_TIPLINK###");
		
		$wrappedSubpartArray=array();
		$tConf = $this->typolink_conf;
		$tConf["additionalParams"].= "&tipUrl=".rawurlencode($url);
//		debug($tConf);
		$wrappedSubpartArray["###LINK###"]= $this->cObj->typolinkWrap($tConf);

		$markerArray=array();
		$markerArray["###URL###"]=$url;
		$markerArray["###URL_ENCODED###"]=rawurlencode($url);
		$markerArray["###URL_SPECIALCHARS###"]=htmlspecialchars($url);

			// Substitute
		$content= $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,array(),$wrappedSubpartArray);
		return $content;
	}
}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/tipafriendLib.inc"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/tipafriendLib.inc"]);
}


?>