<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * This script contains the parent class, 'pibase', providing an API with the most basic methods for frontend plugins
 *
 * $Id$
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  131: class tslib_pibase 
 *
 *              SECTION: Init functions
 *  210:     function tslib_pibase()	
 *  224:     function pi_setPiVarDefaults()	
 *
 *              SECTION: Link functions
 *  263:     function pi_getPageLink($id,$target='',$urlParameters=array())	
 *  279:     function pi_linkToPage($str,$id,$target='',$urlParameters=array())	
 *  293:     function pi_linkTP($str,$urlParameters=array(),$cache=0)	
 *  315:     function pi_linkTP_keepPIvars($str,$overrulePIvars=array(),$cache=0,$clearAnyway=0)	
 *  338:     function pi_linkTP_keepPIvars_url($overrulePIvars=array(),$cache=0,$clearAnyway=0)	
 *  355:     function pi_list_linkSingle($str,$uid,$cache=FALSE,$mergeArr=array(),$urlOnly=FALSE)	
 *  383:     function pi_openAtagHrefInJSwindow($str,$winName='',$winParams='width=670,height=500,status=0,menubar=0,scrollbars=1,resizable=1')	
 *
 *              SECTION: Functions for listing, browsing, searching etc.
 *  423:     function pi_list_browseresults($showResultCount=1,$tableParams='')	
 *  492:     function pi_list_searchBox($tableParams='')	
 *  520:     function pi_list_modeSelector($items=array(),$tableParams='')	
 *  558:     function pi_list_makelist($res,$tableParams='')	
 *  593:     function pi_list_row($c)	
 *  605:     function pi_list_header()	
 *
 *              SECTION: Stylesheet, CSS
 *  636:     function pi_getClassName($class)	
 *  648:     function pi_classParam($class)	
 *  662:     function pi_setClassStyle($class,$data,$selector='')	
 *  673:     function pi_wrapInBaseClass($str)	
 *
 *              SECTION: Frontend editing: Edit panel, edit icons
 *  722:     function pi_getEditPanel($row='',$tablename='',$label='',$conf=Array())	
 *  763:     function pi_getEditIcon($content,$fields,$title='',$row='',$tablename='')	
 *
 *              SECTION: Localization, locallang functions
 *  810:     function pi_getLL($key,$alt='',$hsc=FALSE)	
 *  831:     function pi_loadLL()	
 *
 *              SECTION: Database, queries
 *  893:     function pi_list_query($table,$count=0,$addWhere='',$mm_cat='',$groupBy='',$orderBy='',$query='')	
 *  954:     function pi_getRecord($table,$uid,$checkPage=0)	
 *  965:     function pi_getPidList($pid_list,$recursive=0)	
 *  986:     function pi_prependFieldsWithTable($table,$fieldList)	
 * 1003:     function pi_getCategoryTableContents($table,$pid,$addWhere='')	
 *
 *              SECTION: Various
 * 1039:     function pi_isOnlyFields($fList,$lowerThan=-1)	
 * 1059:     function pi_autoCache($inArray)	
 * 1090:     function pi_RTEcssText($str)	
 *
 *              SECTION: FlexForms related functions
 * 1111:     function pi_initPIflexForm()	
 * 1129:     function pi_getFFvalue($T3FlexForm_array,$fieldName,$sheet='sDEF',$lang='lDEF',$value='vDEF')	
 * 1146:     function pi_getFFvalueFromSheetArray($sheetArray,$fieldNameArr,$value)	
 *
 * TOTAL FUNCTIONS: 34
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */






















/**
 * Base class for frontend plugins
 * Most modern frontend plugins are extension classes of this one.
 * This class contains functions which assists these plugins in creating lists, searching, displaying menus, page-browsing (next/previous/1/2/3) and handling links.
 * Functions are all prefixed "pi_" which is reserved for this class. Those functions can of course be overridden in the extension classes (that is the point...)
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */
class tslib_pibase {

		// Reserved variables:
	var $cObj;			// The backReference to the mother cObj object set at call time
	var $prefixId;		// Should be same as classname of the plugin, used for CSS classes, variables
	var $scriptRelPath;	// Path to the plugin class script relative to extension directory, eg. 'pi1/class.tx_newfaq_pi1.php'
	var $extKey;		// Extension key.
	var $piVars = Array (	// This is the incomming array by name $this->prefixId merged between POST and GET, POST taking precedence. Eg. if the class name is 'tx_myext' then the content of this array will be whatever comes into &tx_myext[...]=...
		'pointer' => '',			// Used as a pointer for lists
		'mode' => '',				// List mode
		'sword' => '',				// Search word
		'sort' => '',				// [Sorting column]:[ASC=0/DESC=1]
	);
	var $internal = Array(	// Used internally for general storage of values between methods
		'res_count' => 0,			// Total query count
		'results_at_a_time' => 20,	// pi_list_browseresults(): Show number of results at a time
		'maxPages' => 10,			// pi_list_browseresults(): Max number of 'Page 1 - Page 2 - ...' in the list browser
		'currentRow' => Array(),	// Current result row
		'currentTable' => '',		// Current table
	);

	var $LOCAL_LANG = Array();	// Local Language content
	var $LOCAL_LANG_loaded = 0;	// Flag that tells if the locallang file has been fetch (or tried to be fetched) already.
	var $LLkey='default';		// Pointer to the language to use.
	var $LLtestPrefix='';		// You can set this during development to some value that makes it easy for you to spot all labels that ARe delivered by the getLL function.
	var $LLtestPrefixAlt='';	// Save as LLtestPrefix, but additional prefix for the alternative value in getLL() function calls

	var $pi_isOnlyFields = 'mode,pointer';
	var $pi_alwaysPrev = 0;
	var $pi_lowerThan = 5;
	var $pi_moreParams='';
	var $pi_listFields='*';
	
