<?php
namespace TYPO3\CMS\Dbal\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script class; Backend module for DBAL extension
 */
class ModuleController extends BaseScriptClass
{
    /**
     * @var string
     */
    protected $thisScript;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'tools_txdbalM1';

    /**
     * ModuleTemplateContainer
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Initializes this module.
     *
     * @return void
     */
    public function init()
    {
        $this->MCONF = [
            'name' => $this->moduleName,
        ];
        $this->getLanguageService()->includeLLFile('EXT:dbal/Resources/Private/Language/locallang.xlf');
        parent::init();
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     *
     * @return void
     */
    public function menuConfig()
    {
        $languageService = $this->getLanguageService();
        $this->MOD_MENU = [
            'function' => [
                0 => $languageService->getLL('Debug_log'),
                'info' => $languageService->getLL('Cached_info'),
                'sqlcheck' => $languageService->getLL('SQL_check')
            ]
        ];
        parent::menuConfig();
    }

    /**
     * Main function of the module. Write the content to $this->content
     *
     * @return void
     */
    public function main()
    {
        $languageService = $this->getLanguageService();
        $this->thisScript = BackendUtility::getModuleUrl($this->MCONF['name']);
        // Clean up settings:
        $this->MOD_SETTINGS = BackendUtility::getModuleData(
            $this->MOD_MENU,
            GeneralUtility::_GP('SET'),
            $this->MCONF['name']
        );
        // Draw the header
        // DBAL page title:
        $this->content .= '<h1>' . $languageService->getLL('title') . '</h1>';
        $this->generateMenu();
        $shortcutName = $languageService->getLL('Debug_log');
        // Debug log:
        switch ($this->MOD_SETTINGS['function']) {
            case 'info':
                $this->content .= '<h3>' . $languageService->getLL('Cached_info') . '</h3>';
                $this->content .= '<div>' . $this->printCachedInfo() . '</div>';
                $shortcutName = $languageService->getLL('Cached_info');
                break;
            case 'sqlcheck':
                $this->content .= '<h3>' . $languageService->getLL('SQL_check') . '</h3>';
                $this->content .= '<div>' . $this->printSqlCheck() . '</div>';
                $shortcutName = $languageService->getLL('SQL_check');
                break;
            case 0:
                $this->content .= '<h3>' . $languageService->getLL('Debug_log') . '</h3>';
                $this->content .= '<div>' . $this->printLogMgm() . '</div>';
                break;
        }
        // ShortCut
        $shortcutButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeShortcutButton()
            ->setModuleName($this->MCONF['name'])
            ->setDisplayName($shortcutName)
            ->setSetVariables(['function']);
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($shortcutButton);
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();
        $this->main();
        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Displays a form to check DBAL SQL methods and parse raw SQL.
     *
     * @return string HTML output
     */
    protected function printSqlCheck()
    {
        $input = GeneralUtility::_GP('tx_dbal');
        $out = '
			<form name="sql_check" action="' . $this->thisScript . '" method="post" enctype="multipart/form-data">
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
		<tr>
			<td></td>
			<td style="text-align:right;">
				<input class="btn btn-default" type="submit" value="CHECK" />
			</td>
		</tr>
			<script type="text/javascript">
/*<![CDATA[*/
updateQryForm(\'' . $input['QUERY'] . '\');
/*]]>*/
				</script>
			';
        $out .= '<tr id="tx-dbal-result" class="bgColor4"><th>Result:</th><td>';
        switch ($input['QUERY']) {
            case 'SELECT':
                $qry = $this->getDatabaseConnection()->SELECTquery($input['FIELDS'], $input['FROM'], $input['WHERE'], $input['GROUP'], $input['ORDER'], $input['LIMIT']);
                break;
            case 'INSERT':
                $qry = $this->getDatabaseConnection()->INSERTquery($input['INTO'], $this->createFieldsValuesArray($input['INSERTVALUES']));
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
			<tr class="tableheader bgColor5">
				<th colspan="2">RAW SQL check</th>
			</tr>
			<tr>
				<td colspan="2" style="text-align:right;">
					<textarea name="tx_dbal[RAWSQL]" cols="60" rows="5">' . $input['RAWSQL'] . '</textarea>
					<br />
					<input class="btn btn-default" type="submit" value="CHECK" />
				</td>
			</tr>';
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
    protected function createFieldsValuesArray($in)
    {
        $ret = [];
        $in = explode(LF, $in);
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
     * @return string
     */
    protected function printCachedInfo()
    {
        // Get cmd:
        if ((string)GeneralUtility::_GP('cmd') === 'clear') {
            $this->getDatabaseConnection()->clearCachedFieldInfo();
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
        $out .= '<a name="fieldtypes"></a><h2>Field types</h2>';
        $out .= '
            <table border="1" cellspacing="0">
                <tbody>
                    <tr>
                        <th colspan="5">Table</th>
                    </tr>
                    <tr>
                        <th>Field</th>
                        <th>Type</th><th>
                        <a href="#metatypes">Metatype</a></th>
                        <th>NOT NULL</th>
                        <th>Default</th></th>
                    </tr>';
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
    protected function printLogMgm()
    {
        // Disable debugging in any case...
        $GLOBALS['TYPO3_DB']->debug = false;
        // Get cmd:
        $cmd = (string)GeneralUtility::_GP('cmd');
        switch ($cmd) {
            case 'flush':
                $res = $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('tx_dbal_debuglog');
                $res = $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('tx_dbal_debuglog_where');
                $outStr = 'Log FLUSHED!';
                break;
            case 'joins':
                $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('table_join,exec_time,query,script', 'tx_dbal_debuglog', 'table_join!=\'\'', 'table_join,script,exec_time,query');
                // Init vars in which to pick up the query result:
                $tableIndex = [];
                $tRows = [];
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
                $tRows = [];
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
                $tRows = [];
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
                $tRows = [];
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
								<td>' . BackendUtility::datetime($row['tstamp']) . '</td>
								<td>' . htmlspecialchars($row['script']) . '</td>
								<td>' . htmlspecialchars($row['tablename']) . '</td>
									<td>' . str_replace(['\'\'', '""', 'IS NULL', 'IS NOT NULL'], ['<span style="background-color:#ff0000;color:#ffffff;padding:2px;font-weight:bold;">\'\'</span>', '<span style="background-color:#ff0000;color:#ffffff;padding:2px;font-weight:bold;">""</span>', '<span style="background-color:#00ff00;color:#ffffff;padding:2px;font-weight:bold;">IS NULL</span>', '<span style="background-color:#00ff00;color:#ffffff;padding:2px;font-weight:bold;">IS NOT NULL</span>'], htmlspecialchars($row['whereclause'])) . '</td>
							</tr>';
                }
                $outStr = '
						<table border="1" cellspacing="0">' . implode('', $tRows) . '
						</table>';
                break;
            default:
                // Look for request to view specific script exec:
                $specTime = GeneralUtility::_GP('specTime');
                if ($specTime) {
                    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('exec_time,errorFlag,table_join,serdata,query', 'tx_dbal_debuglog', 'tstamp=' . (int)$specTime);
                    $tRows = [];
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
									<td>' . str_replace(['\'\'', '""', 'IS NULL', 'IS NOT NULL'], ['<span style="background-color:#ff0000;color:#ffffff;padding:2px;font-weight:bold;">\'\'</span>', '<span style="background-color:#ff0000;color:#ffffff;padding:2px;font-weight:bold;">""</span>', '<span style="background-color:#00ff00;color:#ffffff;padding:2px;font-weight:bold;">IS NULL</span>', '<span style="background-color:#00ff00;color:#ffffff;padding:2px;font-weight:bold;">IS NOT NULL</span>'], htmlspecialchars($row['query'])) . '</td>
								</tr>';
                    }
                } else {
                    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp,script, SUM(exec_time) as calc_sum, count(*) AS qrycount, MAX(errorFlag) as error', 'tx_dbal_debuglog', '', 'tstamp,script', 'tstamp DESC');
                    $tRows = [];
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
									<td>' . BackendUtility::datetime($row['tstamp']) . '</td>
									<td>' . htmlspecialchars($row['qrycount']) . '</td>
									<td>' . ($row['error'] ? '<strong style="color:#f00">ERR</strong>' : '') . '</td>
									<td>' . htmlspecialchars($row['calc_sum']) . '</td>
									<td><a href="' . $this->thisScript . '&amp;specTime=' . (int)$row['tstamp'] . '">' . htmlspecialchars($row['script']) . '</a></td>
								</tr>';
                    }
                }
                $outStr = '
						<table border="1" cellspacing="0">' . implode('', $tRows) . '
						</table>';
        }
        $menu = '
					<a href="' . $this->thisScript . '&amp;cmd=flush">FLUSH LOG</a> -
					<a href="' . $this->thisScript . '&amp;cmd=joins">JOINS</a> -
					<a href="' . $this->thisScript . '&amp;cmd=errors">ERRORS</a> -
					<a href="' . $this->thisScript . '&amp;cmd=parsing">PARSING</a> -
					<a href="' . $this->thisScript . '">LOG</a> -
					<a href="' . $this->thisScript . '&amp;cmd=where">WHERE</a> -

					<a href="' . htmlspecialchars(GeneralUtility::linkThisScript()) . '" target="tx_debuglog">[New window]</a>
					<hr />
		';
        return $menu . $outStr;
    }

    /**
     * Generate the ModuleMenu
     */
    protected function generateMenu()
    {
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('DBALJumpMenu');
        foreach ($this->MOD_MENU['function'] as $controller => $title) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    BackendUtility::getModuleUrl(
                        $this->moduleName,
                        [
                            'id' => $this->id,
                            'SET' => [
                                'function' => $controller
                            ]
                        ]
                    )
                )
                ->setTitle($title);
            if ($controller === $this->MOD_SETTINGS['function']) {
                $item->setActive(true);
            }
            $menu->addMenuItem($item);
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Returns the language service.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the DatabaseConnection
     *
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
