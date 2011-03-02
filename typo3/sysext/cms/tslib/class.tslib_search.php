<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Searching in database tables, typ. "pages" and "tt_content"
 * Used to generate search queries for TypoScript.
 * The class is included from "class.tslib_pagegen.php" based on whether there has been detected content in the GPvar "sword"
 *
 * $Id$
 * Revised for TYPO3 3.6 June/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz	<r.fritz@colorcube.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   88: class tslib_search
 *  127:     function register_tables_and_columns($requestedCols,$allowedCols)
 *  168:     function explodeCols($in)
 *  193:     function register_and_explode_search_string($sword)
 *  226:     function split($origSword, $specchars='+-', $delchars='+.,-')
 *  269:     function quotemeta($str)
 *  285:     function build_search_query($endClause)
 *  371:     function build_search_query_for_searchwords()
 *  413:     function get_operator($operator)
 *  436:     function count_query()
 *  449:     function execute_query()
 *  462:     function get_searchwords()
 *  477:     function get_searchwordsArray()
 *
 * TOTAL FUNCTIONS: 12
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



















/**
 * Search class used for the content object SEARCHRESULT
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @see	tslib_cObj::SEARCHRESULT()
 */
class tslib_search {
	var $tables = Array ();

	var $group_by = 'PRIMARY_KEY';							// Alternatively 'PRIMARY_KEY'; sorting by primary key
	var $default_operator = 'AND';							// Standard SQL-operator between words
	var $operator_translate_table_caseinsensitive = TRUE;
	var $operator_translate_table = Array (					// case-sensitiv. Defineres the words, which will be operators between words
		Array ('+' , 'AND'),
		Array ('|' , 'AND'),
		Array ('-' , 'AND NOT'),
			// english
		Array ('and' , 'AND'),
		Array ('or' , 'OR'),
		Array ('not' , 'AND NOT'),
	);

	// Internal
	var $sword_array;		// Contains the search-words and operators
	var $queryParts;		// Contains the query parts after processing.

	var $other_where_clauses;	// Addition to the whereclause. This could be used to limit search to a certain page or alike in the system.
	var $fTable;		// This is set with the foreign table that 'pages' are connected to.

	var $res_offset = 0;	// How many rows to offset from the beginning
	var $res_shows = 20;	// How many results to show (0 = no limit)
	var $res_count;			// Intern: How many results, there was last time (with the exact same searchstring.

	var $pageIdList='';		// List of pageIds.

	var $listOfSearchFields ='';

	/**
	 * Creates the $this->tables-array.
	 * The 'pages'-table is ALWAYS included as the search is page-based. Apart from this there may be one and only one table, joined with the pages-table. This table is the first table mentioned in the requested-list. If any more tables are set here, they are ignored.
	 *
	 * @param	string		is a list (-) of columns that we want to search. This could be input from the search-form (see TypoScript documentation)
	 * @param	string		$allowedCols: is the list of columns, that MAY be searched. All allowed cols are set as result-fields. All requested cols MUST be in the allowed-fields list.
	 * @return	void
	 */
	function register_tables_and_columns($requestedCols,$allowedCols)	{
		$rCols=$this->explodeCols($requestedCols);
		$aCols=$this->explodeCols($allowedCols);

		foreach ($rCols as $k => $v)	{
			$rCols[$k]=trim($v);
			if (in_array($rCols[$k], $aCols))	{
				$parts = explode('.',$rCols[$k]);
				$this->tables[$parts[0]]['searchfields'][] = $parts[1];
			}
		}
		$this->tables['pages']['primary_key'] = 'uid';
		$this->tables['pages']['resultfields'][] = 'uid';
		unset($this->tables['pages']['fkey']);

		foreach ($aCols as $k => $v)	{
			$aCols[$k]=trim($v);
			$parts = explode('.',$aCols[$k]);
			$this->tables[$parts[0]]['resultfields'][] = $parts[1].' AS '.str_replace('.','_',$aCols[$k]);
			$this->tables[$parts[0]]['fkey']='pid';
		}

		$this->fTable='';
		foreach ($this->tables as $t => $v)	{
			if ($t!='pages')	{
				if (!$this->fTable)	{
					$this->fTable = $t;
				} else {
					unset($this->tables[$t]);
				}
			}
		}
	}

