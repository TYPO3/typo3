<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Module 'DBAL Debug' for the 'dbal' extension.
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */



	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);	
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:dbal/mod1/locallang.php');
require_once (PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]





/**
 * Script class; Backend module for DBAL extension
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_dbal
 */
class tx_dbal_module1 extends t3lib_SCbase {

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 * 
	 * @return	void		
	 */
	function menuConfig()	{
		$this->MOD_MENU = Array (
			'function' => Array (
				0 => $GLOBALS['LANG']->getLL('Debug_log'),
				'info' => $GLOBALS['LANG']->getLL('Cached_info'),
				'config' => $GLOBALS['LANG']->getLL('Configuration'),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * 
	 * @return	void		
	 */
	function main()	{
		global $BACK_PATH,$BE_USER;

			// Clean up settings:
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);

			// Draw the header.
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form='<form action="" method="post">';
		$this->doc->docType = 'xhtml_trans';

			// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
				script_ended = 0;
				function jumpToUrl(URL)	{	//
					document.location = URL;
				}
			');

			// DBAL page title:
		$this->content.=$this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content.=$this->doc->header($GLOBALS['LANG']->getLL('title'));
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$this->doc->funcMenu('',t3lib_BEfunc::getFuncMenu(0,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));

			// Debug log:
		switch($this->MOD_SETTINGS['function']) {
		    case 'info':
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('Cached_info'), $this->printCachedInfo());
			break;
		    case 'config':
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('Configuration'), $this->printConfig());
			break;
		    case 0:
			$this->content.= $this->doc->section($GLOBALS['LANG']->getLL('Debug_log'), $this->printLogMgm());
			break;
		}

			// ShortCut
		if ($BE_USER->mayMakeShortcut())	{
			$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
		}

		$this->content.=$this->doc->spacer(10);
	}

