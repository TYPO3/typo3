<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 * Index search frontend
 *
 * $Id$
 *
 * Creates a searchform for indexed search. Indexing must be enabled
 * for this to make sense.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @co-author	Christian Jul Jensen <christian@typo3.com>
 */




require_once(PATH_tslib."class.tslib_pibase.php");
require_once(PATH_tslib."class.tslib_search.php");
require_once(t3lib_extMgm::extPath("indexed_search")."class.indexer.php");

class tx_indexedsearch extends tslib_pibase {
    var $prefixId = "tx_indexedsearch";        // Same as class name
    var $scriptRelPath = "pi/class.tx_indexedsearch.php";    // Path to this script relative to the extension dir.
    var $extKey = "indexed_search";    // The extension key.
	var $join_pages=0;	// See document for info about this flag...

	var $defaultResultNumber=20;
	var $wholeSiteIdList = 0;

	var $operator_translate_table = Array (		// case-sensitiv. Defineres the words, which will be operators between words
		Array ("+" , "AND"),
		Array ("|" , "OR"),
		Array ("-" , "AND NOT"),
			// english
#		Array ("AND" , "AND"),
#		Array ("OR" , "OR"),
#		Array ("NOT" , "AND NOT"),
	);

		// Internals:
	var $cache_path=array();
	var $cache_rl=array();
	var $fe_groups_required=array();
	var $domain_records=array();
	var $sWArr=array();
	var $wSelClauses=array();
	var $firstRow=array();
	var $resultSections=array();

	var $anchorPrefix = '';			// Prefix for local anchors. For "speaking URLs" to work with <base>-url set.

    function main($content,$conf)    {
        $this->conf=$conf;
        $this->pi_loadLL();
		$this->pi_setPiVarDefaults();
		$this->anchorPrefix = substr(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),strlen(t3lib_div::getIndpEnv('TYPO3_SITE_URL')));

#debug($this->piVars);

			// Initialize the indexer-class - just to use a few function (for making hashes)
		$this->indexerObj = t3lib_div::makeInstance("tx_indexedsearch_indexer");

			// If "_sections" is set, this value overrides any existing value.
		if ($this->piVars["_sections"])		$this->piVars["sections"] = $this->piVars["_sections"];

			// Add previous search words to current
		if ($this->piVars['sword_prev_include'] && $this->piVars["sword_prev"])	{
			$this->piVars["sword"] = trim($this->piVars["sword_prev"]).' '.$this->piVars["sword"];
		}

			// Selector-box values defined here:
		$optValues = Array(
			"type" => Array(
				"0" => $this->pi_getLL("opt_type_0"),
				"1" => $this->pi_getLL("opt_type_1"),
				"2" => $this->pi_getLL("opt_type_2"),
				"3" => $this->pi_getLL("opt_type_3"),
				"10" => $this->pi_getLL("opt_type_10"),
				"20" => $this->pi_getLL("opt_type_20"),
			),
			"defOp" => Array(
				"0" => $this->pi_getLL("opt_defOp_0"),
				"1" => $this->pi_getLL("opt_defOp_1"),
			),
			"sections" => Array(
				"0" => $this->pi_getLL("opt_sections_0"),
				"-1" => $this->pi_getLL("opt_sections_-1"),
				"-2" => $this->pi_getLL("opt_sections_-2"),
				"-3" => $this->pi_getLL("opt_sections_-3"),
				// Here values like "rl1_" and "rl2_" + a rootlevel 1/2 id can be added to perform searches in rootlevel 1+2 specifically. The id-values can even be commaseparated. Eg. "rl1_1,2" would search for stuff inside pages on menu-level 1 which has the uid's 1 and 2.
			),
			"media" => Array(
				"-1" => $this->pi_getLL("opt_media_-1"),
				"0" => $this->pi_getLL("opt_media_0"),
				"-2" => $this->pi_getLL("opt_media_-2"),
				"1" => $this->pi_getLL("opt_media_1"),
				"2" => $this->pi_getLL("opt_media_2"),
				"3" => $this->pi_getLL("opt_media_3"),
			),
			"order" => Array(
				"rank_flag" => $this->pi_getLL("opt_order_rank_flag"),
				"rank_freq" => $this->pi_getLL("opt_order_rank_freq"),
				"rank_first" => $this->pi_getLL("opt_order_rank_first"),
				"rank_count" => $this->pi_getLL("opt_order_rank_count"),
				"mtime" => $this->pi_getLL("opt_order_mtime"),
				"title" => $this->pi_getLL("opt_order_title"),
				"crdate" => $this->pi_getLL("opt_order_crdate"),
#				"rating" => "Page-rating",
#				"hits" => "Page-hits",
			),
			"group" => Array (
				"sections" => $this->pi_getLL("opt_group_sections"),
				"flat" => $this->pi_getLL("opt_group_flat"),
			),
			"lang" => Array (
				-1 => $this->pi_getLL("opt_lang_-1"),
				0 => $this->pi_getLL("opt_lang_0"),
			),
			"desc" => Array (
				"0" => $this->pi_getLL("opt_desc_0"),
				"1" => $this->pi_getLL("opt_desc_1"),
			),
			"results" => Array (
				"10" => "10",
				"20" => "20",
				"50" => "50",
				"100" => "100",
			)
		);

		$this->operator_translate_table[]=Array ($this->pi_getLL("local_operator_AND") , "AND");
		$this->operator_translate_table[]=Array ($this->pi_getLL("local_operator_OR") , "OR");
		$this->operator_translate_table[]=Array ($this->pi_getLL("local_operator_NOT") , "AND NOT");

			// This is the id of the site root. This value may be a commalist of integer (prepared for this)
		$this->wholeSiteIdList=intval($GLOBALS["TSFE"]->config["rootLine"][0]["uid"]);

			// This selects the first and secondary menus for the "sections" selector - so we can search in sections and sub sections.
		if ($this->conf["show."]["L1sections"])	{
			$firstLevelMenu = $this->getMenu($this->wholeSiteIdList);
	#		debug($firstLevelMenu);
			while(list($kk,$mR)=each($firstLevelMenu))	{
				if ($mR["doktype"]!=5)	{
					$optValues["sections"]["rl1_".$mR["uid"]]=trim($this->pi_getLL("opt_RL1")." ".$mR["title"]);
					if ($this->conf["show."]["L2sections"])	{
						$secondLevelMenu = $this->getMenu($mR["uid"]);
						while(list($kk2,$mR2)=each($secondLevelMenu))	{
							if ($mR["doktype"]!=5)	{
								$optValues["sections"]["rl2_".$mR2["uid"]]=trim($this->pi_getLL("opt_RL2")." ".$mR2["title"]);
							} else unset($secondLevelMenu[$kk2]);
						}
						$optValues["sections"]["rl2_".implode(",",array_keys($secondLevelMenu))]=$this->pi_getLL("opt_RL2ALL");
					}
				} else unset($firstLevelMenu[$kk]);
			}
			$optValues["sections"]["rl1_".implode(",",array_keys($firstLevelMenu))]=$this->pi_getLL("opt_RL1ALL");
		}

			// This happens AFTER the use of $this->wholeSiteIdList above because the above will then fetch the menu for the CURRENT site - regardless of this kind of searching here. Thus a general search will lookup in the WHOLE database while a specific section search will take the current sections...
		if ($this->conf["search."]["rootPidList"])	{
			$this->wholeSiteIdList = implode(",",t3lib_div::intExplode(",",$this->conf["search."]["rootPidList"]));
#debug($this->wholeSiteIdList);
		}


			// Add search languages:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_language', '1=1'.$this->cObj->enableFields('sys_language'));
		while($lR = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$optValues["lang"][$lR["uid"]]=$lR["title"];
		}



#debug($this->piVars);
			// Setting first values in optValues as default values IF there is not corresponding piVar value set already.
		reset($optValues);
		while(list($kk,$vv)=each($optValues))	{
			if (!isset($this->piVars[$kk]))	{
				reset($vv);
				$this->piVars[$kk]=key($vv);
			}
		}
#debug($this->piVars);

