<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
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
 * Contains a class for evaluation of database integrity according to $TCA
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   89: class t3lib_admin 
 *  115:     function genTree($theID, $depthData)	
 *  149:     function lostRecords($pid_list)	
 *  178:     function fixLostRecord($table,$uid)	
 *  196:     function countRecords($pid_list)	
 *  226:     function getGroupFields($mode)	
 *  260:     function getFileFields($uploadfolder)	
 *  283:     function getDBFields($theSearchTable)	
 *  311:     function selectNonEmptyRecordsWithFkeys($fkey_arrays)	
 *  386:     function testFileRefs ()	
 *  437:     function testDBRefs($theArray)	
 *  476:     function whereIsRecordReferenced($searchTable,$id)	
 *  510:     function whereIsFileReferenced($uploadfolder,$filename)	
 *
 * TOTAL FUNCTIONS: 12
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */




















/**
 * This class holds functions used by the TYPO3 backend to check the integrity of the database (The DBint module, 'lowlevel' extension)
 * 
 * Depends on:		Depends on loaddbgroup from t3lib/
 * 
 * @todo	Need to really extend this class when the tcemain library has been updated and the whole API is better defined. There are some known bugs in this library. Further it would be nice with a facility to not only analyze but also clean up!
 * @see SC_mod_tools_dbint_index::func_relations(), SC_mod_tools_dbint_index::func_records()
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */
class t3lib_admin {
	var $genTree_includeDeleted = 1;	// if set, genTree() includes deleted pages. This is default.
	var $perms_clause='';		// extra where-clauses for the tree-selection
	var $genTree_makeHTML = 0;			// if set, genTree() generates HTML, that visualizes the tree.
		// internal
	var $genTree_idlist = '';	// Will hold the id-list from genTree()
	var $getTree_HTML = '';		// Will hold the HTML-code visualising the tree. genTree()
	var $backPath='';	

		// internal
	var $checkFileRefs = Array();
	var $checkSelectDBRefs = Array();	// From the select-fields
	var $checkGroupDBRefs = Array();	// From the group-fields

	var $page_idArray=Array();
	var $recStat = Array();
	var $lRecords = Array();
	var $lostPagesList = '';

	/**
	 * Generates a list of Page-uid's that corresponds to the tables in the tree. This list should ideally include all records in the pages-table.
	 * 
	 * @param	integer		a pid (page-record id) from which to start making the tree
	 * @param	string		HTML-code (image-tags) used when this function calls itself recursively.
	 * @return	integer		Number of mysql_num_rows (most recent query)
	 */
	function genTree($theID, $depthData)	{
		$res = mysql(TYPO3_db, 'SELECT uid,title,doktype,deleted'.(t3lib_extMgm::isLoaded('cms')?',hidden':'').' FROM pages WHERE pid="'.$theID.'" '.((!$this->genTree_includeDeleted)?'AND NOT deleted':'').$this->perms_clause.' ORDER BY sorting');
		$a=0;
		$c=mysql_num_rows($res);
		while ($row = mysql_fetch_assoc($res))	{
			$a++;
			$newID =$row['uid'];
			if ($this->genTree_makeHTML)	{	
				$this->genTree_HTML.=chr(10).'<div><nobr>';
				$PM = 'join';
				$LN = ($a==$c)?'blank':'line';
				$BTM = ($a==$c)?'bottom':'';
				$this->genTree_HTML.= $depthData.'<img src="'.$this->backPath.'t3lib/gfx/ol/'.$PM.$BTM.'.gif" width="18" height="16" align="top" alt="" /><img src="'.$this->backPath.t3lib_iconWorks::getIcon('pages',$row).'" width="18" height="16" align="top" alt="" />'.htmlspecialchars($row['uid'].': '.t3lib_div::fixed_lgd(strip_tags($row['title']),50)).'</nobr></div>';
			}

			if (isset($page_idlist[$newID]))	{
				$this->recStat['doublePageID'][]=$newID;
			}
			$this->page_idArray[$newID]=$newID;
			if ($row['deleted']) {$this->recStat['deleted']++;}
			if ($row['hidden']) {$this->recStat['hidden']++;}
			$this->recStat['doktype'][$row['doktype']]++;
			
			$this->genTree($newID,$this->genTree_HTML ? $depthData.'<img src="'.$this->backPath.'t3lib/gfx/ol/'.$LN.'.gif" width="18" height="16" align="top" alt="" />'  : '');
		}
		return (mysql_num_rows($res));
	}
	
