<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001 René Fritz (r.fritz@colorcube.de)
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
 * pollLib.inc
 *
 * version 0.91
 *
 * Creates a poll object
 *
 * TypoScript config:
 * - See static_template "plugin.tt_poll"
 * - See TS_ref.pdf
 *
 * Other resources:
 * 'poll_submit.inc' is used for submission of the poll value to the database. This is done through the FEData TypoScript object. See the static_template 'plugin.tt_poll' for an example of how to set this up.
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 */

/***************************************************************
TODO

---
show message if user tried to vote twice
---
check double votes with IPs - are we paranoid?
---

****************************************************************/



class user_poll {

	var $cObj;		// The backReference to the mother cObj object set at call time

	var $cObj="";

	var $enableFields ="";		// The enablefields of the tt_poll table.

	var $config=array();
	var $conf=array();

	var $pollTable = "";
	var $pollTableUid = "";
	var $pollTablePid = "";

	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	function main_poll($content,$conf)	{

			// getting configuration values:

		$this->conf=$conf;

		$this->enableFields = $this->cObj->enableFields("tt_poll");

			// "CODE" decides what will be rendered:
		$this->config["code"] = $this->cObj->stdWrap($this->conf["code"],$this->conf["code."]);
		if (!$this->config["code"]) { $this->config["code"]="SHOWHELP"; } // dummy code to display help

			//  we decide if we are in another object or in content.shortcut
		$items=array();
		if ( $this->conf["pollTable"] ) {
			$this->pollTable = $this->conf["pollTable"];
			$this->pollTablePid = intval($this->cObj->stdWrap($this->conf["pollTablePid"],$this->conf["pollTablePid."]));
			$this->pollTablePid = $this->pollTablePid ? $this->pollTablePid : $GLOBALS["TSFE"]->id;
			$this->pollTableUid = intval($this->cObj->stdWrap($this->conf["pollTableUid"],$this->conf["pollTableUid."]));
			if (!$this->pollTableUid) {
				list (,$this->pollTableUid) = explode(":",$this->cObj->currentRecord);
			}
			$item = $this->getItempollLink($this->pollTable."_".$this->pollTableUid, $this->pollTablePid);
			if (is_array($item)) {
				$items[] = $item;
			}
		} else {
			$items[] = $this->cObj->data;
		}

		$afterBegin = $this->cObj->data["starttime"] ? ($this->cObj->data["starttime"] <= time()) : TRUE;
		$inProgress = ($afterBegin AND $this->cObj->data["endtime"]) ? (($this->cObj->data["starttime"] <= time()) AND (time() <=$this->cObj->data["endtime"])) : $afterBegin;
		$openEnd = ($afterBegin AND !$this->cObj->data["endtime"]);

		// $items[] as array is not neccessary but I didn't change it from previous code
		// maybe used later to create poll verview lists


			// *************************************
			// *** Let's go
			// *************************************

		$codes=t3lib_div::trimExplode(",", strtoupper($this->config["code"]),1);

		$content="";
		reset($items);
		while(list(,$item)=each($items))	{

			$answers = explode("\n",$item["answers"]);

				// look for a submitted user vote
			unset($submittedVoteKey);
			if($GLOBALS["HTTP_COOKIE_VARS"]["t3_tt_poll_voted_".$itema["uid"]]){
				$submittedVoteKey = $GLOBALS["HTTP_COOKIE_VARS"]["t3_tt_poll_voted_".$itema["uid"]];
			}else{
				$datakeys = explode(":",$GLOBALS["HTTP_POST_VARS"]["locationData"]);
				$submittedVoteKey = $GLOBALS["HTTP_POST_VARS"]["data"][$datakeys[1]][$datakeys[2]]["vote"];
			}
			$submittedVoteText = "";
			$submittedVote = "";
			$voteMsg = "";

				// searching for the vote text of the submitted vote
			if ( $submittedVoteKey ) {
				reset($answers);
				while(list(,$value)=each($answers))	{
					list(,$answer) = explode("|",$value);
					$answer=trim($answer);
					if ($submittedVoteKey == md5($answer)) {
						$submittedVoteText = $answer;
					}
				}
			}

			reset($codes);
			while(list(,$theCode)=each($codes))	{
				$theCode = (string)trim($theCode);
				switch($theCode)	{
					case "VOTEFORM":
						if ($inProgress) {
							$lConf = $this->conf["voteform."];

							list ($submitButton) = array_values($lConf["dataArray."]);

							unset($lConf["dataArray."]);
							$count = 10;
							reset($answers);
							while(list(,$value)=each($answers))	{
								list(,$answer) = explode("|",$value);
								$answer=trim($answer);
								$lConf["dataArray."][$count."."]["type"] = "*data[tt_poll][".$item["uid"]."][vote]=radio";
								$lConf["dataArray."][$count."."]["value"] = $answer."=".md5($answer);
								$count += 10;
							}
							$lConf["dataArray."]["9990."] = $submitButton;

							$lConf["dataArray."]["9998."] = array(
								"type" => "clearCachePid=hidden",
								"value" => $GLOBALS["TSFE"]->id
							);
							$target_id = intval($this->cObj->stdWrap($lConf["redirect"],$lConf["redirect."]));
							$target_id = $target_id ? $target_id : intval($this->cObj->stdWrap($lConf["type"],$lConf["type."]));
							if ($target_id AND ($target_id != $GLOBALS["TSFE"]->id)) {
								$lConf["dataArray."]["9999."] = array(
									"type" => "clearCacheTargetPid=hidden",
									"value" => $target_id
								);
							}
//debug($lConf);
							$content .= $this->cObj->FORM($lConf);
						}
					break;

					case "RESULT":
						if ($afterBegin) {
							$content .= $this->cObj->cObjGetSingle ($this->conf["resultObj"], $this->conf["resultObj."]);

							if (isset ($this->conf["resultItemObj."])) {
								$contentItems = "";
								$answers = explode("\n",$item["answers"]);
								reset($answers);
								while(list(,$value)=each($answers))	{
									if (trim($value)) {
										$lConf = $this->conf["resultItemObj."];
										list($votes,$answer) = explode("|",$value);
										$markContentArray = array();
										$markContentArray["###ITEMVOTES###"] = $votes;
										$markContentArray["###PERCENT###"] = $item["votes"]?(string)(round((double)($votes * 100.0 / (double)$item["votes"]),1)):0;
										$markContentArray["###ANSWER###"] = $answer;
										$markContentArray["###POLLFULLWIDTH###"] = $this->conf["pollOutputWidth"];
										$markContentArray["###POLLWIDTH###"] = $item["votes"]?(int)((double)$this->conf["pollOutputWidth"]*($votes / (double)$item["votes"])):0;
										$markContentArray["###POLLREMAINWIDTH###"] = (int)$markContentArray["###POLLFULLWIDTH###"] - (int)$markContentArray["###POLLWIDTH###"];
										$this->cObj->substituteMarkerInObject ($lConf, $markContentArray);
										$contentItems .= $this->cObj->cObjGetSingle ($this->conf["resultItemObj"], $lConf);
									}
								}
							}
						}
					break;

					case "SUBMITTEDVOTE":
						if (($inProgress OR $openEnd) AND $submittedVoteText AND $GLOBALS["no_cache"]) {
							$voteMsg = $this->cObj->cObjGetSingle ($conf["submittedVoteObj"], $conf["submittedVoteObj."]);
							$submittedVote = $submittedVoteText;
						}
					break;

					default:
						$langKey = strtoupper($GLOBALS["TSFE"]->config["config"]["language"]);
						$helpTemplate = $this->cObj->fileResource("media/scripts/poll_help.tmpl");

							// Get language version
						$helpTemplate_lang="";
						if ($langKey)	{$helpTemplate_lang = $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_".$langKey."###");}
						$helpTemplate = $helpTemplate_lang ? $helpTemplate_lang : $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_DEFAULT###");

							// Markers and substitution:
						$markerArray["###CODE###"] = ($theCode=="SHOWHELP") ? "" : $theCode;
						$content.=$this->cObj->substituteMarkerArray($helpTemplate,$markerArray);
					break;
				}
			}
		$markContentArray = array();
		$markContentArray["###RESULTITEMS###"] = $contentItems;
		$markContentArray["###TITLE###"] = $item["title"];
		$markContentArray["###QUESTION###"] = $item["question"];
		$markContentArray["###TOTALVOTES###"] = $item["votes"];
		$markContentArray["###VOTEMSG###"] = $voteMsg;
		$markContentArray["###SUBMITTEDVOTE###"] = $submittedVote;
		$markContentArray["###PROGRESSMSG###"] = "";
		if ($inProgress) {
			if (!$openEnd) {
				$markContentArray["###PROGRESSMSG###"] = $this->cObj->cObjGetSingle ($this->conf["inProgressObj"], $this->conf["inProgressObj."]);
			}
		} else {
			$markContentArray["###PROGRESSMSG###"] = $this->cObj->cObjGetSingle ($this->conf["finishedObj"], $this->conf["finishedObj."]);
		}
		$markContentArray["###STARTTIME###"] = $this->cObj->stdWrap($this->cObj->data["starttime"],$conf["date_stdWrap."]);
		$markContentArray["###ENDTIME###"] = $this->cObj->stdWrap($this->cObj->data["endtime"],$conf["date_stdWrap."]);
		$content = $this->cObj->substituteMarkerArray ($content, $markContentArray);
		}

		return $this->cObj->wrap($content,$conf["wrap."]);
	}



		// get a 'linked' poll record
	function getItemPollLink($recordlink, $pid)		{
		$query = "SELECT * FROM tt_poll WHERE recordlink = ('".$recordlink."') AND pid = (".$pid.")".$this->enableFields;
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		return mysql_fetch_assoc($res);
	}


}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/pollLib.inc"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/pollLib.inc"]);
}

?>
