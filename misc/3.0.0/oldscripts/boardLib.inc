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
 * boardLib.inc
 * 
 * Creates a forum/board in tree or list style
 * 
 * TypoScript config:
 * - See static_template "plugin.tt_board_tree" and plugin.tt_board_list
 * - See TS_ref.pdf
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */

class user_board {
	var $cObj;		// The backReference to the mother cObj object set at call time

	var $enableFields ="";		// The enablefields of the tt_board table.
	var $dontParseContent=0;
	var $treeIcons=array(
		"joinBottom"=>"\\-",
		"join"=>"|-",
		"line"=>"|&nbsp;",
		"blank"=>"&nbsp;&nbsp;",
		"thread"=>"+",
		"end"=>"-"
	);
	var $searchFieldList="author,email,subject,message";

	var $emoticons = 1;
	var $emoticonsPath = "media/emoticons/";
	var $emoticonsTag = '<img src="{}" valign="bottom" hspace=4>';
	var $emoticonsSubst=array(
		":-)" => "smile.gif",
		";-)" => "wink.gif",
		":-D" => "veryhappy.gif",
		":-(" => "sad.gif"
	);
	
	var $alternativeLayouts="";
	var $allowCaching="";
	var $conf=array();
	var $config=array();
	var $tt_board_uid="";
	var $pid="";
	var $orig_templateCode="";
	var $typolink_conf=array();
	var $local_cObj="";


