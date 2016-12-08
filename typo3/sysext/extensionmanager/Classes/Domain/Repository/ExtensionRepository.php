<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Repository;

/*
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
 */
class ExtensionRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @var string
     */
    const TABLE_NAME = 'tx_extensionmanager_domain_model_extension';

    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected $dataMapper;

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
     */
    public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * Do not include pid in queries
     *
     * @return void
     */
    public function initializeObject()
    {
        /** @var $defaultQuerySettings \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface */
        $defaultQuerySettings = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);
        $defaultQuerySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($defaultQuerySettings);
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Count all extensions
     *
     * @return int
     */
    public function countAll()
    {
        $query = $this->createQuery();
        $query = $this->addDefaultConstraints($query);
        return $query->execute()->count();
    }

    /**
     * Finds all extensions
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAll()
    {
        $query = $this->createQuery();
        $query = $this->addDefaultConstraints($query);
        $query->setOrderings(
            [
                'lastUpdated' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
            ]
        );
        return $query->execute();
    }

    /**
     * Find an extension by extension key ordered by version
     *
     * @param string $extensionKey
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findByExtensionKeyOrderedByVersion($extensionKey)
    {
        $query = $this->createQuery();
        $query->matching($query->logicalAnd($query->equals('extensionKey', $extensionKey), $query->greaterThanOrEqual('reviewState', 0)));
        $query->setOrderings(['integerVersion' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING]);
        return $query->execute();
    }

    /**
     * Find the current version by extension key
     *
     * @param string $extensionKey
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findOneByCurrentVersionByExtensionKey($extensionKey)
    {
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
    public function findOneByExtensionKeyAndVersion($extensionKey, $version)
    {
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
    public function findByTitleOrAuthorNameOrExtensionKey($searchString)
    {
        $quotedSearchString = $this->databaseConnection->escapeStrForLike($this->databaseConnection->quoteStr($searchString, 'tx_extensionmanager_domain_model_extension'), 'tx_extensionmanager_domain_model_extension');
        $quotedSearchStringForLike = '\'%' . $quotedSearchString . '%\'';
        $quotedSearchString = '\'' . $quotedSearchString . '\'';
        $select =
            self::TABLE_NAME . '.*, ' .
            'CASE ' .
                'WHEN extension_key = ' . $quotedSearchString . ' THEN 16 ' .
                'WHEN extension_key LIKE ' . $quotedSearchStringForLike . ' THEN 8 ' .
                'WHEN title LIKE ' . $quotedSearchStringForLike . ' THEN 4 ' .
                'WHEN description LIKE ' . $quotedSearchStringForLike . ' THEN 2 ' .
                'WHEN author_name LIKE ' . $quotedSearchStringForLike . ' THEN 1 ' .
            'END AS position';
        $where = '(
					extension_key = ' . $quotedSearchString . ' OR
					extension_key LIKE ' . $quotedSearchStringForLike . ' OR
					title LIKE ' . $quotedSearchStringForLike . ' OR
					description LIKE ' . $quotedSearchStringForLike . ' OR
					author_name LIKE ' . $quotedSearchStringForLike . '
				)
				AND current_version = 1 AND review_state >= 0';
        $order = 'position DESC';
        $result = $this->databaseConnection->exec_SELECTgetRows($select, self::TABLE_NAME, $where, '', $order);
        return $this->dataMapper->map(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, $result);
    }

    /**
     * Find an extension between a certain version range ordered by version number
     *
     * @param string $extensionKey
     * @param int $lowestVersion
     * @param int $highestVersion
     * @param bool $includeCurrentVersion
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $lowestVersion = 0, $highestVersion = 0, $includeCurrentVersion = true)
    {
        $query = $this->createQuery();
        $constraint = null;
        if ($lowestVersion !== 0 && $highestVersion !== 0) {
            if ($includeCurrentVersion) {
                $constraint = $query->logicalAnd($query->lessThanOrEqual('integerVersion', $highestVersion), $query->greaterThanOrEqual('integerVersion', $lowestVersion), $query->equals('extensionKey', $extensionKey));
            } else {
                $constraint = $query->logicalAnd($query->lessThanOrEqual('integerVersion', $highestVersion), $query->greaterThan('integerVersion', $lowestVersion), $query->equals('extensionKey', $extensionKey));
            }
        } elseif ($lowestVersion === 0 && $highestVersion !== 0) {
            if ($includeCurrentVersion) {
                $constraint = $query->logicalAnd($query->lessThanOrEqual('integerVersion', $highestVersion), $query->equals('extensionKey', $extensionKey));
            } else {
                $constraint = $query->logicalAnd($query->lessThan('integerVersion', $highestVersion), $query->equals('extensionKey', $extensionKey));
            }
        } elseif ($lowestVersion !== 0 && $highestVersion === 0) {
            if ($includeCurrentVersion) {
                $constraint = $query->logicalAnd($query->greaterThanOrEqual('integerVersion', $lowestVersion), $query->equals('extensionKey', $extensionKey));
            } else {
                $constraint = $query->logicalAnd($query->greaterThan('integerVersion', $lowestVersion), $query->equals('extensionKey', $extensionKey));
            }
        } elseif ($lowestVersion === 0 && $highestVersion === 0) {
            $constraint = $query->equals('extensionKey', $extensionKey);
        }
        if ($constraint) {
            $query->matching($query->logicalAnd($constraint, $query->greaterThanOrEqual('reviewState', 0)));
        }
        $query->setOrderings([
            'integerVersion' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
        ]);
        return $query->execute();
    }

    /**
     * Finds all extensions with category "distribution" not published by the TYPO3 CMS Team
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllCommunityDistributions()
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('category', \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::DISTRIBUTION_CATEGORY),
                $query->equals('currentVersion', 1),
                $query->logicalNot($query->equals('ownerusername', 'typo3v4'))
            )
        );

        $query->setOrderings([
            'alldownloadcounter' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
        ]);

        return $query->execute();
    }

    /**
     * Finds all extensions with category "distribution" that are published by the TYPO3 CMS Team
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllOfficialDistributions()
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('category', \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::DISTRIBUTION_CATEGORY),
                $query->equals('currentVersion', 1),
                $query->equals('ownerusername', 'typo3v4')
            )
        );

        $query->setOrderings([
            'alldownloadcounter' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
        ]);

        return $query->execute();
    }

    /**
     * Count extensions with a certain key between a given version range
     *
     * @param string $extensionKey
     * @param int $lowestVersion
     * @param int $highestVersion
     * @return int
     */
    public function countByVersionRangeAndExtensionKey($extensionKey, $lowestVersion = 0, $highestVersion = 0)
    {
        return $this->findByVersionRangeAndExtensionKeyOrderedByVersion($extensionKey, $lowestVersion, $highestVersion)->count();
    }

    /**
     * Find highest version available of an extension
     *
     * @param string $extensionKey
     * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Extension
     */
    public function findHighestAvailableVersion($extensionKey)
    {
        $query = $this->createQuery();
        $query->matching($query->logicalAnd($query->equals('extensionKey', $extensionKey), $query->greaterThanOrEqual('reviewState', 0)));
        $query->setOrderings([
            'integerVersion' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
        ]);
        return $query->setLimit(1)->execute()->getFirst();
    }

    /**
     * Updates the current_version field after update.
     *
     * @param int $repositoryUid
     * @return int
     */
    public function insertLastVersion($repositoryUid = 1)
    {
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
    protected function markExtensionWithMaximumVersionAsCurrent($repositoryUid)
    {
        $uidsOfCurrentVersion = $this->fetchMaximalVersionsForAllExtensions($repositoryUid);

        $this->databaseConnection->exec_UPDATEquery(
            self::TABLE_NAME,
            'uid IN (' . implode(',', $uidsOfCurrentVersion) . ')',
            [
                'current_version' => 1,
            ]
        );
    }

    /**
     * Fetches the UIDs of all maximal versions for all extensions.
     * This is done by doing a LEFT JOIN to itself ("a" and "b") and comparing
     * both integer_version fields.
     *
     * @param int $repositoryUid
     * @return array
     */
    protected function fetchMaximalVersionsForAllExtensions($repositoryUid)
    {
        $queryResult = $this->databaseConnection->sql_query(
            'SELECT a.uid AS uid ' .
            'FROM ' . self::TABLE_NAME . ' a ' .
            'LEFT JOIN ' . self::TABLE_NAME . ' b ON a.repository = b.repository AND a.extension_key = b.extension_key AND a.integer_version < b.integer_version ' .
            'WHERE a.repository = ' . (int)$repositoryUid . ' AND b.extension_key IS NULL ' .
            'ORDER BY a.uid'
        );

        $extensionUids = [];
        while ($row = $this->databaseConnection->sql_fetch_assoc($queryResult)) {
            $extensionUids[] = $row['uid'];
        }
        $this->databaseConnection->sql_free_result($queryResult);
        return $extensionUids;
    }

    /**
     * Returns the number of extensions that are current.
     *
     * @return int
     */
    protected function getNumberOfCurrentExtensions()
    {
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
    protected function addDefaultConstraints(\TYPO3\CMS\Extbase\Persistence\Generic\Query $query)
    {
        if ($query->getConstraint()) {
            $query->matching($query->logicalAnd(
                $query->getConstraint(),
                $query->equals('current_version', true),
                $query->greaterThanOrEqual('reviewState', 0)
            ));
        } else {
            $query->matching($query->logicalAnd(
                $query->equals('current_version', true),
                $query->greaterThanOrEqual('reviewState', 0)
            ));
        }
        return $query;
    }
}
