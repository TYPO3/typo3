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
 * Standard functions available for the TYPO3 backend.
 * You are encouraged to use this class in your own applications (Backend Modules)
 *
 * Call ALL methods without making an object!
 * Eg. to get a page-record 51 do this: 't3lib_BEfunc::getRecord('pages',51)'
 *
 * $Id$
 * Usage counts are based on search 22/2 2003 through whole backend source of typo3/
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  160: class t3lib_BEfunc
 *
 *              SECTION: SQL-related, selecting records, searching
 *  181:     function deleteClause($table)
 *  205:     function getRecord($table,$uid,$fields='*',$where='')
 *  228:     function getRecordRaw($table,$where='',$fields='*')
 *  251:     function getRecordsByField($theTable,$theField,$theValue,$whereClause='',$groupBy='',$orderBy='',$limit='')
 *  284:     function searchQuery($searchWords,$fields,$table='')
 *  300:     function listQuery($field,$value)
 *  313:     function splitTable_Uid($str)
 *  329:     function getSQLselectableList ($in_list,$tablename,$default_tablename)
 *  358:     function BEenableFields($table,$inv=0)
 *
 *              SECTION: SQL-related, DEPRECIATED functions
 *  422:     function mm_query($select,$local_table,$mm_table,$foreign_table,$whereClause='',$groupBy='',$orderBy='',$limit='')
 *  444:     function DBcompileInsert($table,$fields_values)
 *  458:     function DBcompileUpdate($table,$where,$fields_values)
 *
 *              SECTION: Page tree, TCA related
 *  488:     function BEgetRootLine ($uid,$clause='')
 *  540:     function getRecordPath($uid, $clause, $titleLimit, $fullTitleLimit=0)
 *  578:     function getExcludeFields()
 *  613:     function readPageAccess($id,$perms_clause)
 *  643:     function getTCAtypes($table,$rec,$useFieldNameAsKey=0)
 *  697:     function getTCAtypeValue($table,$rec)
 *  721:     function getSpecConfParts($str, $defaultExtras)
 *  752:     function getSpecConfParametersFromArray($pArr)
 *  777:     function getFlexFormDS($conf,$row,$table)
 *
 *              SECTION: Caching related
 *  882:     function storeHash($hash,$data,$ident)
 *  903:     function getHash($hash,$expTime)
 *
 *              SECTION: TypoScript related
 *  940:     function getPagesTSconfig($id,$rootLine='',$returnPartArray=0)
 *  991:     function updatePagesTSconfig($id,$pageTS,$TSconfPrefix,$impParams='')
 * 1047:     function implodeTSParams($p,$k='')
 *
 *              SECTION: Users / Groups related
 * 1085:     function getUserNames($fields='username,usergroup,usergroup_cached_list,uid',$where='')
 * 1104:     function getGroupNames($fields='title,uid', $where='')
 * 1122:     function getListGroupNames($fields='title,uid')
 * 1142:     function blindUserNames($usernames,$groupArray,$excludeBlindedFlag=0)
 * 1176:     function blindGroupNames($groups,$groupArray,$excludeBlindedFlag=0)
 *
 *              SECTION: Output related
 * 1220:     function daysUntil($tstamp)
 * 1233:     function date($tstamp)
 * 1245:     function datetime($value)
 * 1258:     function time($value)
 * 1275:     function calcAge ($seconds,$labels)
 * 1302:     function dateTimeAge($tstamp,$prefix=1,$date='')
 * 1321:     function titleAttrib($content='',$hsc=0)
 * 1333:     function titleAltAttrib($content)
 * 1358:     function thumbCode($row,$table,$field,$backPath,$thumbScript='',$uploaddir='',$abs=0,$tparams='',$size='')
 * 1427:     function getThumbNail($thumbScript,$theFile,$tparams='',$size='')
 * 1445:     function titleAttribForPages($row,$perms_clause='',$includeAttrib=1)
 * 1503:     function getRecordIconAltText($row,$table='pages')
 * 1538:     function getLabelFromItemlist($table,$col,$key)
 * 1565:     function getItemLabel($table,$col,$printAllWrap='')
 * 1591:     function getRecordTitle($table,$row,$prep=0)
 * 1628:     function getProcessedValue($table,$col,$value,$fixed_lgd_chars=0,$defaultPassthrough=0)
 * 1723:     function getProcessedValueExtra($table,$fN,$fV,$fixed_lgd_chars=0)
 * 1748:     function getFileIcon($ext)
 * 1763:     function getCommonSelectFields($table,$prefix)
 * 1790:     function makeConfigForm($configArray,$defaults,$dataPrefix)
 *
 *              SECTION: Backend Modules API functions
 * 1867:     function helpTextIcon($table,$field,$BACK_PATH,$force=0)
 * 1889:     function helpText($table,$field,$BACK_PATH)
 * 1910:     function editOnClick($params,$backPath='',$requestUri='')
 * 1927:     function viewOnClick($id,$backPath='',$rootLine='',$anchor='',$altUrl='')
 * 1954:     function getModTSconfig($id,$TSref)
 * 1976:     function getFuncMenu($id,$elementName,$currentValue,$menuItems,$script='',$addparams='')
 * 2006:     function getFuncCheck($id,$elementName,$currentValue,$script='',$addparams='',$tagParams='')
 * 2027:     function getFuncInput($id,$elementName,$currentValue,$size=10,$script="",$addparams="")
 * 2044:     function unsetMenuItems($modTSconfig,$itemArray,$TSref)
 * 2068:     function getSetUpdateSignal($set='')
 * 2119:     function getModuleData($MOD_MENU, $CHANGED_SETTINGS, $modName, $type='', $dontValidateList='', $setDefaultList='')
 *
 *              SECTION: Core
 * 2195:     function lockRecords($table='',$uid=0,$pid=0)
 * 2225:     function isRecordLocked($table,$uid)
 * 2266:     function exec_foreign_table_where_query($fieldValue,$field='',$TSconfig=array(),$prefix='')
 * 2348:     function getTCEFORM_TSconfig($table,$row)
 * 2396:     function getTSconfig_pidValue($table,$uid,$pid)
 * 2425:     function getPidForModTSconfig($table,$uid,$pid)
 * 2442:     function getTSCpid($table,$uid,$pid)
 * 2459:     function firstDomainRecord($rootLine)
 * 2482:     function getDomainStartPage($domain, $path='')
 * 2513:     function RTEsetup($RTEprop,$table,$field,$type='')
 * 2571:     function isModuleSetInTBE_MODULES($modName)
 *
 *              SECTION: Miscellaneous
 * 2621:     function typo3PrintError ($header,$text,$js='',$head=1)
 * 2668:     function getPathType_web_nonweb($path)
 * 2681:     function ADMCMD_previewCmds($pageinfo)
 * 2704:     function processParams($params)
 * 2731:     function getListOfBackendModules($name,$perms_clause,$backPath='',$script='index.php')
 *
 * TOTAL FUNCTIONS: 78
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Standard functions available for the TYPO3 backend.
 * Don't instantiate - call functions with "t3lib_BEfunc::" prefixed the function name.
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_BEfunc	{



	/*******************************************
	 *
	 * SQL-related, selecting records, searching
	 *
	 *******************************************/


	/**
	 * Returns the WHERE clause " AND NOT [tablename].[deleted-field]" if a deleted-field is configured in $TCA for the tablename, $table
	 * This function should ALWAYS be called in the backend for selection on tables which are configured in TCA since it will ensure consistent selection of records, even if they are marked deleted (in which case the system must always treat them as non-existent!)
	 * In the frontend a function, ->enableFields(), is known to filter hidden-field, start- and endtime and fe_groups as well. But that is a job of the frontend, not the backend. If you need filtering on those fields as well in the backend you can use ->BEenableFields() though.
	 *
	 * Usage: 80
	 *
	 * @param	string		Table name present in $TCA
	 * @return	string		WHERE clause for filtering out deleted records, eg " AND NOT tablename.deleted"
	 */
	function deleteClause($table)	{
		global $TCA;
		if ($TCA[$table]['ctrl']['delete'])	{
			return ' AND NOT '.$table.'.'.$TCA[$table]['ctrl']['delete'];
		} else {
			return '';
		}
	}

	/**
	 * Gets record with uid=$uid from $table
	 * You can set $field to a list of fields (default is '*')
	 * Additional WHERE clauses can be added by $where (fx. ' AND blabla=1')
	 * Will automatically check if records has been deleted and if so, not return anything.
	 * $table must be found in $TCA
	 *
	 * Usage: 168
	 *
	 * @param	string		Table name present in $TCA
	 * @param	integer		UID of record
	 * @param	string		List of fields to select
	 * @param	string		Additional WHERE clause, eg. " AND blablabla=0"
	 * @return	array		Returns the row if found, otherwise nothing
	 */
	function getRecord($table,$uid,$fields='*',$where='')	{
		if ($GLOBALS['TCA'][$table])	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, 'uid='.intval($uid).t3lib_BEfunc::deleteClause($table).$where);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				return $row;
			}
		}
	}

	/**
	 * Returns the first record found from $table with $where as WHERE clause
	 * This function does NOT check if a record has the deleted flag set.
	 * $table does NOT need to be configured in $TCA
	 * The query used is simply this:
	 * $query='SELECT '.$fields.' FROM '.$table.' WHERE '.$where;
	 *
	 * Usage: 5 (ext: sys_todos)
	 *
	 * @param	string		Table name (not necessarily in TCA)
	 * @param	string		WHERE clause
	 * @param	string		$fields is a list of fields to select, default is '*'
	 * @return	array		First row found, if any
	 */
	function getRecordRaw($table,$where='',$fields='*')	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $row;
		}
	}

	/**
	 * Returns records from table, $theTable, where a field ($theField) equals the value, $theValue
	 * The records are returned in an array
	 * If no records were selected, the function returns nothing
	 *
	 * Usage: 8
	 *
	 * @param	string		Table name present in $TCA
	 * @param	string		Field to select on
	 * @param	string		Value that $theField must match
	 * @param	string		Optional additional WHERE clauses put in the end of the query. DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	mixed		Multidimensional array with selected records (if any is selected)
	 */
	function getRecordsByField($theTable,$theField,$theValue,$whereClause='',$groupBy='',$orderBy='',$limit='')	{
		global $TCA;
		if (is_array($TCA[$theTable])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						$theTable,
						$theField.'="'.$GLOBALS['TYPO3_DB']->quoteStr($theValue, $theTable).'"'.
							t3lib_BEfunc::deleteClause($theTable).' '.
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

	/**
	 * Returns a WHERE clause which will make an AND search for the words in the $searchWords array in any of the fields in array $fields.
	 *
	 * Usage: 1 (class t3lib_fullsearch)
	 *
	 * @param	array		Array of search words
	 * @param	array		Array of fields
	 * @param	string		Table in which we are searching (for DBAL detection of quoteStr() method)
	 * @return	string		WHERE clause for search
	 * @depreciated		Use $GLOBALS['TYPO3_DB']->searchQuery() directly!
	 */
	function searchQuery($searchWords,$fields,$table='')	{
		return $GLOBALS['TYPO3_DB']->searchQuery($searchWords,$fields,$table);
	}

	/**
	 * Returns a WHERE clause that can find a value ($value) in a list field ($field)
	 * For instance a record in the database might contain a list of numbers, "34,234,5" (with no spaces between). This query would be able to select that record based on the value "34", "234" or "5" regardless of their positioni in the list (left, middle or right).
	 * Is nice to look up list-relations to records or files in TYPO3 database tables.
	 *
	 * Usage: 3
	 *
	 * @param	string		Table field name
	 * @param	string		Value to find in list
	 * @return	string		WHERE clause for a query
	 * @depreciated		Use $GLOBALS['TYPO3_DB']->listQuery() directly!
	 */
	function listQuery($field,$value)	{
		return $GLOBALS['TYPO3_DB']->listQuery($field,$value,'');
	}

	/**
	 * Makes an backwards explode on the $str and returns an array with ($table,$uid).
	 * Example: tt_content_45	=>	array('tt_content',45)
	 *
	 * Usage: 1
	 *
	 * @param	string		[tablename]_[uid] string to explode
	 * @return	array
	 */
	function splitTable_Uid($str)	{
		list($uid,$table) = explode('_',strrev($str),2);
		return array(strrev($table),strrev($uid));
	}

	/**
	 * Returns a list of pure integers based on $in_list being a list of records with table-names prepended.
	 * Ex: $in_list = "pages_4,tt_content_12,45" would result in a return value of "4,45" if $tablename is "pages" and $default_tablename is 'pages' as well.
	 *
	 * Usage: 1 (t3lib_userauthgroup)
	 *
	 * @param	string		Input list
	 * @param	string		Table name from which ids is returned
	 * @param	string		$default_tablename denotes what table the number '45' is from (if nothing is prepended on the value)
	 * @return	string		List of ids
	 */
	function getSQLselectableList ($in_list,$tablename,$default_tablename)	{
		$list = Array();
		if ((string)trim($in_list)!='')	{
			$tempItemArray = explode(',',trim($in_list));
			while(list($key,$val)=each($tempItemArray))	{
				$val = strrev($val);
				$parts = explode('_',$val,2);
				if ((string)trim($parts[0])!='')	{
					$theID = intval(strrev($parts[0]));
					$theTable = trim($parts[1]) ? strrev(trim($parts[1])) : $default_tablename;
					if ($theTable==$tablename)	{$list[]=$theID;}
				}
			}
		}
		return implode(',',$list);
	}

	/**
	 * Backend implementation of enableFields()
	 * Notice that "fe_groups" is not selected for - only disabled, starttime and endtime.
	 * Notice that deleted-fields are NOT filtered - you must ALSO call deleteClause in addition.
	 * $GLOBALS["SIM_EXEC_TIME"] is used for date.
	 *
	 * Usage: 5
	 *
	 * @param	string		$table is the table from which to return enableFields WHERE clause. Table name must have a 'ctrl' section in $TCA.
	 * @param	boolean		$inv means that the query will select all records NOT VISIBLE records (inverted selection)
	 * @return	string		WHERE clause part
	 */
	function BEenableFields($table,$inv=0)	{
		$ctrl = $GLOBALS['TCA'][$table]['ctrl'];
		$query=array();
		$invQuery=array();
		if (is_array($ctrl))	{
			if (is_array($ctrl['enablecolumns']))	{
				if ($ctrl['enablecolumns']['disabled'])	{
					$field = $table.'.'.$ctrl['enablecolumns']['disabled'];
					$query[]='NOT '.$field;
					$invQuery[]=$field;
				}
				if ($ctrl['enablecolumns']['starttime'])	{
					$field = $table.'.'.$ctrl['enablecolumns']['starttime'];
					$query[]='('.$field.'<='.$GLOBALS['SIM_EXEC_TIME'].')';
					$invQuery[]='('.$field.'!=0 AND '.$field.'>'.$GLOBALS['SIM_EXEC_TIME'].')';
				}
				if ($ctrl['enablecolumns']['endtime'])	{
					$field = $table.'.'.$ctrl['enablecolumns']['endtime'];
					$query[]='('.$field.'=0 OR '.$field.'>'.$GLOBALS['SIM_EXEC_TIME'].')';
					$invQuery[]='('.$field.'!=0 AND '.$field.'<='.$GLOBALS['SIM_EXEC_TIME'].')';
				}
			}
		}
		$outQ = ' AND '.($inv ? '('.implode(' OR ',$invQuery).')' : implode(' AND ',$query));

		return $outQ;
	}










	/*******************************************
	 *
	 * SQL-related, DEPRECIATED functions
	 * (use t3lib_DB functions instead)
	 *
	 *******************************************/


	/**
	 * Returns a SELECT query, selecting fields ($select) from two/three tables joined
	 * $local_table and $mm_table is mandatory. $foreign_table is optional.
	 * The JOIN is done with [$local_table].uid <--> [$mm_table].uid_local  / [$mm_table].uid_foreign <--> [$foreign_table].uid
	 * The function is very useful for selecting MM-relations between tables adhering to the MM-format used by TCE (TYPO3 Core Engine). See the section on $TCA in Inside TYPO3 for more details.
	 * DEPRECIATED - Use $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query() instead since that will return the result pointer while this returns the query. Using this function may make your application less fitted for DBAL later.
	 *
	 * @param	string		Field list for SELECT
	 * @param	string		Tablename, local table
	 * @param	string		Tablename, relation table
	 * @param	string		Tablename, foreign table
	 * @param	string		Optional additional WHERE clauses put in the end of the query. DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	string		Full SQL query
	 * @depreciated
	 * @see t3lib_DB::exec_SELECT_mm_query()
	 */
	function mm_query($select,$local_table,$mm_table,$foreign_table,$whereClause='',$groupBy='',$orderBy='',$limit='')	{
		$query = $GLOBALS['TYPO3_DB']->SELECTquery(
					$select,
					$local_table.','.$mm_table.($foreign_table?','.$foreign_table:''),
					$local_table.'.uid='.$mm_table.'.uid_local'.($foreign_table?' AND '.$foreign_table.'.uid='.$mm_table.'.uid_foreign':'').' '.
						$whereClause,	// whereClauseMightContainGroupOrderBy
					$groupBy,
					$orderBy,
					$limit
				);
		return $query;
	}

	/**
	 * Creates an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
	 * DEPRECIATED - $GLOBALS['TYPO3_DB']->INSERTquery() directly instead! But better yet, use $GLOBALS['TYPO3_DB']->exec_INSERTquery()
	 *
	 * @param	string		Table name
	 * @param	array		Field values as key=>value pairs.
	 * @return	string		Full SQL query for INSERT
	 * @depreciated
	 */
	function DBcompileInsert($table,$fields_values)	{
		return $GLOBALS['TYPO3_DB']->INSERTquery($table, $fields_values);
	}

	/**
	 * Creates an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 * DEPRECIATED - $GLOBALS['TYPO3_DB']->UPDATEquery() directly instead! But better yet, use $GLOBALS['TYPO3_DB']->exec_UPDATEquery()
	 *
	 * @param	string		Database tablename
	 * @param	string		WHERE clause, eg. "uid=1"
	 * @param	array		Field values as key=>value pairs.
	 * @return	string		Full SQL query for UPDATE
	 * @depreciated
	 */
	function DBcompileUpdate($table,$where,$fields_values)	{
		return $GLOBALS['TYPO3_DB']->UPDATEquery($table, $where, $fields_values);
	}










	/*******************************************
	 *
	 * Page tree, TCA related
	 *
	 *******************************************/

	/**
	 * Returns what is called the 'RootLine'. That is an array with information about the page records from a page id ($uid) and back to the root.
	 * By default deleted pages are filtered.
	 * This RootLine will follow the tree all the way to the root. This is opposite to another kind of root line known from the frontend where the rootline stops when a root-template is found.
	 *
	 * Usage: 13
	 *
	 * @param	integer		Page id for which to create the root line.
	 * @param	string		$clause can be used to select other criteria. It would typically be where-clauses that stops the proces if we meet a page, the user has no reading access to.
	 * @return	array		Root line array, all the way to the page tree root (or as far as $clause allows!)
	 */
	function BEgetRootLine ($uid,$clause='')	{
		$loopCheck = 100;
		$theRowArray = Array();
		$output=Array();
		while ($uid!=0 && $loopCheck>0)	{
			$loopCheck--;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'pid,uid,title,TSconfig,is_siteroot,storage_pid',
							'pages',
							'uid='.intval($uid).' '.
								t3lib_BEfunc::deleteClause('pages').' '.
								$clause		// whereClauseMightContainGroupOrderBy
						);
			if ($GLOBALS['TYPO3_DB']->sql_error())	{
				debug($GLOBALS['TYPO3_DB']->sql_error(),1);
			}
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$uid = $row['pid'];
				$theRowArray[]=$row;
			} else {
				break;
			}
		}
		if ($uid==0) {$theRowArray[]=Array('uid'=>0,'title'=>'');}
		if (is_array($theRowArray))	{
			reset($theRowArray);
			$c=count($theRowArray);
			while(list($key,$val)=each($theRowArray))	{
				$c--;
				$output[$c]['uid'] = $val['uid'];
				$output[$c]['title'] = $val['title'];
				$output[$c]['TSconfig'] = $val['TSconfig'];
				$output[$c]['is_siteroot'] = $val['is_siteroot'];
				$output[$c]['storage_pid'] = $val['storage_pid'];
			}
		}
		return $output;
	}

	/**
	 * Returns the path (visually) of a page $uid, fx. "/First page/Second page/Another subpage"
	 * Each part of the path will be limited to $titleLimit characters
	 * Deleted pages are filtered out.
	 *
	 * Usage: 23
	 *
	 * @param	integer		Page uid for which to create record path
	 * @param	string		$clause is additional where clauses, eg. "
	 * @param	integer		Title limit
	 * @param	integer		Title limit of Full title (typ. set to 1000 or so)
	 * @return	mixed		Path of record (string) OR array with short/long title if $fullTitleLimit is set.
	 */
	function getRecordPath($uid, $clause, $titleLimit, $fullTitleLimit=0)	{
		if (!$titleLimit) { $titleLimit=1000; }

		$loopCheck = 100;
		$output = $fullOutput = '/';
		while ($uid!=0 && $loopCheck>0)	{
			$loopCheck--;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'pid,title',
						'pages',
						'uid='.intval($uid).
							t3lib_BEfunc::deleteClause('pages').
							(strlen(trim($clause)) ? ' AND '.$clause : '')
					);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$uid = $row['pid'];
				$output = '/'.t3lib_div::fixed_lgd(strip_tags($row['title']),$titleLimit).$output;
				if ($fullTitleLimit)	$fullOutput = '/'.t3lib_div::fixed_lgd(strip_tags($row['title']),$fullTitleLimit).$fullOutput;
			} else {
				break;
			}
		}

		if ($fullTitleLimit)	{
			return array($output, $fullOutput);
		} else {
			return $output;
		}
	}

	/**
	 * Returns an array with the exclude-fields as defined in TCA
	 * Used for listing the exclude-fields in be_groups forms
	 *
	 * Usage: 2 (t3lib_tceforms + t3lib_transferdata)
	 *
	 * @return	array		Array of arrays with excludeFields (fieldname, table:fieldname) from all TCA entries
	 */
	function getExcludeFields()	{
		global $TCA;
			// All TCA keys:
		$theExcludeArray = Array();
		$tc_keys = array_keys($TCA);
		foreach($tc_keys as $table)	{
				// Load table
			t3lib_div::loadTCA($table);
				// All field names configured:
			if (is_array($TCA[$table]['columns']))	{
				$f_keys = array_keys($TCA[$table]['columns']);
				foreach($f_keys as $field)	{
					if ($TCA[$table]['columns'][$field]['exclude'])	{
							// Get Human Readable names of fields and table:
						$Fname=$GLOBALS['LANG']->sl($TCA[$table]['ctrl']['title']).': '.$GLOBALS['LANG']->sl($TCA[$table]['columns'][$field]['label']);
							// add entry:
						$theExcludeArray[] = Array($Fname , $table.':'.$field);
					}
				}
			}
		}
		return $theExcludeArray;
	}

	/**
	 * Returns a page record (of page with $id) with an extra field "_thePath" set to the record path IF the WHERE clause, $perms_clause, selects the record.
	 * If $id is zero a pseudo root-page with "_thePath" set is returned IF the current BE_USER is admin.
	 * In any case ->isInWebMount must return true for the user (regardless of $perms_clause)
	 *
	 * Usage: 21
	 *
	 * @param	integer		Page uid for which to check read-access
	 * @param	string		$perms_clause is typically a value generated with $BE_USER->getPagePermsClause(1);
	 * @return	array		Returns page record if OK, otherwise false.
	 */
	function readPageAccess($id,$perms_clause)	{
		if ((string)$id!='')	{
			$id = intval($id);
			if (!$id)	{
				if ($GLOBALS['BE_USER']->isAdmin())	{
					$path = '/';
					$pageinfo['_thePath'] = $path;
					return $pageinfo;
				}
			} else {
				$pageinfo = t3lib_BEfunc::getRecord('pages',$id,'*',($perms_clause ? ' AND '.$perms_clause : ''));
				if ($pageinfo['uid'] && $GLOBALS['BE_USER']->isInWebMount($id,$perms_clause))	{
					list($pageinfo['_thePath'],$pageinfo['_thePathFull']) = t3lib_BEfunc::getRecordPath(intval($pageinfo['uid']), $perms_clause, 15, 1000);
					return $pageinfo;
				}
			}
		}
		return false;
	}

	/**
	 * Returns the "types" configuration parsed into an array for the record, $rec, from table, $table
	 *
	 * Usage: 6
	 *
	 * @param	string		Table name (present in TCA)
	 * @param	array		Record from $table
	 * @param	boolean		If $useFieldNameAsKey is set, then the fieldname is associative keys in the return array, otherwise just numeric keys.
	 * @return	array
	 */
	function getTCAtypes($table,$rec,$useFieldNameAsKey=0)	{
		global $TCA;

		t3lib_div::loadTCA($table);
		if ($TCA[$table])	{

				// Get type value:
			$fieldValue = t3lib_BEfunc::getTCAtypeValue($table,$rec);

				// Get typesConf
			$typesConf = $TCA[$table]['types'][$fieldValue];

				// Get fields list and traverse it
			$fieldList = explode(',', $typesConf['showitem']);
			$altFieldList = array();

				// Traverse fields in types config and parse the configuration into a nice array:
			foreach($fieldList as $k => $v)	{
				list($pFieldName, $pAltTitle, $pPalette, $pSpec) = t3lib_div::trimExplode(';', $v);
				$defaultExtras = is_array($TCA[$table]['columns'][$pFieldName]) ? $TCA[$table]['columns'][$pFieldName]['defaultExtras'] : '';
				$specConfParts = t3lib_BEfunc::getSpecConfParts($pSpec, $defaultExtras);

				$fieldList[$k]=array(
					'field' => $pFieldName,
					'title' => $pAltTitle,
					'palette' => $pPalette,
					'spec' => $specConfParts,
					'origString' => $v
				);
				if ($useFieldNameAsKey)	{
					$altFieldList[$fieldList[$k]['field']] = $fieldList[$k];
				}
			}
			if ($useFieldNameAsKey)	{
				$fieldList = $altFieldList;
			}

				// Return array:
			return $fieldList;
		}
	}

	/**
	 * Returns the "type" value of $rec from $table which can be used to look up the correct "types" rendering section in $TCA
	 * If no "type" field is configured in the "ctrl"-section of the $TCA for the table, zero is used.
	 * If zero is not an index in the "types" section of $TCA for the table, then the $fieldValue returned will default to 1 (no matter if that is an index or not)
	 *
	 * Usage: 7
	 *
	 * @param	string		Table name present in TCA
	 * @param	array		Record from $table
	 * @return	string		Field value
	 * @see getTCAtypes()
	 */
	function getTCAtypeValue($table,$rec)	{
		global $TCA;

			// If no field-value, set it to zero. If there is no type matching the field-value (which now may be zero...) test field-value '1' as default.
		t3lib_div::loadTCA($table);
		if ($TCA[$table])	{
			$field = $TCA[$table]['ctrl']['type'];
			$fieldValue = $field ? ($rec[$field] ? $rec[$field] : 0) : 0;
			if (!is_array($TCA[$table]['types'][$fieldValue]))	$fieldValue = 1;
			return $fieldValue;
		}
	}

	/**
	 * Parses a part of the field lists in the "types"-section of $TCA arrays, namely the "special configuration" at index 3 (position 4)
	 * Elements are splitted by ":" and within those parts, parameters are splitted by "|".
	 * Everything is returned in an array and you should rather see it visually than listen to me anymore now...  Check out example in Inside TYPO3
	 *
	 * Usage: 3
	 *
	 * @param	string		Content from the "types" configuration of TCA (the special configuration) - see description of function
	 * @param	string		The ['defaultExtras'] value from field configuration
	 * @return	array
	 */
	function getSpecConfParts($str, $defaultExtras)	{

			// Add defaultExtras:
		$specConfParts = t3lib_div::trimExplode(':', $defaultExtras.':'.$str, 1);

		if (count($specConfParts))	{
			foreach($specConfParts as $k2 => $v2)	{
				unset($specConfParts[$k2]);
				if (ereg('(.*)\[(.*)\]',$v2,$reg))	{
					$specConfParts[trim($reg[1])] = array(
						'parameters' => t3lib_div::trimExplode('|', $reg[2], 1)
					);
				} else {
					$specConfParts[trim($v2)] = 1;
				}
			}
		} else {
			$specConfParts = array();
		}
		return $specConfParts;
	}

	/**
	 * Takes an array of "[key]=[value]" strings and returns an array with the keys set as keys pointing to the value.
	 * Better see it in action! Find example in Inside TYPO3
	 *
	 * Usage: 6
	 *
	 * @param	array		Array of "[key]=[value]" strings to convert.
	 * @return	array
	 */
	function getSpecConfParametersFromArray($pArr)	{
		$out=array();
		if (is_array($pArr))	{
			reset($pArr);
			while(list($k,$v)=each($pArr))	{
				$parts=explode('=',$v,2);
				if (count($parts)==2)	{
					$out[trim($parts[0])]=trim($parts[1]);
				} else {
					$out[$k]=$v;
				}
			}
		}
		return $out;
	}

	/**
	 * Finds the Data Structure for a FlexForm field
	 *
	 * @param	array		Field config array
	 * @param	array		Record data
	 * @param	string		The table name
	 * @return	mixed		If array, the data structure was found and returned as an array. Otherwise (string) it is an error message.
	 * @see t3lib_TCEforms::getSingleField_typeFlex()
	 */
	function getFlexFormDS($conf,$row,$table)	{

			// Get pointer field etc from TCA-config:
		$ds_pointerField = 	$conf['ds_pointerField'];
		$ds_array = 		$conf['ds'];
		$ds_tableField = 	$conf['ds_tableField'];
		$ds_searchParentField = 	$conf['ds_pointerField_searchParent'];


			// Find source value:
		$dataStructArray='';
		if (is_array($ds_array))	{	// If there is a data source array, that takes precedence
				// If a pointer field is set, take the value from that field in the $row array and use as key.
			if ($ds_pointerField)	{
				$srcPointer = $row[$ds_pointerField];
				$srcPointer = isset($ds_array[$srcPointer]) ? $srcPointer : 'default';
			} else $srcPointer='default';

				// Get Data Source: Detect if it's a file reference and in that case read the file and parse as XML. Otherwise the value is expected to be XML.
			if (substr($ds_array[$srcPointer],0,5)=='FILE:')	{
				$file = t3lib_div::getFileAbsFileName(substr($ds_array[$srcPointer],5));
				if ($file && @is_file($file))	{
					$dataStructArray = t3lib_div::xml2array(t3lib_div::getUrl($file));
				} else $dataStructArray = 'The file "'.substr($dsSrc,5).'" in ds-array key "'.$srcPointer.'" was not found ("'.$file.'")';	// Error message.
			} else {
				$dataStructArray = t3lib_div::xml2array($ds_array[$srcPointer]);
			}

		} elseif ($ds_pointerField) {	// If pointer field AND possibly a table/field is set:
				// Value of field pointed to:
			$srcPointer = $row[$ds_pointerField];

				// Searching recursively back if 'ds_pointerField_searchParent' is defined (typ. a page rootline, or maybe a tree-table):
			if ($ds_searchParentField && !$srcPointer)	{
				$rr = t3lib_BEfunc::getRecord($table,$row['uid'],$ds_searchParentField);	// Get the "pid" field - we cannot know that it is in the input record!
				$uidAcc=array();	// Used to avoid looping, if any should happen.
				$subFieldPointer = $conf['ds_pointerField_searchParent_subField'];
				while(!$srcPointer)		{
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
									'uid,'.$ds_pointerField.','.$ds_searchParentField.($subFieldPointer?','.$subFieldPointer:''),
									$table,
									'uid='.intval($rr[$ds_searchParentField]).t3lib_BEfunc::deleteClause($table)
								);
					$rr = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					if (!is_array($rr) || isset($uidAcc[$rr['uid']]))	break;	// break if no result from SQL db or if looping...
					$uidAcc[$rr['uid']]=1;

					$srcPointer = ($subFieldPointer && $rr[$subFieldPointer]) ? $rr[$subFieldPointer] : $rr[$ds_pointerField];
				}
			}

				// If there is a srcPointer value:
			if ($srcPointer)	{
				if (t3lib_div::testInt($srcPointer))	{	// If integer, then its a record we will look up:
					list($tName,$fName) = explode(':',$ds_tableField,2);
					if ($tName && $fName && is_array($GLOBALS['TCA'][$tName]))	{
						$dataStructRec = t3lib_BEfunc::getRecord($tName, $srcPointer);
						$dataStructArray = t3lib_div::xml2array($dataStructRec[$fName]);
					} else $dataStructArray = 'No tablename ('.$tName.') or fieldname ('.$fName.') was found an valid!';
				} else {	// Otherwise expect it to be a file:
					$file = t3lib_div::getFileAbsFileName($srcPointer);
					if ($file && @is_file($file))	{
						$dataStructArray = t3lib_div::xml2array(t3lib_div::getUrl($file));
					} else $dataStructArray='The file "'.$srcPointer.'" was not found ("'.$file.'")';	// Error message.
				}
			} else $dataStructArray='No source value in fieldname "'.$ds_pointerField.'"';	// Error message.
		} else $dataStructArray='No proper configuration!';

		return $dataStructArray;
	}


















	/*******************************************
	 *
	 * Caching related
	 *
	 *******************************************/

	/**
	 * Stores the string value $data in the 'cache_hash' table with the hash key, $hash, and visual/symbolic identification, $ident
	 * IDENTICAL to the function by same name found in t3lib_page:
	 *
	 * Usage: 2
	 *
	 * @param	string		Hash key, 32 bytes hex
	 * @param	string		$data must be serialized before function call
	 * @param	string		Visual/symbolic identification (informational only)
	 * @return	void
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
	 * Retrieves the string content stored with hash key, $hash, in cache_hash
	 * IDENTICAL to the function by same name found in t3lib_page:
	 *
	 * Usage: 2
	 *
	 * @param	string		Hash key, 32 bytes hex
	 * @param	integer		$expTime represents the expire time in seconds. For instance a value of 3600 would allow cached content within the last hour, otherwise nothing is returned.
	 * @return	string
	 */
	function getHash($hash,$expTime)	{
			// if expTime is not set, the hash will never expire
		$expTime = intval($expTime);
		if ($expTime)	{
			$whereAdd = ' AND tstamp > '.(time()-$expTime);
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('content', 'cache_hash', 'hash="'.$GLOBALS['TYPO3_DB']->quoteStr($hash, 'cache_hash').'"'.$whereAdd);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $row['content'];
		}
	}








	/*******************************************
	 *
	 * TypoScript related
	 *
	 *******************************************/

	/**
	 * Returns the Page TSconfig for page with id, $id
	 * Requires class "t3lib_TSparser"
	 *
	 * Usage: 26 (spec. in ext info_pagetsconfig)
	 *
	 * @param	integer		Page uid for which to create Page TSconfig
	 * @param	array		If $rootLine is an array, that is used as rootline, otherwise rootline is just calculated
	 * @param	boolean		If $returnPartArray is set, then the array with accumulated Page TSconfig is returned non-parsed. Otherwise the output will be parsed by the TypoScript parser.
	 * @return	array		Page TSconfig
	 * @see t3lib_TSparser
	 */
	function getPagesTSconfig($id,$rootLine='',$returnPartArray=0)	{
		$id=intval($id);
		if (!is_array($rootLine))	{
			$rootLine = t3lib_BEfunc::BEgetRootLine($id,'');
		}
		ksort($rootLine);	// Order correctly, changed 030102
		reset($rootLine);
		$TSdataArray = array();
		$TSdataArray['defaultPageTSconfig']=$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'];	// Setting default configuration:
		while(list($k,$v)=each($rootLine))	{
			$TSdataArray['uid_'.$v['uid']]=$v['TSconfig'];
		}
		$TSdataArray = t3lib_TSparser::checkIncludeLines_array($TSdataArray);
		if ($returnPartArray)	{
			return $TSdataArray;
		}

			// Parsing the user TS (or getting from cache)
		$userTS = implode($TSdataArray,chr(10).'[GLOBAL]'.chr(10));
		$hash = md5('pageTS:'.$userTS);
		$cachedContent = t3lib_BEfunc::getHash($hash,0);
		$TSconfig = array();
		if (isset($cachedContent))	{
			$TSconfig = unserialize($cachedContent);
		} else {
			$parseObj = t3lib_div::makeInstance('t3lib_TSparser');
			$parseObj->parse($userTS);
			$TSconfig = $parseObj->setup;
			t3lib_BEfunc::storeHash($hash,serialize($TSconfig),'PAGES_TSconfig');
		}
		return $TSconfig;
	}

	/**
	 * Updates Page TSconfig for a page with $id
	 * The function seems to take $pageTS as an array with properties and compare the values with those that already exists for the "object string", $TSconfPrefix, for the page, then sets those values which were not present.
	 * $impParams can be supplied as already known Page TSconfig, otherwise it's calculated.
	 *
	 * THIS DOES NOT CHECK ANY PERMISSIONS. SHOULD IT?
	 * More documentation is needed.
	 *
	 * Usage: 1 (ext. direct_mail)
	 *
	 * @param	integer		Page id
	 * @param	array		Page TS array to write
	 * @param	string		Prefix for object paths
	 * @param	array		[Description needed.]
	 * @return	void
	 * @internal
	 * @see implodeTSParams(), getPagesTSconfig()
	 */
	function updatePagesTSconfig($id,$pageTS,$TSconfPrefix,$impParams='')	{
		$id=intval($id);
		if (is_array($pageTS) && $id>0)	{
			if (!is_array($impParams))	{
				$impParams =t3lib_BEfunc::implodeTSParams(t3lib_BEfunc::getPagesTSconfig($id));
			}
			reset($pageTS);
			$set=array();
			while(list($f,$v)=each($pageTS))	{
				$f = $TSconfPrefix.$f;
				if ((!isset($impParams[$f])&&trim($v)) || strcmp(trim($impParams[$f]),trim($v)))	{
					$set[$f]=trim($v);
				}
			}
			if (count($set))	{
					// Get page record and TS config lines
				$pRec = t3lib_befunc::getRecord('pages',$id);
				$TSlines = explode(chr(10),$pRec['TSconfig']);
				$TSlines = array_reverse($TSlines);
					// Reset the set of changes.
				reset($set);
				while(list($f,$v)=each($set))	{
					reset($TSlines);
					$inserted=0;
					while(list($ki,$kv)=each($TSlines))	{
						if (substr($kv,0,strlen($f)+1)==$f.'=')	{
							$TSlines[$ki]=$f.'='.$v;
							$inserted=1;
							break;
						}
					}
					if (!$inserted)	{
						$TSlines = array_reverse($TSlines);
						$TSlines[]=$f.'='.$v;
						$TSlines = array_reverse($TSlines);
					}
				}
				$TSlines = array_reverse($TSlines);

					// store those changes
				$TSconf = implode(chr(10),$TSlines);

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'uid='.intval($id), array('TSconfig' => $TSconf));
			}
		}
	}

	/**
	 * Implodes a multi dimensional TypoScript array, $p, into a one-dimentional array (return value)
	 *
	 * Usage: 3
	 *
	 * @param	array		TypoScript structure
	 * @param	string		Prefix string
	 * @return	array		Imploded TypoScript objectstring/values
	 */
	function implodeTSParams($p,$k='')	{
		$implodeParams=array();
		if (is_array($p))	{
			reset($p);
			while(list($kb,$val)=each($p))	{
				if (is_array($val))	{
					$implodeParams = array_merge($implodeParams,t3lib_BEfunc::implodeTSParams($val,$k.$kb));
				} else {
					$implodeParams[$k.$kb]=$val;
				}
			}
		}
		return $implodeParams;
	}








	/*******************************************
	 *
	 * Users / Groups related
	 *
	 *******************************************/

	/**
	 * Returns an array with be_users records of all user NOT DELETED sorted by their username
	 * Keys in the array is the be_users uid
	 *
	 * Usage: 14 (spec. ext. "beuser" and module "web_perm")
	 *
	 * @param	string		Optional $fields list (default: username,usergroup,usergroup_cached_list,uid) can be used to set the selected fields
	 * @param	string		Optional $where clause (fx. "AND username='pete'") can be used to limit query
	 * @return	array
	 */
	function getUserNames($fields='username,usergroup,usergroup_cached_list,uid',$where='')	{
		$be_user_Array=Array();

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, 'be_users', 'pid=0 '.$where.t3lib_BEfunc::deleteClause('be_users'), '', 'username');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$be_user_Array[$row['uid']]=$row;
		}
		return $be_user_Array;
	}

	/**
	 * Returns an array with be_groups records (title, uid) of all groups NOT DELETED sorted by their title
	 *
	 * Usage: 8 (spec. ext. "beuser" and module "web_perm")
	 *
	 * @param	string		Field list
	 * @param	string		WHERE clause
	 * @return	array
	 */
	function getGroupNames($fields='title,uid', $where='')	{
		$be_group_Array = Array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, 'be_groups', 'pid=0 '.$where.t3lib_BEfunc::deleteClause('be_groups'), '', 'title');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$be_group_Array[$row['uid']] = $row;
		}
		return $be_group_Array;
	}

	/**
	 * Returns an array with be_groups records (like ->getGroupNames) but:
	 * - if the current BE_USER is admin, then all groups are returned, otherwise only groups that the current user is member of (usergroup_cached_list) will be returned.
	 *
	 * Usage: 2 (module "web_perm" and ext. taskcenter)
	 *
	 * @param	string		Field list; $fields specify the fields selected (default: title,uid)
	 * @return	array
	 */
	function getListGroupNames($fields='title,uid')	{
		$exQ=' AND hide_in_lists=0';
		if (!$GLOBALS['BE_USER']->isAdmin())	{
			$exQ.=' AND uid IN ('.($GLOBALS['BE_USER']->user['usergroup_cached_list']?$GLOBALS['BE_USER']->user['usergroup_cached_list']:0).')';
		}
		return t3lib_BEfunc::getGroupNames($fields,$exQ);
	}

	/**
	 * Returns the array $usernames with the names of all users NOT IN $groupArray changed to the uid (hides the usernames!).
	 * If $excludeBlindedFlag is set, then these records are unset from the array $usernames
	 * Takes $usernames (array made by t3lib_BEfunc::getUserNames()) and a $groupArray (array with the groups a certain user is member of) as input
	 *
	 * Usage: 8
	 *
	 * @param	array		User names
	 * @param	array		Group names
	 * @param	boolean		If $excludeBlindedFlag is set, then these records are unset from the array $usernames
	 * @return	array		User names, blinded
	 */
	function blindUserNames($usernames,$groupArray,$excludeBlindedFlag=0)	{
		if (is_array($usernames) && is_array($groupArray))	{
			while(list($uid,$row)=each($usernames))	{
				$userN=$uid;
				$set=0;
				if ($row['uid']!=$GLOBALS['BE_USER']->user['uid'])	{
					reset($groupArray);
					while(list(,$v)=each($groupArray))	{
						if ($v && t3lib_div::inList($row['usergroup_cached_list'],$v))	{
							$userN = $row['username'];
							$set=1;
						}
					}
				} else {
					$userN = $row['username'];
					$set=1;
				}
				$usernames[$uid]['username']=$userN;
				if ($excludeBlindedFlag && !$set) {unset($usernames[$uid]);}
			}
		}
		return $usernames;
	}

	/**
	 * Corresponds to blindUserNames but works for groups instead
	 *
	 * Usage: 2 (module web_perm)
	 *
	 * @param	array		Group names
	 * @param	array		Group names (reference)
	 * @param	boolean		If $excludeBlindedFlag is set, then these records are unset from the array $usernames
	 * @return	array
	 */
	function blindGroupNames($groups,$groupArray,$excludeBlindedFlag=0)	{
		if (is_array($groups) && is_array($groupArray))	{
			while(list($uid,$row)=each($groups))	{
				$groupN=$uid;
				$set=0;
				if (t3lib_div::inArray($groupArray,$uid))	{
					$groupN=$row['title'];
					$set=1;
				}
				$groups[$uid]['title']=$groupN;
				if ($excludeBlindedFlag && !$set) {unset($groups[$uid]);}
			}
		}
		return $groups;
	}













	/*******************************************
	 *
	 * Output related
	 *
	 *******************************************/



	/**
	 * Returns the difference in days between input $tstamp and $EXEC_TIME
	 *
	 * Usage: 2 (class t3lib_BEfunc)
	 *
	 * @param	integer		Time stamp, seconds
	 * @return	integer
	 */
	function daysUntil($tstamp)	{
		$delta_t = $tstamp-$GLOBALS['EXEC_TIME'];
		return ceil($delta_t/(3600*24));
	}

	/**
	 * Returns $tstamp formatted as "ddmmyy" (According to $TYPO3_CONF_VARS['SYS']['ddmmyy'])
	 *
	 * Usage: 11
	 *
	 * @param	integer		Time stamp, seconds
	 * @return	string		Formatted time
	 */
	function date($tstamp)	{
		return Date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],$tstamp);
	}

	/**
	 * Returns $tstamp formatted as "ddmmyy hhmm" (According to $TYPO3_CONF_VARS['SYS']['ddmmyy'] AND $TYPO3_CONF_VARS['SYS']['hhmm'])
	 *
	 * Usage: 28
	 *
	 * @param	integer		Time stamp, seconds
	 * @return	string		Formatted time
	 */
	function datetime($value)	{
		return Date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'].' '.$GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],$value);
	}

	/**
	 * Returns $value (in seconds) formatted as hh:mm:ss
	 * For instance $value = 3600 + 60*2 + 3 should return "01:02:03"
	 *
	 * Usage: 1 (class t3lib_BEfunc)
	 *
	 * @param	integer		Time stamp, seconds
	 * @return	string		Formatted time
	 */
	function time($value)	{
		$hh = floor($value/3600);
		$min = floor(($value-$hh*3600)/60);
		$sec = $value-$hh*3600-$min*60;
		$l = sprintf('%02d',$hh).':'.sprintf('%02d',$min).':'.sprintf('%02d',$sec);
		return $l;
	}

	/**
	 * Returns the "age" of the number of $seconds inputted.
	 *
	 * Usage: 15
	 *
	 * @param	integer		$seconds could be the difference of a certain timestamp and time()
	 * @param	string		$labels should be something like ' min| hrs| days| yrs'. This value is typically delivered by this function call: $GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears")
	 * @return	string		Formatted time
	 */
	function calcAge ($seconds,$labels)	{
		$labelArr = explode('|',$labels);
		$prefix='';
		if ($seconds<0)	{$prefix='-'; $seconds=abs($seconds);}
		if ($seconds<3600)	{
			$seconds = round ($seconds/60).$labelArr[0];
		} elseif ($seconds<24*3600)	{
			$seconds = round ($seconds/3600).$labelArr[1];
		} elseif ($seconds<365*24*3600)	{
			$seconds = round ($seconds/(24*3600)).$labelArr[2];
		} else {
			$seconds = round ($seconds/(365*24*3600)).$labelArr[3];
		}
		return $prefix.$seconds;
	}

	/**
	 * Returns a formatted timestamp if $tstamp is set.
	 * The date/datetime will be followed by the age in parenthesis.
	 *
	 * Usage: 3
	 *
	 * @param	integer		Time stamp, seconds
	 * @param	integer		1/-1 depending on polarity of age.
	 * @param	string		$date=="date" will yield "dd:mm:yy" formatting, otherwise "dd:mm:yy hh:mm"
	 * @return	string
	 */
	function dateTimeAge($tstamp,$prefix=1,$date='')	{
		return $tstamp ?
				($date=='date' ? t3lib_BEfunc::date($tstamp) : t3lib_BEfunc::datetime($tstamp)).
				' ('.t3lib_BEfunc::calcAge($prefix*(time()-$tstamp),$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')).')' : '';
	}

	/**
	 * Returns either title='' or alt='' attribute. This depends on the client browser and whether it supports title='' or not (which is the default)
	 * If no $content is given only the attribute name is returned.
	 * The returned attribute with content will have a leading space char.
	 * Warning: Be careful to submit empty $content var - that will return just the attribute name!
	 *
	 * Usage: 203
	 *
	 * @param	string		String to set as title-attribute. If no $content is given only the attribute name is returned.
	 * @param	boolean		If $hsc is set, then content of the attribute is htmlspecialchar()'ed (which is good for XHTML and other reasons...)
	 * @return	string
	 * @depreciated		The idea made sense with older browsers, but now all browsers should support the "title" attribute - so just hardcode the title attribute instead!
	 */
	function titleAttrib($content='',$hsc=0)	{
		global $CLIENT;
		$attrib= ($CLIENT['BROWSER']=='net'&&$CLIENT['VERSION']<5)||$CLIENT['BROWSER']=='konqu' ? 'alt' : 'title';
		return strcmp($content,'')?' '.$attrib.'="'.($hsc?htmlspecialchars($content):$content).'"' : $attrib;
	}

	/**
	 * Returns alt="" and title="" attributes with the value of $content.
	 *
	 * @param	string		Value for 'alt' and 'title' attributes (will be htmlspecialchars()'ed before output)
	 * @return	string
	 */
	function titleAltAttrib($content)	{
		$out='';
		$out.=' alt="'.htmlspecialchars($content).'"';
		$out.=' title="'.htmlspecialchars($content).'"';
		return $out;
	}

	/**
	 * Returns a linked image-tag for thumbnail(s) from a database row with a list of image files in a field
	 * All $TYPO3_CONF_VARS['GFX']['imagefile_ext'] extension are made to thumbnails + ttf file (renders font-example)
	 * Thumbsnails are linked to the show_item.php script which will display further details.
	 *
	 * Usage: 7
	 *
	 * @param	array		$row is the database row from the table, $table.
	 * @param	string		Table name for $row (present in TCA)
	 * @param	string		$field is pointing to the field with the list of image files
	 * @param	string		Back path prefix for image tag src="" field
	 * @param	string		Optional: $thumbScript os by default 'thumbs.php' if you don't set it otherwise
	 * @param	string		Optional: $uploaddir is the directory relative to PATH_site where the image files from the $field value is found (Is by default set to the entry in $TCA for that field! so you don't have to!)
	 * @param	boolean		If set, uploaddir is NOT prepended with "../"
	 * @param	string		Optional: $tparams is additional attributes for the image tags
	 * @param	integer		Optional: $size is [w]x[h] of the thumbnail. 56 is default.
	 * @return	string		Thumbnail image tag.
	 */
	function thumbCode($row,$table,$field,$backPath,$thumbScript='',$uploaddir='',$abs=0,$tparams='',$size='')	{
		global $TCA;
			// Load table.
		t3lib_div::loadTCA($table);

			// Find uploaddir automatically
		$uploaddir = $uploaddir ? $uploaddir : $TCA[$table]['columns'][$field]['config']['uploadfolder'];

			// Set thumbs-script:
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'])	{
			$thumbScript='gfx/notfound_thumb.gif';
		} elseif(!$thumbScript)	{
			$thumbScript='thumbs.php';
		}
			// Check and parse the size parameter
		$sizeParts=array();
		if ($size = trim($size)) {
			$sizeParts = explode('x', $size.'x'.$size);
			if(!intval($sizeParts[0])) $size='';
		}

			// Traverse files:
		$thumbs = explode(',', $row[$field]);
		$thumbData='';
		while(list(,$theFile)=each($thumbs))	{
			if (trim($theFile))	{
				$fI = t3lib_div::split_fileref($theFile);
				$ext = $fI['fileext'];
						// New 190201 start
				$max=0;
				if (t3lib_div::inList('gif,jpg,png',$ext)) {
					$imgInfo=@getimagesize(PATH_site.$uploaddir.'/'.$theFile);
					if (is_array($imgInfo))	{$max = max($imgInfo);}
				}
					// use the original image if it's size fits to the thumbnail size
				if ($max && $max<=(count($sizeParts)&&max($sizeParts)?max($sizeParts):56))	{
					$url = $uploaddir.'/'.trim($theFile);
					$theFile = '../'.$url;
					$onClick='top.launchView(\''.$theFile.'\',\'\',\''.$backPath.'\');return false;';
					$thumbData.='<a href="#" onclick="'.htmlspecialchars($onClick).'"><img src="../'.$backPath.$url.'" '.$imgInfo[3].' hspace="2" border="0" title="'.trim($url).'"'.$tparams.' alt="" /></a> ';
						// New 190201 stop
				} elseif ($ext=='ttf' || t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],$ext)) {
					$theFile = ($abs?'':'../').$uploaddir.'/'.trim($theFile);
					$params = '&file='.rawurlencode($theFile);
					$params .= $size?'&size='.$size:'';
					$url = $thumbScript.'?&dummy='.$GLOBALS['EXEC_TIME'].$params;
					$onClick='top.launchView(\''.$theFile.'\',\'\',\''.$backPath.'\');return false;';
					$thumbData.='<a href="#" onclick="'.htmlspecialchars($onClick).'"><img src="'.htmlspecialchars($backPath.$url).'" hspace="2" border="0" title="'.trim($theFile).'"'.$tparams.' alt="" /></a> ';
				} else {
					$icon = t3lib_BEfunc::getFileIcon($ext);
					$url = 'gfx/fileicons/'.$icon;
					$thumbData.='<img src="'.$backPath.$url.'" hspace="2" border="0" title="'.trim($theFile).'"'.$tparams.' alt="" /> ';
				}
			}
		}
		return $thumbData;
	}

	/**
	 * Returns single image tag to thumbnail
	 *
	 * Usage: 3
	 *
	 * @param	string		$thumbScript must point to "thumbs.php" relative to the script position
	 * @param	string		$theFile must be the proper reference to the file thumbs.php should show
	 * @param	string		$tparams are additional attributes for the image tag
	 * @param	integer		$size is the size of the thumbnail send along to "thumbs.php"
	 * @return	string		Image tag
	 */
	function getThumbNail($thumbScript,$theFile,$tparams='',$size='')	{
		$params = '&file='.rawurlencode($theFile);
		$params .= trim($size)?'&size='.trim($size):'';
		$url = $thumbScript.'?&dummy='.$GLOBALS['EXEC_TIME'].$params;
		$th='<img src="'.htmlspecialchars($url).'" title="'.trim(basename($theFile)).'"'.($tparams?" ".$tparams:"").' alt="" />';
		return $th;
	}

	/**
	 * Returns title-attribute information for a page-record informing about id, alias, doktype, hidden, starttime, endtime, fe_group etc.
	 *
	 * Usage: 8
	 *
	 * @param	array		Input must be a page row ($row) with the proper fields set (be sure - send the full range of fields for the table)
	 * @param	string		$perms_clause is used to get the record path of the shortcut page, if any (and doktype==4)
	 * @param	boolean		If $includeAttrib is set, then the 'title=""' attribute is wrapped about the return value, which is in any case htmlspecialchar()'ed already
	 * @return	string
	 */
	function titleAttribForPages($row,$perms_clause='',$includeAttrib=1)	{
		global $TCA,$LANG;
		$parts=array();
		$parts[] = 'id='.$row['uid'];
		if ($row['alias'])	$parts[]=$LANG->sL($TCA['pages']['columns']['alias']['label']).' '.$row['alias'];
		if ($row['doktype']=='3')	{
			$parts[]=$LANG->sL($TCA['pages']['columns']['url']['label']).' '.$row['url'];
		} elseif ($row['doktype']=='4')	{
			if ($perms_clause)	{
				$label = t3lib_BEfunc::getRecordPath(intval($row['shortcut']),$perms_clause,20);
			} else {
				$lRec = t3lib_BEfunc::getRecord('pages',intval($row['shortcut']),'title');
				$label = $lRec['title'];
			}
			if ($row['shortcut_mode']>0)	{
				$label.=', '.$LANG->sL($TCA['pages']['columns']['shortcut_mode']['label']).' '.
							$LANG->sL(t3lib_BEfunc::getLabelFromItemlist('pages','shortcut_mode',$row['shortcut_mode']));
			}
			$parts[]=$LANG->sL($TCA['pages']['columns']['shortcut']['label']).' '.$label;
		} elseif ($row['doktype']=='7')	{
			if ($perms_clause)	{
				$label = t3lib_BEfunc::getRecordPath(intval($row['mount_pid']),$perms_clause,20);
			} else {
				$lRec = t3lib_BEfunc::getRecord('pages',intval($row['mount_pid']),'title');
				$label = $lRec['title'];
			}
			$parts[]=$LANG->sL($TCA['pages']['columns']['mount_pid']['label']).' '.$label;
			if ($row['mount_pid_ol'])	{
				$parts[] = $LANG->sL($TCA['pages']['columns']['mount_pid_ol']['label']);
			}
		}
		if ($row['nav_hide'])	$parts[] = ereg_replace(':$','',$LANG->sL($TCA['pages']['columns']['nav_hide']['label']));
		if ($row['hidden'])	$parts[] = $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.hidden');
		if ($row['starttime'])	$parts[] = $LANG->sL($TCA['pages']['columns']['starttime']['label']).' '.t3lib_BEfunc::dateTimeAge($row['starttime'],-1,'date');
		if ($row['endtime'])	$parts[] = $LANG->sL($TCA['pages']['columns']['endtime']['label']).' '.t3lib_BEfunc::dateTimeAge($row['endtime'],-1,'date');
		if ($row['fe_group'])	{
			if ($row['fe_group']<0)	{
				$label = $LANG->sL(t3lib_BEfunc::getLabelFromItemlist('pages','fe_group',$row['fe_group']));
			} else {
				$lRec = t3lib_BEfunc::getRecord('fe_groups',$row['fe_group'],'title');
				$label = $lRec['title'];
			}
			$parts[] = $LANG->sL($TCA['pages']['columns']['fe_group']['label']).' '.$label;
		}
		$out = htmlspecialchars(implode(' - ',$parts));
		return $includeAttrib ? 'title="'.$out.'"' : $out;
	}

	/**
	 * Returns title-attribute information for ANY record (from a table defined in TCA of course)
	 * The included information depends on features of the table, but if hidden, starttime, endtime and fe_group fields are configured for, information about the record status in regard to these features are is included.
	 * "pages" table can be used as well and will return the result of ->titleAttribForPages() for that page.
	 *
	 * Usage: 10
	 *
	 * @param	array		Table row; $row is a row from the table, $table
	 * @param	string		Table name
	 * @return	string
	 */
	function getRecordIconAltText($row,$table='pages')	{
		if ($table=='pages')	{
			$out = t3lib_BEfunc::titleAttribForPages($row,'',0);
		} else {
			$ctrl = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'];

			$out='id='.$row['uid'];	// Uid is added
			if ($table=='pages' && $row['alias'])	{
				$out.=' / '.$row['alias'];
			}
			if ($ctrl['disabled'])	{		// Hidden ...
				$out.=($row[$ctrl['disabled']]?' - '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.hidden'):'');
			}
			if ($ctrl['starttime'])	{
				if ($row[$ctrl['starttime']] > $GLOBALS['EXEC_TIME'])	{
					$out.=' - '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.starttime').':'.t3lib_BEfunc::date($row[$ctrl['starttime']]).' ('.t3lib_BEfunc::daysUntil($row[$ctrl['starttime']]).' '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.days').')';
				}
			}
			if ($row[$ctrl['endtime']])	{
				$out.=' - '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.endtime').': '.t3lib_BEfunc::date($row[$ctrl['endtime']]).' ('.t3lib_BEfunc::daysUntil($row[$ctrl['endtime']]).' '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.days').')';
			}
		}
		return htmlspecialchars($out);
	}

	/**
	 * Returns the label of the first found entry in an "items" array from $TCA (tablename=$table/fieldname=$col) where the value is $key
	 *
	 * Usage: 9
	 *
	 * @param	string		Table name, present in $TCA
	 * @param	string		Field name, present in $TCA
	 * @param	string		items-array value to match
	 * @return	string		Label for item entry
	 */
	function getLabelFromItemlist($table,$col,$key)	{
		global $TCA;
			// Load full TCA for $table
		t3lib_div::loadTCA($table);

			// Check, if there is an "items" array:
		if (is_array($TCA[$table]) && is_array($TCA[$table]['columns'][$col]) && is_array($TCA[$table]['columns'][$col]['config']['items']))	{
				// Traverse the items-array...
			reset($TCA[$table]['columns'][$col]['config']['items']);
			while(list($k,$v)=each($TCA[$table]['columns'][$col]['config']['items']))	{
					// ... and return the first found label where the value was equal to $key
				if (!strcmp($v[1],$key))	return $v[0];
			}
		}
	}

	/**
	 * Returns the label-value for fieldname $col in table, $table
	 * If $printAllWrap is set (to a "wrap") then it's wrapped around the $col value IF THE COLUMN $col DID NOT EXIST in TCA!, eg. $printAllWrap='<b>|</b>' and the fieldname was 'not_found_field' then the return value would be '<b>not_found_field</b>'
	 *
	 * Usage: 17
	 *
	 * @param	string		Table name, present in $TCA
	 * @param	string		Field name
	 * @param	string		Wrap value - set function description
	 * @return	string
	 */
	function getItemLabel($table,$col,$printAllWrap='')	{
		global $TCA;
			// Load full TCA for $table
		t3lib_div::loadTCA($table);
			// Check if column exists
		if (is_array($TCA[$table]) && is_array($TCA[$table]['columns'][$col]))	{
				// Re
			return $TCA[$table]['columns'][$col]['label'];
		}
		if ($printAllWrap)	{
			$parts = explode('|',$printAllWrap);
			return $parts[0].$col.$parts[1];
		}
	}

	/**
	 * Returns the "title"-value in record, $row, from table, $table
	 * The field(s) from which the value is taken is determined by the "ctrl"-entries 'label', 'label_alt' and 'label_alt_force'
	 *
	 * Usage: 26
	 *
	 * @param	string		Table name, present in TCA
	 * @param	array		Row from table
	 * @param	boolean		If set, result is prepared for output: The output is cropped to a limited lenght (depending on BE_USER->uc['titleLen']) and if no value is found for the title, '<em>[No title]</em>' is returned (localized). Further, the output is htmlspecialchars()'ed
	 * @return	string
	 */
	function getRecordTitle($table,$row,$prep=0)	{
		global $TCA;
		if (is_array($TCA[$table]))	{
			$t = $row[$TCA[$table]['ctrl']['label']];
			if ($TCA[$table]['ctrl']['label_alt'] && ($TCA[$table]['ctrl']['label_alt_force'] || !strcmp($t,'')))	{
				$altFields=t3lib_div::trimExplode(',',$TCA[$table]['ctrl']['label_alt'],1);
				$tA=array();
				$tA[]=$t;
				while(list(,$fN)=each($altFields))	{
					$t = $tA[] = trim(strip_tags($row[$fN]));
					if (strcmp($t,'') && !$TCA[$table]['ctrl']['label_alt_force'])	break;
				}
				if ($TCA[$table]['ctrl']['label_alt_force'])	$t=implode(', ',$tA);
			}
			if ($prep) 	{
				$t = htmlspecialchars(t3lib_div::fixed_lgd($t,$GLOBALS['BE_USER']->uc['titleLen']));
				if (!strcmp(trim($t),''))	$t='<em>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title',1).']</em>';
			}
			return $t;
		}
	}

	/**
	 * Returns a human readable output of a value from a record
	 * For instance a database record relation would be looked up to display the title-value of that record. A checkbox with a "1" value would be "Yes", etc.
	 * $table/$col is tablename and fieldname
	 * REMEMBER to pass the output through htmlspecialchars() if you output it to the browser! (To protect it from XSS attacks and be XHTML compliant)
	 *
	 * Usage: 24
	 *
	 * @param	string		Table name, present in TCA
	 * @param	string		Field name, present in TCA
	 * @param	string		$value is the value of that field from a selected record
	 * @param	integer		$fixed_lgd_chars is the max amount of characters the value may occupy
	 * @param	boolean		$defaultPassthrough flag means that values for columns that has no conversion will just be pass through directly (otherwise cropped to 200 chars or returned as "N/A")
	 * @return	string
	 */
	function getProcessedValue($table,$col,$value,$fixed_lgd_chars=0,$defaultPassthrough=0)	{
		global $TCA;
			// Load full TCA for $table
		t3lib_div::loadTCA($table);
			// Check if table and field is configured:
		if (is_array($TCA[$table]) && is_array($TCA[$table]['columns'][$col]))	{
				// Depending on the fields configuration, make a meaningful output value.
			$theColConf = $TCA[$table]['columns'][$col]['config'];
			$l='';
			switch((string)$theColConf['type'])	{
				case 'radio':
					$l=t3lib_BEfunc::getLabelFromItemlist($table,$col,$value);
				break;
				case 'select':
					if ($theColConf['MM'])	{
						$l='N/A';
					} else {
						$l=t3lib_BEfunc::getLabelFromItemlist($table,$col,$value);
						$l=$GLOBALS['LANG']->sL($l);
						if ($theColConf['foreign_table'] && !$l && $TCA[$theColConf['foreign_table']])	{
							$rParts = t3lib_div::trimExplode(',',$value,1);
							reset($rParts);
							$lA=array();
							while(list(,$rVal)=each($rParts))	{
								$rVal = intval($rVal);
								if ($rVal>0) {
									$r=t3lib_BEfunc::getRecord($theColConf['foreign_table'],$rVal);
								} else {
									$r=t3lib_BEfunc::getRecord($theColConf['neg_foreign_table'],-$rVal);
								}
								if (is_array($r))	{
									$lA[]=$GLOBALS['LANG']->sL($rVal>0?$theColConf['foreign_table_prefix']:$theColConf['neg_foreign_table_prefix']).t3lib_BEfunc::getRecordTitle($rVal>0?$theColConf['foreign_table']:$theColConf['neg_foreign_table'],$r);
								} else {
									$lA[]=$rVal?'['.$rVal.'!]':'';
								}
							}
							$l=implode(',',$lA);
						}
					}
				break;
				case 'check':
					if (!is_array($theColConf['items']) || count($theColConf['items'])==1)	{
						$l = $value ? 'Yes' : '';
					} else {
						reset($theColConf['items']);
						$lA=Array();
						while(list($key,$val)=each($theColConf['items']))	{
							if ($value & pow(2,$key))	{$lA[]=$GLOBALS['LANG']->sL($val[0]);}
						}
						$l = implode($lA,', ');
					}
				break;
				case 'input':
					if ($value)	{
						if (t3lib_div::inList($theColConf['eval'],'date'))	{
							$l = t3lib_BEfunc::date($value).' ('.(time()-$value>0?'-':'').t3lib_BEfunc::calcAge(abs(time()-$value), $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')).')';
						} elseif (t3lib_div::inList($theColConf['eval'],'time'))	{
							$l = t3lib_BEfunc::time($value);
						} elseif (t3lib_div::inList($theColConf['eval'],'datetime'))	{
							$l = t3lib_BEfunc::datetime($value);
						} else {
							$l = $value;
						}
					}
				break;
				default:
					if ($defaultPassthrough)	{
						$l=$value;
					} elseif ($theColConf['MM'])	{
						$l='N/A';
					} elseif ($value)	{
						$l=t3lib_div::fixed_lgd(strip_tags($value),200);
					}
				break;
			}
			if ($fixed_lgd_chars)	{
				return t3lib_div::fixed_lgd($l,$fixed_lgd_chars);
			} else {
				return $l;
			}
		}
	}

	/**
	 * Same as ->getProcessedValue() but will go easy on fields like "tstamp" and "pid" which are not configured in TCA - they will be formatted by this function instead.
	 *
	 * Usage: 2
	 *
	 * @param	string		Table name, present in TCA
	 * @param	string		Field name
	 * @param	string		Field value
	 * @param	integer		$fixed_lgd_chars is the max amount of characters the value may occupy
	 * @return	string
	 * @see getProcessedValue()
	 */
	function getProcessedValueExtra($table,$fN,$fV,$fixed_lgd_chars=0)	{
		global $TCA;
		$fVnew = t3lib_BEfunc::getProcessedValue($table,$fN,$fV,$fixed_lgd_chars);
		if (!isset($fVnew))	{
			if (is_array($TCA[$table]))	{
				if ($fN==$TCA[$table]['ctrl']['tstamp'] || $fN==$TCA[$table]['ctrl']['crdate'])	{
					$fVnew = t3lib_BEfunc::datetime($fV);
				} elseif ($fN=='pid'){
					$fVnew = t3lib_BEfunc::getRecordPath($fV,'1',20);	// Fetches the path with no regard to the users permissions to select pages.
				} else {
					$fVnew = $fV;
				}
			}
		}
		return $fVnew;
	}

	/**
	 * Returns file icon name (from $FILEICONS) for the fileextension $ext
	 *
	 * Usage: 10
	 *
	 * @param	string		File extension, lowercase
	 * @return	string		File icon filename
	 */
	function getFileIcon($ext)	{
		return $GLOBALS['FILEICONS'][$ext] ? $GLOBALS['FILEICONS'][$ext] : $GLOBALS['FILEICONS']['default'];
	}

	/**
	 * Returns fields for a table, $table, which would typically be interesting to select
	 * This includes uid, the fields defined for title, icon-field.
	 * Returned as a list ready for query ($prefix can be set to eg. "pages." if you are selecting from the pages table and want the table name prefixed)
	 *
	 * Usage: 3
	 *
	 * @param	string		Table name, present in TCA
	 * @param	string		Table prefix
	 * @return	string		List of fields.
	 */
	function getCommonSelectFields($table,$prefix)	{
		global $TCA;
		$fields=array();
		$fields[]=$prefix.'uid';
		$fields[]=$prefix.$TCA[$table]['ctrl']['label'];
		if ($TCA[$table]['ctrl']['label_alt'])	{
			$secondFields = t3lib_div::trimExplode(',',$TCA[$table]['ctrl']['label_alt'],1);
			while(list(,$fieldN)=each($secondFields))	{
				$fields[]=$prefix.$fieldN;
			}
		}
		if ($TCA[$table]['ctrl']['selicon_field'])	$fields[]=$prefix.$TCA[$table]['ctrl']['selicon_field'];
		return implode(',',$fields);
	}

	/**
	 * Makes a form for configuration of some values based on configuration found in the array $configArray, with default values from $defaults and a data-prefix $dataPrefix
	 * <form>-tags must be supplied separately
	 * Needs more documentation and examples, in particular syntax for configuration array. See Inside TYPO3. That's were you can expect to find example, if anywhere.
	 *
	 * Usage: 1 (ext. direct_mail)
	 *
	 * @param	array		Field configuration code.
	 * @param	array		Defaults
	 * @param	string		Prefix for formfields
	 * @return	string		HTML for a form.
	 */
	function makeConfigForm($configArray,$defaults,$dataPrefix)	{
		$params = $defaults;
		if (is_array($configArray))	{
			reset($configArray);
			$lines=array();
			while(list($fname,$config)=each($configArray))	{
				if (is_array($config))	{
					$lines[$fname]='<strong>'.htmlspecialchars($config[1]).'</strong><br />';
					$lines[$fname].=$config[2].'<br />';
					switch($config[0])	{
						case 'string':
						case 'short':
							$formEl = '<input type="text" name="'.$dataPrefix.'['.$fname.']" value="'.$params[$fname].'"'.$GLOBALS['TBE_TEMPLATE']->formWidth($config[0]=='short'?24:48).' />';
						break;
						case 'check':
							$formEl = '<input type="hidden" name="'.$dataPrefix.'['.$fname.']" value="0" /><input type="checkbox" name="'.$dataPrefix.'['.$fname.']" value="1"'.($params[$fname]?' checked="checked"':'').' />';
						break;
						case 'comment':
							$formEl = '';
						break;
						case 'select':
							reset($config[3]);
							$opt=array();
							while(list($k,$v)=each($config[3]))	{
								$opt[]='<option value="'.htmlspecialchars($k).'"'.($params[$fname]==$k?' selected="selected"':'').'>'.htmlspecialchars($v).'</option>';
							}
							$formEl = '<select name="'.$dataPrefix.'['.$fname.']">'.implode('',$opt).'</select>';
						break;
						default:
							debug($config);
						break;
					}
					$lines[$fname].=$formEl;
					$lines[$fname].='<br /><br />';
				} else {
					$lines[$fname]='<hr />';
					if ($config)	$lines[$fname].='<strong>'.strtoupper(htmlspecialchars($config)).'</strong><br />';
					if ($config)	$lines[$fname].='<br />';
				}
			}
		}
		$out = implode('',$lines);
		$out.='<input type="submit" name="submit" value="Update configuration" />';
		return $out;
	}













	/*******************************************
	 *
	 * Backend Modules API functions
	 *
	 *******************************************/


	/**
	 * Returns help-text icon if configured for.
	 * TCA_DESCR must be loaded prior to this function and $BE_USER must have 'edit_showFieldHelp' set to 'icon', otherwise nothing is returned
	 *
	 * Usage: 6
	 *
	 * @param	string		Table name
	 * @param	string		Field name
	 * @param	string		Back path
	 * @param	boolean		Force display of icon nomatter BE_USER setting for help
	 * @return	string		HTML content for a help icon/text
	 */
	function helpTextIcon($table,$field,$BACK_PATH,$force=0)	{
		global $TCA_DESCR,$BE_USER;
		if (is_array($TCA_DESCR[$table]) && is_array($TCA_DESCR[$table]['columns'][$field]) && ($BE_USER->uc['edit_showFieldHelp']=='icon' || $force))	{
			$onClick = 'vHWin=window.open(\''.$BACK_PATH.'view_help.php?tfID='.($table.'.'.$field).'\',\'viewFieldHelp\',\'height=300,width=250,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
			return '<a href="#" onclick="'.htmlspecialchars($onClick).'">'.
					'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/helpbubble.gif','width="14" height="14"').' hspace="2" border="0" class="absmiddle"'.($GLOBALS['CLIENT']['FORMSTYLE']?' style="cursor:help;"':'').' alt="" />'.
					'</a>';
		}
	}

	/**
	 * Returns CSH help text (description), if configured for.
	 * TCA_DESCR must be loaded prior to this function and $BE_USER must have "edit_showFieldHelp" set to "text", otherwise nothing is returned
	 * Will automatically call t3lib_BEfunc::helpTextIcon() to get the icon for the text.
	 *
	 * Usage: 10
	 *
	 * @param	string		Table name
	 * @param	string		Field name
	 * @param	string		Back path
	 * @return	string		HTML content for help text
	 */
	function helpText($table,$field,$BACK_PATH)	{
		global $TCA_DESCR,$BE_USER;
		if (is_array($TCA_DESCR[$table]) && is_array($TCA_DESCR[$table]['columns'][$field]) && $BE_USER->uc['edit_showFieldHelp']=='text')	{
			$fDat = $TCA_DESCR[$table]['columns'][$field];
			return '<table border="0" cellpadding="2" cellspacing="0" width="90%"><tr><td valign="top" width="14">'.t3lib_BEfunc::helpTextIcon($table,$field,$BACK_PATH,
				$fDat['details']||$fDat['syntax']||$fDat['image_descr']||$fDat['image']||$fDat['seeAlso']
				).'</td><td valign="top">'.$fDat['description'].'</td></tr></table>';
		}
	}

	/**
	 * Returns a JavaScript string (for an onClick handler) which will load the alt_doc.php script that shows the form for editing of the record(s) you have send as params.
	 * REMEMBER to always htmlspecialchar() content in href-properties to ampersands get converted to entities (XHTML requirement and XSS precaution)
	 *
	 * Usage: 35
	 *
	 * @param	string		$params is parameters sent along to alt_doc.php. This requires a much more details description which you must seek in Inside TYPO3s documentation of the alt_doc.php API. And example could be '&edit[pages][123]=edit' which will show edit form for page record 123.
	 * @param	string		$backPath must point back to the TYPO3_mainDir directory (where alt_doc.php is)
	 * @param	string		$requestUri is an optional returnUrl you can set - automatically set to REQUEST_URI.
	 * @return	string
	 */
	function editOnClick($params,$backPath='',$requestUri='')	{
		$retUrl = 'returnUrl='.($requestUri==-1?"'+T3_THIS_LOCATION+'":rawurlencode($requestUri?$requestUri:t3lib_div::getIndpEnv('REQUEST_URI')));
		return "document.location='".$backPath."alt_doc.php?".$retUrl.$params."'; return false;";
	}

	/**
	 * Returns a JavaScript string for viewing the page id, $id
	 *
	 * Usage: 8
	 *
	 * @param	integer		$id is page id
	 * @param	string		$backpath must point back to TYPO3_mainDir (where the site is assumed to be one level above)
	 * @param	array		If root line is supplied the function will look for the first found domain record and use that URL instead (if found)
	 * @param	string		$anchor is optional anchor to the URL
	 * @param	string		$altUrl is an alternative URL which - if set - will make all other parameters ignored: The function will just return the window.open command wrapped around this URL!
	 * @return	string
	 */
	function viewOnClick($id,$backPath='',$rootLine='',$anchor='',$altUrl='')	{
		if ($altUrl)	{
			$url = $altUrl;
		} else {
			if ($rootLine)	{
				$parts = parse_url(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
				if (t3lib_BEfunc::getDomainStartPage($parts['host'],$parts['path']))	{
					$preUrl_temp = t3lib_BEfunc::firstDomainRecord($rootLine);
				}
			}
			$preUrl = $preUrl_temp ? 'http://'.$preUrl_temp : $backPath.'..';
			$url = $preUrl.'/index.php?id='.$id.$anchor;
		}

		return "previewWin=window.open('".$url."','newTypo3FrontendWindow','status=1,menubar=1,resizable=1,location=1,scrollbars=1,toolbar=1');previewWin.focus();";
	}

	/**
	 * Returns the merged User/Page TSconfig for page id, $id.
	 * Please read details about module programming elsewhere!
	 *
	 * Usage: 15
	 *
	 * @param	integer		Page uid
	 * @param	string		$TSref is an object string which determines the path of the TSconfig to return.
	 * @return	array
	 */
	function getModTSconfig($id,$TSref)	{
		$pageTS_modOptions = $GLOBALS['BE_USER']->getTSConfig($TSref,t3lib_BEfunc::getPagesTSconfig($id));
		$BE_USER_modOptions = $GLOBALS['BE_USER']->getTSConfig($TSref);
		$modTSconfig = t3lib_div::array_merge_recursive_overrule($pageTS_modOptions,$BE_USER_modOptions);
		return $modTSconfig;
	}

	/**
	 * Returns a selector box "function menu" for a module
	 * Requires the JS function jumpToUrl() to be available
	 * See Inside TYPO3 for details about how to use / make Function menus
	 *
	 * Usage: 50
	 *
	 * @param	string		$id is the "&id=" parameter value to be sent to the module
	 * @param	string		$elementName it the form elements name, probably something like "SET[...]"
	 * @param	string		$currentValue is the value to be selected currently.
	 * @param	array		$menuItems is an array with the menu items for the selector box
	 * @param	string		$script is the script to send the &id to, if empty it's automatically found
	 * @param	string		$addParams is additional parameters to pass to the script.
	 * @return	string		HTML code for selector box
	 */
	function getFuncMenu($id,$elementName,$currentValue,$menuItems,$script='',$addparams='')	{
		if (is_array($menuItems))	{
			if (!$script) { $script=basename(PATH_thisScript); }
			$options='';
			reset($menuItems);
			while(list($value,$label)=each($menuItems))	{
				$options.='<option value="'.htmlspecialchars($value).'"'.(!strcmp($currentValue,$value)?' selected="selected"':'').'>'.t3lib_div::deHSCentities(htmlspecialchars($label)).'</option>';
			}
			if ($options)	{
				$onChange= 'jumpToUrl(\''.$script.'?id='.rawurlencode($id).$addparams.'&'.$elementName.'=\'+this.options[this.selectedIndex].value,this);';
				return '<select name="'.$elementName.'" onchange="'.htmlspecialchars($onChange).'">'.$options.'</select>';
			}
		}
	}

	/**
	 * Checkbox function menu.
	 * Works like ->getFuncMenu() but takes no $menuItem array since this is a simple checkbox.
	 *
	 * Usage: 34
	 *
	 * @param	string		$id is the "&id=" parameter value to be sent to the module
	 * @param	string		$elementName it the form elements name, probably something like "SET[...]"
	 * @param	string		$currentValue is the value to be selected currently.
	 * @param	string		$script is the script to send the &id to, if empty it's automatically found
	 * @param	string		$addParams is additional parameters to pass to the script.
	 * @param	string		Additional attributes for the checkbox input tag
	 * @return	string		HTML code for checkbox
	 * @see getFuncMenu()
	 */
	function getFuncCheck($id,$elementName,$currentValue,$script='',$addparams='',$tagParams='')	{
		if (!$script) {basename(PATH_thisScript);}
		$onClick = 'jumpToUrl(\''.$script.'?id='.$id.$addparams.'&'.$elementName.'=\'+(this.checked?1:0),this);';
		return '<input type="checkbox" name="'.$elementName.'"'.($currentValue?' checked="checked"':'').' onclick="'.htmlspecialchars($onClick).'"'.($tagParams?' '.$tagParams:'').' />';
	}

	/**
	 * Input field function menu
	 * Works like ->getFuncMenu() / ->getFuncCheck() but displays a input field instead which updates the script "onchange"
	 *
	 * Usage: 1
	 *
	 * @param	string		$id is the "&id=" parameter value to be sent to the module
	 * @param	string		$elementName it the form elements name, probably something like "SET[...]"
	 * @param	string		$currentValue is the value to be selected currently.
	 * @param	integer		Relative size of input field, max is 48
	 * @param	string		$script is the script to send the &id to, if empty it's automatically found
	 * @param	string		$addParams is additional parameters to pass to the script.
	 * @return	string		HTML code for input text field.
	 * @see getFuncMenu()
	 */
	function getFuncInput($id,$elementName,$currentValue,$size=10,$script="",$addparams="")	{
		if (!$script) {basename(PATH_thisScript);}
		$onChange = 'jumpToUrl(\''.$script.'?id='.$id.$addparams.'&'.$elementName.'=\'+escape(this.value),this);';
		return '<input type="text"'.$GLOBALS['TBE_TEMPLATE']->formWidth($size).' name="'.$elementName.'" value="'.htmlspecialchars($currentValue).'" onchange="'.htmlspecialchars($onChange).'" />';
	}

	/**
	 * Removes menu items from $itemArray if they are configured to be removed by TSconfig for the module ($modTSconfig)
	 * See Inside TYPO3 about how to program modules and use this API.
	 *
	 * Usage: 4
	 *
	 * @param	array		Module TS config array
	 * @param	array		Array of items from which to remove items.
	 * @param	string		$TSref points to the "object string" in $modTSconfig
	 * @return	array		The modified $itemArray is returned.
	 */
	function unsetMenuItems($modTSconfig,$itemArray,$TSref)	{
			// Getting TS-config options for this module for the Backend User:
		$conf = $GLOBALS['BE_USER']->getTSConfig($TSref,$modTSconfig);
		if (is_array($conf['properties']))	{
			reset($conf['properties']);
			while(list($key,$val)=each($conf['properties']))	{
				if (!$val)	{
					unset($itemArray[$key]);
				}
			}
		}
		return $itemArray;
	}

	/**
	 * Call to update the page tree frame (or something else..?) after
	 * t3lib_BEfunc::getSetUpdateSignal('updatePageTree') -> will set the page tree to be updated.
	 * t3lib_BEfunc::getSetUpdateSignal() -> will return some JavaScript that does the update (called in the typo3/template.php file, end() function)
	 *
	 * Usage: 11
	 *
	 * @param	string		Whether to set or clear the update signal. When setting, this value contains strings telling WHAT to set. At this point it seems that the value "updatePageTree" is the only one it makes sense to set.
	 * @return	string		HTML code (<script> section)
	 */
	function getSetUpdateSignal($set='')	{
		global $BE_USER;
		$key = 't3lib_BEfunc::getSetUpdateSignal';
		$out='';
		if ($set)	{
			$modData=array();
			$modData['set']=$set;
			$BE_USER->pushModuleData($key,$modData);
		} else {
			$modData = $BE_USER->getModuleData($key,'ses');
			if (trim($modData['set']))	{
				$l=explode(',',$modData['set']);
				while(list(,$v)=each($l))	{
					switch($v)	{
						case 'updatePageTree':
						case 'updateFolderTree':
							$out.='
					<script type="text/javascript">
					/*<![CDATA[*/
							if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)	{
								top.content.nav_frame.refresh_nav();
							}
					/*]]>*/
					</script>';
						break;
					}
				}
				$modData=array();
				$modData['set']='';
				$BE_USER->pushModuleData($key,$modData);
			}
		}
		return $out;
	}


	/**
	 * Returns an array which is most backend modules becomes MOD_SETTINGS containing values from function menus etc. determining the function of the module.
	 * This is kind of session variable management framework for the backend users.
	 * If a key from MOD_MENU is set in the CHANGED_SETTINGS array (eg. a value is passed to the script from the outside), this value is put into the settings-array
	 * Ultimately, see Inside TYPO3 for how to use this function in relation to your modules.
	 * Usage: 23
	 *
	 * @param	array		MOD_MENU is an array that defines the options in menus.
	 * @param	array		CHANGED_SETTINGS represents the array used when passing values to the script from the menus.
	 * @param	string		modName is the name of this module. Used to get the correct module data.
	 * @param	string		If type is 'ses' then the data is stored as session-lasting data. This means that it'll be wiped out the next time the user logs in.
	 * @param	string		dontValidateList can be used to list variables that should not be checked if their value is found in the MOD_MENU array. Used for dynamically generated menus.
	 * @param	string		List of default values from $MOD_MENU to set in the output array (only if the values from MOD_MENU are not arrays)
	 * @return	array		The array $settings, which holds a key for each MOD_MENU key and the values of each key will be within the range of values for each menuitem
	 */
	function getModuleData($MOD_MENU, $CHANGED_SETTINGS, $modName, $type='', $dontValidateList='', $setDefaultList='')	{

		if ($modName && is_string($modName))	{
					// GETTING stored user-data from this module:
			$settings = $GLOBALS['BE_USER']->getModuleData($modName,$type);

			$changed=0;
			if (!is_array($settings))	{
				$changed=1;
				$settings=array();
			}
			if (is_array($MOD_MENU))	{
				reset($MOD_MENU);
				while(list($key,$var)=each($MOD_MENU))	{
						// If a global var is set before entering here. eg if submitted, then it's substituting the current value the array.
					if (is_array($CHANGED_SETTINGS) && isset($CHANGED_SETTINGS[$key]) && strcmp($settings[$key],$CHANGED_SETTINGS[$key]))	{
						$settings[$key] = (string)$CHANGED_SETTINGS[$key];
						$changed=1;
					}
						// If the $var is an array, which denotes the existence of a menu, we check if the value is permitted
					if (is_array($var) && (!$dontValidateList || !t3lib_div::inList($dontValidateList,$key)))	{
							// If the setting is an array or not present in the menu-array, MOD_MENU, then the default value is inserted.
						if (is_array($settings[$key]) || !isset($MOD_MENU[$key][$settings[$key]]))	{
							$settings[$key]=(string)key($var);
							$changed=1;
						}
					}
					if ($setDefaultList && !is_array($var))	{	// Sets default values (only strings/checkboxes, not menus)
						if (t3lib_div::inList($setDefaultList,$key) && !isset($settings[$key]))	{
							$settings[$key]=$var;
						}
					}
				}
			} else {die ('No menu!');}

			if ($changed)	{
				$GLOBALS['BE_USER']->pushModuleData($modName,$settings);
			}

			return  $settings;
		} else {die ('Wrong module name: "'.$modName.'"');}
	}













	/*******************************************
	 *
	 * Core
	 *
	 *******************************************/



	/**
	 * Unlock or Lock a record from $table with $uid
	 * If $table and $uid is not set, then all locking for the current BE_USER is removed!
	 *
	 * Usage: 5
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @param	integer		Record pid
	 * @return	void
	 * @internal
	 * @see t3lib_transferData::lockRecord(), alt_doc.php, db_layout.php, db_list.php, wizard_rte.php
	 */
	function lockRecords($table='',$uid=0,$pid=0)	{
		$user_id = intval($GLOBALS['BE_USER']->user['uid']);
		if ($table && $uid)	{
			$fields_values = array(
				'userid' => $user_id,
				'tstamp' => $GLOBALS['EXEC_TIME'],
				'record_table' => $table,
				'record_uid' => $uid,
				'username' => $GLOBALS['BE_USER']->user['username'],
				'record_pid' => $pid
			);

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_lockedrecords', $fields_values);
		} else {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_lockedrecords', 'userid='.intval($user_id));
		}
	}

	/**
	 * Returns information about whether the record from table, $table, with uid, $uid is currently locked (edited by another user - which should issue a warning).
	 * Notice: Locking is not strictly carried out since locking is abandoned when other backend scripts are activated - which means that a user CAN have a record "open" without having it locked. So this just serves as a warning that counts well in 90% of the cases, which should be sufficient.
	 *
	 * Usage: 5
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @return	array
	 * @internal
	 * @see class.db_layout.inc, alt_db_navframe.php, alt_doc.php, db_layout.php
	 */
	function isRecordLocked($table,$uid)	{
		global $LOCKED_RECORDS;
		if (!is_array($LOCKED_RECORDS))	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'*',
							'sys_lockedrecords',
							'sys_lockedrecords.userid!='.intval($GLOBALS['BE_USER']->user['uid']).'
								AND sys_lockedrecords.tstamp > '.($GLOBALS['EXEC_TIME']-2*3600)
						);
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$LOCKED_RECORDS[$row['record_table'].':'.$row['record_uid']]=$row;
				$LOCKED_RECORDS[$row['record_table'].':'.$row['record_uid']]['msg']=sprintf(
					$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.lockedRecord'),
					$row['username'],
					t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME']-$row['tstamp'],$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears'))
				);
				if ($row['record_pid'] && !isset($LOCKED_RECORDS[$row['record_table'].':'.$row['record_pid']]))	{
					$LOCKED_RECORDS['pages:'.$row['record_pid']]['msg']=sprintf(
						$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.lockedRecord_content'),
						$row['username'],
						t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME']-$row['tstamp'],$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears'))
					);
				}
			}
		}
		return $LOCKED_RECORDS[$table.':'.$uid];
	}

	/**
	 * Returns select statement for MM relations (as used by TCEFORMs etc)
	 *
	 * Usage: 3
	 *
	 * @param	array		Configuration array for the field, taken from $TCA
	 * @param	string		Field name
	 * @param	array		TSconfig array from which to get further configuration settings for the field name
	 * @param	string		Prefix string for the key "*foreign_table_where" from $fieldValue array
	 * @return	string		Part of query
	 * @internal
	 * @see t3lib_transferData::renderRecord(), t3lib_TCEforms::foreignTable()
	 */
	function exec_foreign_table_where_query($fieldValue,$field='',$TSconfig=array(),$prefix='')	{
		global $TCA;
		$foreign_table = $fieldValue['config'][$prefix.'foreign_table'];
		$rootLevel = $TCA[$foreign_table]['ctrl']['rootLevel'];

		$fTWHERE = $fieldValue['config'][$prefix.'foreign_table_where'];
		if (strstr($fTWHERE,'###REC_FIELD_'))	{
			$fTWHERE_parts = explode('###REC_FIELD_',$fTWHERE);
			while(list($kk,$vv)=each($fTWHERE_parts))	{
				if ($kk)	{
					$fTWHERE_subpart = explode('###',$vv,2);
					$fTWHERE_parts[$kk]=$TSconfig['_THIS_ROW'][$fTWHERE_subpart[0]].$fTWHERE_subpart[1];
				}
			}
			$fTWHERE = implode('',$fTWHERE_parts);
		}

		$fTWHERE = str_replace('###CURRENT_PID###',intval($TSconfig['_CURRENT_PID']),$fTWHERE);
		$fTWHERE = str_replace('###THIS_UID###',intval($TSconfig['_THIS_UID']),$fTWHERE);
		$fTWHERE = str_replace('###THIS_CID###',intval($TSconfig['_THIS_CID']),$fTWHERE);
		$fTWHERE = str_replace('###STORAGE_PID###',intval($TSconfig['_STORAGE_PID']),$fTWHERE);
		$fTWHERE = str_replace('###SITEROOT###',intval($TSconfig['_SITEROOT']),$fTWHERE);
		$fTWHERE = str_replace('###PAGE_TSCONFIG_ID###',intval($TSconfig[$field]['PAGE_TSCONFIG_ID']),$fTWHERE);
		$fTWHERE = str_replace('###PAGE_TSCONFIG_IDLIST###',$GLOBALS['TYPO3_DB']->cleanIntList($TSconfig[$field]['PAGE_TSCONFIG_IDLIST']),$fTWHERE);
		$fTWHERE = str_replace('###PAGE_TSCONFIG_STR###',$GLOBALS['TYPO3_DB']->quoteStr($TSconfig[$field]['PAGE_TSCONFIG_STR'], $foreign_table),$fTWHERE);

			// rootLevel = -1 is not handled 'properly' here - it goes as if it was rootLevel = 1 (that is pid=0)
		$wgolParts = $GLOBALS['TYPO3_DB']->splitGroupOrderLimit($fTWHERE);
		if ($rootLevel)	{
			$queryParts = array(
				'SELECT' => t3lib_BEfunc::getCommonSelectFields($foreign_table,$foreign_table.'.'),
				'FROM' => $foreign_table,
				'WHERE' => $foreign_table.'.pid=0 '.
							t3lib_BEfunc::deleteClause($foreign_table).' '.
							$wgolParts['WHERE'],
				'GROUPBY' => $wgolParts['GROUPBY'],
				'ORDERBY' => $wgolParts['ORDERBY'],
				'LIMIT' => $wgolParts['LIMIT']
			);
		} else {
			$pageClause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			if ($foreign_table!='pages')	{
				$queryParts = array(
					'SELECT' => t3lib_BEfunc::getCommonSelectFields($foreign_table,$foreign_table.'.'),
					'FROM' => $foreign_table.',pages',
					'WHERE' => 'pages.uid='.$foreign_table.'.pid
								AND NOT pages.deleted '.
								t3lib_BEfunc::deleteClause($foreign_table).
								' AND '.$pageClause.' '.
								$wgolParts['WHERE'],
					'GROUPBY' => $wgolParts['GROUPBY'],
					'ORDERBY' => $wgolParts['ORDERBY'],
					'LIMIT' => $wgolParts['LIMIT']
				);
			} else {
				$queryParts = array(
					'SELECT' => t3lib_BEfunc::getCommonSelectFields($foreign_table,$foreign_table.'.'),
					'FROM' => 'pages',
					'WHERE' => 'NOT pages.deleted
								AND '.$pageClause.' '.
								$wgolParts['WHERE'],
					'GROUPBY' => $wgolParts['GROUPBY'],
					'ORDERBY' => $wgolParts['ORDERBY'],
					'LIMIT' => $wgolParts['LIMIT']
				);
			}
		}

		return $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
	}

	/**
	 * Returns TSConfig for the TCEFORM object in Page TSconfig.
	 * Used in TCEFORMs
	 *
	 * Usage: 4
	 *
	 * @param	string		Table name present in TCA
	 * @param	array		Row from table
	 * @return	array
	 * @see t3lib_transferData::renderRecord(), t3lib_TCEforms::setTSconfig(), SC_wizard_list::main(), SC_wizard_add::main()
	 */
	function getTCEFORM_TSconfig($table,$row) {
		$res = array();
		$typeVal = t3lib_BEfunc::getTCAtypeValue($table,$row);

			// Get main config for the table
		list($TScID,$cPid) = t3lib_BEfunc::getTSCpid($table,$row['uid'],$row['pid']);

		$rootLine = t3lib_BEfunc::BEgetRootLine($TScID,'');
		if ($TScID>=0)	{
			$tempConf = $GLOBALS['BE_USER']->getTSConfig('TCEFORM.'.$table,t3lib_BEfunc::getPagesTSconfig($TScID,$rootLine));
			if (is_array($tempConf['properties']))	{
				while(list($key,$val)=each($tempConf['properties']))	{
					if (is_array($val))	{
						$fieldN = substr($key,0,-1);
						$res[$fieldN] = $val;
						unset($res[$fieldN]['types.']);
						if (strcmp($typeVal,'') && is_array($val['types.'][$typeVal.'.']))	{
							$res[$fieldN] = t3lib_div::array_merge_recursive_overrule($res[$fieldN],$val['types.'][$typeVal.'.']);
						}
					}
				}
			}
		}
		$res['_CURRENT_PID']=$cPid;
		$res['_THIS_UID']=$row['uid'];
		$res['_THIS_CID']=$row['cid'];
		$res['_THIS_ROW']=$row;	// So the row will be passed to foreign_table_where_query()
		reset($rootLine);
		while(list(,$rC)=each($rootLine))	{
			if (!$res['_STORAGE_PID'])	$res['_STORAGE_PID']=intval($rC['storage_pid']);
			if (!$res['_SITEROOT'])	$res['_SITEROOT']=$rC['is_siteroot']?intval($rC['uid']):0;
		}

		return $res;
	}

	/**
	 * Find the real PID of the record (with $uid from $table). This MAY be impossible if the pid is set as a reference to the former record or a page (if two records are created at one time).
	 *
	 * Usage: 2
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @param	integer		Record pid
	 * @return	integer
	 * @internal
	 * @see t3lib_TCEmain::copyRecord(), getTSCpid()
	 */
	function getTSconfig_pidValue($table,$uid,$pid)	{
		if (t3lib_div::testInt($pid))	{	// If pid is an integer this takes precedence in our lookup.
			$thePidValue = intval($pid);
			if ($thePidValue<0)	{	// If ref to another record, look that record up.
				$pidRec = t3lib_BEfunc::getRecord($table,abs($thePidValue),'pid');
				$thePidValue= is_array($pidRec) ? $pidRec['pid'] : -2;	// Returns -2 if the record did not exist.
			}
			// ... else the pos/zero pid is just returned here.
		} else {	// No integer pid and we are forced to look up the $pid
			$rr = t3lib_BEfunc::getRecord($table,$uid,'pid');	// Try to fetch the record pid from uid. If the uid is 'NEW...' then this will of course return nothing...
			if (is_array($rr))	{
				$thePidValue = $rr['pid'];	// Returning the 'pid' of the record
			} else $thePidValue=-1;	// Returns -1 if the record with the pid was not found.
		}
		return $thePidValue;
	}

	/**
	 * Return $uid if $table is pages and $uid is integer - otherwise the $pid
	 *
	 * Usage: 1
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @param	integer		Record pid
	 * @return	integer
	 * @internal
	 * @see t3lib_TCEforms::getTSCpid()
	 */
	function getPidForModTSconfig($table,$uid,$pid)	{
		$retVal = ($table=='pages' && t3lib_div::testInt($uid)) ? $uid : $pid;
		return $retVal;
	}

	/**
	 * Returns the REAL pid of the record, if possible. If both $uid and $pid is strings, then pid=-1 is returned as an error indication.
	 *
	 * Usage: 8
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @param	integer		Record pid
	 * @return	array		Array of two integers; first is the REAL PID of a record and if its a new record negative values are resolved to the true PID, second value is the PID value for TSconfig (uid if table is pages, otherwise the pid)
	 * @internal
	 * @see t3lib_TCEmain::setHistory(), t3lib_TCEmain::process_datamap()
	 */
	function getTSCpid($table,$uid,$pid)	{
			// If pid is negative (referring to another record) the pid of the other record is fetched and returned.
		$cPid = t3lib_BEfunc::getTSconfig_pidValue($table,$uid,$pid);
			// $TScID is the id of $table=pages, else it's the pid of the record.
		$TScID = t3lib_BEfunc::getPidForModTSconfig($table,$uid,$cPid);

		return array($TScID,$cPid);
	}

	/**
	 * Returns first found domain record "domainName" (without trailing slash) if found in the input $rootLine
	 *
	 * Usage: 2
	 *
	 * @param	array		Root line array
	 * @return	string		Domain name, if found.
	 */
	function firstDomainRecord($rootLine)	{
		if (t3lib_extMgm::isLoaded('cms'))	{
			reset($rootLine);
			while(list(,$row)=each($rootLine))	{
				$dRec = t3lib_BEfunc::getRecordsByField('sys_domain','pid',$row['uid'],' AND redirectTo="" AND hidden=0', '', 'sorting');
				if (is_array($dRec))	{
					reset($dRec);
					$dRecord = current($dRec);
					return ereg_replace('\/$','',$dRecord['domainName']);
				}
			}
		}
	}

	/**
	 * Returns the sys_domain record for $domain, optionally with $path appended.
	 *
	 * Usage: 2
	 *
	 * @param	string		Domain name
	 * @param	string		Appended path
	 * @return	array		Domain record, if found
	 */
	function getDomainStartPage($domain, $path='')	{
		if (t3lib_extMgm::isLoaded('cms'))	{
			$domain = explode(':',$domain);
			$domain = strtolower(ereg_replace('\.$','',$domain[0]));
				// path is calculated.
			$path = trim(ereg_replace('\/[^\/]*$','',$path));
				// stuff:
			$domain.=$path;

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('sys_domain.*', 'pages,sys_domain', '
				pages.uid=sys_domain.pid
				AND NOT sys_domain.hidden
				AND (sys_domain.domainName="'.$GLOBALS['TYPO3_DB']->quoteStr($domain, 'sys_domain').'" or sys_domain.domainName="'.$GLOBALS['TYPO3_DB']->quoteStr($domain.'/', 'sys_domain').'")'.
				t3lib_BEfunc::deleteClause('pages'),
				'', '', '1');
			return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		}
	}

	/**
	 * Returns overlayered RTE setup from an array with TSconfig. Used in TCEforms and TCEmain
	 *
	 * Usage: 8
	 *
	 * @param	array		The properties of Page TSconfig in the key "RTE."
	 * @param	string		Table name
	 * @param	string		Field name
	 * @param	string		Type value of the current record (like from CType of tt_content)
	 * @return	array		Array with the configuration for the RTE
	 * @internal
	 */
	function RTEsetup($RTEprop,$table,$field,$type='')	{
		$thisConfig = is_array($RTEprop['default.']) ? $RTEprop['default.'] : array();
		$thisFieldConf = $RTEprop['config.'][$table.'.'][$field.'.'];
		if (is_array($thisFieldConf))	{
			unset($thisFieldConf['types.']);
			$thisConfig = t3lib_div::array_merge_recursive_overrule($thisConfig,$thisFieldConf);
		}
		if ($type && is_array($RTEprop['config.'][$table.'.'][$field.'.']['types.'][$type.'.']))	{
			$thisConfig = t3lib_div::array_merge_recursive_overrule($thisConfig,$RTEprop['config.'][$table.'.'][$field.'.']['types.'][$type.'.']);
		}
		return $thisConfig;
	}

	/**
	 * Returns first possible RTE object if available.
	 *
	 * @return	mixed		If available, returns RTE object, otherwise an array of messages from possible RTEs
	 */
	function &RTEgetObj()	{

			// If no RTE object has been set previously, try to create it:
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['T3_VAR']['RTEobj']))	{

				// Set the object string to blank by default:
			$GLOBALS['TYPO3_CONF_VARS']['T3_VAR']['RTEobj'] = array();

				// Traverse registered RTEs:
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_reg']))	{
				foreach($GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_reg'] as $extKey => $rteObjCfg)	{
					$rteObj = &t3lib_div::getUserObj($rteObjCfg['objRef']);
					if (is_object($rteObj))	{
						if ($rteObj->isAvailable())	{
							$GLOBALS['TYPO3_CONF_VARS']['T3_VAR']['RTEobj'] = &$rteObj;
							break;
						} else {
							$GLOBALS['TYPO3_CONF_VARS']['T3_VAR']['RTEobj'] = array_merge($GLOBALS['TYPO3_CONF_VARS']['T3_VAR']['RTEobj'], $rteObj->errorLog);
						}
					}
				}
			}

			if (!count($GLOBALS['TYPO3_CONF_VARS']['T3_VAR']['RTEobj']))	{
				$GLOBALS['TYPO3_CONF_VARS']['T3_VAR']['RTEobj'][] = 'No RTEs configured at all';
			}
		}

			// Return RTE object (if any!)
		return $GLOBALS['TYPO3_CONF_VARS']['T3_VAR']['RTEobj'];
	}

	/**
	 * Returns true if $modName is set and is found as a main- or submodule in $TBE_MODULES array
	 *
	 * Usage: 1
	 *
	 * @param	string		Module name
	 * @return	boolean
	 */
	function isModuleSetInTBE_MODULES($modName)	{
		reset($GLOBALS['TBE_MODULES']);
		$loaded=array();
		while(list($mkey,$list)=each($GLOBALS['TBE_MODULES']))	{
			$loaded[$mkey]=1;
			if (trim($list))	{
				$subList = t3lib_div::trimExplode(',',$list,1);
				while(list(,$skey)=each($subList))	{
					$loaded[$mkey.'_'.$skey]=1;
				}
			}
		}
		return $modName && isset($loaded[$modName]);
	}


















	/*******************************************
	 *
	 * Miscellaneous
	 *
	 *******************************************/


	/**
	 * Print error message with header, text etc.
	 *
	 * Usage: 19
	 *
	 * @param	string		Header string
	 * @param	string		Content string
	 * @param	boolean		Will return an alert() with the content of header and text.
	 * @param	boolean		Print header.
	 * @return	void
	 */
	function typo3PrintError ($header,$text,$js='',$head=1)	{
		// This prints out a TYPO3 error message.
		// If $js is set the message will be output in JavaScript
		if ($js)	{
			echo"alert('".t3lib_div::slashJS($header.'\n'.$text)."');";
		} else {
			echo $head?'<html>
				<head>
					<title>Error!</title>
				</head>
				<body bgcolor="white" topmargin="0" leftmargin="0" marginwidth="0" marginheight="0">':'';
			echo '<div align="center">
					<table border="0" cellspacing="0" cellpadding="0" width="333">
						<tr>
							<td align="center">'.
								($GLOBALS['TBE_STYLES']['logo_login']?'<img src="'.$GLOBALS['BACK_PATH'].$GLOBALS['TBE_STYLES']['logo_login'].'" alt="" />':'<img src="'.$GLOBALS['BACK_PATH'].'gfx/typo3logo.gif" width="333" height="43" vspace="10" />').
							'</td>
						</tr>
						<tr>
							<td bgcolor="black">
								<table width="100%" border="0" cellspacing="1" cellpadding="10">
									<tr>
										<td bgcolor="#F4F0E8">
											<font face="verdana,arial,helvetica" size="2">';
			echo '<b><center><font size="+1">'.$header.'</font></center></b><br />'.$text;
			echo '							</font>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>';
			echo $head?'
				</body>
			</html>':'';
		}
	}

	/**
	 * Returns "web" if the $path (absolute) is within the DOCUMENT ROOT - and thereby qualifies as a "web" folder.
	 *
	 * Usage: 4
	 *
	 * @param	string		Path to evaluate
	 * @return	boolean
	 */
	function getPathType_web_nonweb($path)	{
		return t3lib_div::isFirstPartOfStr($path,t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT')) ? 'web' : '';
	}

	/**
	 * Creates ADMCMD parameters for the "viewpage" extension / "cms" frontend
	 *
	 * Usage: 1
	 *
	 * @param	array		Page record
	 * @return	string		Query-parameters
	 * @internal
	 */
	function ADMCMD_previewCmds($pageinfo)	{
		if ($pageinfo['fe_group']>0)	{
			$simUser = '&ADMCMD_simUser='.$pageinfo['fe_group'];
		}
		if ($pageinfo['starttime']>time())	{
			$simTime = '&ADMCMD_simTime='.$pageinfo['starttime'];
		}
		if ($pageinfo['endtime']<time() && $pageinfo['endtime']!=0)	{
			$simTime = '&ADMCMD_simTime='.($pageinfo['endtime']-1);
		}
		return $simUser.$simTime;
	}

	/**
	 * Returns an array with key=>values based on input text $params
	 * $params is exploded by line-breaks and each line is supposed to be on the syntax [key] = [some value]
	 * These pairs will be parsed into an array an returned.
	 *
	 * Usage: 1
	 *
	 * @param	string		String of parameters on multiple lines to parse into key-value pairs (see function description)
	 * @return	array
	 */
	function processParams($params)	{
		$paramArr=array();
		$lines=explode(chr(10),$params);
		while(list(,$val)=each($lines))	{
			$val = trim($val);
			if ($val)	{
				$pair = explode('=',$val,2);
				$paramArr[trim($pair[0])] = trim($pair[1]);
			}
		}
		return $paramArr;
	}

	/**
	 * Returns "list of backend modules". Most likely this will be obsolete soon / removed. Don't use.
	 *
	 * Usage: 3
	 *
	 * @param	array		Module names in array. Must be "addslashes()"ed
	 * @param	string		Perms clause for SQL query
	 * @param	string		Backpath
	 * @param	string		The URL/script to jump to (used in A tag)
	 * @return	array		Two keys, rows and list
	 * @internal
	 * @depreciated
	 * @obsolete
	 */
	function getListOfBackendModules($name,$perms_clause,$backPath='',$script='index.php')	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'doktype!=255 AND module IN ("'.implode('","',$name).'") AND'.$perms_clause.t3lib_BEfunc::deleteClause('pages'));
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	return false;

		$out='';
		$theRows=array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$theRows[]=$row;
			$out.='<span class="nobr"><a href="'.htmlspecialchars($script.'?id='.$row['uid']).'">'.
					t3lib_iconWorks::getIconImage('pages',$row,$backPath,'title="'.htmlspecialchars(t3lib_BEfunc::getRecordPath($row['uid'],$perms_clause,20)).'" align="top"').
					htmlspecialchars($row['title']).
					'</a></span><br />';
		}
		return array('rows'=>$theRows,'list'=>$out);
	}
}
?>
