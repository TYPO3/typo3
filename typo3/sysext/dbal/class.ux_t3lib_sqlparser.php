<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  (c) 2004-2009 Karsten Dambekalns <karsten@typo3.org>
*  (c) 2009 Xavier Perseguers <typo3@perseguers.ch>
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
 * PHP SQL engine
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Karsten Dambekalns <k.dambekalns@fishfarm.de>
 * @author	Xavier Perseguers <typo3@perseguers.ch>
 */


/**
 * PHP SQL engine / server
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class ux_t3lib_sqlparser extends t3lib_sqlparser {

	// START: Methods originally belonging to t3lib_sqlparser but copied and fixed here as they were not used in Core

	/**
	 * Parsing the tablenames in the "FROM [$parseString] WHERE" part of a query into an array.
	 * The success of this parsing determines if that part of the query is supported by TYPO3.
	 *
	 * @param	string		list of tables, eg. "pages, tt_content" or "pages A, pages B". NOTICE: passed by reference!
	 * @param	string		Regular expressing to STOP parsing, eg. '^(WHERE)([[:space:]]*)'
	 * @return	array		If successful parsing, returns an array, otherwise an error string.
	 * @see compileFromTables()
	 */
	public function parseFromTables(&$parseString, $stopRegex = '') {

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
			if ($stack[$pnt]['table'] = $this->nextPart($parseString,'^([[:alnum:]_]+)(,|[[:space:]]+)')) {
					// Looking for stop-keywords before fetching potential table alias:
				if ($stopRegex && ($this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))) {
					$this->lastStopKeyWord = strtoupper(str_replace(array(' ',"\t","\r","\n"), '', $this->lastStopKeyWord));
					return $stack;
				}
				if (!preg_match('/^(LEFT|RIGHT|JOIN|INNER)[[:space:]]+/i', $parseString)) {
					$stack[$pnt]['as_keyword'] = $this->nextPart($parseString,'^(AS[[:space:]]+)');
					$stack[$pnt]['as'] = $this->nextPart($parseString,'^([[:alnum:]_]+)[[:space:]]*');
				}
			} else return $this->parseError('No table name found as expected in parseFromTables()!', $parseString);

				// Looking for JOIN
			$joinCnt = 0;
			while ($join = $this->nextPart($parseString,'^(LEFT[[:space:]]+JOIN|LEFT[[:space:]]+OUTER[[:space:]]+JOIN|RIGHT[[:space:]]+JOIN|RIGHT[[:space:]]+OUTER[[:space:]]+JOIN|INNER[[:space:]]+JOIN|JOIN)[[:space:]]+')) {
				$stack[$pnt]['JOIN'][$joinCnt]['type'] = $join;
				if ($stack[$pnt]['JOIN'][$joinCnt]['withTable'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+', 1)) {
					if (!preg_match('/^ON[[:space:]]+/i', $parseString)) {
						$stack[$pnt]['JOIN'][$joinCnt]['as_keyword'] = $this->nextPart($parseString, '^(AS[[:space:]]+)');
						$stack[$pnt]['JOIN'][$joinCnt]['as'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
					}
					if (!$this->nextPart($parseString, '^(ON[[:space:]]+)')) {
						return $this->parseError('No join condition found in parseFromTables()!', $parseString);
					}
					$field1 = $this->nextPart($parseString,'^([[:alnum:]_.]+)[[:space:]]*=[[:space:]]*', 1);
					$field2 = $this->nextPart($parseString,'^([[:alnum:]_.]+)[[:space:]]+');
					if ($field1 && $field2) {

						// Explode fields into field and table:
						$tableField = explode('.', $field1, 2);
						$field1 = array();
						if (count($tableField) != 2) {
							$field1['table'] = '';
							$field1['field'] = $tableField[0];
						} else {
							$field1['table'] = $tableField[0];
							$field1['field'] = $tableField[1];
						}
						$tableField = explode('.', $field2, 2);
						$field2 = array();
						if (count($tableField) != 2) {
							$field2['table'] = '';
							$field2['field'] = $tableField[0];
						} else {
							$field2['table'] = $tableField[0];
							$field2['field'] = $tableField[1];
						}
						$stack[$pnt]['JOIN'][$joinCnt]['ON'] = array($field1, $field2);
						$joinCnt++;
					} else return $this->parseError('No join fields found in parseFromTables()!', $parseString);
				} else return $this->parseError('No join table found in parseFromTables()!', $parseString);
			}

				// Looking for stop-keywords:
			if ($stopRegex && $this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex)) {
				$this->lastStopKeyWord = strtoupper(str_replace(array(' ',"\t","\r","\n"), '', $this->lastStopKeyWord));
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
	 * @param	string		WHERE clause to parse. NOTICE: passed by reference!
	 * @param	string		Regular expressing to STOP parsing, eg. '^(GROUP BY|ORDER BY|LIMIT)([[:space:]]*)'
	 * @return	mixed		If successful parsing, returns an array, otherwise an error string.
	 */
	public function parseWhereClause(&$parseString, $stopRegex = '') {

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
			if ($newLevel == '(') {			// If new level is started, manage stack/pointers:
				$level++;					// Increase level
				$pnt[$level] = 0;			// Reset pointer for this level
				$stack[$level] = array();	// Reset stack for this level
			} else {	// If no new level is started, just parse the current level:

					// Find "modifier", eg. "NOT or !"
				$stack[$level][$pnt[$level]]['modifier'] = trim($this->nextPart($parseString, '^(!|NOT[[:space:]]+)'));

					// See if condition is EXISTS with a subquery
				if (preg_match('/^EXISTS[[:space:]]*[(]/', $parseString)) {
					$stack[$level][$pnt[$level]]['func']['type'] = $this->nextPart($parseString, '^(EXISTS)');
					$this->nextPart($parseString, '^([(])');
					$stack[$level][$pnt[$level]]['func']['subquery'] = $this->parseSELECT($parseString);
						// Seek to new position in parseString after parsing of the subquery
					$parseString = $stack[$level][$pnt[$level]]['func']['subquery']['parseString'];
					unset($stack[$level][$pnt[$level]]['func']['subquery']['parseString']);
					if (!$this->nextPart($parseString, '^([)])')) {
						return 'No ) parenthesis at end of subquery';
 					}
 				} else {

	 					// Support calculated value only for:
						// - "&" (boolean AND)
						// - "+" (addition)
						// - "-" (substraction)
						// - "*" (multiplication)
						// - "/" (division)
						// - "%" (modulo)
					$calcOperators = '&|\+|-|\*|\/|%';
	
						// Fieldname:
					if ($fieldName = $this->nextPart($parseString, '^([[:alnum:]._]+)([[:space:]]+|' . $calcOperators . '|<=|>=|<|>|=|!=|IS)')) {
	
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
	
						// Find "comparator":
					$stack[$level][$pnt[$level]]['comparator'] = $this->nextPart($parseString, '^(<=|>=|<|>|=|!=|NOT[[:space:]]+IN|IN|NOT[[:space:]]+LIKE|LIKE|IS[[:space:]]+NOT|IS)');
					if (strlen($stack[$level][$pnt[$level]]['comparator'])) {
						if (preg_match('/^CONCAT[[:space:]]*\(/', $parseString)) {
							$this->nextPart($parseString, '^(CONCAT[[:space:]]?[(])');
							$values = array(
								'operator' => 'CONCAT',
								'args' => array(),
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
						} else if (t3lib_div::inList('IN,NOT IN', $stack[$level][$pnt[$level]]['comparator']) && preg_match('/^[(][[:space:]]*SELECT[[:space:]]+/', $parseString)) {
							$this->nextPart($parseString, '^([(])');
							$stack[$level][$pnt[$level]]['subquery'] = $this->parseSELECT($parseString);
								// Seek to new position in parseString after parsing of the subquery
							$parseString = $stack[$level][$pnt[$level]]['subquery']['parseString'];
							unset($stack[$level][$pnt[$level]]['subquery']['parseString']);
							if (!$this->nextPart($parseString, '^([)])')) {
								return 'No ) parenthesis at end of subquery';
							}
						} else {
								// Finding value for comparator:
							$stack[$level][$pnt[$level]]['value'] = $this->getValue($parseString, $stack[$level][$pnt[$level]]['comparator']);
							if ($this->parse_error)	{
								return $this->parse_error;
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
				while ($this->nextPart($parseString,'^([)])')) {
					$level--;		// Decrease level:
					$stack[$level][$pnt[$level]]['sub'] = $stack[$level+1];		// Copy stack
					$pnt[$level]++;	// Increase pointer of the new level

						// Make recursivity check:
					$loopExit++;
					if ($loopExit > 500) {
						return $this->parseError('More than 500 loops (in search for exit parenthesis), exiting prematurely in parseWhereClause()...', $parseString);
					}
				}

					// Detecting the operator for the next level:
				$op = $this->nextPart($parseString, '^(AND[[:space:]]+NOT|&&[[:space:]]+NOT|OR[[:space:]]+NOT|OR[[:space:]]+NOT|\|\|[[:space:]]+NOT|AND|&&|OR|\|\|)(\(|[[:space:]]+)');
				if ($op) {
						// Normalize boolean operator
					$op = str_replace(array('&&', '||'), array('AND', 'OR'), $op);
					$stack[$level][$pnt[$level]]['operator'] = $op;
				} elseif (strlen($parseString)) {

						// Looking for stop-keywords:
					if ($stopRegex && $this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex)) {
						$this->lastStopKeyWord = strtoupper(str_replace(array(' ',"\t","\r","\n"), '', $this->lastStopKeyWord));
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
	 * Compiles a "SELECT [output] FROM..:" field list based on input array (made with ->parseFieldList())
	 * Can also compile field lists for ORDER BY and GROUP BY.
	 *
	 * @param	array		Array of select fields, (made with ->parseFieldList())
	 * @param	boolean		Whether comments should be compiled
	 * @return	string		Select field string
	 * @see parseFieldList()
	 */
	public function compileFieldList($selectFields, $compileComments = TRUE) {
			// TODO: Handle SQL hints in comments according to current DBMS
		return parent::compileFieldList($selectFields, FALSE);
	}

	/**
	 * Compiles a "FROM [output] WHERE..:" table list based on input array (made with ->parseFromTables())
	 *
	 * @param	array		Array of table names, (made with ->parseFromTables())
	 * @return	string		Table name string
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
						$outputParts[$k] .= ($join['ON'][0]['table']) ? $join['ON'][0]['table'] . '.' : '';
						$outputParts[$k] .= $join['ON'][0]['field'];
						$outputParts[$k] .= '=';
						$outputParts[$k] .= ($join['ON'][1]['table']) ? $join['ON'][1]['table'] . '.' : '';
						$outputParts[$k] .= $join['ON'][1]['field'];
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
	 * @param	array		WHERE clause configuration
	 * @return	string		WHERE clause as string.
	 * @see	explodeWhereClause()
	 */
	protected function nativeCompileWhereClause($clauseArray) {

			// Prepare buffer variable:
		$output = '';

			// Traverse clause array:
		if (is_array($clauseArray)) {
			foreach ($clauseArray as $k => $v) {

					// Set operator:
				$output .= $v['operator'] ? ' ' . $v['operator'] : '';

					// Look for sublevel:
				if (is_array($v['sub'])) {
					$output .= ' (' . trim($this->nativeCompileWhereClause($v['sub'])) . ')';
				} elseif (isset($v['func'])) {
					$output .= ' ' . trim($v['modifier']) . ' ' . $v['func']['type'] . ' (' . $this->compileSELECT($v['func']['subquery']) . ')';
				} else {

						// Set field/table with modifying prefix if any:
					$output .= ' ' . trim($v['modifier'] . ' ' . ($v['table'] ? $v['table'] . '.' : '') . $v['field']);

						// Set calculation, if any:
					if ($v['calc']) {
						$output .= $v['calc'] . $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1];
					}

						// Set comparator:
					if ($v['comparator']) {
						$output .= ' ' . $v['comparator'];

							// Detecting value type; list or plain:
						if (t3lib_div::inList('NOTIN,IN', strtoupper(str_replace(array(' ', "\t", "\r", "\n"), '', $v['comparator'])))) {
							if (isset($v['subquery'])) {
								$output .= ' (' . $this->compileSELECT($v['subquery']) . ')';	
							} else {
								$valueBuffer = array();
								foreach ($v['value'] as $realValue) {
									$valueBuffer[] = $realValue[1] . $this->compileAddslashes($realValue[0]) . $realValue[1];
								}
								$output .= ' (' . trim(implode(',', $valueBuffer)) . ')';
							}
						} else if (isset($v['value']['operator'])) {
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

			// Return output buffer:
		return $output;
	}
	
	// END: Methods originally belonging to t3lib_sqlparser but copied and fixed here as they were not used in Core
	
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
	public function compileSQL($components) {
		switch($components['type']) {
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
				$query = $this->compileDROPTABLE($components);
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
	 * Compiles an INSERT statement from components array
	 *
	 * @param array Array of SQL query components
	 * @return string SQL INSERT query
	 * @see parseINSERT()
	 */
	function compileINSERT($components) {
		switch ((string)$GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
			case 'native':
				$query = parent::compileINSERT($components);
				break;
			case 'adodb':
				if (isset($components['VALUES_ONLY']) && is_array($components['VALUES_ONLY'])) {
					$fields = $GLOBALS['TYPO3_DB']->cache_fieldType[$components['TABLE']];
					$fc = 0;
					foreach ($fields as $fn => $fd) {
						$query[$fn] = $components['VALUES_ONLY'][$fc++][0];
					}
				} else {
						// Initialize:
					foreach ($components['FIELDS'] as $fN => $fV) {
						$query[$fN]=$fV[0];
					}
				}
				break;
		}

		return $query;
	}

	/**
	 * Compiles a DROP TABLE statement from components array
	 *
	 * @param array Array of SQL query components
	 * @return string SQL DROP TABLE query
	 * @see compileSQL()
	 */
	private function compileDROPTABLE($components) {
		switch ((string)$GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type'])	{
			case 'native':
				$query = 'DROP TABLE' . ($components['ifExists'] ? ' IF EXISTS' : '') . ' ' . $components['TABLE'];
				break;
			case 'adodb':
				$handlerKey = $GLOBALS['TYPO3_DB']->handler_getFromTableList($components['TABLE']);
				$tableName = $GLOBALS['TYPO3_DB']->quoteName($components['TABLE'], $handlerKey, TRUE);
				$query = $GLOBALS['TYPO3_DB']->handlerInstance[$handlerKey]->DataDictionary->DropTableSQL($tableName);
				break;
		}

		return $query;
	}

	/**
	 * Compiles a CREATE TABLE statement from components array
	 *
	 * @param	array		Array of SQL query components
	 * @return	array		array with SQL CREATE TABLE/INDEX command(s)
	 * @see parseCREATETABLE()
	 */
	protected function compileCREATETABLE($components) {
			// Execute query (based on handler derived from the TABLE name which we actually know for once!)
		switch ((string)$GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->handler_getFromTableList($components['TABLE'])]['type']) {
			case 'native':
				$query[] = parent::compileCREATETABLE($components);
				break;
			case 'adodb':
					// Create fields and keys:
				$fieldsKeys = array();
				$indexKeys = array();

				foreach ($components['FIELDS'] as $fN => $fCfg) {
					$handlerKey = $GLOBALS['TYPO3_DB']->handler_getFromTableList($components['TABLE']);
					$fieldsKeys[$fN] = $GLOBALS['TYPO3_DB']->quoteName($fN, $handlerKey, TRUE) . ' ' . $this->compileFieldCfg($fCfg['definition']);
				}

				if (isset($components['KEYS']) && is_array($components['KEYS'])) {
					foreach($components['KEYS'] as $kN => $kCfg) {
						if ($kN === 'PRIMARYKEY') {
							foreach ($kCfg as $n => $field) {
								$fieldsKeys[$field] .= ' PRIMARY';
							}
						} elseif ($kN === 'UNIQUE') {
							foreach ($kCfg as $n => $field) {
								$indexKeys = array_merge($indexKeys, $GLOBALS['TYPO3_DB']->handlerInstance[$GLOBALS['TYPO3_DB']->handler_getFromTableList($components['TABLE'])]->DataDictionary->CreateIndexSQL($n, $components['TABLE'], $field, array('UNIQUE')));
							}
						} else {
							$indexKeys = array_merge($indexKeys, $GLOBALS['TYPO3_DB']->handlerInstance[$GLOBALS['TYPO3_DB']->handler_getFromTableList($components['TABLE'])]->DataDictionary->CreateIndexSQL($components['TABLE'] . '_' . $kN, $components['TABLE'], $kCfg));
						}
					}
				}

					// Generally create without OID on PostgreSQL
				$tableOptions = array('postgres' => 'WITHOUT OIDS');

					// Fetch table/index generation query:
				$tableName = $GLOBALS['TYPO3_DB']->quoteName($components['TABLE'], NULL, TRUE);
				$query = array_merge($GLOBALS['TYPO3_DB']->handlerInstance[$GLOBALS['TYPO3_DB']->lastHandlerKey]->DataDictionary->CreateTableSQL($tableName, implode(',' . chr(10), $fieldsKeys), $tableOptions), $indexKeys);
				break;
		}

		return $query;
	}

	/**
	 * Compiles an ALTER TABLE statement from components array
	 *
	 * @param array Array of SQL query components
	 * @return string SQL ALTER TABLE query
	 * @see parseALTERTABLE()
	 */
	protected function compileALTERTABLE($components) {
			// Execute query (based on handler derived from the TABLE name which we actually know for once!)
		switch ((string)$GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
			case 'native':
				$query[] = parent::compileALTERTABLE($components);
				break;
			case 'adodb':
				$tableName = $GLOBALS['TYPO3_DB']->quoteName($components['TABLE'], NULL, TRUE);
				$fieldName = $GLOBALS['TYPO3_DB']->quoteName($components['FIELD'], NULL, TRUE);
				switch (strtoupper(str_replace(array(' ', "\n", "\r", "\t"), '', $components['action']))) {
					case 'ADD':
						$query = $GLOBALS['TYPO3_DB']->handlerInstance[$GLOBALS['TYPO3_DB']->lastHandlerKey]->DataDictionary->AddColumnSQL($tableName, $fieldName . ' ' . $this->compileFieldCfg($components['definition']));
						break;
					case 'CHANGE':
						$query = $GLOBALS['TYPO3_DB']->handlerInstance[$GLOBALS['TYPO3_DB']->lastHandlerKey]->DataDictionary->AlterColumnSQL($tableName, $fieldName . ' ' . $this->compileFieldCfg($components['definition']));
						break;
					case 'DROP':
					case 'DROPKEY':
						break;
					case 'ADDKEY':
					case 'ADDPRIMARYKEY':
						$query .= ' (' . implode(',', $components['fields']) . ')';
						break;
				}
				break;
		}

		return $query;
	}

	/**
	 * Compile field definition
	 *
	 * @param	array		Field definition parts
	 * @return	string		Field definition string
	 */
	public function compileFieldCfg($fieldCfg) {
		switch ((string)$GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
			case 'native':
				$cfg = parent::compileFieldCfg($fieldCfg);
				break;
			case 'adodb':
					// Set type:
				$type = $GLOBALS['TYPO3_DB']->MySQLMetaType($fieldCfg['fieldType']);
				$cfg = $type;

					// Add value, if any:
				if (strlen($fieldCfg['value']) && (in_array($type, array('C', 'C2')))) {
					$cfg .= ' '.$fieldCfg['value'];
				} elseif (!isset($fieldCfg['value']) && (in_array($type, array('C', 'C2')))) {
					$cfg .= ' 255'; // add 255 as length for varchar without specified length (e.g. coming from tinytext, tinyblob)
				}

					// Add additional features:
				$noQuote = TRUE;
				if (is_array($fieldCfg['featureIndex'])) {

						// MySQL assigns DEFAULT value automatically if NOT NULL, fake this here
						// numeric fields get 0 as default, other fields an empty string
					if (isset($fieldCfg['featureIndex']['NOTNULL']) && !isset($fieldCfg['featureIndex']['DEFAULT']) && !isset($fieldCfg['featureIndex']['AUTO_INCREMENT'])) {
						switch ($type) {
							case 'I8':
							case 'F':
							case 'N':
								$fieldCfg['featureIndex']['DEFAULT'] = array('keyword' => 'DEFAULT', 'value' => array('0', ''));
								break;
							default:
								$fieldCfg['featureIndex']['DEFAULT'] = array('keyword' => 'DEFAULT', 'value' => array('', '\''));
						}
					}

					foreach ($fieldCfg['featureIndex'] as $feature => $featureDef) {
						switch (TRUE) {
								// unsigned only for mysql, as it is mysql specific
							case ($feature === 'UNSIGNED' && !$GLOBALS['TYPO3_DB']->runningADOdbDriver('mysql')):
								// auto_increment is removed, it is handled by (emulated) sequences
							case ($feature === 'AUTO_INCREMENT'):
								// never add NOT NULL if running on Oracle and we have an empty string as default
							case ($feature === 'NOTNULL' && $GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8')):
								continue;
							case ($feature === 'NOTNULL'):
								$cfg .= ' NOTNULL';
								break;
							default:
								$cfg .= ' ' . $featureDef['keyword'];
						}

							// Add value if found:
						if (is_array($featureDef['value'])) {
							if ($featureDef['value'][0] === '') {
								$cfg .= ' "\'\'"';
							} else {
								$cfg .= ' ' . $featureDef['value'][1] . $this->compileAddslashes($featureDef['value'][0]) . $featureDef['value'][1];
								if (!is_numeric($featureDef['value'][0])) {
									$noQuote = FALSE;
								}
							}
						}
					}
				}
				if ($noQuote) {
					$cfg .= ' NOQUOTE';
				}
				break;
		}

			// Return field definition string:
		return $cfg;
	}

	/**
	 * Checks if the submitted feature index contains a default value definition and the default value
	 *
	 * @param array $featureIndex A feature index as produced by parseFieldDef()
	 * @return boolean
	 * @see t3lib_sqlparser::parseFieldDef()
	 */
	public function checkEmptyDefaultValue($featureIndex) {
		if (is_array($featureIndex['DEFAULT']['value'])) {
			if (!is_numeric($featureIndex['DEFAULT']['value'][0]) && empty($featureIndex['DEFAULT']['value'][0])) {
				return TRUE;
			} else {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Implodes an array of WHERE clause configuration into a WHERE clause.
	 *
	 * DBAL-specific: The only(!) handled "calc" operators supported by parseWhereClause() are:
	 * - the bitwise logical and (&)
	 * - the addition (+)
	 * - the substraction (-)
	 * - the multiplication (*)
	 * - the division (/)
	 * - the modulo (%)
	 *
	 * @param array WHERE clause configuration
	 * @return string WHERE clause as string.
	 * @see	t3lib_sqlparser::parseWhereClause()
	 */
	public function compileWhereClause($clauseArray, $functionMapping = TRUE) {
		switch ((string)$GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
			case 'native':
				$output = $this->nativeCompileWhereClause($clauseArray);
				break;
			case 'adodb':
					// Prepare buffer variable:
				$output = '';

					// Traverse clause array:
				if (is_array($clauseArray)) {
					foreach($clauseArray as $k => $v) {

							// Set operator:
						$output .= $v['operator'] ? ' ' . $v['operator'] : '';

							// Look for sublevel:
						if (is_array($v['sub'])) {
							$output .= ' (' . trim($this->compileWhereClause($v['sub'], $functionMapping)) . ')';
						} elseif (isset($v['func'])) {
							$output .= ' ' . trim($v['modifier']) . ' ' . $v['func']['type'] . ' (' . $this->compileSELECT($v['func']['subquery']) . ')';
						} else {

								// Set field/table with modifying prefix if any:
							$output .= ' ' . trim($v['modifier']) . ' ';

								// DBAL-specific: Set calculation, if any:
							if ($v['calc'] === '&' && $functionMapping) {
								switch(TRUE) {
									case $GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8'):
											// Oracle only knows BITAND(x,y) - sigh
										$output .= 'BITAND(' . trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']) . ',' . $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1] . ')';
										break;
									default:
											// MySQL, MS SQL Server, PostgreSQL support the &-syntax
										$output .= trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']) . $v['calc'] . $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1];
										break;
								}
							} elseif ($v['calc']) {
								$output .= trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']) . $v['calc'];
								if (isset($v['calc_table'])) {
									$output .= trim(($v['calc_table'] ? $v['calc_table'] . '.' : '') . $v['calc_field']);
								} else {
									$output .= $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1];
								}
							} elseif (!($GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8') && $v['comparator'] === 'LIKE' && $functionMapping)) {
								$output .= trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']);
							}

								// Set comparator:
							if ($v['comparator']) {
								switch (TRUE) {
									case ($GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8') && $v['comparator'] === 'LIKE' && $functionMapping):
												// Oracle cannot handle LIKE on CLOB fields - sigh
											if (isset($v['value']['operator'])) {
												$values = array();
												foreach ($v['value']['args'] as $fieldDef) {
													$values[] = ($fieldDef['table'] ? $fieldDef['table'] . '.' : '') . $fieldDef['field'];
												}
												$compareValue = ' ' . $v['value']['operator'] . '(' . implode(',', $values) . ')';
											} else {
												$compareValue = $v['value'][1] . $this->compileAddslashes(trim($v['value'][0], '%')) . $v['value'][1]; 
											}
											$output .= '(dbms_lob.instr(' . trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']) . ', ' . $compareValue . ',1,1) > 0)';
										break;
									default:
										$output .= ' ' . $v['comparator'];

											// Detecting value type; list or plain:
										if (t3lib_div::inList('NOTIN,IN', strtoupper(str_replace(array(' ', "\t", "\r", "\n"), '', $v['comparator'])))) {
											if (isset($v['subquery'])) {
												$output .= ' (' . $this->compileSELECT($v['subquery']) . ')';	
											} else {
												$valueBuffer = array();
												foreach ($v['value'] as $realValue) {
													$valueBuffer[] = $realValue[1] . $this->compileAddslashes($realValue[0]) . $realValue[1];
												}
												$output .= ' (' . trim(implode(',', $valueBuffer)) . ')';
											}
										} else if (isset($v['value']['operator'])) {
											$values = array();
											foreach ($v['value']['args'] as $fieldDef) {
												$values[] = ($fieldDef['table'] ? $fieldDef['table'] . '.' : '') . $fieldDef['field'];
											}
											$output .= ' ' . $v['value']['operator'] . '(' . implode(',', $values) . ')';
										} else {
											$output .= ' ' . $v['value'][1] . $this->compileAddslashes($v['value'][0]) . $v['value'][1];
										}
										break;
								}
							}
						}
					}
				}
				break;
		}

		return $output;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/class.ux_t3lib_sqlparser.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/class.ux_t3lib_sqlparser.php']);
}

?>