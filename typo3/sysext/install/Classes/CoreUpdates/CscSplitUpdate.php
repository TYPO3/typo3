<?php
namespace TYPO3\CMS\Install\CoreUpdates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Susanne Moog <typo3@susanne-moog.de>
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
 * Contains the update class for the split of css styled content templates. Used by the update wizard in the install tool.
 *
 * @author Susanne Moog <typo3@susanne-moog.de>
 */
class CscSplitUpdate extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	protected $title = 'Split TypoScript Templates from CSS Styled Content';

	/**
	 * Function which checks if update is needed. Called in the beginning of an update process.
	 *
	 * @param 	string		pointer to description for the update
	 * @return 	boolean		TRUE if update is needs to be performed, FALSE otherwise.
	 * @todo Define visibility
	 */
	public function checkForUpdate(&$description) {
		$templates = $this->getTemplatesWithCsc($dbQueries, $customMessages);
		$templates = $this->findUpdateableTemplatesWithCsc($templates);
		if (count($templates)) {
			$description = '<p>Run this wizard if you use CSS styled content in your templates, as the inclusion of the static templates changed. </p>' . '<p>You are currently using CSS styled content in <strong>' . count($templates) . '&nbsp;templates</strong>  (including deleted and hidden),' . ' so if you did not run this wizard before, <strong>do it now</strong>.</p>' . '<p>The wizard will automatically choose the right template according to your compatibility version. So if you want to ' . 'change the rendering back to an older version, you will have to use the changeCompatibilityVersion wizard above ' . 'first, and then return back to this one.</p>';
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Performs the update itself
	 *
	 * @param 	array		pointer where to insert all DB queries made, so they can be shown to the user if wanted
	 * @param 	string		pointer to output custom messages
	 * @return 	boolean		TRUE if update succeeded, FALSE otherwise
	 * @todo Define visibility
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$templates = $this->getTemplatesWithCsc($dbQueries, $customMessages);
		$templates = $this->findUpdateableTemplatesWithCsc($templates);
		$this->updateCscTemplates($templates, $dbQueries, $customMessages);
		if ($customMessages) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Gets the templates that include the static css styled content template
	 *
	 * @param 	array		pointer where to insert all DB queries made, so they can be shown to the user if wanted
	 * @param 	string		pointer to output custom messages
	 * @return 	array		uid and inclusion string for the templates, that include csc
	 * @todo Define visibility
	 */
	public function getTemplatesWithCsc(&$dbQueries, &$customMessages) {
		$fields = 'uid, include_static_file';
		$table = 'sys_template';
		$where = 'include_static_file LIKE "%EXT:css_styled_content/static/%"';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		$dbQueries[] = str_replace(chr(10), ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);
		if ($GLOBALS['TYPO3_DB']->sql_error()) {
			$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
		}
		$templates = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$templates[] = $row;
		}
		return $templates;
	}

	/**
	 * Take a list of templates and filter them if they need an update or not
	 *
	 * @param 	array		uid and inclusion string for the templates, that include csc
	 * @return 	array		uid and inclusion string for the templates, that include csc and need an update
	 * @todo Define visibility
	 */
	public function findUpdateableTemplatesWithCsc($allTemplates) {
		$compatVersion = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger($GLOBALS['TYPO3_CONF_VARS']['SYS']['compat_version']);
		$currentVersion = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch);
		$templatesCount = count($allTemplates);
		$updateableTemplates = array();
		for ($i = 0; $i < $templatesCount; $i++) {
			$templateNeedsUpdate = FALSE;
			$includedTemplates = explode(',', $allTemplates[$i]['include_static_file']);
			$includedTemplatesCount = count($includedTemplates);
			// loop through every entry in the "include static file"
			for ($j = 0; $j < $includedTemplatesCount; $j++) {
				if (strpos($includedTemplates[$j], 'css_styled_content') !== FALSE) {
					if ($compatVersion <= \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('3.8')) {
						if ($includedTemplates[$j] != 'EXT:css_styled_content/static/v3.8/') {
							$includedTemplates[$j] = 'EXT:css_styled_content/static/v3.8/';
							$templateNeedsUpdate = TRUE;
						}
					} elseif ($compatVersion <= \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('4.1')) {
						if ($includedTemplates[$j] != 'EXT:css_styled_content/static/v3.9/') {
							$includedTemplates[$j] = 'EXT:css_styled_content/static/v3.9/';
							$templateNeedsUpdate = TRUE;
						}
					} elseif ($compatVersion <= \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('4.2')) {
						if ($includedTemplates[$j] != 'EXT:css_styled_content/static/v4.2/') {
							$includedTemplates[$j] = 'EXT:css_styled_content/static/v4.2/';
							$templateNeedsUpdate = TRUE;
						}
					} elseif ($compatVersion <= \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('4.3')) {
						if ($includedTemplates[$j] != 'EXT:css_styled_content/static/v4.3/') {
							$includedTemplates[$j] = 'EXT:css_styled_content/static/v4.3/';
							$templateNeedsUpdate = TRUE;
						}
					} elseif ($compatVersion <= \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('4.4')) {
						if ($includedTemplates[$j] != 'EXT:css_styled_content/static/v4.4/') {
							$includedTemplates[$j] = 'EXT:css_styled_content/static/v4.4/';
							$templateNeedsUpdate = TRUE;
						}
					} elseif ($compatVersion <= \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('4.5')) {
						if ($includedTemplates[$j] != 'EXT:css_styled_content/static/v4.5/') {
							$includedTemplates[$j] = 'EXT:css_styled_content/static/v4.5/';
							$templateNeedsUpdate = TRUE;
						}
					} elseif ($compatVersion === $currentVersion || $compatVersion > '4.6') {
						if ($includedTemplates[$j] != 'EXT:css_styled_content/static/') {
							$includedTemplates[$j] = 'EXT:css_styled_content/static/';
							$templateNeedsUpdate = TRUE;
						}
					}
				}
			}
			$allTemplates[$i]['include_static_file'] = implode(',', $includedTemplates);
			if ($templateNeedsUpdate) {
				$updateableTemplates[] = $allTemplates[$i];
			}
		}
		return $updateableTemplates;
	}

	/**
	 * updates the template records to include the new css styled content templates, according to the current compat version
	 *
	 * @param 	array		template records to update, fetched by getTemplates() and filtered by
	 * @param 	array		pointer where to insert all DB queries made, so they can be shown to the user if wanted
	 * @param 	string		pointer to output custom messages
	 * @todo Define visibility
	 */
	public function updateCscTemplates($templates, &$dbQueries, &$customMessages) {
		foreach ($templates as $template) {
			$table = 'sys_template';
			$where = 'uid =' . $template['uid'];
			$field_values = array(
				'include_static_file' => $template['include_static_file']
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $field_values);
			$dbQueries[] = str_replace(chr(10), ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);
			if ($GLOBALS['TYPO3_DB']->sql_error()) {
				$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
			}
		}
	}

}


?>