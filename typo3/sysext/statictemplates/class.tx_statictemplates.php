<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  (c) 2009-2010 Benjamin Mack (benn@typo3.org)
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

class tx_statictemplates {


	/**
	 * Includes static template records from static_template table, loaded through a hook
	 *
	 * @param	string		A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records, "static" for "static_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
	 * @param	string		The id of the current template. Same syntax as $idList ids, eg. "sys_123"
	 * @param	array		The PID of the input template record
	 * @param	array		A full TypoScript template record
	 * @return	void
	 */
	public function includeStaticTypoScriptSources(&$params, &$pObj) {
			// Static Template Records (static_template): include_static is a 
			// list of static templates to include
		if (trim($params['row']['include_static'])) {
			$includeStaticArr = t3lib_div::intExplode(',', $params['row']['include_static']);
				// traversing list
			foreach ($includeStaticArr as $id) {
					// if $id is not already included ...
				if (!t3lib_div::inList($params['idList'], 'static_' . $id)) {
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'static_template', 'uid = ' . intval($id));
						// there was a template, then we fetch that
					if ($subrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						$subrow = $pObj->prependStaticExtra($subrow);
						$pObj->processTemplate($subrow, $params['idList'] . ',static_' . $id, $params['pid'], 'static_' . $id, $params['templateId']);
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}
			}
		}
	}
}

?>
