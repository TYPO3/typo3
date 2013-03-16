<?php
namespace TYPO3\CMS\Core\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * TYPO3 SQL parser class.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class SqlParser {

	// Parser:
	// Parsing error string
	/**
	 * @todo Define visibility
	 */
	public $parse_error = '';

	// Last stop keyword used.
	/**
	 * @todo Define visibility
	 */
	public $lastStopKeyWord = '';

	/*************************************
	 *
	 * SQL Parsing, full queries
	 *
	 **************************************/
	/**
	 * Parses any single SQL query
	 *
	 * @param string $parseString SQL query
	 * @return array Result array with all the parts in - or error message string
	 * @see compileSQL(), debug_testSQL()
	 */
	public function parseSQL($parseString) {
		// Prepare variables:
		$parseString = $this->trimSQL($parseString);
		$this->parse_error = '';
		$result = array();
		// Finding starting keyword of string:
		$_parseString = $parseString;
		// Protecting original string...
		$keyword = $this->nextPart($_parseString, '^(SELECT|UPDATE|INSERT[[:space:]]+INTO|DELETE[[:space:]]+FROM|EXPLAIN|DROP[[:space:]]+TABLE|CREATE[[:space:]]+TABLE|CREATE[[:space:]]+DATABASE|ALTER[[:space:]]+TABLE|TRUNCATE[[:space:]]+TABLE)[[:space:]]+');
		$keyword = strtoupper(str_replace(array(' ', TAB, CR, LF), '', $keyword));
		switch ($keyword) {
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
		case 'TRUNCATETABLE':
			// Parsing TRUNCATE TABLE query:
			$result = $this->parseTRUNCATETABLE($parseString);
			break;
		default:
			$result = $this->parseError('"' . $keyword . '" is not a keyword', $parseString);
			break;
		}
		return $result;
	}