	var $pi_autoCacheFields=array();
	var $pi_autoCacheEn=0;
	
	var $pi_USER_INT_obj = 0;	// If set, then links are 1) not using cHash and 2) allowing pages to be cached.
	
	/**
	 * Should normally be set in the main function with the TypoScript content passed to the method.
	 * 
	 * $conf[LOCAL_LANG][_key_] is reserved for Local Language overrides.
	 * $conf[userFunc] / $conf[includeLibs]  reserved for setting up the USER / USER_INT object. See TSref
	 */ 
	var $conf = Array();	
	
	// internal, don't mess with...
	var $pi_EPtemp_cObj;	
	var $pi_tmpPageId=0;

	
	














	/***************************
	 * 
	 * Init functions
	 *
	 **************************/

	/**
	 * Class Constructor (true constructor)
	 * Initializes $this->piVars if $this->prefixId is set to any value
	 * Will also set $this->LLkey based on the config.language setting.
	 * 
	 * @return	void		
	 */
	function tslib_pibase()	{
		if ($this->prefixId)	{
			$this->piVars = t3lib_div::GParrayMerged($this->prefixId);
		}
		if ($GLOBALS['TSFE']->config['config']['language'])	{
			$this->LLkey = $GLOBALS['TSFE']->config['config']['language'];
		}
	}

	/**
	 * If internal TypoScript property "_DEFAULT_PI_VARS." is set then it will merge the current $this->piVars array onto these default values.
	 * 
	 * @return	void		
	 */
	function pi_setPiVarDefaults()	{
		if (is_array($this->conf['_DEFAULT_PI_VARS.']))	{
			$this->piVars = t3lib_div::array_merge_recursive_overrule($this->conf['_DEFAULT_PI_VARS.'],is_array($this->piVars)?$this->piVars:array());
		}
	}


	












	/***************************
	 * 
	 * Link functions
	 *
	 **************************/

	/**
	 * Get URL to some page.
	 * Returns the URL to page $id with $target and an array of additional url-parameters, $urlParameters
	 * Simple example: $this->pi_getPageLink(123) to get the URL for page-id 123.
	 * 
	 * The function basically calls $this->cObj->getTypoLink_URL()
	 * 
	 * @param	integer		Page id
	 * @param	string		Target value to use. Affects the &type-value of the URL, defaults to current.
	 * @param	array		Additional URL parameters to set (key/value pairs)
	 * @return	string		The resulting URL
	 * @see pi_linkToPage()
	 */
	function pi_getPageLink($id,$target='',$urlParameters=array())	{
		return $this->cObj->getTypoLink_URL($id,$urlParameters,$target);	// ?$target:$GLOBALS['TSFE']->sPre
	}

	/**
	 * Link a string to some page.
	 * Like pi_getPageLink() but takes a string as first parameter which will in turn be wrapped with the URL including target attribute
	 * Simple example: $this->pi_getPageLink('My link', 123) to get something like <a href="index.php?id=123&type=1">My link</a> (or <a href="123.1.html">My link</a> if simulateStaticDocuments is set)
	 * 
	 * @param	string		The content string to wrap in <a> tags
	 * @param	integer		Page id
	 * @param	string		Target value to use. Affects the &type-value of the URL, defaults to current.
	 * @param	array		Additional URL parameters to set (key/value pairs)
	 * @return	string		The input string wrapped in <a> tags with the URL and target set.
	 * @see pi_getPageLink(), tslib_cObj::getTypoLink()
	 */
	function pi_linkToPage($str,$id,$target='',$urlParameters=array())	{
		return $this->cObj->getTypoLink($str,$id,$urlParameters,$target);	// ?$target:$GLOBALS['TSFE']->sPre
	}

	/**
	 * Link string to the current page.
	 * Returns the $str wrapped in <a>-tags with a link to the CURRENT page, but with $urlParameters set as extra parameters for the page.
	 * 
	 * @param	string		The content string to wrap in <a> tags
	 * @param	array		Array with URL parameters as key/value pairs. They will be "imploded" and added to the list of parameters defined in the plugins TypoScript property "parent.addParams" plus $this->pi_moreParams.
	 * @param	boolean		If $cache is set, the page is asked to be cached by a &cHash value (unless the current plugin using this class is a USER_INT). Otherwise the no_cache-parameter will be a part of the link.
	 * @return	string		The input string wrapped in <a> tags
	 * @see pi_linkTP_keepPIvars(), tslib_cObj::typoLink()
	 */
	function pi_linkTP($str,$urlParameters=array(),$cache=0)	{
		$conf=array();
		$conf['useCacheHash']=$this->pi_USER_INT_obj?0:$cache;
		$conf['no_cache']=$this->pi_USER_INT_obj?0:!$cache;
		$conf['parameter']=$this->pi_tmpPageId ? $this->pi_tmpPageId : $GLOBALS['TSFE']->id;
		$conf['additionalParams']=$this->conf['parent.']['addParams'].t3lib_div::implodeArrayForUrl('',$urlParameters,'',1).$this->pi_moreParams;
		
		return $this->cObj->typoLink($str, $conf);
	}

