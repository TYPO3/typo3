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
 * Searching in database tables, typ. "pages" and "tt_content"
 * Used to generate search queries for TypoScript.
 * The class is included from "class.tslib_pagegen.php" based on whether there has been detected content in the GPvar "sword"
 *
 * $Id$
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @author	Rene Fritz	<r.fritz@colorcube.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   88: class tslib_search 
 *  138:     function register_tables_and_columns($requestedCols,$allowedCols)	
 *  180:     function explodeCols($in)	
 *  205:     function register_and_explode_search_string ($sword)	
 *  238:     function split($origSword, $specchars='+-')	
 *  269:     function quotemeta($str)	
 *  283:     function build_search_query ($endClause) 
 *  366:     function build_search_query_for_searchwords ()	
 *  415:     function get_operator ($operator)	
 *  438:     function count_query () 
 *  452:     function execute_query() 
 *  468:     function get_searchwords()	
 *  484:     function get_searchwordsArray()	
 *
 * TOTAL FUNCTIONS: 12
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
/**
 * Search class used for the content object SEARCHRESULT
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @see	tslib_cObj::SEARCHRESULT()
 */
class tslib_search {
	var $tables = Array ();

	var $standalone = '';	// true / false - if the searchresult MAY NOT be a part-string (doesn't work yet. Does not find words with parentheses or periods before/after....beginning/end of a line is also a problem!)
	var $mixedcase = 'yes';		// true / false - matches both upper and lower case (doesn't work if you disable. Matches all cases currently)

	var $order_by = '';		// ORDER BY part of the query. (field-reference, eg. 'pages.uid'
	var $group_by = 'PRIMARY_KEY';		// Alternatively 'PRIMARY_KEY'; sorting by primary key

	var $default_operator = 'AND';	// Standard SQL-operator between words
	var $operator_translate_table_caseinsensitive = '1';
	var $operator_translate_table = Array (		// case-sensitiv. Defineres the words, which will be operators between words
		Array ('+' , 'AND'),
		Array ('-' , 'AND NOT'),
			// english
		Array ('AND' , 'AND'),
		Array ('OR' , 'OR'),
		Array ('NOT' , 'AND NOT'),
			// danish
		Array ('OG' , 'AND'),
		Array ('ELLER' , 'OR'),
		Array ('UDEN' , 'AND NOT')
	);

	// Internal
	var $sword_array;		// Contains the search-words and operators
	var $query_begin = '';		// Beginning of query
	var $query_end = '';			// Ending of query