	/**
	 * Prints out the module HTML
	 * 
	 * @return	void		
	 */
	function printContent()	{
		global $SOBE;

		$this->content.=$this->doc->middle();
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	function printConfig()	{
	    ob_start();
	    var_dump($GLOBALS['TYPO3_DB']->conf);
	    $out = '<pre>'.ob_get_contents().'</pre>';
	    ob_end_clean();

	    return $out;
	}

	function printCachedInfo()	{
	    // Get cmd:
	    if((string)t3lib_div::_GP('cmd') == 'clear') {
		$GLOBALS['TYPO3_DB']->clearCachedFieldInfo();
		$GLOBALS['TYPO3_DB']->cacheFieldInfo();
	    }

	    $out = '<table border="1"><caption>auto_increment</caption><tbody><tr><th>Table</th><th>Field</th></tr>';
	    foreach($GLOBALS['TYPO3_DB']->cache_autoIncFields as $table => $field) {
		$out .= '<tr>';
		$out .= '<td>'.$table.'</td>';
		$out .= '<td>'.$field.'</td>';
		$out .= '</tr>';
	    }
	    $out .= '</tbody></table>';
	    $out .= $this->doc->spacer(5);
	    $out .= '<table border="1"><caption>Primary keys</caption><tbody><tr><th>Table</th><th>Field(s)</th></tr>';
	    foreach($GLOBALS['TYPO3_DB']->cache_primaryKeys as $table => $field) {
		$out .= '<tr>';
		$out .= '<td>'.$table.'</td>';
		$out .= '<td>'.$field.'</td>';
		$out .= '</tr>';
	    }
	    $out .= '</tbody></table>';
	    $out .= $this->doc->spacer(5);
	    $out .= '<table border="1"><caption>Field types</caption><tbody><tr><th colspan="3">Table</th></tr><tr><th>Field</th><th>Type</th><th>Metatype</th><th>NOT NULL</th></th></tr>';
	    foreach($GLOBALS['TYPO3_DB']->cache_fieldType as $table => $fields) {
		$out .= '<th colspan="3">'.$table.'</th>';
		foreach($fields as $field => $data) {
		    $out .= '<tr>';
		    $out .= '<td>'.$field.'</td>';
		    $out .= '<td>'.$data['type'].'</td>';
		    $out .= '<td>'.$data['metaType'].'</td>';
		    $out .= '<td>'.$data['notnull'].'</td>';
		    $out .= '</tr>';
		}
	    }
	    $out .= '</tbody></table>';

	    $menu = '<a href="index.php?cmd=clear">CLEAR DATA</a><hr />';		

	    return $menu.$out;
	}

	/**
	 * Printing the debug-log from the DBAL extension
	 * To enabled debugging, you will have to enabled it in the configuration!
	 * 
	 * @return	string		HTML content
	 */
	function printLogMgm()	{

			// Disable debugging in any case...
		$GLOBALS['TYPO3_DB']->debug = FALSE;

			// Get cmd:
		$cmd = (string)t3lib_div::_GP('cmd');
		switch($cmd)	{
			case 'flush':
				$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_dbal_debuglog','');
				$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_dbal_debuglog_where','');
				$outStr = 'Log FLUSHED!';
			break;
			case 'joins':
			
					// Query:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('table_join,exec_time,query,script','tx_dbal_debuglog','table_join!=\'\'', 'table_join,script,exec_time,query');

					// Init vars in which to pick up the query result:
				$tableIndex = array();
				$tRows = array();
				$tRows[] = '
					<tr>
						<td>Exec. time:</td>
						<td>Table joins:</td>
						<td>Script:</td>
						<td>Query</td>
					</tr>';

				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$tableArray = $GLOBALS['TYPO3_DB']->SQLparser->parseFromTables($row['table_join']);

						// Create table name index:
					foreach($tableArray as $a)	{
						foreach($tableArray as $b)	{
							if ($b['table']!=$a['table'])	{
								$tableIndex[$a['table']][$b['table']]=1;
							}
						}
					}

						// Create output row
					$tRows[] = '
						<tr>
							<td>'.htmlspecialchars($row['exec_time']).'</td>
							<td>'.htmlspecialchars($row['table_join']).'</td>
							<td>'.htmlspecialchars($row['script']).'</td>
							<td>'.htmlspecialchars($row['query']).'</td>
						</tr>';
				}

					// Printing direct joins:				
				$outStr.= '<h4>Direct joins:</h4>'.t3lib_div::view_array($tableIndex);


					// Printing total dependencies:
				foreach($tableIndex as $priTable => $a)	{
					foreach($tableIndex as $tableN => $v)	{
						foreach($v as $tableP => $vv)	{
							if ($tableP == $priTable)	{
								$tableIndex[$priTable] = array_merge($v, $a);
							}
						}
					}
				}
				$outStr.= '<h4>Total dependencies:</h4>'.t3lib_div::view_array($tableIndex);
				
					// Printing data rows:
				$outStr.= '
					<table border="1">'.implode('',$tRows).'
					</table>';
			break;
			case 'errors':
			
					// Query:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('serdata,exec_time,query,script','tx_dbal_debuglog','errorFlag>0','','tstamp DESC');

					// Init vars in which to pick up the query result:
				$tRows = array();
				$tRows[] = '
					<tr>
						<td>Exec. time:</td>
						<td>Error data:</td>
						<td>Script:</td>
						<td>Query</td>
					</tr>';

				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
						// Create output row
					$tRows[] = '
						<tr>
							<td>'.htmlspecialchars($row['exec_time']).'</td>
							<td>'.t3lib_div::view_array(unserialize($row['serdata'])).'</td>
							<td>'.htmlspecialchars($row['script']).'</td>
							<td>'.htmlspecialchars($row['query']).'</td>
						</tr>';
				}

					// Printing data rows:
				$outStr.= '
					<table border="1">'.implode('',$tRows).'
					</table>';
			break;
			case 'parsing':
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('query,serdata','tx_dbal_debuglog','errorFlag&2=2');
				$tRows = array();
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
						// Create output row
					$tRows[] = '
						<tr>
							<td>'.htmlspecialchars($row['query']).'</td>
						</tr>';
				}
				
