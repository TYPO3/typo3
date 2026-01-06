<?php

declare(strict_types=1);

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Enum\ExtensionCategory;

/**
 * A repository for extensions.
 *
 * The implementation does not filter extension marked as insecure, this has
 * to happen on a different level when needed.
 *
 * @internal This class is a specific domain repository implementation and is not part of the Public TYPO3 API.
 */
readonly class ExtensionRepository
{
    private const TABLE_NAME = 'tx_extensionmanager_domain_model_extension';

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function countAll(): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        return $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('current_version', true),
                $queryBuilder->expr()->gte('review_state', 0),
            )
            ->fetchOne();
    }

    /**
     * @return Extension[]
     */
    public function findAll(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $result = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('current_version', true),
                $queryBuilder->expr()->gte('review_state', 0),
            )
            ->orderBy('last_updated', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();
        $extensions = [];
        foreach ($result as $row) {
            $extensions[] = Extension::createObjectFromRow($row);
        }
        return $extensions;
    }

    /**
     * @return Extension[]
     */
    public function findByExtensionKeyOrderedByVersion(string $extensionKey): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $result = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('extension_key', $queryBuilder->createNamedParameter($extensionKey, Connection::PARAM_STR)),
                $queryBuilder->expr()->gte('review_state', 0),
            )
            ->orderBy('integer_version', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();
        $extensions = [];
        foreach ($result as $row) {
            $extensions[] = Extension::createObjectFromRow($row);
        }
        return $extensions;
    }

    public function findByUid(int $uid): ?Extension
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();
        if (!$row) {
            return null;
        }
        return Extension::createObjectFromRow($row);
    }

    public function findOneByCurrentVersionByExtensionKey(string $extensionKey): ?Extension
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $row = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('extension_key', $queryBuilder->createNamedParameter($extensionKey, Connection::PARAM_STR)),
                $queryBuilder->expr()->gte('review_state', 0),
                $queryBuilder->expr()->eq('current_version', 1),
            )
            ->executeQuery()
            ->fetchAssociative();
        if (is_array($row)) {
            return Extension::createObjectFromRow($row);
        }
        return null;
    }

    /**
     * @param string $version (example: 4.3.10)
     */
    public function findOneByExtensionKeyAndVersion(string $extensionKey, string $version): ?Extension
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $row = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('extension_key', $queryBuilder->createNamedParameter($extensionKey, Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('version', $queryBuilder->createNamedParameter($version, Connection::PARAM_STR)),
            )
            ->executeQuery()
            ->fetchAssociative();
        if (is_array($row)) {
            return Extension::createObjectFromRow($row);
        }
        return null;
    }

    /**
     * Find extensions by title, author name or extension key.
     * Uses a simple scoring to sort matches.
     *
     * @param string $searchString The string to search for extensions
     * @return Extension[]
     */
    public function findByTitleOrAuthorNameOrExtensionKey(string $searchString): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $searchPlaceholderForLike = '%' . $queryBuilder->escapeLikeWildcards($searchString) . '%';
        $searchConstraints = [
            'extension_key' => $queryBuilder->expr()->eq(
                'extension_key',
                $queryBuilder->createNamedParameter($searchString)
            ),
            'extension_key_like' => $queryBuilder->expr()->like(
                'extension_key',
                $queryBuilder->createNamedParameter($searchPlaceholderForLike)
            ),
            'title' => $queryBuilder->expr()->like(
                'title',
                $queryBuilder->createNamedParameter($searchPlaceholderForLike)
            ),
            'description' => $queryBuilder->expr()->like(
                'description',
                $queryBuilder->createNamedParameter($searchPlaceholderForLike)
            ),
            'author_name' => $queryBuilder->expr()->like(
                'author_name',
                $queryBuilder->createNamedParameter($searchPlaceholderForLike)
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
                $queryBuilder->expr()->or(...array_values($searchConstraints)),
                $queryBuilder->expr()->eq('current_version', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                $queryBuilder->expr()->in('review_state', $queryBuilder->createNamedParameter([0, -2], Connection::PARAM_INT_ARRAY))
            )
            ->orderBy('position', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();
        $extensions = [];
        foreach ($result as $row) {
            $extensions[] = Extension::createObjectFromRow($row);
        }
        return $extensions;
    }

    /**
     * Find an extension between a certain version range ordered by version number.
     *
     * @return Extension[]
     */
    public function findByVersionRangeAndExtensionKeyOrderedByVersion(string $extensionKey, int $lowestVersion = 0, int $highestVersion = 0, bool $includeCurrentVersion = true): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->select('*')->from(self::TABLE_NAME);
        $constraints = [
            $queryBuilder->expr()->eq('extension_key', $queryBuilder->createNamedParameter($extensionKey, Connection::PARAM_STR)),
        ];
        if ($lowestVersion !== 0 && $highestVersion !== 0) {
            $constraints[] = $queryBuilder->expr()->lte('integer_version', $queryBuilder->createNamedParameter($highestVersion, Connection::PARAM_INT));
            if ($includeCurrentVersion) {
                $constraints[] = $queryBuilder->expr()->gte('integer_version', $queryBuilder->createNamedParameter($lowestVersion, Connection::PARAM_INT));
            } else {
                $constraints[] = $queryBuilder->expr()->gt('integer_version', $queryBuilder->createNamedParameter($lowestVersion, Connection::PARAM_INT));
            }
        } elseif ($lowestVersion === 0 && $highestVersion !== 0) {
            if ($includeCurrentVersion) {
                $constraints[] = $queryBuilder->expr()->lte('integer_version', $queryBuilder->createNamedParameter($highestVersion, Connection::PARAM_INT));
            } else {
                $constraints[] = $queryBuilder->expr()->lt('integer_version', $queryBuilder->createNamedParameter($highestVersion, Connection::PARAM_INT));
            }
        } elseif ($lowestVersion !== 0) {
            if ($includeCurrentVersion) {
                $constraints[] = $queryBuilder->expr()->gte('integer_version', $queryBuilder->createNamedParameter($lowestVersion, Connection::PARAM_INT));
            } else {
                $constraints[] = $queryBuilder->expr()->gt('integer_version', $queryBuilder->createNamedParameter($lowestVersion, Connection::PARAM_INT));
            }
        }
        $constraints[] = $queryBuilder->expr()->gte('review_state', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT));
        $queryBuilder->where(...$constraints);
        $queryBuilder->orderBy('integer_version', 'DESC');
        $result = $queryBuilder->executeQuery()->fetchAllAssociative();
        $extensions = [];
        foreach ($result as $row) {
            $extensions[] = Extension::createObjectFromRow($row);
        }
        return $extensions;
    }

    /**
     * Find all extensions with category "distribution" not published by the TYPO3 CMS Team.
     *
     * @return Extension[]
     */
    public function findAllCommunityDistributions(bool $showUnsuitableDistributions = false): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $result = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('category', $queryBuilder->createNamedParameter(ExtensionCategory::Distribution->value)),
                    $queryBuilder->expr()->neq('ownerusername', $queryBuilder->createNamedParameter('typo3v4'))
                )
            )
            ->orderBy('alldownloadcounter', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();
        $extensions = [];
        foreach ($result as $row) {
            $extensions[] = Extension::createObjectFromRow($row);
        }
        return $this->filterYoungestVersionOfExtensionList($extensions, $showUnsuitableDistributions);
    }

    /**
     * Find all extensions with category "distribution" that have been published by the TYPO3 CMS Team.
     *
     * @return Extension[]
     */
    public function findAllOfficialDistributions(bool $showUnsuitableDistributions = false): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $result = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('category', ExtensionCategory::Distribution->value),
                $queryBuilder->expr()->eq('ownerusername', $queryBuilder->createNamedParameter('typo3v4', Connection::PARAM_STR)),
            )
            ->orderBy('alldownloadcounter', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();
        $extensions = [];
        foreach ($result as $row) {
            $extensions[] = Extension::createObjectFromRow($row);
        }
        return $this->filterYoungestVersionOfExtensionList($extensions, $showUnsuitableDistributions);
    }

    /**
     * Find the highest version available of an extension.
     */
    public function findHighestAvailableVersion(string $extensionKey): ?Extension
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $result = $queryBuilder->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('extension_key', $queryBuilder->createNamedParameter($extensionKey, Connection::PARAM_STR)),
                $queryBuilder->expr()->gte('review_state', 0),
            )
            ->orderBy('integer_version', 'DESC')
            ->executeQuery()
            ->fetchAssociative();
        if (is_array($result)) {
            return Extension::createObjectFromRow($result);
        }
        return null;
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
     * Filter a list of various extensions in various versions to return
     * a list containing the extension-version combination with
     * the highest version number.
     *
     * @param Extension[] $extensions
     * @return Extension[]
     */
    protected function filterYoungestVersionOfExtensionList(array $extensions, bool $showUnsuitable): array
    {
        if (!$showUnsuitable) {
            $extensions = $this->getExtensionsSuitableForTypo3Version($extensions);
        }
        $filteredExtensions = [];
        foreach ($extensions as $extension) {
            $extensionKey = $extension->extensionKey;
            if (!array_key_exists($extensionKey, $filteredExtensions)) {
                $filteredExtensions[$extensionKey] = $extension;
                continue;
            }
            $currentVersion = $filteredExtensions[$extensionKey]->version;
            $newVersion = $extension->version;
            if (version_compare($newVersion, $currentVersion, '>')) {
                $filteredExtensions[$extensionKey] = $extension;
            }
        }
        return $filteredExtensions;
    }
}
