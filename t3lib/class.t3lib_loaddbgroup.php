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
 * Contains class for loading database groups
 *
 * Revised for TYPO3 3.6 September/2003 by Kasper Skårhøj
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   69: class t3lib_loadDBGroup	
 *   96:     function start ($itemlist,$tablelist, $MMtable='',$MMuid=0)	
 *  137:     function readList($itemlist)	
 *  183:     function readMM($tableName,$uid)	
 *  213:     function writeMM($tableName,$uid,$prependTableName=0)	
 *  242:     function getValueArray($prependTableName='')	
 *  270:     function convertPosNeg($valueArray,$fTable,$nfTable)	
 *  292:     function getFromDB()	
 *  325:     function readyForInterface()	
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */










/**
 * Load database groups (relations)
 * Used to process the relations created by the TCA element types "group" and "select" for database records. Manages MM-relations as well.
 * 
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_loadDBGroup	{
		// External, static:
	var $fromTC = 1;					// Means that only uid and the label-field is returned
	var $registerNonTableValues=0;		// If set, values that are not ids in tables are normally discarded. By this options they will be preserved.

		// Internal, dynamic:
	var $tableArray=Array();			// Contains the table names as keys. The values are the id-values for each table. Should ONLY contain proper table names.
	var $itemArray=Array();				// Contains items in an numeric array (table/id for each). Tablenames here might be "_NO_TABLE"
	var $nonTableArray=array();			// Array for NON-table elements
	var $additionalWhere=array();
	var $checkIfDeleted = 1;			// deleted-column is added to additionalWhere... if this is set...
	var $dbPaths=Array();
	var $firstTable = '';				// Will contain the first table name in the $tablelist (for positive ids)
	var $secondTable = '';				// Will contain the second table name in the $tablelist (for negative ids)
	
	


	/**
	 * Initialization of the class.
	 * 
	 * @param	string		List of group/select items
	 * @param	string		Comma list of tables, first table takes priority if no table is set for an entry in the list.
	 * @param	string		Name of a MM table.
	 * @param	integer		Local UID for MM lookup
	 * @return	void		
	 */
	function start ($itemlist,$tablelist, $MMtable='',$MMuid=0)	{
			// If the table list is "*" then all tables are used in the list:
		if (!strcmp(trim($tablelist),'*'))	{
			$tablelist = implode(',',array_keys($GLOBALS['TCA']));
		}

			// The tables are traversed and internal arrays are initialized:
		$tempTableArray = t3lib_div::trimExplode(',',$tablelist,1);
		foreach($tempTableArray as $key => $val)	{
			$tName = trim($val);
			$this->tableArray[$tName] = Array();
			if ($this->checkIfDeleted && $GLOBALS['TCA'][$tName]['ctrl']['delete'])	{
				$fieldN = $tName.'.'.$GLOBALS['TCA'][$tName]['ctrl']['delete'];
				$this->additionalWhere[$tName].=' AND NOT '.$fieldN;
			}
		}
		
		if (is_array($this->tableArray))	{
			reset($this->tableArray);
		} else {return 'No tables!';}

			// Set first and second tables:
		$this->firstTable = key($this->tableArray);		// Is the first table
		next($this->tableArray);
		$this->secondTable = key($this->tableArray);	// If the second table is set and the ID number is less than zero (later) then the record is regarded to come from the second table...
		
			// Now, populate the internal itemArray and tableArray arrays:
		if ($MMtable)	{	// If MM, then call this function to do that:
			$this->readMM($MMtable,$MMuid);
		} else {
				// If not MM, then explode the itemlist by "," and traverse the list:
			$this->readList($itemlist);
		}		
	}
	
	/**
	 * Explodes the item list and stores the parts in the internal arrays itemArray and tableArray from MM records.
	 * 
	 * @param	string		Item list
	 * @return	void		
	 */
	function readList($itemlist)	{
		if ((string)trim($itemlist)!='')	{
			$tempItemArray = explode(',',$itemlist);
			while(list($key,$val)=each($tempItemArray))	{
				$isSet = 0;	// Will be set to "1" if the entry was a real table/id:

					// Extract table name and id. This is un the formular [tablename]_[id] where table name MIGHT contain "_", hence the reversion of the string!					
				$val = strrev($val);
				$parts = explode('_',$val,2);
				$theID = strrev($parts[0]);

					// Check that the id IS an integer:
				if (t3lib_div::testInt($theID))	{
						// Get the table name: If a part of the exploded string, use that. Otherwise if the id number is LESS than zero, use the second table, otherwise the first table
					$theTable = trim($parts[1]) ? strrev(trim($parts[1])) : ($this->secondTable && $theID<0 ? $this->secondTable : $this->firstTable);
						// If the ID is not blank and the table name is among the names in the inputted tableList, then proceed:
					if ((string)$theID!='' && $theID && $theTable && isset($this->tableArray[$theTable]))	{
							// Get ID as the right value:
						$theID = $this->secondTable ? abs(intval($theID)) : intval($theID);
							// Register ID/table name in internal arrays:
						$this->itemArray[$key]['id'] = $theID;
						$this->itemArray[$key]['table'] = $theTable;
						$this->tableArray[$theTable][]=$theID;
							// Set update-flag:
						$isSet=1;
					}
				}
				
					// If it turns out that the value from the list was NOT a valid reference to a table-record, then we might still set it as a NO_TABLE value:
				if (!$isSet && $this->registerNonTableValues)	{
					$this->itemArray[$key]['id'] = $tempItemArray[$key];
					$this->itemArray[$key]['table'] = '_NO_TABLE';
					$this->nonTableArray[] = $tempItemArray[$key];
				}
			}
		}
	}
	
	/**
	 * Reads the record tablename/id into the internal arrays itemArray and tableArray from MM records.
	 * You can call this function after start if you supply no list to start()
	 * 
	 * @param	string		MM Tablename
	 * @param	integer		Local UID
	 * @return	void		
	 */
	function readMM($tableName,$uid)	{
			// Select all MM relations:
		$query='SELECT * FROM '.$tableName.' WHERE uid_local='.intval($uid).' ORDER BY sorting';
		$res=mysql(TYPO3_db,$query);
		echo mysql_error();
		
		$key=0;
		while($row=mysql_fetch_assoc($res))	{
			$theTable = $row['tablenames'] ? $row['tablenames'] : $this->firstTable;		// If tablesnames columns exists and contain a name, then this value is the table, else it's the the firstTable...
			if (($row['uid_foreign'] || $theTable=='pages') && $theTable && isset($this->tableArray[$theTable]))	{
				$this->itemArray[$key]['id'] = $row['uid_foreign'];
				$this->itemArray[$key]['table'] = $theTable;
				$this->tableArray[$theTable][]= $row['uid_foreign'];
			} elseif ($this->registerNonTableValues)	{
				$this->itemArray[$key]['id'] = $row['uid_foreign'];
				$this->itemArray[$key]['table'] = '_NO_TABLE';
				$this->nonTableArray[] = $row['uid_foreign'];
			}
			$key++;
		}
	}

	/**
	 * Writes the internal itemArray to MM table:
	 * 
	 * @param	string		MM table name
	 * @param	integer		Local UID
	 * @param	boolean		If set, then table names will always be written.
	 * @return	void		
	 */
	function writeMM($tableName,$uid,$prependTableName=0)	{
			// Delete all relations:
		$query='DELETE FROM '.$tableName.' WHERE uid_local='.intval($uid);
		$res=mysql(TYPO3_db,$query);
		
			// If there are tables...			
		$tableC = count($this->tableArray);
		if ($tableC)	{
			$prep = ($tableC>1||$prependTableName) ? 1 : 0;
			$c=0;
			$tName=array();
				// For each item, insert it:
			foreach($this->itemArray as $val)	{
				$c++;
				if ($prep || $val['table']=='_NO_TABLE')	{
					$tName=array(',tablenames', ',"'.addslashes($val['table']).'"');
				}
				$query='INSERT INTO '.$tableName.' (uid_local,uid_foreign,sorting'.$tName[0].') VALUES ("'.$uid.'","'.addslashes($val['id']).'",'.$c.$tName[1].')';
				$res=mysql(TYPO3_db,$query);
			}
		}
	}

	/**
	 * After initialization you can extract an array of the elements from the object. Use this function for that.
	 * 
	 * @param	boolean		If set, then table names will ALWAYS be prepended (unless its a _NO_TABLE value)
	 * @return	array		A numeric array.
	 */
	function getValueArray($prependTableName='')	{
			// INIT:
		$valueArray=Array();
		$tableC = count($this->tableArray);
		
			// If there are tables in the table array:
		if ($tableC)	{
				// If there are more than ONE table in the table array, then always prepend table names:
			$prep = ($tableC>1||$prependTableName) ? 1 : 0;
			
				// Traverse the array of items:
			foreach($this->itemArray as $val)	{
				$valueArray[]=(($prep && $val['table']!='_NO_TABLE') ? $val['table'].'_' : '').
									$val['id'];
			}
		}
			// Return the array
		return $valueArray;	
	}

	/**
	 * Converts id numbers from negative to positive.
	 * 
	 * @param	array		Array of [table]_[id] pairs.
	 * @param	string		Foreign table (the one used for positive numbers)
	 * @param	string		NEGative foreign table
	 * @return	array		The array with ID integer values, converted to positive for those where the table name was set but did NOT match the positive foreign table.
	 */
	function convertPosNeg($valueArray,$fTable,$nfTable)	{
		if (is_array($valueArray) && $fTable)	{
			foreach($valueArray as $key => $val)	{
				$val = strrev($val);
				$parts = explode('_',$val,2);
				$theID = strrev($parts[0]);
				$theTable = strrev($parts[1]);
				
				if ( t3lib_div::testInt($theID) && (!$theTable || !strcmp($theTable,$fTable) || !strcmp($theTable,$nfTable)) )	{
					$valueArray[$key]= $theTable && strcmp($theTable,$fTable) ? $theID*-1 : $theID;
				}
			}
		}
		return $valueArray;	
	}
	
	/**
	 * Reads all records from internal tableArray into the internal ->results array where keys are table names and for each table, records are stored with uids as their keys.
	 * If $this->fromTC is set you can save a little memory since only uid,pid and a few other fields are selected.
	 * 
	 * @return	void		
	 */
	function getFromDB()	{
			// Traverses the tables listed:		
		foreach($this->tableArray as $key => $val)	{
			if (is_array($val))	{
				$itemList = implode($val,',');
				if ($itemList)	{
					$from = '*';
					if ($this->fromTC)	{
						$from = 'uid,pid';
						if ($GLOBALS['TCA'][$key]['ctrl']['label'])	{
							$from.= ','.$GLOBALS['TCA'][$key]['ctrl']['label'];	// Titel
						}
						if ($GLOBALS['TCA'][$key]['ctrl']['thumbnail'])	{
							$from.= ','.$GLOBALS['TCA'][$key]['ctrl']['thumbnail'];	// Thumbnail
						}
					}
					$query='SELECT '.$from.' FROM '.$key.' WHERE uid IN ('.$itemList.')'.$this->additionalWhere[$key];
					$res=mysql(TYPO3_db,$query);
					while($row = mysql_fetch_assoc($res))	{
						$this->results[$key][$row['uid']]=$row;
					}
				}
			}
		}
		return $this->results;
	}

	/**
	 * Prepare items from itemArray to be transferred to the TCEforms interface (as a comma list)
	 * 
	 * @return	string		
	 * @see t3lib_transferdata::renderRecord()
	 */
	function readyForInterface()	{
		global $TCA;
		
		if (!is_array($this->itemArray))	{return false;}

		$output=array();
		$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);		// For use when getting the paths....
		$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);

		foreach($this->itemArray as $key => $val)	{
			$theRow = $this->results[$val['table']][$val['id']];
			if ($theRow && is_array($TCA[$val['table']]))	{
				$label = t3lib_div::fixed_lgd(strip_tags($theRow[$TCA[$val['table']]['ctrl']['label']]),$titleLen);
				$label = ($label)?$label:'[...]';
				$output[]=str_replace(',','',$val['table'].'_'.$val['id'].'|'.rawurlencode($label));
			}
		}
		return implode(',',$output);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_loaddbgroup.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_loaddbgroup.php']);
}
?>