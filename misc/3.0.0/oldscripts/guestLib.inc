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
 * guestLib.inc
 * 
 * Creates a guestbook/comment-list.
 * 
 * TypoScript config:
 * - See static_template "plugin.tt_guest"
 * - See TS_ref.pdf
 *
 * Other resources:
 * 'guest_submit.inc' is used for submission of the guest book entries to the database. This is done through the FEData TypoScript object. See the static_template 'plugin.tt_guest' for an example of how to set this up.
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */

class user_guest {
	var $cObj;		// The backReference to the mother cObj object set at call time

	var $enableFields ="";		// The enablefields of the tt_board table.
	var $dontParseContent=0;	
	
	var $orig_templateCode="";

	/**
	 * Main guestbook function.
	 * Call it from a USER cObject with 'userFunc = user_guest->main_guestbook'
	 */
	function main_guestbook($content,$conf)	{
		$content="";
		
		// *************************************
		// *** getting configuration values:
		// *************************************
		$alternativeLayouts = intval($conf["alternatingLayouts"])>0 ? intval($conf["alternatingLayouts"]) : 2;
			
			// pid_list is the pid/list of pids from where to fetch the guest items.
		$config["pid_list"] = trim($this->cObj->stdWrap($conf["pid_list"],$conf["pid_list."]));
		$config["pid_list"] = $config["pid_list"] ? implode(t3lib_div::intExplode(",",$config["pid_list"]),",") : $GLOBALS["TSFE"]->id;
		list($pid) = explode(",",$config["pid_list"]);
	
			// "CODE" decides what is rendered:
		$config["code"] = $this->cObj->stdWrap($conf["code"],$conf["code."]);
	
			// template is read.
		$this->orig_templateCode = $this->cObj->fileResource($conf["templateFile"]);
	

			// globally substituted markers, fonts and colors.	
		$splitMark = md5(microtime());
		$globalMarkerArray=array();
		list($globalMarkerArray["###GW1B###"],$globalMarkerArray["###GW1E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$conf["wrap1."]));
		list($globalMarkerArray["###GW2B###"],$globalMarkerArray["###GW2E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$conf["wrap2."]));
		$globalMarkerArray["###GC1###"] = $this->cObj->stdWrap($conf["color1"],$conf["color1."]);
		$globalMarkerArray["###GC2###"] = $this->cObj->stdWrap($conf["color2"],$conf["color2."]);
		$globalMarkerArray["###GC3###"] = $this->cObj->stdWrap($conf["color3"],$conf["color3."]);

			// Substitute Global Marker Array
		$this->orig_templateCode= $this->cObj->substituteMarkerArray($this->orig_templateCode, $globalMarkerArray);

			// If the current record should be displayed.
		$config["displayCurrentRecord"] = $conf["displayCurrentRecord"];
		if ($config["displayCurrentRecord"])	{
			$config["code"]="GUESTBOOK";
		}
			

		// *************************************
		// *** doing the things...:
		// *************************************
		$this->init($this->cObj->enableFields("tt_guest"));
		$this->dontParseContent = $conf["dontParseContent"];
		$cObj =t3lib_div::makeInstance("tslib_cObj");	// Initiate new cObj, because we're loading the data-array
		
		$codes=t3lib_div::trimExplode(",", $config["code"]?$config["code"]:$conf["defaultCode"],1);
		if (!count($codes))	$codes=array("");
		while(list(,$theCode)=each($codes))	{
			$theCode = (string)strtoupper(trim($theCode));
			switch($theCode)	{
				case "POSTFORM":
					$lConf = $conf["postform."];
					$content.=$cObj->FORM($lConf);
				break;
				case "GUESTBOOK":
					$lConf=$conf;
				
					if (!$lConf["subpartMarker"])	{
						$lConf["subpartMarker"]="TEMPLATE_GUESTBOOK";
					}
	
						// Getting template subpart from file.
					$templateCode = $cObj->getSubpart($this->orig_templateCode, "###".$lConf["subpartMarker"]."###");
					if ($templateCode)	{
							// Getting the specific parts of the template
						$postHeader=$this->getLayouts($templateCode,$alternativeLayouts,"POST");

							// Fetching the guest book item(s) to display:
						if ($config["displayCurrentRecord"])	{
							$recentPosts=array();
							$recentPosts[] = $this->cObj->data;	
						} else {
							$recentPosts = $this->getItems($pid);
						}
							// Traverse the items and display them:
						reset($recentPosts);
						$c_post=0;
						$subpartContent="";
						while(list(,$recentPost)=each($recentPosts))	{
								// Passing data through stdWrap and into the markerArray
							$cObj->start($recentPost);		// Set this->data to the current record tt_guest record.
							$markerArray=array();
							$markerArray["###POST_TITLE###"] = $cObj->stdWrap($this->formatStr($recentPost["title"]), $conf["title_stdWrap."]);
							$markerArray["###POST_CONTENT###"] = $cObj->stdWrap($this->formatStr($recentPost["note"]), $conf["note_stdWrap."]);
							$markerArray["###POST_AUTHOR###"] = $cObj->stdWrap($this->formatStr($recentPost["cr_name"]), $conf["author_stdWrap."]);
							$markerArray["###POST_EMAIL###"] = $cObj->stdWrap($this->formatStr($recentPost["cr_email"]), $conf["email_stdWrap."]);
							$markerArray["###POST_WWW###"] = $cObj->stdWrap($this->formatStr($recentPost["www"]), $conf["www_stdWrap."]);
							$markerArray["###POST_DATE###"] = $cObj->stdWrap($recentPost["crdate"],$conf["date_stdWrap."]);
							$markerArray["###POST_TIME###"] = $cObj->stdWrap($recentPost["crdate"],$conf["time_stdWrap."]);
							$markerArray["###POST_AGE###"] = $cObj->stdWrap($recentPost["crdate"],$conf["age_stdWrap."]);
								// Substitute the markerArray in the proper template code (POST subparts, alternating)
							$out=$postHeader[$c_post%count($postHeader)];
							$c_post++;
							$subpartContent.=$cObj->substituteMarkerArrayCached($out,$markerArray);
						}
						
							// Total Substitution:
						if ($lConf["requireRecords"] && !count($recentPosts))	{
							$content.= "";
						} else {
							$content.= $cObj->substituteSubpart($templateCode,"###CONTENT###",$subpartContent) ;
						}
					} else {
						debug("No template code for the subpart maker ###".$lConf["subpartMarker"]."###"); 
					}
			
				break;
				default:
					$langKey = strtoupper($GLOBALS["TSFE"]->config["config"]["language"]);
					$helpTemplate = $this->cObj->fileResource("media/scripts/guest_help.tmpl");

						// Get language version
					$helpTemplate_lang="";
					if ($langKey)	{$helpTemplate_lang = $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_".$langKey."###");}
					$helpTemplate = $helpTemplate_lang ? $helpTemplate_lang : $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_DEFAULT###");

						// Markers and substitution:
					$markerArray["###CODE###"] = $theCode;
					$content.=$this->cObj->substituteMarkerArray($helpTemplate,$markerArray);
				break;
			}
		}
		return $content;
	}
	
	/**
	 * Main guestbook function.
	 * Call it from a USER cObject with 'userFunc = user_guest->guestbook'
	 */
	function init($enableFields)	{
		$this->enableFields = $enableFields;
	}

	/**
	 * Main guestbook function.
	 * Call it from a USER cObject with 'userFunc = user_guest->guestbook'
	 */
	function getLayouts($templateCode,$alternativeLayouts,$marker)	{
		$out=array();
		for($a=0;$a<$alternativeLayouts;$a++)	{
			$m= "###".$marker.($a?"_".$a:"")."###";
			if(strstr($templateCode,$m))	{
				$out[]=$GLOBALS["TSFE"]->cObj->getSubpart($templateCode, $m);
			} else {
				break;
			}
		}
		return $out;
	}

	/**
	 * Main guestbook function.
	 * Call it from a USER cObject with 'userFunc = user_guest->guestbook'
	 */
	function getItems($pid)	{
		$query = "SELECT * FROM tt_guest WHERE pid IN (".$pid.")".$this->enableFields." ORDER BY crdate DESC";
		$res = mysql(TYPO3_db,$query);
		$out=array();
		while($row = mysql_fetch_assoc($res))	{
			$out[]=$row;
		}
		return $out;
	}

	/**
	 * Main guestbook function.
	 * Call it from a USER cObject with 'userFunc = user_guest->guestbook'
	 */
	function formatStr($str)	{
		if (!$this->dontParseContent)	{
			return nl2br(htmlspecialchars($str));
		} else {
			return $str;
		}
	}
}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/guestLib.inc"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/guestLib.inc"]);
}

?>