	/**
	 * Function that can convert the syntax for entering which tables/fields the search should be conducted in.
	 *
	 * @param	string		This is the code-line defining the tables/fields to search. Syntax: '[table1].[field1]-[field2]-[field3] : [table2].[field1]-[field2]'
	 * @return	array		An array where the values is "[table].[field]" strings to search
	 * @see	register_tables_and_columns()
	 */
	function explodeCols($in)	{
		$theArray = explode(':',$in);
		$out = Array();
		foreach ($theArray as $val) {
			$val=trim($val);
			$parts = explode('.',$val);
			if ($parts[0] && $parts[1])	{
				$subparts = explode('-',$parts[1]);
				foreach ($subparts as $piece) {
					$piece=trim($piece);
					if ($piece)		$out[]=$parts[0].'.'.$piece;
				}
			}
		}
		return $out;
	}

	/**
	 * Takes a search-string (WITHOUT SLASHES or else it'll be a little sppooky , NOW REMEMBER to unslash!!)
	 * Sets up $this->sword_array op with operators.
	 * This function uses $this->operator_translate_table as well as $this->default_operator
	 *
	 * @param	string		The input search-word string.
	 * @return	void
	 */
	function register_and_explode_search_string($sword)	{
		$sword = trim($sword);
		if ($sword)	{
			$components = $this->split($sword);
			$s_sword = '';	 // the searchword is stored here during the loop
			if (is_array($components))	{
				$i=0;
				$lastoper = '';
				foreach ($components as $key => $val) {
					$operator=$this->get_operator($val);
					if ($operator)	{
						$lastoper = $operator;
					} elseif (strlen($val)>1) {		// A searchword MUST be at least two characters long!
						$this->sword_array[$i]['sword'] = $val;
						$this->sword_array[$i]['oper'] = ($lastoper) ? $lastoper : $this->default_operator;
						$lastoper = '';
						$i++;
					}
				}
			}
		}
	}

	/**
	 * Used to split a search-word line up into elements to search for. This function will detect boolean words like AND and OR, + and -, and even find sentences encapsulated in ""
	 * This function could be re-written to be more clean and effective - yet it's not that important.
	 *
	 * @param	string		The raw sword string from outside
	 * @param	string		Special chars which are used as operators (+- is default)
	 * @param	string		Special chars which are deleted if the append the searchword (+-., is default)
	 * @return	mixed		Returns an ARRAY if there were search words, othervise the return value may be unset.
	 */
	function split($origSword, $specchars='+-', $delchars='+.,-')	{
		$sword = $origSword;
		$specs = '[' . preg_quote($specchars, '/') . ']';

			// As long as $sword is true (that means $sword MUST be reduced little by little until its empty inside the loop!)
		while ($sword)	{
			if (preg_match('/^"/',$sword))	{		// There was a double-quote and we will then look for the ending quote.
				$sword = preg_replace('/^"/','',$sword);		// Removes first double-quote
				preg_match('/^[^"]*/',$sword,$reg);  // Removes everything till next double-quote
				$value[] = $reg[0];  // reg[0] is the value, should not be trimmed
				$sword = preg_replace('/^' . preg_quote($reg[0], '/') . '/', '', $sword);
				$sword = trim(preg_replace('/^"/','',$sword));		// Removes last double-quote
			} elseif (preg_match('/^'.$specs.'/',$sword,$reg)) {
				$value[] = $reg[0];
				$sword = trim(preg_replace('/^'.$specs.'/','',$sword));		// Removes = sign
			} elseif (preg_match('/[\+\-]/',$sword)) {	// Check if $sword contains + or -
					// + and - shall only be interpreted as $specchars when there's whitespace before it
					// otherwise it's included in the searchword (e.g. "know-how")
				$a_sword = explode(' ',$sword);	// explode $sword to single words
				$word = array_shift($a_sword);	// get first word
				$word = rtrim($word, $delchars);		// Delete $delchars at end of string
				$value[] = $word;	// add searchword to values
				$sword = implode(' ',$a_sword);	// re-build $sword
			} else {
					// There are no double-quotes around the value. Looking for next (space) or special char.
				preg_match('/^[^ ' . preg_quote($specchars, '/') . ']*/', $sword, $reg);
				$word = rtrim(trim($reg[0]), $delchars);		// Delete $delchars at end of string
				$value[] = $word;
				$sword = trim(preg_replace('/^' . preg_quote($reg[0], '/') . '/', '', $sword));
			}
		}

		return $value;
	}