	/**
	 * Link a string to the current page while keeping currently set values in piVars.
	 * Like pi_linkTP, but $urlParameters is by default set to $this->piVars with $overrulePIvars overlaid.
	 * This means any current entries from this->piVars are passed on (except the key "DATA" which will be unset before!) and entries in $overrulePIvars will OVERRULE the current in the link.
	 * 
	 * @param	string		The content string to wrap in <a> tags
	 * @param	array		Array of values to override in the current piVars. Contrary to pi_linkTP the keys in this array must correspond to the real piVars array and therefore NOT be prefixed with the $this->prefixId string. Further, if a value is a blank string it means the piVar key will not be a part of the link (unset)
	 * @param	boolean		If $cache is set, the page is asked to be cached by a &cHash value (unless the current plugin using this class is a USER_INT). Otherwise the no_cache-parameter will be a part of the link.
	 * @param	boolean		If set, then the current values of piVars will NOT be preserved anyways... Practical if you want an easy way to set piVars without having to worry about the prefix, "tx_xxxxx[]"
	 * @return	string		The input string wrapped in <a> tags
	 * @see pi_linkTP()
	 */
	function pi_linkTP_keepPIvars($str,$overrulePIvars=array(),$cache=0,$clearAnyway=0)	{
		if (is_array($this->piVars) && is_array($overrulePIvars) && !$clearAnyway)	{
			$piVars = $this->piVars;
			unset($piVars['DATA']);
			$overrulePIvars = t3lib_div::array_merge_recursive_overrule($piVars,$overrulePIvars);
			if ($this->pi_autoCacheEn)	{
				$cache = $this->pi_autoCache($overrulePIvars);
			}
		}
		$res = $this->pi_linkTP($str,Array($this->prefixId=>$overrulePIvars),$cache);
		return $res;
	}

	/**
	 * Get URL to the current page while keeping currently set values in piVars.
	 * Same as pi_linkTP_keepPIvars but returns only the URL from the link.
	 * 
	 * @param	array		See pi_linkTP_keepPIvars
	 * @param	boolean		See pi_linkTP_keepPIvars
	 * @param	boolean		See pi_linkTP_keepPIvars
	 * @return	string		The URL ($this->cObj->lastTypoLinkUrl)
	 * @see pi_linkTP_keepPIvars()
	 */
	function pi_linkTP_keepPIvars_url($overrulePIvars=array(),$cache=0,$clearAnyway=0)	{
		$this->pi_linkTP_keepPIvars('|',$overrulePIvars,$cache,$clearAnyway);
		return $this->cObj->lastTypoLinkUrl;
	}

	/**
	 * Wraps the $str in a link to a single display of the record (using piVars[showUid])
	 * Uses pi_linkTP for the linking
	 * 
	 * @param	string		The content string to wrap in <a> tags
	 * @param	integer		UID of the record for which to display details (basically this will become the value of [showUid]
	 * @param	boolean		See pi_linkTP_keepPIvars
	 * @param	array		Array of values to override in the current piVars. Same as $overrulePIvars in pi_linkTP_keepPIvars
	 * @param	boolean		If true, only the URL is returned, not a full link
	 * @return	string		The input string wrapped in <a> tags
	 * @see pi_linkTP(), pi_linkTP_keepPIvars()
	 */
	function pi_list_linkSingle($str,$uid,$cache=FALSE,$mergeArr=array(),$urlOnly=FALSE)	{
		if ($this->prefixId)	{
			if ($cache)	{
				$overrulePIvars=$uid?array('showUid'=>$uid):Array();
				$overrulePIvars=array_merge($overrulePIvars,$mergeArr);
				$str = $this->pi_linkTP($str,Array($this->prefixId=>$overrulePIvars),$cache);
			} else {
				$overrulePIvars=array('showUid'=>$uid?$uid:'');
				$overrulePIvars=array_merge($overrulePIvars,$mergeArr);
				$str = $this->pi_linkTP_keepPIvars($str,$overrulePIvars,$cache);
			}
			
				// If urlOnly flag, return only URL as it has recently be generated.
			if ($urlOnly)	{
				$str = $this->cObj->lastTypoLinkUrl;
			}
		}
		return $str;
	}

	/**
	 * Will change the href value from <a> in the input string and turn it into an onclick event that will open a new window with the URL
	 * 
	 * @param	string		The string to process. This should be a string already wrapped/including a <a> tag which will be modified to contain an onclick handler. Only the attributes "href" and "onclick" will be left.
	 * @param	string		Window name for the pop-up window
	 * @param	string		Window parameters, see the default list for inspiration
	 * @return	string		The processed input string, modified IF a <a> tag was found
	 */
	function pi_openAtagHrefInJSwindow($str,$winName='',$winParams='width=670,height=500,status=0,menubar=0,scrollbars=1,resizable=1')	{
		if (eregi('(.*)(<a[^>]*>)(.*)',$str,$match))	{
			$aTagContent = t3lib_div::get_tag_attributes($match[2]);
			$match[2]='<a href="#" onclick="'.
				htmlspecialchars('vHWin=window.open(\''.$aTagContent['href'].'\',\''.($winName?$winName:md5($aTagContent['href'])).'\',\''.$winParams.'\');vHWin.focus();return false;').
				'">';
			$str=$match[1].$match[2].$match[3];
		}
		return $str;
	}















	/***************************
	 * 
	 * Functions for listing, browsing, searching etc.
	 *
	 **************************/

