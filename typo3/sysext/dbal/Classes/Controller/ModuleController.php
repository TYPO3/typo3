<?php
namespace TYPO3\CMS\Dbal\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2004-2013 Karsten Dambekalns (karsten@typo3.org)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Script class; Backend module for DBAL extension
 *
 * @author 	Kasper Skårhøj <kasper@typo3.com>
 * @author 	Karsten Dambekalns <karsten@typo3.org>
 */
class ModuleController extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	/**
	 * @var string
	 */
	protected $thisScript;

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return 	void
	 */
	public function menuConfig() {
		$this->MOD_MENU = array(
			'function' => array(
				0 => $GLOBALS['LANG']->getLL('Debug_log'),
				'info' => $GLOBALS['LANG']->getLL('Cached_info'),
				'sqlcheck' => $GLOBALS['LANG']->getLL('SQL_check')
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return 	void
	 */
	public function main() {
		$this->thisScript = 'mod.php?M=' . $this->MCONF['name'];
		// Clean up settings:
		$this->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $this->MCONF['name']);
		// Draw the header
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->form = '<form action="" method="post">';
		// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
				script_ended = 0;
				function jumpToUrl(URL)	{	//
					window.location.href = URL;
				}
			');
		// DBAL page title:
		$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
		$this->content .= $this->doc->spacer(5);
		$this->content .= $this->doc->section('', $this->doc->funcMenu('', \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'])));
		// Debug log:
		switch ($this->MOD_SETTINGS['function']) {
		case 'info':
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('Cached_info'), $this->printCachedInfo());
			break;
		case 'sqlcheck':
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('SQL_check'), $this->printSqlCheck());
			break;
		case 0:
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('Debug_log'), $this->printLogMgm());
			break;
		}
		// ShortCut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$this->content .= $this->doc->spacer(20) . $this->doc->section('', $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
		}
		$this->content .= $this->doc->spacer(10);
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return 	string HTML output
	 */
	public function printContent() {
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Displays a form to check DBAL SQL methods and parse raw SQL.
	 *
	 * @return string HTML output
	 */
	protected function printSqlCheck() {
		$input = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_dbal');
		$out = '
			<form name="sql_check" action="' . $this->thisScript . '" method="post" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '">
			<script type="text/javascript">
/*<![CDATA[*/
function updateQryForm(s) {
	document.getElementById(\'tx-dbal-result\').style.display = \'none\';
	switch(s) {
	case \'SELECT\':
		document.getElementById(\'tx-dbal-qryupdate\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryfields\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qryinsertvalues\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryupdatevalues\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryfrom\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qryinto\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qrywhere\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qrygroup\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qryorder\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qrylimit\').style.display = \'table-row\';
	break;
	case \'INSERT\':
		document.getElementById(\'tx-dbal-qryupdate\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryfields\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryinsertvalues\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qryupdatevalues\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryfrom\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryinto\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qrywhere\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qrygroup\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qryorder\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qrylimit\').style.display = \'table-row\';
	break;
	case \'UPDATE\':
		document.getElementById(\'tx-dbal-qryupdate\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qryfields\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryinsertvalues\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryupdatevalues\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qryfrom\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryinto\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryupdate\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qrywhere\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qrygroup\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryorder\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qrylimit\').style.display = \'none\';
	break;
	case \'DELETE\':
		document.getElementById(\'tx-dbal-qryupdate\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryfields\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryinsertvalues\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryupdatevalues\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryfrom\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qryinto\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qrywhere\').style.display = \'table-row\';
		document.getElementById(\'tx-dbal-qrygroup\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qryorder\').style.display = \'none\';
		document.getElementById(\'tx-dbal-qrylimit\').style.display = \'none\';
	break;
	}
}
/*]]>*/
				</script>
	    <table>
	    <tr class="tableheader bgColor5"><th colspan="2">Easy SQL check</th></tr>
	    <tr><td colspan="2">
	    <select name="tx_dbal[QUERY]"size="1" onchange="updateQryForm(this.options[this.selectedIndex].value)">
	     <option value="SELECT" ' . ($input['QUERY'] === 'SELECT' ? 'selected="selected"' : '') . '>SELECT</option>
	     <option value="INSERT" ' . ($input['QUERY'] === 'INSERT' ? 'selected="selected"' : '') . '>INSERT</option>
	     <option value="UPDATE" ' . ($input['QUERY'] === 'UPDATE' ? 'selected="selected"' : '') . '>UPDATE</option>
	     <option value="DELETE" ' . ($input['QUERY'] === 'DELETE' ? 'selected="selected"' : '') . '>DELETE</option>
	    </select>
	    </td></tr>
 	    <tr id="tx-dbal-qryupdate" style="display:none;"><td></td><td><input name="tx_dbal[UPDATE]" value="' . $input['UPDATE'] . '" type="text" size="30" maxsize="100" /></td></tr>
	    <tr id="tx-dbal-qryfields"><td></td><td><input name="tx_dbal[FIELDS]" value="' . $input['FIELDS'] . '" type="text" size="30" maxsize="100" /></td></tr>
	    <tr id="tx-dbal-qryinsertvalues" style="display:none;"><td></td><td><textarea name="tx_dbal[INSERTVALUES]" cols="30" rows="4">' . $input['INSERTVALUES'] . '</textarea></td></tr>
	    <tr id="tx-dbal-qryupdatevalues" style="display:none;"><th>SET</th><td><textarea name="tx_dbal[UPDATEVALUES]" cols="30" rows="4">' . $input['UPDATEVALUES'] . '</textarea></td></tr>
 	    <tr id="tx-dbal-qryfrom"><th>FROM</th><td><input name="tx_dbal[FROM]" value="' . $input['FROM'] . '" type="text" size="30" maxsize="100" /></td></tr>
 	    <tr id="tx-dbal-qryinto" style="display:none;"><th>INTO</th><td><input name="tx_dbal[INTO]" value="' . $input['INTO'] . '" type="text" size="30" maxsize="100" /></td></tr>
	    <tr id="tx-dbal-qrywhere"><th>WHERE</th><td><input name="tx_dbal[WHERE]" value="' . $input['WHERE'] . '" type="text" size="30" maxsize="100" /></td></tr>
	    <tr id="tx-dbal-qrygroup"><th>GROUP BY</th><td><input name="tx_dbal[GROUP]" value="' . $input['GROUP'] . '" type="text" size="30" maxsize="100" /></td></tr>
	    <tr id="tx-dbal-qryorder"><th>ORDER BY</th><td><input name="tx_dbal[ORDER]" value="' . $input['ORDER'] . '" type="text" size="30" maxsize="100" /></td></tr>
	    <tr id="tx-dbal-qrylimit"><th>LIMIT</th><td><input name="tx_dbal[LIMIT]" value="' . $input['LIMIT'] . '" type="text" size="30" maxsize="100" /></td></tr>
			<tr><td></td><td style="text-align:right;"><input type="submit" value="CHECK" /></td></tr>
			<script type="text/javascript">
/*<![CDATA[*/
updateQryForm(\'' . $input['QUERY'] . '\');
/*]]>*/
				</script>
			';
		$out .= '<tr id="tx-dbal-result" class="bgColor4"><th>Result:</th><td>';
		switch ($input['QUERY']) {
		case 'SELECT':
			$qry = $GLOBALS['TYPO3_DB']->SELECTquery($input['FIELDS'], $input['FROM'], $input['WHERE'], $input['GROUP'], $input['ORDER'], $input['LIMIT']);
			break;
		case 'INSERT':
			$qry = $GLOBALS['TYPO3_DB']->INSERTquery($input['INTO'], $this->createFieldsValuesArray($input['INSERTVALUES']));
			break;
		case 'UPDATE':
			$qry = $GLOBALS['TYPO3_DB']->UPDATEquery($input['UPDATE'], $input['WHERE'], $this->createFieldsValuesArray($input['UPDATEVALUES']));
			break;
		case 'DELETE':
			$qry = $GLOBALS['TYPO3_DB']->DELETEquery($input['FROM'], $input['WHERE']);
			break;
		}
		$out .= '<pre>' . htmlspecialchars($qry) . '</pre></td></tr>';
		$out .= '
			<tr class="tableheader bgColor5"><th colspan="2">RAW SQL check</th></tr>
			<tr><td colspan="2" style="text-align:right;"><textarea name="tx_dbal[RAWSQL]" cols="60" rows="5">' . $input['RAWSQL'] . '</textarea><br /><input type="submit" value="CHECK" /></td></tr>';
		if (!empty($input['RAWSQL'])) {
			$out .= '<tr class="bgColor4">';
			$parseResult = $GLOBALS['TYPO3_DB']->SQLparser->parseSQL($input['RAWSQL']);
			if (is_array($parseResult)) {
				$newQuery = $GLOBALS['TYPO3_DB']->SQLparser->compileSQL($parseResult);
				$testResult = $GLOBALS['TYPO3_DB']->SQLparser->debug_parseSQLpartCompare($input['RAWSQL'], $newQuery);
				if (!is_array($testResult)) {
					$out .= '<td colspan="2">' . $newQuery;
				} else {
					$out .= '<td colspan="2">' . htmlspecialchars($testResult[0]) . '</td></tr>
					<tr><th>Error:</th><td style="border:2px solid #f00;">Input query did not match the parsed and recompiled query exactly (not observing whitespace):<br />' . htmlspecialchars($testResult[1]);
				}
			} else {
				$out .= '<th>Result:</th><td style="border:2px solid #f00;">' . $parseResult;
			}
			$out .= '</td></tr>';
		}
		$out .= '</table></form>';
		return $out;
	}

	/**
	 * Parses a very simple text format into an array.
	 *
	 * Each line is seen as a key/value pair that is exploded at =. This is used
	 * in the simple SQL check to input values for INSERT and UPDATE statements.
	 *
	 * @param string $in String to parse into key/value array.
	 * @return array Array created from the input string.
	 */
	protected function createFieldsValuesArray($in) {
		$ret = array();
		$in = explode(chr(10), $in);
		foreach ($in as $v) {
			$fv = explode('=', $v);
			$ret[$fv[0]] = $fv[1];
		}
		return $ret;
	}

	/**
	 * Prints out the cached information about the database.
	 *
	 * The DBAL caches a lot of information, e.g. about auto increment fields,
	 * field types and primary keys. This method formats all this into a HTML
	 * table to display in the BE.
	 *
	 * @return string	HTML output
	 */
	protected function printCachedInfo() {
		// Get cmd:
		if ((string) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd') === 'clear') {
			$GLOBALS['TYPO3_DB']->clearCachedFieldInfo();
			$GLOBALS['TYPO3_DB']->cacheFieldInfo();
		}
		$out = '<a name="autoincrement"></a><h2>auto_increment</h2>';
		$out .= '<table border="1" cellspacing="0"><tbody><tr><th>Table</th><th>Field</th></tr>';
		ksort($GLOBALS['TYPO3_DB']->cache_autoIncFields);
		foreach ($GLOBALS['TYPO3_DB']->cache_autoIncFields as $table => $field) {
			$out .= '<tr>';
			$out .= '<td>' . $table . '</td>';
			$out .= '<td>' . $field . '</td>';
			$out .= '</tr>';
		}
		$out .= '</tbody></table>';
		$out .= $this->doc->spacer(5);
		$out .= '<a name="primarykeys"></a><h2>Primary keys</h2>';
		$out .= '<table border="1" cellspacing="0"><tbody><tr><th>Table</th><th>Field(s)</th></tr>';
		ksort($GLOBALS['TYPO3_DB']->cache_primaryKeys);
		foreach ($GLOBALS['TYPO3_DB']->cache_primaryKeys as $table => $field) {
			$out .= '<tr>';
			$out .= '<td>' . $table . '</td>';
			$out .= '<td>' . $field . '</td>';
			$out .= '</tr>';
		}
		$out .= '</tbody></table>';
		$out .= $this->doc->spacer(5);
		$out .= '<a name="fieldtypes"></a><h2>Field types</h2>';
		$out .= '<table border="1" cellspacing="0"><tbody><tr><th colspan="5">Table</th></tr><tr><th>Field</th><th>Type</th><th><a href="#metatypes">Metatype</a></th><th>NOT NULL</th><th>Default</th></th></tr>';
		ksort($GLOBALS['TYPO3_DB']->cache_fieldType);
		foreach ($GLOBALS['TYPO3_DB']->cache_fieldType as $table => $fields) {
			$out .= '<th colspan="5">' . $table . '</th>';
			foreach ($fields as $field => $data) {
				$out .= '<tr>';
				$out .= '<td>' . $field . '</td>';
				$out .= '<td>' . $data['type'] . '</td>';
				$out .= '<td>' . $data['metaType'] . '</td>';
				$out .= '<td>' . ($data['notnull'] ? 'NOT NULL' : '') . '</td>';
				$out .= '<td>' . $data['default'] . '</td>';
				$out .= '</tr>';
			}
		}
		$out .= '</tbody></table>';
		$out .= $this->doc->spacer(5);
		$out .= '<a name="metatypes"></a><h2>Metatype explanation</h2>';
		$out .= '<pre>
  C:  Varchar, capped to 255 characters.
  X:  Larger varchar, capped to 4000 characters (to be compatible with Oracle).
  XL: For Oracle, returns CLOB, otherwise the largest varchar size.

  C2: Multibyte varchar
  X2: Multibyte varchar (largest size)

  B:  BLOB (binary large object)

  D:  Date (some databases do not support this, and we return a datetime type)
  T:  Datetime or Timestamp
  L:  Integer field suitable for storing booleans (0 or 1)
  I:  Integer (mapped to I4)
  I1: 1-byte integer
  I2: 2-byte integer
  I4: 4-byte integer
  I8: 8-byte integer
  F:  Floating point number
  N:  Numeric or decimal number</pre>';
		$menu = '<a href="' . $this->thisScript . '&amp;cmd=clear">CLEAR DATA</a><hr />';
		$menu .= '<a href="#autoincrement">auto_increment</a> | <a href="#primarykeys">Primary keys</a> | <a href="#fieldtypes">Field types</a> | <a href="#metatypes">Metatype explanation</a><hr />';
		return $menu . $out;
	}

	/**
	 * Printing the debug-log from the DBAL extension
	 *
	 * To enabled debugging, you will have to enabled it in the configuration!
	 *
	 * @return 	string HTML content
	 */
	protected function printLogMgm() {
		// Disable debugging in any case...
		$GLOBALS['TYPO3_DB']->debug = FALSE;
		// Get cmd:
		$cmd = (string) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd');
		switch ($cmd) {
		case 'flush':
			$res = $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('tx_dbal_debuglog');
			$res = $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('tx_dbal_debuglog_where');
			$outStr = 'Log FLUSHED!';
			break;
		case 'joins':
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('table_join,exec_time,query,script', 'tx_dbal_debuglog', 'table_join!=\'\'', 'table_join,script,exec_time,query');
			// Init vars in which to pick up the query result:
			$tableIndex = array();
			$tRows = array();
			$tRows[] = '
					<tr>
						<td>Execution time</td>
						<td>Table joins</td>
						<td>Script</td>
						<td>Query</td>
					</tr>';
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$tableArray = $GLOBALS['TYPO3_DB']->SQLparser->parseFromTables($row['table_join']);
				// Create table name index:
				foreach ($tableArray as $a) {
					foreach ($tableArray as $b) {
						if ($b['table'] != $a['table']) {
							$tableIndex[$a['table']][$b['table']] = 1;
						}
					}
				}
				// Create output row
				$tRows[] = '
						<tr>
							<td>' . htmlspecialchars($row['exec_time']) . '</td>
							<td>' . htmlspecialchars($row['table_join']) . '</td>
							<td>' . htmlspecialchars($row['script']) . '</td>
							<td>' . htmlspecialchars($row['query']) . '</td>
						</tr>';
			}
			// Printing direct joins:
			$outStr .= '<h4>Direct joins:</h4>' . \TYPO3\CMS\Core\Utility\DebugUtility::viewArray($tableIndex);
			// Printing total dependencies:
			foreach ($tableIndex as $priTable => $a) {
				foreach ($tableIndex as $tableN => $v) {
					foreach ($v as $tableP => $vv) {
						if ($tableP == $priTable) {
							$tableIndex[$priTable] = array_merge($v, $a);
						}
					}
				}
			}
			$outStr .= '<h4>Total dependencies:</h4>' . \TYPO3\CMS\Core\Utility\DebugUtility::viewArray($tableIndex);
			// Printing data rows:
			$outStr .= '
					<table border="1" cellspacing="0">' . implode('', $tRows) . '
					</table>';
			break;
		case 'errors':
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('serdata,exec_time,query,script', 'tx_dbal_debuglog', 'errorFlag>0', '', 'tstamp DESC');
			// Init vars in which to pick up the query result:
			$tRows = array();
			$tRows[] = '
					<tr>
						<td>Execution time</td>
						<td>Error data</td>
						<td>Script</td>
						<td>Query</td>
					</tr>';
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// Create output row
				$tRows[] = '
						<tr>
							<td>' . htmlspecialchars($row['exec_time']) . '</td>
							<td>' . \TYPO3\CMS\Core\Utility\DebugUtility::viewArray(unserialize($row['serdata'])) . '</td>
							<td>' . htmlspecialchars($row['script']) . '</td>
							<td>' . htmlspecialchars($row['query']) . '</td>
						</tr>';
			}
			// Printing data rows:
			$outStr .= '
					<table border="1" cellspacing="0">' . implode('', $tRows) . '
					</table>';
			break;
		case 'parsing':
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('query,serdata', 'tx_dbal_debuglog', 'errorFlag&2=2');
			$tRows = array();
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// Create output row
				$tRows[] = '
						<tr>
							<td>' . htmlspecialchars($row['query']) . '</td>
						</tr>';
			}
			// Printing data rows:
			$outStr .= '
					<table border="1" cellspacing="0">' . implode('', $tRows) . '
					</table>';
			break;
		case 'where':
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp,script,tablename,whereclause', 'tx_dbal_debuglog_where', '', '', 'tstamp DESC');
			$tRows = array();
			$tRows[] = '
					<tr>
						<td>Time</td>
						<td>Script</td>
						<td>Table</td>
						<td>WHERE clause</td>
					</tr>';
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$tRows[] = '
						<tr>
							<td>' . \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row['tstamp']) . '</td>
							<td>' . htmlspecialchars($row['script']) . '</td>
							<td>' . htmlspecialchars($row['tablename']) . '</td>
								<td>' . str_replace(array('\'\'', '""', 'IS NULL', 'IS NOT NULL'), array('<span style="background-color:#ff0000;color:#ffffff;padding:2px;font-weight:bold;">\'\'</span>', '<span style="background-color:#ff0000;color:#ffffff;padding:2px;font-weight:bold;">""</span>', '<span style="background-color:#00ff00;color:#ffffff;padding:2px;font-weight:bold;">IS NULL</span>', '<span style="background-color:#00ff00;color:#ffffff;padding:2px;font-weight:bold;">IS NOT NULL</span>'), htmlspecialchars($row['whereclause'])) . '</td>
						</tr>';
			}
			$outStr = '
					<table border="1" cellspacing="0">' . implode('', $tRows) . '
					</table>';
			break;
		default:
			// Look for request to view specific script exec:
			$specTime = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('specTime');
			if ($specTime) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('exec_time,errorFlag,table_join,serdata,query', 'tx_dbal_debuglog', 'tstamp=' . (int) $specTime);
				$tRows = array();
				$tRows[] = '
						<tr>
							<td>Execution time</td>
							<td>Error</td>
							<td>Table joins</td>
							<td>Data</td>
							<td>Query</td>
						</tr>';
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$tRows[] = '
							<tr>
								<td>' . htmlspecialchars($row['exec_time']) . '</td>
								<td>' . ($row['errorFlag'] ? 1 : 0) . '</td>
								<td>' . htmlspecialchars($row['table_join']) . '</td>
								<td>' . \TYPO3\CMS\Core\Utility\DebugUtility::viewArray(unserialize($row['serdata'])) . '</td>
								<td>' . str_replace(array('\'\'', '""', 'IS NULL', 'IS NOT NULL'), array('<span style="background-color:#ff0000;color:#ffffff;padding:2px;font-weight:bold;">\'\'</span>', '<span style="background-color:#ff0000;color:#ffffff;padding:2px;font-weight:bold;">""</span>', '<span style="background-color:#00ff00;color:#ffffff;padding:2px;font-weight:bold;">IS NULL</span>', '<span style="background-color:#00ff00;color:#ffffff;padding:2px;font-weight:bold;">IS NOT NULL</span>'), htmlspecialchars($row['query'])) . '</td>
							</tr>';
				}
			} else {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp,script, SUM(exec_time) as calc_sum, count(*) AS qrycount, MAX(errorFlag) as error', 'tx_dbal_debuglog', '', 'tstamp,script', 'tstamp DESC');
				$tRows = array();
				$tRows[] = '
						<tr>
							<td>Time</td>
							<td># of queries</td>
							<td>Error</td>
							<td>Time (ms)</td>
							<td>Script</td>
						</tr>';
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$tRows[] = '
							<tr>
								<td>' . \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row['tstamp']) . '</td>
								<td>' . htmlspecialchars($row['qrycount']) . '</td>
								<td>' . ($row['error'] ? '<strong style="color:#f00">ERR</strong>' : '') . '</td>
								<td>' . htmlspecialchars($row['calc_sum']) . '</td>
								<td><a href="' . $this->thisScript . '&amp;specTime=' . intval($row['tstamp']) . '">' . htmlspecialchars($row['script']) . '</a></td>
							</tr>';
				}
			}
			$outStr = '
					<table border="1" cellspacing="0">' . implode('', $tRows) . '
					</table>';
			break;
		}
		$menu = '
					<a href="' . $this->thisScript . '&amp;cmd=flush">FLUSH LOG</a> -
					<a href="' . $this->thisScript . '&amp;cmd=joins">JOINS</a> -
					<a href="' . $this->thisScript . '&amp;cmd=errors">ERRORS</a> -
					<a href="' . $this->thisScript . '&amp;cmd=parsing">PARSING</a> -
					<a href="' . $this->thisScript . '">LOG</a> -
					<a href="' . $this->thisScript . '&amp;cmd=where">WHERE</a> -

					<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript()) . '" target="tx_debuglog">[New window]</a>
					<hr />
		';
		return $menu . $outStr;
	}

}


?>