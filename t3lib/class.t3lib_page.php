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
 * Contains a class with "Page functions" mainly for the frontend
 *
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML-trans compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  102: class t3lib_pageSelect
 *  120:     function init($show_hidden)
 *
 *              SECTION: Selecting page records
 *  159:     function getPage($uid)
 *  174:     function getPage_noCheck($uid)
 *  189:     function getFirstWebPage($uid)
 *  206:     function getPageIdFromAlias($alias)
 *  222:     function getPageOverlay($pageInput,$lUid=-1)
 *
 *              SECTION: Page related: Menu, Domain record, Root line
 *  301:     function getMenu($uid,$fields='*',$sortField='sorting',$addWhere='')
 *  335:     function getDomainStartPage($domain, $path='',$request_uri='')
 *  383:     function getRootLine($uid, $MP='', $ignoreMPerrors=FALSE)
 *  495:     function getPathFromRootline($rl,$len=20)
 *  516:     function getExtURL($pagerow,$disable=0)
 *  540:     function getMountPointInfo($pageId, $pageRec=FALSE, $prevMountPids=array(), $firstPageUid=0)
 *
 *              SECTION: Selecting records in general
 *  615:     function checkRecord($table,$uid,$checkPage=0)
 *  645:     function getRawRecord($table,$uid,$fields='*')
 *  668:     function getRecordsByField($theTable,$theField,$theValue,$whereClause='',$groupBy='',$orderBy='',$limit='')
 *
 *              SECTION: Caching and standard clauses
 *  719:     function getHash($hash,$expTime=0)
 *  742:     function storeHash($hash,$data,$ident)
 *  760:     function deleteClause($table)
 *  775:     function enableFields($table,$show_hidden=-1,$ignore_array=array())
 *
 * TOTAL FUNCTIONS: 19
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


















/**
 * Page functions, a lot of sql/pages-related functions
 * Mainly used in the frontend but also in some cases in the backend.
 * It's important to set the right $where_hid_del in the object so that the functions operate properly
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see tslib_fe::fetch_the_id()
 */
class t3lib_pageSelect {
	var $urltypes = Array('','http://','ftp://','mailto:');
	var $where_hid_del = ' AND pages.deleted=0';	// This is not the final clauses. There will normally be conditions for the hidden,starttime and endtime fields as well. You MUST initialize the object by the init() function
	var $sys_language_uid=0;


		// Internal, dynamic:
	var $error_getRootLine = '';		// Error string set by getRootLine


	/**
	 * init() MUST be run directly after creating a new template-object
	 * This sets the internal variable $this->where_hid_del to the correct where clause for page records taking deleted/hidden/starttime/endtime into account
	 *
	 * @param	boolean		If $show_hidden is true, the hidden-field is ignored!! Normally this should be false. Is used for previewing.
	 * @return	void
	 * @see tslib_fe::fetch_the_id(), tx_tstemplateanalyzer::initialize_editor()
	 */
	function init($show_hidden)	{
		$this->where_hid_del = ' AND pages.deleted=0 ';
		if (!$show_hidden)	{
			$this->where_hid_del.= 'AND pages.hidden=0 ';
		}
		$this->where_hid_del.= 'AND (pages.starttime<='.$GLOBALS['SIM_EXEC_TIME'].') AND (pages.endtime=0 OR pages.endtime>'.$GLOBALS['SIM_EXEC_TIME'].') ';
	}

















	/*******************************************
	 *
	 * Selecting page records
	 *
	 ******************************************/