			// Blind selectors:
		if (is_array($this->conf["blind."]))	{
			reset($this->conf["blind."]);
			while(list($kk,$vv)=each($this->conf["blind."]))	{
				if (is_array($vv))	{
					reset($vv);
					while(list($kkk,$vvv)=each($vv))	{
						if (!is_array($vvv) && $vvv && is_array($optValues[substr($kk,0,-1)]))	{
							unset($optValues[substr($kk,0,-1)][$kkk]);
						}
					}
				} elseif ($vv) {	// If value is not set, unset the option array.
					unset($optValues[$kk]);
				}
			}
		}


			// This gets the search-words into the $sWArr:
		$this->sWArr = $sWArr = $this->getSearchWords($this->piVars["defOp"]);
#debug($this->sWArr);

			// If there was any search words entered...
		if (is_array($sWArr))	{
			$content = $this->doSearch($sWArr);
		}	// END: There was a search word.

			// Finally compile all the content, form, messages and results:
        $content=
			$this->makeSearchForm($optValues).
			$this->printRules().
    	    $content;

        return $this->pi_wrapInBaseClass($content);
    }

	/**
	 * Performs the search, if any search words found.
	 */
	function doSearch($sWArr)	{
		$rowcontent="";
		$pt1=t3lib_div::milliseconds();

			// This SEARCHES for the searchwords in $sWArr AND returns a COMPLETE list of phash-integers of the matches.
		$list = $this->getPhashList($sWArr);
		$pt2=t3lib_div::milliseconds();


			// IF there were any matches (there is results...) then go on.
		if ($list)	{
			$GLOBALS["TT"]->push("Searching Final result");

				// Do the search:
			$res = $this->execFinalQuery($list);

				// Get some variables:
			$count = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
#debug($count);
			$this->piVars["results"] = $displayCount = t3lib_div::intInRange($this->piVars["results"],1,100000,$this->defaultResultNumber);
			$pointer=t3lib_div::intInRange($this->piVars["pointer"],0,floor($count/$displayCount));

			$pt3=t3lib_div::milliseconds();

				// Now, traverse result and put the rows to be displayed into an array
			$lines=Array();
			$c=0;
			$this->firstRow=Array();	// Will hold the first row in result - used to calculate relative hit-ratings.
			$this->resultRows=Array();	// Will hold the results rows for display.
			$this->grouping_phashes=array();	// Used to filter out duplicates.
			$this->grouping_chashes=array();	// Used to filter out duplicates BASED ON cHash.
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				if (!$c)	{
					$this->firstRow=$row;
				}

				$row["show_resume"]=$this->checkResume($row);
				$phashGr = !in_array($row["phash_grouping"],$this->grouping_phashes);
				$chashGr = !in_array($row["contentHash"].".".$row["data_page_id"],$this->grouping_chashes);
				if ($phashGr && $chashGr)	{
					if ($row["show_resume"])	{	// Only if the resume may be shown are we going to filter out duplicates...
						if ($row["item_type"]!=2)	{	// Only on documents which are not PDF files.
							$this->grouping_phashes[]=$row["phash_grouping"];
						}
						$this->grouping_chashes[]=$row["contentHash"].".".$row["data_page_id"];
					}
					$c++;

						// All rows for display is put into resultRows[]
					if ($c > $pointer*$displayCount)	{
						$row["result_number"]=$c;
						$this->resultRows[] = $row;
						if ($c+1 > ($pointer+1)*$displayCount)	break;
					}
				} else {
					$count--;	// For each time a phash_grouping document is found (which is thus not displayed) the search-result count is reduced, so that it matches the number of rows displayed.
#					debug();
				}
			}
			$GLOBALS["TT"]->pull();

#debug($this->resultRows);
#debug(count($this->resultRows));
#debug($this->grouping_chashes);

			$GLOBALS["TT"]->push("Display Final result");

				// SO, on to the result display here:
			$rowcontent.=$this->compileResult($this->resultRows);
			$pt4=t3lib_div::milliseconds();

				// Makes the result browsing navigation (next/prev, 1-2-3)
#			$PS = $this->makePointerSelector($count,$displayCount,$pointer);


				// Browsing box:
			if ($count)	{
				#$content.=$PS."<HR>";
				$this->internal["res_count"]=$count;
				$this->internal["results_at_a_time"]=$displayCount;
				$this->internal["maxPages"]=t3lib_div::intInRange($this->conf["search."]["page_links"],1,100,10);
				$addString = ($count&&$this->piVars["group"]=="sections"?" ".sprintf($this->pi_getLL("inNsection".(count($this->resultSections)>1?"s":"")),count($this->resultSections)):"");
				$browseBox1 = $this->pi_list_browseresults(1,$addString,$this->printResultSectionLinks());
				$browseBox2 = $this->pi_list_browseresults(0);
			}

				// Print the time the search took:
			if ($pt1 && $this->conf["show."]["parsetimes"])	{
				$parsetimes="";
				$parsetimes.="<p>Word Search took: ".($pt2-$pt1)." ms<BR>";
				$parsetimes.="Order Search took: ".($pt3-$pt2)." ms<BR>";
				$parsetimes.="Display took: ".($pt4-$pt3)." ms</p><HR>";
			}

				// Browsing nav, bottom.
			if ($count)	{
				$content=$browseBox1.$rowcontent.$browseBox2;
			} else {
				$content='<p'.$this->pi_classParam("noresults").'>'.$this->pi_getLL("noResults").'</p>';
			}
			$content.=$parsetimes;

			$GLOBALS["TT"]->pull();
		} else {	// No results found:
			$content.='<p'.$this->pi_classParam("noresults").'>'.$this->pi_getLL("noResults").'</p>';
		}

			// Print a message telling which words we searched for, and in which sections etc.
		$what=$this->tellUsWhatIsSeachedFor($sWArr).
			(substr($this->piVars["sections"],0,2)=="rl"?" ".$this->pi_getLL("inSection")." '".substr($this->getPathFromPageId(substr($this->piVars["sections"],4)),1)."'":"");
		$what='<div'.$this->pi_classParam("whatis").'><p>'.$what.'</p></div>';
		$content=$what.$content;

			// Write search statistics
		$this->writeSearchStat($sWArr,$count,array($pt1,$pt2,$pt3,$pt4));
		return $content;
	}






	/***********************************

		SEARCHING FUNCTIONS

	***********************************/







	/**
	 * This splits the search word input into an array where each word is
	 *
	 * Only words with 2 or more characters are accepted
	 * Max 200 chars total
	 * Space is used to split words, "" can be used search for a whole string (not indexed search then)
	 * AND, OR and NOT are prefix words, overruling the default operator
	 * +/|/- equals AND, OR and NOT as operators.
	 * All search words are converted to lowercase.
	 *
	 * $defOp is the default operator. 1=OR, 0=AND
	 */
	function getSearchWords($defOp)	{
		$inSW = substr($this->piVars["sword"],0,200);

			// Convert to UTF-8:
		$inSW = $GLOBALS['TSFE']->csConvObj->utf8_encode($inSW, $GLOBALS['TSFE']->metaCharset);

		if ($this->piVars["type"]==20)	{
			return array(array("sword"=>trim($inSW),"oper"=>"AND"));
		} else {
			$search = t3lib_div::makeInstance("tslib_search");
			$search->default_operator = $defOp==1 ? 'OR' : 'AND';
			$search->operator_translate_table = $this->operator_translate_table;
			$search->register_and_explode_search_string($inSW);

			if (is_array($search->sword_array))	{
				return $search->sword_array;
			}
		}
	}

	/**
	 * Returns a COMPLETE list of phash-integers matching the search-result composed of the search-words in the sWArr array.
	 * The list of phash integers are unsorted and should be used for subsequent selection of index_phash records for display of the result.
	 */
	function getPhashList($sWArr)	{
		$c=0;

		$totalHashList=array();	// This array accumulates the phash-values
		$this->wSelClauses=array();

		reset($sWArr);
		while(list($k,$v)=each($sWArr))	{
			$sWord = strtolower($v["sword"]);	// lower-case all of them...

			$GLOBALS["TT"]->push("SearchWord ".$sWord);

			$plusQ="";
/*
				// Maybe this will improve the search queries. Tests has shown it not to do so though...
			if (count($totalHashList))	{
				switch($v["oper"])	{
					case "OR":
						$plusQ = "AND IR.phash NOT IN (".implode(",",$totalHashList).")";
					break;
					case "AND NOT":
					default:	// AND
						$plusQ = "AND IR.phash IN (".implode(",",$totalHashList).")";
					break;
				}
			}
			$plusQ="";
*/

				// Making the query for a single search word based on the search-type
			$res="";
			$theType = (string)$this->piVars["type"];
			if (strstr($sWord," "))	$theType=20;	// If there are spaces in the search-word, make a full text search instead.

			$wSel="";
			switch($theType)	{
				case "1":
					$wSel = "IW.baseword LIKE '%".$GLOBALS['TYPO3_DB']->quoteStr($sWord, 'index_words')."%'";
					$res = $this->execPHashListQuery($wSel,$plusQ);

				break;
				case "2":
					$wSel = "IW.baseword LIKE '".$GLOBALS['TYPO3_DB']->quoteStr($sWord, 'index_words')."%'";
					$res = $this->execPHashListQuery($wSel,$plusQ);
				break;
				case "3":
					$wSel = "IW.baseword LIKE '%".$GLOBALS['TYPO3_DB']->quoteStr($sWord, 'index_words')."'";
					$res = $this->execPHashListQuery($wSel,$plusQ);
				break;
				case "10":
					$wSel = "IW.metaphone = ".$this->indexerObj->metaphone($sWord);
					$res = $this->execPHashListQuery($wSel,$plusQ);
				break;
				case "20":
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'ISEC.phash',
								'index_section ISEC, index_fulltext IFT',
								'IFT.fulltextdata LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($sWord, 'index_fulltext').'%\' AND
									ISEC.phash = IFT.phash
									'.$this->sectionTableWhere(),
								'ISEC.phash'
							);
					$wSel = "1=1";

					if ($this->piVars["type"]==20)	$this->piVars["order"]="mtime";		// If there is a fulltext search for a sentence there is a likelyness that sorting cannot be done by the rankings from the rel-table (because no relations will exist for the sentence in the word-table). So therefore mtime is used instaed. It is not required, but otherwise some hits may be left out.
				break;
				default:
					$wSel = "IW.wid = ".$hash = $this->indexerObj->md5inthash($sWord);
					$res = $this->execPHashListQuery($wSel,$plusQ);
				break;
			}
			$this->wSelClauses[]=$wSel;

				// If there was a query to do, then select all phash-integers which resulted from this.
			if ($res)	{

				# Get phash list by searching for it:
				$phashList = array();
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$phashList[]=$row["phash"];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);

					// Here the phash list are merged with the existing result based on whether we are dealing with OR, NOT or AND operations.
				if ($c) {
					switch($v["oper"])	{
						case "OR":
							$totalHashList=array_unique(array_merge($phashList,$totalHashList));
						break;
						case "AND NOT":
							$totalHashList=array_diff($totalHashList,$phashList);
						break;
						default:	// AND...
							$totalHashList=array_intersect($totalHashList,$phashList);
						break;
					}
				} else {
					$totalHashList=$phashList;	// First search
				}
#debug($totalHashList);
			}

			$GLOBALS["TT"]->pull();
			$c++;
		}