	/**
	 * Returns a results browser. This means a bar of page numbers plus a "previous" and "next" link. For each entry in the bar the piVars "pointer" will be pointing to the "result page" to show.
	 * Using $this->piVars['pointer'] as pointer to the page to display
	 * Using $this->internal['res_count'], $this->internal['results_at_a_time'] and $this->internal['maxPages'] for count number, how many results to show and the max number of pages to include in the browse bar.
	 * 
	 * @param	boolean		If set (default) the text "Displaying results..." will be show, otherwise not.
	 * @param	string		Attributes for the table tag which is wrapped around the table cells containing the browse links
	 * @return	string		Output HTML, wrapped in <div>-tags with a class attribute
	 */
	function pi_list_browseresults($showResultCount=1,$tableParams='')	{
			
			// Initializing variables:
		$pointer=$this->piVars['pointer'];
		$count=$this->internal['res_count'];
		$results_at_a_time = t3lib_div::intInRange($this->internal['results_at_a_time'],1,1000);
		$maxPages = t3lib_div::intInRange($this->internal['maxPages'],1,100);
		$max = t3lib_div::intInRange(ceil($count/$results_at_a_time),1,$maxPages);
		$pointer=intval($pointer);
		$links=array();

			// Make browse-table/links:	
		if ($this->pi_alwaysPrev>=0)	{
			if ($pointer>0)	{
				$links[]='
					<td nowrap="nowrap"><p>'.$this->pi_linkTP_keepPIvars($this->pi_getLL('pi_list_browseresults_prev','< Previous',TRUE),array('pointer'=>($pointer-1?$pointer-1:'')),0).'</p></td>';
			} elseif ($this->pi_alwaysPrev)	{
				$links[]='
					<td nowrap="nowrap"><p>'.$this->pi_getLL('pi_list_browseresults_prev','< Previous',TRUE).'</p></td>';
			}
		}
		for($a=0;$a<$max;$a++)	{
			$links[]='
					<td'.($pointer==$a?$this->pi_classParam('browsebox-SCell'):'').' nowrap="nowrap"><p>'.
				$this->pi_linkTP_keepPIvars(trim($this->pi_getLL('pi_list_browseresults_page','Page',TRUE).' '.($a+1)),array('pointer'=>($a?$a:'')),$this->pi_isOnlyFields($this->pi_isOnlyFields)).
				'</p></td>';
		}
		if ($pointer<ceil($count/$results_at_a_time)-1)	{
			$links[]='
					<td nowrap="nowrap"><p>'.
				$this->pi_linkTP_keepPIvars($this->pi_getLL('pi_list_browseresults_next','Next >',TRUE),array('pointer'=>$pointer+1)).
				'</p></td>';
		}
		
		$pR1 = $pointer*$results_at_a_time+1;
		$pR2 = $pointer*$results_at_a_time+$results_at_a_time;
		$sTables = '
		
		<!--
			List browsing box:
		-->	
		<div'.$this->pi_classParam('browsebox').'>'.
			($showResultCount ? '
			<p>'.sprintf(
				str_replace('###SPAN_BEGIN###','<span'.$this->pi_classParam('browsebox-strong').'>',$this->pi_getLL('pi_list_browseresults_displays','Displaying results ###SPAN_BEGIN###%s to %s</span> out of ###SPAN_BEGIN###%s</span>')),
				$pR1,
				min(array($this->internal['res_count'],$pR2)),
				$this->internal['res_count']
				).'</p>':''
			).
		'
		
			<'.trim('table '.$tableParams).'>
				<tr>
					'.implode('',$links).'
				</tr>
			</table>
		</div>';
		
