<?php
namespace TYPO3\CMS\Install\CoreUpdates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
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
 * Migrates the old media FlexForm to the new
 */
class RootTemplateUpdate extends \TYPO3\CMS\Install\Updates\AbstractUpdate implements \TYPO3\CMS\Install\Updates\InstallerProcessInterface {
	protected $title = 'Integrate TypoScript Root Template';

	/**
	 * Checks whether updates need to be performed
	 *
	 * @param string &$description The description for the update
	 * @param integer &$showUpdate 0=dont show update; 1=show update and next button; 2=only show description
	 * @return boolean
	 */
	public function checkForUpdate(&$description, &$showUpdate = 0) {
		$pages = $this->findRootLevelPages();

		if ($pages !== NULL && $this->findRootTemplates(array_keys($pages)) !== NULL) {
			$description = 'There is already at least one TypoScript root template available.';
			$showUpdate = 0;
		} else {
			$description = 'There is no TypoScript root template! However, one is required for Extbase to behave correctly.';
			$showUpdate = 1;
		}

		return $showUpdate > 0;
	}

	/**
	 * Performs updates and creates one page and Typoscript root template.
	 *
	 * @param 	array		&$dbQueries: queries done in this update
	 * @param 	mixed		&$customMessages: custom messages
	 * @return 	boolean		whether the updated was made or not
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$now = time();
		$result = FALSE;

		$status = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'pages',
			array(
				'pid' => 0,
				'title' => 'Home',
				'is_siteroot' => 1,
				'crdate' => $now,
				'tstamp' => $now,
			)
		);

		$dbQueries[] = str_replace(chr(10), ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);

		if ($status) {
			$pageId = $GLOBALS['TYPO3_DB']->sql_insert_id();

			$status = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'sys_template',
				array(
					'pid' => $pageId,
					'title' => 'Default Root Template',
					'root' => 1,
					'clear' => 1,
					'crdate' => $now,
					'tstamp' => $now,
				)
			);

			$dbQueries[] = str_replace(chr(10), ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);

			if ($status) {
				$result = TRUE;
			}
		}

		return $result;
	}

	/**
	 * Finds pages on the root level (pid 0).
	 *
	 * @return array|NULL
	 */
	protected function findRootLevelPages() {
		$pages = NULL;

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid,pid',
			'pages',
			'pid=0 AND deleted=0',
			'',
			'',
			'',
			'uid'
		);

		if (is_array($rows)) {
			$pages = $rows;
		}

		return $pages;
	}

	/**
	 * Finds root templates in the given pages.
	 *
	 * @param array $pageUids
	 * @return array|NULL
	 */
	protected function findRootTemplates(array $pageUids) {
		$templates = NULL;

		$pageUids = array_map('intval', $pageUids);
		$pageUidList = implode(', ', $pageUids);

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid,pid',
			'sys_template',
			'deleted=0 AND root=1 AND pid IN (' . $pageUidList . ')',
			'',
			'',
			'',
			'uid'
		);

		if (is_array($rows)) {
			$templates = $rows;
		}

		return $templates;
	}
}


?>