<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * TYPO3 SQL parser
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  106: class t3lib_sqlparser
 *
 *              SECTION: SQL Parsing, full queries
 *  128:     function parseSQL($parseString)
 *  188:     function parseSELECT($parseString)
 *  257:     function parseUPDATE($parseString)
 *  311:     function parseINSERT($parseString)
 *  371:     function parseDELETE($parseString)
 *  409:     function parseEXPLAIN($parseString)
 *  431:     function parseCREATETABLE($parseString)
 *  503:     function parseALTERTABLE($parseString)
 *  572:     function parseDROPTABLE($parseString)
 *
 *              SECTION: SQL Parsing, helper functions for parts of queries
 *  631:     function parseFieldList(&$parseString, $stopRegex='')
 *  749:     function parseFromTables(&$parseString, $stopRegex='')
 *  816:     function parseWhereClause(&$parseString, $stopRegex='')
 *  924:     function parseFieldDef(&$parseString, $stopRegex='')
 *
 *              SECTION: Parsing: Helper functions
 *  985:     function nextPart(&$parseString,$regex,$trimAll=FALSE)
 *  999:     function getValue(&$parseString,$comparator='')
 * 1054:     function getValueInQuotes(&$parseString,$quote)
 * 1079:     function parseStripslashes($str)
 * 1093:     function compileAddslashes($str)
 * 1107:     function parseError($msg,$restQuery)
 * 1121:     function trimSQL($str)
 *
 *              SECTION: Compiling queries
 * 1149:     function compileSQL($components)
 * 1187:     function compileSELECT($components)
 * 1218:     function compileUPDATE($components)
 * 1246:     function compileINSERT($components)
 * 1286:     function compileDELETE($components)
 * 1306:     function compileCREATETABLE($components)
 * 1337:     function compileALTERTABLE($components)
 *
 *              SECTION: Compiling queries, helper functions for parts of queries
 * 1390:     function compileFieldList($selectFields)
 * 1432:     function compileFromTables($tablesArray)
 * 1468:     function compileWhereClause($clauseArray)
 * 1522:     function compileFieldCfg($fieldCfg)
 *
 *              SECTION: Debugging
 * 1571:     function debug_parseSQLpart($part,$str)
 * 1593:     function debug_parseSQLpartCompare($str,$newStr,$caseInsensitive=FALSE)
 * 1626:     function debug_testSQL($SQLquery)
 *
 * TOTAL FUNCTIONS: 34
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */








