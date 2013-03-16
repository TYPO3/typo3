<?php
namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Steffen Ritter <steffen.ritter@typo3.org>
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
 */
class RootlineUtility {

	/**
	 * @var integer
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
	 * @var integer
	 */
	protected $languageUid = 0;

	/**
	 * @var integer
	 */
	protected $workspaceUid = 0;

	/**
	 * @var boolean
	 */
	protected $versionPreview = FALSE;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
	 */
	static protected $cache = NULL;

	/**
	 * @var array
	 */
	static protected $localCache = array();

	/**
	 * Fields to fetch when populating rootline data
	 *
	 * @var array
	 */
	static protected $rootlineFields = array(
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
	 * Rootline Context
	 *
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository
	 */
	protected $pageContext;

	/**
	 * @var array
	 */
	static protected $pageRecordCache = array();

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * @param int $uid
	 * @param string $mountPointParameter
	 * @param \TYPO3\CMS\Frontend\Page\PageRepository $context
	 * @throws \RuntimeException
	 */
	public function __construct($uid, $mountPointParameter = '', \TYPO3\CMS\Frontend\Page\PageRepository $context = NULL) {
		$this->pageUid = intval($uid);
		$this->mountPointParameter = trim($mountPointParameter);
		if ($context === NULL) {
			if (isset($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE']->sys_page)) {
				$this->pageContext = $GLOBALS['TSFE']->sys_page;
			} else {
				$this->pageContext = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
			}
		} else {
			$this->pageContext = $context;
		}
		$this->initializeObject();
	}

	/**
	 * Initialize a state to work with
	 *
	 * @throws \RuntimeException
	 * @return void
	 */
	protected function initializeObject() {
		$this->languageUid = intval($this->pageContext->sys_language_uid);
		$this->workspaceUid = intval($this->pageContext->versioningWorkspaceId);
		$this->versionPreview = $this->pageContext->versioningPreview;
		if ($this->mountPointParameter !== '') {
			if (!$GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids']) {
				throw new \RuntimeException('Mount-Point Pages are disabled for this installation. Cannot resolve a Rootline for a page with Mount-Points', 1343462896);
			} else {
				$this->parseMountPointParameter();
			}
		}
		if (self::$cache === NULL) {
			self::$cache = $GLOBALS['typo3CacheManager']->getCache('cache_rootline');
		}
		self::$rootlineFields = array_merge(self::$rootlineFields, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'], TRUE));
		self::$rootlineFields = array_unique(self::$rootlineFields);
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Constructs the cache Identifier
	 *
	 * @param integer $otherUid
	 * @return string
	 */
	public function getCacheIdentifier($otherUid = NULL) {
		return implode('_', array(
			$otherUid !== NULL ? intval($otherUid) : $this->pageUid,
			$this->mountPointParameter,
			$this->languageUid,
			$this->workspaceUid,
			$this->versionPreview ? 1 : 0
		));
	}

	/**
	 * Returns the actual rootline
	 *
	 * @return array
	 */
	public function get() {
		$cacheIdentifier = $this->getCacheIdentifier();
		if (!isset(self::$localCache[$cacheIdentifier])) {
			$entry = self::$cache->get($cacheIdentifier);
			if (!$entry) {
				$this->generateRootlineCache();
			} else {
				self::$localCache[$cacheIdentifier] = $entry;
			}
		}
		return self::$localCache[$cacheIdentifier];
	}

	/**
	 * Queries the database for the page record and returns it.
	 *
	 * @param integer $uid Page id
	 * @throws \RuntimeException
	 * @return array
	 */
	protected function getRecordArray($uid) {
		if (!isset(self::$pageRecordCache[$this->getCacheIdentifier($uid)])) {
			$row = $this->databaseConnection->exec_SELECTgetSingleRow(implode(',', self::$rootlineFields), 'pages', 'uid = ' . intval($uid) . ' AND pages.deleted = 0 AND pages.doktype <> ' . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_RECYCLER);
			if (empty($row)) {
				throw new \RuntimeException('Could not fetch page data for uid ' . $uid . '.', 1343589451);
			}
			$this->pageContext->versionOL('pages', $row, FALSE, TRUE);
			$this->pageContext->fixVersioningPid('pages', $row);
			if (is_array($row)) {
				if ($this->languageUid > 0) {
					$row = $this->pageContext->getPageOverlay($row, $this->languageUid);
				}
				$row = $this->enrichWithRelationFields(isset($row['_PAGES_OVERLAY_UID']) ? $row['_PAGES_OVERLAY_UID'] : $uid, $row);
				self::$pageRecordCache[$this->getCacheIdentifier($uid)] = $row;
			}
		}
		if (!is_array(self::$pageRecordCache[$this->getCacheIdentifier($uid)])) {
			throw new \RuntimeException('Broken rootline. Could not resolve page with uid ' . $uid . '.', 1343464101);
		}
		return self::$pageRecordCache[$this->getCacheIdentifier($uid)];
	}

	/**
	 * Resolve relations as defined in TCA and add them to the provided $pageRecord array.
	 *
	 * @param integer $uid Page id
	 * @param array $pageRecord Array with page data to add relation data to.
	 * @throws \RuntimeException
	 * @return array $pageRecord with additional relations
	 */
	protected function enrichWithRelationFields($uid, array $pageRecord) {
		foreach ($GLOBALS['TCA']['pages']['columns'] as $column => $configuration) {
			if ($this->columnHasRelationToResolve($configuration)) {
				$configuration = $configuration['config'];
				if ($configuration['MM']) {
					/** @var $loadDBGroup \TYPO3\CMS\Core\Database\RelationHandler */
					$loadDBGroup = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
					$loadDBGroup->start($pageRecord[$column], $configuration['foreign_table'], $configuration['MM'], $uid, 'pages', $configuration);
					$relatedUids = $loadDBGroup->tableArray[$configuration['foreign_table']];
				} else {
					$table = $configuration['foreign_table'];
					$field = $configuration['foreign_field'];
					$whereClauseParts = array($field . ' = ' . intval($uid));
					if (isset($configuration['foreign_match_fields']) && is_array($configuration['foreign_match_fields'])) {
						foreach ($configuration['foreign_match_fields'] as $field => $value) {
							$whereClauseParts[] = $field . ' = ' . $this->databaseConnection->fullQuoteStr($value, $table);
						}
					}
					if (isset($configuration['foreign_table_field'])) {
						if (intval($this->languageUid) > 0) {
							$whereClauseParts[] = trim($configuration['foreign_table_field']) . ' = \'pages_language_overlay\'';
						} else {
							$whereClauseParts[] = trim($configuration['foreign_table_field']) . ' = \'pages\'';
						}
					}
					$whereClause = implode(' AND ', $whereClauseParts);
					$whereClause .= $this->pageContext->deleteClause($table);
					$rows = $this->databaseConnection->exec_SELECTgetRows('uid', $table, $whereClause);
					if (!is_array($rows)) {
						throw new \RuntimeException('Could to resolve related records for page ' . $uid . ' and foreign_table ' . htmlspecialchars($configuration['foreign_table']), 1343589452);
					}
					$relatedUids = array();
					foreach ($rows as $row) {
						$relatedUids[] = $row['uid'];
					}
				}
				$pageRecord[$column] = implode(',', $relatedUids);
			}
		}
		return $pageRecord;
	}

	/**
	 * Checks whether the TCA Configuration array of a column
	 * describes a relation which is not stored as CSV in the record
	 *
	 * @param array $configuration TCA configuration to check
	 * @return boolean TRUE, if it describes a non-CSV relation
	 */
	protected function columnHasRelationToResolve(array $configuration) {
		$configuration = $configuration['config'];
		if (!empty($configuration['MM']) && !empty($configuration['type']) && in_array($configuration['type'], array('select', 'inline', 'group'))) {
			return TRUE;
		}
		if (!empty($configuration['foreign_field']) && !empty($configuration['type']) && in_array($configuration['type'], array('select', 'inline'))) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Actual function to generate the rootline and cache it
	 *
	 * @throws \RuntimeException
	 * @return void
	 */
	protected function generateRootlineCache() {
		$page = $this->getRecordArray($this->pageUid);
		// If the current page is a mounted (according to the MP parameter) handle the mount-point
		if ($this->isMountedPage()) {
			$mountPoint = $this->getRecordArray($this->parsedMountPointParameters[$this->pageUid]);
			$page = $this->processMountedPage($page, $mountPoint);
			$parentUid = $mountPoint['pid'];
			// Anyhow after reaching the mount-point, we have to go up that rootline
			unset($this->parsedMountPointParameters[$this->pageUid]);
		} else {
			$parentUid = $page['pid'];
		}
		$cacheTags = array('pageId_' . $page['uid']);
		if ($parentUid > 0) {
			// Get rootline of (and including) parent page
			$mountPointParameter = count($this->parsedMountPointParameters) > 0 ? $this->mountPointParameter : '';
			/** @var $rootline \TYPO3\CMS\Core\Utility\RootlineUtility */
			$rootline = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\RootlineUtility', $parentUid, $mountPointParameter, $this->pageContext);
			$rootline = $rootline->get();
			// retrieve cache tags of parent rootline
			foreach ($rootline as $entry) {
				$cacheTags[] = 'pageId_' . $entry['uid'];
				if ($entry['uid'] == $this->pageUid) {
					throw new \RuntimeException('Circular connection in rootline for page with uid ' . $this->pageUid . ' found. Check your mountpoint configuration.', 1343464103);
				}
			}
		} else {
			$rootline = array();
		}
		array_push($rootline, $page);
		krsort($rootline);
		self::$cache->set($this->getCacheIdentifier(), $rootline, $cacheTags);
		self::$localCache[$this->getCacheIdentifier()] = $rootline;
	}

	/**
	 * Checks whether the current Page is a Mounted Page
	 * (according to the MP-URL-Parameter)
	 *
	 * @return boolean
	 */
	public function isMountedPage() {
		return in_array($this->pageUid, array_keys($this->parsedMountPointParameters));
	}

	/**
	 * Enhances with mount point information or replaces the node if needed
	 *
	 * @param array $mountedPageData page record array of mounted page
	 * @param array $mountPointPageData page record array of mount point page
	 * @throws \RuntimeException
	 * @return array
	 */
	protected function processMountedPage(array $mountedPageData, array $mountPointPageData) {
		if ($mountPointPageData['mount_pid'] != $mountedPageData['uid']) {
			throw new \RuntimeException('Broken rootline. Mountpoint parameter does not match the actual rootline. mount_pid (' . $mountPointPageData['mount_pid'] . ') does not match page uid (' . $mountedPageData['uid'] . ').', 1343464100);
		}
		// Current page replaces the original mount-page
		if ($mountPointPageData['mount_pid_ol']) {
			$mountedPageData['_MOUNT_OL'] = TRUE;
			$mountedPageData['_MOUNT_PAGE'] = array(
				'uid' => $mountPointPageData['uid'],
				'pid' => $mountPointPageData['pid'],
				'title' => $mountPointPageData['title']
			);
		} else {
			// The mount-page is not replaced, the mount-page itself has to be used
			$mountedPageData = $mountPointPageData;
		}
		$mountedPageData['_MOUNTED_FROM'] = $this->pageUid;
		$mountedPageData['_MP_PARAM'] = $this->pageUid . '-' . $mountPointPageData['uid'];
		return $mountedPageData;
	}

	/**
	 * Parse the MountPoint Parameters
	 * Splits the MP-Param via "," for several nested mountpoints
	 * and afterwords registers the mountpoint configurations
	 *
	 * @return void
	 */
	protected function parseMountPointParameter() {
		$mountPoints = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->mountPointParameter);
		foreach ($mountPoints as $mP) {
			list($mountedPageUid, $mountPageUid) = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode('-', $mP);
			$this->parsedMountPointParameters[$mountedPageUid] = $mountPageUid;
		}
	}

}


?>
