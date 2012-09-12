<?php
namespace TYPO3\CMS\Statictemplates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2009-2011 Benjamin Mack (benn@typo3.org)
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
 * Statictemplates
 *
 * @author Kasper Skårhøj (kasperYYYY@typo3.com)
 * @author Benjamin Mack (benni@typo3.org)
 */
class StaticTemplatesHook {

	/**
	 * Includes static template records from static_template table, loaded through a hook
	 *
	 * @param array $params
	 * @param object $pObj
	 * @return void
	 */
	public function includeStaticTypoScriptSources(&$params, &$pObj) {
			// Static Template Records (static_template): include_static is a list of static templates to include
		if (trim($params['row']['include_static'])) {
			$includeStaticArr = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $params['row']['include_static']);
				// Traversing list
			foreach ($includeStaticArr as $id) {
					// If $id is not already included ...
				if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($params['idList'], ('static_' . $id))) {
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'static_template', 'uid = ' . intval($id));
						// ... there was a template, then we fetch that
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