#debug($sWArr);
		return implode(",",$totalHashList);
	}

	/**
	 * Returns a query which selects the search-word from the word/rel tables.
	 */
	function execPHashListQuery($wordSel,$plusQ="")	{
		return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'IR.phash',
					'index_words IW,
						index_rel IR,
						index_section ISEC',
					$wordSel.'
						AND IW.wid=IR.wid
						AND ISEC.phash = IR.phash
						'.$this->sectionTableWhere().'
						'.$plusQ,
					'IR.phash'
				);
	}

	/**
	 * Returns AND statement for selection of section in database. (rootlevel 0-2 + page_id)
	 */
	function sectionTableWhere()	{
#debug($this->piVars["sections"]);
		$out = $this->wholeSiteIdList<0 ? "" : "AND ISEC.rl0 IN (".$this->wholeSiteIdList.")";
		$list = implode(",",t3lib_div::intExplode(",",substr($this->piVars["sections"],4)));

		if (substr($this->piVars["sections"],0,4)=="rl1_")	{
			$out.= "AND ISEC.rl1 IN (".$list.")";
		} else if (substr($this->piVars["sections"],0,4)=="rl2_")	{
			$out.= "AND ISEC.rl2 IN (".$list.")";
		} else {
			switch((string)$this->piVars["sections"])	{
				case "-1":		// "-1" => "Only this page",
					$out.= " AND ISEC.page_id=".$GLOBALS["TSFE"]->id;
				break;
				case "-2":		// "-2" => "Top + level 1",
					$out.= " AND ISEC.rl2=0";
				break;
				case "-3":		// "-3" => "Level 2 and out",
					$out.= " AND ISEC.rl2>0";
				break;
			}
		}
		return $out;
	}

	/**
	 * Returns AND statement for selection of media type
	 */
	function mediaTypeWhere()	{
		switch($this->piVars["media"])	{
			case 0:		// "0" => "Kun TYPO3 sider",
				$out = "AND IP.item_type=0";
			break;
			case 1:		// "1" => "Kun HTML dokumenter",
				$out = "AND IP.item_type=1";
			break;
			case 2:		// "2" => "Kun PDF dokumenter",
				$out = "AND IP.item_type=2";
			break;
			case 3:		// "3" => "Kun Word dokumenter",
				$out = "AND IP.item_type=3";
			break;
			case -2:		// All external documents
				$out = "AND IP.item_type>0";
			break;
			case -1:
			default:
				$out="";
			break;
		}
		return $out;
	}

	/**
	 * Returns AND statement for selection of langauge
	 */
	function languageWhere()	{
		if ($this->piVars["lang"]>=0)	{	// -1 is the same as ALL language.
			return "AND IP.sys_language_uid=".intval($this->piVars["lang"]);
		}
	}

	/**
	 * Execute final query:
	 */
	function execFinalQuery($list)	{

		$page_join="";
		$page_where="";
		if ($this->join_pages)	{
			$page_join = ",
				pages";
			$page_where = "pages.uid = ISEC.page_id
				".$this->cObj->enableFields("pages")."
				AND pages.no_search=0
				AND pages.doktype<200
			";
		} elseif ($this->wholeSiteIdList>=0) {
			$siteIdNumbers = t3lib_div::intExplode(",",$this->wholeSiteIdList);
			$id_list=array();
			while(list(,$rootId)=each($siteIdNumbers))	{
				$id_list[]=$this->cObj->getTreeList($rootId,9999,0,0,"","").$rootId;
			}
			$page_where = "ISEC.page_id IN (".implode(",",$id_list).")";
		} else {
			$page_where = " 1=1 ";
		}

			// If any of the ranking sortings are selected, we must make a join with the word/rel-table again, because we need to calculate ranking based on all search-words found.
		if (substr($this->piVars["order"],0,5)=="rank_")	{
				/*
					 OK there were some fancy calculations promoted by Graeme Merrall:

					"However, regarding relevance you probably want to look at something like
					Salton's formula which is a good easy way to measure relevance.
					Oracle Intermedia uses this and it's pretty simple:
					Score can be between 0 and 100, but the top-scoring document in the query
					will not necessarily have a score of 100 -- scoring is relative, not
					absolute. This means that scores are not comparable across indexes, or even
					across different queries on the same index. Score for each document is
					computed using the standard Salton formula:

					    3f(1+log(N/n))

					Where f is the frequency of the search term in the document, N is the total
					number of rows in the table, and n is the number of rows which contain the
					search term. This is converted into an integer in the range 0 - 100.

					There's a good doc on it at
					http://ls6-www.informatik.uni-dortmund.de/bib/fulltext/ir/Pfeifer:97/
					although it may be a little complex for what you require so just pick the
					relevant parts out.
					"

					However I chose not to go with this for several reasons.
					I do not claim that my ways of calculating importance here is the best.
					ANY (better) suggestions for ranking calculation is accepted! (as long as they are shipped with tested code in exchange for this.)
				*/

			switch($this->piVars["order"])	{
				case "rank_flag":	// This gives priority to word-position (max-value) so that words in title, keywords, description counts more than in content.
									// The ordering is refined with the frequency sum as well.
					$grsel = "MAX(IR.flags) AS order_val1, SUM(IR.freq) AS order_val2";
					$orderBy = "order_val1".$this->isDescending().",order_val2".$this->isDescending();
				break;
				case "rank_first":	// Results in average position of search words on page. Must be inversely sorted (low numbers are closer to top)
					$grsel = "AVG(IR.first) AS order_val";
					$orderBy = "order_val".$this->isDescending(1);
				break;
				case "rank_count":	// Number of words found
					$grsel = "SUM(IR.count) AS order_val";
					$orderBy = "order_val".$this->isDescending();
				break;
				default:	// Frequency sum. I'm not sure if this is the best way to do it (make a sum...). Or should it be the average?
					$grsel = "SUM(IR.freq) AS order_val";
					$orderBy = "order_val".$this->isDescending();
				break;
			}

				// So, words are imploded into an OR statement (no "sentence search" should be done here - may deselect results)
			$wordSel='('.implode(' OR ',$this->wSelClauses).') AND ';

			return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'ISEC.*, IP.phash_grouping,  IP.data_filename, IP.data_page_id, IP.data_page_reg1, IP.data_page_type, IP.data_page_mp, IP.gr_list, IP.item_type, IP.item_title, IP.item_description, IP.item_mtime, IP.tstamp, IP.item_size, IP.contentHash, IP.crdate, IP.parsetime, IP.sys_language_uid, IP.item_crdate, '
						.$grsel,
						'index_words IW,
							index_rel IR,
							index_section ISEC,
							index_phash IP'.
							$page_join,
						$wordSel.'
							IP.phash IN ('.$list.') '.
							$this->mediaTypeWhere().' '.
							$this->languageWhere().'
							AND IW.wid=IR.wid
							AND ISEC.phash = IR.phash
							AND IP.phash = IR.phash
							AND	'.$page_where,
						'IP.phash,ISEC.phash,ISEC.phash_t3,ISEC.rl0,ISEC.rl1,ISEC.rl2 ,ISEC.page_id,ISEC.uniqid,IP.phash_grouping,IP.data_filename ,IP.data_page_id ,IP.data_page_reg1,IP.data_page_type,IP.data_page_mp,IP.gr_list,IP.item_type,IP.item_title,IP.item_description,IP.item_mtime,IP.tstamp,IP.item_size,IP.contentHash,IP.crdate,IP.parsetime,IP.sys_language_uid,IP.item_crdate',
						$orderBy
					);
		} else {	// Otherwise, if sorting are done with the pages table or other fields, there is no need for joining with the rel/word tables:

			$orderBy = '';
			switch((string)$this->piVars["order"])	{
				case "rating":
					debug("rating: NOT ACTIVE YET");
				break;
				case "hits":
					debug("rating: NOT ACTIVE YET");
				break;
				case "title":
					$orderBy = "IP.item_title".$this->isDescending();
				break;
				case "crdate":
					$orderBy = "IP.item_crdate".$this->isDescending();
				break;
				case "mtime":
					$orderBy = "IP.item_mtime".$this->isDescending();
				break;
			}

			return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'ISEC.*, IP.*',
						'index_phash IP,index_section ISEC'.$page_join,
						'IP.phash IN ('.$list.') '.
							$this->mediaTypeWhere().' '.
							$this->languageWhere().'
							AND IP.phash = ISEC.phash
							AND '.$page_where,
						'IP.phash',
						$orderBy
					);
		}
	}

	/**
	 * Checking if the resume can be shown for the search result:
	 */
	function checkResume($row)	{
		if ($row["item_type"]>0)	{
				// phash_t3 is the phash of the parent TYPO3 page row which initiated the indexing of the documents in this section.

				// So, selecting for the grlist records belonging to the parent phash-row where the current users gr_list exists.
				// If this is NOT found, there is still a theoretical possibility that another user accessible page would display a link, so maybe the resume of such a document here may be unjustified hidden. But this case is rare.
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash', 'index_grlist', 'phash='.intval($row['phash_t3']).' AND gr_list='.$GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['TSFE']->gr_list, 'index_grlist'));
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
#				debug("Look up for external media '".$row["data_filename"]."': phash:".$row["phash_t3"]." YES - (".$GLOBALS["TSFE"]->gr_list.")!",1);
				return 1;
			} else {
#				debug("Look up for external media '".$row["data_filename"]."': phash:".$row["phash_t3"]." NO - (".$GLOBALS["TSFE"]->gr_list.")!",1);
				return 0;
			}
		} else {	// ALm typo3 pages:
			if (strcmp($row["gr_list"],$GLOBALS["TSFE"]->gr_list))	{
					// Selecting for the grlist records belonging to the phash-row where the current users gr_list exists. If it is found it is proof that this user has direct access to the phash-rows content although he did not himself initiate the indexing...
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('phash', 'index_grlist', 'phash='.intval($row['phash']).' AND gr_list='.$GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['TSFE']->gr_list, 'index_grlist'));
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
					#debug("Checking on it ...".$row["item_title"]."/".$row["phash"]." - YES (".$GLOBALS["TSFE"]->gr_list.")",1);
					return 1;
				} else {
					#debug("Checking on it ...".$row["item_title"]."/".$row["phash"]." - NOPE",1);
					return 0;
				}
			} else {
					#debug("Resume can be shown, because the document was in fact indexed by this combination of groups!".$GLOBALS["TSFE"]->gr_list." - ".$row["item_title"]."/".$row["phash"],1);
				return 1;
			}
		}
	}

	/**
	 * Returns "DESC" or "" depending on the settings of the incoming highest/lowest result order.
	 */
	function isDescending($inverse=0)	{
		$desc = $this->piVars["desc"];
		if ($inverse)	$desc=!$desc;
		return !$desc ? " DESC":"";
	}

	/**
	 * Write statistics information for the search:
	 */
	function writeSearchStat($sWArr,$count,$pt)	{
		$insertFields = array(
			'searchstring' => $this->piVars['sword'],
			'searchoptions' => serialize(array($this->piVars,$sWArr,$pt)),
			'feuser_id' => intval($this->fe_user->user['uid']),			// fe_user id, integer
			'cookie' => $this->fe_user->id,						// cookie as set or retrieve. If people has cookies disabled this will vary all the time...
			'IP' => t3lib_div::getIndpEnv('REMOTE_ADDR'),		// Remote IP address
			'hits' => intval($count),							// Number of hits on the search.
			'tstamp' => $GLOBALS['EXEC_TIME']					// Time stamp
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_stat_search', $insertFields);
		$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();

		if ($newId)	{
			foreach($sWArr as $val)	{
				$insertFields = array(
					'word' => strtolower($val['sword']),
					'index_stat_search_id' => $newId,
					'tstamp' => $GLOBALS['EXEC_TIME']		// Time stamp
				);

				$GLOBALS['TYPO3_DB']->exec_INSERTquery('index_stat_word', $insertFields);
			}
		}
	}













	/***********************************

		LAYOUT FUNCTIONS

	***********************************/




	/**
	 * Make search form
	 */
	function makeSearchForm($optValues)	{
		$rows=array();
			// Adding search field and button:
		$rows[]='<tr>
				<td nowrap><p>'.$this->pi_getLL("form_searchFor").'&nbsp;</p></td>
				<td><input type="text" name="'.$this->prefixId.'[sword]" value="'.htmlspecialchars($this->conf["show."]["clearSearchBox"]?'':$this->piVars["sword"]).'"'.$this->pi_classParam("searchbox-sword").'>&nbsp;&nbsp;<input type="submit" name="'.$this->prefixId.'[submit_button]" value="'.$this->pi_getLL("submit_button_label").'"'.$this->pi_classParam("searchbox-button").'></td>
			</tr>';

		if ($this->conf["show."]["clearSearchBox"] && $this->conf["show."]["clearSearchBox."]['enableSubSearchCheckBox'])	{
			$rows[]='<tr>
				<td></td>
				<td><input type="hidden" name="'.$this->prefixId.'[sword_prev]" value="'.htmlspecialchars($this->piVars["sword"]).'"><input type="checkbox" name="'.$this->prefixId.'[sword_prev_include]" value="1"'.($this->piVars['sword_prev_include']?' checked="checked"':'').'> Add to current search words</td>
			</tr>';
		}


		if ($this->piVars["ext"])	{
			if (is_array($optValues["type"]) || is_array($optValues["defOp"]))	$rows[]='<tr>
					<td nowrap><p>'.$this->pi_getLL("form_match").'&nbsp;</p></td>
					<td>'.$this->renderSelectBox($this->prefixId.'[type]',$this->piVars["type"],$optValues["type"]).
					$this->renderSelectBox($this->prefixId.'[defOp]',$this->piVars["defOp"],$optValues["defOp"]).'</td>
				</tr>';
			if (is_array($optValues["media"]) || is_array($optValues["lang"]))	$rows[]='<tr>
					<td nowrap><p>'.$this->pi_getLL("form_searchIn").'&nbsp;</p></td>
					<td>'.$this->renderSelectBox($this->prefixId.'[media]',$this->piVars["media"],$optValues["media"]).
					$this->renderSelectBox($this->prefixId.'[lang]',$this->piVars["lang"],$optValues["lang"]).'</td>
				</tr>';
			if (is_array($optValues["sections"]))	$rows[]='<tr>
					<td nowrap><p>'.$this->pi_getLL("form_fromSection").'&nbsp;</p></td>
					<td>'.$this->renderSelectBox($this->prefixId.'[sections]',$this->piVars["sections"],$optValues["sections"]).'</td>
				</tr>';
			if (is_array($optValues["order"]) || is_array($optValues["desc"]) || is_array($optValues["results"]))	$rows[]='<tr>
					<td nowrap><p>'.$this->pi_getLL("form_orderBy").'&nbsp;</p></td>
					<td><p>'.$this->renderSelectBox($this->prefixId.'[order]',$this->piVars["order"],$optValues["order"]).
						$this->renderSelectBox($this->prefixId.'[desc]',$this->piVars["desc"],$optValues["desc"]).
						$this->renderSelectBox($this->prefixId.'[results]',$this->piVars["results"],$optValues["results"]).'&nbsp;'.$this->pi_getLL("form_atATime").'</p></td>
				</tr>';
			if (is_array($optValues["group"]) || !$this->conf["blind."]["extResume"])	$rows[]='<tr>
					<td nowrap><p>'.$this->pi_getLL("form_style").'&nbsp;</p></td>
					<td><p>'.$this->renderSelectBox($this->prefixId.'[group]',$this->piVars["group"],$optValues["group"]).
					(!$this->conf["blind."]["extResume"] ? '&nbsp; &nbsp;
					<input type="hidden" name="'.$this->prefixId.'[extResume]" value="0"><input type="checkbox" value="1" name="'.$this->prefixId.'[extResume]"'.($this->piVars["extResume"]?" CHECKED":"").'>'.$this->pi_getLL("form_extResume"):'').'</p></td>
				</tr>';
		}

#debug(array($GLOBALS["TSFE"]->id,$GLOBALS["TSFE"]->sPre,$this->pi_getPageLink($GLOBALS["TSFE"]->id,$GLOBALS["TSFE"]->sPre)));

		$out='<table '.$this->conf["tableParams."]["searchBox"].'>
					<form action="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id,$GLOBALS["TSFE"]->sPre).'" method="POST" name="'.$this->prefixId.'">
				'.implode(chr(10),$rows).'
						<input type="hidden" name="'.$this->prefixId.'[_sections]" value="0">
						<input type="hidden" name="'.$this->prefixId.'[pointer]" value="0">
						<input type="hidden" name="'.$this->prefixId.'[ext]" value="'.($this->piVars["ext"]?1:0).'">
		            </form>
				</table>';
		$out.='<p>'.
				($this->piVars["ext"] ?
					'<a href="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id,$GLOBALS["TSFE"]->sPre,array($this->prefixId."[ext]"=>0)).'">'.$this->pi_getLL("link_regularSearch").'</a>' :
					'<a href="'.$this->pi_getPageLink($GLOBALS["TSFE"]->id,$GLOBALS["TSFE"]->sPre,array($this->prefixId."[ext]"=>1)).'">'.$this->pi_getLL("link_advancedSearch").'</a>'
				).'</p>';

		return '<div'.$this->pi_classParam("searchbox").'>'.$out.'</div>';
	}

	/**
	 * Print the searching rules
	 */
	function printRules()	{
		$out = "";
		if ($this->conf["show."]["rules"])	{
			$out = '<h2>'.$this->pi_getLL("rules_header").'</h2><p>'.nl2br(htmlspecialchars(trim($this->pi_getLL("rules_text")))).'</p>';
			$out = '<div'.$this->pi_classParam("rules").'>'.$this->cObj->stdWrap($out, $this->conf["rules_stdWrap."]).'</div>';
		}
		return $out;
	}

	/**
	 * Returns the anchor-links to the sections inside the displayed result rows.
	 */
	function printResultSectionLinks()	{
		$lines=array();
		reset($this->resultSections);
		while(list($id,$dat)=each($this->resultSections))	{
			$lines[]='<li><a href="'.$this->anchorPrefix.'#'.md5($id).'">'.(trim($dat[0])?htmlspecialchars(trim($dat[0])):$this->pi_getLL("unnamedSection")).' ('.$dat[1].' '.$this->pi_getLL("word_page".($dat[1]>1?"s":"")).')</a></li>';
		}
		$out = '<ul>'.implode(chr(10),$lines).'</ul>';
		return '<div'.$this->pi_classParam("sectionlinks").'>'.$this->cObj->stdWrap($out, $this->conf["sectionlinks_stdWrap."]).'</div>';
	}

	/**
	 * Returns the links for the result browser bar (next/prev/1-2-3)
	 */
	function makePointerSelector($count,$displayCount,$pointer)	{
		$lines=array();

			// Previous pointer:
		if ($pointer>0)	{
			$lines[]=$this->makePointerSelector_link("PREV",0);
		}

			// 1-2-3
		for ($a=0;$a<t3lib_div::intInRange(ceil($count/$displayCount),1,10);$a++)	{
#			$str = ($a*$displayCount+1)."-".(($a+1)*$displayCount);
			$str = $a+1;
			$linkStr = $this->makePointerSelector_link($str,$a);
			$lines[]=	$pointer==$a ? '<strong>['.$linkStr.']</strong>' : $linkStr;
		}

			// Next pointer:
		if ($pointer+1<ceil($count/$displayCount))	$lines[]=$this->makePointerSelector_link("NEXT",$pointer+1);

		return implode(" - ",$lines);
	}

	/**
	 * Used to make the link for the result-browser.
	 * Notive now the links must resubmit the form after setting the new pointer-value in a hidden formfield.
	 */
	function makePointerSelector_link($str,$p)	{
		return '<a href="#" onClick="document.'.$this->prefixId.'[\''.$this->prefixId.'[pointer]\'].value=\''.$p.'\';document.'.$this->prefixId.'.submit();return false;">'.$str.'</a>';
	}

	/**
	 * Returns a string that tells which search words are searched for.
	 */
	function tellUsWhatIsSeachedFor($sWArr)	{
		reset($sWArr);
		$searchingFor="";
		$c=0;
		while(list($k,$v)=each($sWArr))	{
			if ($c)	{
				switch($v["oper"])	{
					case "OR":
						$searchingFor.=" ".$this->pi_getLL("searchFor_or")." ".$this->wrapSW($v["sword"]);
					break;
					case "AND NOT":
						$searchingFor.=" ".$this->pi_getLL("searchFor_butNot")." ".$this->wrapSW($v["sword"]);
					break;
					default:	// AND...
						$searchingFor.=" ".$this->pi_getLL("searchFor_and")." ".$this->wrapSW($v["sword"]);
					break;
				}

			} else {
				$searchingFor=$this->pi_getLL("searchFor")." ".$this->utf8_to_currentCharset($this->wrapSW($v["sword"]));
			}
			$c++;
		}
		return $searchingFor;
	}

	/**
	 * Wraps the search words in the search-word list display (from ->tellUsWhatIsSeachedFor())
	 */
	function wrapSW($str)	{
		return "'<span".$this->pi_classParam("sw").">".htmlspecialchars($str)."</span>'";
	}

	/**
	 * Makes a selector box
	 */
	function renderSelectBox($name,$value,$optValues)	{
		if (is_array($optValues))	{
			$opt=array();
			$isSelFlag=0;
			reset($optValues);
			while(list($k,$v)=each($optValues))	{
				$sel = (!strcmp($k,$value)?" SELECTED":"");
				if ($sel)	$isSelFlag++;
				$opt[]='<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
			}
	#		if (!$isSelFlag && strcmp("",$value))	$opt[]='<option value="'.$value.'" SELECTED>'.htmlspecialchars("CURRENT VALUE '".$value."' DID NOT EXIST AMONG THE OPTIONS").'</option>';
			return '<select name="'.$name.'">'.implode("",$opt).'</select>';
		}
	}















	/***********************************

		Result row LAYOUT

	***********************************/

	/**
	 * Takes the array with resultrows as input and returns the result-HTML-code
	 * Takes the "group" var into account: Makes a "section" or "flat" display.
	 */
	function compileResult($resultRows)	{
		$content="";

		$newResultRows=array();
		reset($resultRows);
		while(list(,$row)=each($resultRows))	{
			$id = md5($row["phash_grouping"]);
			if (is_array($newResultRows[$id]))	{
				if (!$newResultRows[$id]["show_resume"] && $row["show_resume"])	{	// swapping:
						// Remove old
					$subrows = $newResultRows[$id]["_sub"];
					unset($newResultRows[$id]["_sub"]);
					$subrows[] =$newResultRows[$id];

						// Insert new:
					$newResultRows[$id]=$row;
					$newResultRows[$id]["_sub"]=$subrows;
				} else $newResultRows[$id]["_sub"][]=$row;
			} else {
				$newResultRows[$id]=$row;
			}
		}
		$resultRows=$newResultRows;


		switch($this->piVars["group"])	{
			case "sections":
				$rl2flag = substr($this->piVars["sections"],0,2)=="rl";
				$sections=array();
				reset($resultRows);
				while(list(,$row)=each($resultRows))	{
					$id = $row["rl0"]."-".$row["rl1"].($rl2flag?"-".$row["rl2"]:"");
					$sections[$id][]=$row;
				}

				$this->resultSections=array();
				reset($sections);
				while(list($id,$resultRows)=each($sections))	{
					$rlParts = explode("-",$id);

					$theId = $rlParts[2]?$rlParts[2]:($rlParts[1]?$rlParts[1]:$rlParts[0]);
					$theRLid = $rlParts[2]?"rl2_".$rlParts[2]:($rlParts[1]?"rl1_".$rlParts[1]:"0");

					$sectionName = substr($this->getPathFromPageId($theId),1);
					if (!trim($sectionName))	{
						$sectionTitleLinked=$this->pi_getLL("unnamedSection").":";
					} else {
						$sectionTitleLinked = '<a href="#" onClick="document.'.$this->prefixId.'[\''.$this->prefixId.'[_sections]\'].value=\''.$theRLid.'\';document.'.$this->prefixId.'.submit();return false;">'.$sectionName.':</a>';
					}

					$content.=$this->makeSectionHeader($id,$sectionTitleLinked,count($resultRows));
					$this->resultSections[$id] = array($sectionName,count($resultRows));
					reset($resultRows);
					while(list(,$row)=each($resultRows))	{
						$content.=$this->printResultRow($row);
					}
				}
			break;
			default:	// flat:
				reset($resultRows);
				while(list(,$row)=each($resultRows))	{
					$content.=$this->printResultRow($row);
				}
			break;
		}
		return '<div'.$this->pi_classParam("res").'>'.$content.'</div>';
	}

	/**
	 * Returns the section header of the search result.
	 */
	function makeSectionHeader($id,$sectionTitleLinked,$countResultRows)	{
		return '<div'.$this->pi_classParam("secHead").'><a name="'.md5($id).'"></a><table '.$this->conf["tableParams."]["secHead"].'>
						<tr>
						<td width="95%"><h2>'.$sectionTitleLinked.'</h2></td>
						<td align="right" nowrap><p>'.$countResultRows.' '.$this->pi_getLL("word_page".($countResultRows>1?"s":"")).'</p></td>
						</tr>
					</table></div>';
	}

	/**
	 * This prints a single result row, including a recursive call for subrows.
	 */
	function printResultRow($row,$headerOnly=0)	{
		$specRowConf = $this->getSpecialConfigForRow($row);
		$CSSsuffix = $specRowConf["CSSsuffix"]?"-".$specRowConf["CSSsuffix"]:"";

			// If external media, link to the media-file instead.
		if ($row["item_type"])	{
			if ($row["show_resume"])	{	// Can link directly.
				$title = '<a href="'.$row["data_filename"].'">'.$row["result_number"].": ".$this->makeTitle($row).'</a>';
			} else {	// Suspicious, so linking to page instead...
				$copy_row=$row;
				unset($copy_row["cHashParams"]);
				$title = $this->linkPage($row["page_id"],$row["result_number"].": ".$this->makeTitle($row),$copy_row);
			}
		} else {	// Else the page:
			$title = $this->linkPage($row["data_page_id"],$row["result_number"].": ".$this->makeTitle($row),$row);
		}

			// Make the header row with title, icon and rating bar.:
		$out.='<tr '.$this->pi_classParam("title".$CSSsuffix).'>
			<td width="16">'.$this->makeItemTypeIcon($row["item_type"],"",$specRowConf).'</td>
			<td width="95%" nowrap><p>'.$title.'</p></td>
			<td nowrap><p'.$this->pi_classParam("percent".$CSSsuffix).'>'.$this->makeRating($row).'</p></td>
		</tr>';

			// Print the resume-section. If headerOnly is 1, then  only the short resume is printed
		if (!$headerOnly)	{
			$out.='<tr>
				<td></td>
				<td colspan=2'.$this->pi_classParam("descr".$CSSsuffix).'><p>'.$this->makeDescription($row,$this->piVars["extResume"]?0:1).'</p></td>
			</tr>';
			$out.='<tr>
				<td></td>
				<td '.$this->pi_classParam("info".$CSSsuffix).' nowrap><p>'.$this->makeInfo($row).'</p></td>
				<td '.$this->pi_classParam("info".$CSSsuffix).' align="right"><p>'.$this->makeAccessIndication($row["page_id"]).''.$this->makeLanguageIndication($row).'</p></td>
			</tr>';
		} elseif ($headerOnly==1) {
			$out.='<tr>
				<td></td>
				<td colspan=2'.$this->pi_classParam("descr".$CSSsuffix).'><p>'.$this->makeDescription($row,1,180).'</p></td>
			</tr>';
		} elseif ($headerOnly==2) {
			// nothing.
		}

			// If there are subrows (eg. subpages in a PDF-file or if a duplicate page is selected due to user-login (phash_grouping))
		if (is_array($row["_sub"]))	{
			if ($row["item_type"]==2)	{
				$out.='<tr>
					<td></td>
					<td colspan=2><p><BR>'.$this->pi_getLL("res_otherMatching").'<BR><BR></p></td>
				</tr>';

				reset($row["_sub"]);
				while(list(,$subRow)=each($row["_sub"]))	{
					$out.='<tr>
						<td></td>
						<td colspan=2><p>'.$this->printResultRow($subRow,1).'</p></td>
					</tr>';
				}
			} else {
				$out.='<tr>
					<td></td>
					<td colspan=2><p>'.$this->pi_getLL("res_otherPageAsWell").'</p></td>
				</tr>';
			}
		}

		return '<table '.$this->conf["tableParams."]["searchRes"].'>'.$out.'</table><BR>';
	}

	/**
	 * Return the icon corresponding to media type $it;
	 */
	function makeItemTypeIcon($it,$alt="",$specRowConf=array())	{
		$spec_flag = 0;

		switch($it)	{
			case 1:
				$icon="html.gif";
			break;
			case 2:
				$icon="pdf.gif";
			break;
			case 3:
				$icon="doc.gif";
			break;
			case 4:
				$icon="txt.gif";
			break;
			default:
				$icon="pages.gif";
				if (is_array($specRowConf["pageIcon."]))	{
					$spec_flag = 1;
					$spec = $this->cObj->IMAGE($specRowConf["pageIcon."]);
				}
			break;
		}
		if ($spec_flag)	{
			return $spec;
		} else {
			$fullPath = t3lib_extMgm::siteRelPath("indexed_search").'pi/res/'.$icon;
			$info = @getimagesize(PATH_site.$fullPath);

			return is_array($info) ? '<img src="'.$fullPath.'" hspace=3 '.$info[3].' title="'.htmlspecialchars($alt).'">' : '';
		}
	}

	/**
	 * Return the rating-HTML code for the result row. This makes use of the $this->firstRow
	 */
	function makeRating($row)	{
/*
				"rank_flag" => "Rang, prioritet",
				"rank_freq" => "Rang, frekvens",
				"rank_first" => "Rang, tæt på toppen",
				"rank_count" => "Rang, antal forekomster",
				"rating" => "Side-rating",
				"hits" => "Side-hits",
				"title" => "Dokumenttitel",
				"crdate" => "Oprettelsesdato",
				"mtime" => "Ændringsdato",
*/
		switch((string)$this->piVars["order"])	{
			case "rank_count":
				return $row["order_val"]." matches";
			break;
			case "rank_first":
				return ceil(t3lib_div::intInRange(255-$row["order_val"],1,255)/255*100)."%";
			break;
			case "rank_flag":
				if ($this->firstRow["order_val2"])	{
					$base=$row["order_val1"]*256; // (3 MSB bit, 224 is highest value of order_val1 currently)
					$freqNumber = $row["order_val2"]/$this->firstRow["order_val2"]*pow(2,13);	// 16-3 MSB = 13
					$total = t3lib_div::intInRange($base+$freqNumber,0,65536);
					#debug($total);
					#debug(log($total)/log(65536)*100,1);
					return ceil(log($total)/log(65536)*100)."%";
				}
			break;
			case "rank_freq":
				$max = 10000;
				$total = t3lib_div::intInRange($row["order_val"],0,$max);
#				debug($total);
				return ceil(log($total)/log($max)*100)."%";
			break;
			case "crdate":
				return $this->cObj->calcAge(time()-$row["item_crdate"],0); // ,$conf["age"]
			break;
			case "mtime":
				return $this->cObj->calcAge(time()-$row["item_mtime"],0); // ,$conf["age"]
			break;
			default:	// fx. title
#				return "[rating...]";
				return "&nbsp;";
			break;
		}
	}

	/**
	 * Returns the resume for the search-result.
	 * If noMarkup is NOT set, then the index_fulltext table is used to select the content of the page, split it with regex to display the search words in the text.
	 */
	function makeDescription($row,$noMarkup=0,$lgd=180)	{
		if ($row["show_resume"])	{
	#debug($row)	;
			if (!$noMarkup)	{
				$markedSW = "";
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'index_fulltext', 'phash='.intval($row['phash']));
				if ($ftdrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$markedSW = $this->markupSWpartsOfString($ftdrow["fulltextdata"]);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}

			return $this->utf8_to_currentCharset(htmlspecialchars(t3lib_div::fixed_lgd(str_replace("&nbsp;"," ",$row["item_description"]),$lgd)).(trim($markedSW)?" ... ".$markedSW:"")).'
				<BR><img src=clear.gif width=1 height=5>';
		} else {
			return '<font color="#666666">'.$this->pi_getLL("res_noResume").'
				<BR><img src=clear.gif width=1 height=5></font>';
		}
	}

	/**
	 * Marks up the search words from $this->sWarr in the $str with a color.
	 */
	function markupSWpartsOfString($str)	{
		$str = str_replace("&nbsp;"," ",t3lib_parsehtml::bidir_htmlspecialchars($str,-1));
		reset($this->sWArr);
#debug($this->sWArr);
		$swForReg=array();
		while(list(,$d)=each($this->sWArr))	{
			$swForReg[]=quotemeta($d["sword"]);
		}
		$regExString = implode("|",$swForReg);

		$noAlph="[^[:alnum:]]";
		$theType = (string)$this->piVars["type"];
		switch($theType)	{
			case "1":
			case "20":
				// Nothing...
			break;
			case "2":
				$regExString = $noAlph."(".$regExString.")";
			break;
			case "3":
				$regExString = "(".$regExString.")".$noAlph;
			break;
			case "10":
			break;
			default:
				$regExString = $noAlph."(".$regExString.")".$noAlph;
			break;
		}
#debug(array($regExString));

		$parts = spliti($regExString,$str,6);
#debug($parts);
		reset($parts);
		$strLen=0;

		$snippets=array();
		while(list($k,$strP)=each($parts))	{
			if ($k+1<count($parts))	{
				$strLen+=strlen($strP);
				$reg=array();
				eregi("^".$regExString,substr($str,$strLen,50),$reg);

				$snippets[] = array(
#					substr($str,$strLen-50,50),
					substr($parts[$k],-50),
					substr($str,$strLen,strlen($reg[0])),
					substr($parts[$k+1],0,50)
#					substr($str,$strLen+strlen($reg[0]),50),
				);

				$strLen+=strlen($reg[0]);
#				debug($reg);
			}
		}

		reset($snippets);
		$content=array();
		while(list(,$d)=each($snippets))	{
			$content[]=htmlspecialchars($d[0]).'<span'.$this->pi_classParam("redMarkup").'>'.htmlspecialchars($d[1]).'</span>'.htmlspecialchars($d[2]);
		}

		return implode(" ... ",$content);
	}

	/**
	 * Returns the title of the search result row
	 */
	function makeTitle($row)	{
		$add="";
		if ($row["item_type"]==2)	{
			$dat = unserialize($row["cHashParams"]);
			$pp=explode("-",$dat["key"]);
			if ($pp[0]!=$pp[1])	{
				$add=", pages ".$dat["key"];
			} else $add=", page ".$pp[0];
		}
		return $this->utf8_to_currentCharset(htmlspecialchars(t3lib_div::fixed_lgd($row["item_title"],50))).$add;
	}

	/**
	 * Returns the info-string in the bottom of the result-row display (size, dates, path)
	 */
	function makeInfo($row)	{
		$lines=array();
		$lines[]=$this->pi_getLL("res_size")." ".t3lib_div::formatSize($row["item_size"])."";
		$lines[]=$this->pi_getLL("res_created")." ".date("d-m-y",$row["item_crdate"])."";
		$lines[]=$this->pi_getLL("res_modified")." ".date("d-m-y H:i",$row["item_mtime"])."";
		$out = implode(" - ",$lines);
		$pathId = $row["data_page_id"]?$row["data_page_id"]:$row["page_id"];
		$pathMP = $row["data_page_id"]?$row["data_page_mp"]:"";
		$out.="<BR>".$this->pi_getLL("res_path")." ".$this->linkPage($pathId,htmlspecialchars($this->getPathFromPageId($pathId,$pathMP)));
		return $out;
	}

	/**
	 * Returns the info-string in the bottom of the result-row display (size, dates, path)
	 */
	function getSpecialConfigForRow($row)	{
		$pathId = $row["data_page_id"]?$row["data_page_id"]:$row["page_id"];
		$pathMP = $row["data_page_id"]?$row["data_page_mp"]:"";

		$rl = $this->getRootLine($pathId,$pathMP);
		$specConf = $this->conf["specConfs."]["0."];
		if (is_array($rl))	{
			reset($rl);
			while(list(,$dat)=each($rl))	{
				if (is_array($this->conf["specConfs."][$dat["uid"]."."]))	{
					$specConf = $this->conf["specConfs."][$dat["uid"]."."];
					break;
				}
			}
		}

		return $specConf;
	}

	/**
	 * Returns the HTML code for language indication.
	 */
	function makeLanguageIndication($row)	{
		if ($row["item_type"]==0)	{
			switch($row["sys_language_uid"])	{
					// OBVIOUSLY this will not work generally. First we need to know WHICH flag is used for WHICH language-uid. This just shows the concept works.
				case 1:
					return '<img src="tslib/media/flags/flag_dk.gif" width="21" height="13" border="0" alt="Danish">';
				break;
				case 2:
					return '<img src="tslib/media/flags/flag_de.gif" width="21" height="13" border="0" alt="German">';
				break;
				default:
				#	return '<img src="tslib/media/flags/flag_uk.gif" width="21" height="13" border="0" alt="English - default">';
				break;
			}
		}
		return "&nbsp;";
	}

	/**
	 * Returns the HTML code for the locking symbol.
	 */
	function makeAccessIndication($id)	{
		if (is_array($this->fe_groups_required[$id]) && count($this->fe_groups_required[$id]))	{
			return '<img src="'.t3lib_extMgm::siteRelPath("indexed_search").'pi/res/locked.gif" width="12" height="15" vspace=5 title="'.sprintf($this->pi_getLL("res_memberGroups"),implode(",",array_unique($this->fe_groups_required[$id]))).'">';
		}
	}

	/**
	 * Links the $str to page $id
	 */
	function linkPage($id,$str,$row=array())	{
#		return $str;
		$urlParameters = unserialize($row["cHashParams"]);
#		$urlParameters["cHash"] = t3lib_div::shortMD5(serialize($row["cHashParams"]));	// not needed - it's there already...
#debug($urlParameters);


			// This will make sure that the path is retrieved if it hasn't been already. Used only for the sake of the domain_record thing...
		if (!is_array($this->domain_records[$id]))	{
			$this->getPathFromPageId($id);
		}

			// If external domain, then link to that:
		if (count($this->domain_records[$id]))	{
			reset($this->domain_records[$id]);
			$firstDom = current($this->domain_records[$id]);
			$scheme = t3lib_div::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://';

			$addParams="";
			if (is_array($urlParameters))	{
				if (count($urlParameters))	{
					reset($urlParameters);
					while(list($k,$v)=each($urlParameters))	{
						$addParams.="&".$k."=".rawurlencode($v);
					}
				}
			}

			return '<a href="'.$scheme.$firstDom.'/index.php?id='.$id.$addParams.'" target="'.$this->conf["search."]["detect_sys_domain_records."]["target"].'">'.htmlspecialchars($str).'</a>';
		} else {
			return $this->pi_linkToPage($str,$id,$this->conf["result_link_target"],$urlParameters);
		}
	}

	/**
	 * Returns the path to the page $id
	 */
	function getRootLine($id,$pathMP="")	{
		$identStr = $id."|".$pathMP;

		if (!isset($this->cache_path[$identStr]))	{
			$this->cache_rl[$identStr] = $GLOBALS["TSFE"]->sys_page->getRootLine($id,$pathMP);
		}
		return $this->cache_rl[$identStr];
	}

	/**
	 * Gets the first sys_domain record for the page, $id
	 */
	function getFirstSysDomainRecordForPage($id)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('domainName', 'sys_domain', 'pid='.intval($id).$this->cObj->enableFields('sys_domain'), '', 'sorting');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return ereg_replace("\/$","",$row["domainName"]);
	}

	/**
	 * Returns the path to the page $id
	 */
	function getPathFromPageId($id,$pathMP="")	{
			// Here I imagine some caching...
		$identStr = $id."|".$pathMP;

		if (!isset($this->cache_path[$identStr]))	{
			$this->fe_groups_required[$id]=array();
			$this->domain_records[$id]=array();
			$rl = $this->getRootLine($id,$pathMP);
			$hitRoot=0;
			$path="";
			if (is_array($rl) && count($rl))	{
				reset($rl);
				while(list($k,$v)=each($rl))	{
						// Check fe_user
					if ($v["fe_group"] && ($v["uid"]==$id || $v["extendToSubpages"]))	{
						$this->fe_groups_required[$id][]=$v["fe_group"];
					}
						// Check sys_domain.
					if ($this->conf["search."]["detect_sys_domain_records"])	{
						$sysDName = $this->getFirstSysDomainRecordForPage($v["uid"]);
						if ($sysDName)	{
							$this->domain_records[$id][]=$sysDName;
#							debug($sysDName);
								// Set path accordingly:
							$path=$sysDName.$path;
							break;
						}
					}

						// Stop, if we find that the current id is the current root page.
					if ($v["uid"]==$GLOBALS["TSFE"]->config["rootLine"][0]["uid"])		{
						break;
					}
					$path="/".$v["title"].$path;
				}
			}

#			$pageRec = $GLOBALS["TSFE"]->sys_page->checkRecord("pages",$id);
#			$path.="/".$pageRec["title"];
			$this->cache_path[$identStr] = $path;

			if (is_array($this->conf["path_stdWrap."]))	{
				$this->cache_path[$identStr] = $this->cObj->stdWrap($this->cache_path[$identStr], $this->conf["path_stdWrap."]);
			}
		}

		return $this->cache_path[$identStr];
	}

	/**
	 * Returns a results browser
	 */
	function pi_list_browseresults($showResultCount=1,$addString="",$addPart="")	{

			// Initializing variables:
		$pointer=$this->piVars["pointer"];
		$count=$this->internal["res_count"];
		$results_at_a_time = t3lib_div::intInRange($this->internal["results_at_a_time"],1,1000);
		$maxPages = t3lib_div::intInRange($this->internal["maxPages"],1,100);
		$max = t3lib_div::intInRange(ceil($count/$results_at_a_time),1,$maxPages);
		$pointer=intval($pointer);
		$links=array();

			// Make browse-table/links:
		if ($pointer>0)	{
			$links[]='<td><p>'.$this->makePointerSelector_link($this->pi_getLL("pi_list_browseresults_prev","< Previous"),$pointer-1).'</p></td>';
		}
		for($a=0;$a<$max;$a++)	{
			$links[]='<td'.($pointer==$a?$this->pi_classParam("browsebox-SCell"):"").'><p>'.$this->makePointerSelector_link(trim($this->pi_getLL("pi_list_browseresults_page","Page")." ".($a+1)),$a).'</p></td>';
		}
		if ($pointer<ceil($count/$results_at_a_time)-1)	{
			$links[]='<td><p>'.$this->makePointerSelector_link($this->pi_getLL("pi_list_browseresults_next","Next >"),$pointer+1).'</p></td>';
		}

		$pR1 = $pointer*$results_at_a_time+1;
		$pR2 = $pointer*$results_at_a_time+$results_at_a_time;
		$sTables = '<DIV'.$this->pi_classParam("browsebox").'>'.
			($showResultCount ? '<P>'.sprintf(
				str_replace("###SPAN_BEGIN###","<span".$this->pi_classParam("browsebox-strong").">",$this->pi_getLL("pi_list_browseresults_displays","Displaying results ###SPAN_BEGIN###%s to %s</span> out of ###SPAN_BEGIN###%s</span>")),
				$pR1,
				min(array($this->internal["res_count"],$pR2)),
				$this->internal["res_count"]
				).$addString.'</P>':''
			).$addPart.
		'<table>
			<tr>'.implode("",$links).'</tr>
		</table></DIV>';
		return $sTables;
	}

	/**
	 * Return the menu of pages used for the selector.
	 */
	function getMenu($id)	{
		if ($this->conf["show."]["LxALLtypes"])	{
			$output = Array();
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid', 'pages', 'pid='.intval($id).$this->cObj->enableFields('pages'), '', 'sorting');
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$output[$row["uid"]]=$GLOBALS["TSFE"]->sys_page->getPageOverlay($row);
			}
			return $output;
		} else {
			return $GLOBALS["TSFE"]->sys_page->getMenu($id);
		}
	}


	/**
	 * Converts the input string from utf-8 to the backend charset.
	 *
	 * @param	string		String to convert (utf-8)
	 * @return	string		Converted string (backend charset if different from utf-8)
	 */
	function utf8_to_currentCharset($str)	{
		return $GLOBALS['TSFE']->csConv($str,'utf-8');
	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/indexed_search/pi/class.tx_indexedsearch.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/indexed_search/pi/class.tx_indexedsearch.php"]);
}

?>
