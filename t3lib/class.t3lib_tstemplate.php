<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * Class with template object that is responsible for generating the template
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  103: class t3lib_TStemplate	
 *  203:     function init()	
 *  241:     function getCurrentPageData()	
 *  258:     function matching($cc)	
 *  282:     function start($theRootLine)	
 *
 *              SECTION: Fetching TypoScript code text for the Template Hierarchy
 *  391:     function runThroughTemplates($theRootLine,$start_template_uid=0)	
 *  436:     function processTemplate($row, $idList,$pid,$templateID='',$templateParent='')	
 *  549:     function includeStaticTypoScriptSources($idList,$templateID,$pid,$row)	
 *  610:     function addExtensionStatics($idList,$templateID,$pid,$row) 
 *  643:     function prependStaticExtra($subrow)	
 *
 *              SECTION: Parsing TypoScript code text from Template Records into PHP array
 *  679:     function generateConfig()	
 *  842:     function procesIncludes()	
 *  866:     function mergeConstantsFromPageTSconfig($constArray)	
 *  895:     function flattenSetup($setupArray, $prefix, $resourceFlag)	
 *  919:     function substituteConstants($all)	
 *
 *              SECTION: Various API functions, used from elsewhere in the frontend classes
 *  969:     function splitConfArray($conf,$splitCount)	
 * 1035:     function getFileName($fileFromSetup)	
 * 1092:     function extractFromResources($res,$file)	
 * 1120:     function checkFile($name,$menuArr)	
 * 1137:     function printTitle($title,$no_title=0,$titleFirst=0)	
 * 1160:     function fileContent($fName)	
 * 1180:     function wrap($content,$wrap)	
 * 1203:     function linkData($page,$oTarget,$no_cache,$script,$overrideArray='',$addParams='',$typeOverride='')	
 * 1296:     function removeQueryString($url)	
 * 1312:     function sortedKeyList($setupArr)	
 *
 * TOTAL FUNCTIONS: 24
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once (PATH_t3lib.'class.t3lib_tsparser.php');
require_once (PATH_t3lib.'class.t3lib_matchcondition.php');
















/**
 * Template object that is responsible for generating the TypoScript template based on template records.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see	t3lib_tsparser.php, t3lib_matchcondition.php
 */
class t3lib_TStemplate	{

		// Debugging, analysis:
	var $tt_track = 1;					// If set, the global tt-timeobject is used to log the performance.
	var $forceTemplateParsing=0;		// If set, the template is always rendered. Used from Admin Panel.

		// Backend Analysis modules settings:
	var $matchAlternative=array();		// This array is passed on to matchObj by generateConfig(). If it holds elements, they are used for matching instead. See commment at the match-class. Used for backend modules only. Never frontend!
	var $matchAll=0;					// If set, the match-class matches everything! Used for backend modules only. Never frontend!
	var $parseEditorCfgField=0;			// If set, the Backend Editor Configuration TypoScript is also parsed (this is not needed for the frontend)
	var $backend_info = 0;
	var $getFileName_backPath='';		// Set from the backend - used to set an absolute path (PATH_site) so that relative resources are properly found with getFileName()
	
		// Externally set breakpoints (used by Backend Modules)
	var $ext_constants_BRP=0;
	var $ext_config_BRP=0;
	var $ext_editorcfg_BRP=0;
	
		// Constants:
	var $uplPath = 'uploads/tf/';
	var $tempPath = 'typo3temp/';
	var $menuclasses = 'gmenu,tmenu,imgmenu,jsmenu';

		// Set Internally:
	var $whereClause = '';				// This MUST be initialized by the init() function
	var $debug = 0;
	var $allowedPaths = array();		// This is the only paths (relative!!) that are allowed for resources in TypoScript. Should all be appended with '/'. You can extend these by the global array TYPO3_CONF_VARS. See init() function.
	var $currentPageData = '';			// Contains "currentPageData" when rendered/fetched from cache. See getCurrentPageData()
	var $simulationHiddenOrTime=0;		// See init(); Set if preview of some kind is enabled.

	var $loaded = 0;					// Set, if the TypoScript template structure is loaded and OK, see ->start()
	var $setup = Array(					// Default TypoScript Setup code
		'styles.' => Array (
			'insertContent' => 'CONTENT',
			'insertContent.' => Array (
				'table' => 'tt_content',
				'select.' => Array (
					'orderBy' => 'sorting',
					'where' => 'colPos=0',
					'languageField' => 'sys_language_uid'
				)
			)
		),
		'config.' => Array (
			'extTarget' => '_top',
			'stat' => 1,
			'stat_typeNumList' => '0,1'
		)
	);
	var $flatSetup = Array (
	);
	var $const = Array (	// Default TypoScript Constants code:
		'_clear' => '<img src="clear.gif" width="1" height="1" alt="" />',
		'_blackBorderWrap' => '<table border="0" bgcolor="black" cellspacing="0" cellpadding="1"><tr><td> | </td></tr></table>',
		'_tableWrap' => '<table border="0" cellspacing="0" cellpadding="0"> | </table>',
		'_tableWrap_DEBUG' => '<table border="1" cellspacing="0" cellpadding="0"> | </table>',
		'_stdFrameParams' => 'frameborder="no" marginheight="0" marginwidth="0" noresize="noresize"',
		'_stdFramesetParams' => 'border="0" framespacing="0" frameborder="no"'
	);
	

		// For fetching TypoScript code from template hierarchy before parsing it. Each array contains code field values from template records/files:
	var $config = array();				// Setup field
	var $constants = array();			// Constant field
	var $editorcfg = array();			// Backend Editor Configuration field
	
	var $hierarchyInfo = array();		// For Template Analyser in backend 
	var $nextLevel=0;					// Next-level flag (see runThroughTemplates())
	var $rootId;						// The Page UID of the root page
	var $rootLine;						// The rootline from current page to the root page 
	var $absoluteRootLine;				// Rootline all the way to the root. Set but runThroughTemplates
	var $outermostRootlineIndexWithTemplate=0;	// A pointer to the last entry in the rootline where a template was found.
	var $rowSum;						// Array of arrays with title/uid of templates in hierarchy
	var $resources='';					// Resources for the template hierarchy in a comma list
	var $sitetitle='';					// The current site title field.
	var $sections;						// Tracking all conditions found during parsing of TypoScript. Used for the "all" key in currentPageData
	var $sectionsMatch;					// Tracking all matching conditions found
	
		// Backend: ts_analyzer
	var $clearList_const=array();
	var $clearList_setup=array();
	var $clearList_editorcfg=array();
	var $parserErrors=array();			
	var $setup_constants = array();

		// Other:
	var $fileCache = Array();			// Used by getFileName for caching of references to file resources
	var $frames = Array();				// Keys are frame names and values are type-values, which must be used to refer correctly to the content of the frames.





