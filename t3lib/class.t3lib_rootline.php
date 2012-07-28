<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Steffen Ritter <steffen.ritter@typo3.org>
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
 * A utility resolving and Caching the Rootline generation
 *
 * @author Steffen Ritter <steffen.ritter@typo3.org>
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_rootline {


	/**
	 * @var int
	 */
	protected $pageUid;

	/**
	 * @var string
	 */
	protected $mountPointParameter;

	/**
	 * @var array
	 */
	protected $parsedMountPointParameters = array();

	/**
	 * @var int
	 */
	protected $languageUid;

	/**
	 * @var int
	 */
	protected $workspaceUid;

	/**
	 * @var t3lib_cache_frontend_Frontend
	 */
	protected static $cache = NULL;

	/**
	 * Fields to fetch when populating rootline data
	 *
	 * @var array
	 */
	protected static $rootlineFields = array(
		'pid',
		'uid',
		't3ver_oid',
		't3ver_wsid',
		't3ver_state',
		'title',
		'alias',
		'nav_title',
		'media',
		'layout',
		'hidden',
		'starttime',
		'endtime',
		'fe_group',
		'extendToSubpages',
		'doktype',
		'TSconfig',
		'storage_pid',
		'is_siteroot',
		'mount_pid',
		'mount_pid_ol',
		'fe_login_mode',
		'backend_layout_next_level'
	);

	/**
	 * @var t3lib_pageSelect
	 */
	protected static $pageSelect;

	/**
	 * @var array
	 */
	protected static $pageRecordCache = array();

	/**
	 * @param int $uid
	 * @param string $mountPointParameter
	 * @param int $languageUid
	 * @param int $workspaceUid
	 * @throws RuntimeException
	 */
	public function __construct($uid, $mountPointParameter = '', $languageUid = 0, $workspaceUid = 0) {
		$this->pageUid = intval($uid);
		$this->mountPointParameter = trim($mountPointParameter);
		$this->languageUid = intval($languageUid);
		$this->workspaceUid = intval($workspaceUid);

		$this->initializeObject();
	}

	/**
	 * Initialize a state to work with
	 *
	 * @throws RuntimeException
	 */
	protected function initializeObject() {


		if ($this->mountPointParameter !== '') {
			if (!$GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids']) {
				throw new RuntimeException('Mount-Point Pages are disabled for this installation. Cannot resolve a Rootline for a page with Mount-Points', 1343462896);
			} else {
				$this->parseMountPointParameter();
			}
		}

		if (self::$cache === NULL) {
			self::$cache = $GLOBALS['typo3CacheManager']->getCache('cache_rootline');
		}

		self::$rootlineFields = array_merge(
			self::$rootlineFields,
			t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'], TRUE)
		);
		array_unique(self::$rootlineFields);

		self::$pageSelect = $GLOBALS['TSFE']->sys_page;
		t3lib_div::loadTCA('pages');
	}

	/**
	 * Constructs the cache Identifier
	 *
	 * @return string
	 */
	public function getCacheIdentifier() {
		return implode('_', array(
			$this->pageUid,
			$this->mountPointParameter,
			$this->languageUid,
			$this->workspaceUid
		));
	}

	/**
	 * Returns the actual rootline
	 * @return array
	 */
	public function get() {
		if (!self::$cache->has($this->getCacheIdentifier())) {
			$this->generateRootlineCache();
		}
		return self::$cache->get($this->getCacheIdentifier());
	}

	/**
	 * Queries the database for the page record and returns it.
	 *
	 * @param $uid Page id
	 * @return array
	 */
	protected function getRecordArray($uid) {
		if (!isset(self::$pageRecordCache[$uid])) {
			$array = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				implode(',', self::$rootlineFields),
				'pages',
				'uid = ' . intval($uid) . ' AND pages.deleted = 0 AND pages.doktype <> ' . t3lib_pageSelect::DOKTYPE_RECYCLER
			);
			self::$pageSelect->versionOL('pages', $array, FALSE, TRUE);
			self::$pageSelect->fixVersioningPid('pages', $array);

			$array = $this->enrichWithRelationFields($uid, $array);

			self::$pageSelect->getPageOverlay($array, $this->languageUid);
			self::$pageRecordCache[$uid] = $array;
		}
		return self::$pageRecordCache[$uid];
	}

	/**
	 *
	 * @param int $uid
	 * @param array $array
	 * @return array
	 */
	protected function enrichWithRelationFields($uid, array $array) {
		foreach ($GLOBALS['TCA']['pages']['columns'] as $column => $configuration) {
			if ($this->columnHasRelationToResolve($configuration)) {
				if ($configuration['MM']) {
					/** @var t3lib_loadDBGroup $loadDBGroup */
					$loadDBGroup = t3lib_div::makeInstance('t3lib_loadDBGroup');
					$loadDBGroup->start(
						$array[$column],
						$configuration['foreign_table'],
						$configuration['MM'],
						$uid,
						'pages',
						$configuration
					);
					$relatedUids = $loadDBGroup->tableArray[$configuration['foreign_table']];
				} elseif ($configuration['foreign_field']) {
					$table = $configuration['foreign_table'];
					$field = $configuration['foreign_field'];
					$whereClause = $field . ' = ' . $uid;
					if (isset($configuration['foreign_table_match']) && is_array($configuration['foreign_table_match'])) {
						$parts = array($whereClause);
						foreach($configuration['foreign_table_match'] AS $field => $value) {
							$parts[] = $field . ' = \'' . $value . '\'';
						}
						$whereClause = implode(' AND ', $parts);
					}
					$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid AS id', $table, $whereClause);
					$relatedUids = array();
					foreach ($rows AS $row) {
						$relatedUids[] = $row['id'];
					}
				}

				$array[$column] = implode(',', $relatedUids);
			}
		}
		return $array;
	}

	/**
	 * Checks whether the TCA Configuration array of a column
	 * describes a relation which is not stored as CSV in the record
	 *
	 * @param array $configuration
	 * @return boolean
	 */
	protected function columnHasRelationToResolve($configuration) {
		if (isset($configuration['MM']) && isset($configuration['type']) && in_array($configuration['type'], array('select', 'inline', 'group'))) {
			return TRUE;
		}
		if (isset($configuration['foreign_key']) && isset($configuration['type']) && in_array($configuration['type'], array('select', 'inline'))) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Actual function to generate the rootline and cache it
	 *
	 * @throws RuntimeException
	 * @return void
	 */
	protected function generateRootlineCache() {
		$page = $this->getRecordArray($this->pageUid);

		if ($page == NULL) {
			throw new RuntimeException('Broken rootline. Could not resolve page with uid ' . $this->pageUid . '.', 1343464101);
		}

			// If the current page is a mounted (according to the MP parameter) handle the mount-point
		if ($this->isMountedPage()) {
			$mountPoint = $this->getRecordArray($this->parsedMountPointParameters[$this->pageUid]);

			$page = $this->processMountedPage($page, $mountPoint);
			$parentUid = $mountPoint['pid'];
				// anyhow after reaching the mount-point, we have to go up that rootline
			unset($this->parsedMountPointParameters[$this->pageUid]);
		} else {
			$parentUid = $page['pid'];
		}

		$cacheTags = array('pageId_' . $page['uid']);

		if ($parentUid > 0) {
				// get Rootline of (and including) parent page
			$mountPointParameter = count($this->parsedMountPointParameters) > 0 ? $this->mountPointParameter : '';
			$rootline = new self($parentUid, $mountPointParameter, $this->languageUid, $this->workspaceUid);
			$rootline = $rootline->get();

				// retrieve Cache Tags of parent rootline
			foreach ($rootline AS $entry) {
				$cacheTags[] = 'pageId_' . $entry['uid'];
				if ($entry['uid'] == $this->pageUid) {
					throw new RuntimeException('Circular connection in rootline for page with uid ' . $this->pageUid . ' found. Check your mountpoint configuration.', 1343464103);
				}
			}
		} else {
			$rootline = array();
		}

		array_push($rootline, $page);

		krsort($rootline);
		self::$cache->set(
			$this->getCacheIdentifier(),
			$rootline,
			$cacheTags
		);
	}

	/**
	 * Checks whether the current Page is a Mounted Page
	 * (according to the MP-URL-Parameter)
	 *
	 * @return boolean
	 */
	protected function isMountedPage() {
		return in_array($this->pageUid, array_keys($this->parsedMountPointParameters));
	}

	/**
	 * Enhances with MountPoint Information or replaces the node if needed
	 *
	 * @param array $mountedPageData
	 * @param array $mountPointPageData
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	protected function processMountedPage(array $mountedPageData, array $mountPointPageData) {
		if ($mountPointPageData['doktype'] == t3lib_pageSelect::DOKTYPE_MOUNTPOINT && $mountPointPageData['mount_pid'] == $mountedPageData['uid']) {
				// current page replaces the original mount-page
			if ($mountPointPageData['mount_pid_ol']) {

				$mountedPageData['_MOUNT_OL'] = TRUE;
				$mountedPageData['_MOUNT_PAGE'] = array(
					'uid' => $mountPointPageData['uid'],
					'pid' => $mountPointPageData['pid'],
					'title' => $mountPointPageData['title']
				);
			} else {
					// the mount-page is not replaced, the mount-page itself has to be used
				$mountedPageData = $mountPointPageData;
			}

			$mountedPageData['_MOUNTED_FROM'] = $this->pageUid;
			$mountedPageData['_MP_PARAM'] = $this->pageUid . '-' . $mountPointPageData['uid'];
			return $mountedPageData;

		} else {
			throw new RuntimeException(
				'Broken rootline. Mountpoint parameter does not match the actual rootline. mount_pid (' . $mountPointPageData['mount_pid'] . ') does not match page uid (' . $mountedPageData['uid'] . ').',
				1343464100);
		}
	}

	/**
	 * Parse the MountPoint Parameters
	 * Splits the MP-Param via "," for several nested mountpoints
	 * and afterwords registers the mountpoint configurations
	 *
	 * @return void
	 */
	protected function parseMountPointParameter() {
		$mountPoints = t3lib_div::trimExplode(',', $this->mountPointParameter);

		foreach ($mountPoints as $mP) {
			list($mountedPageUid, $mountPageUid) = t3lib_div::intExplode('-', $mP);
			$this->parsedMountPointParameters[$mountedPageUid] = $mountPageUid;
		}
	}

}

?>