<?php
namespace TYPO3\CMS\Statictemplates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Statictemplates for typo script tstemplate menu
 *
 * Shows 'static template selector' and adds handling
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class TypoScriptTemplateModuleControllerHook {

	/**
	 * Render a drop down of available static templates
	 *
	 * @param array $params Params array
	 * @param object $pObj Parent object
	 * @return void
	 */
	public function render(array $params, $pObj) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid', 'static_template', '', '', 'title');
		$opt = '';
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if (substr(trim($row['title']), 0, 8) == 'template') {
				$opt .= '<option value="' . $row['uid'] . '">' . htmlspecialchars($row['title']) . '</option>';
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		$params['selectorHtml'] = '<select name="createStandard"><option></option>' . $opt . '</select><br />';
		$params['staticsText'] = ', optionally based on one of the standard templates';
	}

	/**
	 * Manipulate row data when creating new template
	 *
	 * @param array $params Params array
	 * @param object $pObj Parent object
	 * @return void
	 */
	public function handle(array $params, $pObj) {
		if (intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('createStandard'))) {
			$staticT = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('createStandard'));
			$params['recData']['sys_template']['NEW'] = array(
				'pid' => $params['id'],
				'title' => $GLOBALS['LANG']->getLL('titleNewSiteStandard'),
				'sorting' => 0,
				'root' => 1,
				'clear' => 3,
				'include_static' => $staticT . ',57'
			);
		}
	}
}

?>