	/**
	 * Fills $this->lRecords with the records from all tc-tables that are not attached to a PID in the pid-list.
	 * 
	 * @param	string		list of pid's (page-record uid's). This list is probably made by genTree()
	 * @return	void		
	 */
	function lostRecords($pid_list)	{
		global $TCA;
		reset($TCA);
		$this->lostPagesList='';
		if ($pid_list)	{
			while (list($table)=each($TCA))	{
				t3lib_div::loadTCA($table);
			 	$query = 'SELECT uid,pid,'.$TCA[$table]['ctrl']['label'].' FROM '.$table.' WHERE pid NOT IN ('.$pid_list.')';
				$garbage = mysql(TYPO3_db,$query);
				echo mysql_error();
				$lostIdList=Array();
				while ($row = mysql_fetch_assoc($garbage))	{
					$this->lRecords[$table][$row['uid']]=Array('uid'=>$row['uid'], 'pid'=>$row['pid'], 'title'=> strip_tags($row[$TCA[$table]['ctrl']['label']]) );
					$lostIdList[]=$row['uid'];
				}
				if ($table=='pages')	{
					$this->lostPagesList=implode($lostIdList,',');
				}
			}
		}
	}

	/**
	 * Fixes lost record from $table with uid $uid by setting the PID to zero. If there is a disabled column for the record that will be set as well.
	 * 
	 * @param	string		Tablename
	 * @param	integer		The uid of the record which will have the PID value set to 0 (zero)
	 * @return	boolean		True if done.
	 */
	function fixLostRecord($table,$uid)	{
		if ($table && $GLOBALS['TCA'][$table] && $uid && is_array($this->lRecords[$table][$uid]) && $GLOBALS['BE_USER']->user['admin'])	{
			$extra='';
			if ($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'])	{	// If possible a lost record restored is hidden as default
				$extra.=','.$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'].'=1';
			}
			$fixQuery = 'UPDATE '.$table.' SET pid=0'.$extra.' WHERE uid="'.$uid.'"';
			$fixQ = mysql(TYPO3_db,$fixQuery);
			return true;
		} else return false;
	}

	/**
	 * Counts records from $TCA-tables that ARE attached to an existing page.
	 * 
	 * @param	string		list of pid's (page-record uid's). This list is probably made by genTree()
	 * @return	array		an array with the number of records from all $TCA-tables that are attached to a PID in the pid-list.
	 */
	function countRecords($pid_list)	{
		global $TCA;
		reset($TCA);
		$list=Array();
		$list_n=Array();
		if ($pid_list)	{
			while (list($table)=each($TCA))	{
				t3lib_div::loadTCA($table);
			 	$query = 'SELECT count(*) FROM '.$table.' WHERE pid IN ('.$pid_list.')';
				$count = mysql(TYPO3_db,$query);
				if ($row = mysql_fetch_row($count))	{
					$list[$table]=$row[0];
				}

			 	$query = 'SELECT count(*) FROM '.$table.' WHERE pid IN ('.$pid_list.')'.t3lib_BEfunc::deleteClause($table);
				$count = mysql(TYPO3_db,$query);
				if ($row = mysql_fetch_row($count))	{
					$list_n[$table]=$row[0];
				}
			}
		}
		return array('all' => $list, 'non_deleted' => $list_n);
	}