	/**
	 * Initialize
	 * MUST be called directly after creating a new template-object
	 * 
	 * @return	void		
	 * @see tslib_fe::initTemplate()
	 */
	function init()	{
	 		// $this->whereClause is used only to select templates from sys_template.
			// $GLOBALS['SIM_EXEC_TIME'] is used so that we're able to simulate a later time as a test...
		$this->whereClause='AND NOT deleted ';
		if (!$GLOBALS['TSFE']->showHiddenRecords)	{
			$this->whereClause.='AND NOT hidden ';
		}
		if ($GLOBALS['TSFE']->showHiddenRecords || $GLOBALS['SIM_EXEC_TIME']!=$GLOBALS['EXEC_TIME'])	{	// Set the simulation flag, if simulation is detected!
			$this->simulationHiddenOrTime=1;
		}
		$this->whereClause.= 'AND (starttime<='.$GLOBALS['SIM_EXEC_TIME'].') AND (endtime=0 OR endtime>'.$GLOBALS['SIM_EXEC_TIME'].')';
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'])	{
			$this->menuclasses='tmenu,jsmenu,gmenu';
		}

			// Sets the paths from where TypoScript resources are allowed to be used:		
		$this->allowedPaths = Array ('media/','fileadmin/','uploads/','typo3temp/','t3lib/fonts/',TYPO3_mainDir.'ext/',TYPO3_mainDir.'sysext/','typo3conf/ext/');
		if ($GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths'])	{
			$pathArr = t3lib_div::trimExplode(',',$GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths'],1);
			while(list(,$p)=each($pathArr))	{	
					// Once checked for path, but as this may run from typo3/mod/web/ts/ dir, that'll not work!! So the paths ar uncritically included here.
				$this->allowedPaths[] = $p;
			}
		}
	}

	/**
	 * Fetches the "currentPageData" array from cache
	 * 
	 * NOTE about currentPageData:
	 * It holds information about the TypoScript conditions along with the list of template uid's which is used on the page.
	 * In the getFromCache function in TSFE, currentPageData is used to evaluate if there is a template and if the matching conditions are alright
	 * Unfortunately this does not take into account if the templates in the rowSum of currentPageData has changed composition, eg. due to hidden fields or start/end time.
	 * So if a template is hidden or times out, it'll not be discovered unless the page is regenerated - at least the this->start function must be called, because this will make a new portion of data in currentPageData string
	 * 
	 * @return	mixed		The array $this->currentPageData if found cached in "cache_pagesection". If the string "none" was returned it means the array must be generated and stored in the cache-table
	 * @see start(), t3lib_fe::getFromCache()
	 */
	function getCurrentPageData()	{
		$res = mysql(TYPO3_db, 'SELECT content FROM cache_pagesection WHERE page_id='.intval($GLOBALS['TSFE']->id));
		if ($row=mysql_fetch_assoc($res))	{
			$this->currentPageData = unserialize($row['content']);
		} else {
			$this->currentPageData = 'none';
		}
		return $this->currentPageData;
	}

	/**
	 * Fetches data about which TypoScript-matches there are at this page. Then it performs a matchingtest.
	 * 
	 * @param	array		An array with three keys, "all", "rowSum" and "rootLine" - all coming from the $this->currentPageData array
	 * @return	array		The input array but with a new key added, "match" which contains the items from the "all" key which when passed to tslib_matchCondition returned true.
	 * @see t3lib_matchCondition, t3lib_fe::getFromCache()
	 */
	function matching($cc)	{	
		if (is_array($cc['all']))	{
			reset($cc['all']);
			$matchObj = t3lib_div::makeInstance('t3lib_matchCondition');
			$matchObj->altRootLine=$cc['rootLine'];
			while(list($key,$pre)=each($cc['all']))	{
				if ($matchObj->match($pre))	{
					$sectionsMatch[$key]=$pre;
				}
			}
			$cc['match']=$sectionsMatch;
		}
		return $cc;
	}

	/**
	 * This is all about fetching the right TypoScript template structure. If it's not cached then it must be generated and cached!
	 * The method traverse the rootline structure from out to in, fetches the hierarchy of template records and based on this either finds the cached TypoScript template structure or parses the template and caches it for next time.
	 * Sets $this->setup to the parsed TypoScript Template array
	 * 
	 * @param	array		The rootline of the current page (going ALL the way to tree root)
	 * @return	void		
	 * @see tslib_fe::getConfigArray()
	 */
	function start($theRootLine)	{
		if (is_array($theRootLine))	{
			$setupData='';
			$cc=Array();
			$hash='';
			$this->runThroughTemplates($theRootLine);
			
				// Getting the currentPageData if not already found
			if (!$this->currentPageData)	{
				$this->getCurrentPageData();
			}
			
				// This is about getting the hash string which is used to fetch the cached TypoScript template.
				// If there was some cached currentPageData that's good (it gives us the hash), 
				// However if the actual rowSum and the rowSum of currentPageData is different from each other, thats a problem, and we should re-make the current page data.
			if (is_array($this->currentPageData) &&
				!strcmp(serialize($this->rowSum), serialize($this->currentPageData['rowSum']))	// The two ROWsums must NOT be different from each other - which they will be if start/endtime or hidden has changed!
			)	{	
					// If currentPageData was actually there, we match the result...
				$cc['all'] = $this->currentPageData['all'];
				$cc['rowSum'] = $this->currentPageData['rowSum'];
				$cc = $this->matching($cc);
				$hash = md5(serialize($cc));
			} else {	
					// If currentPageData was not there, we first find $rowSum (freshly generated). After that we try to see, if rowSum is stored with a list of all matching-parameters. If so we match the result
				$rowSumHash = md5('ROWSUM:'.serialize($this->rowSum));
				$result = t3lib_pageSelect::getHash($rowSumHash, 0);
				if ($result)	{
					$cc['all'] = unserialize($result);
					$cc['rowSum'] = $this->rowSum;
					$cc = $this->matching($cc);
					$hash = md5(serialize($cc));
				}
			}

			if ($hash)	{
					// Get TypoScript setup array
				$setupData = t3lib_pageSelect::getHash($hash, 0);
			}

			if ($hash && $setupData && !$this->forceTemplateParsing)		{
					// If TypoScript setup structure was cached we unserialize it here:
				$this->setup = unserialize($setupData);
			} else {
					// Make configuration
				$this->generateConfig();

					// This stores the template hash thing
				$cc=Array();
				$cc['all']=$this->sections;	// All sections in the template at this point is found
				$cc['rowSum']=$this->rowSum;	// The line of templates is collected
				$cc = $this->matching($cc);

				$hash = md5(serialize($cc));

					// This stores the data.
				t3lib_pageSelect::storeHash($hash, serialize($this->setup), 'TS TEMPLATE');	

				if ($this->tt_track)	$GLOBALS['TT']->setTSlogMessage('TS template size, serialized: '.strlen(serialize($this->setup)).' bytes');
				
				$rowSumHash = md5('ROWSUM:'.serialize($this->rowSum));
				t3lib_pageSelect::storeHash($rowSumHash, serialize($cc['all']), 'TMPL CONDITIONS - ALL');
			}
				// Add rootLine
			$cc['rootLine'] = $this->rootLine;
				// Make global and save.
			$GLOBALS['TSFE']->all=$cc;

			if (!$this->simulationHiddenOrTime)	{	// Only save currentPageData, if we're not simulating by hidden/starttime/endtime
				$res = mysql (TYPO3_db, 'DELETE FROM cache_pagesection WHERE page_id='.intval($GLOBALS['TSFE']->id));
				$res = mysql(TYPO3_db, 'INSERT INTO	cache_pagesection (page_id, content, tstamp) VALUES ('.intval($GLOBALS['TSFE']->id).', "'.addslashes(serialize($cc)).'", '.$GLOBALS['EXEC_TIME'].')');
			}
				// If everything OK.
			if ($this->rootId && $this->rootLine && $this->setup)	{
				$this->loaded = 1;
			}
		}
	}