	/**
	 * Main board function. Call this from TypoScript
	 */
	function main_board($content,$conf)	{
		// *************************************
		// *** getting configuration values:
		// *************************************
		$this->conf = $conf;
		$this->tt_board_uid = intval(t3lib_div::GPvar("tt_board_uid"));
	
		$this->alternativeLayouts = intval($this->conf["alternatingLayouts"])>0 ? intval($this->conf["alternatingLayouts"]) : 2;
			
			// pid_list is the pid/list of pids from where to fetch the board items.
		$this->config["pid_list"] = trim($this->cObj->stdWrap($this->conf["pid_list"],$this->conf["pid_list."]));
		$this->config["pid_list"] = $this->config["pid_list"] ? implode(t3lib_div::intExplode(",",$this->config["pid_list"]),",") : $GLOBALS["TSFE"]->id;
		list($pid) = explode(",",$this->config["pid_list"]);
		$this->pid = $pid;
	
			// "CODE" decides what is rendered:
		$this->config["code"] = $this->cObj->stdWrap($this->conf["code"],$this->conf["code."]);
		$this->allowCaching = $this->conf["allowCaching"]?1:0;
		
			// If the current record should be displayed.
		$this->config["displayCurrentRecord"] = $this->conf["displayCurrentRecord"];
		if ($this->config["displayCurrentRecord"])	{
			$this->config["code"]="FORUM";
			$this->tt_board_uid=$this->cObj->data["uid"];
		}
	
	//	debug($this->config["code"]);
			// template is read.
		$this->orig_templateCode = $this->cObj->fileResource($this->conf["templateFile"]);


			// globally substituted markers, fonts and colors.	
		$splitMark = md5(microtime());
		$globalMarkerArray=array();
		list($globalMarkerArray["###GW1B###"],$globalMarkerArray["###GW1E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$conf["wrap1."]));
		list($globalMarkerArray["###GW2B###"],$globalMarkerArray["###GW2E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$conf["wrap2."]));
		list($globalMarkerArray["###GW3B###"],$globalMarkerArray["###GW3E###"]) = explode($splitMark,$this->cObj->stdWrap($splitMark,$conf["wrap3."]));
		$globalMarkerArray["###GC1###"] = $this->cObj->stdWrap($conf["color1"],$conf["color1."]);
		$globalMarkerArray["###GC2###"] = $this->cObj->stdWrap($conf["color2"],$conf["color2."]);
		$globalMarkerArray["###GC3###"] = $this->cObj->stdWrap($conf["color3"],$conf["color3."]);
		$globalMarkerArray["###GC4###"] = $this->cObj->stdWrap($conf["color4"],$conf["color4."]);

			// Substitute Global Marker Array
		$this->orig_templateCode= $this->cObj->substituteMarkerArray($this->orig_templateCode, $globalMarkerArray);

	
			// TypoLink.
		$this->typolink_conf = $this->conf["typolink."];
		$this->typolink_conf["parameter."]["current"] = 1;
		$this->typolink_conf["additionalParams"] = $this->cObj->stdWrap($this->typolink_conf["additionalParams"],$this->typolink_conf["additionalParams."]);
		unset($this->typolink_conf["additionalParams."]);
		

	
		// *************************************
		// *** doing the things...:
		// *************************************

		$this->enableFields = $this->cObj->enableFields("tt_board");
		$this->dontParseContent = $this->conf["dontParseContent"];

		$this->local_cObj =t3lib_div::makeInstance("tslib_cObj");		// Local cObj.
		
		$codes=t3lib_div::trimExplode(",", $this->config["code"]?$this->config["code"]:$this->conf["defaultCode"],1);
		if (!count($codes))	$codes=array("");
		while(list(,$theCode)=each($codes))	{
			$theCode = (string)strtoupper(trim($theCode));
			switch($theCode)	{
				case "LIST_CATEGORIES":
				case "LIST_FORUMS":
					$content.= $this->forum_list($theCode);
				break;
				case "POSTFORM":
				case "POSTFORM_REPLY":
				case "POSTFORM_THREAD":
					$content.= $this->forum_postform($theCode);
				break;
				case "FORUM":
				case "THREAD_TREE":
					$content.= $this->forum_forum($theCode);
				break;
				default:
					$langKey = strtoupper($GLOBALS["TSFE"]->config["config"]["language"]);
					$helpTemplate = $this->cObj->fileResource("media/scripts/board_help.tmpl");

						// Get language version
					$helpTemplate_lang="";
					if ($langKey)	{$helpTemplate_lang = $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_".$langKey."###");}
					$helpTemplate = $helpTemplate_lang ? $helpTemplate_lang : $this->cObj->getSubpart($helpTemplate,"###TEMPLATE_DEFAULT###");

						// Markers and substitution:
					$markerArray["###CODE###"] = $theCode;
					$content.=$this->cObj->substituteMarkerArray($helpTemplate,$markerArray);
				break;
			}		// Switch
		}
		return $content;
	}

	/**
	 * Creates a list of forums or categories depending on theCode
	 */
	function forum_list($theCode)	{
		if (!$this->tt_board_uid)	{
			$forumlist=0;		// set to true if this is a list of forums and not categories + forums
			if ($theCode=="LIST_CATEGORIES")	{
					// Config if categories are listed.
				$lConf=	$this->conf["list_categories."];
			} else {
				$forumlist=1;
					// Config if forums are listed.
				$lConf=	$this->conf["list_forums."];
				$lConf["noForums"] = 0;
			}
			$GLOBALS["TSFE"]->set_cache_timeout_default($lConf["cache_timeout"] ? intval($lConf["cache_timeout"]) : 60*5);
			$templateCode = $this->local_cObj->getSubpart($this->orig_templateCode, "###TEMPLATE_OVERVIEW###");

			if ($templateCode)	{
					// Getting the specific parts of the template
				$categoryHeader=$this->getLayouts($templateCode,$this->alternativeLayouts,"CATEGORY");
				$forumHeader=$this->getLayouts($templateCode,$this->alternativeLayouts,"FORUM");
				$postHeader=$this->getLayouts($templateCode,$this->alternativeLayouts,"POST");
				$subpartContent="";
				
					// Getting categories
				$categories = $this->getPagesInPage($this->config["pid_list"]);
				reset($categories);
				$c_cat=0;
				while(list(,$catData)=each($categories))	{
						// Getting forums in category
					if ($forumlist)	{
						$forums = $categories;
					} else {
						$forums = $this->getPagesInPage($catData["uid"]);
					}
					if (!$forumlist && count($categoryHeader))	{
							// Rendering category
						$out=$categoryHeader[$c_cat%count($categoryHeader)];
						$c_cat++;

						
						$this->local_cObj->start($catData);	
							
							// Clear
						$markerArray=array();
						$wrappedSubpartContentArray=array();

							// Markers
						$markerArray["###CATEGORY_TITLE###"]=$this->local_cObj->stdWrap($this->formatStr($catData["title"]), $lConf["title_stdWrap."]);
						$markerArray["###CATEGORY_DESCRIPTION###"]=$this->local_cObj->stdWrap($this->formatStr($catData["subtitle"]), $lConf["subtitle_stdWrap."]);
						$markerArray["###CATEGORY_FORUMNUMBER###"]=$this->local_cObj->stdWrap(count($forums), $lConf["count_stdWrap."]);

							// Link to the category (wrap)
						$this->local_cObj->setCurrentVal($catData["uid"]);
						$wrappedSubpartContentArray["###LINK###"]=$this->local_cObj->typolinkWrap($this->typolink_conf);

							// Substitute
						$subpartContent.=$this->local_cObj->substituteMarkerArrayCached($out,$markerArray,array(),$wrappedSubpartContentArray);
					}
					if (count($forumHeader) && !$lConf["noForums"])	{
							// Rendering forums
						reset($forums);
						$c_forum=0;
						while(list(,$forumData)=each($forums))	{
							$out=$forumHeader[$c_forum%count($forumHeader)];
							$c_forum++;
							
							$this->local_cObj->start($forumData);

								// Clear
							$markerArray=array();
							$wrappedSubpartContentArray=array();
							
								// Markers
							$markerArray["###FORUM_TITLE###"]=$this->local_cObj->stdWrap($this->formatStr($forumData["title"]), $lConf["forum_title_stdWrap."]);
							$markerArray["###FORUM_DESCRIPTION###"]=$this->local_cObj->stdWrap($this->formatStr($forumData["subtitle"]), $lConf["forum_description_stdWrap."]);
							$markerArray["###FORUM_POSTS###"]=$this->local_cObj->stdWrap($this->getNumPosts($forumData["uid"]), $lConf["forum_posts_stdWrap."]);
							$markerArray["###FORUM_THREADS###"]=$this->local_cObj->stdWrap($this->getNumThreads($forumData["uid"]), $lConf["forum_threads_stdWrap."]);

								// Link to the forum (wrap)
							$this->local_cObj->setCurrentVal($forumData["uid"]);
							$wrappedSubpartContentArray["###LINK###"]=$this->local_cObj->typolinkWrap($this->typolink_conf);

							
								// LAST POST:
							$lastPostInfo = $this->getLastPost($forumData["uid"]);
							$this->local_cObj->start($lastPostInfo);
							if ($lastPostInfo)	{
								$markerArray["###LAST_POST_AUTHOR###"] = $this->local_cObj->stdWrap($this->formatStr($lastPostInfo["author"]), $lConf["last_post_author_stdWrap."]);
								$markerArray["###LAST_POST_DATE###"] = $this->local_cObj->stdWrap($this->recentDate($lastPostInfo),$this->conf["date_stdWrap."]);
								$markerArray["###LAST_POST_TIME###"] = $this->local_cObj->stdWrap($this->recentDate($lastPostInfo),$this->conf["time_stdWrap."]);
								$markerArray["###LAST_POST_AGE###"] = $this->local_cObj->stdWrap($this->recentDate($lastPostInfo),$this->conf["age_stdWrap."]);
							} else {
								$markerArray["###LAST_POST_AUTHOR###"] = "";
								$markerArray["###LAST_POST_DATE###"] = "";
								$markerArray["###LAST_POST_TIME###"] = "";
								$markerArray["###LAST_POST_AGE###"] = "";
							}

								// Link to the last post
							$this->local_cObj->setCurrentVal($lastPostInfo["pid"]);
							$temp_conf=$this->typolink_conf;
							$temp_conf["additionalParams"].= "&tt_board_uid=".$lastPostInfo["uid"];
							$temp_conf["useCacheHash"]=$this->allowCaching;
							$temp_conf["no_cache"]=!$this->allowCaching;
							$wrappedSubpartContentArray["###LINK_LAST_POST###"]=$this->local_cObj->typolinkWrap($temp_conf);

								// Add result
							$subpartContent.=$this->local_cObj->substituteMarkerArrayCached($out,$markerArray,array(),$wrappedSubpartContentArray);
							
								// Rendering the most recent posts
							if (count($postHeader) && $lConf["numberOfRecentPosts"])	{
								$recentPosts = $this->getMostRecentPosts($forumData["uid"],intval($lConf["numberOfRecentPosts"]));
								reset($recentPosts);
								$c_post=0;
								while(list(,$recentPost)=each($recentPosts))	{
									$out=$postHeader[$c_post%count($postHeader)];
									$c_post++;
									$this->local_cObj->start($recentPost);
										
										// Clear:
									$markerArray=array();
									$wrappedSubpartContentArray=array();

										// markers:
									$markerArray["###POST_TITLE###"] = $this->local_cObj->stdWrap($this->formatStr($recentPost["subject"]), $lConf["post_title_stdWrap."]);
									$markerArray["###POST_CONTENT###"] = $this->substituteEmoticons($this->local_cObj->stdWrap($this->formatStr($recentPost["message"]), $lConf["post_content_stdWrap."]));	
									$markerArray["###POST_REPLIES###"] = $this->local_cObj->stdWrap($this->getNumReplies($recentPost["pid"],$recentPost["uid"]), $lConf["post_replies_stdWrap."]);
									$markerArray["###POST_AUTHOR###"] = $this->local_cObj->stdWrap($this->formatStr($recentPost["author"]), $lConf["post_author_stdWrap."]);
									$markerArray["###POST_DATE###"] = $this->local_cObj->stdWrap($this->recentDate($recentPost),$this->conf["date_stdWrap."]);
									$markerArray["###POST_TIME###"] = $this->local_cObj->stdWrap($this->recentDate($recentPost),$this->conf["time_stdWrap."]);
									$markerArray["###POST_AGE###"] = $this->local_cObj->stdWrap($this->recentDate($recentPost),$this->conf["age_stdWrap."]);

										// Link to the post:
									$this->local_cObj->setCurrentVal($recentPost["pid"]);
									$temp_conf=$this->typolink_conf;
									$temp_conf["additionalParams"].= "&tt_board_uid=".$recentPost["uid"];
									$temp_conf["useCacheHash"]=$this->allowCaching;
									$temp_conf["no_cache"]=!$this->allowCaching;
									$wrappedSubpartContentArray["###LINK###"]=$this->local_cObj->typolinkWrap($temp_conf);
									$subpartContent.=$this->local_cObj->substituteMarkerArrayCached($out,$markerArray,array(),$wrappedSubpartContentArray);

										// add result
									$subpartContent.=$out;
								}
							}
						}
					}
					if ($forumlist)	{
						break;
					}
				}
					// Substitution:
				$content.= $this->local_cObj->substituteSubpart($templateCode,"###CONTENT###",$subpartContent) ;
			} else {
				$content = $this->outMessage("No template code for ###TEMPLATE_OVERVIEW###");
			}
		}
		return $content;
	}

	/**
	 * Creates a post form for a forum
	 */
	function forum_postform($theCode)	{
		$parent=0;		// This is the parent item for the form. If this ends up being is set, then the form is a reply and not a new post.
		$nofity=array();
			// Find parent, if any
		if ($this->tt_board_uid)	{
			if ($this->conf["tree"])	{
				$parent=$this->tt_board_uid;
			} else {
				$parentR = $this->getRootParent($this->tt_board_uid);
				$parent = $parentR["uid"];
			}

			$rootParent = $this->getRootParent($parent);
			$wholeThread = $this->getSingleThread($rootParent["uid"],1);
			reset($wholeThread);
			while(list(,$recordP)=each($wholeThread))	{
				if ($recordP["notify_me"] && $recordP["email"])		{
					$notify[md5(trim(strtolower($recordP["email"])))] = trim($recordP["email"]);
				}
			}
		}

			// Get the render-code 
		$lConf = $this->conf["postform."];
		$modEmail = $this->conf["moderatorEmail"];
		if (!$parent && isset($this->conf["postform_newThread."]))	{
			$lConf = $this->conf["postform_newThread."] ? $this->conf["postform_newThread."] : $lConf;			// Special form for newThread posts...
			$modEmail = $this->conf["moderatorEmail_newThread"] ? $this->conf["moderatorEmail_newThread"] : $modEmail;
		}
		if ($modEmail)	{
			$modEmail = explode(",", $modEmail);
			while(list(,$modEmail_s)=each($modEmail))	{
				$notify[md5(trim(strtolower($modEmail_s)))] = trim($modEmail_s);
			}
		}
//debug($notify);
		if ($theCode=="POSTFORM" || ($theCode=="POSTFORM_REPLY" && $parent) || ($theCode=="POSTFORM_THREAD" && !$parent))	{
			$lConf["dataArray."]["9999."] = array(
				"type" => "*data[tt_board][NEW][parent]=hidden",
				"value" => $parent
			);
			$lConf["dataArray."]["9998."] = array(
				"type" => "*data[tt_board][NEW][pid]=hidden",
				"value" => $this->pid
			);
			$lConf["dataArray."]["9997."] = array(
				"type" => "tt_board_uid=hidden",
				"value" => $parent
			);
			if (count($notify))		{
				$lConf["dataArray."]["9997."] = array(
					"type" => "notify_me=hidden",
					"value" => htmlspecialchars(implode($notify,","))
				);
			}
//						debug($lConf);
			$content.=$this->local_cObj->FORM($lConf);
		}
		return $content;
	}

	/**
	 * Creates the forum display, including listing all items/a single item
	 */
	function forum_forum($theCode)	{
		if ($this->conf["iconCode"])	{
			$this->treeIcons["joinBottom"] = $this->local_cObj->stdWrap($this->conf["iconCode."]["joinBottom"],$this->conf["iconCode."]["joinBottom."]);
			$this->treeIcons["join"] = $this->local_cObj->stdWrap($this->conf["iconCode."]["join"],$this->conf["iconCode."]["join."]);
			$this->treeIcons["line"] = $this->local_cObj->stdWrap($this->conf["iconCode."]["line"],$this->conf["iconCode."]["line."]);
			$this->treeIcons["blank"] = $this->local_cObj->stdWrap($this->conf["iconCode."]["blank"],$this->conf["iconCode."]["blank."]);
			$this->treeIcons["thread"] = $this->local_cObj->stdWrap($this->conf["iconCode."]["thread"],$this->conf["iconCode."]["thread."]);
			$this->treeIcons["end"] = $this->local_cObj->stdWrap($this->conf["iconCode."]["end"],$this->conf["iconCode."]["end."]);
		}

		if ($this->tt_board_uid && $theCode!="THREAD_TREE")	{
			if (!$this->allowCaching)		$GLOBALS["TSFE"]->set_no_cache();		// MUST set no_cache as this displays single items and not a whole page....
			$lConf=	$this->conf["view_thread."];
			$templateCode = $this->local_cObj->getSubpart($this->orig_templateCode, "###TEMPLATE_THREAD###");

			if ($templateCode)	{
				$rootParent = $this->getRootParent($this->tt_board_uid);
				$wholeThread = $this->getSingleThread($rootParent["uid"],1);
				
				if ($lConf["single"])	{
					reset($wholeThread);
					while(list(,$recentP)=each($wholeThread))	{
						if ($recentP["uid"]==$this->tt_board_uid)	{
							$recentPosts[]=$recentP;
							break;
						}
					}
				} else {
					$recentPosts = $wholeThread;
				}
				$nextThread = $this->getThreadRoot($this->config["pid_list"],$rootParent);
				$prevThread = $this->getThreadRoot($this->config["pid_list"],$rootParent,"prev");

				$subpartContent="";

					// Clear
				$markerArray=array();
				$wrappedSubpartContentArray=array();
						

					// Getting the specific parts of the template
				$markerArray["###FORUM_TITLE###"] = $this->local_cObj->stdWrap($GLOBALS["TSFE"]->page["title"],$lConf["forum_title_stdWrap."]);

					// Link back to forum
				$this->local_cObj->setCurrentVal($this->pid);
				$wrappedSubpartContentArray["###LINK_BACK_TO_FORUM###"]=$this->local_cObj->typolinkWrap($this->typolink_conf);

					// Link to next thread
				$this->local_cObj->setCurrentVal($this->pid);
				$temp_conf=$this->typolink_conf;
				if (is_array($nextThread))	{
					$temp_conf["additionalParams"].= "&tt_board_uid=".$nextThread["uid"];
					$temp_conf["useCacheHash"]=$this->allowCaching;
					$temp_conf["no_cache"]=!$this->allowCaching;
				}
				$wrappedSubpartContentArray["###LINK_NEXT_THREAD###"]=$this->local_cObj->typolinkWrap($temp_conf);

					// Link to prev thread
				$this->local_cObj->setCurrentVal($this->pid);
				$temp_conf=$this->typolink_conf;
				if (is_array($prevThread))	{
					$temp_conf["additionalParams"].= "&tt_board_uid=".$prevThread["uid"];
					$temp_conf["useCacheHash"]=$this->allowCaching;
					$temp_conf["no_cache"]=!$this->allowCaching;
				}
				$wrappedSubpartContentArray["###LINK_PREV_THREAD###"]=$this->local_cObj->typolinkWrap($temp_conf);

					// Link to first !!
				$this->local_cObj->setCurrentVal($this->pid);
				$temp_conf=$this->typolink_conf;
				$temp_conf["additionalParams"].= "&tt_board_uid=".$rootParent["uid"];
				$temp_conf["useCacheHash"]=$this->allowCaching;
				$temp_conf["no_cache"]=!$this->allowCaching;
				$wrappedSubpartContentArray["###LINK_FIRST_POST###"]=$this->local_cObj->typolinkWrap($temp_conf);

					// Substitute:
				$templateCode=$this->local_cObj->substituteMarkerArrayCached($templateCode,$markerArray,array(),$wrappedSubpartContentArray);

					// Getting subpart for items:
				$postHeader=$this->getLayouts($templateCode,$this->alternativeLayouts,"POST");

				reset($recentPosts);
				$c_post=0;
				while(list(,$recentPost)=each($recentPosts))	{
					$out=$postHeader[$c_post%count($postHeader)];
					$c_post++;
					
						// Clear
					$markerArray=array();
					$wrappedSubpartContentArray=array();
						
						// Markers
					$markerArray["###POST_THREAD_CODE###"] = $this->local_cObj->stdWrap($recentPost["treeIcons"], $lConf["post_thread_code_stdWrap."]);
					$markerArray["###POST_TITLE###"] = $this->local_cObj->stdWrap($this->formatStr($recentPost["subject"]), $lConf["post_title_stdWrap."]);
					$markerArray["###POST_CONTENT###"] = $this->substituteEmoticons($this->local_cObj->stdWrap($this->formatStr($recentPost["message"]), $lConf["post_content_stdWrap."]));
					$markerArray["###POST_REPLIES###"] = $this->local_cObj->stdWrap($this->getNumReplies($recentPost["pid"],$recentPost["uid"]), $lConf["post_replies_stdWrap."]);
					$markerArray["###POST_AUTHOR###"] = $this->local_cObj->stdWrap($this->formatStr($recentPost["author"]), $lConf["post_author_stdWrap."]);
					$markerArray["###POST_AUTHOR_EMAIL###"] = $recentPost["email"];
					$markerArray["###POST_DATE###"] = $this->local_cObj->stdWrap($this->recentDate($recentPost),$this->conf["date_stdWrap."]);
					$markerArray["###POST_TIME###"] = $this->local_cObj->stdWrap($this->recentDate($recentPost),$this->conf["time_stdWrap."]);
					$markerArray["###POST_AGE###"] = $this->local_cObj->stdWrap($this->recentDate($recentPost),$this->conf["age_stdWrap."]);

						// Link to the post
					$this->local_cObj->setCurrentVal($recentPost["pid"]);
					$temp_conf=$this->typolink_conf;
					$temp_conf["additionalParams"].= "&tt_board_uid=".$recentPost["uid"];
					$temp_conf["useCacheHash"]=$this->allowCaching;
					$temp_conf["no_cache"]=!$this->allowCaching;
					$wrappedSubpartContentArray["###LINK###"]=$this->local_cObj->typolinkWrap($temp_conf);

						// Link to next thread
					$this->local_cObj->setCurrentVal($recentPost["pid"]);
					$temp_conf=$this->typolink_conf;
					$temp_conf["additionalParams"].= "&tt_board_uid=".($recentPost["nextUid"]?$recentPost["nextUid"]:$nextThread["uid"]);
					$temp_conf["useCacheHash"]=$this->allowCaching;
					$temp_conf["no_cache"]=!$this->allowCaching;
					$wrappedSubpartContentArray["###LINK_NEXT_POST###"]=$this->local_cObj->typolinkWrap($temp_conf);

						// Link to prev thread
					$this->local_cObj->setCurrentVal($recentPost["pid"]);
					$temp_conf=$this->typolink_conf;
					$temp_conf["additionalParams"].= "&tt_board_uid=".($recentPost["prevUid"]?$recentPost["prevUid"]:$prevThread["uid"]);
					$temp_conf["useCacheHash"]=$this->allowCaching;
					$temp_conf["no_cache"]=!$this->allowCaching;
					$wrappedSubpartContentArray["###LINK_PREV_POST###"]=$this->local_cObj->typolinkWrap($temp_conf);
					
						// Substitute:
					$subpartContent.=$this->local_cObj->substituteMarkerArrayCached($out,$markerArray,array(),$wrappedSubpartContentArray);
				}
					// Substitution:
				$content.= $this->local_cObj->substituteSubpart($templateCode,"###CONTENT###",$subpartContent);
			} else {
				debug("No template code for "); 
			}
		} else {
			if ($theCode=="THREAD_TREE")	{
				if (!$this->tt_board_uid)	{break;}
				$lConf=	$this->conf["thread_tree."];
			} else {
				$lConf=	$this->conf["list_threads."];
			}
		
			$templateCode = $this->local_cObj->getSubpart($this->orig_templateCode, "###TEMPLATE_FORUM###");

			if ($templateCode)	{
					// Getting the specific parts of the template
				$templateCode = $this->local_cObj->substituteMarker($templateCode,"###FORUM_TITLE###",$this->local_cObj->stdWrap($GLOBALS["TSFE"]->page["title"],$lConf["forum_title_stdWrap."]));
				$postHeader=$this->getLayouts($templateCode,$this->alternativeLayouts,"POST");
					// Template code used if tt_board_uid matches...
				$postHeader_active = $this->getLayouts($templateCode,1,"POST_ACTIVE");
				
				$subpartContent="";
				
				if ($theCode=="THREAD_TREE")	{
					$rootParent = $this->getRootParent($this->tt_board_uid);
					$recentPosts = $this->getSingleThread($rootParent["uid"],1);
				} else {
					$recentPosts = $this->getThreads($this->config["pid_list"],$this->conf["tree"], $lConf["thread_limit"]?$lConf["thread_limit"]:"50", t3lib_div::GPvar("tt_board_sword"));
				}
				reset($recentPosts);
				$c_post=0;
				while(list(,$recentPost)=each($recentPosts))	{
					$GLOBALS["TT"]->push("/Post/");
					$out=$postHeader[$c_post%count($postHeader)];
					if ($recentPost["uid"]==$this->tt_board_uid && $postHeader_active[0])	{
						$out = $postHeader_active[0];
					}
					$c_post++;
					
						// Clear
					$markerArray=array();
					$wrappedSubpartContentArray=array();

						// Markers
					$GLOBALS["TT"]->push("/postMarkers/");
					$markerArray["###POST_THREAD_CODE###"] = $this->local_cObj->stdWrap($recentPost["treeIcons"], $lConf["post_thread_code_stdWrap."]);
					$markerArray["###POST_TITLE###"] = $this->local_cObj->stdWrap($this->formatStr($recentPost["subject"]), $lConf["post_title_stdWrap."]);
					$markerArray["###POST_CONTENT###"] = $this->substituteEmoticons($this->local_cObj->stdWrap($this->formatStr($recentPost["message"]), $lConf["post_content_stdWrap."]));
					$markerArray["###POST_REPLIES###"] = $this->local_cObj->stdWrap($this->getNumReplies($recentPost["pid"],$recentPost["uid"]), $lConf["post_replies_stdWrap."]);
					$markerArray["###POST_AUTHOR###"] = $this->local_cObj->stdWrap($this->formatStr($recentPost["author"]), $lConf["post_author_stdWrap."]);
					$markerArray["###POST_DATE###"] = $this->local_cObj->stdWrap($this->recentDate($recentPost),$this->conf["date_stdWrap."]);
					$markerArray["###POST_TIME###"] = $this->local_cObj->stdWrap($this->recentDate($recentPost),$this->conf["time_stdWrap."]);
					$markerArray["###POST_AGE###"] = $this->local_cObj->stdWrap($this->recentDate($recentPost),$this->conf["age_stdWrap."]);

						// Link to the post
					$this->local_cObj->setCurrentVal($recentPost["pid"]);
					$temp_conf=$this->typolink_conf;
					$temp_conf["additionalParams"].= "&tt_board_uid=".$recentPost["uid"];
					$temp_conf["useCacheHash"]=$this->allowCaching;
					$temp_conf["no_cache"]=!$this->allowCaching;
					$wrappedSubpartContentArray["###LINK###"]=$this->local_cObj->typolinkWrap($temp_conf);
					$GLOBALS["TT"]->pull();

						// Last post processing:
					$GLOBALS["TT"]->push("/last post info/");
					$lastPostInfo = $this->getLastPostInThread($recentPost["pid"],$recentPost["uid"]);
					$GLOBALS["TT"]->pull();
					if (!$lastPostInfo)	$lastPostInfo=$recentPost;

					$GLOBALS["TT"]->push("/lastPostMarkers/");
					$markerArray["###LAST_POST_DATE###"]=$this->local_cObj->stdWrap($this->recentDate($lastPostInfo),$this->conf["date_stdWrap."]);
					$markerArray["###LAST_POST_TIME###"]=$this->local_cObj->stdWrap($this->recentDate($lastPostInfo),$this->conf["time_stdWrap."]);
					$markerArray["###LAST_POST_AGE###"]=$this->local_cObj->stdWrap($this->recentDate($lastPostInfo),$this->conf["age_stdWrap."]);
					$markerArray["###LAST_POST_AUTHOR###"]=$this->local_cObj->stdWrap($this->formatStr($lastPostInfo["author"]), $lConf["last_post_author_stdWrap."]);

						// Link to the last post
					$this->local_cObj->setCurrentVal($lastPostInfo["pid"]);
					$temp_conf=$this->typolink_conf;
					$temp_conf["additionalParams"].= "&tt_board_uid=".$lastPostInfo["uid"];
					$temp_conf["useCacheHash"]=$this->allowCaching;
					$temp_conf["no_cache"]=!$this->allowCaching;
					$wrappedSubpartContentArray["###LINK_LAST_POST###"]=$this->local_cObj->typolinkWrap($temp_conf);
					$GLOBALS["TT"]->pull();
					
						// Substitute:
					$subpartContent.=$this->local_cObj->substituteMarkerArrayCached($out,$markerArray,array(),$wrappedSubpartContentArray);
					$GLOBALS["TT"]->pull();
				}
					// Substitution:
				$markerArray=array();
				$subpartContentArray=array();
					// Fill in array
				$markerArray["###SEARCH_WORD###"]=$GLOBALS["TSFE"]->no_cache ? t3lib_div::GPvar("tt_board_sword") : "";		// Setting search words in field if cache is disabled.
					// Set FORM_URL
				$this->local_cObj->setCurrentVal($GLOBALS["TSFE"]->id);
				$temp_conf=$this->typolink_conf;
				$temp_conf["no_cache"]=1;
				$markerArray["###FORM_URL###"]=$this->local_cObj->typoLink_URL($temp_conf);
				
					// Substitute CONTENT-subpart
				$subpartContentArray["###CONTENT###"]=$subpartContent;
				$content.= $this->local_cObj->substituteMarkerArrayCached($templateCode,$markerArray,$subpartContentArray);
			} else {
				debug("No template code for "); 
			}
		}
		return $content;
	}
	
	/**
	 * Get a record tree of forum items
	 */
	function getRecordTree($theRows,$parent,$pid,$treeIcons="") {
		$query = "SELECT * FROM tt_board WHERE pid=".$pid." and parent=".$parent.$this->enableFields." ".$this->orderBy();
		$GLOBALS["TT"]->setTSselectQuery($query,"getRecordTree");
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		$c=0;
		$rc = mysql_num_rows($res);

		$theRows[count($theRows)-1]["treeIcons"].= mysql_num_rows($res) ? $this->treeIcons["thread"] : $this->treeIcons["end"];

		while($row = mysql_fetch_assoc($res))	{
			$c++;
			$row["treeIcons"] = $treeIcons.($rc==$c ? $this->treeIcons["joinBottom"] : $this->treeIcons["join"]);
				// prev/next item:
			$theRows[count($theRows)-1]["nextUid"] = $row["uid"];
			$row["prevUid"] = $theRows[count($theRows)-1]["uid"];
			
			$theRows[]=$row;
				// get the branch
			$theRows = $this->getRecordTree($theRows,$row["uid"],$row["pid"],$treeIcons.($rc==$c ? $this->treeIcons["blank"] : $this->treeIcons["line"]));
		}
		return $theRows;	
	}

	/**
	 * Get subpages
	 *
	 * This function returns an array a pagerecords from the page-uid's in the pid_list supplied. 
	 * Excludes pages, that would normally not enter a regular menu. That means hidden, timed or deleted pages + pages with another doktype than "standard" or "advanced"
	 */
	function getPagesInPage($pid_list)	{
		$thePids = t3lib_div::intExplode(",",$pid_list);
		$theMenu = array();
		while(list(,$p_uid)=each($thePids))	{
			$theMenu = array_merge($theMenu, $GLOBALS["TSFE"]->sys_page->getMenu($p_uid));
		}
			// Exclude pages not of doktype "Standard" or "Advanced"
		reset($theMenu);
		while(list($key,$data)=each($theMenu))	{
			if (!t3lib_div::inList("1,2,5",$data["doktype"]))	{unset($theMenu[$key]);} // All pages including pages 'not in menu'
		}
		return $theMenu;
	}

	/**
	 * Returns number of post in a forum.
	 */
	function getNumPosts($pid)	{
		$query = "SELECT count(*) FROM tt_board WHERE pid IN (".$pid.")".$this->enableFields;
		$GLOBALS["TT"]->setTSselectQuery($query,"getNumPosts");
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		$row = mysql_fetch_row($res);
		return $row[0];
	}

	/**
	 * Returns number of threads.
	 */
	function getNumThreads($pid)	{
		$query = "SELECT count(*) FROM tt_board WHERE pid IN (".$pid.") AND parent=0".$this->enableFields;
		$GLOBALS["TT"]->setTSselectQuery($query,"getNumThreads");
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		$row = mysql_fetch_row($res);
		return $row[0];
	}

	/**
	 * Returns number of replies.
	 */
	function getNumReplies($pid,$uid)	{
		$query = "SELECT count(*) FROM tt_board WHERE pid IN (".$pid.") AND parent=".$uid.$this->enableFields;
		$GLOBALS["TT"]->setTSselectQuery($query,"getNumReplies");
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		$row = mysql_fetch_row($res);
		return $row[0];
	}

	/**
	 * Returns last post.
	 */
	function getLastPost($pid)	{
		$query = "SELECT * FROM tt_board WHERE pid IN (".$pid.")".$this->enableFields.$this->orderBy("DESC")." LIMIT 1";
		$GLOBALS["TT"]->setTSselectQuery($query,"getLastPost");
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		$row = mysql_fetch_assoc($res);
		return $row;
	}

	/**
	 * Returns last post in thread.
	 */
	function getLastPostInThread($pid,$uid)	{
		$query = "SELECT * FROM tt_board WHERE pid IN (".$pid.") AND parent=".$uid.$this->enableFields.$this->orderBy("DESC")." LIMIT 1";
		$GLOBALS["TT"]->setTSselectQuery($query,"getLastPostInThread");
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		$row = mysql_fetch_assoc($res);
		return $row;
	}

	/**
	 * Most recent posts.
	 *
	 * Returns an array with records
	 */
	function getMostRecentPosts($pid,$number)	{
		$query = "SELECT * FROM tt_board WHERE pid IN (".$pid.")".$this->enableFields.$this->orderBy("DESC")." LIMIT ".$number;
		$GLOBALS["TT"]->setTSselectQuery($query,"getMostRecentPosts");
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		$out=array();
		while($row = mysql_fetch_assoc($res))	{
			$out[]=$row;
		}
		return $out;
	}

	/**
	 * Returns an array with threads
	 */
	function getThreads($pid,$decend=0,$limit=100,$searchWord)	{
		$out=array();
		if ($searchWord)	{
			$where=$this->cObj->searchWhere($searchWord,$this->searchFieldList);
			$query = "SELECT * FROM tt_board WHERE pid IN (".$pid.") ".$where.$this->enableFields.$this->orderBy("DESC")." limit ".intval($limit);
			$GLOBALS["TT"]->setTSselectQuery($query,"getThreads");
			$res = mysql(TYPO3_db,$query);
			$set=array();
			while($row = mysql_fetch_assoc($res))	{
				$rootRow = $this->getRootParent($row["uid"]);
				if (is_array($rootRow) && !isset($set[$rootRow["uid"]]))	{
					$set[$rootRow["uid"]]=1;
					$out[]=$rootRow;
					if ($decend)	{
						$out=$this->getRecordTree($out,$rootRow["uid"],$rootRow["pid"]);			
					}
				}
			}
		} else {
			$query = "SELECT * FROM tt_board WHERE pid IN (".$pid.") AND parent=0".$this->enableFields.$this->orderBy("DESC")." limit ".intval($limit);
			$GLOBALS["TT"]->setTSselectQuery($query,"getThreads: No search Word");
			$res = mysql(TYPO3_db,$query);
			while($row = mysql_fetch_assoc($res))	{
				$out[]=$row;
				if ($decend)	{
					$out=$this->getRecordTree($out,$row["uid"],$row["pid"]);			
				}
			}
		}
		return $out;
	}

	/**
	 * Returns records in a thread
	 */
	function getSingleThread($uid,$decend=0)	{
		$hash = md5($uid."|".$decend);
		if ($this->cache_thread[$hash])	{return $this->cache_thread[$hash]; debug("!");}
	
		$out=array();
		if ($uid)	{
			$query = "SELECT * FROM tt_board WHERE uid=".$uid.$this->enableFields;
			$GLOBALS["TT"]->setTSselectQuery($query,"getSingleThread");
			$res = mysql(TYPO3_db,$query);
			if	($row = mysql_fetch_assoc($res))	{
				$out[]=$row;
				if ($decend)	{
					$out=$this->getRecordTree($out,$row["uid"],$row["pid"]);			
				}
			}
		}
		return $out;
	}

	/**
	 * Get root parent of a tt_board record.
	 */
	function getRootParent($uid,$limit=20)	{
		$query = "SELECT * FROM tt_board WHERE uid=".$uid.$this->enableFields;
		$GLOBALS["TT"]->setTSselectQuery($query,"getRootParent");
		$res = mysql(TYPO3_db,$query);
		if($row=mysql_fetch_assoc($res))	{
			if ($limit>0)	{
				if ($row["parent"])	{
					return $this->getRootParent($row["parent"],$limit-1);
				} else {
					return $row;
				}
			}
		}
	}
	
	/**
	 * Returns next or prev thread in a tree
	 */
	function getThreadRoot($pid,$rootParent,$type="next")	{
		$datePart = " and crdate".($type!="next"?">":"<").intval($rootParent["crdate"]);
		$query = "SELECT * FROM tt_board WHERE pid IN (".$pid.") AND parent=0".$datePart.$this->enableFields.$this->orderBy($type!="next"?"":"DESC");
		$GLOBALS["TT"]->setTSselectQuery($query,"getThreadRoot");
//		debug($query);
		$res = mysql(TYPO3_db,$query);
		return mysql_fetch_assoc($res);
	}

	/**
	 * Format string with nl2br and htmlspecialchars()
	 */
	function formatStr($str)	{
		if (!$this->dontParseContent)	{
			return nl2br(htmlspecialchars($str));
		} else {
			return $str;
		}
	}

	/**
	 * Emoticons substitution
	 */
	function substituteEmoticons($str)	{	
		if ($this->emoticons)	{
			reset($this->emoticonsSubst);
			while(list($source,$dest)=each($this->emoticonsSubst))	{
				$str = str_replace($source, str_replace("{}", $this->emoticonsPath.$dest, $this->emoticonsTag), $str);
			}
		}
		return $str;
	}

	/**
	 * Returns alternating layouts
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
	 * Returns a message, formatted
	 */
	function outMessage($string,$content="")	{
		$msg= '
		<HR>
		<h3>'.$string.'</h3>
		'.$content.'
		<HR>
		';
		
		return $msg;
	}
	
	/**
	 * Returns ORDER BY parts.
	 */
	function orderBy($desc="")	{
		return " ORDER BY crdate ".$desc;
//		return " ORDER BY datetime ".$desc.",crdate ".$desc;
	}

	/**
	 * Returns recent date from a tt_board record
	 */
	function recentDate($rec)	{
		return $rec["crdate"];
//		return $rec["datetime"] ? $rec["datetime"] : $rec["crdate"];
	}
}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/boardLib.inc"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["media/scripts/boardLib.inc"]);
}

?>