	/**
	 * Parsing SELECT query
	 *
	 * @param string $parseString SQL string with SELECT query to parse
	 * @param array $parameterReferences Array holding references to either named (:name) or question mark (?) parameters found
	 * @return mixed Returns array with components of SELECT query on success, otherwise an error message string.
	 * @see compileSELECT()
	 */
	protected function parseSELECT($parseString, &$parameterReferences = NULL) {
		// Removing SELECT:
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr($parseString, 6));
		// Init output variable:
		$result = array();
		if ($parameterReferences === NULL) {
			$result['parameters'] = array();
			$parameterReferences = &$result['parameters'];
		}
		$result['type'] = 'SELECT';
		// Looking for STRAIGHT_JOIN keyword:
		$result['STRAIGHT_JOIN'] = $this->nextPart($parseString, '^(STRAIGHT_JOIN)[[:space:]]+');
		// Select fields:
		$result['SELECT'] = $this->parseFieldList($parseString, '^(FROM)[[:space:]]+');
		if ($this->parse_error) {
			return $this->parse_error;
		}
		// Continue if string is not ended:
		if ($parseString) {
			// Get table list:
			$result['FROM'] = $this->parseFromTables($parseString, '^(WHERE)[[:space:]]+');
			if ($this->parse_error) {
				return $this->parse_error;
			}
			// If there are more than just the tables (a WHERE clause that would be...)
			if ($parseString) {
				// Get WHERE clause:
				$result['WHERE'] = $this->parseWhereClause($parseString, '^(GROUP[[:space:]]+BY|ORDER[[:space:]]+BY|LIMIT)[[:space:]]+', $parameterReferences);
				if ($this->parse_error) {
					return $this->parse_error;
				}
				// If the WHERE clause parsing was stopped by GROUP BY, ORDER BY or LIMIT, then proceed with parsing:
				if ($this->lastStopKeyWord) {
					// GROUP BY parsing:
					if ($this->lastStopKeyWord == 'GROUPBY') {
						$result['GROUPBY'] = $this->parseFieldList($parseString, '^(ORDER[[:space:]]+BY|LIMIT)[[:space:]]+');
						if ($this->parse_error) {
							return $this->parse_error;
						}
					}
					// ORDER BY parsing:
					if ($this->lastStopKeyWord == 'ORDERBY') {
						$result['ORDERBY'] = $this->parseFieldList($parseString, '^(LIMIT)[[:space:]]+');
						if ($this->parse_error) {
							return $this->parse_error;
						}
					}
					// LIMIT parsing:
					if ($this->lastStopKeyWord == 'LIMIT') {
						if (preg_match('/^([0-9]+|[0-9]+[[:space:]]*,[[:space:]]*[0-9]+)$/', trim($parseString))) {
							$result['LIMIT'] = $parseString;
						} else {
							return $this->parseError('No value for limit!', $parseString);
						}
					}
				}
			}
		} else {
			return $this->parseError('No table to select from!', $parseString);
		}
		// Store current parseString in the result array for possible further processing (e.g., subquery support by DBAL)
		$result['parseString'] = $parseString;
		// Return result:
		return $result;
	}

	/**
	 * Parsing UPDATE query
	 *
	 * @param string $parseString SQL string with UPDATE query to parse
	 * @return mixed Returns array with components of UPDATE query on success, otherwise an error message string.
	 * @see compileUPDATE()
	 */
	protected function parseUPDATE($parseString) {
		// Removing UPDATE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr($parseString, 6));
		// Init output variable:
		$result = array();
		$result['type'] = 'UPDATE';
		// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
		// Continue if string is not ended:
		if ($result['TABLE']) {
			if ($parseString && $this->nextPart($parseString, '^(SET)[[:space:]]+')) {
				$comma = TRUE;
				// Get field/value pairs:
				while ($comma) {
					if ($fieldName = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]*=')) {
						// Strip of "=" sign.
						$this->nextPart($parseString, '^(=)');
						$value = $this->getValue($parseString);
						$result['FIELDS'][$fieldName] = $value;
					} else {
						return $this->parseError('No fieldname found', $parseString);
					}
					$comma = $this->nextPart($parseString, '^(,)');
				}
				// WHERE
				if ($this->nextPart($parseString, '^(WHERE)')) {
					$result['WHERE'] = $this->parseWhereClause($parseString);
					if ($this->parse_error) {
						return $this->parse_error;
					}
				}
			} else {
				return $this->parseError('Query missing SET...', $parseString);
			}
		} else {
			return $this->parseError('No table found!', $parseString);
		}
		// Should be no more content now:
		if ($parseString) {
			return $this->parseError('Still content in clause after parsing!', $parseString);
		}
		// Return result:
		return $result;
	}

	/**
	 * Parsing INSERT query
	 *
	 * @param string $parseString SQL string with INSERT query to parse
	 * @return mixed Returns array with components of INSERT query on success, otherwise an error message string.
	 * @see compileINSERT()
	 */
	protected function parseINSERT($parseString) {
		// Removing INSERT
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString, 6)), 4));
		// Init output variable:
		$result = array();
		$result['type'] = 'INSERT';
		// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\\()');
		if ($result['TABLE']) {
			// In this case there are no field names mentioned in the SQL!
			if ($this->nextPart($parseString, '^(VALUES)([[:space:]]+|\\()')) {
				// Get values/fieldnames (depending...)
				$result['VALUES_ONLY'] = $this->getValue($parseString, 'IN');
				if ($this->parse_error) {
					return $this->parse_error;
				}
				if (preg_match('/^,/', $parseString)) {
					$result['VALUES_ONLY'] = array($result['VALUES_ONLY']);
					$result['EXTENDED'] = '1';
					while ($this->nextPart($parseString, '^(,)') === ',') {
						$result['VALUES_ONLY'][] = $this->getValue($parseString, 'IN');
						if ($this->parse_error) {
							return $this->parse_error;
						}
					}
				}
			} else {
				// There are apparently fieldnames listed:
				$fieldNames = $this->getValue($parseString, '_LIST');
				if ($this->parse_error) {
					return $this->parse_error;
				}
				// "VALUES" keyword binds the fieldnames to values:
				if ($this->nextPart($parseString, '^(VALUES)([[:space:]]+|\\()')) {
					$result['FIELDS'] = array();
					do {
						// Using the "getValue" function to get the field list...
						$values = $this->getValue($parseString, 'IN');
						if ($this->parse_error) {
							return $this->parse_error;
						}
						$insertValues = array();
						foreach ($fieldNames as $k => $fN) {
							if (preg_match('/^[[:alnum:]_]+$/', $fN)) {
								if (isset($values[$k])) {
									if (!isset($insertValues[$fN])) {
										$insertValues[$fN] = $values[$k];
									} else {
										return $this->parseError('Fieldname ("' . $fN . '") already found in list!', $parseString);
									}
								} else {
									return $this->parseError('No value set!', $parseString);
								}
							} else {
								return $this->parseError('Invalid fieldname ("' . $fN . '")', $parseString);
							}
						}
						if (isset($values[$k + 1])) {
							return $this->parseError('Too many values in list!', $parseString);
						}
						$result['FIELDS'][] = $insertValues;
					} while ($this->nextPart($parseString, '^(,)') === ',');
					if (count($result['FIELDS']) === 1) {
						$result['FIELDS'] = $result['FIELDS'][0];
					} else {
						$result['EXTENDED'] = '1';
					}
				} else {
					return $this->parseError('VALUES keyword expected', $parseString);
				}
			}
		} else {
			return $this->parseError('No table found!', $parseString);
		}
		// Should be no more content now:
		if ($parseString) {
			return $this->parseError('Still content after parsing!', $parseString);
		}
		// Return result
		return $result;
	}

	/**
	 * Parsing DELETE query
	 *
	 * @param string $parseString SQL string with DELETE query to parse
	 * @return mixed Returns array with components of DELETE query on success, otherwise an error message string.
	 * @see compileDELETE()
	 */
	protected function parseDELETE($parseString) {
		// Removing DELETE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString, 6)), 4));
		// Init output variable:
		$result = array();
		$result['type'] = 'DELETE';
		// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
		if ($result['TABLE']) {
			// WHERE
			if ($this->nextPart($parseString, '^(WHERE)')) {
				$result['WHERE'] = $this->parseWhereClause($parseString);
				if ($this->parse_error) {
					return $this->parse_error;
				}
			}
		} else {
			return $this->parseError('No table found!', $parseString);
		}
		// Should be no more content now:
		if ($parseString) {
			return $this->parseError('Still content in clause after parsing!', $parseString);
		}
		// Return result:
		return $result;
	}

	/**
	 * Parsing EXPLAIN query
	 *
	 * @param string $parseString SQL string with EXPLAIN query to parse
	 * @return mixed Returns array with components of EXPLAIN query on success, otherwise an error message string.
	 * @see parseSELECT()
	 */
	protected function parseEXPLAIN($parseString) {
		// Removing EXPLAIN
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr($parseString, 6));
		// Init output variable:
		$result = $this->parseSELECT($parseString);
		if (is_array($result)) {
			$result['type'] = 'EXPLAIN';
		}
		return $result;
	}

	/**
	 * Parsing CREATE TABLE query
	 *
	 * @param string $parseString SQL string starting with CREATE TABLE
	 * @return mixed Returns array with components of CREATE TABLE query on success, otherwise an error message string.
	 * @see compileCREATETABLE()
	 */
	protected function parseCREATETABLE($parseString) {
		// Removing CREATE TABLE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString, 6)), 5));
		// Init output variable:
		$result = array();
		$result['type'] = 'CREATETABLE';
		// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]*\\(', TRUE);
		if ($result['TABLE']) {
			// While the parseString is not yet empty:
			while (strlen($parseString) > 0) {
				// Getting key
				if ($key = $this->nextPart($parseString, '^(KEY|PRIMARY KEY|UNIQUE KEY|UNIQUE)([[:space:]]+|\\()')) {
					$key = strtoupper(str_replace(array(' ', TAB, CR, LF), '', $key));
					switch ($key) {
					case 'PRIMARYKEY':
						$result['KEYS']['PRIMARYKEY'] = $this->getValue($parseString, '_LIST');
						if ($this->parse_error) {
							return $this->parse_error;
						}
						break;
					case 'UNIQUE':

					case 'UNIQUEKEY':
						if ($keyName = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\\()')) {
							$result['KEYS']['UNIQUE'] = array($keyName => $this->getValue($parseString, '_LIST'));
							if ($this->parse_error) {
								return $this->parse_error;
							}
						} else {
							return $this->parseError('No keyname found', $parseString);
						}
						break;
					case 'KEY':
						if ($keyName = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\\()')) {
							$result['KEYS'][$keyName] = $this->getValue($parseString, '_LIST', 'INDEX');
							if ($this->parse_error) {
								return $this->parse_error;
							}
						} else {
							return $this->parseError('No keyname found', $parseString);
						}
						break;
					}
				} elseif ($fieldName = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+')) {
					// Getting field:
					$result['FIELDS'][$fieldName]['definition'] = $this->parseFieldDef($parseString);
					if ($this->parse_error) {
						return $this->parse_error;
					}
				}
				// Finding delimiter:
				$delim = $this->nextPart($parseString, '^(,|\\))');
				if (!$delim) {
					return $this->parseError('No delimiter found', $parseString);
				} elseif ($delim == ')') {
					break;
				}
			}
			// Finding what is after the table definition - table type in MySQL
			if ($delim == ')') {
				if ($this->nextPart($parseString, '^((ENGINE|TYPE)[[:space:]]*=)')) {
					$result['tableType'] = $parseString;
					$parseString = '';
				}
			} else {
				return $this->parseError('No fieldname found!', $parseString);
			}
		} else {
			return $this->parseError('No table found!', $parseString);
		}
		// Should be no more content now:
		if ($parseString) {
			return $this->parseError('Still content in clause after parsing!', $parseString);
		}
		return $result;
	}

	/**
	 * Parsing ALTER TABLE query
	 *
	 * @param string $parseString SQL string starting with ALTER TABLE
	 * @return mixed Returns array with components of ALTER TABLE query on success, otherwise an error message string.
	 * @see compileALTERTABLE()
	 */
	protected function parseALTERTABLE($parseString) {
		// Removing ALTER TABLE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString, 5)), 5));
		// Init output variable:
		$result = array();
		$result['type'] = 'ALTERTABLE';
		// Get table:
		$hasBackquote = $this->nextPart($parseString, '^(`)') === '`';
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)' . ($hasBackquote ? '`' : '') . '[[:space:]]+');
		if ($hasBackquote && $this->nextPart($parseString, '^(`)') !== '`') {
			return $this->parseError('No end backquote found!', $parseString);
		}
		if ($result['TABLE']) {
			if ($result['action'] = $this->nextPart($parseString, '^(CHANGE|DROP[[:space:]]+KEY|DROP[[:space:]]+PRIMARY[[:space:]]+KEY|ADD[[:space:]]+KEY|ADD[[:space:]]+PRIMARY[[:space:]]+KEY|ADD[[:space:]]+UNIQUE|DROP|ADD|RENAME|DEFAULT[[:space:]]+CHARACTER[[:space:]]+SET|ENGINE)([[:space:]]+|\\(|=)')) {
				$actionKey = strtoupper(str_replace(array(' ', TAB, CR, LF), '', $result['action']));
				// Getting field:
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('ADDPRIMARYKEY,DROPPRIMARYKEY,ENGINE', $actionKey) || ($fieldKey = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+'))) {
					switch ($actionKey) {
					case 'ADD':
						$result['FIELD'] = $fieldKey;
						$result['definition'] = $this->parseFieldDef($parseString);
						if ($this->parse_error) {
							return $this->parse_error;
						}
						break;
					case 'DROP':

					case 'RENAME':
						$result['FIELD'] = $fieldKey;
						break;
					case 'CHANGE':
						$result['FIELD'] = $fieldKey;
						if ($result['newField'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+')) {
							$result['definition'] = $this->parseFieldDef($parseString);
							if ($this->parse_error) {
								return $this->parse_error;
							}
						} else {
							return $this->parseError('No NEW field name found', $parseString);
						}
						break;
					case 'ADDKEY':

					case 'ADDPRIMARYKEY':

					case 'ADDUNIQUE':
						$result['KEY'] = $fieldKey;
						$result['fields'] = $this->getValue($parseString, '_LIST', 'INDEX');
						if ($this->parse_error) {
							return $this->parse_error;
						}
						break;
					case 'DROPKEY':
						$result['KEY'] = $fieldKey;
						break;
					case 'DROPPRIMARYKEY':
						// ??? todo!
						break;
					case 'DEFAULTCHARACTERSET':
						$result['charset'] = $fieldKey;
						break;
					case 'ENGINE':
						$result['engine'] = $this->nextPart($parseString, '^=[[:space:]]*([[:alnum:]]+)[[:space:]]+', TRUE);
						break;
					}
				} else {
					return $this->parseError('No field name found', $parseString);
				}
			} else {
				return $this->parseError('No action CHANGE, DROP or ADD found!', $parseString);
			}
		} else {
			return $this->parseError('No table found!', $parseString);
		}
		// Should be no more content now:
		if ($parseString) {
			return $this->parseError('Still content in clause after parsing!', $parseString);
		}
		return $result;
	}

	/**
	 * Parsing DROP TABLE query
	 *
	 * @param string $parseString SQL string starting with DROP TABLE
	 * @return mixed Returns array with components of DROP TABLE query on success, otherwise an error message string.
	 */
	protected function parseDROPTABLE($parseString) {
		// Removing DROP TABLE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString, 4)), 5));
		// Init output variable:
		$result = array();
		$result['type'] = 'DROPTABLE';
		// IF EXISTS
		$result['ifExists'] = $this->nextPart($parseString, '^(IF[[:space:]]+EXISTS[[:space:]]+)');
		// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
		if ($result['TABLE']) {
			// Should be no more content now:
			if ($parseString) {
				return $this->parseError('Still content in clause after parsing!', $parseString);
			}
			return $result;
		} else {
			return $this->parseError('No table found!', $parseString);
		}
	}

	/**
	 * Parsing CREATE DATABASE query
	 *
	 * @param string $parseString SQL string starting with CREATE DATABASE
	 * @return mixed Returns array with components of CREATE DATABASE query on success, otherwise an error message string.
	 */
	protected function parseCREATEDATABASE($parseString) {
		// Removing CREATE DATABASE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString, 6)), 8));
		// Init output variable:
		$result = array();
		$result['type'] = 'CREATEDATABASE';
		// Get table:
		$result['DATABASE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
		if ($result['DATABASE']) {
			// Should be no more content now:
			if ($parseString) {
				return $this->parseError('Still content in clause after parsing!', $parseString);
			}
			return $result;
		} else {
			return $this->parseError('No database found!', $parseString);
		}
	}

	/**
	 * Parsing TRUNCATE TABLE query
	 *
	 * @param string $parseString SQL string starting with TRUNCATE TABLE
	 * @return mixed Returns array with components of TRUNCATE TABLE query on success, otherwise an error message string.
	 */
	protected function parseTRUNCATETABLE($parseString) {
		// Removing TRUNCATE TABLE
		$parseString = $this->trimSQL($parseString);
		$parseString = ltrim(substr(ltrim(substr($parseString, 8)), 5));
		// Init output variable:
		$result = array();
		$result['type'] = 'TRUNCATETABLE';
		// Get table:
		$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
		if ($result['TABLE']) {
			// Should be no more content now:
			if ($parseString) {
				return $this->parseError('Still content in clause after parsing!', $parseString);
			}
			return $result;
		} else {
			return $this->parseError('No table found!', $parseString);
		}
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
	 * @param string $parseString The string with fieldnames, eg. "title, uid AS myUid, max(tstamp), count(*)" etc. NOTICE: passed by reference!
	 * @param string $stopRegex Regular expressing to STOP parsing, eg. '^(FROM)([[:space:]]*)'
	 * @return array If successful parsing, returns an array, otherwise an error string.
	 * @see compileFieldList()
	 */
	public function parseFieldList(&$parseString, $stopRegex = '') {
		$stack = array();
		// Contains the parsed content
		if (strlen($parseString) == 0) {
			return $stack;
		}
		// FIXME - should never happen, why does it?
		// Pointer to positions in $stack
		$pnt = 0;
		// Indicates the parenthesis level we are at.
		$level = 0;
		// Recursivity brake.
		$loopExit = 0;
		// Prepare variables:
		$parseString = $this->trimSQL($parseString);
		$this->lastStopKeyWord = '';
		$this->parse_error = '';
		// Parse any SQL hint / comments
		$stack[$pnt]['comments'] = $this->nextPart($parseString, '^(\\/\\*.*\\*\\/)');
		// $parseString is continuously shortened by the process and we keep parsing it till it is zero:
		while (strlen($parseString)) {
			// Checking if we are inside / outside parenthesis (in case of a function like count(), max(), min() etc...):
			// Inside parenthesis here (does NOT detect if values in quotes are used, the only token is ")" or "("):
			if ($level > 0) {
				// Accumulate function content until next () parenthesis:
				$funcContent = $this->nextPart($parseString, '^([^()]*.)');
				$stack[$pnt]['func_content.'][] = array(
					'level' => $level,
					'func_content' => substr($funcContent, 0, -1)
				);
				$stack[$pnt]['func_content'] .= $funcContent;
				// Detecting ( or )
				switch (substr($stack[$pnt]['func_content'], -1)) {
				case '(':
					$level++;
					break;
				case ')':
					$level--;
					// If this was the last parenthesis:
					if (!$level) {
						$stack[$pnt]['func_content'] = substr($stack[$pnt]['func_content'], 0, -1);
						// Remove any whitespace after the parenthesis.
						$parseString = ltrim($parseString);
					}
					break;
				}
			} else {
				// Outside parenthesis, looking for next field:
				// Looking for a flow-control construct (only known constructs supported)
				if (preg_match('/^case([[:space:]][[:alnum:]\\*._]+)?[[:space:]]when/i', $parseString)) {
					$stack[$pnt]['type'] = 'flow-control';
					$stack[$pnt]['flow-control'] = $this->parseCaseStatement($parseString);
					// Looking for "AS" alias:
					if ($as = $this->nextPart($parseString, '^(AS)[[:space:]]+')) {
						$stack[$pnt]['as'] = $this->nextPart($parseString, '^([[:alnum:]_]+)(,|[[:space:]]+)');
						$stack[$pnt]['as_keyword'] = $as;
					}
				} else {
					// Looking for a known function (only known functions supported)
					$func = $this->nextPart($parseString, '^(count|max|min|floor|sum|avg)[[:space:]]*\\(');
					if ($func) {
						// Strip of "("
						$parseString = trim(substr($parseString, 1));
						$stack[$pnt]['type'] = 'function';
						$stack[$pnt]['function'] = $func;
						// increse parenthesis level counter.
						$level++;
					} else {
						$stack[$pnt]['distinct'] = $this->nextPart($parseString, '^(distinct[[:space:]]+)');
						// Otherwise, look for regular fieldname:
						if (($fieldName = $this->nextPart($parseString, '^([[:alnum:]\\*._]+)(,|[[:space:]]+)')) !== '') {
							$stack[$pnt]['type'] = 'field';
							// Explode fieldname into field and table:
							$tableField = explode('.', $fieldName, 2);
							if (count($tableField) == 2) {
								$stack[$pnt]['table'] = $tableField[0];
								$stack[$pnt]['field'] = $tableField[1];
							} else {
								$stack[$pnt]['table'] = '';
								$stack[$pnt]['field'] = $tableField[0];
							}
						} else {
							return $this->parseError('No field name found as expected in parseFieldList()', $parseString);
						}
					}
				}
			}
			// After a function or field we look for "AS" alias and a comma to separate to the next field in the list:
			if (!$level) {
				// Looking for "AS" alias:
				if ($as = $this->nextPart($parseString, '^(AS)[[:space:]]+')) {
					$stack[$pnt]['as'] = $this->nextPart($parseString, '^([[:alnum:]_]+)(,|[[:space:]]+)');
					$stack[$pnt]['as_keyword'] = $as;
				}
				// Looking for "ASC" or "DESC" keywords (for ORDER BY)
				if ($sDir = $this->nextPart($parseString, '^(ASC|DESC)([[:space:]]+|,)')) {
					$stack[$pnt]['sortDir'] = $sDir;
				}
				// Looking for stop-keywords:
				if ($stopRegex && ($this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))) {
					$this->lastStopKeyWord = strtoupper(str_replace(array(' ', TAB, CR, LF), '', $this->lastStopKeyWord));
					return $stack;
				}
				// Looking for comma (since the stop-keyword did not trigger a return...)
				if (strlen($parseString) && !$this->nextPart($parseString, '^(,)')) {
					return $this->parseError('No comma found as expected in parseFieldList()', $parseString);
				}
				// Increasing pointer:
				$pnt++;
			}
			// Check recursivity brake:
			$loopExit++;
			if ($loopExit > 500) {
				return $this->parseError('More than 500 loops, exiting prematurely in parseFieldList()...', $parseString);
			}
		}
		// Return result array:
		return $stack;
	}

	/**
	 * Parsing a CASE ... WHEN flow-control construct.
	 * The output from this function can be compiled back with ->compileCaseStatement()
	 *
	 * @param string $parseString The string with the CASE ... WHEN construct, eg. "CASE field WHEN 1 THEN 0 ELSE ..." etc. NOTICE: passed by reference!
	 * @return array If successful parsing, returns an array, otherwise an error string.
	 * @see compileCaseConstruct()
	 */
	protected function parseCaseStatement(&$parseString) {
		$result = array();
		$result['type'] = $this->nextPart($parseString, '^(case)[[:space:]]+');
		if (!preg_match('/^when[[:space:]]+/i', $parseString)) {
			$value = $this->getValue($parseString);
			if (!(isset($value[1]) || is_numeric($value[0]))) {
				$result['case_field'] = $value[0];
			} else {
				$result['case_value'] = $value;
			}
		}
		$result['when'] = array();
		while ($this->nextPart($parseString, '^(when)[[:space:]]')) {
			$when = array();
			$when['when_value'] = $this->parseWhereClause($parseString, '^(then)[[:space:]]+');
			$when['then_value'] = $this->getValue($parseString);
			$result['when'][] = $when;
		}
		if ($this->nextPart($parseString, '^(else)[[:space:]]+')) {
			$result['else'] = $this->getValue($parseString);
		}
		if (!$this->nextPart($parseString, '^(end)[[:space:]]+')) {
			return $this->parseError('No "end" keyword found as expected in parseCaseStatement()', $parseString);
		}
		return $result;
	}

	/**
	 * Parsing the tablenames in the "FROM [$parseString] WHERE" part of a query into an array.
	 * The success of this parsing determines if that part of the query is supported by TYPO3.
	 *
	 * @param string $parseString List of tables, eg. "pages, tt_content" or "pages A, pages B". NOTICE: passed by reference!
	 * @param string $stopRegex Regular expressing to STOP parsing, eg. '^(WHERE)([[:space:]]*)'
	 * @return array If successful parsing, returns an array, otherwise an error string.
	 * @see compileFromTables()
	 */
	public function parseFromTables(&$parseString, $stopRegex = '') {
		// Prepare variables:
		$parseString = $this->trimSQL($parseString);
		$this->lastStopKeyWord = '';
		$this->parse_error = '';
		// Contains the parsed content
		$stack = array();
		// Pointer to positions in $stack
		$pnt = 0;
		// Recursivity brake.
		$loopExit = 0;
		// $parseString is continously shortend by the process and we keep parsing it till it is zero:
		while (strlen($parseString)) {
			// Looking for the table:
			if ($stack[$pnt]['table'] = $this->nextPart($parseString, '^([[:alnum:]_]+)(,|[[:space:]]+)')) {
				// Looking for stop-keywords before fetching potential table alias:
				if ($stopRegex && ($this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))) {
					$this->lastStopKeyWord = strtoupper(str_replace(array(' ', TAB, CR, LF), '', $this->lastStopKeyWord));
					return $stack;
				}
				if (!preg_match('/^(LEFT|RIGHT|JOIN|INNER)[[:space:]]+/i', $parseString)) {
					$stack[$pnt]['as_keyword'] = $this->nextPart($parseString, '^(AS[[:space:]]+)');
					$stack[$pnt]['as'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]*');
				}
			} else {
				return $this->parseError('No table name found as expected in parseFromTables()!', $parseString);
			}
			// Looking for JOIN
			$joinCnt = 0;
			while ($join = $this->nextPart($parseString, '^(LEFT[[:space:]]+JOIN|LEFT[[:space:]]+OUTER[[:space:]]+JOIN|RIGHT[[:space:]]+JOIN|RIGHT[[:space:]]+OUTER[[:space:]]+JOIN|INNER[[:space:]]+JOIN|JOIN)[[:space:]]+')) {
				$stack[$pnt]['JOIN'][$joinCnt]['type'] = $join;
				if ($stack[$pnt]['JOIN'][$joinCnt]['withTable'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+', 1)) {
					if (!preg_match('/^ON[[:space:]]+/i', $parseString)) {
						$stack[$pnt]['JOIN'][$joinCnt]['as_keyword'] = $this->nextPart($parseString, '^(AS[[:space:]]+)');
						$stack[$pnt]['JOIN'][$joinCnt]['as'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
					}
					if (!$this->nextPart($parseString, '^(ON[[:space:]]+)')) {
						return $this->parseError('No join condition found in parseFromTables()!', $parseString);
					}
					$stack[$pnt]['JOIN'][$joinCnt]['ON'] = array();
					$condition = array('operator' => '');
					$parseCondition = TRUE;
					while ($parseCondition) {
						if (($fieldName = $this->nextPart($parseString, '^([[:alnum:]._]+)[[:space:]]*(<=|>=|<|>|=|!=)')) !== '') {
							// Parse field name into field and table:
							$tableField = explode('.', $fieldName, 2);
							$condition['left'] = array();
							if (count($tableField) == 2) {
								$condition['left']['table'] = $tableField[0];
								$condition['left']['field'] = $tableField[1];
							} else {
								$condition['left']['table'] = '';
								$condition['left']['field'] = $tableField[0];
							}
						} else {
							return $this->parseError('No join field found in parseFromTables()!', $parseString);
						}
						// Find "comparator":
						$condition['comparator'] = $this->nextPart($parseString, '^(<=|>=|<|>|=|!=)');
						if (($fieldName = $this->nextPart($parseString, '^([[:alnum:]._]+)')) !== '') {
							// Parse field name into field and table:
							$tableField = explode('.', $fieldName, 2);
							$condition['right'] = array();
							if (count($tableField) == 2) {
								$condition['right']['table'] = $tableField[0];
								$condition['right']['field'] = $tableField[1];
							} else {
								$condition['right']['table'] = '';
								$condition['right']['field'] = $tableField[0];
							}
						} else {
							return $this->parseError('No join field found in parseFromTables()!', $parseString);
						}
						$stack[$pnt]['JOIN'][$joinCnt]['ON'][] = $condition;
						if (($operator = $this->nextPart($parseString, '^(AND|OR)')) !== '') {
							$condition = array('operator' => $operator);
						} else {
							$parseCondition = FALSE;
						}
					}
					$joinCnt++;
				} else {
					return $this->parseError('No join table found in parseFromTables()!', $parseString);
				}
			}
			// Looking for stop-keywords:
			if ($stopRegex && ($this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))) {
				$this->lastStopKeyWord = strtoupper(str_replace(array(' ', TAB, CR, LF), '', $this->lastStopKeyWord));
				return $stack;
			}
			// Looking for comma:
			if (strlen($parseString) && !$this->nextPart($parseString, '^(,)')) {
				return $this->parseError('No comma found as expected in parseFromTables()', $parseString);
			}
			// Increasing pointer:
			$pnt++;
			// Check recursivity brake:
			$loopExit++;
			if ($loopExit > 500) {
				return $this->parseError('More than 500 loops, exiting prematurely in parseFromTables()...', $parseString);
			}
		}
		// Return result array:
		return $stack;
	}

	/**
	 * Parsing the WHERE clause fields in the "WHERE [$parseString] ..." part of a query into a multidimensional array.
	 * The success of this parsing determines if that part of the query is supported by TYPO3.
	 *
	 * @param string $parseString WHERE clause to parse. NOTICE: passed by reference!
	 * @param string $stopRegex Regular expressing to STOP parsing, eg. '^(GROUP BY|ORDER BY|LIMIT)([[:space:]]*)'
	 * @param array $parameterReferences Array holding references to either named (:name) or question mark (?) parameters found
	 * @return mixed If successful parsing, returns an array, otherwise an error string.
	 */
	public function parseWhereClause(&$parseString, $stopRegex = '', array &$parameterReferences = array()) {
		// Prepare variables:
		$parseString = $this->trimSQL($parseString);
		$this->lastStopKeyWord = '';
		$this->parse_error = '';
		// Contains the parsed content
		$stack = array(0 => array());
		// Pointer to positions in $stack
		$pnt = array(0 => 0);
		// Determines parenthesis level
		$level = 0;
		// Recursivity brake.
		$loopExit = 0;
		// $parseString is continously shortend by the process and we keep parsing it till it is zero:
		while (strlen($parseString)) {
			// Look for next parenthesis level:
			$newLevel = $this->nextPart($parseString, '^([(])');
			// If new level is started, manage stack/pointers:
			if ($newLevel == '(') {
				// Increase level
				$level++;
				// Reset pointer for this level
				$pnt[$level] = 0;
				// Reset stack for this level
				$stack[$level] = array();
			} else {
				// If no new level is started, just parse the current level:
				// Find "modifier", eg. "NOT or !"
				$stack[$level][$pnt[$level]]['modifier'] = trim($this->nextPart($parseString, '^(!|NOT[[:space:]]+)'));
				// See if condition is EXISTS with a subquery
				if (preg_match('/^EXISTS[[:space:]]*[(]/i', $parseString)) {
					$stack[$level][$pnt[$level]]['func']['type'] = $this->nextPart($parseString, '^(EXISTS)[[:space:]]*');
					// Strip of "("
					$parseString = trim(substr($parseString, 1));
					$stack[$level][$pnt[$level]]['func']['subquery'] = $this->parseSELECT($parseString, $parameterReferences);
					// Seek to new position in parseString after parsing of the subquery
					$parseString = $stack[$level][$pnt[$level]]['func']['subquery']['parseString'];
					unset($stack[$level][$pnt[$level]]['func']['subquery']['parseString']);
					if (!$this->nextPart($parseString, '^([)])')) {
						return 'No ) parenthesis at end of subquery';
					}
				} else {
					// See if LOCATE function is found
					if (preg_match('/^LOCATE[[:space:]]*[(]/i', $parseString)) {
						$stack[$level][$pnt[$level]]['func']['type'] = $this->nextPart($parseString, '^(LOCATE)[[:space:]]*');
						// Strip of "("
						$parseString = trim(substr($parseString, 1));
						$stack[$level][$pnt[$level]]['func']['substr'] = $this->getValue($parseString);
						if (!$this->nextPart($parseString, '^(,)')) {
							return $this->parseError('No comma found as expected in parseWhereClause()', $parseString);
						}
						if ($fieldName = $this->nextPart($parseString, '^([[:alnum:]\\*._]+)[[:space:]]*')) {
							// Parse field name into field and table:
							$tableField = explode('.', $fieldName, 2);
							if (count($tableField) == 2) {
								$stack[$level][$pnt[$level]]['func']['table'] = $tableField[0];
								$stack[$level][$pnt[$level]]['func']['field'] = $tableField[1];
							} else {
								$stack[$level][$pnt[$level]]['func']['table'] = '';
								$stack[$level][$pnt[$level]]['func']['field'] = $tableField[0];
							}
						} else {
							return $this->parseError('No field name found as expected in parseWhereClause()', $parseString);
						}
						if ($this->nextPart($parseString, '^(,)')) {
							$stack[$level][$pnt[$level]]['func']['pos'] = $this->getValue($parseString);
						}
						if (!$this->nextPart($parseString, '^([)])')) {
							return $this->parseError('No ) parenthesis at end of function', $parseString);
						}
					} elseif (preg_match('/^IFNULL[[:space:]]*[(]/i', $parseString)) {
						$stack[$level][$pnt[$level]]['func']['type'] = $this->nextPart($parseString, '^(IFNULL)[[:space:]]*');
						$parseString = trim(substr($parseString, 1));
						// Strip of "("
						if ($fieldName = $this->nextPart($parseString, '^([[:alnum:]\\*._]+)[[:space:]]*')) {
							// Parse field name into field and table:
							$tableField = explode('.', $fieldName, 2);
							if (count($tableField) == 2) {
								$stack[$level][$pnt[$level]]['func']['table'] = $tableField[0];
								$stack[$level][$pnt[$level]]['func']['field'] = $tableField[1];
							} else {
								$stack[$level][$pnt[$level]]['func']['table'] = '';
								$stack[$level][$pnt[$level]]['func']['field'] = $tableField[0];
							}
						} else {
							return $this->parseError('No field name found as expected in parseWhereClause()', $parseString);
						}
						if ($this->nextPart($parseString, '^(,)')) {
							$stack[$level][$pnt[$level]]['func']['default'] = $this->getValue($parseString);
						}
						if (!$this->nextPart($parseString, '^([)])')) {
							return $this->parseError('No ) parenthesis at end of function', $parseString);
						}
					} elseif (preg_match('/^FIND_IN_SET[[:space:]]*[(]/i', $parseString)) {
						$stack[$level][$pnt[$level]]['func']['type'] = $this->nextPart($parseString, '^(FIND_IN_SET)[[:space:]]*');
						// Strip of "("
						$parseString = trim(substr($parseString, 1));
						if ($str = $this->getValue($parseString)) {
							$stack[$level][$pnt[$level]]['func']['str'] = $str;
							if ($fieldName = $this->nextPart($parseString, '^,[[:space:]]*([[:alnum:]._]+)[[:space:]]*', TRUE)) {
								// Parse field name into field and table:
								$tableField = explode('.', $fieldName, 2);
								if (count($tableField) == 2) {
									$stack[$level][$pnt[$level]]['func']['table'] = $tableField[0];
									$stack[$level][$pnt[$level]]['func']['field'] = $tableField[1];
								} else {
									$stack[$level][$pnt[$level]]['func']['table'] = '';
									$stack[$level][$pnt[$level]]['func']['field'] = $tableField[0];
								}
							} else {
								return $this->parseError('No field name found as expected in parseWhereClause()', $parseString);
							}
							if (!$this->nextPart($parseString, '^([)])')) {
								return $this->parseError('No ) parenthesis at end of function', $parseString);
							}
						} else {
							return $this->parseError('No item to look for found as expected in parseWhereClause()', $parseString);
						}
					} else {
						// Support calculated value only for:
						// - "&" (boolean AND)
						// - "+" (addition)
						// - "-" (substraction)
						// - "*" (multiplication)
						// - "/" (division)
						// - "%" (modulo)
						$calcOperators = '&|\\+|-|\\*|\\/|%';
						// Fieldname:
						if (($fieldName = $this->nextPart($parseString, '^([[:alnum:]._]+)([[:space:]]+|' . $calcOperators . '|<=|>=|<|>|=|!=|IS)')) !== '') {
							// Parse field name into field and table:
							$tableField = explode('.', $fieldName, 2);
							if (count($tableField) == 2) {
								$stack[$level][$pnt[$level]]['table'] = $tableField[0];
								$stack[$level][$pnt[$level]]['field'] = $tableField[1];
							} else {
								$stack[$level][$pnt[$level]]['table'] = '';
								$stack[$level][$pnt[$level]]['field'] = $tableField[0];
							}
						} else {
							return $this->parseError('No field name found as expected in parseWhereClause()', $parseString);
						}
						// See if the value is calculated:
						$stack[$level][$pnt[$level]]['calc'] = $this->nextPart($parseString, '^(' . $calcOperators . ')');
						if (strlen($stack[$level][$pnt[$level]]['calc'])) {
							// Finding value for calculation:
							$calc_value = $this->getValue($parseString);
							$stack[$level][$pnt[$level]]['calc_value'] = $calc_value;
							if (count($calc_value) == 1 && is_string($calc_value[0])) {
								// Value is a field, store it to allow DBAL to post-process it (quoting, remapping)
								$tableField = explode('.', $calc_value[0], 2);
								if (count($tableField) == 2) {
									$stack[$level][$pnt[$level]]['calc_table'] = $tableField[0];
									$stack[$level][$pnt[$level]]['calc_field'] = $tableField[1];
								} else {
									$stack[$level][$pnt[$level]]['calc_table'] = '';
									$stack[$level][$pnt[$level]]['calc_field'] = $tableField[0];
								}
							}
						}
					}
					// Find "comparator":
					$comparatorPatterns = array(
						'<=',
						'>=',
						'<>',
						'<',
						'>',
						'=',
						'!=',
						'NOT[[:space:]]+IN',
						'IN',
						'NOT[[:space:]]+LIKE[[:space:]]+BINARY',
						'LIKE[[:space:]]+BINARY',
						'NOT[[:space:]]+LIKE',
						'LIKE',
						'IS[[:space:]]+NOT',
						'IS',
						'BETWEEN',
						'NOT[[:space]]+BETWEEN'
					);
					$stack[$level][$pnt[$level]]['comparator'] = $this->nextPart($parseString, '^(' . implode('|', $comparatorPatterns) . ')');
					if (strlen($stack[$level][$pnt[$level]]['comparator'])) {
						if (preg_match('/^CONCAT[[:space:]]*\\(/', $parseString)) {
							$this->nextPart($parseString, '^(CONCAT[[:space:]]?[(])');
							$values = array(
								'operator' => 'CONCAT',
								'args' => array()
							);
							$cnt = 0;
							while ($fieldName = $this->nextPart($parseString, '^([[:alnum:]._]+)')) {
								// Parse field name into field and table:
								$tableField = explode('.', $fieldName, 2);
								if (count($tableField) == 2) {
									$values['args'][$cnt]['table'] = $tableField[0];
									$values['args'][$cnt]['field'] = $tableField[1];
								} else {
									$values['args'][$cnt]['table'] = '';
									$values['args'][$cnt]['field'] = $tableField[0];
								}
								// Looking for comma:
								$this->nextPart($parseString, '^(,)');
								$cnt++;
							}
							// Look for ending parenthesis:
							$this->nextPart($parseString, '([)])');
							$stack[$level][$pnt[$level]]['value'] = $values;
						} else {
							if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('IN,NOT IN', $stack[$level][$pnt[$level]]['comparator']) && preg_match('/^[(][[:space:]]*SELECT[[:space:]]+/', $parseString)) {
								$this->nextPart($parseString, '^([(])');
								$stack[$level][$pnt[$level]]['subquery'] = $this->parseSELECT($parseString, $parameterReferences);
								// Seek to new position in parseString after parsing of the subquery
								$parseString = $stack[$level][$pnt[$level]]['subquery']['parseString'];
								unset($stack[$level][$pnt[$level]]['subquery']['parseString']);
								if (!$this->nextPart($parseString, '^([)])')) {
									return 'No ) parenthesis at end of subquery';
								}
							} else {
								if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('BETWEEN,NOT BETWEEN', $stack[$level][$pnt[$level]]['comparator'])) {
									$stack[$level][$pnt[$level]]['values'] = array();
									$stack[$level][$pnt[$level]]['values'][0] = $this->getValue($parseString);
									if (!$this->nextPart($parseString, '^(AND)')) {
										return $this->parseError('No AND operator found as expected in parseWhereClause()', $parseString);
									}
									$stack[$level][$pnt[$level]]['values'][1] = $this->getValue($parseString);
								} else {
									// Finding value for comparator:
									$stack[$level][$pnt[$level]]['value'] = &$this->getValueOrParameter($parseString, $stack[$level][$pnt[$level]]['comparator'], '', $parameterReferences);
									if ($this->parse_error) {
										return $this->parse_error;
									}
								}
							}
						}
					}
				}
				// Finished, increase pointer:
				$pnt[$level]++;
				// Checking if we are back to level 0 and we should still decrease level,
				// meaning we were probably parsing as subquery and should return here:
				if ($level === 0 && preg_match('/^[)]/', $parseString)) {
					// Return the stacks lowest level:
					return $stack[0];
				}
				// Checking if we are back to level 0 and we should still decrease level,
				// meaning we were probably parsing a subquery and should return here:
				if ($level === 0 && preg_match('/^[)]/', $parseString)) {
					// Return the stacks lowest level:
					return $stack[0];
				}
				// Checking if the current level is ended, in that case do stack management:
				while ($this->nextPart($parseString, '^([)])')) {
					$level--;
					// Decrease level:
					// Copy stack
					$stack[$level][$pnt[$level]]['sub'] = $stack[$level + 1];
					// Increase pointer of the new level
					$pnt[$level]++;
					// Make recursivity check:
					$loopExit++;
					if ($loopExit > 500) {
						return $this->parseError('More than 500 loops (in search for exit parenthesis), exiting prematurely in parseWhereClause()...', $parseString);
					}
				}
				// Detecting the operator for the next level:
				$op = $this->nextPart($parseString, '^(AND[[:space:]]+NOT|&&[[:space:]]+NOT|OR[[:space:]]+NOT|OR[[:space:]]+NOT|\\|\\|[[:space:]]+NOT|AND|&&|OR|\\|\\|)(\\(|[[:space:]]+)');
				if ($op) {
					// Normalize boolean operator
					$op = str_replace(array('&&', '||'), array('AND', 'OR'), $op);
					$stack[$level][$pnt[$level]]['operator'] = $op;
				} elseif (strlen($parseString)) {
					// Looking for stop-keywords:
					if ($stopRegex && ($this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))) {
						$this->lastStopKeyWord = strtoupper(str_replace(array(' ', TAB, CR, LF), '', $this->lastStopKeyWord));
						return $stack[0];
					} else {
						return $this->parseError('No operator, but parsing not finished in parseWhereClause().', $parseString);
					}
				}
			}
			// Make recursivity check:
			$loopExit++;
			if ($loopExit > 500) {
				return $this->parseError('More than 500 loops, exiting prematurely in parseWhereClause()...', $parseString);
			}
		}
		// Return the stacks lowest level:
		return $stack[0];
	}

	/**
	 * Parsing the WHERE clause fields in the "WHERE [$parseString] ..." part of a query into a multidimensional array.
	 * The success of this parsing determines if that part of the query is supported by TYPO3.
	 *
	 * @param string $parseString WHERE clause to parse. NOTICE: passed by reference!
	 * @param string $stopRegex Regular expressing to STOP parsing, eg. '^(GROUP BY|ORDER BY|LIMIT)([[:space:]]*)'
	 * @return mixed If successful parsing, returns an array, otherwise an error string.
	 */
	public function parseFieldDef(&$parseString, $stopRegex = '') {
		// Prepare variables:
		$parseString = $this->trimSQL($parseString);
		$this->lastStopKeyWord = '';
		$this->parse_error = '';
		$result = array();
		// Field type:
		if ($result['fieldType'] = $this->nextPart($parseString, '^(int|smallint|tinyint|mediumint|bigint|double|numeric|decimal|float|varchar|char|text|tinytext|mediumtext|longtext|blob|tinyblob|mediumblob|longblob)([[:space:],]+|\\()')) {
			// Looking for value:
			if (substr($parseString, 0, 1) == '(') {
				$parseString = substr($parseString, 1);
				if ($result['value'] = $this->nextPart($parseString, '^([^)]*)')) {
					$parseString = ltrim(substr($parseString, 1));
				} else {
					return $this->parseError('No end-parenthesis for value found in parseFieldDef()!', $parseString);
				}
			}
			// Looking for keywords
			while ($keyword = $this->nextPart($parseString, '^(DEFAULT|NOT[[:space:]]+NULL|AUTO_INCREMENT|UNSIGNED)([[:space:]]+|,|\\))')) {
				$keywordCmp = strtoupper(str_replace(array(' ', TAB, CR, LF), '', $keyword));
				$result['featureIndex'][$keywordCmp]['keyword'] = $keyword;
				switch ($keywordCmp) {
				case 'DEFAULT':
					$result['featureIndex'][$keywordCmp]['value'] = $this->getValue($parseString);
					break;
				}
			}
		} else {
			return $this->parseError('Field type unknown in parseFieldDef()!', $parseString);
		}
		return $result;
	}

	/************************************
	 *
	 * Parsing: Helper functions
	 *
	 ************************************/
	/**
	 * Strips off a part of the parseString and returns the matching part.
	 * Helper function for the parsing methods.
	 *
	 * @param string $parseString Parse string; if $regex finds anything the value of the first () level will be stripped of the string in the beginning. Further $parseString is left-trimmed (on success). Notice; parsestring is passed by reference.
	 * @param string $regex Regex to find a matching part in the beginning of the string. Rules: You MUST start the regex with "^" (finding stuff in the beginning of string) and the result of the first parenthesis is what will be returned to you (and stripped of the string). Eg. '^(AND|OR|&&)[[:space:]]+' will return AND, OR or && if found and having one of more whitespaces after it, plus shorten $parseString with that match and any space after (by ltrim())
	 * @param boolean $trimAll If set the full match of the regex is stripped of the beginning of the string!
	 * @return string The value of the first parenthesis level of the REGEX.
	 */
	protected function nextPart(&$parseString, $regex, $trimAll = FALSE) {
		$reg = array();
		// Adding space char because [[:space:]]+ is often a requirement in regex's
		if (preg_match('/' . $regex . '/i', $parseString . ' ', $reg)) {
			$parseString = ltrim(substr($parseString, strlen($reg[$trimAll ? 0 : 1])));
			return $reg[1];
		}
		// No match found
		return '';
	}

	/**
	 * Finds value or either named (:name) or question mark (?) parameter markers at the beginning
	 * of $parseString, returns result and strips it of parseString.
	 * This method returns a pointer to the parameter or value that was found. In case of a parameter
	 * the pointer is a reference to the corresponding item in array $parameterReferences.
	 *
	 * @param string $parseString The parseString
	 * @param string $comparator The comparator used before.
	 * @param string $mode The mode, e.g., "INDEX
	 * @param mixed The value (string/integer) or parameter (:name/?). Otherwise an array with error message in first key (0)
	 */
	protected function &getValueOrParameter(&$parseString, $comparator = '', $mode = '', array &$parameterReferences = array()) {
		$parameter = $this->nextPart($parseString, '^(\\:[[:alnum:]_]+|\\?)');
		if ($parameter === '?') {
			if (!isset($parameterReferences['?'])) {
				$parameterReferences['?'] = array();
			}
			$value = array('?');
			$parameterReferences['?'][] = &$value;
		} elseif ($parameter !== '') {
			// named parameter
			if (isset($parameterReferences[$parameter])) {
				// Use the same reference as last time we encountered this parameter
				$value = &$parameterReferences[$parameter];
			} else {
				$value = array($parameter);
				$parameterReferences[$parameter] = &$value;
			}
		} else {
			$value = $this->getValue($parseString, $comparator, $mode);
		}
		return $value;
	}

	/**
	 * Finds value in beginning of $parseString, returns result and strips it of parseString
	 *
	 * @param string $parseString The parseString, eg. "(0,1,2,3) ..." or "('asdf','qwer') ..." or "1234 ..." or "'My string value here' ...
	 * @param string $comparator The comparator used before. If "NOT IN" or "IN" then the value is expected to be a list of values. Otherwise just an integer (un-quoted) or string (quoted)
	 * @param string $mode The mode, eg. "INDEX
	 * @return mixed The value (string/integer). Otherwise an array with error message in first key (0)
	 */
	protected function getValue(&$parseString, $comparator = '', $mode = '') {
		$value = '';
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('NOTIN,IN,_LIST', strtoupper(str_replace(array(' ', LF, CR, TAB), '', $comparator)))) {
			// List of values:
			if ($this->nextPart($parseString, '^([(])')) {
				$listValues = array();
				$comma = ',';
				while ($comma == ',') {
					$listValues[] = $this->getValue($parseString);
					if ($mode === 'INDEX') {
						// Remove any length restriction on INDEX definition
						$this->nextPart($parseString, '^([(]\\d+[)])');
					}
					$comma = $this->nextPart($parseString, '^([,])');
				}
				$out = $this->nextPart($parseString, '^([)])');
				if ($out) {
					if ($comparator == '_LIST') {
						$kVals = array();
						foreach ($listValues as $vArr) {
							$kVals[] = $vArr[0];
						}
						return $kVals;
					} else {
						return $listValues;
					}
				} else {
					return array($this->parseError('No ) parenthesis in list', $parseString));
				}
			} else {
				return array($this->parseError('No ( parenthesis starting the list', $parseString));
			}
		} else {
			// Just plain string value, in quotes or not:
			// Quote?
			$firstChar = substr($parseString, 0, 1);
			switch ($firstChar) {
			case '"':
				$value = array($this->getValueInQuotes($parseString, '"'), '"');
				break;
			case '\'':
				$value = array($this->getValueInQuotes($parseString, '\''), '\'');
				break;
			default:
				$reg = array();
				if (preg_match('/^([[:alnum:]._-]+)/i', $parseString, $reg)) {
					$parseString = ltrim(substr($parseString, strlen($reg[0])));
					$value = array($reg[1]);
				}
				break;
			}
		}
		return $value;
	}

	/**
	 * Get value in quotes from $parseString.
	 * NOTICE: If a query being parsed was prepared for another database than MySQL this function should probably be changed
	 *
	 * @param string $parseString String from which to find value in quotes. Notice that $parseString is passed by reference and is shortend by the output of this function.
	 * @param string $quote The quote used; input either " or '
	 * @return string The value, passed through stripslashes() !
	 */
	protected function getValueInQuotes(&$parseString, $quote) {
		$parts = explode($quote, substr($parseString, 1));
		$buffer = '';
		foreach ($parts as $k => $v) {
			$buffer .= $v;
			$reg = array();
			preg_match('/\\\\$/', $v, $reg);
			if ($reg and strlen($reg[0]) % 2) {
				$buffer .= $quote;
			} else {
				$parseString = ltrim(substr($parseString, strlen($buffer) + 2));
				return $this->parseStripslashes($buffer);
			}
		}
	}

	/**
	 * Strip slashes function used for parsing
	 * NOTICE: If a query being parsed was prepared for another database than MySQL this function should probably be changed
	 *
	 * @param string $str Input string
	 * @return string Output string
	 */
	protected function parseStripslashes($str) {
		$search = array('\\\\', '\\\'', '\\"', '\0', '\n', '\r', '\Z');
		$replace = array('\\', '\'', '"', "\x00", "\x0a", "\x0d", "\x1a");

		return str_replace($search, $replace, $str);
	}

	/**
	 * Add slashes function used for compiling queries
	 * NOTICE: If a query being parsed was prepared for another database than MySQL this function should probably be changed
	 *
	 * @param string $str Input string
	 * @return string Output string
	 */
	protected function compileAddslashes($str) {
		$search = array('\\', '\'', '"', "\x00", "\x0a", "\x0d", "\x1a");
		$replace = array('\\\\', '\\\'', '\\"', '\0', '\n', '\r', '\Z');

		return str_replace($search, $replace, $str);
	}

	/**
	 * Setting the internal error message value, $this->parse_error and returns that value.
	 *
	 * @param string $msg Input error message
	 * @param string $restQuery Remaining query to parse.
	 * @return string Error message.
	 */
	protected function parseError($msg, $restQuery) {
		$this->parse_error = 'SQL engine parse ERROR: ' . $msg . ': near "' . substr($restQuery, 0, 50) . '"';
		return $this->parse_error;
	}

	/**
	 * Trimming SQL as preparation for parsing.
	 * ";" in the end is stripped of.
	 * White space is trimmed away around the value
	 * A single space-char is added in the end
	 *
	 * @param string $str Input string
	 * @return string Output string
	 */
	protected function trimSQL($str) {
		return rtrim(rtrim(trim($str), ';')) . ' ';
	}

	/*************************
	 *
	 * Compiling queries
	 *
	 *************************/
	/**
	 * Compiles an SQL query from components
	 *
	 * @param array $components Array of SQL query components
	 * @return string SQL query
	 * @see parseSQL()
	 */
	public function compileSQL($components) {
		switch ($components['type']) {
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
			$query = 'EXPLAIN ' . $this->compileSELECT($components);
			break;
		case 'DROPTABLE':
			$query = 'DROP TABLE' . ($components['ifExists'] ? ' IF EXISTS' : '') . ' ' . $components['TABLE'];
			break;
		case 'CREATETABLE':
			$query = $this->compileCREATETABLE($components);
			break;
		case 'ALTERTABLE':
			$query = $this->compileALTERTABLE($components);
			break;
		case 'TRUNCATETABLE':
			$query = $this->compileTRUNCATETABLE($components);
			break;
		}
		return $query;
	}

	/**
	 * Compiles a SELECT statement from components array
	 *
	 * @param array $components Array of SQL query components
	 * @return string SQL SELECT query
	 * @see parseSELECT()
	 */
	protected function compileSELECT($components) {
		// Initialize:
		$where = $this->compileWhereClause($components['WHERE']);
		$groupBy = $this->compileFieldList($components['GROUPBY']);
		$orderBy = $this->compileFieldList($components['ORDERBY']);
		$limit = $components['LIMIT'];
		// Make query:
		$query = 'SELECT ' . ($components['STRAIGHT_JOIN'] ? $components['STRAIGHT_JOIN'] . '' : '') . '
				' . $this->compileFieldList($components['SELECT']) . '
				FROM ' . $this->compileFromTables($components['FROM']) . (strlen($where) ? '
				WHERE ' . $where : '') . (strlen($groupBy) ? '
				GROUP BY ' . $groupBy : '') . (strlen($orderBy) ? '
				ORDER BY ' . $orderBy : '') . (strlen($limit) ? '
				LIMIT ' . $limit : '');
		return $query;
	}

	/**
	 * Compiles an UPDATE statement from components array
	 *
	 * @param array $components Array of SQL query components
	 * @return string SQL UPDATE query
	 * @see parseUPDATE()
	 */
	protected function compileUPDATE($components) {
		// Where clause:
		$where = $this->compileWhereClause($components['WHERE']);
		// Fields
		$fields = array();
		foreach ($components['FIELDS'] as $fN => $fV) {
			$fields[] = $fN . '=' . $fV[1] . $this->compileAddslashes($fV[0]) . $fV[1];
		}
		// Make query:
		$query = 'UPDATE ' . $components['TABLE'] . ' SET
				' . implode(',
				', $fields) . '
				' . (strlen($where) ? '
				WHERE ' . $where : '');
		return $query;
	}

	/**
	 * Compiles an INSERT statement from components array
	 *
	 * @param array $components Array of SQL query components
	 * @return string SQL INSERT query
	 * @see parseINSERT()
	 */
	protected function compileINSERT($components) {
		$values = array();
		if (isset($components['VALUES_ONLY']) && is_array($components['VALUES_ONLY'])) {
			$valuesComponents = $components['EXTENDED'] === '1' ? $components['VALUES_ONLY'] : array($components['VALUES_ONLY']);
			$tableFields = array();
		} else {
			$valuesComponents = $components['EXTENDED'] === '1' ? $components['FIELDS'] : array($components['FIELDS']);
			$tableFields = array_keys($valuesComponents[0]);
		}
		foreach ($valuesComponents as $valuesComponent) {
			$fields = array();
			foreach ($valuesComponent as $fV) {
				$fields[] = $fV[1] . $this->compileAddslashes($fV[0]) . $fV[1];
			}
			$values[] = '(' . implode(',
				', $fields) . ')';
		}
		// Make query:
		$query = 'INSERT INTO ' . $components['TABLE'];
		if (count($tableFields)) {
			$query .= '
				(' . implode(',
				', $tableFields) . ')';
		}
		$query .= '
			VALUES
			' . implode(',
			', $values);
		return $query;
	}

	/**
	 * Compiles an DELETE statement from components array
	 *
	 * @param array $components Array of SQL query components
	 * @return string SQL DELETE query
	 * @see parseDELETE()
	 */
	protected function compileDELETE($components) {
		// Where clause:
		$where = $this->compileWhereClause($components['WHERE']);
		// Make query:
		$query = 'DELETE FROM ' . $components['TABLE'] . (strlen($where) ? '
				WHERE ' . $where : '');
		return $query;
	}

	/**
	 * Compiles a CREATE TABLE statement from components array
	 *
	 * @param array $components Array of SQL query components
	 * @return string SQL CREATE TABLE query
	 * @see parseCREATETABLE()
	 */
	protected function compileCREATETABLE($components) {
		// Create fields and keys:
		$fieldsKeys = array();
		foreach ($components['FIELDS'] as $fN => $fCfg) {
			$fieldsKeys[] = $fN . ' ' . $this->compileFieldCfg($fCfg['definition']);
		}
		foreach ($components['KEYS'] as $kN => $kCfg) {
			if ($kN === 'PRIMARYKEY') {
				$fieldsKeys[] = 'PRIMARY KEY (' . implode(',', $kCfg) . ')';
			} elseif ($kN === 'UNIQUE') {
				$key = key($kCfg);
				$fields = current($kCfg);
				$fieldsKeys[] = 'UNIQUE KEY ' . $key . ' (' . implode(',', $fields) . ')';
			} else {
				$fieldsKeys[] = 'KEY ' . $kN . ' (' . implode(',', $kCfg) . ')';
			}
		}
		// Make query:
		$query = 'CREATE TABLE ' . $components['TABLE'] . ' (
			' . implode(',
			', $fieldsKeys) . '
			)' . ($components['tableType'] ? ' TYPE=' . $components['tableType'] : '');
		return $query;
	}

	/**
	 * Compiles an ALTER TABLE statement from components array
	 *
	 * @param array $components Array of SQL query components
	 * @return string SQL ALTER TABLE query
	 * @see parseALTERTABLE()
	 */
	protected function compileALTERTABLE($components) {
		// Make query:
		$query = 'ALTER TABLE ' . $components['TABLE'] . ' ' . $components['action'] . ' ' . ($components['FIELD'] ? $components['FIELD'] : $components['KEY']);
		// Based on action, add the final part:
		switch (strtoupper(str_replace(array(' ', TAB, CR, LF), '', $components['action']))) {
		case 'ADD':
			$query .= ' ' . $this->compileFieldCfg($components['definition']);
			break;
		case 'CHANGE':
			$query .= ' ' . $components['newField'] . ' ' . $this->compileFieldCfg($components['definition']);
			break;
		case 'DROP':

		case 'DROPKEY':
			break;
		case 'ADDKEY':

		case 'ADDPRIMARYKEY':

		case 'ADDUNIQUE':
			$query .= ' (' . implode(',', $components['fields']) . ')';
			break;
		case 'DEFAULTCHARACTERSET':
			$query .= $components['charset'];
			break;
		case 'ENGINE':
			$query .= '= ' . $components['engine'];
			break;
		}
		// Return query
		return $query;
	}

	/**
	 * Compiles a TRUNCATE TABLE statement from components array
	 *
	 * @param array $components Array of SQL query components
	 * @return string SQL TRUNCATE TABLE query
	 * @see parseTRUNCATETABLE()
	 */
	protected function compileTRUNCATETABLE(array $components) {
		// Make query:
		$query = 'TRUNCATE TABLE ' . $components['TABLE'];
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
	 * @param array $selectFields Array of select fields, (made with ->parseFieldList())
	 * @param boolean $compileComments Whether comments should be compiled
	 * @return string Select field string
	 * @see parseFieldList()
	 */
	public function compileFieldList($selectFields, $compileComments = TRUE) {
		// Prepare buffer variable:
		$fields = '';
		// Traverse the selectFields if any:
		if (is_array($selectFields)) {
			$outputParts = array();
			foreach ($selectFields as $k => $v) {
				// Detecting type:
				switch ($v['type']) {
				case 'function':
					$outputParts[$k] = $v['function'] . '(' . $v['func_content'] . ')';
					break;
				case 'flow-control':
					if ($v['flow-control']['type'] === 'CASE') {
						$outputParts[$k] = $this->compileCaseStatement($v['flow-control']);
					}
					break;
				case 'field':
					$outputParts[$k] = ($v['distinct'] ? $v['distinct'] : '') . ($v['table'] ? $v['table'] . '.' : '') . $v['field'];
					break;
				}
				// Alias:
				if ($v['as']) {
					$outputParts[$k] .= ' ' . $v['as_keyword'] . ' ' . $v['as'];
				}
				// Specifically for ORDER BY and GROUP BY field lists:
				if ($v['sortDir']) {
					$outputParts[$k] .= ' ' . $v['sortDir'];
				}
			}
			if ($compileComments && $selectFields[0]['comments']) {
				$fields = $selectFields[0]['comments'] . ' ';
			}
			$fields .= implode(', ', $outputParts);
		}
		return $fields;
	}

	/**
	 * Compiles a CASE ... WHEN flow-control construct based on input array (made with ->parseCaseStatement())
	 *
	 * @param array $components Array of case components, (made with ->parseCaseStatement())
	 * @return string Case when string
	 * @see parseCaseStatement()
	 */
	protected function compileCaseStatement(array $components) {
		$statement = 'CASE';
		if (isset($components['case_field'])) {
			$statement .= ' ' . $components['case_field'];
		} elseif (isset($components['case_value'])) {
			$statement .= ' ' . $components['case_value'][1] . $components['case_value'][0] . $components['case_value'][1];
		}
		foreach ($components['when'] as $when) {
			$statement .= ' WHEN ';
			$statement .= $this->compileWhereClause($when['when_value']);
			$statement .= ' THEN ';
			$statement .= $when['then_value'][1] . $when['then_value'][0] . $when['then_value'][1];
		}
		if (isset($components['else'])) {
			$statement .= ' ELSE ';
			$statement .= $components['else'][1] . $components['else'][0] . $components['else'][1];
		}
		$statement .= ' END';
		return $statement;
	}

	/**
	 * Compiles a "FROM [output] WHERE..:" table list based on input array (made with ->parseFromTables())
	 *
	 * @param array $tablesArray Array of table names, (made with ->parseFromTables())
	 * @return string Table name string
	 * @see parseFromTables()
	 */
	public function compileFromTables($tablesArray) {
		// Prepare buffer variable:
		$outputParts = array();
		// Traverse the table names:
		if (is_array($tablesArray)) {
			foreach ($tablesArray as $k => $v) {
				// Set table name:
				$outputParts[$k] = $v['table'];
				// Add alias AS if there:
				if ($v['as']) {
					$outputParts[$k] .= ' ' . $v['as_keyword'] . ' ' . $v['as'];
				}
				if (is_array($v['JOIN'])) {
					foreach ($v['JOIN'] as $join) {
						$outputParts[$k] .= ' ' . $join['type'] . ' ' . $join['withTable'];
						// Add alias AS if there:
						if (isset($join['as']) && $join['as']) {
							$outputParts[$k] .= ' ' . $join['as_keyword'] . ' ' . $join['as'];
						}
						$outputParts[$k] .= ' ON ';
						foreach ($join['ON'] as $condition) {
							if ($condition['operator'] !== '') {
								$outputParts[$k] .= ' ' . $condition['operator'] . ' ';
							}
							$outputParts[$k] .= $condition['left']['table'] ? $condition['left']['table'] . '.' : '';
							$outputParts[$k] .= $condition['left']['field'];
							$outputParts[$k] .= $condition['comparator'];
							$outputParts[$k] .= $condition['right']['table'] ? $condition['right']['table'] . '.' : '';
							$outputParts[$k] .= $condition['right']['field'];
						}
					}
				}
			}
		}
		// Return imploded buffer:
		return implode(', ', $outputParts);
	}

	/**
	 * Implodes an array of WHERE clause configuration into a WHERE clause.
	 *
	 * @param array $clauseArray WHERE clause configuration
	 * @return string WHERE clause as string.
	 * @see 	explodeWhereClause()
	 */
	public function compileWhereClause($clauseArray) {
		// Prepare buffer variable:
		$output = '';
		// Traverse clause array:
		if (is_array($clauseArray)) {
			foreach ($clauseArray as $k => $v) {
				// Set operator:
				$output .= $v['operator'] ? ' ' . $v['operator'] : '';
				// Look for sublevel:
				if (is_array($v['sub'])) {
					$output .= ' (' . trim($this->compileWhereClause($v['sub'])) . ')';
				} elseif (isset($v['func']) && $v['func']['type'] === 'EXISTS') {
					$output .= ' ' . trim($v['modifier']) . ' EXISTS (' . $this->compileSELECT($v['func']['subquery']) . ')';
				} else {
					if (isset($v['func']) && $v['func']['type'] === 'LOCATE') {
						$output .= ' ' . trim($v['modifier']) . ' LOCATE(';
						$output .= $v['func']['substr'][1] . $v['func']['substr'][0] . $v['func']['substr'][1];
						$output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
						$output .= isset($v['func']['pos']) ? ', ' . $v['func']['pos'][0] : '';
						$output .= ')';
					} elseif (isset($v['func']) && $v['func']['type'] === 'IFNULL') {
						$output .= ' ' . trim($v['modifier']) . ' IFNULL(';
						$output .= ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
						$output .= ', ' . $v['func']['default'][1] . $this->compileAddslashes($v['func']['default'][0]) . $v['func']['default'][1];
						$output .= ')';
					} elseif (isset($v['func']) && $v['func']['type'] === 'FIND_IN_SET') {
						$output .= ' ' . trim($v['modifier']) . ' FIND_IN_SET(';
						$output .= $v['func']['str'][1] . $v['func']['str'][0] . $v['func']['str'][1];
						$output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
						$output .= ')';
					} else {
						// Set field/table with modifying prefix if any:
						$output .= ' ' . trim(($v['modifier'] . ' ' . ($v['table'] ? $v['table'] . '.' : '') . $v['field']));
						// Set calculation, if any:
						if ($v['calc']) {
							$output .= $v['calc'] . $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1];
						}
					}
					// Set comparator:
					if ($v['comparator']) {
						$output .= ' ' . $v['comparator'];
						// Detecting value type; list or plain:
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('NOTIN,IN', strtoupper(str_replace(array(' ', TAB, CR, LF), '', $v['comparator'])))) {
							if (isset($v['subquery'])) {
								$output .= ' (' . $this->compileSELECT($v['subquery']) . ')';
							} else {
								$valueBuffer = array();
								foreach ($v['value'] as $realValue) {
									$valueBuffer[] = $realValue[1] . $this->compileAddslashes($realValue[0]) . $realValue[1];
								}
								$output .= ' (' . trim(implode(',', $valueBuffer)) . ')';
							}
						} else {
							if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('BETWEEN,NOT BETWEEN', $v['comparator'])) {
								$lbound = $v['values'][0];
								$ubound = $v['values'][1];
								$output .= ' ' . $lbound[1] . $this->compileAddslashes($lbound[0]) . $lbound[1];
								$output .= ' AND ';
								$output .= $ubound[1] . $this->compileAddslashes($ubound[0]) . $ubound[1];
							} else {
								if (isset($v['value']['operator'])) {
									$values = array();
									foreach ($v['value']['args'] as $fieldDef) {
										$values[] = ($fieldDef['table'] ? $fieldDef['table'] . '.' : '') . $fieldDef['field'];
									}
									$output .= ' ' . $v['value']['operator'] . '(' . implode(',', $values) . ')';
								} else {
									$output .= ' ' . $v['value'][1] . $this->compileAddslashes($v['value'][0]) . $v['value'][1];
								}
							}
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
	 * @param array $fieldCfg Field definition parts
	 * @return string Field definition string
	 */
	public function compileFieldCfg($fieldCfg) {
		// Set type:
		$cfg = $fieldCfg['fieldType'];
		// Add value, if any:
		if (strlen($fieldCfg['value'])) {
			$cfg .= '(' . $fieldCfg['value'] . ')';
		}
		// Add additional features:
		if (is_array($fieldCfg['featureIndex'])) {
			foreach ($fieldCfg['featureIndex'] as $featureDef) {
				$cfg .= ' ' . $featureDef['keyword'];
				// Add value if found:
				if (is_array($featureDef['value'])) {
					$cfg .= ' ' . $featureDef['value'][1] . $this->compileAddslashes($featureDef['value'][0]) . $featureDef['value'][1];
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
	 * @param string $part Part definition of string; "SELECT" = fieldlist (also ORDER BY and GROUP BY), "FROM" = table list, "WHERE" = Where clause.
	 * @param string $str SQL string to verify parsability of
	 * @return mixed Returns array with string 1 and 2 if error, otherwise FALSE
	 */
	public function debug_parseSQLpart($part, $str) {
		$retVal = FALSE;
		switch ($part) {
		case 'SELECT':
			$retVal = $this->debug_parseSQLpartCompare($str, $this->compileFieldList($this->parseFieldList($str)));
			break;
		case 'FROM':
			$retVal = $this->debug_parseSQLpartCompare($str, $this->compileFromTables($this->parseFromTables($str)));
			break;
		case 'WHERE':
			$retVal = $this->debug_parseSQLpartCompare($str, $this->compileWhereClause($this->parseWhereClause($str)));
			break;
		}
		return $retVal;
	}

	/**
	 * Compare two query strins by stripping away whitespace.
	 *
	 * @param string $str SQL String 1
	 * @param string $newStr SQL string 2
	 * @param boolean $caseInsensitive If TRUE, the strings are compared insensitive to case
	 * @return mixed Returns array with string 1 and 2 if error, otherwise FALSE
	 */
	public function debug_parseSQLpartCompare($str, $newStr, $caseInsensitive = FALSE) {
		if ($caseInsensitive) {
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

		if (strcmp(str_replace(array(' ', TAB, CR, LF), '', $this->trimSQL($str1)), str_replace(array(' ', TAB, CR, LF), '', $this->trimSQL($str2)))) {
			return array(
				str_replace(array(' ', TAB, CR, LF), ' ', $str),
				str_replace(array(' ', TAB, CR, LF), ' ', $newStr),
			);
		}
	}

	/**
	 * Performs the ultimate test of the parser: Direct a SQL query in; You will get it back (through the parsed and re-compiled) if no problems, otherwise the script will print the error and exit
	 *
	 * @param string $SQLquery SQL query
	 * @return string Query if all is well, otherwise exit.
	 */
	public function debug_testSQL($SQLquery) {
		// Getting result array:
		$parseResult = $this->parseSQL($SQLquery);
		// If result array was returned, proceed. Otherwise show error and exit.
		if (is_array($parseResult)) {
			// Re-compile query:
			$newQuery = $this->compileSQL($parseResult);
			// TEST the new query:
			$testResult = $this->debug_parseSQLpartCompare($SQLquery, $newQuery);
			// Return new query if OK, otherwise show error and exit:
			if (!is_array($testResult)) {
				return $newQuery;
			} else {
				debug(array('ERROR MESSAGE' => 'Input query did not match the parsed and recompiled query exactly (not observing whitespace)', 'TEST result' => $testResult), 'SQL parsing failed:');
				die;
			}
		} else {
			debug(array('query' => $SQLquery, 'ERROR MESSAGE' => $parseResult), 'SQL parsing failed:');
			die;
		}
	}

}


?>