	/**
	 * Finding relations in database based on type 'group' (files or database-uid's in a list)
	 * 
	 * @param	string		$mode = file, $mode = db, $mode = '' (all...)
	 * @return	array		An array with all fields listed that somehow are references to other records (foreign-keys) or files
	 */
	function getGroupFields($mode)	{
		global $TCA;
		reset ($TCA);
		$result = Array();
		while (list($table)=each($TCA))	{
			t3lib_div::loadTCA($table);
			$cols = $TCA[$table]['columns'];
			reset ($cols);
			while (list($field,$config)=each($cols))	{
				if ($config['config']['type']=='group')	{
					if (
						((!$mode||$mode=='file') && $config['config']['internal_type']=='file') || 
						((!$mode||$mode=='db') && $config['config']['internal_type']=='db')
						)	{
						$result[$table][]=$field;
					}
				}
				if ( (!$mode||$mode=='db') && $config['config']['type']=='select' && $config['config']['foreign_table'])	{
					$result[$table][]=$field;
				}
			}
			if ($result[$table])	{
				$result[$table] = implode(',',$result[$table]);
			}
		}
		return $result;
	}

	/**
	 * Finds all fields that hold filenames from uploadfolder
	 * 
	 * @param	string		Path to uploadfolder
	 * @return	array		An array with all fields listed that have references to files in the $uploadfolder
	 */
	function getFileFields($uploadfolder)	{
		global $TCA;
		reset ($TCA);
		$result = Array();
		while (list($table)=each($TCA))	{
			t3lib_div::loadTCA($table);
			$cols = $TCA[$table]['columns'];
			reset ($cols);
			while (list($field,$config)=each($cols))	{
				if ($config['config']['type']=='group' && $config['config']['internal_type']=='file' && $config['config']['uploadfolder']==$uploadfolder)	{
					$result[]=Array($table,$field);
				}
			}
		}
		return $result;
	}

	/**
	 * Returns an array with arrays of table/field pairs which are allowed to hold references to the input table name - according to $TCA
	 * 
	 * @param	string		Table name
	 * @return	array		
	 */
	function getDBFields($theSearchTable)	{
		global $TCA;
		$result = Array();
		reset ($TCA);
		while (list($table)=each($TCA))	{
			t3lib_div::loadTCA($table);
			$cols = $TCA[$table]['columns'];
			reset ($cols);
			while (list($field,$config)=each($cols))	{
				if ($config['config']['type']=='group' && $config['config']['internal_type']=='db')	{
					if (trim($config['config']['allowed'])=='*' || strstr($config['config']['allowed'],$theSearchTable))	{
						$result[]=Array($table,$field);
					}
				} else if ($config['config']['type']=='select' && $config['config']['foreign_table']==$theSearchTable)	{
					$result[]=Array($table,$field);
				}
			}
		}
		return $result;
	}

