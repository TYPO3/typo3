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
 * ratingLib.inc
 *
 * version 0.92
 *
 * Creates a rating object
 *
 * TypoScript config:
 * - See static_template "plugin.tt_rating"
 * - See TS_ref.pdf
 *
 * Other resources:
 * 'rating_submit.inc' is used for submission of the rating value to the database. This is done through the FEData TypoScript object. See the static_template 'plugin.tt_rating' for an example of how to set this up.
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 */

/***************************************************************
TODO

> The rating idea is quite good - I'd like to see it extended to be a survey tool too if possible.. it
>is often useful to see a little bargraph of the proportions of responses... rather than just the mean,
>or a single figure answer. Of course you'll need to keep a total for each of the responses... but this
>could realistically be limited to 10 at most.. perhaps less. The same information can be used to
>generate either a mini bar chart - or a single figure overview.. perhaps a single bar chart..

stats are written but there's no output yet
---
check double ratings with IPs - are we paranoid?
---
.ratingOutputSteps - Usefull if you don't want bars with values like 4.26 but 4.5
---
sorting in list mode ???

****************************************************************/



class user_rating {

	var $cObj;		// The backReference to the mother cObj object set at call time

	var $cObj="";

	var $enableFields ="";		// The enablefields of the tt_rating table.

	var $config=array();
	var $conf=array();