	/*******************************************************************
	 *
	 * Fetching TypoScript code text for the Template Hierarchy
	 *
	 *******************************************************************/

	/**
	 * Traverses the rootLine from the root and out. For each page it checks if there is a template record. If there is a template record, $this->processTemplate() is called.
	 * Resets and affects internal variables like $this->constants, $this->config, $this->editorcfg and $this->rowSum
	 * Also creates $this->rootLine which is a root line stopping at the root template (contrary to $GLOBALS['TSFE']->rootLine which goes all the way to the root of the tree
	 * 
	 * @param	array		The rootline of the current page (going ALL the way to tree root)
	 * @param	integer		Set specific template record UID to select; this is only for debugging/development/analysis use in backend modules like "Web > Template". For parsing TypoScript templates in the frontend it should be 0 (zero)
	 * @return	void		
	 * @see start()
	 */
	function runThroughTemplates($theRootLine,$start_template_uid=0)	{
		$this->constants = Array();
		$this->config = Array();
		$this->editorcfg = Array();
		$this->rowSum = Array();
		$this->absoluteRootLine=$theRootLine;	// Is the TOTAL rootline
		
		reset ($this->absoluteRootLine);
		$c=count($this->absoluteRootLine);
		for ($a=0;$a<$c;$a++)	{
			if ($this->nextLevel)	{	// If some template loaded before has set a template-id for the next level, then load this template first!
				$res = mysql(TYPO3_db, 'SELECT * FROM sys_template WHERE uid='.intval($this->nextLevel).' '.$this->whereClause);
				$this->nextLevel = 0;
				if ($row = mysql_fetch_assoc($res))	{
					$this->processTemplate($row,'sys_'.$row['uid'],$this->absoluteRootLine[$a]['uid'],'sys_'.$row['uid']);
					$this->outermostRootlineIndexWithTemplate=$a;
				}
			}
			$addC='';
			if ($a==($c-1) && $start_template_uid)	{	// If first loop AND there is set an alternative template uid, use that
				$addC=' AND uid='.intval($start_template_uid);
			}
			$query = 'SELECT * FROM sys_template WHERE pid='.$this->absoluteRootLine[$a]['uid'].$addC.' '.$this->whereClause.' ORDER BY sorting LIMIT 1';

			$res = mysql(TYPO3_db, $query);
			if ($row = mysql_fetch_assoc($res))	{
				$this->processTemplate($row,'sys_'.$row['uid'],$this->absoluteRootLine[$a]['uid'],'sys_'.$row['uid']);
				$this->outermostRootlineIndexWithTemplate=$a;
			}
			$this->rootLine[] = $this->absoluteRootLine[$a];
		}
	}

	/**
	 * Checks if the template ($row) has some included templates and after including them it fills the arrays with the setup
	 * Builds up $this->rowSum
	 * 
	 * @param	array		A full TypoScript template record (sys_template/static_template/forged "dummy" record made from static template file)
	 * @param	string		A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records, "static" for "static_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
	 * @param	array		The PID of the input template record
	 * @param	string		The id of the current template. Same syntax as $idList ids, eg. "sys_123"
	 * @param	string		Parent template id (during recursive call); Same syntax as $idList ids, eg. "sys_123"
	 * @return	void		
	 * @see runThroughTemplates()
	 */
	function processTemplate($row, $idList,$pid,$templateID='',$templateParent='')	{
			// Adding basic template record information to rowSum array
		$this->rowSum[]=Array($row['uid'],$row['title'],$row['tstamp']);
		
			// Processing "Clear"-flags
		if ($row['clear'])	{
			$clConst = $row['clear']&1;
			$clConf = $row['clear']&2;
			if ($clConst)	{
				$this->constants = Array();
				$this->clearList_const=array();
			}
			if ($clConf)	{
				$this->config = Array();
				$this->clearList_setup=array();

				$this->editorcfg = Array();
				$this->clearList_editorcfg=array();
			}
		}

			// Include static records (static_template) or files (from extensions) (#1/2)
		if (!$row['includeStaticAfterBasedOn'])		{		// NORMAL inclusion, The EXACT same code is found below the basedOn inclusion!!!
			$this->includeStaticTypoScriptSources($idList,$templateID,$pid,$row);
		}

			// Include "Based On" sys_templates:
		if (trim($row['basedOn']))	{		// 'basedOn' is a list of templates to include
				// Manually you can put this value in the field and then the based_on ID will be taken from the HTTP_GET_VARS var defined by '=....'.
				// Example: If $row['basedOn'] is 'EXTERNAL_BASED_ON_TEMPLATE_ID=based_on_uid', then the global var, based_on_uid - given by the URL like '&based_on_uid=999' - is included instead!
				// This feature allows us a hack to test/demonstrate various included templates on the same set of content bearing pages. Used by the "freesite" extension.
			$basedOn_hackFeature = explode('=',$row['basedOn']);
			if ($basedOn_hackFeature[0]=='EXTERNAL_BASED_ON_TEMPLATE_ID' && $basedOn_hackFeature[1])		{	
				$id=intval($GLOBALS['HTTP_GET_VARS'][$basedOn_hackFeature[1]]);
				if ($id && !t3lib_div::inList($idList,'sys_'.$id))	{	// if $id is not allready included ...
					$res = mysql(TYPO3_db, 'SELECT * FROM sys_template WHERE uid='.$id.' '.$this->whereClause);
					echo mysql_error();
					if ($subrow = mysql_fetch_assoc($res))	{	// there was a template, then we fetch that
						$this->processTemplate($subrow,$idList.',sys_'.$id,$pid, 'sys_'.$id,$templateID);
					}
				}
			} else {	// NORMAL OPERATION:
				$basedOnArr = t3lib_div::intExplode(',',$row['basedOn']);
				while(list(,$id)=each($basedOnArr))	{	// traversing list
					if (!t3lib_div::inList($idList,'sys_'.$id))	{	// if $id is not allready included ...
						$res = mysql(TYPO3_db, 'SELECT * FROM sys_template WHERE uid='.$id.' '.$this->whereClause);
						echo mysql_error();
						if ($subrow = mysql_fetch_assoc($res))	{	// there was a template, then we fetch that
							$this->processTemplate($subrow,$idList.',sys_'.$id,$pid, 'sys_'.$id,$templateID);
						}
					}
				}
			}
		}

			// Include static records (static_template) or files (from extensions) (#2/2)
		if ($row['includeStaticAfterBasedOn'])		{
			$this->includeStaticTypoScriptSources($idList,$templateID,$pid,$row);
		}

			// Creating hierarchy information; Used by backend analysis tools
		$this->hierarchyInfo[] = array(
			'root'=>trim($row['root']), 
			'next'=>$row['nextLevel'], 
			'clConst'=>$clConst, 
			'clConf'=>$clConf, 
			'templateID'=>$templateID, 
			'templateParent'=>$templateParent, 
			'title'=>$row['title'], 
			'uid'=>$row['uid'], 
			'pid'=>$row['pid']
		);
		
			// Adding the content of the fields constants (Constants), config (Setup) and editorcfg (Backend Editor Configuration) to the internal arrays.
		$this->constants[] = $row['constants'];
		$this->config[] = $row['config'];
		if ($this->parseEditorCfgField)		$this->editorcfg[] = $row['editorcfg'];

			// For backend analysis (Template Analyser) provide the order of added constants/config/editorcfg template IDs
		$this->clearList_const[]=$templateID;
		$this->clearList_setup[]=$templateID;
		if ($this->parseEditorCfgField)		$this->clearList_editorcfg[]=$templateID;

			// Add resources and sitetitle if found:
		if (trim($row['resources']))	{
			$this->resources = $row['resources'].','.$this->resources;
		}
		if (trim($row['sitetitle']))	{
			$this->sitetitle = $row['sitetitle'];
		}
			// If the template record is a Rootlevel record, set the flag and clear the template rootLine (so it starts over from this point)
		if (trim($row['root']))	{
			$this->rootId = $pid;
			$this->rootLine = Array();
		}
			// If a template is set to be active on the next level set this internal value to point to this UID. (See runThroughTemplates())
		if ($row['nextLevel'])	{
			$this->nextLevel = $row['nextLevel'];
		} else {
			$this->nextLevel = 0;
		}
	}