	/**
	 * Local version of quotemeta. This is the same as the PHP function
	 * but the vertical line, |, and minus, -, is also escaped with a slash.
	 *
	 * @deprecated This function is deprecated since TYPO3 4.6 and will be removed in TYPO3 4.8. Please, use preg_quote() instead.
	 * @param	string		String to pass through quotemeta()
	 * @return	string		Return value
	 */
	function quotemeta($str) {
		t3lib_div::logDeprecatedFunction();

		$str = str_replace('|','\|',quotemeta($str));
		#$str = str_replace('-','\-',$str);		// Breaks "-" which should NOT have a slash before it inside of [ ] in a regex.
		return $str;
	}

	/**
	 * This creates the search-query.
	 * In TypoScript this is used for searching only records not hidden, start/endtimed and fe_grouped! (enable-fields, see tt_content)
	 * Sets $this->queryParts
	 *
	 * @param	string		$endClause is some extra conditions that the search must match.
	 * @return	boolean		Returns true no matter what - sweet isn't it!
	 * @access private
	 * @see	tslib_cObj::SEARCHRESULT()
	 */
	function build_search_query($endClause) {

		if (is_array($this->tables))	{
			$tables = $this->tables;
			$primary_table = '';

				// Primary key table is found.
			foreach($tables as $key => $val)	{
				if ($tables[$key]['primary_key'])	{$primary_table = $key;}
			}

			if ($primary_table) {

					// Initialize query parts:
				$this->queryParts = array(
					'SELECT' => '',
					'FROM' => '',
					'WHERE' => '',
					'GROUPBY' => '',
					'ORDERBY' => '',
					'LIMIT' => '',
				);

					// Find tables / field names to select:
				$fieldArray = array();
				$tableArray = array();
				foreach($tables as $key => $val)	{
					$tableArray[] = $key;
					$resultfields = $tables[$key]['resultfields'];
					if (is_array($resultfields))	{
						foreach($resultfields as $key2 => $val2)	{
							$fieldArray[] = $key.'.'.$val2;
						}
					}
				}
				$this->queryParts['SELECT'] = implode(',',$fieldArray);
				$this->queryParts['FROM'] = implode(',',$tableArray);

					// Set join WHERE parts:
				$whereArray = array();

				$primary_table_and_key = $primary_table.'.'.$tables[$primary_table]['primary_key'];
				$primKeys = Array();
				foreach($tables as $key => $val)	{
					$fkey = $tables[$key]['fkey'];
					if ($fkey)	{
						 $primKeys[] = $key.'.'.$fkey.'='.$primary_table_and_key;
					}
				}
				if (count($primKeys))	{
					$whereArray[] = '('.implode(' OR ',$primKeys).')';
				}

					// Additional where clause:
				if (trim($endClause))	{
					$whereArray[] = trim($endClause);
				}

					// Add search word where clause:
				$query_part = $this->build_search_query_for_searchwords();
				if (!$query_part)	{
					$query_part = '(0!=0)';
				}
				$whereArray[] = '('.$query_part.')';

					// Implode where clauses:
				$this->queryParts['WHERE'] = implode(' AND ',$whereArray);

					// Group by settings:
				if ($this->group_by)	{
					if ($this->group_by == 'PRIMARY_KEY')	{
						$this->queryParts['GROUPBY'] = $primary_table_and_key;
					} else {
						$this->queryParts['GROUPBY'] = $this->group_by;
					}
				}
			}
		}
	}