	/**
	 * Returns the $row for the page with uid = $uid (observing ->where_hid_del)
	 * Any pages_language_overlay will be applied before the result is returned.
	 * If no page is found an empty array is returned.
	 *
	 * @param	integer		The page id to look up.
	 * @return	array		The page row with overlayed localized fields. Empty it no page.
	 * @see getPage_noCheck()
	 */
	function getPage($uid)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid='.intval($uid).$this->where_hid_del);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $this->getPageOverlay($row);
		}
		return Array();
	}

	/**
	 * Return the $row for the page with uid = $uid WITHOUT checking for ->where_hid_del (start- and endtime or hidden). Only "deleted" is checked!
	 *
	 * @param	integer		The page id to look up
	 * @return	array		The page row with overlayed localized fields. Empty it no page.
	 * @see getPage()
	 */
	function getPage_noCheck($uid)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid='.intval($uid).$this->deleteClause('pages'));
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $this->getPageOverlay($row);
		}
		return Array();
	}

	/**
	 * Returns the $row of the first web-page in the tree (for the default menu...)
	 *
	 * @param	integer		The page id for which to fetch first subpages (PID)
	 * @return	mixed		If found: The page record (with overlayed localized fields, if any). If NOT found: blank value (not array!)
	 * @see tslib_fe::fetch_the_id()
	 */
	function getFirstWebPage($uid)	{
		$output = '';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'pid='.intval($uid).$this->where_hid_del, '', 'sorting', '1');
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$output = $this->getPageOverlay($row);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $output;
	}

	/**
	 * Returns a pagerow for the page with alias $alias
	 *
	 * @param	string		The alias to look up the page uid for.
	 * @return	integer		Returns page uid (integer) if found, otherwise 0 (zero)
	 * @see tslib_fe::checkAndSetAlias(), tslib_cObj::typoLink()
	 */
	function getPageIdFromAlias($alias)	{
		$alias = strtolower($alias);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'alias="'.$GLOBALS['TYPO3_DB']->quoteStr($alias, 'pages').'" AND pid>=0 AND pages.deleted=0');	// "AND pid>=0" is because of versioning...
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $row['uid'];
		}
		return 0;
	}

	/**
	 * Returns the relevant page overlay record fields
	 *
	 * @param	mixed		If $pageInput is an integer, it's the pid of the pageOverlay record and thus the page overlay record is returned. If $pageInput is an array, it's a page-record and based on this page record the language record is found and OVERLAYED before the page record is returned.
	 * @param	integer		Language UID if you want to set an alternative value to $this->sys_language_uid which is default. Should be >=0
	 * @return	array		Page row which is overlayed with language_overlay record (or the overlay record alone)
	 */
	function getPageOverlay($pageInput,$lUid=-1)	{

			// Initialize:
		if ($lUid<0)	$lUid = $this->sys_language_uid;
		unset($row);

			// If language UID is different from zero, do overlay:
		if ($lUid)	{
			$fieldArr = explode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields']);
			if (is_array($pageInput))	{
				$page_id = $pageInput['uid'];	// Was the whole record
				$fieldArr = array_intersect($fieldArr,array_keys($pageInput));		// Make sure that only fields which exist in the incoming record are overlaid!
			} else {
				$page_id = $pageInput;	// Was the id
			}

			if (count($fieldArr))	{
				/*
					NOTE to enabledFields('pages_language_overlay'):
					Currently the showHiddenRecords of TSFE set will allow pages_language_overlay records to be selected as they are child-records of a page.
					However you may argue that the showHiddenField flag should determine this. But that's not how it's done right now.
				*/
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							implode(',',$fieldArr),
							'pages_language_overlay',
							'pid='.intval($page_id).'
								AND sys_language_uid='.intval($lUid).
								$this->enableFields('pages_language_overlay'),
							'',
							'',
							'1'
						);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			}
		}

			// Create output:
		if (is_array($pageInput))	{
			return is_array($row) ? array_merge($pageInput,$row) : $pageInput;	// If the input was an array, simply overlay the newfound array and return...
		} else {
			return is_array($row) ? $row : array();	// always an array in return
		}
	}


















	/*******************************************
	 *
	 * Page related: Menu, Domain record, Root line
	 *
	 ******************************************/

	/**
	 * Returns an array with pagerows for subpages with pid=$uid (which is pid here!). This is used for menus.
	 * If there are mount points in overlay mode the _MP_PARAM field is set to the corret MPvar.
	 * If the $uid being input does in itself require MPvars to define a correct rootline these must be handled externally to this function.
	 *
	 * @param	integer		The page id for which to fetch subpages (PID)
	 * @param	string		List of fields to select. Default is "*" = all
	 * @param	string		The field to sort by. Default is "sorting"
	 * @param	string		Optional additional where clauses. Like "AND title like '%blabla%'" for instance.
	 * @return	array		Array with key/value pairs; keys are page-uid numbers. values are the corresponding page records (with overlayed localized fields, if any)
	 * @see tslib_fe::getPageShortcut(), tslib_menu::makeMenu(), tx_wizardcrpages_webfunc_2, tx_wizardsortpages_webfunc_2
	 */
	function getMenu($uid,$fields='*',$sortField='sorting',$addWhere='')	{
		$output = Array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, 'pages', 'pid='.intval($uid).$this->where_hid_del.' '.$addWhere, '', $sortField);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{

				// Keep mount point:
			$origUid = $row['uid'];
			$mount_info = $this->getMountPointInfo($origUid, $row);	// $row MUST have "uid", "pid", "doktype", "mount_pid", "mount_pid_ol" fields in it
			if (is_array($mount_info) && $mount_info['overlay'])	{	// There is a valid mount point.
				$mp_row = $this->getPage($mount_info['mount_pid']);		// Using "getPage" is OK since we need the check for enableFields AND for type 2 of mount pids we DO require a doktype < 200!
				if (count($mp_row))	{
					$row = $mp_row;
					$row['_MP_PARAM'] = $mount_info['MPvar'];
				} else unset($row);	// If the mount point could not be fetched with respect to enableFields, unset the row so it does not become a part of the menu!
			}

				// Add to output array after overlaying language:
			if (is_array($row))	{
				$output[$origUid] = $this->getPageOverlay($row);
			}
		}
		return $output;
	}

	/**
	 * Will find the page carrying the domain record matching the input domain.
	 * Might exit after sending a redirect-header IF a found domain record instructs to do so.
	 *
	 * @param	string		Domain name to search for. Eg. "www.typo3.com". Typical the HTTP_HOST value.
	 * @param	string		Path for the current script in domain. Eg. "/somedir/subdir". Typ. supplied by t3lib_div::getIndpEnv('SCRIPT_NAME')
	 * @param	string		Request URI: Used to get parameters from if they should be appended. Typ. supplied by t3lib_div::getIndpEnv('REQUEST_URI')
	 * @return	mixed		If found, returns integer with page UID where found. Otherwise blank. Might exit if location-header is sent, see description.
	 * @see tslib_fe::findDomainRecord()
	 */
	function getDomainStartPage($domain, $path='',$request_uri='')	{
		$domain = explode(':',$domain);
		$domain = strtolower(ereg_replace('\.$','',$domain[0]));
			// Removing extra trailing slashes
		$path = trim(ereg_replace('\/[^\/]*$','',$path));
			// Appending to domain string
		$domain.= $path;
		$domain = ereg_replace('\/*$','',$domain);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'pages.uid,sys_domain.redirectTo,sys_domain.prepend_params',
					'pages,sys_domain',
					'pages.uid=sys_domain.pid
						AND sys_domain.hidden=0
						AND (sys_domain.domainName="'.$GLOBALS['TYPO3_DB']->quoteStr($domain, 'sys_domain').'" OR sys_domain.domainName="'.$GLOBALS['TYPO3_DB']->quoteStr($domain.'/', 'sys_domain').'") '.
						$this->where_hid_del,
					'',
					'',
					1
				);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			if ($row['redirectTo'])	{
				$rURL = $row['redirectTo'];
				if ($row['prepend_params'])	{
					$rURL = ereg_replace('\/$','',$rURL);
					$prependStr = ereg_replace('^\/','',substr($request_uri,strlen($path)));
					$rURL.='/'.$prependStr;
				}
				Header('Location: '.t3lib_div::locationHeaderUrl($rURL));
				exit;
			} else {
				return $row['uid'];
			}
		}
	}

	/**
	 * Returns array with fields of the pages from here ($uid) and back to the root
	 * NOTICE: This function only takes deleted pages into account! So hidden, starttime and endtime restricted pages are included no matter what.
	 * Further: If any "recycler" page is found (doktype=255) then it will also block for the rootline)
	 * If you want more fields in the rootline records than default such can be added by listing them in $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields']
	 *
	 * @param	integer		The page uid for which to seek back to the page tree root.
	 * @param	string		Commalist of MountPoint parameters, eg. "1-2,3-4" etc. Normally this value comes from the GET var, MP
	 * @param	boolean		If set, some errors related to Mount Points in root line are ignored.
	 * @return	array		Array with page records from the root line as values. The array is ordered with the outer records first and root record in the bottom. The keys are numeric but in reverse order. So if you traverse/sort the array by the numeric keys order you will get the order from root and out. If an error is found (like eternal looping or invalid mountpoint) it will return an empty array.
	 * @see tslib_fe::getPageAndRootline()
	 */
	function getRootLine($uid, $MP='', $ignoreMPerrors=FALSE)	{

			// Initialize:
		$selFields = t3lib_div::uniqueList('pid,uid,title,alias,nav_title,media,layout,hidden,starttime,endtime,fe_group,extendToSubpages,doktype,TSconfig,storage_pid,is_siteroot,mount_pid,mount_pid_ol,'.$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields']);
		$this->error_getRootLine = '';

			// Splitting the $MP parameters if present
		$MPA = array();
		if ($MP)	{
			$MPA = explode(',',$MP);
			reset($MPA);
			while(list($MPAk) = each($MPA))	{
				$MPA[$MPAk] = explode('-', $MPA[$MPAk]);
			}
		}

		$loopCheck = 0;
		$theRowArray = Array();
		$uid = intval($uid);

		while ($uid!=0 && $loopCheck<20)	{	// Max 20 levels in the page tree.
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($selFields, 'pages', 'uid='.intval($uid).' AND pages.deleted=0 AND pages.doktype!=255');
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{

					// Mount Point page types are allowed ONLY a) if they are the outermost record in rootline and b) if the overlay flag is not set:
				if ($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] && $row['doktype']==7 && !$ignoreMPerrors)	{
					$mount_info = $this->getMountPointInfo($row['uid'], $row);
					if ($loopCheck>0 || $mount_info['overlay'])	{
						$this->error_getRootLine = 'Illegal Mount Point found in rootline';
						return array();
					}
				}

				$uid = $row['pid'];	// Next uid

				if (count($MPA) && $GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'])	{
					$curMP = end($MPA);
					if (!strcmp($row['uid'],$curMP[0]))	{

						array_pop($MPA);
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($selFields, 'pages', 'uid='.intval($curMP[1]).' AND pages.deleted=0 AND pages.doktype!=255');
						$mp_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

						if (is_array($mp_row))	{
							$mount_info = $this->getMountPointInfo($mp_row['uid'], $mp_row);
							if (is_array($mount_info) && $mount_info['mount_pid']==$curMP[0])	{
								$uid = $mp_row['pid'];	// Setting next uid

								if ($mount_info['overlay'])	{	// Symlink style: Keep mount point (current row).
									$row['_MOUNT_OL'] = TRUE;	// Set overlay mode:
									$row['_MOUNT_PAGE'] = array(
										'uid' => $mp_row['uid'],
										'pid' => $mp_row['pid'],
										'title' =>  $mp_row['title'],
									);
								} else {	// Normal operation: Insert the mount page row in rootline instead mount point.
									if ($loopCheck>0)	{
										$row = $mp_row;
									} else {
										$this->error_getRootLine = 'Current Page Id is a mounted page of the overlay type and cannot be accessed directly!';
										return array();	// Matching the page id (first run, $loopCheck = 0) with the MPvar is ONLY allowed if the mount point is the "overlay" type (otherwise it could be forged!)
									}
								}

								$row['_MOUNTED_FROM'] = $curMP[0];
								$row['_MP_PARAM'] = $mount_info['MPvar'];
							} else {
								$this->error_getRootLine = 'MP var was corrupted';
								return array();	// The MP variables did NOT connect proper mount points:
							}
						} else {
							$this->error_getRootLine = 'No moint point record found according to PID in MP var';
							return array();	// The second PID in MP var was NOT a valid page.
						}
					}
				}
					// Add row to rootline with language overlaid:
				$theRowArray[] = $this->getPageOverlay($row);
			} else {
				$this->error_getRootLine = 'Broken rootline';
				return array();	// broken rootline.
			}

			$loopCheck++;
		}

			// If the MPA array is NOT empty, we have to return an error; All MP elements were not resolved!
		if (count($MPA))	{
			$this->error_getRootLine = 'MP value remain!';
			return array();
		}

			// Create output array (with reversed order of numeric keys):
		$output = Array();
		$c = count($theRowArray);
		foreach($theRowArray as $key => $val)	{
			$c--;
			$output[$c] = $val;
		}

		return $output;
	}

	/**
	 * Creates a "path" string for the input root line array titles.
	 * Used for writing statistics.
	 *
	 * @param	array		A rootline array!
	 * @param	integer		The max length of each title from the rootline.
	 * @return	string		The path in the form "/page title/This is another pageti.../Another page"
	 * @see tslib_fe::getConfigArray()
	 */
	function getPathFromRootline($rl,$len=20)	{
		if (is_array($rl))	{
			$c=count($rl);
			$path = '';
			for ($a=0;$a<$c;$a++)	{
				if ($rl[$a]['uid'])	{
					$path.='/'.t3lib_div::fixed_lgd_cs(strip_tags($rl[$a]['title']),$len);
				}
			}
			return $path;
		}
	}

	/**
	 * Returns the URL type for the input page row IF the doktype is 3 and not disabled.
	 *
	 * @param	array		The page row to return URL type for
	 * @param	boolean		A flag to simply disable any output from here.
	 * @return	string		The URL type from $this->urltypes array. False if not found or disabled.
	 * @see tslib_fe::checkJumpUrl()
	 */
	function getExtURL($pagerow,$disable=0)	{
		if ($pagerow['doktype']==3 && !$disable)	{
			$redirectTo = $this->urltypes[$pagerow['urltype']].$pagerow['url'];

				// If relative path, prefix Site URL:
			$uI = parse_url($redirectTo);
			if (!$uI['scheme'] && substr($redirectTo,0,1)!='/')	{ // relative path assumed now...
				$redirectTo = t3lib_div::getIndpEnv('TYPO3_SITE_URL').$redirectTo;
			}
			return $redirectTo;
		}
	}

	/**
	 * Returns MountPoint id for page
	 * Does a recursive search if the mounted page should be a mount page itself. It has a run-away break so it can't go into infinite loops.
	 *
	 * @param	integer		Page id for which to look for a mount pid. Will be returned only if mount pages are enabled, the correct doktype (7) is set for page and there IS a mount_pid (which has a valid record that is not deleted...)
	 * @param	array		Optional page record for the page id. If not supplied it will be looked up by the system.
	 * @param	array		Array accumulating formerly tested page ids for mount points. Used for recursivity brake.
	 * @param	integer		The first page id.
	 * @return	mixed		Returns FALSE if no mount point was found, "-1" if there should have been one, but no connection to it, otherwise an array with information about mount pid and modes.
	 * @see tslib_menu
	 */
	function getMountPointInfo($pageId, $pageRec=FALSE, $prevMountPids=array(), $firstPageUid=0)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'])	{

				// Get pageRec if not supplied:
			if (!is_array($pageRec))	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid,doktype,mount_pid,mount_pid_ol', 'pages', 'uid='.intval($pageId).' AND pages.deleted=0 AND pages.doktype!=255');
				$pageRec = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			}

				// Set first Page uid:
			if (!$firstPageUid)	$firstPageUid = $pageRec['uid'];

				// Look for mount pid value plus other required circumstances:
			$mount_pid = intval($pageRec['mount_pid']);
			if (is_array($pageRec) && $pageRec['doktype']==7 && $mount_pid>0 && !in_array($mount_pid, $prevMountPids))	{

					// Get the mount point record (to verify its general existence):
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid,doktype,mount_pid,mount_pid_ol', 'pages', 'uid='.$mount_pid.' AND pages.deleted=0 AND pages.doktype!=255');
				$mount_rec = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				if (is_array($mount_rec))	{

						// Look for recursive mount point:
					$prevMountPids[] = $mount_pid;
					$recursiveMountPid = $this->getMountPointInfo($mount_pid, $mount_rec, $prevMountPids, $firstPageUid);

						// Return mount point information:
					return $recursiveMountPid ?
								$recursiveMountPid :
								array(
									'mount_pid' => $mount_pid,
									'overlay' => $pageRec['mount_pid_ol'],
									'MPvar' => $mount_pid.'-'.$firstPageUid,
									'mount_point_rec' => $pageRec,
									'mount_pid_rec' => $mount_rec,
								);
				} else {
					return -1;	// Means, there SHOULD have been a mount point, but there was none!
				}
			}
		}

		return FALSE;
	}

















	/*********************************
	 *
	 * Selecting records in general
	 *
	 **********************************/

	/**
	 * Checks if a record exists and is accessible.
	 * The row is returned if everything's OK.
	 *
	 * @param	string		The table name to search
	 * @param	integer		The uid to look up in $table
	 * @param	boolean		If checkPage is set, it's also required that the page on which the record resides is accessible
	 * @return	mixed		Returns array (the record) if OK, otherwise blank/0 (zero)
	 */
	function checkRecord($table,$uid,$checkPage=0)	{
		global $TCA;
		$uid=intval($uid);
		if (is_array($TCA[$table])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid='.intval($uid).$this->enableFields($table));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				if ($checkPage)	{
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'uid='.intval($row['pid']).$this->enableFields('pages'));
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
						return $row;
					} else {
						return 0;
					}
				} else {
					return $row;
				}
			}
		}
	}

	/**
	 * Returns record no matter what - except if record is deleted
	 *
	 * @param	string		The table name to search
	 * @param	integer		The uid to look up in $table
	 * @param	string		The fields to select, default is "*"
	 * @return	mixed		Returns array (the record) if found, otherwise blank/0 (zero)
	 * @see getPage_noCheck()
	 */
	function getRawRecord($table,$uid,$fields='*')	{
		global $TCA;
		$uid = intval($uid);
		if (is_array($TCA[$table])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, 'uid='.intval($uid).$this->deleteClause($table));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				return $row;
			}
		}
	}

	/**
	 * Selects records based on matching a field (ei. other than UID) with a value
	 *
	 * @param	string		The table name to search, eg. "pages" or "tt_content"
	 * @param	string		The fieldname to match, eg. "uid" or "alias"
	 * @param	string		The value that fieldname must match, eg. "123" or "frontpage"
	 * @param	string		Optional additional WHERE clauses put in the end of the query. DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	mixed		Returns array (the record) if found, otherwise blank/0 (zero)
	 */
	function getRecordsByField($theTable,$theField,$theValue,$whereClause='',$groupBy='',$orderBy='',$limit='')	{
		global $TCA;
		if (is_array($TCA[$theTable])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						$theTable,
						$theField.'="'.$GLOBALS['TYPO3_DB']->quoteStr($theValue, $theTable).'"'.
							$this->deleteClause($theTable).' '.
								$whereClause,	// whereClauseMightContainGroupOrderBy
						$groupBy,
						$orderBy,
						$limit
					);
			$rows = array();
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$rows[] = $row;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			if (count($rows))	return $rows;
		}
	}














	/*********************************
	 *
	 * Caching and standard clauses
	 *
	 **********************************/

	/**
	 * Returns string value stored for the hash string in the table "cache_hash"
	 * Can be used to retrieved a cached value
	 * Can be used from your frontend plugins if you like. Is also used to store the parsed TypoScript template structures. You can call it directly like t3lib_pageSelect::getHash()
	 *
	 * @param	string		The hash-string which was used to store the data value
	 * @param	integer		Allowed expiretime in seconds. Basically a record is selected only if it is not older than this value in seconds. If expTime is not set, the hashed value will never expire.
	 * @return	string		The "content" field of the "cache_hash" table row.
	 * @see tslib_TStemplate::start(), storeHash()
	 */
	function getHash($hash,$expTime=0)	{
			//
		$expTime = intval($expTime);
		if ($expTime)	{
			$whereAdd = ' AND tstamp > '.(time()-$expTime);
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('content', 'cache_hash', 'hash="'.$GLOBALS['TYPO3_DB']->quoteStr($hash, 'cache_hash').'"'.$whereAdd);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			return $row['content'];
		}
	}

	/**
	 * Stores a string value in the cache_hash table identified by $hash.
	 * Can be used from your frontend plugins if you like. You can call it directly like t3lib_pageSelect::storeHash()
	 *
	 * @param	string		32 bit hash string (eg. a md5 hash of a serialized array identifying the data being stored)
	 * @param	string		The data string. If you want to store an array, then just serialize it first.
	 * @param	string		$ident is just a textual identification in order to inform about the content! May be 20 characters long.
	 * @return	void
	 * @see tslib_TStemplate::start(), getHash()
	 */
	function storeHash($hash,$data,$ident)	{
		$insertFields = array(
			'hash' => $hash,
			'content' => $data,
			'ident' => $ident,
			'tstamp' => time()
		);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_hash', 'hash="'.$GLOBALS['TYPO3_DB']->quoteStr($hash, 'cache_hash').'"');
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_hash', $insertFields);
	}

	/**
	 * Returns the "AND NOT deleted" clause for the tablename given IF $TCA configuration points to such a field.
	 *
	 * @param	string		Tablename
	 * @return	string
	 * @see enableFields()
	 */
	function deleteClause($table)	{
		global $TCA;
		return $TCA[$table]['ctrl']['delete'] ? ' AND '.$TCA[$table]['ctrl']['delete'].'=0' : '';
	}

	/**
	 * Returns a part of a WHERE clause which will filter out records with start/end times or hidden/fe_groups fields set to values that should de-select them according to the current time, preview settings or user login. Definitely a frontend function.
	 * Is using the $TCA arrays "ctrl" part where the key "enablefields" determines for each table which of these features applies to that table.
	 *
	 * @param	string		Table name found in the $TCA array
	 * @param	integer		If $show_hidden is set (0/1), any hidden-fields in records are ignored. NOTICE: If you call this function, consider what to do with the show_hidden parameter. Maybe it should be set? See tslib_cObj->enableFields where it's implemented correctly.
	 * @param	array		Array you can pass where keys can be "disabled", "starttime", "endtime", "fe_group" (keys from "enablefields" in TCA) and if set they will make sure that part of the clause is not added. Thus disables the specific part of the clause. For previewing etc.
	 * @return	string		The clause starting like " AND ...=... AND ...=..."
	 * @see tslib_cObj::enableFields(), deleteClause()
	 */
	function enableFields($table,$show_hidden=-1,$ignore_array=array())	{
		if ($show_hidden==-1 && is_object($GLOBALS['TSFE']))	{	// If show_hidden was not set from outside and if TSFE is an object, set it based on showHiddenPage and showHiddenRecords from TSFE
			$show_hidden = $table=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords;
		}
		if ($show_hidden==-1)	$show_hidden=0;	// If show_hidden was not changed during the previous evaluation, do it here.

		$ctrl = $GLOBALS['TCA'][$table]['ctrl'];
		$query='';
		if (is_array($ctrl))	{
			if ($ctrl['delete'])	{
				$query.=' AND '.$table.'.'.$ctrl['delete'].'=0';
			}
			if (is_array($ctrl['enablecolumns']))	{
				if ($ctrl['enablecolumns']['disabled'] && !$show_hidden && !$ignore_array['disabled'])	{
					$field = $table.'.'.$ctrl['enablecolumns']['disabled'];
					$query.=' AND '.$field.'=0';
				}
				if ($ctrl['enablecolumns']['starttime'] && !$ignore_array['starttime'])	{
					$field = $table.'.'.$ctrl['enablecolumns']['starttime'];
					$query.=' AND ('.$field.'<='.$GLOBALS['SIM_EXEC_TIME'].')';
				}
				if ($ctrl['enablecolumns']['endtime'] && !$ignore_array['endtime'])	{
					$field = $table.'.'.$ctrl['enablecolumns']['endtime'];
					$query.=' AND ('.$field.'=0 OR '.$field.'>'.$GLOBALS['SIM_EXEC_TIME'].')';
				}
				if ($ctrl['enablecolumns']['fe_group'] && !$ignore_array['fe_group'])	{
					$field = $table.'.'.$ctrl['enablecolumns']['fe_group'];
					$gr_list = $GLOBALS['TSFE']->gr_list;
					if (!strcmp($gr_list,''))	$gr_list=0;
					$query.=' AND '.$field.' IN ('.$gr_list.')';
				}
			}
		} else {
			die ('NO entry in the $TCA-array for the table "'.$table.'". This means that the function enableFields() is called with an invalid table name as argument.');
		}

		return $query;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_page.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_page.php']);
}
?>