	/**
	 * Includes static template records (from static_template table) and static template files (from extensions) for the input template record row.
	 * 
	 * @param	string		A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records, "static" for "static_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
	 * @param	string		The id of the current template. Same syntax as $idList ids, eg. "sys_123"
	 * @param	array		The PID of the input template record
	 * @param	array		A full TypoScript template record
	 * @return	void		
	 * @see processTemplate()
	 */
	function includeStaticTypoScriptSources($idList,$templateID,$pid,$row)	{
			// Static Template Records (static_template): include_static is a list of static templates to include
		if (trim($row['include_static']))	{
			$include_staticArr = t3lib_div::intExplode(',',$row['include_static']);
			reset($include_staticArr);
			while(list(,$id)=each($include_staticArr))	{	// traversing list
				if (!t3lib_div::inList($idList,'static_'.$id))	{	// if $id is not allready included ...
					$res = mysql(TYPO3_db, 'SELECT * FROM static_template WHERE uid='.$id);
					echo mysql_error();
					if ($subrow = mysql_fetch_assoc($res))	{	// there was a template, then we fetch that
						$subrow = $this->prependStaticExtra($subrow);
						$this->processTemplate($subrow,$idList.',static_'.$id,$pid,'static_'.$id,$templateID);
					}
				}
			}
		}

			// Static Template Files (Text files from extensions): include_static_file is a list of static files to include (from extensions)
		if (trim($row['include_static_file']))	{
			$include_static_fileArr = t3lib_div::trimExplode(',',$row['include_static_file'],1);
			reset($include_static_fileArr);
			while(list(,$ISF_file)=each($include_static_fileArr))	{	// traversing list
				$ISF_file = trim($ISF_file);
				if (substr($ISF_file,0,4)=='EXT:')	{
					list($ISF_extKey,$ISF_localPath) = explode('/',substr($ISF_file,4),2);
					if (strcmp($ISF_extKey,'') && t3lib_extMgm::isLoaded($ISF_extKey) && strcmp($ISF_localPath,''))	{
						$ISF_localPath = ereg_replace('\/$','',$ISF_localPath).'/';
						$ISF_filePath = t3lib_extMgm::extPath($ISF_extKey).$ISF_localPath;
						if (@is_dir($ISF_filePath))	{
							$mExtKey = str_replace('_','',$ISF_extKey.'/'.$ISF_localPath);
							$subrow=array(
								'constants'=>	@is_file($ISF_filePath.'constants.txt')	?t3lib_div::getUrl($ISF_filePath.'constants.txt'):'',
								'config'=>		@is_file($ISF_filePath.'setup.txt')		?t3lib_div::getUrl($ISF_filePath.'setup.txt'):'',
								'editorcfg'=>	@is_file($ISF_filePath.'editorcfg.txt')	?t3lib_div::getUrl($ISF_filePath.'editorcfg.txt'):'',
								'include_static'=>	@is_file($ISF_filePath.'include_static.txt')?implode(',',array_unique(t3lib_div::intExplode(',',t3lib_div::getUrl($ISF_filePath.'include_static.txt')))):'',
								'title' => 		$ISF_file,
								'uid' => 		$mExtKey
							);
							$subrow = $this->prependStaticExtra($subrow);
							
							$this->processTemplate($subrow,$idList.',ext_'.$mExtKey,$pid, 'ext_'.$mExtKey,$templateID);
						}
					}
				}
			}
		}
		
		$this->addExtensionStatics($idList,$templateID,$pid,$row);
	}

	/**
	 * Adds the default TypoScript files for extensions if any.
	 * 
	 * @param	string		A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records, "static" for "static_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
	 * @param	string		The id of the current template. Same syntax as $idList ids, eg. "sys_123"
	 * @param	array		The PID of the input template record
	 * @param	array		A full TypoScript template record
	 * @return	void		
	 * @access private
	 * @see includeStaticTypoScriptSources()
	 */
	function addExtensionStatics($idList,$templateID,$pid,$row) {
		global $TYPO3_LOADED_EXT;

		if ($row['static_file_mode']==1 || ($row['static_file_mode']==0 && substr($templateID,0,4)=='sys_' && $row['root']))	{
			reset($TYPO3_LOADED_EXT);
			while(list($extKey,$files)=each($TYPO3_LOADED_EXT))	{
				if (is_array($files) && ($files['ext_typoscript_constants.txt'] || $files['ext_typoscript_setup.txt'] || $files['ext_typoscript_editorcfg.txt']))	{
					$mExtKey = str_replace('_','',$extKey);
					$subrow=array(
						'constants'=>	$files['ext_typoscript_constants.txt']?t3lib_div::getUrl($files['ext_typoscript_constants.txt']):'',
						'config'=>		$files['ext_typoscript_setup.txt']?t3lib_div::getUrl($files['ext_typoscript_setup.txt']):'',
						'editorcfg'=>		$files['ext_typoscript_editorcfg.txt']?t3lib_div::getUrl($files['ext_typoscript_editorcfg.txt']):'',
						'title' => 		$extKey,
						'uid' => 		$mExtKey
					);
					$subrow = $this->prependStaticExtra($subrow);
					
					$this->processTemplate($subrow,$idList.',ext_'.$mExtKey,$pid, 'ext_'.$mExtKey,$templateID);
				}
			}
		}
	}