	var $query;			// Contains the final query after processing.

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
//				$this->tables[$parts[0]]['resultfields']['uid']='uid';	// Cannot set this, because then the link to the searched page will not be correct! Must set otherwise
			}
		}
		$this->tables['pages']['primary_key'] = 'uid';
		$this->tables['pages']['resultfields'][]='uid';
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
					$this->fTable=$t;
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
		while(list(,$val)=each($theArray))	{
			$val=trim($val);
			$parts = explode('.',$val);
			if ($parts[0] && $parts[1])	{
				$subparts = explode('-',$parts[1]);
				while(list(,$piece)=each($subparts))	{
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
	function register_and_explode_search_string ($sword)	{
		$sword = trim($sword);
		if ($sword)	{
			$components = $this->split($sword);
			$s_sword = '';	 // the searchword is stored here during the loop
			if (is_array($components))	{
				$i=0;
				$lastoper = '';
				reset($components);
				while (list($key,$val) = each ($components))	{
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
	 * This function also has the charm of still containing some of the original comments - in danish!
	 * This function could be re-written to be more clean and effective - yet it's not that important.
	 * 
	 * @param	string		The raw sword string from outside
	 * @param	string		Special chars which are used as operators (+- is default)
	 * @return	mixed		Returns an ARRAY if there were search words, othervise the return value may be unset.
	 */
	function split($origSword, $specchars='+-')	{
		$sword = $origSword;
		$specs = '['.$this->quotemeta($specchars).']';

			// As long as $sword is true (that means $sword MUST be reduced little by little until its empty inside the loop!)
		while ($sword)	{
			if (ereg('^"',$sword))	{		// There was a double-quote and we will then look for the ending quote.
				$sword = ereg_replace('^"','',$sword);		// Fjerner først gåseøje.
				ereg('^[^"]*',$sword,$reg);  // Tager alt indtil næste gåseøje
				$value[] = $reg[0];  // reg[0] er lig med værdien!! Skal ikke trimmes
				$sword = ereg_replace('^'.$this->quotemeta($reg[0]),'',$sword);
				$sword = trim(ereg_replace('^"','',$sword));		// Fjerner det sidste gåseøje.
			} elseif (ereg('^'.$specs,$sword,$reg)) {
				$value[] = $reg[0];
				$sword = trim(ereg_replace('^'.$specs,'',$sword));		// Fjerner = tegn.
			} else {
				// Der er ikke gåseøjne om værdien. Der ledes efter næste ' ' eller '>'
				ereg('^[^ '.$this->quotemeta($specchars).']*',$sword,$reg);
				$value[] = trim($reg[0]);
				$sword = trim(ereg_replace('^'.$this->quotemeta($reg[0]),'',$sword));
			}
		}
		return $value;
	}

	/**
	 * Local version of quotemeta. This is the same as the PHP function but the vertical line, |, is also escaped with a slash.
	 * 
	 * @param	string		String to pass through quotemeta()
	 * @return	string		Return value
	 */
	function quotemeta($str)	{
		return str_replace('|','\|',quotemeta($str));
	}

	/**
	 * This creates the search-query.
	 * In TypoScript this is used for searching only records not hidden, start/endtimed and fe_grouped! (enable-fields, see tt_content)
	 * Sets $this->query
	 * 
	 * @param	string		$endClause is some extra conditions that the search must match.
	 * @return	boolean		Returns true no matter what - sweet isn't it!
	 * @access private
	 * @see	tslib_cObj::SEARCHRESULT()
	 */
	function build_search_query ($endClause) {
		if (is_array($this->tables))	{
			$tables = $this->tables;
			$primary_table = '';
			$query = 'SELECT';
				// Primary key table is found.
			reset($tables);
			while (list($key,$val) = each($tables))	{
				if ($tables[$key]['primary_key'])	{$primary_table = $key;}
			}
			if ($primary_table) {
				reset($tables);
				while (list($key,$val) = each($tables))	{
					$resultfields = $tables[$key]['resultfields'];
					if (is_array($resultfields))	{
						reset($resultfields);
						while (list($key2,$val2) = each($resultfields))	{
							$query.= ' '.$key.'.'.$val2.',';
						}
					}
				}
	
				$query = t3lib_div::rm_endcomma($query);
				$query.= ' FROM';
	
				reset($tables);
				while (list($key,$val) = each($tables))	{
					$query.= ' '.$key.',';
				}
	
				$query = t3lib_div::rm_endcomma($query);
				$query.= ' WHERE';
				
				reset($tables);
				$primary_table_and_key = $primary_table.'.'.$tables[$primary_table]['primary_key'];
				$primKeys=Array();
				while (list($key,$val) = each($tables))	{
					$fkey = $tables[$key]['fkey'];
					if ($fkey)	{
						 $primKeys[]=$key.'.'.$fkey.'='.$primary_table_and_key;
					}
				}
				if (count($primKeys))	{
					$query.='('.implode($primKeys,' OR ').')';
				}
				if (!ereg('WHERE$',trim($query)))	{
					$query.=' AND';
				}

				$tempClause = trim($endClause);
				if ($tempClause)	{
					$query.= ' ('.$tempClause.') AND';
				}		

				$query.= ' (';
				$this->query_begin = $query;
	
				if ($this->group_by)	{
					if ($this->group_by == 'PRIMARY_KEY')	{
						$this->query_end = ') GROUP BY '.$primary_table_and_key;
					} else {
						$this->query_end = ') GROUP BY '.$this->group_by;
					}
				} else {
					$this->query_end = ')';
				}
			}
		}

		$query_part = $this->build_search_query_for_searchwords();
		if (!$query_part)	{
			$query_part='(0!=0)';
		}
		$this->query = $this->query_begin.$query_part.$this->query_end;
		return true;
	}

	/**
	 * Creates the part of the SQL-sentence, that searches for the search-words ($this->sword_array)
	 * 
	 * @return	string		Part of where class limiting result to the those having the search word.
	 * @access private
	 */
	function build_search_query_for_searchwords ()	{
		$tables = $this->tables;
		$sword_array = $this->sword_array;
		$query_part = '';
		$sp='';
		if ($this->standalone)	{$sp=' ';}		// Der indsættes et space foran og efter ordet, hvis det SKAL stå alene. Dette er dog ikke korrekt implementeret, fordi det ikke finder ord i starten, slutningen og i parenteser osv. Egentlig skal der være check på noget om der er alfanumeriske værdier før eller efter!!
		if (is_array($sword_array))	{
			reset($sword_array);
			while (list($key,$val) = each($sword_array))	{
				$s_sword = $sword_array[$key]['sword'];
					// Get subQueryPart
				$sub_query_part='';
				reset ($tables);
				$this->listOfSearchFields='';
				while (list($key3,$val3) = each($tables))	{
					$searchfields = $tables[$key3]['searchfields'];
					if (is_array($searchfields))	{
						reset ($searchfields);
						while (list($key2,$val2) = each($searchfields))	{
							$this->listOfSearchFields.=$key3.'.'.$val2.',';
							$sub_query_part.= ' '.$key3.'.'.$val2.' LIKE "%'.$sp.addslashes($s_sword).$sp.'%" OR';
						}
					}
				}
				$sub_query_part = trim(ereg_replace('OR$','',$sub_query_part));

				if ($sub_query_part)	{
					if ($query_part != '')	{
						$query_part.= ' '.$sword_array[$key]['oper'].' ('.$sub_query_part.')';
					} else {
						$query_part.= '('.$sub_query_part.')';
					}
				}
			}
			$query_part = trim($query_part);
			if (!$query_part || trim($query_part)=='()')	{
				$query_part = '';
			}
			return $query_part;
		}
	}

	/**
	 * This returns an SQL search-operator (eg. AND, OR, NOT) translated from the current localized set of operators (eg. in danish OG, ELLER, IKKE).
	 * 
	 * @param	string		The possible operator to find in the internal operator array.
	 * @return	string		If found, the SQL operator for the localized input operator.
	 * @access private
	 */
	function get_operator ($operator)	{
		$operator = trim($operator);
		$op_array = $this->operator_translate_table;
		reset ($op_array);
		if ($this->operator_translate_table_caseinsensitive)	{
			$operator = strtoupper($operator);
		}
		while (list($key,$val) = each($op_array))	{
			$item = $op_array[$key][0];
			if ($this->operator_translate_table_caseinsensitive)	{
				$item = strtoupper($item);
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
	function count_query () {
		if ($this->query)	{
			$res = mysql(TYPO3_db, $this->query);
			echo mysql_error();
		    $this->res_count = mysql_num_rows($res);
			return true;
		}
	}

	/**
	 * Executes the search, sets result pointer in $this->result
	 * 
	 * @return	boolean		True, if $this->query was set and query performed
	 */
	function execute_query() {
		$query = $this->query;
		if ($query)	{
			if ($this->order_by)	$query.= ' ORDER BY '.$this->order_by;
	        $this->result = mysql(TYPO3_db, $query);
			echo mysql_error();
			return true;
		}
	}

	/**
	 * Returns URL-parameters with the current search words.
	 * Used when linking to result pages so that search words can be highlighted.
	 * 
	 * @return	string		URL-parameters with the searchwords
	 */
	function get_searchwords()	{
		$SWORD_PARAMS='';
		if (is_array($this->sword_array))	{
			reset($this->sword_array);
			while (list($key,$val)=each($this->sword_array))	{
				$SWORD_PARAMS.='&sword_list[]='.rawurlencode($val['sword']);
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
			reset($this->sword_array);
			while (list($key,$val)=each($this->sword_array))	{
				$swords[]=$val['sword'];
			}
		}
		return $swords;
	}
}	   




if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_search.php']);
}

?>