					// Printing data rows:
				$outStr.= '
					<table border="1">'.implode('',$tRows).'
					</table>';
			break;
			case 'where':
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp,script,tablename,whereclause','tx_dbal_debuglog_where','','','tstamp DESC');
				$tRows = array();
				$tRows[] = '
					<tr>
						<td>Time:</td>
						<td>Script:</td>
						<td>Table:</td>
						<td>WHERE:</td>
					</tr>';
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$tRows[] = '
						<tr>
							<td>'.t3lib_BEfunc::datetime($row['tstamp']).'</td>
							<td>'.htmlspecialchars($row['script']).'</td>
							<td>'.htmlspecialchars($row['tablename']).'</td>
							<td>'.str_replace('\'\'', '<span style="background-color:#ff0000;color:#ffffff;padding:2px;font-weight:bold;">\'\'</span>', htmlspecialchars($row['whereclause'])).'</td>
						</tr>';
				}

				$outStr = '
					<table border="1">'.implode('',$tRows).'
					</table>';
			break;
			default:
			
					// Look for request to view specific script exec:
				$specTime = t3lib_div::_GP('specTime');
				
				if ($specTime)	{
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_dbal_debuglog','tstamp='.intval($specTime));
					$tRows = array();
					$tRows[] = '
						<tr>
							<td>Exec. time:</td>
							<td>Error</td>
							<td>Table joins:</td>
							<td>Data</td>
							<td>Query</td>
						</tr>';
					while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
						$tRows[] = '
							<tr>
								<td>'.htmlspecialchars($row['exec_time']).'</td>
								<td>'.($row['errorFlag'] ? 1 : 0).'</td>
								<td>'.htmlspecialchars($row['table_join']).'</td>
								<td>'.t3lib_div::view_array(unserialize($row['serdata'])).'</td>
								<td>'.htmlspecialchars($row['query']).'</td>
							</tr>';
					}
				} else {
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp,script, SUM(exec_time) as calc_sum, count(*) AS qrycount, MAX(errorFlag) as error','tx_dbal_debuglog','','tstamp,script','tstamp DESC');
					$tRows = array();
					$tRows[] = '
						<tr>
							<td>Time:</td>
							<td># queries:</td>
							<td>Error:</td>
							<td>T.ms:</td>
							<td>Script:</td>
						</tr>';
					while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
						$tRows[] = '
							<tr>
								<td>'.t3lib_BEfunc::datetime($row['tstamp']).'</td>
								<td>'.htmlspecialchars($row['qrycount']).'</td>
								<td>'.htmlspecialchars($row['error'] ? 'ERR' : '').'</td>
								<td>'.htmlspecialchars($row['calc_sum']).'</td>
								<td><a href="index.php?specTime='.intval($row['tstamp']).'">'.htmlspecialchars($row['script']).'</a></td>
							</tr>';
					}
				}
				$outStr = '
					<table border="1">'.implode('',$tRows).'
					</table>';
				
			break;
		}

		$menu = '
					<a href="index.php?cmd=flush">FLUSH LOG</a> - 
					<a href="index.php?cmd=joins">JOINS</a> - 
					<a href="index.php?cmd=errors">ERRORS</a> - 
					<a href="index.php?cmd=parsing">PARSING</a> - 
					<a href="index.php">LOG</a> - 
					<a href="index.php?cmd=where">WHERE</a> - 

					<a href="'.htmlspecialchars(t3lib_div::linkThisScript()).'" target="blablabla">[New window]</a>
					<hr />
		';		
		return $menu.$outStr;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_dbal_module1');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>