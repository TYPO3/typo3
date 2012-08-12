<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <typo3@susannemoog.de>
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
 * A repository for extensions
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Repository
 */
class Tx_Extensionmanager_Domain_Repository_ExtensionRepository extends Tx_Extbase_Persistence_Repository {

	/**
	 * @var Tx_Extbase_Persistence_Mapper_DataMapper
	 */
	protected $dataMapper;

	/**
	 * Injects the DataMapper to map records to objects
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
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
	 * @return array|Tx_Extbase_Persistence_QueryResultInterface
	 */
	public function findAll() {
		$query = $this->createQuery();
		$query = $this->addDefaultConstraints($query);
		return $query->execute();
	}

	/**
	 * Find an extension by extension key ordered by version
	 *
	 * @param string $extensionKey
	 * @return Tx_Extbase_Persistence_QueryResultInterface
	 */
	public function findByExtensionKeyOrderedByVersion($extensionKey) {
		$query = $this->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('extensionKey', $extensionKey),
				$query->greaterThanOrEqual('reviewState', 0)
			)
		);
		$query->setOrderings(array('version' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING));
		return $query->execute();
	}

	/**
	 * Find one extension by extension key and version
	 *
	 * @param string $extensionKey
	 * @param string $version (example: 4.3.10)
	 * @return array|Tx_Extbase_Persistence_QueryResultInterface
	 */
	public function findOneByExtensionKeyAndVersion($extensionKey, $version) {
		$query = $this->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('extensionKey', $extensionKey),
				$query->equals('version', $version)
			)
		);
		return $query->setLimit(1)
			->execute()
			->getFirst();
	}

	/**
	 * Find an extension by title, author name or extension key
	 * This is the function used by the TER search. It is using a
	 * scoring for the matches to sort the extension with an
	 * exact key match on top
	 *
	 * @param $searchString the string to search for
	 * @return mixed
	 */
	public function findByTitleOrAuthorNameOrExtensionKey($searchString) {
		$searchStringForLike = '%' . $searchString . '%';
		$select = 'cache_extensions.*,
			(
				(extkey like "' . $searchString . '") * 8 +
				(extkey like "' . $searchStringForLike . '") * 4 +
				(title like "' . $searchStringForLike . '") * 2 +
				(authorname like "' . $searchStringForLike . '")
			) as position';
		$from = 'cache_extensions';
		$where = '(
					extkey = "' . $searchString . '"
					OR
					extkey LIKE "' . $searchStringForLike . '"
					OR
					description LIKE "' . $searchStringForLike . '"
					OR
					title LIKE "' . $searchStringForLike . '"
				)
				AND lastversion=1
				HAVING position > 0';
		$order = 'position desc';
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($select, $from, $where, '', $order);
		return $this->dataMapper->map('Tx_Extensionmanager_Domain_Model_Extension', $result);
	}

	/**
	 * Find an extension between a certain version range ordered by version number
	 *
	 * @param string $extensionKey
	 * @param integer $lowestVersion
	 * @param integer $highestVersion
	 * @return Tx_Extbase_Persistence_QueryResultInterface
	 */
	public function findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $lowestVersion = 0, $highestVersion = 0) {
		$query = $this->createQuery();
		$constraint = NULL;

		if ($lowestVersion !== 0 && $highestVersion !== 0) {
			$constraint = $query->logicalAnd(
				$query->lessThan('integerVersion', $highestVersion),
				$query->greaterThan('integerVersion', $lowestVersion),
				$query->equals('extensionKey', $extensionKey)
			);
		} elseif ($lowestVersion === 0 && $highestVersion !== 0) {
			$constraint = $query->logicalAnd(
				$query->lessThan('integerVersion', $highestVersion),
				$query->equals('extensionKey', $extensionKey)
			);
		} elseif ($lowestVersion !== 0 && $highestVersion === 0) {
			$constraint = $query->logicalAnd(
				$query->greaterThan('integerVersion', $lowestVersion),
				$query->equals('extensionKey', $extensionKey)
			);
		} elseif ($lowestVersion === 0 && $highestVersion === 0) {
			$constraint = $query->equals('extensionKey', $extensionKey);
		}
		if ($constraint) {
			$query->matching(
				$query->logicalAnd(
					$constraint,
					$query->greaterThanOrEqual('reviewState', 0)
				)
			);
		}
		$query->setOrderings(
			array(
				'integerVersion' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
			)
		);
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
	 * @return object
	 */
	public function findHighestAvailableVersion($extensionKey) {
		$query = $this->createQuery();
		$query->matching(
			$query->logicalAnd(
				$query->equals('extensionKey', $extensionKey),
				$query->greaterThanOrEqual('reviewState', 0)
			)
		);
		$query->setOrderings(
			array(
				'integerVersion' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
			)
		);
		return $query->setLimit(1)
			->execute()
			->getFirst();
	}

	/**
	 * Update the lastversion field after update
	 * For performance reason "native" TYPO3_DB is
	 * used here directly.
	 *
	 * @param integer $repositoryUid
	 * @return integer
	 */
	public function insertLastVersion($repositoryUid = 1) {
		$groupedRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'extkey, version, max(intversion) maxintversion',
			'cache_extensions',
			'repository=' . intval($repositoryUid),
			'extkey'
		);
		$extensions = count($groupedRows);

		if ($extensions > 0) {
				// set all to 0
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'cache_extensions',
				'lastversion=1 AND repository=' . intval($repositoryUid),
				array('lastversion' => 0)
			);

				// Find latest version of extensions and set lastversion to 1 for these
			foreach ($groupedRows as $row) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'cache_extensions',
					'extkey=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($row['extkey'], 'cache_extensions') .
						' AND intversion=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($row['maxintversion'], 'cache_extensions') .
						' AND repository=' . intval($repositoryUid),
					array('lastversion' => 1)
				);
			}
		}

		return $extensions;
	}

	/**
	 * Adds default constraints to the query - in this case it
	 * enables us to always just search for the latest version of an extension
	 *
	 * @param Tx_Extbase_Persistence_Query $query the query to adjust
	 * @return Tx_Extbase_Persistence_Query
	 */
	protected function addDefaultConstraints(Tx_Extbase_Persistence_Query $query) {
		if($query->getConstraint()) {
			$query->matching(
				$query->logicalAnd(
					$query->getConstraint(),
					$query->equals('lastversion', TRUE)
				)
			);
		} else {
			$query->matching(
				$query->equals('lastversion', TRUE)
			);
		}
		return $query;
	}
}
?>