	/**
	 * This selects non-empty-records from the tables/fields in the fkey_array generated by getGroupFields()
	 * 
	 * @param	array		
	 * @return	void		
	 * @see getGroupFields()
	 */
	function selectNonEmptyRecordsWithFkeys($fkey_arrays)	{
		global $TCA;
		if (is_array($fkey_arrays))	{
			reset($fkey_arrays);
			while (list($table,$field_list)=each($fkey_arrays))	{
				if ($TCA[$table] && trim($field_list))	{
					t3lib_div::loadTCA($table);
					$fieldArr = explode(',',$field_list);
					$cl_fl = implode ('!="" OR ',$fieldArr). '!=""';
					$query = 'SELECT uid,'.$field_list.' FROM '.$table.' WHERE '.$cl_fl;
					$mres = mysql(TYPO3_db,$query);
					echo mysql_error();
					while ($row=mysql_fetch_assoc($mres))	{
						reset($fieldArr);
						while (list(,$field)=each($fieldArr))	{
							if (trim($row[$field]))		{
								$fieldConf = $TCA[$table]['columns'][$field]['config'];
								if ($fieldConf['type']=='group')	{
									if ($fieldConf['internal_type']=='file')	{
										// files...
										if ($fieldConf['MM'])	{
											$tempArr=array();
											$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
											$dbAnalysis->start('','files',$fieldConf['MM'],$row['uid']);
											reset($dbAnalysis->itemArray);
											while (list($somekey,$someval)=each($dbAnalysis->itemArray))	{
												if ($someval['id'])	{
													$tempArr[]=$someval['id'];
												}
											}
										} else {
											$tempArr = explode(',',trim($row[$field]));
										}
										reset($tempArr);
										while (list(,$file)=each($tempArr))	{
											$file = trim($file);
											if ($file)	{
												$this->checkFileRefs[$fieldConf['uploadfolder']][$file]+=1;
											}
										}
									}
									if ($fieldConf['internal_type']=='db')	{
										// dbs - group
										$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
										$dbAnalysis->start($row[$field],$fieldConf['allowed'],$fieldConf['MM'],$row['uid']);
										reset($dbAnalysis->itemArray);
										while (list(,$tempArr)=each($dbAnalysis->itemArray))	{
											$this->checkGroupDBRefs[$tempArr['table']][$tempArr['id']]+=1;
										}
									}
								}
								if ($fieldConf['type']=='select' && $fieldConf['foreign_table'])	{
									// dbs - select
									$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
									$dbAnalysis->start($row[$field],$fieldConf['foreign_table'],$fieldConf['MM'],$row['uid']);
									reset($dbAnalysis->itemArray);
									while (list(,$tempArr)=each($dbAnalysis->itemArray))	{
										if ($tempArr['id']>0)	{
											$this->checkGroupDBRefs[$fieldConf['foreign_table']][$tempArr['id']]+=1;
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Depends on selectNonEmpty.... to be executed first!!
	 * 
	 * @return	array		Report over files; keys are "moreReferences", "noReferences", "noFile", "error"
	 */
	function testFileRefs ()	{
		$output=Array();
		reset($this->checkFileRefs);
		while(list($folder,$fileArr)=each($this->checkFileRefs))	{
			$path = PATH_site.$folder;
			if (@is_dir($path))	{
				$d = dir($path);
				while($entry=$d->read()) {
					if (@is_file($path.'/'.$entry))	{
						if (isset($fileArr[$entry]))	{
							if ($fileArr[$entry] > 1)	{
								$temp = $this->whereIsFileReferenced($folder,$entry);
								$tempList = '';
								while(list(,$inf)=each($temp))	{
									$tempList.='['.$inf['table'].']['.$inf['uid'].']['.$inf['field'].'] (pid:'.$inf['pid'].') - ';
								}
								$output['moreReferences'][] = Array($path,$entry,$fileArr[$entry],$tempList);
							}
							unset($fileArr[$entry]);
						} else {
							if (!strstr($entry,'index.htm'))	{
								$output['noReferences'][] = Array($path,$entry);
							}
						}
					}
				}
				$d->close();
				reset($fileArr);
				$tempCounter=0;
				while(list($file,)=each($fileArr))	{
					$temp = $this->whereIsFileReferenced($folder,$file);
					$tempList = '';
					while(list(,$inf)=each($temp))	{
						$tempList.='['.$inf['table'].']['.$inf['uid'].']['.$inf['field'].'] (pid:'.$inf['pid'].') - ';
					}
					$tempCounter++;
					$output['noFile'][substr($path,-3).'_'.substr($file,0,3).'_'.$tempCounter] = Array($path,$file,$tempList);
				}
			} else {
				$output['error'][] = Array($path);
			}
		}
		return $output;
	}

	/**
	 * Depends on selectNonEmpty.... to be executed first!!
	 * 
	 * @param	array		Table with key/value pairs being table names and arrays with uid numbers
	 * @return	string		HTML Error message
	 */
	function testDBRefs($theArray)	{
		global $TCA;
		reset($theArray);
		while(list($table,$dbArr)=each($theArray))	{
			if ($TCA[$table])	{
				$idlist = Array();
				while(list($id,)=each($dbArr))	{
					$idlist[]=$id;
				}
				$theList = implode($idlist,',');
				if ($theList)	{
					$query='SELECT uid FROM '.$table.' WHERE uid IN ('.$theList.') '.t3lib_BEfunc::deleteClause($table);
					$mres = mysql(TYPO3_db,$query);
					while($row=mysql_fetch_assoc($mres))	{
						if (isset($dbArr[$row['uid']]))	{
							unset ($dbArr[$row['uid']]);
						} else {
							$result.='Strange Error. ...<br />';
						}
					}
					reset($dbArr);
					while (list($theId,$theC)=each($dbArr))	{
						$result.='There are '.$theC.' records pointing to this missing or deleted record; ['.$table.']['.$theId.']<br />';
					}
				}
			} else {
				$result.='Codeerror. Table is not a table...<br />';
			}
		}
		return $result;
	}

	/**
	 * Finding all references to record based on table/uid
	 * 
	 * @param	string		Table name
	 * @param	integer		Uid
	 * @return	array		Array with other arrays containing information about where references was found
	 */
	function whereIsRecordReferenced($searchTable,$id)	{
		global $TCA;
		$fileFields = $this->getDBFields($searchTable);	// Gets tables / Fields that reference to files...
		$theRecordList=Array();	
		while (list(,$info)=each($fileFields))	{
			$table=$info[0];	$field=$info[1];
			t3lib_div::loadTCA($table);
			$query = 'SELECT uid,pid,'.$TCA[$table]['ctrl']['label'].','.$field.' FROM '.$table.' WHERE '.$field.' LIKE "%'.addSlashes($id).'%"';
			$mres = mysql(TYPO3_db,$query);
			while ($row = mysql_fetch_assoc($mres))	{
					// Now this is the field, where the reference COULD come from. But we're not garanteed, so we must carefully examine the data.
				$fieldConf = $TCA[$table]['columns'][$field]['config'];
				$allowedTables = ($fieldConf['type']=='group') ? $fieldConf['allowed'] : $fieldConf['foreign_table'];
			
				$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
				$dbAnalysis->start($row[$field],$allowedTables,$fieldConf['MM'],$row['uid']);
				reset($dbAnalysis->itemArray);
				while (list(,$tempArr)=each($dbAnalysis->itemArray))	{
					if ($tempArr['table']==$searchTable && $tempArr['id']==$id)	{
						$theRecordList[]=Array('table'=>$table,'uid'=>$row['uid'],'field'=>$field,'pid'=>$row['pid']);
					}
				}
			}
		}
		return $theRecordList;
	}

	/**
	 * Finding all references to file based on uploadfolder / filename
	 * 
	 * @param	string		Upload folder where file is found
	 * @param	string		filename
	 * @return	array		Array with other arrays containing information about where references was found
	 */
	function whereIsFileReferenced($uploadfolder,$filename)	{
		global $TCA;
		$fileFields = $this->getFileFields($uploadfolder);	// Gets tables / Fields that reference to files...
		$theRecordList=Array();	
		while (list(,$info)=each($fileFields))	{
			$table=$info[0];	$field=$info[1];
			$query = 'SELECT uid,pid,'.$TCA[$table]['ctrl']['label'].','.$field.' FROM '.$table.' WHERE '.$field.' LIKE "%'.addSlashes($filename).'%"';
			$mres = mysql(TYPO3_db,$query);
			while ($row = mysql_fetch_assoc($mres))	{
				// Now this is the field, where the reference COULD come from. But we're not garanteed, so we must carefully examine the data.
				$tempArr = explode(',',trim($row[$field]));
				while (list(,$file)=each($tempArr))	{
					$file = trim($file);
					if ($file==$filename)	{
						$theRecordList[]=Array('table'=>$table,'uid'=>$row['uid'],'field'=>$field,'pid'=>$row['pid']);
					}
				}
			}
		}
		return $theRecordList;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_admin.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_admin.php']);
}
?>