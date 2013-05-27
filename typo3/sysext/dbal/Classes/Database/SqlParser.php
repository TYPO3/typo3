<?php
namespace TYPO3\CMS\Dbal\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2004-2013 Karsten Dambekalns <karsten@typo3.org>
 *  (c) 2009-2013 Xavier Perseguers <xavier@typo3.org>
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
 * PHP SQL engine / server
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author 	Karsten Dambekalns <karsten@typo3.org>
 * @author 	Xavier Perseguers <xavier@typo3.org>
 */
class SqlParser extends \TYPO3\CMS\Core\Database\SqlParser {

	/**
	 * Gets value in quotes from $parseString.
	 *
	 * @param string $parseString String from which to find value in quotes. Notice that $parseString is passed by reference and is shortened by the output of this function.
	 * @param string $quote The quote used; input either " or '
	 * @return string The value, passed through parseStripslashes()!
	 */
	protected function getValueInQuotes(&$parseString, $quote) {
		switch ((string) $GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
		case 'adodb':
			if ($GLOBALS['TYPO3_DB']->runningADOdbDriver('mssql')) {
				$value = $this->getValueInQuotesMssql($parseString, $quote);
			} else {
				$value = parent::getValueInQuotes($parseString, $quote);
			}
			break;
		default:
			$value = parent::getValueInQuotes($parseString, $quote);
			break;
		}
		return $value;
	}

	/**
	 * Gets value in quotes from $parseString. This method targets MSSQL exclusively.
	 *
	 * @param string $parseString String from which to find value in quotes. Notice that $parseString is passed by reference and is shortened by the output of this function.
	 * @param $quote The quote used; input either " or '
	 * @return string
	 */
	protected function getValueInQuotesMssql(&$parseString, $quote) {
		$previousIsQuote = FALSE;
		$inQuote = FALSE;
		// Go through the whole string
		for ($c = 0; $c < strlen($parseString); $c++) {
			// If the parsed string character is the quote string
			if ($parseString[$c] === $quote) {
				// If we are already in a quote
				if ($inQuote) {
					// Was the previous a quote?
					if ($previousIsQuote) {
						// If yes, replace it by a \
						$parseString[$c - 1] = '\\';
					}
					// Invert the state
					$previousIsQuote = !$previousIsQuote;
				} else {
					// So we are in a quote since now
					$inQuote = TRUE;
				}
			} elseif ($inQuote && $previousIsQuote) {
				$inQuote = FALSE;
				$previousIsQuote = FALSE;
			} else {
				$previousIsQuote = FALSE;
			}
		}
		$parts = explode($quote, substr($parseString, 1));
		$buffer = '';
		foreach ($parts as $k => $v) {
			$buffer .= $v;
			$reg = array();
			preg_match('/\\\\$/', $v, $reg);
			if ($reg && strlen($reg[0]) % 2) {
				$buffer .= $quote;
			} else {
				$parseString = ltrim(substr($parseString, strlen($buffer) + 2));
				return $this->parseStripslashes($buffer);
			}
		}
	}