		return $sTables;
	}

	/**
	 * Returns a Search box, sending search words to piVars "sword" and setting the "no_cache" parameter as well in the form.
	 * Submits the search request to the current REQUEST_URI
	 * 
	 * @param	string		Attributes for the table tag which is wrapped around the table cells containing the search box
	 * @return	string		Output HTML, wrapped in <div>-tags with a class attribute
	 */
	function pi_list_searchBox($tableParams='')	{
			// Search box design:
		$sTables = '
		
		<!--
			List search box:
		-->	
		<div'.$this->pi_classParam('searchbox').'>
			<form action="'.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')).'" method="post" style="margin: 0 0 0 0;">
			<'.trim('table '.$tableParams).'>
				<tr>
					<td><input type="text" name="'.$this->prefixId.'[sword]" value="'.htmlspecialchars($this->piVars['sword']).'"'.$this->pi_classParam('searchbox-sword').' /></td>
					<td><input type="submit" value="'.$this->pi_getLL('pi_list_searchBox_search','Search',TRUE).'"'.$this->pi_classParam('searchbox-button').' /><input type="hidden" name="no_cache" value="1" /></td>
				</tr>
			</table>
			</form>
		</div>';
		
		return $sTables;
	}

	/**
	 * Returns a mode selector; a little menu in a table normally put in the top of the page/list.
	 * 
	 * @param	array		Key/Value pairs for the menu; keys are the piVars[mode] values and the "values" are the labels for them.
	 * @param	string		Attributes for the table tag which is wrapped around the table cells containing the menu
	 * @return	string		Output HTML, wrapped in <div>-tags with a class attribute
	 */
	function pi_list_modeSelector($items=array(),$tableParams='')	{
		$cells=array();
		reset($items);
		while(list($k,$v)=each($items))	{
			$cells[]='
					<td'.($this->piVars['mode']==$k?$this->pi_classParam('modeSelector-SCell'):'').'><p>'.
				$this->pi_linkTP_keepPIvars(htmlspecialchars($v),array('mode'=>$k),$this->pi_isOnlyFields($this->pi_isOnlyFields)).
				'</p></td>';
		}
		
		$sTables = '
		
		<!--
			Mode selector (menu for list):
		-->	
		<div'.$this->pi_classParam('modeSelector').'>
			<'.trim('table '.$tableParams).'>
				<tr>
					'.implode('',$cells).'
				</tr>
			</table>
		</div>';
		
		return $sTables;
	}

	/**
	 * Returns the list of items based on the input MySQL result pointer
	 * For each result row the internal var, $this->internal['currentRow'], is set with the row returned.
	 * $this->pi_list_header() makes the header row for the list
	 * $this->pi_list_row() is used for rendering each row
	 * Notice that these two functions are typically ALWAYS defined in the extension class of the plugin since they are directly concerned with the specific layout for that plugins purpose.
	 * 
	 * @param	pointer		Result pointer to a MySQL result which can be traversed.
	 * @param	string		Attributes for the table tag which is wrapped around the table rows containing the list
	 * @return	string		Output HTML, wrapped in <div>-tags with a class attribute
	 * @see pi_list_row(), pi_list_header()
	 */
	function pi_list_makelist($res,$tableParams='')	{
			// Make list table header:
		$tRows=array();
		$this->internal['currentRow']='';
		$tRows[]=$this->pi_list_header();

			// Make list table rows
		$c=0;
		while($this->internal['currentRow'] = mysql_fetch_assoc($res))	{
			$tRows[]=$this->pi_list_row($c);
			$c++;
		}

		$out = '
		
		<!--
			Record list:
		-->	
		<div'.$this->pi_classParam('listrow').'>
			<'.trim('table '.$tableParams).'>
				'.implode('',$tRows).'
			</table>
		</div>';
		
		return $out;
	}

	/**
	 * Returns a list row. Get data from $this->internal['currentRow'];
	 * (Dummy)
	 * Notice: This function should ALWAYS be defined in the extension class of the plugin since it is directly concerned with the specific layout of the listing for your plugins purpose.
	 * 
	 * @param	integer		Row counting. Starts at 0 (zero). Used for alternating class values in the output rows.
	 * @return	string		HTML output, a table row with a class attribute set (alternative based on odd/even rows)
	 */
	function pi_list_row($c)	{
		// Dummy
		return '<tr'.($c%2 ? $this->pi_classParam('listrow-odd') : '').'><td><p>[dummy row]</p></td></tr>';
	}

	/**
	 * Returns a list header row.
	 * (Dummy)
	 * Notice: This function should ALWAYS be defined in the extension class of the plugin since it is directly concerned with the specific layout of the listing for your plugins purpose.
	 * 
	 * @return	string		HTML output, a table row with a class attribute set
	 */
	function pi_list_header()	{
		return '<tr'.$this->pi_classParam('listrow-header').'><td><p>[dummy header row]</p></td></tr>';
	}















	/***************************
	 * 
	 * Stylesheet, CSS
	 *
	 **************************/


	/**
	 * Returns a class-name prefixed with $this->prefixId and with all underscores substituted to dashes (-)
	 * 
	 * @param	string		The class name (or the END of it since it will be prefixed by $this->prefixId.'-')
	 * @return	string		The combined class name (with the correct prefix)
	 */
	function pi_getClassName($class)	{
		return str_replace('_','-',$this->prefixId).($this->prefixId?'-':'').$class;
	}

	/**
	 * Returns the class-attribute with the correctly prefixed classname
	 * Using pi_getClassName()
	 * 
	 * @param	string		The class name (suffix)
	 * @return	string		A "class" attribute with value and a single space char before it.
	 * @see pi_getClassName()
	 */
	function pi_classParam($class)	{
		return ' class="'.$this->pi_getClassName($class).'"';
	}

	/**
	 * Sets CSS style-data for the $class-suffix (prefixed by pi_getClassName())
	 * 
	 * @param	string		$class: Class suffix, see pi_getClassName
	 * @param	string		$data: CSS data
	 * @param	string		If $selector is set to any CSS selector, eg 'P' or 'H1' or 'TABLE' then the style $data will regard those HTML-elements only
	 * @return	void		
	 * @depreciated		I think this function should not be used (and probably isn't used anywhere). It was a part of a concept which was left behind quite quickly.
	 * @private
	 */
	function pi_setClassStyle($class,$data,$selector='')	{
		$GLOBALS['TSFE']->setCSS($this->pi_getClassName($class).($selector?' '.$selector:''),'.'.$this->pi_getClassName($class).($selector?' '.$selector:'').' {'.$data.'}');
	}

	/**
	 * Wraps the input string in a <div> tag with the class attribute set to the prefixId.
	 * All content returned from your plugins should be returned through this function so all content from your plugin is encapsulated in a <div>-tag nicely identifying the content of your plugin.
	 * 
	 * @param	string		HTML content to wrap in the div-tags with the "main class" of the plugin
	 * @return	string		HTML content wrapped, ready to return to the parent object.
	 */
	function pi_wrapInBaseClass($str)	{
		return '
				

	<!--
	
		BEGIN: Content of extension "'.$this->extKey.'", plugin "'.$this->prefixId.'"
	
	-->	
	<div class="'.str_replace('_','-',$this->prefixId).'">
		'.$str.'
	</div>
	<!-- END: Content of extension "'.$this->extKey.'", plugin "'.$this->prefixId.'" -->

	';
	}

















	/***************************
	 * 
	 * Frontend editing: Edit panel, edit icons
	 *
	 **************************/

	/**
	 * Returns the Backend User edit panel for the $row from $tablename
	 * 
	 * @param	array		Record array.
	 * @param	string		Table name
	 * @param	string		A label to show with the panel.
	 * @param	array		TypoScript parameters to pass along to the EDITPANEL content Object that gets rendered. The property "allow" WILL get overridden/set though.
	 * @return	string		Returns false/blank if no BE User login and of course if the panel is not shown for other reasons. Otherwise the HTML for the panel (a table).
	 * @see tslib_cObj::EDITPANEL()
	 */
	function pi_getEditPanel($row='',$tablename='',$label='',$conf=Array())	{
		$panel='';
		if (!$row || !$tablename)	{
			$row = $this->internal['currentRow'];
			$tablename = $this->internal['currentTable'];
		}
		
		if ($GLOBALS['TSFE']->beUserLogin)	{
				// Create local cObj if not set:
			if (!is_object($this->pi_EPtemp_cObj))	{
				$this->pi_EPtemp_cObj = t3lib_div::makeInstance('tslib_cObj');
				$this->pi_EPtemp_cObj->setParent($this->cObj->data,$this->cObj->currentRecord);
			}
			
				// Initialize the cObj object with current row
			$this->pi_EPtemp_cObj->start($row,$tablename);
			
				// Setting TypoScript values in the $conf array. See documentation in TSref for the EDITPANEL cObject.
			$conf['allow'] = 'edit,new,delete,move,hide';
			$panel = $this->pi_EPtemp_cObj->cObjGetSingle('EDITPANEL',$conf,'editpanel');
		}
		
		if ($panel)	{
			if ($label)	{
				return '<!-- BEGIN: EDIT PANEL --><table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td valign="top">'.$label.'</td><td valign="top" align="right">'.$panel.'</td></tr></table><!-- END: EDIT PANEL -->';
			} else return '<!-- BEGIN: EDIT PANEL -->'.$panel.'<!-- END: EDIT PANEL -->';
		} else return $label;
	}

	/**
	 * Adds edit-icons to the input content.
	 * tslib_cObj::editIcons used for rendering
	 * 
	 * @param	string		HTML content to add icons to. The icons will be put right after the last content part in the string (that means before the ending series of HTML tags)
	 * @param	string		The list of fields to edit when the icon is clicked.
	 * @param	string		Title for the edit icon.
	 * @param	array		Table record row
	 * @param	string		Table name
	 * @return	string		The processed content
	 * @see tslib_cObj::editIcons()
	 */
	function pi_getEditIcon($content,$fields,$title='',$row='',$tablename='')	{
		if ($GLOBALS['TSFE']->beUserLogin){
			if (!$row || !$tablename)	{
				$row = $this->internal['currentRow'];
				$tablename = $this->internal['currentTable'];
			}
			$conf=array(
				'beforeLastTag'=>1,
				'iconTitle' => $title
			);
			$content=$this->cObj->editIcons($content,$tablename.':'.$fields,$conf,$tablename.':'.$row['uid'],$row,'&viewUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')));
		}	
		return $content;
	}

















	/***************************
	 * 
	 * Localization, locallang functions
	 *
	 **************************/


	/**
	 * Returns the localized label of the LOCAL_LANG key, $key
	 * Notice that for debugging purposes prefixes for the output values can be set with the internal vars ->LLtestPrefixAlt and ->LLtestPrefix
	 * 
	 * @param	string		The key from the LOCAL_LANG array for which to return the value.
	 * @param	string		Alternative string to return IF no value is found set for the key, neither for the local language nor the default.
	 * @param	boolean		If true, the output label is passed through htmlspecialchars()
	 * @return	string		The value from LOCAL_LANG.
	 */
	function pi_getLL($key,$alt='',$hsc=FALSE)	{
		if (isset($this->LOCAL_LANG[$this->LLkey][$key]))	{
			$word = $GLOBALS['TSFE']->csConv($this->LOCAL_LANG[$this->LLkey][$key]);
		} elseif (isset($this->LOCAL_LANG['default'][$key]))	{
			$word = $this->LOCAL_LANG['default'][$key];
		} else {
			$word = $this->LLtestPrefixAlt.$alt;
		}

		$output = $this->LLtestPrefix.$word;
		if ($hsc)	$output = htmlspecialchars($output);

		return $output;
	}
	
	/**
	 * Loads local-language values by looking for a "locallang.php" file in the plugin class directory ($this->scriptRelPath) and if found includes it.
	 * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.php" file.
	 * 
	 * @return	void		
	 */
	function pi_loadLL()	{
		if (!$this->LOCAL_LANG_loaded && $this->scriptRelPath)	{
			$basePath = t3lib_extMgm::siteRelPath($this->extKey).dirname($this->scriptRelPath).'/locallang.php';
			if (@is_file($basePath))	{
				include('./'.$basePath);
				$this->LOCAL_LANG = $LOCAL_LANG;
				if (is_array($this->conf['_LOCAL_LANG.']))	{
					reset($this->conf['_LOCAL_LANG.']);
					while(list($k,$lA)=each($this->conf['_LOCAL_LANG.']))	{
						if (is_array($lA))	{
							$k = substr($k,0,-1);
							$this->LOCAL_LANG[$k] = t3lib_div::array_merge_recursive_overrule(is_array($this->LOCAL_LANG[$k])?$this->LOCAL_LANG[$k]:array(), $lA);
						}
					}
				}
			}
		}
		$this->LOCAL_LANG_loaded = 1;
	}
	






















	/***************************
	 * 
	 * Database, queries
	 *
	 **************************/

	/**
	 * Makes a standard query for listing of records based on standard input vars from the 'browser' ($this->internal['results_at_a_time'] and $this->piVars['pointer']) and 'searchbox' ($this->piVars['sword'] and $this->internal['searchFieldList'])
	 * Set $count to 1 if you wish to get a count(*) query for selecting the number of results.
	 * Notice that the query will use $this->conf['pidList'] and $this->conf['recursive'] to generate a PID list within which to search for records.
	 * 
	 * @param	string		The table name to make the query for.
	 * @param	boolean		If set, you will get a "count(*)" query back instead of field selecting
	 * @param	string		Additional WHERE clauses (should be starting with " AND ....")
	 * @param	mixed		If an array, then it must contain the keys "table", "mmtable" and (optionally) "catUidList" defining a table to make a MM-relation to in the query (based on fields uid_local and uid_foreign). If not array, the query will be a plain query looking up data in only one table.
	 * @param	string		If set, this is added as a " GROUP BY ...." part of the query.
	 * @param	string		If set, this is added as a " ORDER BY ...." part of the query. The default is that an ORDER BY clause is made based on $this->internal['orderBy'] and $this->internal['descFlag'] where the orderBy field must be found in $this->internal['orderByList']
	 * @param	string		If set, this is taken as the first part of the query instead of what is created internally. Basically this should be a query starting with "FROM [table] WHERE ... AND ...". The $addWhere clauses and all the other stuff is still added. Only the tables and PID selecting clauses are bypassed
	 * @return	string		The query build.
	 */
	function pi_list_query($table,$count=0,$addWhere='',$mm_cat='',$groupBy='',$orderBy='',$query='')	{
			// Begin Query:
		if (!$query)	{
				// Fetches the list of PIDs to select from. 
				// TypoScript property .pidList is a comma list of pids. If blank, current page id is used.
				// TypoScript property .recursive is a int+ which determines how many levels down from the pids in the pid-list subpages should be included in the select.
			$pidList = $this->pi_getPidList($this->conf['pidList'],$this->conf['recursive']);
			if (is_array($mm_cat))	{
				$query='FROM '.$table.','.$mm_cat['table'].','.$mm_cat['mmtable'].chr(10).
						' WHERE '.$table.'.uid='.$mm_cat['mmtable'].'.uid_local AND '.$mm_cat['table'].'.uid='.$mm_cat['mmtable'].'.uid_foreign '.chr(10).
						(strcmp($mm_cat['catUidList'],'')?' AND '.$mm_cat['table'].'.uid IN ('.$mm_cat['catUidList'].')':'').chr(10).
						' AND '.$table.'.pid IN ('.$pidList.')'.chr(10).
						$this->cObj->enableFields($table).chr(10);	// This adds WHERE-clauses that ensures deleted, hidden, starttime/endtime/access records are NOT selected, if they should not! Almost ALWAYS add this to your queries!
			} else {
				$query='FROM '.$table.' WHERE pid IN ('.$pidList.')'.chr(10).
						$this->cObj->enableFields($table).chr(10);	// This adds WHERE-clauses that ensures deleted, hidden, starttime/endtime/access records are NOT selected, if they should not! Almost ALWAYS add this to your queries!
			}
		}
						
			// Add '$addWhere'
		if ($addWhere)	{$query.=' '.$addWhere.chr(10);}

			// Search word:				
		if ($this->piVars['sword'] && $this->internal['searchFieldList'])	{
			$query.=$this->cObj->searchWhere($this->piVars['sword'],$this->internal['searchFieldList'],$table).chr(10);
		}

		if ($count) {
			$query = 'SELECT count(*) '.chr(10).$query;
		} else {
			if ($groupBy)	$query.=' '.$groupBy;
				// Order by data:
			if ($orderBy)	{
				$query.=' '.$orderBy;
			} else {
				if (t3lib_div::inList($this->internal['orderByList'],$this->internal['orderBy']))	{
					$query.= ' ORDER BY '.$table.'.'.$this->internal['orderBy'].($this->internal['descFlag']?' DESC':'').chr(10);
				}
			}
			
				// Limit data:
			$pointer=$this->piVars['pointer'];
			$pointer=intval($pointer);
			$results_at_a_time = t3lib_div::intInRange($this->internal['results_at_a_time'],1,1000);
			$query.= ' LIMIT '.($pointer*$results_at_a_time).','.$results_at_a_time.chr(10);

				// Add 'SELECT'			
			$query = 'SELECT '.$this->pi_prependFieldsWithTable($table,$this->pi_listFields).' '.chr(10).$query;
		}
		return $query;
	}

	/**
	 * Returns the row $uid from $table
	 * (Simply calling $GLOBALS['TSFE']->sys_page->checkRecord())
	 * 
	 * @param	string		The table name
	 * @param	integer		The uid of the record from the table
	 * @param	boolean		If $checkPage is set, it's required that the page on which the record resides is accessible
	 * @return	array		If record is found, an array. Otherwise false.
	 */
	function pi_getRecord($table,$uid,$checkPage=0)	{
		return $GLOBALS['TSFE']->sys_page->checkRecord($table,$uid,$checkPage);	
	}
	
	/**
	 * Returns a commalist of page ids for a query (eg. 'WHERE pid IN (...)')
	 * 
	 * @param	string		$pid_list is a comma list of page ids (if empty current page is used)
	 * @param	integer		$recursive is an integer >=0 telling how deep to dig for pids under each entry in $pid_list
	 * @return	string		List of PID values (comma separated)
	 */
	function pi_getPidList($pid_list,$recursive=0)	{
		if (!strcmp($pid_list,''))	$pid_list = $GLOBALS['TSFE']->id;
		$recursive = t3lib_div::intInRange($recursive,0);
		
		$pid_list_arr = array_unique(t3lib_div::trimExplode(',',$pid_list,1));
		$pid_list='';
		reset($pid_list_arr);
		while(list(,$val)=each($pid_list_arr))	{	
			$val = t3lib_div::intInRange($val,0);
			if ($val)	$pid_list.=$val.','.$this->cObj->getTreeList($val,$recursive);
		}
		return ereg_replace(',$','',$pid_list);
	}
	
	/**
	 * Having a comma list of fields ($fieldList) this is prepended with the $table.'.' name
	 * 
	 * @param	string		Table name to prepend
	 * @param	string		List of fields where each element will be prepended with the table name given.
	 * @return	string		List of fields processed.
	 */
	function pi_prependFieldsWithTable($table,$fieldList)	{
		$list=t3lib_div::trimExplode(',',$fieldList,1);
		$return=array();
		while(list(,$listItem)=each($list))	{
			$return[]=$table.'.'.$listItem;
		}
		return implode(',',$return);
	}

	/**
	 * Will select all records from the "category table", $table, and return them in an array.
	 * 
	 * @param	string		The name of the category table to select from.
	 * @param	integer		The page from where to select the category records.
	 * @param	string		Additional where clauses (basically the end of the query - could include a ORDER BY clause)
	 * @return	array		The array with the category records in.
	 */
	function pi_getCategoryTableContents($table,$pid,$addWhere='')	{
		$query = 'SELECT * FROM '.$table.' WHERE pid='.intval($pid).$this->cObj->enableFields($table).' '.$addWhere;

		$outArr = array();
		$res = mysql(TYPO3_db,$query);
		while($row=mysql_fetch_assoc($res))	{
			$outArr[$row['uid']]=$row;
		}
		return $outArr;
	}







	




	/***************************
	 * 
	 * Various
	 *
	 **************************/
	
	/**
	 * Returns true if the piVars array has ONLY those fields entered that is set in the $fList (commalist) AND if none of those fields value is greater than $lowerThan field if they are integers.
	 * Notice that this function will only work as long as values are integers.
	 * 
	 * @param	string		List of fields (keys from piVars) to evaluate on
	 * @param	integer		Limit for the values.
	 * @return	boolean		Returns true (1) if conditions are met.
	 */
	function pi_isOnlyFields($fList,$lowerThan=-1)	{
		$lowerThan = $lowerThan==-1 ? $this->pi_lowerThan : $lowerThan;

		$fList = t3lib_div::trimExplode(',',$fList,1);
		$tempPiVars = $this->piVars;
		while(list(,$k)=each($fList))	{
			if (!t3lib_div::testInt($tempPiVars[$k]) || $tempPiVars[$k]<$lowerThan)		unset($tempPiVars[$k]);
		}
		if (!count($tempPiVars))	return 1;
	}
	
	/**
	 * Returns true if the array $inArray contains only values allowed to be cached based on the configuration in $this->pi_autoCacheFields
	 * Used by ->pi_linkTP_keepPIvars
	 * This is an advanced form of evaluation of whether a URL should be cached or not.
	 * 
	 * @param	array		An array with piVars values to evaluate
	 * @return	boolean		Returns true (1) if conditions are met.
	 * @see pi_linkTP_keepPIvars()
	 */
	function pi_autoCache($inArray)	{
		if (is_array($inArray))	{
			reset($inArray);
			while(list($fN,$fV)=each($inArray))	{
				if (!strcmp($inArray[$fN],''))	{
					unset($inArray[$fN]);
				} elseif (is_array($this->pi_autoCacheFields[$fN]))	{
					if (is_array($this->pi_autoCacheFields[$fN]['range'])
							 && intval($inArray[$fN])>=intval($this->pi_autoCacheFields[$fN]['range'][0]) 
							 && intval($inArray[$fN])<=intval($this->pi_autoCacheFields[$fN]['range'][1]))	{
								unset($inArray[$fN]);
					}
					if (is_array($this->pi_autoCacheFields[$fN]['list'])
							 && in_array($inArray[$fN],$this->pi_autoCacheFields[$fN]['list']))	{
								unset($inArray[$fN]);
					}
				}
			}
		}
		if (!count($inArray))	return 1;
	}

	/**
	 * Will process the input string with the parseFunc function from tslib_cObj based on configuration set in "lib.parseFunc_RTE" in the current TypoScript template.
	 * This is useful for rendering of content in RTE fields where the transformation mode is set to "ts_css" or so.
	 * Notice that this requires the use of "css_styled_content" to work right.
	 * 
	 * @param	string		The input text string to process
	 * @return	string		The processed string
	 * @see tslib_cObj::parseFunc()
	 */
	function pi_RTEcssText($str)	{
		$parseFunc = $GLOBALS['TSFE']->tmpl->setup['lib.']['parseFunc_RTE.'];
		if (is_array($parseFunc))	$str = $this->cObj->parseFunc($str, $parseFunc);
		return $str;
	}
	




	/*******************************
	 *
	 * FlexForms related functions
	 *
	 *******************************/

	/**
	 * Converts $this->cObj->data['pi_flexform'] from XML string to flexForm array.
	 * 
	 * @return	void		
	 */
	function pi_initPIflexForm()	{
			// Converting flexform data into array:
		if (!is_array($this->cObj->data['pi_flexform']) && $this->cObj->data['pi_flexform'])	{
			$this->cObj->data['pi_flexform'] = t3lib_div::xml2array($this->cObj->data['pi_flexform']);
			if (!is_array($this->cObj->data['pi_flexform']))	$this->cObj->data['pi_flexform']=array();
		}
	}
	
	/**
	 * Return value from somewhere inside a FlexForm structure
	 * 
	 * @param	array		FlexForm data
	 * @param	string		Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
	 * @param	string		Sheet pointer, eg. "sDEF"
	 * @param	string		Language pointer, eg. "lDEF"
	 * @param	string		Value pointer, eg. "vDEF"
	 * @return	string		The content.
	 */
	function pi_getFFvalue($T3FlexForm_array,$fieldName,$sheet='sDEF',$lang='lDEF',$value='vDEF')	{
		$sheetArray = $T3FlexForm_array['data'][$sheet][$lang];
		if (is_array($sheetArray))	{
			return $this->pi_getFFvalueFromSheetArray($sheetArray,explode('/',$fieldName),$value);
		}
	}

	/**
	 * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
	 * 
	 * @param	array		Multidimensiona array, typically FlexForm contents
	 * @param	array		Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
	 * @param	string		Value for outermost key, typ. "vDEF" depending on language.
	 * @return	mixed		The value, typ. string.
	 * @access private
	 * @see pi_getFFvalue()
	 */
	function pi_getFFvalueFromSheetArray($sheetArray,$fieldNameArr,$value)	{
	
		$tempArr=$sheetArray;
		foreach($fieldNameArr as $k => $v)	{
			if (t3lib_div::testInt($v))	{
				if (is_array($tempArr))	{
					$c=0;
					foreach($tempArr as $values)	{
						if ($c==$v)	{
							debug($values);
							$tempArr=$values;
							break;
						}
						$c++;
					}
				}
			} else {
				$tempArr = $tempArr[$v];
			}
		}
		return $tempArr[$value];
	}
}

// NO extension of class - does not make sense here.
?>