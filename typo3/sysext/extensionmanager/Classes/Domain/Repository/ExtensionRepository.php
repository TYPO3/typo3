<?php

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

namespace TYPO3\CMS\Extensionmanager\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * A repository for extensions
 * @internal This class is a specific domain repository implementation and is not part of the Public TYPO3 API.
 */
class ExtensionRepository extends Repository
{
    /**
     * @var string
     */
    const TABLE_NAME = 'tx_extensionmanager_domain_model_extension';

    protected ?QuerySettingsInterface $querySettings = null;

    /**
     * @param QuerySettingsInterface $querySettings
     */
    public function injectQuerySettings(QuerySettingsInterface $querySettings)
    {
        $this->querySettings = $querySettings;
    }

    /**
     * Do not include pid in queries
     */
    public function initializeObject()
    {
        $this->setDefaultQuerySettings($this->querySettings->setRespectStoragePage(false));
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
     * @return array|QueryResultInterface
     */
    public function findAll()
    {
        $query = $this->createQuery();
        $query = $this->addDefaultConstraints($query);
        $query->setOrderings(
            [
                'lastUpdated' => QueryInterface::ORDER_DESCENDING,
            ]
        );
        return $query->execute();
    }

    /**
     * Find an extension by extension key ordered by version
     *
     * @param string $extensionKey
     * @return QueryResultInterface
     */
    public function findByExtensionKeyOrderedByVersion($extensionKey)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('extensionKey', $extensionKey),
                $query->greaterThanOrEqual('reviewState', 0)
            )
        );
        $query->setOrderings(['integerVersion' => QueryInterface::ORDER_DESCENDING]);
        return $query->execute();
    }

    /**
     * Find the current version by extension key
     *
     * @param string $extensionKey
     * @return object|null
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
     * @return object|null
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
            ->executeQuery()
            ->fetchAllAssociative();

        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        return $dataMapper->map(Extension::class, $result);
    }

    /**
     * Find an extension between a certain version range ordered by version number
     *
     * @param string $extensionKey
     * @param int $lowestVersion
     * @param int $highestVersion
     * @param bool $includeCurrentVersion
     * @return QueryResultInterface
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
            'integerVersion' => QueryInterface::ORDER_DESCENDING,
        ]);
        return $query->execute();
    }

    /**
     * Finds all extensions with category "distribution" not published by the TYPO3 CMS Team
     *
     * @param bool $showUnsuitableDistributions
     * @return Extension[]
     */
    public function findAllCommunityDistributions(bool $showUnsuitableDistributions = false): array
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('category', Extension::DISTRIBUTION_CATEGORY),
                $query->logicalNot($query->equals('ownerusername', 'typo3v4'))
            )
        );

        $query->setOrderings([
            'alldownloadcounter' => QueryInterface::ORDER_DESCENDING,
        ]);

        return $this->filterYoungestVersionOfExtensionList($query->execute()->toArray(), $showUnsuitableDistributions);
    }

    /**
     * Finds all extensions with category "distribution" that are published by the TYPO3 CMS Team
     *
     * @param bool $showUnsuitableDistributions
     * @return Extension[]
     */
    public function findAllOfficialDistributions(bool $showUnsuitableDistributions = false): array
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('category', Extension::DISTRIBUTION_CATEGORY),
                $query->equals('ownerusername', 'typo3v4')
            )
        );

        $query->setOrderings([
            'alldownloadcounter' => QueryInterface::ORDER_DESCENDING,
        ]);

        return $this->filterYoungestVersionOfExtensionList($query->execute()->toArray(), $showUnsuitableDistributions);
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
     * @return object|null
     */
    public function findHighestAvailableVersion($extensionKey)
    {
        $query = $this->createQuery();
        $query->matching($query->logicalAnd($query->equals('extensionKey', $extensionKey), $query->greaterThanOrEqual('reviewState', 0)));
        $query->setOrderings([
            'integerVersion' => QueryInterface::ORDER_DESCENDING,
        ]);
        return $query->setLimit(1)->execute()->getFirst();
    }

    /**
     * Adds default constraints to the query - in this case it
     * enables us to always just search for the latest version of an extension
     *
     * @param QueryInterface $query the query to adjust
     * @return QueryInterface
     */
    protected function addDefaultConstraints(QueryInterface $query): QueryInterface
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

    /**
     * Get extensions (out of a given list) that are suitable for the current TYPO3 version
     *
     * @param Extension[] $extensions List of extensions to check
     * @return Extension[] List of extensions suitable for current TYPO3 version
     */
    protected function getExtensionsSuitableForTypo3Version(array $extensions): array
    {
        $suitableExtensions = [];
        foreach ($extensions as $extension) {
            $dependency = $extension->getTypo3Dependency();
            if ($dependency !== null && $dependency->isVersionCompatible(VersionNumberUtility::getNumericTypo3Version())) {
                $suitableExtensions[] = $extension;
            }
        }
        return $suitableExtensions;
    }

    /**
     * Get a list of various extensions in various versions and returns
     * a filtered list containing the extension-version combination with
     * the highest version number.
     *
     * @param Extension[] $extensions
     * @param bool $showUnsuitable
     * @return Extension[]
     */
    protected function filterYoungestVersionOfExtensionList(array $extensions, bool $showUnsuitable): array
    {
        if (!$showUnsuitable) {
            $extensions = $this->getExtensionsSuitableForTypo3Version($extensions);
        }
        $filteredExtensions = [];
        foreach ($extensions as $extension) {
            $extensionKey = $extension->getExtensionKey();
            if (!array_key_exists($extensionKey, $filteredExtensions)) {
                $filteredExtensions[$extensionKey] = $extension;
                continue;
            }
            $currentVersion = $filteredExtensions[$extensionKey]->getVersion();
            $newVersion = $extension->getVersion();
            if (version_compare($newVersion, $currentVersion, '>')) {
                $filteredExtensions[$extensionKey] = $extension;
            }
        }
        return $filteredExtensions;
    }
}