	/**
	 * Compiles a "SELECT [output] FROM..:" field list based on input array (made with ->parseFieldList())
	 * Can also compile field lists for ORDER BY and GROUP BY.
	 *
	 * @param 	array		Array of select fields, (made with ->parseFieldList())
	 * @param 	boolean		Whether comments should be compiled
	 * @param 	boolean		Whether function mapping should take place
	 * @return 	string		Select field string
	 * @see parseFieldList()
	 */
	public function compileFieldList($selectFields, $compileComments = TRUE, $functionMapping = TRUE) {
		switch ((string) $GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
		case 'native':
			$output = parent::compileFieldList($selectFields, $compileComments);
			break;
		case 'adodb':
			$output = '';
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
							$outputParts[$k] = $this->compileCaseStatement($v['flow-control'], $functionMapping);
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
				// TODO: Handle SQL hints in comments according to current DBMS
				if (FALSE && $selectFields[0]['comments']) {
					$output = $selectFields[0]['comments'] . ' ';
				}
				$output .= implode(', ', $outputParts);
			}
			break;
		}
		return $output;
	}

	/**
	 * Compiles a CASE ... WHEN flow-control construct based on input array (made with ->parseCaseStatement())
	 *
	 * @param 	array		Array of case components, (made with ->parseCaseStatement())
	 * @param 	boolean		Whether function mapping should take place
	 * @return 	string		case when string
	 * @see parseCaseStatement()
	 */
	protected function compileCaseStatement(array $components, $functionMapping = TRUE) {
		switch ((string) $GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
		case 'native':
			$output = parent::compileCaseStatement($components);
			break;
		case 'adodb':
			$statement = 'CASE';
			if (isset($components['case_field'])) {
				$statement .= ' ' . $components['case_field'];
			} elseif (isset($components['case_value'])) {
				$statement .= ' ' . $components['case_value'][1] . $components['case_value'][0] . $components['case_value'][1];
			}
			foreach ($components['when'] as $when) {
				$statement .= ' WHEN ';
				$statement .= $this->compileWhereClause($when['when_value'], $functionMapping);
				$statement .= ' THEN ';
				$statement .= $when['then_value'][1] . $when['then_value'][0] . $when['then_value'][1];
			}
			if (isset($components['else'])) {
				$statement .= ' ELSE ';
				$statement .= $components['else'][1] . $components['else'][0] . $components['else'][1];
			}
			$statement .= ' END';
			$output = $statement;
			break;
		}
		return $output;
	}

	/**
	 * Add slashes function used for compiling queries
	 * This method overrides the method from \TYPO3\CMS\Core\Database\SqlParser because
	 * the input string is already properly escaped.
	 *
	 * @param 	string		Input string
	 * @return 	string		Output string
	 */
	protected function compileAddslashes($str) {
		return $str;
	}

	/*************************
	 *
	 * Compiling queries
	 *
	 *************************/
	/**
	 * Compiles an INSERT statement from components array
	 *
	 * @param array Array of SQL query components
	 * @return string SQL INSERT query / array
	 * @see parseINSERT()
	 */
	protected function compileINSERT($components) {
		switch ((string) $GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
		case 'native':
			$query = parent::compileINSERT($components);
			break;
		case 'adodb':
			$values = array();
			if (isset($components['VALUES_ONLY']) && is_array($components['VALUES_ONLY'])) {
				$valuesComponents = $components['EXTENDED'] === '1' ? $components['VALUES_ONLY'] : array($components['VALUES_ONLY']);
				$tableFields = array_keys($GLOBALS['TYPO3_DB']->cache_fieldType[$components['TABLE']]);
			} else {
				$valuesComponents = $components['EXTENDED'] === '1' ? $components['FIELDS'] : array($components['FIELDS']);
				$tableFields = array_keys($valuesComponents[0]);
			}
			foreach ($valuesComponents as $valuesComponent) {
				$fields = array();
				$fc = 0;
				foreach ($valuesComponent as $fV) {
					$fields[$tableFields[$fc++]] = $fV[0];
				}
				$values[] = $fields;
			}
			$query = count($values) === 1 ? $values[0] : $values;
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
		switch ((string) $GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
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
	 * @param 	array		Array of SQL query components
	 * @return 	array		array with SQL CREATE TABLE/INDEX command(s)
	 * @see parseCREATETABLE()
	 */
	public function compileCREATETABLE($components) {
		// Execute query (based on handler derived from the TABLE name which we actually know for once!)
		switch ((string) $GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->handler_getFromTableList($components['TABLE'])]['type']) {
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
				foreach ($components['KEYS'] as $kN => $kCfg) {
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
	public function compileALTERTABLE($components) {
		// Execute query (based on handler derived from the TABLE name which we actually know for once!)
		switch ((string) $GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
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

			case 'ADDUNIQUE':
				$query .= ' (' . implode(',', $components['fields']) . ')';
				break;
			case 'DEFAULTCHARACTERSET':

			case 'ENGINE':
				// ??? todo!
				break;
			}
			break;
		}
		return $query;
	}

	/**
	 * Compile field definition
	 *
	 * @param 	array		Field definition parts
	 * @return 	string		Field definition string
	 */
	public function compileFieldCfg($fieldCfg) {
		switch ((string) $GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
		case 'native':
			$cfg = parent::compileFieldCfg($fieldCfg);
			break;
		case 'adodb':
			// Set type:
			$type = $GLOBALS['TYPO3_DB']->MySQLMetaType($fieldCfg['fieldType']);
			$cfg = $type;
			// Add value, if any:
			if (strlen($fieldCfg['value']) && in_array($type, array('C', 'C2'))) {
				$cfg .= ' ' . $fieldCfg['value'];
			} elseif (!isset($fieldCfg['value']) && in_array($type, array('C', 'C2'))) {
				$cfg .= ' 255';
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
					case $feature === 'UNSIGNED' && !$GLOBALS['TYPO3_DB']->runningADOdbDriver('mysql'):

					case $feature === 'AUTO_INCREMENT':

					case $feature === 'NOTNULL' && $GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8'):
						continue;
					case $feature === 'NOTNULL':
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
	 * @see \TYPO3\CMS\Core\Database\SqlParser::parseFieldDef()
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
	 * @see \TYPO3\CMS\Core\Database\SqlParser::parseWhereClause()
	 */
	public function compileWhereClause($clauseArray, $functionMapping = TRUE) {
		switch ((string) $GLOBALS['TYPO3_DB']->handlerCfg[$GLOBALS['TYPO3_DB']->lastHandlerKey]['type']) {
		case 'native':
			$output = parent::compileWhereClause($clauseArray);
			break;
		case 'adodb':
			// Prepare buffer variable:
			$output = '';
			// Traverse clause array:
			if (is_array($clauseArray)) {
				foreach ($clauseArray as $k => $v) {
					// Set operator:
					$output .= $v['operator'] ? ' ' . $v['operator'] : '';
					// Look for sublevel:
					if (is_array($v['sub'])) {
						$output .= ' (' . trim($this->compileWhereClause($v['sub'], $functionMapping)) . ')';
					} elseif (isset($v['func']) && $v['func']['type'] === 'EXISTS') {
						$output .= ' ' . trim($v['modifier']) . ' EXISTS (' . $this->compileSELECT($v['func']['subquery']) . ')';
					} else {
						if (isset($v['func']) && $v['func']['type'] === 'LOCATE') {
							$output .= ' ' . trim($v['modifier']);
							switch (TRUE) {
							case $GLOBALS['TYPO3_DB']->runningADOdbDriver('mssql') && $functionMapping:
								$output .= ' CHARINDEX(';
								$output .= $v['func']['substr'][1] . $v['func']['substr'][0] . $v['func']['substr'][1];
								$output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
								$output .= isset($v['func']['pos']) ? ', ' . $v['func']['pos'][0] : '';
								$output .= ')';
								break;
							case $GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8') && $functionMapping:
								$output .= ' INSTR(';
								$output .= ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
								$output .= ', ' . $v['func']['substr'][1] . $v['func']['substr'][0] . $v['func']['substr'][1];
								$output .= isset($v['func']['pos']) ? ', ' . $v['func']['pos'][0] : '';
								$output .= ')';
								break;
							default:
								$output .= ' LOCATE(';
								$output .= $v['func']['substr'][1] . $v['func']['substr'][0] . $v['func']['substr'][1];
								$output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
								$output .= isset($v['func']['pos']) ? ', ' . $v['func']['pos'][0] : '';
								$output .= ')';
								break;
							}
						} elseif (isset($v['func']) && $v['func']['type'] === 'IFNULL') {
							$output .= ' ' . trim($v['modifier']) . ' ';
							switch (TRUE) {
							case $GLOBALS['TYPO3_DB']->runningADOdbDriver('mssql') && $functionMapping:
								$output .= 'ISNULL';
								break;
							case $GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8') && $functionMapping:
								$output .= 'NVL';
								break;
							default:
								$output .= 'IFNULL';
								break;
							}
							$output .= '(';
							$output .= ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
							$output .= ', ' . $v['func']['default'][1] . $this->compileAddslashes($v['func']['default'][0]) . $v['func']['default'][1];
							$output .= ')';
						} elseif (isset($v['func']) && $v['func']['type'] === 'FIND_IN_SET') {
							$output .= ' ' . trim($v['modifier']) . ' ';
							if ($functionMapping) {
								switch (TRUE) {
								case $GLOBALS['TYPO3_DB']->runningADOdbDriver('mssql'):
									$field = ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
									if (!isset($v['func']['str_like'])) {
										$v['func']['str_like'] = $v['func']['str'][0];
									}
									$output .= '\',\'+' . $field . '+\',\' LIKE \'%,' . $v['func']['str_like'] . ',%\'';
									break;
								case $GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8'):
									$field = ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
									if (!isset($v['func']['str_like'])) {
										$v['func']['str_like'] = $v['func']['str'][0];
									}
									$output .= '\',\'||' . $field . '||\',\' LIKE \'%,' . $v['func']['str_like'] . ',%\'';
									break;
								case $GLOBALS['TYPO3_DB']->runningADOdbDriver('postgres'):
									$output .= ' FIND_IN_SET(';
									$output .= $v['func']['str'][1] . $v['func']['str'][0] . $v['func']['str'][1];
									$output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
									$output .= ') != 0';
									break;
								default:
									$field = ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
									if (!isset($v['func']['str_like'])) {
										$v['func']['str_like'] = $v['func']['str'][0];
									}
									$output .= '(' . $field . ' LIKE \'%,' . $v['func']['str_like'] . ',%\'' . ' OR ' . $field . ' LIKE \'' . $v['func']['str_like'] . ',%\'' . ' OR ' . $field . ' LIKE \'%,' . $v['func']['str_like'] . '\'' . ' OR ' . $field . '= ' . $v['func']['str'][1] . $v['func']['str'][0] . $v['func']['str'][1] . ')';
									break;
								}
							} else {
								switch (TRUE) {
								case $GLOBALS['TYPO3_DB']->runningADOdbDriver('mssql'):

								case $GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8'):

								case $GLOBALS['TYPO3_DB']->runningADOdbDriver('postgres'):
									$output .= ' FIND_IN_SET(';
									$output .= $v['func']['str'][1] . $v['func']['str'][0] . $v['func']['str'][1];
									$output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
									$output .= ')';
									break;
								default:
									$field = ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
									if (!isset($v['func']['str_like'])) {
										$v['func']['str_like'] = $v['func']['str'][0];
									}
									$output .= '(' . $field . ' LIKE \'%,' . $v['func']['str_like'] . ',%\'' . ' OR ' . $field . ' LIKE \'' . $v['func']['str_like'] . ',%\'' . ' OR ' . $field . ' LIKE \'%,' . $v['func']['str_like'] . '\'' . ' OR ' . $field . '= ' . $v['func']['str'][1] . $v['func']['str'][0] . $v['func']['str'][1] . ')';
									break;
								}
							}
						} else {
							// Set field/table with modifying prefix if any:
							$output .= ' ' . trim($v['modifier']) . ' ';
							// DBAL-specific: Set calculation, if any:
							if ($v['calc'] === '&' && $functionMapping) {
								switch (TRUE) {
								case $GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8'):
									// Oracle only knows BITAND(x,y) - sigh
									$output .= 'BITAND(' . trim((($v['table'] ? $v['table'] . '.' : '') . $v['field'])) . ',' . $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1] . ')';
									break;
								default:
									// MySQL, MS SQL Server, PostgreSQL support the &-syntax
									$output .= trim((($v['table'] ? $v['table'] . '.' : '') . $v['field'])) . $v['calc'] . $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1];
									break;
								}
							} elseif ($v['calc']) {
								$output .= trim((($v['table'] ? $v['table'] . '.' : '') . $v['field'])) . $v['calc'];
								if (isset($v['calc_table'])) {
									$output .= trim(($v['calc_table'] ? $v['calc_table'] . '.' : '') . $v['calc_field']);
								} else {
									$output .= $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1];
								}
							} elseif (!($GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8') && preg_match('/(NOT )?LIKE( BINARY)?/', $v['comparator']) && $functionMapping)) {
								$output .= trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']);
							}
						}
						// Set comparator:
						if ($v['comparator']) {
							$isLikeOperator = preg_match('/(NOT )?LIKE( BINARY)?/', $v['comparator']);
							switch (TRUE) {
							case $GLOBALS['TYPO3_DB']->runningADOdbDriver('oci8') && $isLikeOperator && $functionMapping:
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
								if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($v['comparator'], 'NOT')) {
									$output .= 'NOT ';
								}
								// To be on the safe side
								$isLob = TRUE;
								if ($v['table']) {
									// Table and field names are quoted:
									$tableName = substr($v['table'], 1, strlen($v['table']) - 2);
									$fieldName = substr($v['field'], 1, strlen($v['field']) - 2);
									$fieldType = $GLOBALS['TYPO3_DB']->sql_field_metatype($tableName, $fieldName);
									$isLob = $fieldType === 'B' || $fieldType === 'XL';
								}
								if (strtoupper(substr($v['comparator'], -6)) === 'BINARY') {
									if ($isLob) {
										$output .= '(dbms_lob.instr(' . trim((($v['table'] ? $v['table'] . '.' : '') . $v['field'])) . ', ' . $compareValue . ',1,1) > 0)';
									} else {
										$output .= '(instr(' . trim((($v['table'] ? $v['table'] . '.' : '') . $v['field'])) . ', ' . $compareValue . ',1,1) > 0)';
									}
								} else {
									if ($isLob) {
										$output .= '(dbms_lob.instr(LOWER(' . trim((($v['table'] ? $v['table'] . '.' : '') . $v['field'])) . '), ' . \TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($compareValue) . ',1,1) > 0)';
									} else {
										$output .= '(instr(LOWER(' . trim((($v['table'] ? $v['table'] . '.' : '') . $v['field'])) . '), ' . \TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($compareValue) . ',1,1) > 0)';
									}
								}
								break;
							default:
								if ($isLikeOperator && $functionMapping) {
									if ($GLOBALS['TYPO3_DB']->runningADOdbDriver('postgres') || $GLOBALS['TYPO3_DB']->runningADOdbDriver('postgres64') || $GLOBALS['TYPO3_DB']->runningADOdbDriver('postgres7') || $GLOBALS['TYPO3_DB']->runningADOdbDriver('postgres8')) {
										// Remap (NOT)? LIKE to (NOT)? ILIKE
										// and (NOT)? LIKE BINARY to (NOT)? LIKE
										switch ($v['comparator']) {
										case 'LIKE':
											$v['comparator'] = 'ILIKE';
											break;
										case 'NOT LIKE':
											$v['comparator'] = 'NOT ILIKE';
											break;
										default:
											$v['comparator'] = str_replace(' BINARY', '', $v['comparator']);
											break;
										}
									} else {
										// No more BINARY operator
										$v['comparator'] = str_replace(' BINARY', '', $v['comparator']);
									}
								}
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
								} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('BETWEEN,NOT BETWEEN', $v['comparator'])) {
									$lbound = $v['values'][0];
									$ubound = $v['values'][1];
									$output .= ' ' . $lbound[1] . $this->compileAddslashes($lbound[0]) . $lbound[1];
									$output .= ' AND ';
									$output .= $ubound[1] . $this->compileAddslashes($ubound[0]) . $ubound[1];
								} elseif (isset($v['value']['operator'])) {
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


?>