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

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * Migrate extension data import registry keys from path-based to extension key-based format
 *
 * This wizard updates sys_registry entries that were stored with file paths to use
 * extension keys as prefix instead, making them independent of file path changes.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('migrateExtensionDataImportRegistryKeys')]
class MigrateExtensionDataImportRegistryKeysUpdate implements UpgradeWizardInterface
{
    public function getTitle(): string
    {
        return 'Migrate extension data import registry keys';
    }

    public function getDescription(): string
    {
        return 'Updates sys_registry entries for extension data imports from path-based keys to extension key-based keys. ' .
               'This makes the registry entries independent of file path changes and follows the new format introduced ' .
               'in the extension data import system.';
    }

    public function executeUpdate(): bool
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_registry');

        // Get all extensionDataImport registry entries
        $queryBuilder = $connection->createQueryBuilder();
        $result = $queryBuilder
            ->select('entry_key', 'entry_value')
            ->from('sys_registry')
            ->where(
                $queryBuilder->expr()->eq(
                    'entry_namespace',
                    $queryBuilder->createNamedParameter('extensionDataImport')
                )
            )
            ->orderBy('uid')
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            $oldKey = $row['entry_key'];
            $value = $row['entry_value'];

            // Skip entries that already use the new format (contain ":")
            if (str_contains($oldKey, ':') && !str_starts_with($oldKey, 'EXT:')) {
                continue;
            }

            $newKey = $this->convertPathToExtensionKey($oldKey);
            if ($newKey !== null && $newKey !== $oldKey) {
                // Set the new key with the same value
                $registry->set('extensionDataImport', $newKey, unserialize($value, ['allowed_classes' => false]));
                // Remove the old key
                $registry->remove('extensionDataImport', $oldKey);
            }
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_registry');

        $queryBuilder = $connection->createQueryBuilder();
        $count = $queryBuilder
            ->count('*')
            ->from('sys_registry')
            ->where(
                $queryBuilder->expr()->eq(
                    'entry_namespace',
                    $queryBuilder->createNamedParameter('extensionDataImport')
                ),
                $queryBuilder->expr()->notLike(
                    'entry_key',
                    $queryBuilder->createNamedParameter('%:%')
                )
            )
            ->executeQuery()
            ->fetchOne();

        return $count > 0;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Convert a path-based registry key to an extension key-based format
     */
    protected function convertPathToExtensionKey(string $pathKey): ?string
    {
        // Pattern 1: typo3conf/ext/... or typo3/sysext/...
        if (preg_match('#^(?:typo3conf/ext|typo3/sysext)/([^/]+)/(.+)$#', $pathKey, $matches)) {
            $extensionKey = $matches[1];
            $filePart = $matches[2];
            return $extensionKey . ':' . $filePart;
        }

        // Pattern 2: EXT:extension_name/...
        if (preg_match('#^EXT:([^/]+)/(.+)$#', $pathKey, $matches)) {
            $extensionKey = $matches[1];
            $filePart = $matches[2];
            return $extensionKey . ':' . $filePart;
        }

        // Pattern 3: composer-based mode (vendor/...), in this case we take the last path part before
        // "Initialisation/Files" or "ext_tables_static+adt.sql"
        $pathSegments = GeneralUtility::revExplode('/', $pathKey, 2);
        if ($pathSegments[1] === 'Files' || $pathSegments[1] === 'dataImported') {
            $pathSegments[0] = GeneralUtility::revExplode('/', $pathSegments[0], 2)[0];
            $pathSegments[1] = 'Initialisation/' . $pathSegments[1];
        }
        if (preg_match('#^([^/]+)/(.+)$#', $pathSegments[0], $matches) && in_array($pathSegments[1], ['Initialisation/Files', 'Initialisation/dataImported', 'ext_tables_static+adt.sql'])) {
            $extensionKey = GeneralUtility::revExplode('/', $matches[0], 2)[1];
            $extensionKey = str_replace('-', '_', $extensionKey); // Normalize dashes to underscores
            if (str_starts_with($extensionKey, 'cms_')) {
                $extensionKey = substr($extensionKey, strlen('cms_'));
            }
            $filePart = $pathSegments[1];
            return $extensionKey . ':' . $filePart;
        }

        return null;
    }
}
