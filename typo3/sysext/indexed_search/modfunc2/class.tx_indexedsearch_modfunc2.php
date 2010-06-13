<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2010 Dimitri Ebert (dimitri.ebert@dkd.de)
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
 * @author	Dimitri Ebert <dimitri.ebert@dkd.de>
 */

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   60: class tx_indexedsearch_modfunc2 extends t3lib_extobjbase
 *   67:     function main()
 *   88:     function showStats()
 *  121:     function listSeveralStats($title,$addwhere,$conf)
 *  199:     function extGetTreeList($id,$depth,$begin = 0,$perms_clause)
 *  210:     function &hookRequest($functionName)
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Module extension (addition to function menu) 'Indexed search statistics' for the 'indexed_search' extension.
 *
 * @author	Dimitri Ebert <dimitri.ebert@dkd.de>
 * @package TYPO3
 * @subpackage tx_indexedsearch
 */
class tx_indexedsearch_modfunc2 extends t3lib_extobjbase {

	/**
	 * Calls showStats to generate output.
	 *
	 * @return	string		html table with results from showStats()
	 */
	function main()	{
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$theOutput.=$this->pObj->doc->spacer(5);
		$theOutput.=$this->pObj->doc->section($LANG->getLL('title'),$this->showStats(),0,1);

		$menu=array();
		$menu[]=t3lib_BEfunc::getFuncCheck($this->pObj->id,'SET[tx_indexedsearch_modfunc2_check]',$this->pObj->MOD_SETTINGS['tx_indexedsearch_modfunc2_check'],'','','id="checkTx_indexedsearch_modfunc2_check"').'<label for="checkTx_indexedsearch_modfunc2_check"'.$LANG->getLL('checklabel').'</label>';
		$theOutput.=$this->pObj->doc->spacer(5);

		return $theOutput;
	}


	/**
	 * Generates html table containing the statistics.
	 * Calls listSeveralStats 3 times, for all statistics, statistics of the last 30 days and statistics of the last 24 hours.
	 *
	 * @return	string		html table with results
	 */
	function showStats()	{
		global $LANG, $TYPO3_CONF_VARS;

		$conf['words']=50;	// max words in result list
		$conf['bid'] = intval(t3lib_div::_GET('id'));	// pageid for several statistics

		$addwhere1='';	// all records
		$addwhere2=' AND tstamp > ' . ($GLOBALS['EXEC_TIME'] - 30 * 24 * 60 * 60);	// last 30 days
		$addwhere3=' AND tstamp > ' . ($GLOBALS['EXEC_TIME'] - 24 * 60 * 60);		// last 24 hours

		$content.= $LANG->getLL('title2').'
			<table cellpading="5" cellspacing="5" valign="top"><tr><td valign="top">'
			.$this->listSeveralStats($LANG->getLL('all'),$addwhere1,$conf).'</td><td valign="top">'
			.$this->listSeveralStats($LANG->getLL('last30days'),$addwhere2,$conf).'</td><td valign="top">'
			.$this->listSeveralStats($LANG->getLL('last24hours'),$addwhere3,$conf).'</td></tr></table>'
			.$this->note;

			// Ask hook to include more on the page:
		if ($hookObj = $this->hookRequest('additionalSearchStat')) {
			$content.= $hookObj->additionalSearchStat();
		}

		return $content;
	}

	/**
	 * Generates html table with title and several statistics
	 *
	 * @param	string		title for statistic, like 'Last 30 days' or 'Last 24 hours'
	 * @param	string		add where for sql query
	 * @param	array		configuration: words = max words for results, bid = pageid
	 * @return	string		html table with results
	 */
	function listSeveralStats($title,$addwhere,$conf)	{
		global $LANG;

		$queryParts['SELECT'] = 'word, COUNT(*) AS c';
		$queryParts['FROM']='index_stat_word';
		$queryParts['WHERE']=sprintf('pageid= %d '.$addwhere, $conf['bid']);
		$queryParts['GROUPBY']='word';
		$queryParts['ORDERBY']='c DESC,word';
		$queryParts['LIMIT']=$conf['words'];

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$queryParts['SELECT'],
				$queryParts['FROM'],
				$queryParts['WHERE'],
				$queryParts['GROUPBY'],
				$queryParts['ORDERBY'],
				$queryParts['LIMIT']
			);

		if ($res)	{
			$count = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		} else {
			$count = 0;
		}

			// exist several statistics for this page?
		if ($count > 0)	{
			$this->note = 	$LANG->getLL('justthispage');
		} else {
				// Limit access to pages of the current site
			$secureaddwhere = ' AND pageid IN (' . ($this->extGetTreeList($conf['bid'], 100, 0, '1=1')) . $conf['bid'] . ') ';
			$this->note = $LANG->getLL('allpages');

	 		$queryParts['WHERE'] = '1=1 ' . $addwhere . $secureaddwhere;
		}

			// make real query
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$queryParts['SELECT'],
				$queryParts['FROM'],
				$queryParts['WHERE'],
				$queryParts['GROUPBY'],
				$queryParts['ORDERBY'],
				$queryParts['LIMIT']
		);

		$table1='';
		$i=0;
		if ($res)	{
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$i++;
				$table1.='<tr class="bgColor4"><td>'.$i.'.</td><td>'.$row['word'].'</td><td>&nbsp;&nbsp;'.$row['c'].'</td></tr>';
			}
		}

		if ($i==0)	{
			$table1='<tr class="bgColor4"><td callspan="3">'.$LANG->getLL("noresults").'</td></tr>';
		}

		$table1='<table class="bgColor5" cellpadding="2" cellspacing="1"><tr class="tableheader"><td colspan="3">'.$title.'</td></tr>'.$table1.'</table>';

		return $note.$table1;
	}

	/**
	 * Calls t3lib_tsfeBeUserAuth::extGetTreeList.
	 * Although this duplicates the function t3lib_tsfeBeUserAuth::extGetTreeList
	 * this is necessary to create the object that is used recursively by the original function.
	 *
	 * Generates a list of Page-uid's from $id. List does not include $id itself
	 * The only pages excluded from the list are deleted pages.
	 *
	 * @param	integer		Start page id
	 * @param	integer		Depth to traverse down the page tree.
	 * @param	integer		$begin is an optional integer that determines at which level in the tree to start collecting uid's. Zero means 'start right away', 1 = 'next level and out'
	 * @param	string		Perms clause
	 * @return	string		Returns the list with a comma in the end (if any pages selected!)
	 */
	function extGetTreeList($id,$depth,$begin = 0,$perms_clause)	{
		return t3lib_tsfeBeUserAuth::extGetTreeList($id,$depth,$begin,$perms_clause);
	}

	/**
	 * Returns an object reference to the hook object if any
	 *
	 * @param	string		Name of the function you want to call / hook key
	 * @return	object		Hook object, if any. Otherwise null.
	 * @author Kasper Skaarhoj
	 */
	function hookRequest($functionName) {
		global $TYPO3_CONF_VARS;

			// Hook: menuConfig_preProcessModMenu
		if ($TYPO3_CONF_VARS['EXTCONF']['indexed_search']['be_hooks'][$functionName])	{
			$hookObj = t3lib_div::getUserObj($TYPO3_CONF_VARS['EXTCONF']['indexed_search']['be_hooks'][$functionName]);
			if (method_exists ($hookObj, $functionName))	{
				$hookObj->pObj = $this;
				return $hookObj;
			}
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/modfunc2/class.tx_indexedsearch_modfunc2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/modfunc2/class.tx_indexedsearch_modfunc2.php']);
}

?>