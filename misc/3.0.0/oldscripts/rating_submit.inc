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
 * rating_submit.inc
 *
 * version 0.92
 *
 * 'rating_submit.inc' is used for submission of the rating value to the database. This is done through the FEData TypoScript object. See the static_template 'plugin.tt_rating' for an example of how to set this up.
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 */


if (is_object($this))	{

	if (is_array($this->newData["tt_rating"]))	{
		list($uid,)=each($this->newData["tt_rating"]);

		$cookieName = "t3_tt_rating_rated_".$uid;

			// rating is allowed if cookie not set or the user is logged in the backend
		if (((!isset($GLOBALS["HTTP_COOKIE_VARS"][$cookieName])) OR ($GLOBALS["TSFE"]->beUserLogin)) && ($this->newData["tt_rating"][$uid]["rating"])) {

				// we need the old data to calc the new one
			$query = "SELECT * FROM tt_rating WHERE uid = (".$uid.")";
			$res = mysql(TYPO3_db,$query);
			$actRating = mysql_fetch_assoc($res);

				// we got the data so let's start
			if (is_array ($actRating)) {

					// this is a trick to get the configuration
					// we passed these config values like record fields
					// we need these values for calculation
				$ratingConfig["minRating"] = $this->newData["tt_rating"][$uid]["minRating"];
				unset ($this->newData["tt_rating"][$uid]["minRating"]);
				$ratingConfig["maxRating"] = $this->newData["tt_rating"][$uid]["maxRating"];
				unset ($this->newData["tt_rating"][$uid]["maxRating"]);
				$ratingConfig["ratingStatSteps"] = $this->newData["tt_rating"][$uid]["ratingStatSteps"];
				unset ($this->newData["tt_rating"][$uid]["ratingStatSteps"]);

					// calc the new data
				$submittedRating = max($ratingConfig["minRating"],min($this->newData["tt_rating"][$uid]["rating"],$ratingConfig["maxRating"]));
				if ($submittedRating) {
					$this->newData["tt_rating"][$uid]["rating"] = (($actRating["rating"] * $actRating["votes"])+ $submittedRating) / ++$actRating["votes"]; // calc the new rating
					$this->newData["tt_rating"][$uid]["rating"] = max ($this->newData["tt_rating"][$uid]["rating"] ,$ratingConfig["minRating"]); // check it - it's maybe better to do so
					$this->newData["tt_rating"][$uid]["rating"] = min ($this->newData["tt_rating"][$uid]["rating"] ,$ratingConfig["maxRating"]);
					$this->newData["tt_rating"][$uid]["rating"] = number_format ($this->newData["tt_rating"][$uid]["rating"] ,2 ,"." ,""); // set the number format to xxx.xx
					$this->newData["tt_rating"][$uid]["votes"] = $actRating["votes"];

					$GLOBALS["register"]["tt_rating"][$uid]["submittedRating"] = $submittedRating;
				
						// this makes a statistic how many times each rating is voted
					if ($actRating["votes"]) {
						$ratingStat = explode ("|", $actRating["ratingstat"]);
					} else {
						$ratingStat = "";
					}
					for ($i = 0; $i < $ratingConfig["ratingStatSteps"]; $i++) {
						$ratingStat[$i]=$ratingStat[$i]? $ratingStat[$i]: 0;
					}
					$a = ((double)($ratingConfig["maxRating"]-$ratingConfig["minRating"]+1)/$ratingConfig["ratingStatSteps"]);
					$b = (double)($submittedRating - $ratingConfig["minRating"]+1);
					$ratingStat[ (int)round ($b/$a)-1 ]++;
					$this->newData["tt_rating"][$uid]["ratingstat"] = implode("|",$ratingStat);

						// prepare for database update
					$fp=array();
					unset($this->newData["tt_rating"][$uid]["uid"]);
					$pid = $this->newData["tt_rating"][$uid]["pid"];
					unset($this->newData["tt_rating"][$uid]["pid"]);
					$this->newData["tt_rating"][$uid]["tstamp"]=time();

					while(list($f,$v)=each($this->newData["tt_rating"][$uid]))	{
						if (t3lib_div::inList("tstamp",$f) || isset($GLOBALS["TCA"]["tt_rating"]["columns"][$f]))	{
							$fp[] = $f . "='" . $v . "'";
						}
					}
						// write to database
					$query = "UPDATE tt_rating SET " . implode($fp,", ") . " WHERE uid = (".$uid.")";
					$res = mysql(TYPO3_db,$query);
					echo mysql_error();

						// set a cookie to prevent double ratings
					setcookie($cookieName,$submittedRating,time()+(3600*24*10)); // delete cookie after 10 days

						// clear the page cache
      			if ( $id = t3lib_div::GPvar("clearCachePid") ) 	{
						$this->clear_cacheCmd(intval($id));
					}
				}
			}
		} else {
				// user did already a rating - the display code have to know that he tried again
			$GLOBALS["register"]["tt_rating"][$uid]["triedRating"] = $this->newData["tt_rating"][$uid]["rating"];
		}
	}
}




?>