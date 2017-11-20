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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     */
    public function initializeObject()
    {
        /** @var $defaultQuerySettings \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface */
        $defaultQuerySettings = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);
        $defaultQuerySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($defaultQuerySettings);
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);

        $searchPlaceholderForLike = '%' . $queryBuilder->escapeLikeWildcards($searchString) . '%';

        $searchConstraints = [
            'extension_key' => $queryBuilder->expr()->eq(
                'extension_key',
                $queryBuilder->createNamedParameter($searchString, \PDO::PARAM_STR)
            ),
            'extension_key_like' => $queryBuilder->expr()->like(
                'extension_key',
                $queryBuilder->createNamedParameter($searchPlaceholderForLike, \PDO::PARAM_STR)
            ),
            'title' => $queryBuilder->expr()->like(
                'title',
                $queryBuilder->createNamedParameter($searchPlaceholderForLike, \PDO::PARAM_STR)
            ),
            'description' => $queryBuilder->expr()->like(
                'description',
                $queryBuilder->createNamedParameter($searchPlaceholderForLike, \PDO::PARAM_STR)
            ),
            'author_name' => $queryBuilder->expr()->like(
                'author_name',
                $queryBuilder->createNamedParameter($searchPlaceholderForLike, \PDO::PARAM_STR)
            ),
        ];

        $caseStatement = 'CASE ' .
            'WHEN ' . $searchConstraints['extension_key'] . ' THEN 16 ' .
            'WHEN ' . $searchConstraints['extension_key_like'] . ' THEN 8 ' .
            'WHEN ' . $searchConstraints['title'] . ' THEN 4 ' .
            'WHEN ' . $searchConstraints['description'] . ' THEN 2 ' .
            'WHEN ' . $searchConstraints['author_name'] . ' THEN 1 ' .
            'END AS ' . $queryBuilder->quoteIdentifier('position');

        $result = $queryBuilder
            ->select('*')
            ->addSelectLiteral($caseStatement)
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->orX(...array_values($searchConstraints)),
                $queryBuilder->expr()->eq('current_version', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                $queryBuilder->expr()->gte('review_state', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->orderBy('position', 'DESC')
            ->execute()
            ->fetchAll();

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
     * For performance reasons, the "native" database connection is used here directly.
     *
     * @param int $repositoryUid
     */
    protected function markExtensionWithMaximumVersionAsCurrent($repositoryUid)
    {
        $uidsOfCurrentVersion = $this->fetchMaximalVersionsForAllExtensions($repositoryUid);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);

        $queryBuilder
            ->update(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($uidsOfCurrentVersion, Connection::PARAM_INT_ARRAY)
                )
            )
            ->set('current_version', 1)
            ->execute();
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);

        $queryResult = $queryBuilder
            ->select('a.uid AS uid')
            ->from(self::TABLE_NAME, 'a')
            ->leftJoin(
                'a',
                self::TABLE_NAME,
                'b',
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('a.repository', $queryBuilder->quoteIdentifier('b.repository')),
                    $queryBuilder->expr()->eq('a.extension_key', $queryBuilder->quoteIdentifier('b.extension_key')),
                    $queryBuilder->expr()->lt('a.integer_version', $queryBuilder->quoteIdentifier('b.integer_version'))
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'a.repository',
                    $queryBuilder->createNamedParameter($repositoryUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->isNull('b.extension_key')
            )
            ->orderBy('a.uid')
            ->execute();

        $extensionUids = [];
        while ($row = $queryResult->fetch()) {
            $extensionUids[] = $row['uid'];
        }

        return $extensionUids;
    }

    /**
     * Returns the number of extensions that are current.
     *
     * @return int
     */
    protected function getNumberOfCurrentExtensions()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE_NAME);

        return (int)$queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->eq(
                'current_version',
                $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
            ))
            ->execute()
            ->fetchColumn(0);
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
