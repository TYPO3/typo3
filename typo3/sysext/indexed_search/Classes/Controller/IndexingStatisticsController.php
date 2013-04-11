<?php
namespace TYPO3\CMS\IndexedSearch\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2013 Dimitri Ebert (dimitri.ebert@dkd.de)
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
 * Module extension (addition to function menu) 'Indexed search statistics' for the 'indexed_search' extension.
 *
 * @author 	Dimitri Ebert <dimitri.ebert@dkd.de>
 */
/**
 * Module extension (addition to function menu) 'Indexed search statistics' for the 'indexed_search' extension.
 *
 * @author 	Dimitri Ebert <dimitri.ebert@dkd.de>
 */
class IndexingStatisticsController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * Calls showStats to generate output.
	 *
	 * @return 	string		html table with results from showStats()
	 * @todo Define visibility
	 */
	public function main() {
		// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		$theOutput = $this->pObj->doc->header($GLOBALS['LANG']->getLL('title'));
		$theOutput .= $this->pObj->doc->section('', $this->showStats(), 0, 1);
		$menu = array();
		$functionMenu = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->pObj->id, 'SET[tx_indexedsearch_modfunc2_check]', $this->pObj->MOD_SETTINGS['tx_indexedsearch_modfunc2_check'], '', '', 'id="checkTx_indexedsearch_modfunc2_check"');
		$menu[] = $functionMenu . '<label for="checkTx_indexedsearch_modfunc2_check"' . $GLOBALS['LANG']->getLL('checklabel') . '</label>';
		$theOutput .= $this->pObj->doc->spacer(5);
		return $theOutput;
	}

	/**
	 * Generates html table containing the statistics.
	 * Calls listSeveralStats 3 times, for all statistics, statistics of the last 30 days and statistics of the last 24 hours.
	 *
	 * @return 	string		html table with results
	 * @todo Define visibility
	 */
	public function showStats() {
		$conf['words'] = 50;
		// max words in result list
		$conf['bid'] = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('id'));
		// pageid for several statistics
		$addwhere1 = '';
		// all records
		$addwhere2 = ' AND tstamp > ' . ($GLOBALS['EXEC_TIME'] - 30 * 24 * 60 * 60);
		// last 30 days
		$addwhere3 = ' AND tstamp > ' . ($GLOBALS['EXEC_TIME'] - 24 * 60 * 60);
		// last 24 hours
		$content = $GLOBALS['LANG']->getLL('title2') . '
			<table cellpading="5" cellspacing="5" valign="top"><tr><td valign="top">' . $this->listSeveralStats($GLOBALS['LANG']->getLL('all'), $addwhere1, $conf) . '</td><td valign="top">' . $this->listSeveralStats($GLOBALS['LANG']->getLL('last30days'), $addwhere2, $conf) . '</td><td valign="top">' . $this->listSeveralStats($GLOBALS['LANG']->getLL('last24hours'), $addwhere3, $conf) . '</td></tr></table>' . $this->note;
		// Ask hook to include more on the page:
		if ($hookObj = $this->hookRequest('additionalSearchStat')) {
			$content .= $hookObj->additionalSearchStat();
		}
		return $content;
	}

	/**
	 * Generates html table with title and several statistics
	 *
	 * @param 	string		title for statistic, like 'Last 30 days' or 'Last 24 hours'
	 * @param 	string		add where for sql query
	 * @param 	array		configuration: words = max words for results, bid = pageid
	 * @return 	string		html table with results
	 * @todo Define visibility
	 */
	public function listSeveralStats($title, $addwhere, $conf) {
		global $LANG;
		$queryParts['SELECT'] = 'word, COUNT(*) AS c';
		$queryParts['FROM'] = 'index_stat_word';
		$queryParts['WHERE'] = sprintf('pageid= %d ' . $addwhere, $conf['bid']);
		$queryParts['GROUPBY'] = 'word';
		$queryParts['ORDERBY'] = 'c DESC,word';
		$queryParts['LIMIT'] = $conf['words'];
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($queryParts['SELECT'], $queryParts['FROM'], $queryParts['WHERE'], $queryParts['GROUPBY'], $queryParts['ORDERBY'], $queryParts['LIMIT']);
		if ($res) {
			$count = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		} else {
			$count = 0;
		}
		// exist several statistics for this page?
		if ($count > 0) {
			$this->note = $LANG->getLL('justthispage');
		} else {
			// Limit access to pages of the current site
			$secureaddwhere = ' AND pageid IN (' . $this->extGetTreeList($conf['bid'], 100, 0, '1=1') . $conf['bid'] . ') ';
			$this->note = $LANG->getLL('allpages');
			$queryParts['WHERE'] = '1=1 ' . $addwhere . $secureaddwhere;
		}
		// make real query
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($queryParts['SELECT'], $queryParts['FROM'], $queryParts['WHERE'], $queryParts['GROUPBY'], $queryParts['ORDERBY'], $queryParts['LIMIT']);
		$table1 = '';
		$i = 0;
		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$i++;
				$table1 .= '<tr class="bgColor4"><td>' . $i . '.</td><td>' . htmlspecialchars($row['word']) . '</td><td>&nbsp;&nbsp;' . $row['c'] . '</td></tr>';
			}
		}
		if ($i == 0) {
			$table1 = '<tr class="bgColor4"><td callspan="3">' . $LANG->getLL('noresults') . '</td></tr>';
		}
		$table1 = '<table class="bgColor5" cellpadding="2" cellspacing="1"><tr class="tableheader"><td colspan="3">' . $title . '</td></tr>' . $table1 . '</table>';
		return $note . $table1;
	}

	/**
	 * Calls \TYPO3\CMS\Backend\FrontendBackendUserAuthentication::extGetTreeList.
	 * Although this duplicates the function \TYPO3\CMS\Backend\FrontendBackendUserAuthentication::extGetTreeList
	 * this is necessary to create the object that is used recursively by the original function.
	 *
	 * Generates a list of Page-uid's from $id. List does not include $id itself
	 * The only pages excluded from the list are deleted pages.
	 *
	 * @param 	integer		Start page id
	 * @param 	integer		Depth to traverse down the page tree.
	 * @param 	integer		$begin is an optional integer that determines at which level in the tree to start collecting uid's. Zero means 'start right away', 1 = 'next level and out'
	 * @param 	string		Perms clause
	 * @return 	string		Returns the list with a comma in the end (if any pages selected!)
	 * @todo Define visibility
	 */
	public function extGetTreeList($id, $depth, $begin = 0, $perms_clause) {
		// TODO: Fix this as this calls a non-static method
		return \TYPO3\CMS\Backend\FrontendBackendUserAuthentication::extGetTreeList($id, $depth, $begin, $perms_clause);
	}

	/**
	 * Returns an object reference to the hook object if any
	 *
	 * @param 	string		Name of the function you want to call / hook key
	 * @return 	object		Hook object, if any. Otherwise NULL.
	 * @author Kasper Skårhøj
	 * @todo Define visibility
	 */
	public function hookRequest($functionName) {
		// Hook: menuConfig_preProcessModMenu
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['be_hooks'][$functionName]) {
			$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['be_hooks'][$functionName]);
			if (method_exists($hookObj, $functionName)) {
				$hookObj->pObj = $this;
				return $hookObj;
			}
		}
	}

}


?>