	var $rateTable = "";
	var $rateTableUid = "";
	var $rateTableTitle = "";

	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	function main_rating($content,$conf)	{

			// getting configuration values:

		$this->conf=$conf;

		$this->enableFields = $this->cObj->enableFields("tt_rating");

		if ($this->conf["pid_list"] OR $this->conf["pid_list."]) {
			$this->config["pid_list"] = trim($this->cObj->stdWrap($this->conf["pid_list"],$this->conf["pid_list."]));
			$this->config["pid_list"] = $this->config["pid_list"] ? implode(t3lib_div::intExplode(",",$this->config["pid_list"]),",") : $GLOBALS["TSFE"]->page[uid];
			list($pid) = explode(",",$this->config["pid_list"]);
		}

			// "CODE" decides what will be rendered:
		$this->config["code"] = $this->cObj->stdWrap($this->conf["code"],$this->conf["code."]);
		if (!$this->config["code"]) { $this->config["code"]=$this->conf["defaultCode"]; }
		if (!$this->config["code"]) { $this->config["code"]="SHOWHELP"; } // dummy code to display help

		$this->config["maxRating"] = max ($this->conf["highestRating"],$this->conf["lowestRating"]);
		$this->config["minRating"] = min ($this->conf["highestRating"],$this->conf["lowestRating"]);
		$this->config["defaultTitle"] = $this->cObj->stdWrap($this->conf["defaultTitle"],$this->conf["defaultTitle."]);
		$this->config["defaultDescription"] = $this->cObj->stdWrap($this->conf["defaultDescription"],$this->conf["defaultDescription."]);


			//  we decide if we are in content.list or content.shortcut

		$items=array();
		if (isset($pid) AND $this->cObj->data[CType]=="list") { // we are in tt_content.list
			$items = $this->getItemList($pid);
			if (!count($items) AND $this->conf["allowNew"]) { // there is no record so we create one if we are allowed to
				$items[] = $this->createNewItem($pid);
			}
		} elseif (isset($this->cObj->data[rating])) { // we have the data already - good
				$items[] = $this->cObj->data;
		} elseif ( $this->conf["rateTable"]  ) {
			$this->rateTable = $this->conf["rateTable"];
			$this->rateTableUid = intval($this->cObj->stdWrap($this->conf["rateTableUid"],$this->conf["rateTableUid."]));
			if (!$this->rateTableUid) {
				list (,$this->rateTableUid) = explode(":",$this->cObj->currentRecord);
			}
			$item = $this->getItempollLink($this->pollTable."_".$this->pollTableUid, $this->pollTablePid);
			$this->rateTableTitle = $this->cObj->data["title"] ? $this->cObj->data["title"] : "";
			$pid = intval($this->cObj->stdWrap($this->conf["rateTablePid"],$this->conf["rateTablePid."]));
			$pid = $pid ? $pid : $GLOBALS["TSFE"]->id;
			$item = $this->getItempollLink($this->pollTable."_".$this->pollTableUid, $pid);
			if (is_array($item)) {
				$items[] = $item;
			} elseif ($this->conf["allowNew"]) { // there is no record so we create one if we are allowed to
// I'm not sure if this would be good				$items[] = $this->createNewItem($pid, $this->pollTable."_".$this->pollTableUid);

			}
		} else {
			// no data - what to do?
			// should we output an error?
		}


			// *************************************
			// *** Let's go
			// *************************************

		$codes=t3lib_div::trimExplode(",", strtoupper($this->config["code"]),1);

		$content="";
		reset($items);
		while(list(,$item)=each($items))	{

				// check for double rating - is a cookie set, is a rating in register and are we not logged in as be user
			$cookieName = "t3_tt_rating_rated_".$item["uid"];
			$doubleRating = (isset($GLOBALS["HTTP_COOKIE_VARS"][$cookieName]) AND isset($GLOBALS["register"]["tt_rating"][$item["uid"]]["triedRating"]));
			$doubleRating =  $GLOBALS["TSFE"]->beUserLogin ? FALSE : $doubleRating;
//debug($GLOBALS["HTTP_COOKIE_VARS"]);
//debug($GLOBALS["register"]["tt_rating"][$item["uid"]]["submittedRating"]);
//debug($GLOBALS["register"]["tt_rating"][$item["uid"]]["triedRating"]);
//debug($doubleRating);

			reset($codes);
			while(list(,$theCode)=each($codes))	{
				$theCode = (string)trim($theCode);
				switch($theCode)	{
					case "VOTEFORM":
						$lConf = $this->conf["voteform."];
							//we have to do it this way because [EDIT] is set also in the typoscript template
						if (is_array($lConf["dataArray."]))	{
							reset($lConf["dataArray."]);
							while(list($key,$formEntry)=each($lConf["dataArray."]))	{
								$lConf["dataArray."][$key]["type"] = str_replace ( "[EDIT]", '['.$item["uid"].']', $formEntry["type"]);
							}

							$lConf["dataArray."]["9990."] = array(
								"type" => "data[tt_rating][".$item["uid"]."][maxRating]=hidden",
								"value" => $this->config["maxRating"]
							);
							$lConf["dataArray."]["9991."] = array(
								"type" => "data[tt_rating][".$item["uid"]."][minRating]=hidden",
								"value" => $this->config["minRating"]
							);
							$lConf["dataArray."]["9992."] = array(
								"type" => "data[tt_rating][".$item["uid"]."][ratingStatSteps]=hidden",
								"value" => $this->conf["ratingStatSteps"]
							);
							$lConf["dataArray."]["9993."] = array(
								"type" => "clearCachePid=hidden",
								"value" => $GLOBALS["TSFE"]->id
							);
						} else {
							$lConf["data"] = str_replace ( "[EDIT]", '['.$item["uid"].']', $lConf["data"]);
							$lConf["data"] .= " ||  | data[tt_rating][".$item["uid"]."][maxRating]=hidden | ".$this->config["maxRating"];
							$lConf["data"] .= " ||  | data[tt_rating][".$item["uid"]."][minRating]=hidden | ".$this->config["minRating"];
							$lConf["data"] .= " ||  | data[tt_rating][".$item["uid"]."][ratingStatSteps]=hidden | ".$this->conf["ratingStatSteps"];
							$lConf["data"] .= " ||  | clearCachePid=hidden | ".$GLOBALS["TSFE"]->id;
						}

						$content .= $this->cObj->FORM($lConf);

					break;
					case "RESULT":
						if ($item["votes"] == 0) {
							if (isset ($this->conf["noRatingObj."])) {
								$lConf = $this->conf["noRatingObj."];
								$content .= $this->cObj->cObjGetSingle ($this->conf["noRatingObj"], $lConf);
							}
						} else {
							if (isset ($this->conf["renderObj."])) {
								$lConf = $this->conf["renderObj."];
								$markContentArray = array();
								$markContentArray["###VOTES###"] = $item["votes"];
								$markContentArray["###RATING###"] = $item["rating"];
								$markContentArray["###HIGHEST_RATING###"] = $this->conf["highestRating"];
								$markContentArray["###LOWEST_RATING###"] = $this->conf["lowestRating"];
								$markContentArray["###RATING_OUTPUT_STEPS###"] = $this->conf["ratingOutputSteps"];
								$markContentArray["###RATING_MUL###"] = (string)((int)($item["rating"] * 100.0 / (double)$this->conf["highestRating"]))."/100";
								$markContentArray["###RATING_FULL_WIDTH###"] = $this->conf["ratingOutputWidth"];
								$markContentArray["###RATING_WIDTH###"] = (int)((double)$this->conf["ratingOutputWidth"]*($item["rating"] / (double)$this->conf["highestRating"]));
								$markContentArray["###RATING_REMAIN_WIDTH###"] = (int)$markContentArray["###RATING_FULL_WIDTH###"] - (int)$markContentArray["###RATING_WIDTH###"];
									
									// rating feedback messages
								if($doubleRating) {
									//double rating
									$markContentArray["###RATING_MSG###"] = $this->conf["doubleRatingMsg"];
									$GLOBALS["TSFE"]->set_no_cache();
								} elseif ($GLOBALS["register"]["tt_rating"][$item["uid"]]["submittedRating"]) {
									//submitted rating
									$GLOBALS["TSFE"]->set_no_cache();
									$markContentArray["###RATING_MSG###"] = $this->conf["submittedRatingMsg"];
									$markContentArray["###RATING_MSG###"] = str_replace("###SUBMITTED_RATING###", $GLOBALS["register"]["tt_rating"][$item["uid"]]["submittedRating"], $markContentArray["###RATING_MSG###"]);
								} else {
									$markContentArray["###RATING_MSG###"] = "";
								}
								
								$this->cObj->substituteMarkerInObject ($lConf, $markContentArray);
								$content .= $this->cObj->cObjGetSingle ($this->conf["renderObj"], $lConf);
							}
						}
					break;
					default:
						$langKey = strtoupper($GLOBALS["TSFE"]->config["config"]["language"]);
						$helpTemplate = $this->cObj->fileResource("media/scripts/rating_help.tmpl");

							// Get language version
						$helpTemplate_lang="";
						if ($langKey)	{$helpTemplate_lang = $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_".$langKey."###");}
						$helpTemplate = $helpTemplate_lang ? $helpTemplate_lang : $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_DEFAULT###");

							// Markers and substitution:
						$markerArray["###CODE###"] = $theCode;
						$markerArray["###DEFAULTCODE###"] = $this->conf["defaultCode"];
						$content.=$this->cObj->substituteMarkerArray($helpTemplate,$markerArray);
					break;
				}
			}
		}
		return $this->cObj->wrap($content, $this->conf["wrap"]);
	}



		// get a list of rating record from pid
	function getItemList($pid)	{
		if (is_array($pid)) {
			$pid = implode(",",$pid);
		}
		$query = "SELECT * FROM tt_rating WHERE pid IN (".$pid.")".$this->enableFields." ORDER BY crdate DESC";
		$res = mysql(TYPO3_db,$query);
		$out=array();
		while($row = mysql_fetch_assoc($res))	{
			$out[]=$row;
		}
		return $out;
	}

		// get a 'linked' rating record
	function getItemRatingLink($recordlink, $pid)		{
		$query = "SELECT * FROM tt_rating WHERE recordlink = ('".$recordlink."') AND pid = (".$pid.")".$this->enableFields;
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		return mysql_fetch_assoc($res);
	}


		// get a single rating record from uid
	function getItem($uid)		{
		$query = "SELECT * FROM tt_rating WHERE uid = (".$uid.")".$this->enableFields;
		$res = mysql(TYPO3_db,$query);
		return mysql_fetch_assoc($res);
	}

		// create a new rating record
	function createNewItem($pid=0)	{
		$pid = $pid ? $pid : $GLOBALS["TSFE"]->id;
		$recordLink = "";
		if ($this->rateTable AND $this->rateTableUid) {
			$recordLink = $this->rateTable .":". $this->rateTableUid;
		}
		if (!$this->config["defaultTitle"] AND $this->rateTable AND $this->rateTableUid) {
			if (!$this->rateTableTitle) {
				$query = "SELECT * FROM ".$this->rateTable." WHERE uid = (".$this->rateTableUid.")";
				$res = mysql(TYPO3_db,$query);
				$row = mysql_fetch_assoc($res);
				$this->rateTableTitle = $row[title] ? $row[title] : $recordLink;
			}
			$title = "rating: ".$this->rateTableTitle;
		} else {
			$title = $this->config["defaultTitle"] ? $this->config["defaultTitle"] : "rating: ".$GLOBALS["TSFE"]->page["title"];
		}
		$newData = array("pid"=>$pid,"recordlink"=>$recordLink,"title"=>$title,"description"=>$this->config["defaultDescription"],"rating"=>"0","votes"=>"0");
		$fp=array();
		$vp=array();
		unset($newData["uid"]);
		$newData["tstamp"]=time();
		$newData["crdate"]=time();

		while(list($f,$v)=each($newData))	{
			$fp[]=$f;
			$vp[]=$v;
		}
		$query = "INSERT INTO tt_rating (".implode($fp,",").") VALUES ('".implode($vp,"','")."')";

		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		return $this->getItem(mysql_insert_id());
	}


}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/ratingLib.inc"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/ratingLib.inc"]);
}

?>
