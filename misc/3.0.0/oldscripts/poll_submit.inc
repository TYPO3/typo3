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
 *
 * poll_submit.inc
 *
 * version 0.91
 */



if (is_object($this))	{

	if (is_array($this->newData["tt_poll"]))	{


		list($uid,)=each($this->newData["tt_poll"]);

		$cookieName = "t3_tt_poll_voted_".$uid;

			// poll is allowed if cookie not set or the user is logged in the backend
		if (((!isset($GLOBALS["HTTP_COOKIE_VARS"][$cookieName])) OR ($GLOBALS["TSFE"]->beUserLogin)) && ($this->newData["tt_poll"][$uid]["vote"])) {


				// we need the old data to calc the new one
			$query = "SELECT * FROM tt_poll WHERE uid = (".$uid.")";
			$res = mysql(TYPO3_db,$query);
			$actPoll = mysql_fetch_assoc($res);

				// we got the data so let's start
			if (is_array ($actPoll)) {

					// calc the new data
				if ($submittedVote = $this->newData["tt_poll"][$uid]["vote"]) {
					$answers = explode("\n",$actPoll["answers"]);
					$newAnswers = array();
					$votesTotal = 0;
					reset($answers);
					while(list(,$value)=each($answers))	{
						list($votes,$answer) = explode("|",$value);
						$answer=trim($answer);
						$votes=intval($votes);
						if (md5($answer) == $submittedVote) {
							$votes++;
						}
						$newAnswers[] = $votes."|".$answer;
						$votesTotal += $votes;
					}

					$this->newData["tt_poll"][$uid]["votes"] = $votesTotal;
					$this->newData["tt_poll"][$uid]["answers"] = implode("\n",$newAnswers);

						// prepare for database update
					$fp=array();
					unset($this->newData["tt_poll"][$uid]["uid"]);
					unset($this->newData["tt_poll"][$uid]["pid"]);
					$this->newData["tt_poll"][$uid]["tstamp"]=time();

					while(list($f,$v)=each($this->newData["tt_poll"][$uid]))	{
						if (t3lib_div::inList("tstamp",$f) || isset($GLOBALS["TCA"]["tt_poll"]["columns"][$f]))	{
							$fp[] = $f . "='" . addslashes($v) . "'";
						}
					}
						// write to database
					$query = "UPDATE tt_poll SET " . implode($fp,", ") . " WHERE uid = (".$uid.")";
					$res = mysql(TYPO3_db,$query);
					echo mysql_error();

						// set a cookie for preventing double polls
					setcookie($cookieName,$submittedVote,time()+(3600*24*10)); // delete cookie after 10 days

						// clear the page cache
					if ( $id = t3lib_div::GPvar("clearCachePid") ) 	{
						$this->clear_cacheCmd(intval($id));
					}
					if ( $id = t3lib_div::GPvar("clearCacheTargetPid") ) 	{
						$this->clear_cacheCmd(intval($id));
					}
				}
			}
		} else {
			// user voted already - what to do?
		}
	}
}




?>