	/**
	 * Creates the part of the SQL-sentence, that searches for the search-words ($this->sword_array)
	 *
	 * @return	string		Part of where class limiting result to the those having the search word.
	 * @access private
	 */
	function build_search_query_for_searchwords()	{

		if (is_array($this->sword_array))	{
			$main_query_part = array();

			foreach($this->sword_array as $key => $val)	{
				$s_sword = $this->sword_array[$key]['sword'];

					// Get subQueryPart
				$sub_query_part = array();

				$this->listOfSearchFields='';
				foreach($this->tables as $key3 => $val3)	{
					$searchfields = $this->tables[$key3]['searchfields'];
					if (is_array($searchfields))	{
						foreach($searchfields as $key2 => $val2)	{
							$this->listOfSearchFields.= $key3.'.'.$val2.',';
							$sub_query_part[] = $key3.'.'.$val2.' LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr($s_sword, $key3).'%\'';
						}
					}
				}

				if (count($sub_query_part))	{
					$main_query_part[] = $this->sword_array[$key]['oper'];
					$main_query_part[] = '('.implode(' OR ',$sub_query_part).')';
				}
			}

			if (count($main_query_part))	{
				unset($main_query_part[0]);	// Remove first part anyways.
				return implode(' ',$main_query_part);
			}
		}
	}

	/**
	 * This returns an SQL search-operator (eg. AND, OR, NOT) translated from the current localized set of operators (eg. in danish OG, ELLER, IKKE).
	 *
	 * @param	string		The possible operator to find in the internal operator array.
	 * @return	string		If found, the SQL operator for the localized input operator.
	 * @access private
	 */
	function get_operator($operator)	{
		$operator = trim($operator);
		$op_array = $this->operator_translate_table;
		if ($this->operator_translate_table_caseinsensitive)	{
			$operator = strtolower($operator);	// case-conversion is charset insensitive, but it doesn't spoil anything if input string AND operator table is already converted
		}
		foreach ($op_array as $key => $val) {
			$item = $op_array[$key][0];
			if ($this->operator_translate_table_caseinsensitive)	{
				$item = strtolower($item);	// See note above.
			}
			if ($operator==$item)	{
				return $op_array[$key][1];
			}
		}
	}

	/**
	 * Counts the results and sets the result in $this->res_count
	 *
	 * @return	boolean		True, if $this->query was found
	 */
	function count_query() {
		if (is_array($this->queryParts))	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($this->queryParts['SELECT'], $this->queryParts['FROM'], $this->queryParts['WHERE'], $this->queryParts['GROUPBY']);
		    $this->res_count = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
			return TRUE;
		}
	}

	/**
	 * Executes the search, sets result pointer in $this->result
	 *
	 * @return	boolean		True, if $this->query was set and query performed
	 */
	function execute_query() {
		if (is_array($this->queryParts))	{
	        $this->result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($this->queryParts);
			return TRUE;
		}
	}

	/**
	 * Returns URL-parameters with the current search words.
	 * Used when linking to result pages so that search words can be highlighted.
	 *
	 * @return	string		URL-parameters with the searchwords
	 */
	function get_searchwords()	{
		$SWORD_PARAMS = '';
		if (is_array($this->sword_array))	{
			foreach($this->sword_array as $key => $val)	{
				$SWORD_PARAMS.= '&sword_list[]='.rawurlencode($val['sword']);
			}
		}
		return $SWORD_PARAMS;
	}

	/**
	 * Returns an array with the search words in
	 *
	 * @return	array		IF the internal sword_array contained search words it will return these, otherwise "void"
	 */
	function get_searchwordsArray()	{
		if (is_array($this->sword_array))	{
			foreach($this->sword_array as $key => $val)	{
				$swords[] = $val['sword'];
			}
		}
		return $swords;
	}
}




if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/class.tslib_search.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/class.tslib_search.php']);
}

?>