/**
 * TYPO3 SQL parser class.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_sqlparser {

		// Parser:
	var $parse_error = '';						// Parsing error string
	var $lastStopKeyWord = '';					// Last stop keyword used.




	/*************************************
	 *
	 * SQL Parsing, full queries
	 *
	 **************************************/

	/**
	 * Parses any single SQL query
	 *
	 * @param	string		SQL query
	 * @return	array		Result array with all the parts in - or error message string
	 * @see compileSQL(), debug_testSQL()
	 */
	function parseSQL($parseString)	{
			// Prepare variables:
		$parseString = $this->trimSQL($parseString);
		$this->parse_error = '';
		$result = array();

			// Finding starting keyword of string:
		$_parseString = $parseString;	// Protecting original string...
		$keyword = $this->nextPart($_parseString, '^(SELECT|UPDATE|INSERT[[:space:]]+INTO|DELETE[[:space:]]+FROM|EXPLAIN|DROP[[:space:]]+TABLE|CREATE[[:space:]]+TABLE|CREATE[[:space:]]+DATABASE|ALTER[[:space:]]+TABLE)[[:space:]]+');
		$keyword = strtoupper(str_replace(array(' ',"\t","\r","\n"),'',$keyword));

		switch($keyword)	{
			case 'SELECT':
					// Parsing SELECT query:
				$result = $this->parseSELECT($parseString);
			break;
			case 'UPDATE':
					// Parsing UPDATE query:
				$result = $this->parseUPDATE($parseString);
			break;
			case 'INSERTINTO':
					// Parsing INSERT query:
				$result = $this->parseINSERT($parseString);
			break;
			case 'DELETEFROM':
					// Parsing DELETE query:
				$result = $this->parseDELETE($parseString);
			break;
			case 'EXPLAIN':
					// Parsing EXPLAIN SELECT query:
				$result = $this->parseEXPLAIN($parseString);
			break;
			case 'DROPTABLE':
					// Parsing DROP TABLE query:
				$result = $this->parseDROPTABLE($parseString);
			break;
			case 'ALTERTABLE':
					// Parsing ALTER TABLE query:
				$result = $this->parseALTERTABLE($parseString);
			break;
			case 'CREATETABLE':
					// Parsing CREATE TABLE query:
				$result = $this->parseCREATETABLE($parseString);
			break;
			case 'CREATEDATABASE':
					// Parsing CREATE DATABASE query:
				$result = $this->parseCREATEDATABASE($parseString);
			break;
			default:
				return $this->parseError('"'.$keyword.'" is not a keyword',$parseString);
			break;
		}

		return $result;
	}

	/**
	 * Parsing SELECT query
	 *
	 * @param	string		SQL string with SELECT query to parse
	 * @return	mixed		Returns array with components of SELECT query on success, otherwise an error message string.
	 * @see compileSELECT()
	 */
	function parseSELECT($parseString)	{

			// Removing SELECT:
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr($parseString,6)); // REMOVE eregi_replace('^SELECT[[:space:]]+','',$parseString);

			// Init output variable:
		$result = array();
		$result['type'] = 'SELECT';

			// Looking for STRAIGHT_JOIN keyword:
		$result['STRAIGHT_JOIN'] = $this->nextPart($parseString, '^(STRAIGHT_JOIN)[[:space:]]+');

			// Select fields:
		$result['SELECT'] = $this->parseFieldList($parseString, '^(FROM)[[:space:]]+');
		if ($this->parse_error)	{ return $this->parse_error; }

			// Continue if string is not ended:
		if ($parseString)	{

				// Get table list:
			$result['FROM'] = $this->parseFromTables($parseString, '^(WHERE)[[:space:]]+');
			if ($this->parse_error)	{ return $this->parse_error; }

				// If there are more than just the tables (a WHERE clause that would be...)
			if ($parseString)	{

					// Get WHERE clause:
				$result['WHERE'] = $this->parseWhereClause($parseString, '^(GROUP[[:space:]]+BY|ORDER[[:space:]]+BY|LIMIT)[[:space:]]+');
				if ($this->parse_error)	{ return $this->parse_error; }

					// If the WHERE clause parsing was stopped by GROUP BY, ORDER BY or LIMIT, then proceed with parsing:
				if ($this->lastStopKeyWord)	{

						// GROUP BY parsing:
					if ($this->lastStopKeyWord == 'GROUPBY')	{
						$result['GROUPBY'] = $this->parseFieldList($parseString, '^(ORDER[[:space:]]+BY|LIMIT)[[:space:]]+');
						if ($this->parse_error)	{ return $this->parse_error; }
					}

						// ORDER BY parsing:
					if ($this->lastStopKeyWord == 'ORDERBY')	{
						$result['ORDERBY'] = $this->parseFieldList($parseString, '^(LIMIT)[[:space:]]+');
						if ($this->parse_error)	{ return $this->parse_error; }
					}

						// LIMIT parsing:
					if ($this->lastStopKeyWord == 'LIMIT')	{
						if (preg_match('/^([0-9]+|[0-9]+[[:space:]]*,[[:space:]]*[0-9]+)$/',trim($parseString)))	{
							$result['LIMIT'] = $parseString;
						} else {
							return $this->parseError('No value for limit!',$parseString);
						}
					}
				}
			}
		} else return $this->parseError('No table to select from!',$parseString);

			// Return result:
		return $result;
	}

	/**
	 * Parsing UPDATE query
	 *
	 * @param	string		SQL string with UPDATE query to parse
	 * @return	mixed		Returns array with components of UPDATE query on success, otherwise an error message string.
	 * @see compileUPDATE()
	 */
	function parseUPDATE($parseString)	{

			// Removing UPDATE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr($parseString,6)); // REMOVE eregi_replace('^UPDATE[[:space:]]+','',$parseString);

			// Init output variable:
		$result = array();
		$result['type'] = 'UPDATE';

			// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');

			// Continue if string is not ended:
		if ($result['TABLE'])	{
			if ($parseString && $this->nextPart($parseString, '^(SET)[[:space:]]+'))	{

				$comma = TRUE;

					// Get field/value pairs:
				while($comma)	{
					if ($fieldName = $this->nextPart($parseString,'^([[:alnum:]_]+)[[:space:]]*='))	{
						$this->nextPart($parseString,'^(=)');	// Strip of "=" sign.
						$value = $this->getValue($parseString);
						$result['FIELDS'][$fieldName] = $value;
					} else return $this->parseError('No fieldname found',$parseString);

					$comma = $this->nextPart($parseString,'^(,)');
				}

					// WHERE
				if ($this->nextPart($parseString,'^(WHERE)'))	{
					$result['WHERE'] = $this->parseWhereClause($parseString);
					if ($this->parse_error)	{ return $this->parse_error; }
				}
			} else return $this->parseError('Query missing SET...',$parseString);
		} else return $this->parseError('No table found!',$parseString);

			// Should be no more content now:
		if ($parseString)	{
			return $this->parseError('Still content in clause after parsing!',$parseString);
		}

			// Return result:
		return $result;
	}

	/**
	 * Parsing INSERT query
	 *
	 * @param	string		SQL string with INSERT query to parse
	 * @return	mixed		Returns array with components of INSERT query on success, otherwise an error message string.
	 * @see compileINSERT()
	 */
	function parseINSERT($parseString)	{

			// Removing INSERT
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString,6)),4)); // REMOVE eregi_replace('^INSERT[[:space:]]+INTO[[:space:]]+','',$parseString);

			// Init output variable:
		$result = array();
		$result['type'] = 'INSERT';

			// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\()');

		if ($result['TABLE'])	{

			if ($this->nextPart($parseString,'^(VALUES)[[:space:]]+'))	{	// In this case there are no field names mentioned in the SQL!
					// Get values/fieldnames (depending...)
				$result['VALUES_ONLY'] = $this->getValue($parseString,'IN');
				if ($this->parse_error)	{ return $this->parse_error; }
			} else {	// There are apparently fieldnames listed:
				$fieldNames = $this->getValue($parseString,'_LIST');
				if ($this->parse_error)	{ return $this->parse_error; }

				if ($this->nextPart($parseString,'^(VALUES)[[:space:]]+'))	{	// "VALUES" keyword binds the fieldnames to values:

					$values = $this->getValue($parseString,'IN');	// Using the "getValue" function to get the field list...
					if ($this->parse_error)	{ return $this->parse_error; }

					foreach($fieldNames as $k => $fN)	{
						if (preg_match('/^[[:alnum:]_]+$/',$fN))	{
							if (isset($values[$k]))	{
								if (!isset($result['FIELDS'][$fN]))	{
									$result['FIELDS'][$fN] = $values[$k];
								} else return $this->parseError('Fieldname ("'.$fN.'") already found in list!',$parseString);
							} else return $this->parseError('No value set!',$parseString);
						} else return $this->parseError('Invalid fieldname ("'.$fN.'")',$parseString);
					}
					if (isset($values[$k+1]))	{
						return $this->parseError('Too many values in list!',$parseString);
					}
				} else return $this->parseError('VALUES keyword expected',$parseString);
			}
		}  else return $this->parseError('No table found!',$parseString);

			// Should be no more content now:
		if ($parseString)	{
			return $this->parseError('Still content after parsing!',$parseString);
		}

			// Return result
		return $result;
	}

	/**
	 * Parsing DELETE query
	 *
	 * @param	string		SQL string with DELETE query to parse
	 * @return	mixed		Returns array with components of DELETE query on success, otherwise an error message string.
	 * @see compileDELETE()
	 */
	function parseDELETE($parseString)	{

			// Removing DELETE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString,6)),4)); // REMOVE eregi_replace('^DELETE[[:space:]]+FROM[[:space:]]+','',$parseString);

			// Init output variable:
		$result = array();
		$result['type'] = 'DELETE';

			// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');

		if ($result['TABLE'])	{

				// WHERE
			if ($this->nextPart($parseString,'^(WHERE)'))	{
				$result['WHERE'] = $this->parseWhereClause($parseString);
				if ($this->parse_error)	{ return $this->parse_error; }
			}
		} else return $this->parseError('No table found!',$parseString);

			// Should be no more content now:
		if ($parseString)	{
			return $this->parseError('Still content in clause after parsing!',$parseString);
		}

			// Return result:
		return $result;
	}

	/**
	 * Parsing EXPLAIN query
	 *
	 * @param	string		SQL string with EXPLAIN query to parse
	 * @return	mixed		Returns array with components of EXPLAIN query on success, otherwise an error message string.
	 * @see parseSELECT()
	 */
	function parseEXPLAIN($parseString)	{

			// Removing EXPLAIN
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr($parseString,6)); // REMOVE eregi_replace('^EXPLAIN[[:space:]]+','',$parseString);

			// Init output variable:
		$result = $this->parseSELECT($parseString);
		if (is_array($result))	{
			$result['type'] = 'EXPLAIN';
		}

		return $result;
	}

	/**
	 * Parsing CREATE TABLE query
	 *
	 * @param	string		SQL string starting with CREATE TABLE
	 * @return	mixed		Returns array with components of CREATE TABLE query on success, otherwise an error message string.
	 * @see compileCREATETABLE()
	 */
	function parseCREATETABLE($parseString)	{

			// Removing CREATE TABLE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString,6)),5)); // REMOVE eregi_replace('^CREATE[[:space:]]+TABLE[[:space:]]+','',$parseString);

			// Init output variable:
		$result = array();
		$result['type'] = 'CREATETABLE';

			// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]*\(',TRUE);

		if ($result['TABLE'])	{

				// While the parseString is not yet empty:
			while(strlen($parseString)>0)	{
				if ($key = $this->nextPart($parseString, '^(KEY|PRIMARY KEY)([[:space:]]+|\()'))	{	// Getting key
					$key = strtoupper(str_replace(array(' ',"\t","\r","\n"),'',$key));

					switch($key)	{
						case 'PRIMARYKEY':
							$result['KEYS'][$key] = $this->getValue($parseString,'_LIST');
							if ($this->parse_error)	{ return $this->parse_error; }
						break;
						case 'KEY':
							if ($keyName = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\()'))	{
								$result['KEYS'][$keyName] = $this->getValue($parseString,'_LIST');
								if ($this->parse_error)	{ return $this->parse_error; }
							} else return $this->parseError('No keyname found',$parseString);
						break;
					}
				} elseif ($fieldName = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+'))	{	// Getting field:
					$result['FIELDS'][$fieldName]['definition'] = $this->parseFieldDef($parseString);
					if ($this->parse_error)	{ return $this->parse_error; }
				}

					// Finding delimiter:
				$delim = $this->nextPart($parseString, '^(,|\))');
				if (!$delim)	{
					return $this->parseError('No delimiter found',$parseString);
				} elseif ($delim==')')	{
					break;
				}
			}

				// Finding what is after the table definition - table type in MySQL
			if ($delim==')')	{
				if ($this->nextPart($parseString, '^(TYPE[[:space:]]*=)'))	{
					$result['tableType'] = $parseString;
					$parseString = '';
				}
			} else return $this->parseError('No fieldname found!',$parseString);

				// Getting table type
		} else return $this->parseError('No table found!',$parseString);

			// Should be no more content now:
		if ($parseString)	{
			return $this->parseError('Still content in clause after parsing!',$parseString);
		}

		return $result;
	}

	/**
	 * Parsing ALTER TABLE query
	 *
	 * @param	string		SQL string starting with ALTER TABLE
	 * @return	mixed		Returns array with components of ALTER TABLE query on success, otherwise an error message string.
	 * @see compileALTERTABLE()
	 */
	function parseALTERTABLE($parseString)	{

			// Removing ALTER TABLE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString,5)),5)); // REMOVE eregi_replace('^ALTER[[:space:]]+TABLE[[:space:]]+','',$parseString);

			// Init output variable:
		$result = array();
		$result['type'] = 'ALTERTABLE';

			// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');

		if ($result['TABLE'])	{
			if ($result['action'] = $this->nextPart($parseString, '^(CHANGE|DROP[[:space:]]+KEY|DROP[[:space:]]+PRIMARY[[:space:]]+KEY|ADD[[:space:]]+KEY|ADD[[:space:]]+PRIMARY[[:space:]]+KEY|DROP|ADD|RENAME)([[:space:]]+|\()'))	{
				$actionKey = strtoupper(str_replace(array(' ',"\t","\r","\n"),'',$result['action']));

					// Getting field:
				if (t3lib_div::inList('ADDPRIMARYKEY,DROPPRIMARYKEY',$actionKey) || $fieldKey = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+'))	{

					switch($actionKey)	{
						case 'ADD':
							$result['FIELD'] = $fieldKey;
							$result['definition'] = $this->parseFieldDef($parseString);
							if ($this->parse_error)	{ return $this->parse_error; }
						break;
						case 'DROP':
						case 'RENAME':
							$result['FIELD'] = $fieldKey;
						break;
						case 'CHANGE':
							$result['FIELD'] = $fieldKey;
							if ($result['newField'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+'))	{
								$result['definition'] = $this->parseFieldDef($parseString);
								if ($this->parse_error)	{ return $this->parse_error; }
							} else return $this->parseError('No NEW field name found',$parseString);
						break;

						case 'ADDKEY':
						case 'ADDPRIMARYKEY':
							$result['KEY'] = $fieldKey;
							$result['fields'] = $this->getValue($parseString,'_LIST');
							if ($this->parse_error)	{ return $this->parse_error; }
						break;
						case 'DROPKEY':
							$result['KEY'] = $fieldKey;
						break;
						case 'DROPPRIMARYKEY':
							// ??? todo!
						break;
					}
				} else return $this->parseError('No field name found',$parseString);
			} else return $this->parseError('No action CHANGE, DROP or ADD found!',$parseString);
		} else return $this->parseError('No table found!',$parseString);

			// Should be no more content now:
		if ($parseString)	{
			return $this->parseError('Still content in clause after parsing!',$parseString);
		}

		return $result;
	}

	/**
	 * Parsing DROP TABLE query
	 *
	 * @param	string		SQL string starting with DROP TABLE
	 * @return	mixed		Returns array with components of DROP TABLE query on success, otherwise an error message string.
	 */
	function parseDROPTABLE($parseString)	{

			// Removing DROP TABLE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString,4)),5)); // eregi_replace('^DROP[[:space:]]+TABLE[[:space:]]+','',$parseString);

			// Init output variable:
		$result = array();
		$result['type'] = 'DROPTABLE';

			// IF EXISTS
		$result['ifExists']	= $this->nextPart($parseString, '^(IF[[:space:]]+EXISTS[[:space:]]+)');

			// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');

		if ($result['TABLE'])	{

				// Should be no more content now:
			if ($parseString)	{
				return $this->parseError('Still content in clause after parsing!',$parseString);
			}

			return $result;
		} else return $this->parseError('No table found!',$parseString);
	}

	/**
	 * Parsing CREATE DATABASE query
	 *
	 * @param	string		SQL string starting with CREATE DATABASE
	 * @return	mixed		Returns array with components of CREATE DATABASE query on success, otherwise an error message string.
	 */
	function parseCREATEDATABASE($parseString)	{

			// Removing CREATE DATABASE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString,6)),8)); // eregi_replace('^CREATE[[:space:]]+DATABASE[[:space:]]+','',$parseString);

			// Init output variable:
		$result = array();
		$result['type'] = 'CREATEDATABASE';

			// Get table:
		$result['DATABASE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');

		if ($result['DATABASE'])	{

				// Should be no more content now:
			if ($parseString)	{
				return $this->parseError('Still content in clause after parsing!',$parseString);
			}

			return $result;
		} else return $this->parseError('No database found!',$parseString);
	}















	/**************************************
	 *
	 * SQL Parsing, helper functions for parts of queries
	 *
	 **************************************/

	/**
	 * Parsing the fields in the "SELECT [$selectFields] FROM" part of a query into an array.
	 * The output from this function can be compiled back into a field list with ->compileFieldList()
	 * Will detect the keywords "DESC" and "ASC" after the table name; thus is can be used for parsing the more simply ORDER BY and GROUP BY field lists as well!
	 *
	 * @param	string		The string with fieldnames, eg. "title, uid AS myUid, max(tstamp), count(*)" etc. NOTICE: passed by reference!
	 * @param	string		Regular expressing to STOP parsing, eg. '^(FROM)([[:space:]]*)'
	 * @return	array		If successful parsing, returns an array, otherwise an error string.
	 * @see compileFieldList()
	 */
	function parseFieldList(&$parseString, $stopRegex='')	{

			// Prepare variables:
		$parseString = $this->trimSQL($parseString);
		$this->lastStopKeyWord = '';
		$this->parse_error = '';


		$stack = array();	// Contains the parsed content
		$pnt = 0;			// Pointer to positions in $stack
		$level = 0;			// Indicates the parenthesis level we are at.
		$loopExit = 0;		// Recursivity brake.

			// $parseString is continously shortend by the process and we keep parsing it till it is zero:
		while (strlen($parseString)) {

				// Checking if we are inside / outside parenthesis (in case of a function like count(), max(), min() etc...):
			if ($level>0)	{	// Inside parenthesis here (does NOT detect if values in quotes are used, the only token is ")" or "("):

					// Accumulate function content until next () parenthesis:
				$funcContent = $this->nextPart($parseString,'^([^()]*.)');
				$stack[$pnt]['func_content.'][] = array(
					'level' => $level,
					'func_content' => substr($funcContent,0,-1)
				);
				$stack[$pnt]['func_content'].= $funcContent;

					// Detecting ( or )
				switch(substr($stack[$pnt]['func_content'],-1))	{
					case '(':
						$level++;
					break;
					case ')':
						$level--;
						if (!$level)	{	// If this was the last parenthesis:
							$stack[$pnt]['func_content'] = substr($stack[$pnt]['func_content'],0,-1);
							$parseString = ltrim($parseString);	// Remove any whitespace after the parenthesis.
						}
					break;
				}
			} else {	// Outside parenthesis, looking for next field:

					// Looking for a known function (only known functions supported)
				$func = $this->nextPart($parseString,'^(count|max|min|floor|sum|avg)[[:space:]]*\(');
				if ($func)	{
					$parseString = trim(substr($parseString,1));	// Strip of "("
					$stack[$pnt]['type'] = 'function';
					$stack[$pnt]['function'] = $func;
					$level++;	// increse parenthesis level counter.
				} else {
						// Otherwise, look for regular fieldname:
					if ($fieldName = $this->nextPart($parseString,'^([[:alnum:]\*._]+)(,|[[:space:]]+)'))	{
						$stack[$pnt]['type'] = 'field';

							// Explode fieldname into field and table:
						$tableField = explode('.',$fieldName,2);
						if (count($tableField)==2)	{
							$stack[$pnt]['table'] = $tableField[0];
							$stack[$pnt]['field'] = $tableField[1];
						} else {
							$stack[$pnt]['table'] = '';
							$stack[$pnt]['field'] = $tableField[0];
						}
					} else {
						return $this->parseError('No field name found as expected',$parseString);
					}
				}
			}

				// After a function or field we look for "AS" alias and a comma to separate to the next field in the list:
			if (!$level)	{

					// Looking for "AS" alias:
				if ($as = $this->nextPart($parseString,'^(AS)[[:space:]]+'))	{
					$stack[$pnt]['as'] = $this->nextPart($parseString,'^([[:alnum:]_]+)(,|[[:space:]]+)');
					$stack[$pnt]['as_keyword'] = $as;
				}

					// Looking for "ASC" or "DESC" keywords (for ORDER BY)
				if ($sDir = $this->nextPart($parseString,'^(ASC|DESC)([[:space:]]+|,)'))	{
					$stack[$pnt]['sortDir'] = $sDir;
				}

					// Looking for stop-keywords:
				if ($stopRegex && $this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))	{
					$this->lastStopKeyWord = strtoupper(str_replace(array(' ',"\t","\r","\n"),'',$this->lastStopKeyWord));
					return $stack;
				}

					// Looking for comma (since the stop-keyword did not trigger a return...)
				if (strlen($parseString) && !$this->nextPart($parseString,'^(,)'))	{
					return $this->parseError('No comma found as expected',$parseString);
				}

					// Increasing pointer:
				$pnt++;
			}

				// Check recursivity brake:
			$loopExit++;
			if ($loopExit>500)	{
				return $this->parseError('More than 500 loops, exiting prematurely...',$parseString);
			}
		}

			// Return result array:
		return $stack;
	}

	/**
	 * Parsing the tablenames in the "FROM [$parseString] WHERE" part of a query into an array.
	 * The success of this parsing determines if that part of the query is supported by TYPO3.
	 *
	 * @param	string		list of tables, eg. "pages, tt_content" or "pages A, pages B". NOTICE: passed by reference!
	 * @param	string		Regular expressing to STOP parsing, eg. '^(WHERE)([[:space:]]*)'
	 * @return	array		If successful parsing, returns an array, otherwise an error string.
	 * @see compileFromTables()
	 */
	function parseFromTables(&$parseString, $stopRegex='')	{

			// Prepare variables:
		$parseString = $this->trimSQL($parseString);
		$this->lastStopKeyWord = '';
		$this->parse_error = '';

		$stack = array();	// Contains the parsed content
		$pnt = 0;			// Pointer to positions in $stack
		$loopExit = 0;		// Recursivity brake.

			// $parseString is continously shortend by the process and we keep parsing it till it is zero:
		while (strlen($parseString)) {
				// Looking for the table:
			if ($stack[$pnt]['table'] = $this->nextPart($parseString,'^([[:alnum:]_]+)(,|[[:space:]]+)'))	{
			    $stack[$pnt]['as'] = $this->nextPart($parseString,'^([[:alnum:]_]+)[[:space:]]*');
			} else return $this->parseError('No table name found as expected!',$parseString);

				// Looking for JOIN
			if ($join = $this->nextPart($parseString,'^(JOIN|LEFT[[:space:]]+JOIN)[[:space:]]+'))	{
				$stack[$pnt]['JOIN']['type'] = $join;
				if ($stack[$pnt]['JOIN']['withTable'] = $this->nextPart($parseString,'^([[:alnum:]_]+)[[:space:]]+ON[[:space:]]+',1))	{
					$field1 = $this->nextPart($parseString,'^([[:alnum:]_.]+)[[:space:]]*=[[:space:]]*',1);
					$field2 = $this->nextPart($parseString,'^([[:alnum:]_.]+)[[:space:]]+');
					if ($field1 && $field2)	{
						$stack[$pnt]['JOIN']['ON'] = array($field1,$field2);
					} else return $this->parseError('No join fields found!',$parseString);
				} else  return $this->parseError('No join table found!',$parseString);
			}

				// Looking for stop-keywords:
			if ($stopRegex && $this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))	{
				$this->lastStopKeyWord = strtoupper(str_replace(array(' ',"\t","\r","\n"),'',$this->lastStopKeyWord));
				return $stack;
			}

				// Looking for comma:
			if (strlen($parseString) && !$this->nextPart($parseString,'^(,)'))	{
				return $this->parseError('No comma found as expected',$parseString);
			}

				// Increasing pointer:
			$pnt++;

				// Check recursivity brake:
			$loopExit++;
			if ($loopExit>500)	{
				return $this->parseError('More than 500 loops, exiting prematurely...',$parseString);
			}
		}

			// Return result array:
		return $stack;
	}

	/**
	 * Parsing the WHERE clause fields in the "WHERE [$parseString] ..." part of a query into a multidimensional array.
	 * The success of this parsing determines if that part of the query is supported by TYPO3.
	 *
	 * @param	string		WHERE clause to parse. NOTICE: passed by reference!
	 * @param	string		Regular expressing to STOP parsing, eg. '^(GROUP BY|ORDER BY|LIMIT)([[:space:]]*)'
	 * @return	mixed		If successful parsing, returns an array, otherwise an error string.
	 */
	function parseWhereClause(&$parseString, $stopRegex='')	{

			// Prepare variables:
		$parseString = $this->trimSQL($parseString);
		$this->lastStopKeyWord = '';
		$this->parse_error = '';

		$stack = array(0 => array());	// Contains the parsed content
		$pnt = array(0 => 0);			// Pointer to positions in $stack
		$level = 0;						// Determines parenthesis level
		$loopExit = 0;					// Recursivity brake.

			// $parseString is continously shortend by the process and we keep parsing it till it is zero:
		while (strlen($parseString)) {

				// Look for next parenthesis level:
			$newLevel = $this->nextPart($parseString,'^([(])');
			if ($newLevel=='(')	{			// If new level is started, manage stack/pointers:
				$level++;					// Increase level
				$pnt[$level] = 0;			// Reset pointer for this level
				$stack[$level] = array();	// Reset stack for this level
			} else {	// If no new level is started, just parse the current level:

					// Find "modifyer", eg. "NOT or !"
				$stack[$level][$pnt[$level]]['modifier'] = trim($this->nextPart($parseString,'^(!|NOT[[:space:]]+)'));

					// Fieldname:
				if ($fieldName = $this->nextPart($parseString,'^([[:alnum:]._]+)([[:space:]]+|&|<=|>=|<|>|=|!=|IS)'))	{

						// Parse field name into field and table:
					$tableField = explode('.',$fieldName,2);
					if (count($tableField)==2)	{
						$stack[$level][$pnt[$level]]['table'] = $tableField[0];
						$stack[$level][$pnt[$level]]['field'] = $tableField[1];
					} else {
						$stack[$level][$pnt[$level]]['table'] = '';
						$stack[$level][$pnt[$level]]['field'] = $tableField[0];
					}
				} else {
					return $this->parseError('No field name found as expected',$parseString);
				}

					// See if the value is calculated. Support only for "&" (boolean AND) at the moment:
				$stack[$level][$pnt[$level]]['calc'] = $this->nextPart($parseString,'^(&)');
				if (strlen($stack[$level][$pnt[$level]]['calc']))	{
						// Finding value for calculation:
					$stack[$level][$pnt[$level]]['calc_value'] = $this->getValue($parseString);
				}

					// Find "comparator":
				$stack[$level][$pnt[$level]]['comparator'] = $this->nextPart($parseString,'^(<=|>=|<|>|=|!=|NOT[[:space:]]+IN|IN|NOT[[:space:]]+LIKE|LIKE|IS)');
				if (strlen($stack[$level][$pnt[$level]]['comparator']))	{
						// Finding value for comparator:
					$stack[$level][$pnt[$level]]['value'] = $this->getValue($parseString,$stack[$level][$pnt[$level]]['comparator']);
					if ($this->parse_error)	{ return $this->parse_error; }
				}

					// Finished, increase pointer:
				$pnt[$level]++;

					// Checking if the current level is ended, in that case do stack management:
				while ($this->nextPart($parseString,'^([)])'))	{
					$level--;		// Decrease level:
					$stack[$level][$pnt[$level]]['sub'] = $stack[$level+1];		// Copy stack
					$pnt[$level]++;	// Increase pointer of the new level

						// Make recursivity check:
					$loopExit++;
					if ($loopExit>500)	{
						return $this->parseError('More than 500 loops (in search for exit parenthesis), exiting prematurely...',$parseString);
					}
				}

					// Detecting the operator for the next level; support for AND, OR and &&):
				$op = $this->nextPart($parseString,'^(AND|OR|AND[[:space:]]+NOT)(\(|[[:space:]]+)');
				if ($op)	{
					$stack[$level][$pnt[$level]]['operator'] = $op;
				} elseif (strlen($parseString))	{

						// Looking for stop-keywords:
					if ($stopRegex && $this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))	{
						$this->lastStopKeyWord = strtoupper(str_replace(array(' ',"\t","\r","\n"),'',$this->lastStopKeyWord));
						return $stack[0];
					} else {
						return $this->parseError('No operator, but parsing not finished.',$parseString);
					}
				}
			}

				// Make recursivity check:
			$loopExit++;
			if ($loopExit>500)	{
				return $this->parseError('More than 500 loops, exiting prematurely...',$parseString);
			}
		}

			// Return the stacks lowest level:
		return $stack[0];
	}

	/**
	 * Parsing the WHERE clause fields in the "WHERE [$parseString] ..." part of a query into a multidimensional array.
	 * The success of this parsing determines if that part of the query is supported by TYPO3.
	 *
	 * @param	string		WHERE clause to parse. NOTICE: passed by reference!
	 * @param	string		Regular expressing to STOP parsing, eg. '^(GROUP BY|ORDER BY|LIMIT)([[:space:]]*)'
	 * @return	mixed		If successful parsing, returns an array, otherwise an error string.
	 */
	function parseFieldDef(&$parseString, $stopRegex='')	{
			// Prepare variables:
		$parseString = $this->trimSQL($parseString);
		$this->lastStopKeyWord = '';
		$this->parse_error = '';

		$result = array();

			// Field type:
		if ($result['fieldType'] =  $this->nextPart($parseString,'^(int|smallint|tinyint|mediumint|double|varchar|char|text|tinytext|mediumtext|blob|tinyblob|mediumblob|longblob)([[:space:]]+|\()'))	{

				// Looking for value:
			if (substr($parseString,0,1)=='(')	{
				$parseString = substr($parseString,1);
				if ($result['value'] =  $this->nextPart($parseString,'^([^)]*)'))	{
					$parseString = ltrim(substr($parseString,1));
				} else return $this->parseError('No end-parenthesis for value found!',$parseString);
			}

				// Looking for keywords
			while($keyword = $this->nextPart($parseString,'^(DEFAULT|NOT[[:space:]]+NULL|AUTO_INCREMENT|UNSIGNED)([[:space:]]+|,|\))'))	{
				$keywordCmp = strtoupper(str_replace(array(' ',"\t","\r","\n"),'',$keyword));

				$result['featureIndex'][$keywordCmp]['keyword'] = $keyword;

				switch($keywordCmp)	{
					case 'DEFAULT':
						$result['featureIndex'][$keywordCmp]['value'] = $this->getValue($parseString);
					break;
				}
			}
		} else return $this->parseError('Field type unknown!',$parseString);

		return $result;
	}











	/************************************
	 *
	 * Parsing: Helper functions
	 *
	 ************************************/

	/**
	 * Strips of a part of the parseString and returns the matching part.
	 * Helper function for the parsing methods.
	 *
	 * @param	string		Parse string; if $regex finds anything the value of the first () level will be stripped of the string in the beginning. Further $parseString is left-trimmed (on success). Notice; parsestring is passed by reference.
	 * @param	string		Regex to find a matching part in the beginning of the string. Rules: You MUST start the regex with "^" (finding stuff in the beginning of string) and the result of the first parenthesis is what will be returned to you (and stripped of the string). Eg. '^(AND|OR|&&)[[:space:]]+' will return AND, OR or && if found and having one of more whitespaces after it, plus shorten $parseString with that match and any space after (by ltrim())
	 * @param	boolean		If set the full match of the regex is stripped of the beginning of the string!
	 * @return	string		The value of the first parenthesis level of the REGEX.
	 */
	function nextPart(&$parseString,$regex,$trimAll=FALSE)	{
		//if (eregi($regex,$parseString.' ', $reg))	{	// Adding space char because [[:space:]]+ is often a requirement in regex's
		if (preg_match('/'.$regex.'/i',$parseString.' ', $reg))	{	// Adding space char because [[:space:]]+ is often a requirement in regex's
			$parseString = ltrim(substr($parseString,strlen($reg[$trimAll?0:1])));
			return $reg[1];
		}
	}

	/**
	 * Finds value in beginning of $parseString, returns result and strips it of parseString
	 *
	 * @param	string		The parseString, eg. "(0,1,2,3) ..." or "('asdf','qwer') ..." or "1234 ..." or "'My string value here' ..."
	 * @param	string		The comparator used before. If "NOT IN" or "IN" then the value is expected to be a list of values. Otherwise just an integer (un-quoted) or string (quoted)
	 * @return	string		The value (string/integer). Otherwise an array with error message in first key (0)
	 */
	function getValue(&$parseString,$comparator='')	{
		//if (t3lib_div::inList('NOTIN,IN,_LIST',strtoupper(ereg_replace('[[:space:]]','',$comparator))))	{	// List of values:
		if (t3lib_div::inList('NOTIN,IN,_LIST',strtoupper(str_replace(array(' ',"\n","\r","\t"),'',$comparator))))	{	// List of values:
			if ($this->nextPart($parseString,'^([(])'))	{
				$listValues = array();
				$comma=',';

				while($comma==',')	{
					$listValues[] = $this->getValue($parseString);
					$comma = $this->nextPart($parseString,'^([,])');
				}

				$out = $this->nextPart($parseString,'^([)])');
				if ($out)	{
					if ($comparator=='_LIST')	{
						$kVals = array();
						foreach ($listValues as $vArr)	{
							$kVals[] = $vArr[0];
						}
						return $kVals;
					} else {
						return $listValues;
					}
				} else return array($this->parseError('No ) parenthesis in list',$parseString));
			} else return array($this->parseError('No ( parenthesis starting the list',$parseString));

		} else {	// Just plain string value, in quotes or not:

				// Quote?
			$firstChar = substr($parseString,0,1);

			switch($firstChar)	{
				case '"':
					return array($this->getValueInQuotes($parseString,'"'),'"');
				break;
				case "'":
					return array($this->getValueInQuotes($parseString,"'"),"'");
				break;
				default:
					if (preg_match('/^([[:alnum:]._-]+)/i',$parseString, $reg))	{
						$parseString = ltrim(substr($parseString,strlen($reg[0])));
						return array($reg[1]);
					}
				break;
			}
		}
	}

	/**
	 * Get value in quotes from $parseString.
	 * NOTICE: If a query being parsed was prepared for another database than MySQL this function should probably be changed
	 *
	 * @param	string		String from which to find value in quotes. Notice that $parseString is passed by reference and is shortend by the output of this function.
	 * @param	string		The quote used; input either " or '
	 * @return	string		The value, passed through stripslashes() !
	 */
	function getValueInQuotes(&$parseString,$quote)	{

		$parts = explode($quote,substr($parseString,1));
		$buffer = '';
		foreach($parts as $k => $v)	{
			$buffer.=$v;

			unset($reg);
			//preg_match('/[\]*$/',$v,$reg); // does not work. what is the *exact* meaning of the next line?
			ereg('[\]*$',$v,$reg);
			if (strlen($reg[0])%2)	{
				$buffer.=$quote;
			} else {
				$parseString = ltrim(substr($parseString,strlen($buffer)+2));
				return $this->parseStripslashes($buffer);
			}
		}
	}

	/**
	 * Strip slashes function used for parsing
	 * NOTICE: If a query being parsed was prepared for another database than MySQL this function should probably be changed
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function parseStripslashes($str)	{
		$search = array('\\\\', '\\\'', '\\"', '\0', '\n', '\r', '\Z');
		$replace = array('\\', '\'', '"', "\x00", "\x0a", "\x0d", "\x1a");

		return str_replace($search, $replace, $str);
	}

	/**
	 * Add slashes function used for compiling queries
	 * NOTICE: If a query being parsed was prepared for another database than MySQL this function should probably be changed
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function compileAddslashes($str)	{
return $str;
		$search = array('\\', '\'', '"', "\x00", "\x0a", "\x0d", "\x1a");
		$replace = array('\\\\', '\\\'', '\\"', '\0', '\n', '\r', '\Z');

		return str_replace($search, $replace, $str);
	}

	/**
	 * Setting the internal error message value, $this->parse_error and returns that value.
	 *
	 * @param	string		Input error message
	 * @param	string		Remaining query to parse.
	 * @return	string		Error message.
	 */
	function parseError($msg,$restQuery)	{
		$this->parse_error = 'SQL engine parse ERROR: '.$msg.': near "'.substr($restQuery,0,50).'"';
		return $this->parse_error;
	}

	/**
	 * Trimming SQL as preparation for parsing.
	 * ";" in the end is stripped of.
	 * White space is trimmed away around the value
	 * A single space-char is added in the end
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function trimSQL($str)	{
		return trim(rtrim($str, "; \r\n\t")).' ';
		//return trim(ereg_replace('[[:space:];]*$','',$str)).' ';
	}












	/*************************
	 *
	 * Compiling queries
	 *
	 *************************/

	/**
	 * Compiles an SQL query from components
	 *
	 * @param	array		Array of SQL query components
	 * @return	string		SQL query
	 * @see parseSQL()
	 */
	function compileSQL($components)	{
		switch($components['type'])	{
			case 'SELECT':
				$query = $this->compileSELECT($components);
			break;
			case 'UPDATE':
				$query = $this->compileUPDATE($components);
			break;
			case 'INSERT':
				$query = $this->compileINSERT($components);
			break;
			case 'DELETE':
				$query = $this->compileDELETE($components);
			break;
			case 'EXPLAIN':
				$query = 'EXPLAIN '.$this->compileSELECT($components);
			break;
			case 'DROPTABLE':
				$query = 'DROP TABLE'.($components['ifExists']?' IF EXISTS':'').' '.$components['TABLE'];
			break;
			case 'CREATETABLE':
				$query = $this->compileCREATETABLE($components);
			break;
			case 'ALTERTABLE':
				$query = $this->compileALTERTABLE($components);
			break;
		}

		return $query;
	}

	/**
	 * Compiles a SELECT statement from components array
	 *
	 * @param	array		Array of SQL query components
	 * @return	string		SQL SELECT query
	 * @see parseSELECT()
	 */
	function compileSELECT($components)	{

			// Initialize:
		$where = $this->compileWhereClause($components['WHERE']);
		$groupBy = $this->compileFieldList($components['GROUPBY']);
		$orderBy = $this->compileFieldList($components['ORDERBY']);
		$limit = $components['LIMIT'];

			// Make query:
		$query = 'SELECT '.($components['STRAIGHT_JOIN'] ? $components['STRAIGHT_JOIN'].'' : '').'
				'.$this->compileFieldList($components['SELECT']).'
				FROM '.$this->compileFromTables($components['FROM']).
					(strlen($where)?'
				WHERE '.$where : '').
					(strlen($groupBy)?'
				GROUP BY '.$groupBy : '').
					(strlen($orderBy)?'
				ORDER BY '.$orderBy : '').
					(strlen($limit)?'
				LIMIT '.$limit : '');

		return $query;
	}

	/**
	 * Compiles an UPDATE statement from components array
	 *
	 * @param	array		Array of SQL query components
	 * @return	string		SQL UPDATE query
	 * @see parseUPDATE()
	 */
	function compileUPDATE($components)	{

			// Where clause:
		$where = $this->compileWhereClause($components['WHERE']);

			// Fields
		$fields = array();
		foreach($components['FIELDS'] as $fN => $fV)	{
			$fields[]=$fN.'='.$fV[1].$this->compileAddslashes($fV[0]).$fV[1];
		}

			// Make query:
		$query = 'UPDATE '.$components['TABLE'].' SET
				'.implode(',
				',$fields).'
				'.(strlen($where)?'
				WHERE '.$where : '');

		return $query;
	}

	/**
	 * Compiles an INSERT statement from components array
	 *
	 * @param	array		Array of SQL query components
	 * @return	string		SQL INSERT query
	 * @see parseINSERT()
	 */
	function compileINSERT($components)	{

		if ($components['VALUES_ONLY'])	{
				// Initialize:
			$fields = array();
			foreach($components['VALUES_ONLY'] as $fV)	{
				$fields[]=$fV[1].$this->compileAddslashes($fV[0]).$fV[1];
			}

				// Make query:
			$query = 'INSERT INTO '.$components['TABLE'].'
					VALUES
					('.implode(',
					',$fields).')';
		} else {
				// Initialize:
			$fields = array();
			foreach($components['FIELDS'] as $fN => $fV)	{
				$fields[$fN]=$fV[1].$this->compileAddslashes($fV[0]).$fV[1];
			}

				// Make query:
			$query = 'INSERT INTO '.$components['TABLE'].'
					('.implode(',
					',array_keys($fields)).')
					VALUES
					('.implode(',
					',$fields).')';
		}

		return $query;
	}

	/**
	 * Compiles an DELETE statement from components array
	 *
	 * @param	array		Array of SQL query components
	 * @return	string		SQL DELETE query
	 * @see parseDELETE()
	 */
	function compileDELETE($components)	{

			// Where clause:
		$where = $this->compileWhereClause($components['WHERE']);

			// Make query:
		$query = 'DELETE FROM '.$components['TABLE'].
				(strlen($where)?'
				WHERE '.$where : '');

		return $query;
	}

	/**
	 * Compiles a CREATE TABLE statement from components array
	 *
	 * @param	array		Array of SQL query components
	 * @return	string		SQL CREATE TABLE query
	 * @see parseCREATETABLE()
	 */
	function compileCREATETABLE($components)	{

			// Create fields and keys:
		$fieldsKeys = array();
		foreach($components['FIELDS'] as $fN => $fCfg)	{
			$fieldsKeys[]=$fN.' '.$this->compileFieldCfg($fCfg['definition']);
		}
		foreach($components['KEYS'] as $kN => $kCfg)	{
			if ($kN == 'PRIMARYKEY')	{
				$fieldsKeys[]='PRIMARY KEY ('.implode(',', $kCfg).')';
			} else {
				$fieldsKeys[]='KEY '.$kN.' ('.implode(',', $kCfg).')';
			}
		}

			// Make query:
		$query = 'CREATE TABLE '.$components['TABLE'].' (
			'.implode(',
			', $fieldsKeys).'
			)'.($components['tableType'] ? ' TYPE='.$components['tableType'] : '');

		return $query;
	}

	/**
	 * Compiles an ALTER TABLE statement from components array
	 *
	 * @param	array		Array of SQL query components
	 * @return	string		SQL ALTER TABLE query
	 * @see parseALTERTABLE()
	 */
	function compileALTERTABLE($components)	{

			// Make query:
		$query = 'ALTER TABLE '.$components['TABLE'].' '.$components['action'].' '.($components['FIELD']?$components['FIELD']:$components['KEY']);

			// Based on action, add the final part:
		switch(strtoupper(str_replace(array(' ',"\t","\r","\n"),'',$components['action'])))	{
			case 'ADD':
				$query.=' '.$this->compileFieldCfg($components['definition']);
			break;
			case 'CHANGE':
				$query.=' '.$components['newField'].' '.$this->compileFieldCfg($components['definition']);
			break;
			case 'DROP':
			case 'DROPKEY':
			break;
			case 'ADDKEY':
			case 'ADDPRIMARYKEY':
				$query.=' ('.implode(',',$components['fields']).')';
			break;
		}

			// Return query
		return $query;
	}














	/**************************************
	 *
	 * Compiling queries, helper functions for parts of queries
	 *
	 **************************************/

	/**
	 * Compiles a "SELECT [output] FROM..:" field list based on input array (made with ->parseFieldList())
	 * Can also compile field lists for ORDER BY and GROUP BY.
	 *
	 * @param	array		Array of select fields, (made with ->parseFieldList())
	 * @return	string		Select field string
	 * @see parseFieldList()
	 */
	function compileFieldList($selectFields)	{

			// Prepare buffer variable:
		$outputParts = array();

			// Traverse the selectFields if any:
		if (is_array($selectFields))	{
			foreach($selectFields as $k => $v)	{

					// Detecting type:
				switch($v['type'])	{
					case 'function':
						$outputParts[$k] = $v['function'].'('.$v['func_content'].')';
					break;
					case 'field':
						$outputParts[$k] = ($v['table']?$v['table'].'.':'').$v['field'];
					break;
				}

					// Alias:
				if ($v['as'])	{
					$outputParts[$k].= ' '.$v['as_keyword'].' '.$v['as'];
				}

					// Specifically for ORDER BY and GROUP BY field lists:
				if ($v['sortDir'])	{
					$outputParts[$k].= ' '.$v['sortDir'];
				}
			}
		}

			// Return imploded buffer:
		return implode(', ',$outputParts);
	}

	/**
	 * Compiles a "FROM [output] WHERE..:" table list based on input array (made with ->parseFromTables())
	 *
	 * @param	array		Array of table names, (made with ->parseFromTables())
	 * @return	string		Table name string
	 * @see parseFromTables()
	 */
	function compileFromTables($tablesArray)	{

			// Prepare buffer variable:
		$outputParts = array();

			// Traverse the table names:
		if (is_array($tablesArray))	{
			foreach($tablesArray as $k => $v)	{

					// Set table name:
				$outputParts[$k] = $v['table'];

					// Add alias AS if there:
				if ($v['as'])	{
					$outputParts[$k].= ' '.$v['as_keyword'].' '.$v['as'];
				}

				if (is_array($v['JOIN']))	{
					$outputParts[$k].= ' '.$v['JOIN']['type'].' '.$v['JOIN']['withTable'].' ON '.implode('=',$v['JOIN']['ON']);
				}

			}
		}

			// Return imploded buffer:
		return implode(', ',$outputParts);
	}

	/**
	 * Implodes an array of WHERE clause configuration into a WHERE clause.
	 * NOTICE: MIGHT BY A TEMPORARY FUNCTION. Use for debugging only!
	 * BUT IT IS NEEDED FOR DBAL - MAKE IT PERMANENT?!?!
	 *
	 * @param	array		WHERE clause configuration
	 * @return	string		WHERE clause as string.
	 * @see	explodeWhereClause()
	 */
	function compileWhereClause($clauseArray)	{

			// Prepare buffer variable:
		$output='';

			// Traverse clause array:
		if (is_array($clauseArray))	{
			foreach($clauseArray as $k => $v)	{

					// Set operator:
				$output.=$v['operator'] ? ' '.$v['operator'] : '';

					// Look for sublevel:
				if (is_array($v['sub']))	{
					$output.=' ('.trim($this->compileWhereClause($v['sub'])).')';
				} else {

						// Set field/table with modifying prefix if any:
					$output.=' '.trim($v['modifier'].' '.($v['table']?$v['table'].'.':'').$v['field']);

						// Set calculation, if any:
					if ($v['calc'])	{
						$output.=$v['calc'].$v['calc_value'][1].$this->compileAddslashes($v['calc_value'][0]).$v['calc_value'][1];
					}

						// Set comparator:
					if ($v['comparator'])	{
						$output.=' '.$v['comparator'];

							// Detecting value type; list or plain:
						if (t3lib_div::inList('NOTIN,IN',strtoupper(str_replace(array(' ',"\t","\r","\n"),'',$v['comparator']))))	{
							$valueBuffer = array();
							foreach($v['value'] as $realValue)	{
								$valueBuffer[]=$realValue[1].$this->compileAddslashes($realValue[0]).$realValue[1];
							}
							$output.=' ('.trim(implode(',',$valueBuffer)).')';
						} else {
							$output.=' '.$v['value'][1].$this->compileAddslashes($v['value'][0]).$v['value'][1];
						}
					}
				}
			}
		}

			// Return output buffer:
		return $output;
	}

	/**
	 * Compile field definition
	 *
	 * @param	array		Field definition parts
	 * @return	string		Field definition string
	 */
	function compileFieldCfg($fieldCfg)	{

			// Set type:
		$cfg = $fieldCfg['fieldType'];

			// Add value, if any:
		if (strlen($fieldCfg['value']))	{
			$cfg.='('.$fieldCfg['value'].')';
		}

			// Add additional features:
		if (is_array($fieldCfg['featureIndex']))	{
			foreach($fieldCfg['featureIndex'] as $featureDef)	{
				$cfg.=' '.$featureDef['keyword'];

					// Add value if found:
				if (is_array($featureDef['value']))	{
					$cfg.=' '.$featureDef['value'][1].$this->compileAddslashes($featureDef['value'][0]).$featureDef['value'][1];
				}
			}
		}

			// Return field definition string:
		return $cfg;
	}











	/*************************
	 *
	 * Debugging
	 *
	 *************************/

	/**
	 * Check parsability of input SQL part string; Will parse and re-compile after which it is compared
	 *
	 * @param	string		Part definition of string; "SELECT" = fieldlist (also ORDER BY and GROUP BY), "FROM" = table list, "WHERE" = Where clause.
	 * @param	string		SQL string to verify parsability of
	 * @return	mixed		Returns array with string 1 and 2 if error, otherwise false
	 */
	function debug_parseSQLpart($part,$str)	{
		switch($part)	{
			case 'SELECT':
				return $this->debug_parseSQLpartCompare($str,$this->compileFieldList($this->parseFieldList($str)));
			break;
			case 'FROM':
				return $this->debug_parseSQLpartCompare($str,$this->compileFromTables($this->parseFromTables($str)));
			break;
			case 'WHERE':
				return $this->debug_parseSQLpartCompare($str,$this->compileWhereClause($this->parseWhereClause($str)));
			break;
		}
	}

	/**
	 * Compare two query strins by stripping away whitespace.
	 *
	 * @param	string		SQL String 1
	 * @param	string		SQL string 2
	 * @param	boolean		If true, the strings are compared insensitive to case
	 * @return	mixed		Returns array with string 1 and 2 if error, otherwise false
	 */
	function debug_parseSQLpartCompare($str,$newStr,$caseInsensitive=FALSE)	{
		if ($caseInsensitive)	{
			$str1 = strtoupper($str);
			$str2 = strtoupper($newStr);
		} else {
			$str1 = $str;
			$str2 = $newStr;
		}

			// Fixing escaped chars:
		$search = array('\0', '\n', '\r', '\Z');
		$replace = array("\x00", "\x0a", "\x0d", "\x1a");
		$str1 = str_replace($search, $replace, $str1);
		$str2 = str_replace($search, $replace, $str2);

			# Normally, commented out since they are needed only in tricky cases...
#		$str1 = stripslashes($str1);
#		$str2 = stripslashes($str2);

		if (strcmp(str_replace(array(' ',"\t","\r","\n"),'',$this->trimSQL($str1)),str_replace(array(' ',"\t","\r","\n"),'',$this->trimSQL($str2))))	{
			return array(
					str_replace(array(' ',"\t","\r","\n"),' ',$str),
					str_replace(array(' ',"\t","\r","\n"),' ',$newStr),
				);
		}
	}

	/**
	 * Performs the ultimate test of the parser: Direct a SQL query in; You will get it back (through the parsed and re-compiled) if no problems, otherwise the script will print the error and exit
	 *
	 * @param	string		SQL query
	 * @return	string		Query if all is well, otherwise exit.
	 */
	function debug_testSQL($SQLquery)	{
#		return $SQLquery;
#debug(array($SQLquery));

			// Getting result array:
		$parseResult = $this->parseSQL($SQLquery);

			// If result array was returned, proceed. Otherwise show error and exit.
		if (is_array($parseResult))	{

				// Re-compile query:
			$newQuery = $this->compileSQL($parseResult);

				// TEST the new query:
			$testResult = $this->debug_parseSQLpartCompare($SQLquery, $newQuery);

				// Return new query if OK, otherwise show error and exit:
			if (!is_array($testResult))	{
				return $newQuery;
			} else {
				debug(array('ERROR MESSAGE'=>'Input query did not match the parsed and recompiled query exactly (not observing whitespace)', 'TEST result' => $testResult),'SQL parsing failed:');
				exit;
			}
		} else {
			debug(array('query' => $SQLquery, 'ERROR MESSAGE'=>$parseResult),'SQL parsing failed:');
			exit;
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_sqlparser.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_sqlparser.php']);
}
?>