	/**
	 * Appends (not prepends) additional TypoScript code to static template records/files as set in TYPO3_CONF_VARS
	 * For records the "uid" value is the integer of the "static_template" record
	 * For files the "uid" value is the extension key but with any underscores removed. Possibly with a path if its a static file selected in the template record
	 * 
	 * @param	array		Static template record/file
	 * @return	array		Returns the input array where the values for keys "config", "constants" and "editorcfg" may have been modified with prepended code.
	 * @access private
	 * @see addExtensionStatics(), includeStaticTypoScriptSources()
	 */
	function prependStaticExtra($subrow)	{
		$subrow['config'].=$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'][$subrow['uid']];
		$subrow['editorcfg'].=$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_editorcfg.'][$subrow['uid']];
		$subrow['constants'].=$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants.'][$subrow['uid']];
		return $subrow;
	}

















	/*******************************************************************
	 *
	 * Parsing TypoScript code text from Template Records into PHP array
	 *
	 *******************************************************************/

	/**
	 * Generates the configuration array by replacing constants and parsing the whole thing.
	 * Depends on $this->config and $this->constants to be set prior to this! (done by processTemplate/runThroughTemplates)
	 * 
	 * @return	void		
	 * @see t3lib_TSparser, start()
	 */
	function generateConfig()	{
			// Add default TS for all three code types:
		array_unshift($this->constants,''.$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants']);	// Adding default TS/constants
		array_unshift($this->config,''.$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup']);	// Adding default TS/setup
		array_unshift($this->editorcfg,''.$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_editorcfg']);	// Adding default TS/editorcfg

			// Parse the TypoScript code text for include-instructions!
		$this->procesIncludes();
			
			// These vars are also set lateron...
		$this->setup['resources']= $this->resources;
		$this->setup['sitetitle']= $this->sitetitle;



		// ****************************
		// Parse TypoScript Constants
		// ****************************

			// Initialize parser and match-condition classes:
		$constants = t3lib_div::makeInstance('t3lib_TSparser');
		$constants->breakPointLN=intval($this->ext_constants_BRP);
		$constants->setup = $this->const;
		$constants->setup = $this->mergeConstantsFromPageTSconfig($constants->setup);
		$matchObj = t3lib_div::makeInstance('t3lib_matchCondition');
		$matchObj->matchAlternative = $this->matchAlternative;
		$matchObj->matchAll = $this->matchAll;		// Matches ALL conditions in TypoScript

			// Traverse constants text fields and parse them
		foreach($this->constants as $str)	{
			$constants->parse($str,$matchObj);
		}

			// Read out parse errors if any
		$this->parserErrors['constants']=$constants->errors;

			// Then flatten the structure from a multi-dim array to a single dim array with all constants listed as key/value pairs (ready for substitution)
		$this->flatSetup = Array();
		$this->flattenSetup($constants->setup,'','');
		


		// ***********************************************
		// Parse TypoScript Setup (here called "config")
		// ***********************************************
			// Initialize parser and match-condition classes:
		$config = t3lib_div::makeInstance('t3lib_TSparser');
		$config->breakPointLN=intval($this->ext_config_BRP);
		$config->setup = $this->setup;

			// Transfer information about conditions found in "Constants" and which of them returned true.
		$config->sections = $constants->sections;		
		$config->sectionsMatch = $constants->sectionsMatch;		

			// Traverse setup text fields and concatenate them into one, single string separated by a [GLOBAL] condition
		$all='';
		foreach($this->config as $str)	{
			$all.="\n[GLOBAL]\n".$str;
		}
		
			// Substitute constants in the Setup code:
		if ($this->tt_track)	$GLOBALS['TT']->push('Substitute Constants ('.count($this->flatSetup).')');
		$all = $this->substituteConstants($all);
		if ($this->tt_track)	$GLOBALS['TT']->pull();

			// Searching for possible unsubstituted constants left (only for information)
		if (strstr($all,'{$'))	{
			$findConst = explode('{$',$all);
			$theConstList=Array();
			next($findConst);
			while(list(,$constVal)=each($findConst))	{
				$constLen=t3lib_div::intInRange(strcspn($constVal,'}'),0,50);
				$theConstList[]='{$'.substr($constVal,0,$constLen+1);
			}
			if ($this->tt_track)	$GLOBALS['TT']->setTSlogMessage(implode($theConstList,',').': Constants may remain un-substituted!!',2);
		}

			// Logging the textual size of the TypoScript Setup field text with all constants substituted:
		if ($this->tt_track)	$GLOBALS['TT']->setTSlogMessage('TypoScript template size as textfile: '.strlen($all).' bytes');
		
			// Finally parse the Setup field TypoScript code (where constants are now substituted)
		$config->parse($all,$matchObj);

			// Read out parse errors if any
		$this->parserErrors['config']=$config->errors;

			// Transfer the TypoScript array from the parser object to the internal $this->setup array:
		$this->setup = $config->setup;
		if ($this->backend_info)	{
			$this->setup_constants = $constants->setup;		// Used for backend purposes only
		}




		// **************************************************
		// Parse Backend Editor Configuration (backend only)
		// **************************************************
		if ($this->parseEditorCfgField)	{
			$editorcfg = t3lib_div::makeInstance('t3lib_TSparser');
			$editorcfg->breakPointLN=intval($this->ext_editorcfg_BRP);
			$editorcfg->setup = array();	// Empty as a start...

			$all = implode("\n[GLOBAL]\n",$this->editorcfg);

				// substitute constants in config
			$all = $this->substituteConstants($all);

				// parse Config
			$matchObj->matchAll=1;	// This should make sure that conditions are disabled. For now they are NOT active for the backend.
			$editorcfg->parse($all,$matchObj);
			$this->parserErrors['editorcfg']=$editorcfg->errors;
			$this->setup_editorcfg = $editorcfg->setup;
		}

		
		


		// ****************************************************************
		// Final processing of the $this->setup TypoScript Template array
		// Basically: This is unsetting/setting of certain reserved keys.
		// ****************************************************************
		
			// These vars are allready set after 'processTemplate', but because $config->setup overrides them (in the line above!), we set them again. They are not changed compared to the value they had in the top of the page!
		unset($this->setup['resources']);
		unset($this->setup['resources.']);
		$this->setup['resources']= implode(',',t3lib_div::trimExplode(',',$this->resources,1));

		unset($this->setup['sitetitle']);
		unset($this->setup['sitetitle.']);
		$this->setup['sitetitle']= $this->sitetitle;
			
			// Unsetting some vars...
		unset($this->setup['types.']);
		unset($this->setup['types']);
		if (is_array($this->setup)) {
			reset ($this->setup);
			while(list($theKey,)=each($this->setup))	{
				if ($this->setup[$theKey]=='PAGE')	{
					$tN = $this->setup[$theKey.'.']['typeNum'];
					if (isset($tN))	{
						$this->setup['types.'][$tN] = $theKey;
					}
				}
			}
		}
		unset($this->setup['styles.']);
		unset($this->setup['temp.']);
		unset($constants);
		
			// Storing the conditions found/matched information:
		$this->sections = $config->sections;
		$this->sectionsMatch = $config->sectionsMatch;
	}

	/**
	 * Searching TypoScript code text (for constants, config (Setup) and editorcfg) for include instructions and does the inclusion if needed.
	 * Modifies
	 * 
	 * @return	void		
	 * @see t3lib_TSparser, generateConfig()
	 */
	function procesIncludes()	{
		reset($this->constants);
		while(list($k)=each($this->constants))	{
			$this->constants[$k]=t3lib_TSparser::checkIncludeLines($this->constants[$k]);
		}
		
		reset($this->config);
		while(list($k)=each($this->config))	{
			$this->config[$k]=t3lib_TSparser::checkIncludeLines($this->config[$k]);
		}
		
		reset($this->editorcfg);
		while(list($k)=each($this->editorcfg))	{
			$this->editorcfg[$k]=t3lib_TSparser::checkIncludeLines($this->editorcfg[$k]);
		}
	}
	
	/**
	 * Loads Page TSconfig until the outermost template record and parses the configuration - if TSFE.constants object path is found it is merged with the default data in here!
	 * 
	 * @param	array		Constants array, default input.
	 * @return	array		Constants array, modified
	 * @todo	Apply caching to the parsed Page TSconfig. This is done in the other similar functions for both frontend and backend. However, since this functions works for BOTH frontend and backend we will have to either write our own local caching function or (more likely) detect if we are in FE or BE and use caching functions accordingly. Not having caching affects mostly the backend modules inside the "Template" module since the overhead in the frontend is only seen when TypoScript templates are parsed anyways (after which point they are cached anyways...)
	 */
	function mergeConstantsFromPageTSconfig($constArray)	{
		$TSdataArray = array();
		$TSdataArray[]=$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'];	// Setting default configuration:
		
		for ($a=0;$a<=$this->outermostRootlineIndexWithTemplate;$a++)	{
			$TSdataArray[]=$this->absoluteRootLine[$a]['TSconfig'];
		}
			// Parsing the user TS (or getting from cache)
		$TSdataArray = t3lib_TSparser::checkIncludeLines_array($TSdataArray);
		$userTS = implode($TSdataArray,chr(10).'[GLOBAL]'.chr(10));
		
		$parseObj = t3lib_div::makeInstance('t3lib_TSparser');
		$parseObj->parse($userTS);

		if (is_array($parseObj->setup['TSFE.']['constants.']))	{
			$constArray = t3lib_div::array_merge_recursive_overrule($constArray,$parseObj->setup['TSFE.']['constants.']);
		}
		return $constArray;
	}

	/**
	 * This flattens a hierarchical TypoScript array to $this->flatSetup
	 * 
	 * @param	array		TypoScript array
	 * @param	string		Prefix to the object path. Used for recursive calls to this function.
	 * @param	boolean		If set, then the constant value will be resolved as a TypoScript "resource" data type. Also used internally during recursive calls so that all subproperties for properties named "file." will be resolved as resources.
	 * @return	void		
	 * @see generateConfig()
	 */
	function flattenSetup($setupArray, $prefix, $resourceFlag)	{
		if (is_array($setupArray))	{
			reset($setupArray);
			while(list($key,$val)=each($setupArray))	{
				if ($prefix || substr($key,0,16)!='TSConstantEditor')	{		// We don't want 'TSConstantEditor' in the flattend setup on the first level (190201)
					if (is_array($val))	{
						$this->flattenSetup($val,$prefix.$key, ($key=='file.'));
					} elseif ($resourceFlag) {
						$this->flatSetup[$prefix.$key] = $this->getFileName($val);
					} else {
						$this->flatSetup[$prefix.$key] = $val;
					}
				}
			}
		}
	}

	/**
	 * Substitutes the constants from $this->flatSetup in the text string $all
	 * 
	 * @param	string		TypoScript code text string
	 * @return	string		The processed string with all constants found in $this->flatSetup as key/value pairs substituted.
	 * @see generateConfig(), flattenSetup()
	 */
	function substituteConstants($all)	{
		if ($this->tt_track)	$GLOBALS['TT']->setTSlogMessage('Constants to substitute: '.count($this->flatSetup));
		reset($this->flatSetup);
		while (list($const,$val)=each($this->flatSetup))	{
			if (!is_array($val))	{
				$all = str_replace('{$'.$const.'}',$val,$all);
			}
		}
		return $all;	
	}























	/*******************************************************************
	 *
	 * Various API functions, used from elsewhere in the frontend classes
	 *
	 *******************************************************************/

	/**
	 * Implementation of the "optionSplit" feature in TypoScript (used eg. for MENU objects)
	 * What it does is to split the incoming TypoScript array so that the values are exploded by certain strings ("||" and "|*|") and each part distributed into individual TypoScript arrays with a similar structure, but individualized values.
	 * The concept is known as "optionSplit" and is rather advanced to handle but quite powerful, in particular for creating menus in TYPO3.
	 * 
	 * @param	array		A TypoScript array
	 * @param	integer		The number of items for which to generated individual TypoScript arrays
	 * @return	array		The individualized TypoScript array.
	 * @see tslib_cObj::IMGTEXT(), tslib_menu::procesItemStates()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=289&cHash=6604390b37
	 */
	function splitConfArray($conf,$splitCount)	{
		$splitCount = intval($splitCount);
		$conf2 = Array();
		if ($splitCount && is_array($conf))	{
			while (list($cKey,$val)=each($conf))	{
				if (is_array($val))	{
					$tempConf = $this->splitConfArray($val,$splitCount);
					while (list($aKey,$val)=each($tempConf))	{
						$conf2[$aKey][$cKey] = $val;
					}
				}
			}			
			reset($conf);
			while (list($cKey,$val)=each($conf))	{
				if (!is_array($val))	{
					if (!strstr($val,'|*|') && !strstr($val,'||'))	{
						for ($aKey=0;$aKey<$splitCount;$aKey++)	{
							$conf2[$aKey][$cKey] = $val;
						}
					} else {
						$main = explode ('|*|',$val);
						$mainCount = count($main);
	
						$lastC = 0;
						$middleC = 0;
						$firstC = 0;
	
						if ($main[0])	{
							$first = explode('||',$main[0]);
							$firstC = count($first);
						}
						if ($main[1])	{
							$middle = explode('||',$main[1]);
							$middleC = count($middle);
						}
						if ($main[2])	{
							$last = explode('||',$main[2]);
							$lastC = count($last);
							$value = $last[0];
						}
	
						for ($aKey=0;$aKey<$splitCount;$aKey++)	{
							if ($firstC && isset($first[$aKey])) {
								$value = $first[$aKey];
							} elseif ($middleC) {
								$value = $middle[($aKey-$firstC)%$middleC];
							}
							if ($lastC && $lastC>=($splitCount-$aKey))	{
								$value = $last[$lastC-($splitCount-$aKey)];
							}
							$conf2[$aKey][$cKey] = trim($value);
						}	
					}
				}
			}
		}
		return $conf2;
	}

	/**
	 * Returns the reference to a 'resource' in TypoScript. 
	 * This could be from the filesystem if '/' is found in the value $fileFromSetup, else from the resource-list
	 * 
	 * @param	string		TypoScript "resource" data type value.
	 * @return	string		Resulting filename, if any.
	 */
	function getFileName($fileFromSetup)	{
		$file = trim($fileFromSetup);
		if (!$file)	return;
			// cache
		$hash = md5($file);
		if (isset($this->fileCache[$hash]))	{
			return $this->fileCache[$hash];
		}
		
		if (!strcmp(substr($file,0,4),'EXT:'))	{
			$newFile='';
			list($extKey,$script)=explode('/',substr($file,4),2);
			if ($extKey && t3lib_extMgm::isLoaded($extKey))	{
				$extPath=t3lib_extMgm::extPath($extKey);
				$newFile=substr($extPath,strlen(PATH_site)).$script;
			}
			if (!@is_file(PATH_site.$newFile))	{
				if ($this->tt_track) $GLOBALS['TT']->setTSlogMessage('Extension media file "'.$newFile.'" was not found!',3);
				return;
			} else $file=$newFile;
		}

			// find
		if (strstr($file,'/')) {	// here it is manual media
			if (@is_file($this->getFileName_backPath.$file))	{
				$outFile = $file;
				$fileInfo = t3lib_div::split_fileref($outFile);
				reset($this->allowedPaths);
				$OK=0;
				while(list(,$val)=each($this->allowedPaths))	{
					if (substr($fileInfo['path'],0,strlen($val))==$val){$OK=1; break;}
				}
				if ($OK)	{
					$this->fileCache[$hash]=$outFile;
					return $outFile;
				} elseif ($this->tt_track) $GLOBALS['TT']->setTSlogMessage('"'.$file.'" was not located in the allowed paths: ('.implode($this->allowedPaths,',').')',3);
			} elseif ($this->tt_track) $GLOBALS['TT']->setTSlogMessage('"'.$this->getFileName_backPath.$file.'" is not a file (non-uploads/.. resource, did not exist).',3);
		} else {		// Here it is uploaded media:
			$outFile = $this->extractFromResources($this->setup['resources'],$file);
			if ($outFile)	{
			 	if (@is_file($this->uplPath.$outFile))	{
					$this->fileCache[$hash] = $this->uplPath.$outFile;
					return $this->uplPath.$outFile;
				} elseif ($this->tt_track) $GLOBALS['TT']->setTSlogMessage('"'.$this->uplPath.$outFile.'" is not a file (did not exist).',3);
			} elseif ($this->tt_track) $GLOBALS['TT']->setTSlogMessage('"'.$file.'" is not a file (uploads/.. resource).',3); 
		}
	}

	/**
	 * Searches for the TypoScript resource filename in the list of resource filenames.
	 * 
	 * @param	string		The resource file name list (from $this->setup['resources'])
	 * @param	string		The resource value to match
	 * @return	string		If found, this will be the resource filename that matched. Typically this file is found in "uploads/tf/"
	 * @access private
	 * @see getFileName()
	 */
	function extractFromResources($res,$file)	{
		if (t3lib_div::inList($res,$file))	{
			$outFile = $file;
		} elseif (strstr($file,'*')) {
			$fileparts=explode('*',$file);
			$c=count($fileparts);
			$files = explode(',',$res);
			while(list(,$val)=each($files))	{
				$test = trim($val);
				if (ereg('^'.quotemeta($fileparts[0]).'.*'.quotemeta($fileparts[$c-1]).'$', $test))	{
					$outFile = $test;
					break;
				}
			}
		}
		return $outFile;
	}

	/**
	 * CheckFile runs through the $menuArr and checks every file-reference in $name
	 * (Not used anywhere)
	 * 
	 * @param	string		Property name in the menu array
	 * @param	array		Menu array to traverse
	 * @return	array		Modified menu array
	 * @depreciated
	 * @internal
	 */
	function checkFile($name,$menuArr)	{
		reset ($menuArr);
		while (list($aKey,)=each($menuArr))	{
			$menuArr[$aKey][$name] = $this->getFileName($menuArr[$aKey][$name]);
		}
		return $menuArr;
	}

	/**
	 * Compiles the content for the page <title> tag.
	 * 
	 * @param	string		The input title string, typically the "title" field of a page's record.
	 * @param	boolean		If set, then only the site title is outputted (from $this->setup['sitetitle'])
	 * @param	boolean		If set, then "sitetitle" and $title is swapped
	 * @return	string		The page title on the form "[sitetitle]: [input-title]". Not htmlspecialchar()'ed.
	 * @see tslib_fe::tempPageCacheContent(), TSpagegen::renderContentWithHeader()
	 */
	function printTitle($title,$no_title=0,$titleFirst=0)	{
		$st = trim($this->setup['sitetitle']) ? $this->setup['sitetitle']:'';
		$title = $no_title ? '' : $title;
		if ($titleFirst)	{
			$temp=$st;
			$st=$title;
			$title=$temp;
		}
		if ($title && $st)	{
			return $st.': '.$title;
		} else {
			return $st.$title;
		}
	}

	/**
	 * Reads the fileContent of $fName and returns it.
	 * The same as t3lib_div::getUrl()
	 * 
	 * @param	string		Absolute filepath to record
	 * @return	string		The content returned
	 * @see tslib_cObj::fileResource(), tslib_cObj::MULTIMEDIA(), t3lib_div::getUrl()
	 */
	function fileContent($fName)	{
		$incFile = $this->getFileName($fName);
		if ($incFile && $fd=fopen($incFile,'rb'))	{
			$content = '';
			while (!feof($fd))	{
				$content.=fread($fd, 5000);
			}
			fclose( $fd );
			return $content;
		}
	}

	/**
	 * Ordinary "wrapping" function. Used in the tslib_menu class and extension classes instead of the similar function in tslib_cObj
	 * 
	 * @param	string		The content to wrap
	 * @param	string		The wrap value, eg. "<b> | </b>"
	 * @return	string		Wrapped input string
	 * @see tslib_menu, tslib_cObj::wrap()
	 */
	function wrap($content,$wrap)	{
		if ($wrap)	{
			$wrapArr = explode('|', $wrap);
			return trim($wrapArr[0]).$content.trim($wrapArr[1]);
		} else return $content;
	}

	/**
	 * The mother of all functions creating links/URLs etc in a TypoScript environment.
	 * See the references below.
	 * Basically this function takes care of issues such as type,id,alias and Mount Points, simulate static documents, M5/B6 encoded parameters etc.
	 * It is important to pass all links created through this function since this is the guarantee that globally configured settings for link creating are observed and that your applications will conform to the various/many configuration options in TypoScript Templates regarding this.
	 * 
	 * @param	array		The page record of the page to which we are creating a link. Needed due to fields like uid, alias, target, no_cache, title and sectionIndex_uid.
	 * @param	string		Default target string to use IF not $page['target'] is set.
	 * @param	boolean		If set, then the "&no_cache=1" parameter is included in the URL.
	 * @param	string		Alternative script name if you don't want to use $GLOBALS['TSFE']->config['mainScript'] (normally set to "index.php")
	 * @param	array		Array with overriding values for the $page array.
	 * @param	string		Additional URL parameters to set in the URL. Syntax is "&foo=bar&foo2=bar2" etc. Also used internally to add parameters if needed.
	 * @param	string		If you set this value to something else than a blank string, then the typeNumber used in the link will be forced to this value. Normally the typeNum is based on the target set OR on $GLOBALS['TSFE']->config['config']['forceTypeValue'] if found.
	 * @return	array		Contains keys like "totalURL", "url", "sectionIndex", "linkVars", "no_cache", "type", "target" of which "totalURL" is normally the value you would use while the other keys contains various parts that was used to construct "totalURL"
	 * @see tslib_frameset::frameParams(), tslib_cObj::typoLink(), tslib_cObj::SEARCHRESULT(), TSpagegen::pagegenInit(), tslib_menu::link()
	 */
	function linkData($page,$oTarget,$no_cache,$script,$overrideArray='',$addParams='',$typeOverride='')	{
		$LD = Array();

			// Overriding some fields in the page record and still preserves the values by adding them as parameters. Little strange function.
		if (is_array($overrideArray))	{
			reset($overrideArray);
			while(list($theKey,$theNewVal)=each($overrideArray))	{
				$addParams.='&real_'.$theKey.'='.rawurlencode($page[$theKey]);
				$page[$theKey] = $theNewVal;
			}
		}
			// Adding Mount Points, "&MP=", parameter for the current page if any is set:
		if (trim($GLOBALS['TSFE']->MP_defaults[$page['uid']]) && !strstr($addParams,'&MP='))	{
			$addParams.='&MP='.rawurlencode(trim($GLOBALS['TSFE']->MP_defaults[$page['uid']]));
		}
			// Setting ID/alias:
		if (!$script)	{$script = $GLOBALS['TSFE']->config['mainScript'];}
		if ($page['alias'])	{
			$LD['url']=$script.'?'.$page['alias'];
		} else {
			$LD['url']=$script.'?id='.$page['uid'];
		}
			// Setting target
		$LD['target']= trim($page['target']) ? trim($page['target']) : $oTarget;

			// typeNum
		$typeNum = $this->setup[$LD['target'].'.']['typeNum'];
		if (!$typeOverride && intval($GLOBALS['TSFE']->config['config']['forceTypeValue']))	{
			$typeOverride=intval($GLOBALS['TSFE']->config['config']['forceTypeValue']);
		}
		if (strcmp($typeOverride,''))	{$typeNum=$typeOverride;}	// Override...
		if ($typeNum)	{
			$LD['type']='&type='.intval($typeNum);
		} else {
			$LD['type']='';
		}
		$LD['orig_type'] = $LD['type'];		// Preserving the type number. Will not be cleared if simulateStaticDocuments.

			// noCache
		$LD['no_cache'] = (trim($page['no_cache']) || $no_cache) ? '&no_cache=1' : '';

			// linkVars
		$LD['linkVars']= $GLOBALS['TSFE']->linkVars.$addParams;

			// If simulateStaticDocuments is enabled:
		if ($GLOBALS['TSFE']->config['config']['simulateStaticDocuments'])	{
			$LD['type']='';
			$LD['url']='';

				// MD5/base64 method limitation:
			$remainLinkVars='';
			$flag_simulateStaticDocuments_pEnc = t3lib_div::inList('md5,base64',$GLOBALS['TSFE']->config['config']['simulateStaticDocuments_pEnc']) && !$LD['no_cache'];
			if ($flag_simulateStaticDocuments_pEnc)	{
				list($LD['linkVars'], $remainLinkVars) = $GLOBALS['TSFE']->simulateStaticDocuments_pEnc_onlyP_proc($LD['linkVars']);
			}
			
			$LD['url'].=$GLOBALS['TSFE']->makeSimulFileName($page['title'],
							$page['alias'] ? $page['alias'] : $page['uid'],
							intval($typeNum),
							$LD['linkVars'],
							$LD['no_cache']?1:0);
			
			if ($flag_simulateStaticDocuments_pEnc)	{
				$LD['linkVars']=$remainLinkVars;
			}
			if ($GLOBALS['TSFE']->config['config']['simulateStaticDocuments']=='PATH_INFO')	{
				$LD['url'] = str_replace('.','/',$LD['url']);
				$LD['url'] = 'index.php/'.$LD['url'].'/?';
			} else {
				$LD['url'].= '.html?';
			}
		}

			// Add absRefPrefix if exists.
		$LD['url'] = $GLOBALS['TSFE']->absRefPrefix.$LD['url'];		
		
			// If the special key 'sectionIndex_uid' (added 'manually' in tslib/menu.php to the page-record) is set, then the link jumps directly to a section on the page.
		$LD['sectionIndex'] = $page['sectionIndex_uid'] ? '#'.$page['sectionIndex_uid'] : '';		

			// Compile the normal total url
		$LD['totalURL']= $this->removeQueryString($LD['url'].$LD['type'].$LD['no_cache'].$LD['linkVars'].$GLOBALS['TSFE']->getMethodUrlIdToken).$LD['sectionIndex'];

			// Return the LD-array	
		return $LD;
	}

	/**
	 * Removes the "?" of input string IF the "?" is the last character.
	 * 
	 * @param	string		Input string
	 * @return	string		Output string, free of "?" in the end, if any such character.
	 * @see linkData(), tslib_frameset::frameParams()
	 */
	function removeQueryString($url)	{
		if (substr($url,-1)=='?')	{
			return substr($url,0,-1);
		} else {
			return $url;
		}
	}

	/**
	 * Takes a TypoScript array as input and returns an array which contains all integer properties found which had a value (not only properties). The output array will be sorted numerically.
	 * Call it like t3lib_TStemplate::sortedKeyList()
	 * 
	 * @param	array		TypoScript array with numerical array in
	 * @return	array		An array with all integer properties listed in numeric order.
	 * @see tslib_cObj::cObjGet(), tslib_gifBuilder, tslib_imgmenu::makeImageMap()
	 */
	function sortedKeyList($setupArr)	{
		$keyArr=Array();
		reset($setupArr);
		while(list($key,)=each($setupArr))	{
			$ikey=intval($key);
			if (!strcmp($ikey,$key))	{
				$keyArr[]=$ikey;
			}
		}
		sort($keyArr);
		return $keyArr;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tstemplate.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tstemplate.php']);
}
?>