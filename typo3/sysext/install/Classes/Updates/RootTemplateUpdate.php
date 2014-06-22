<?php
namespace TYPO3\CMS\Install\Updates;

/**
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

/**
 * Migrates the old media FlexForm to the new
 */
class RootTemplateUpdate extends AbstractUpdate implements InstallerProcessInterface {

	/**
	 * @var string
	 */
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
	 * @param array &$dbQueries Queries done in this update
	 * @param mixed &$customMessages Custom messages
	 * @return boolean Whether the updated was made or not
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

		if (is_array($rows) && !empty($rows)) {
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
