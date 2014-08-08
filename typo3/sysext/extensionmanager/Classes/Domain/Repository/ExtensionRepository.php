<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Repository;

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
 * A repository for extensions
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class ExtensionRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * @var string
	 */
	const TABLE_NAME = 'tx_extensionmanager_domain_model_extension';

	/**
	 * Oracle has a limit of 1000 values in an IN clause. Set the size of a chunk
	 * being updated to 500 to make sure it does not collide with a limit in any
	 * other DBMS.
	 *
	 * @var integer
	 */
	const CHUNK_SIZE = 500;

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 * @inject
	 */
	protected $dataMapper;

	/**
	 * Do not include pid in queries
	 *
	 * @return void
	 */
	public function initializeObject() {
		/** @var $defaultQuerySettings \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface */
		$defaultQuerySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface');
		$defaultQuerySettings->setRespectStoragePage(FALSE);
		$this->setDefaultQuerySettings($defaultQuerySettings);
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Count all extensions
	 *
	 * @return integer
	 */
	public function countAll() {
		$query = $this->createQuery();
		$query = $this->addDefaultConstraints($query);
		return $query->execute()->count();
	}

	/**
	 * Finds all extensions
	 *
	 * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findAll() {
		$query = $this->createQuery();
		$query = $this->addDefaultConstraints($query);
		$query->setOrderings(
			array(
				'lastUpdated' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
			)
		);
		return $query->execute();
	}

	/**
	 * Find an extension by extension key ordered by version
	 *
	 * @param string $extensionKey
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findByExtensionKeyOrderedByVersion($extensionKey) {
		$query = $this->createQuery();
		$query->matching($query->logicalAnd($query->equals('extensionKey', $extensionKey), $query->greaterThanOrEqual('reviewState', 0)));
		$query->setOrderings(array('version' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING));
		return $query->execute();
	}

	/**
	 * Find the current version by extension key
	 *
	 * @param string $extensionKey
	 * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findOneByCurrentVersionByExtensionKey($extensionKey) {
		$query = $this->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('extensionKey', $extensionKey),
				$query->greaterThanOrEqual('reviewState', 0),
				$query->equals('currentVersion', 1)
			)
		);
		$query->setLimit(1);
		return $query->execute()->getFirst();
	}

	/**
	 * Find one extension by extension key and version
	 *
	 * @param string $extensionKey
	 * @param string $version (example: 4.3.10)
	 * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findOneByExtensionKeyAndVersion($extensionKey, $version) {
		$query = $this->createQuery();
		// Hint: This method must not filter out insecure extensions, if needed,
		// it should be done on a different level, or with a helper method.
		$query->matching($query->logicalAnd(
			$query->equals('extensionKey', $extensionKey),
			$query->equals('version', $version)
		));
		return $query->setLimit(1)->execute()->getFirst();
	}

	/**
	 * Find an extension by title, author name or extension key
	 * This is the function used by the TER search. It is using a
	 * scoring for the matches to sort the extension with an
	 * exact key match on top
	 *
	 * @param string $searchString The string to search for extensions
	 * @return mixed
	 */
	public function findByTitleOrAuthorNameOrExtensionKey($searchString) {
		$quotedSearchString = $this->databaseConnection->escapeStrForLike($this->databaseConnection->quoteStr($searchString, 'tx_extensionmanager_domain_model_extension'), 'tx_extensionmanager_domain_model_extension');
		$quotedSearchStringForLike = '\'%' . $quotedSearchString . '%\'';
		$quotedSearchString = '\'' . $quotedSearchString . '\'';
		$select = 'tx_extensionmanager_domain_model_extension.*,
			(
				(extension_key like ' . $quotedSearchString . ') * 8 +
				(extension_key like ' . $quotedSearchStringForLike . ') * 4 +
				(title like ' . $quotedSearchStringForLike . ') * 2 +
				(author_name like ' . $quotedSearchStringForLike . ')
			) as position';
		$from = 'tx_extensionmanager_domain_model_extension';
		$where = '(
					extension_key = ' . $quotedSearchString . '
					OR
					extension_key LIKE ' . $quotedSearchStringForLike . '
					OR
					title LIKE ' . $quotedSearchStringForLike . '
					OR
					description LIKE ' . $quotedSearchStringForLike . '
					OR
					author_name LIKE ' . $quotedSearchStringForLike . '
				)
				AND current_version=1 AND review_state >= 0
				HAVING position > 0';
		$order = 'position desc';
		$result = $this->databaseConnection->exec_SELECTgetRows($select, $from, $where, '', $order);
		return $this->dataMapper->map('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', $result);
	}

	/**
	 * Find an extension between a certain version range ordered by version number
	 *
	 * @param string $extensionKey
	 * @param integer $lowestVersion
	 * @param integer $highestVersion
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $lowestVersion = 0, $highestVersion = 0) {
		$query = $this->createQuery();
		$constraint = NULL;
		if ($lowestVersion !== 0 && $highestVersion !== 0) {
			$constraint = $query->logicalAnd($query->lessThanOrEqual('integerVersion', $highestVersion), $query->greaterThanOrEqual('integerVersion', $lowestVersion), $query->equals('extensionKey', $extensionKey));
		} elseif ($lowestVersion === 0 && $highestVersion !== 0) {
			$constraint = $query->logicalAnd($query->lessThanOrEqual('integerVersion', $highestVersion), $query->equals('extensionKey', $extensionKey));
		} elseif ($lowestVersion !== 0 && $highestVersion === 0) {
			$constraint = $query->logicalAnd($query->greaterThanOrEqual('integerVersion', $lowestVersion), $query->equals('extensionKey', $extensionKey));
		} elseif ($lowestVersion === 0 && $highestVersion === 0) {
			$constraint = $query->equals('extensionKey', $extensionKey);
		}
		if ($constraint) {
			$query->matching($query->logicalAnd($constraint, $query->greaterThanOrEqual('reviewState', 0)));
		}
		$query->setOrderings(array(
			'integerVersion' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
		));
		return $query->execute();
	}

	/**
	 * Finds all extensions with category "distribution" not published by the TYPO3 CMS Team
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findAllCommunityDistributions() {
		$query = $this->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('category', \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::DISTRIBUTION_CATEGORY),
				$query->equals('currentVersion', 1),
				$query->logicalNot($query->equals('ownerusername', 'typo3v4'))
			)
		);

		$query->setOrderings(array(
			'alldownloadcounter' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
		));

		return $query->execute();
	}

	/**
	 * Finds all extensions with category "distribution" that are published by the TYPO3 CMS Team
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findAllOfficialDistributions() {
		$query = $this->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('category', \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::DISTRIBUTION_CATEGORY),
				$query->equals('currentVersion', 1),
				$query->equals('ownerusername', 'typo3v4')
			)
		);

		$query->setOrderings(array(
			'alldownloadcounter' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
		));

		return $query->execute();
	}

	/**
	 * Count extensions with a certain key between a given version range
	 *
	 * @param string $extensionKey
	 * @param integer $lowestVersion
	 * @param integer $highestVersion
	 * @return integer
	 */
	public function countByVersionRangeAndExtensionKey($extensionKey, $lowestVersion = 0, $highestVersion = 0) {
		return $this->findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $lowestVersion, $highestVersion)->count();
	}

	/**
	 * Find highest version available of an extension
	 *
	 * @param string $extensionKey
	 * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Extension
	 */
	public function findHighestAvailableVersion($extensionKey) {
		$query = $this->createQuery();
		$query->matching($query->logicalAnd($query->equals('extensionKey', $extensionKey), $query->greaterThanOrEqual('reviewState', 0)));
		$query->setOrderings(array(
			'integerVersion' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
		));
		return $query->setLimit(1)->execute()->getFirst();
	}

	/**
	 * Updates the current_version field after update.
	 *
	 * @param int $repositoryUid
	 * @return int
	 */
	public function insertLastVersion($repositoryUid = 1) {
		$this->markExtensionWithMaximumVersionAsCurrent($repositoryUid);

		return $this->getNumberOfCurrentExtensions();
	}

	/**
	 * Sets current_version = 1 for all extensions where the extension version is maximal.
	 *
	 * For performance reasons, the "native" TYPO3_DB is used here directly.
	 *
	 * @param int $repositoryUid
	 * @return void
	 */
	protected function markExtensionWithMaximumVersionAsCurrent($repositoryUid) {
		$uidsOfCurrentVersion = $this->fetchMaximalVersionsForAllExtensions($repositoryUid);

		// some DBMS limit the amount of expressions, apply the update in chunks
		$chunks = array_chunk($uidsOfCurrentVersion, self::CHUNK_SIZE);
		$chunkCount = count($chunks);
		for ($i = 0; $i < $chunkCount; ++$i) {
			$this->databaseConnection->exec_UPDATEquery(
				self::TABLE_NAME,
				'uid IN (' . implode(',', $chunks[$i]) . ')',
				array(
					'current_version' => 1,
				)
			);
		}
	}

	/**
	 * Fetches the UIDs of all maximal versions for all extensions.
	 * This is done by doing a subselect in the WHERE clause to get all
	 * max versions and then the UID of that record in the outer select.
	 *
	 * @param int $repositoryUid
	 * @return array
	 */
	protected function fetchMaximalVersionsForAllExtensions($repositoryUid) {
		$extensionUids = $this->databaseConnection->exec_SELECTgetRows(
			'a.uid AS uid',
			self::TABLE_NAME . ' a',
			'integer_version=(' .
				$this->databaseConnection->SELECTquery(
					'MAX(integer_version)',
					self::TABLE_NAME . ' b',
					'b.repository=' . (int)$repositoryUid . ' AND a.extension_key=b.extension_key'
				) .
			') AND repository=' . (int)$repositoryUid,
			'', '', '', 'uid'
		);
		return array_keys($extensionUids);
	}

	/**
	 * Returns the number of extensions that are current.
	 *
	 * @return int
	 */
	protected function getNumberOfCurrentExtensions() {
		return $this->databaseConnection->exec_SELECTcountRows(
			'*',
			self::TABLE_NAME,
			'current_version = 1'
		);
	}

	/**
	 * Adds default constraints to the query - in this case it
	 * enables us to always just search for the latest version of an extension
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Query $query the query to adjust
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Query
	 */
	protected function addDefaultConstraints(\TYPO3\CMS\Extbase\Persistence\Generic\Query $query) {
		if ($query->getConstraint()) {
			$query->matching($query->logicalAnd(
				$query->getConstraint(),
				$query->equals('current_version', TRUE),
				$query->greaterThanOrEqual('reviewState', 0)
			));
		} else {
			$query->matching($query->logicalAnd(
				$query->equals('current_version', TRUE),
				$query->greaterThanOrEqual('reviewState', 0)
			));
		}
		return $query;
	}
}
