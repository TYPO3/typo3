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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Installs and downloads EXT:legacy_collections if requested
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class CollectionsExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var ExtensionModel
     */
    protected $extension;

    /**
     * @var Confirmation
     */
    protected $confirmation;

    public function __construct()
    {
        $this->extension = new ExtensionModel(
            'legacy_collections',
            'sys_collection Database APIs',
            '1.0.0',
            'friendsoftypo3/legacy-collections',
            'Re-Adds previously available sys_collection database tables'
        );

        $this->confirmation = new Confirmation(
            'Are you sure?',
            'This API has not been used very often, only install it if you have entries in your sys_collection database table. ' . $this->extension->getDescription(),
            false
        );
    }

    /**
     * Return a confirmation message instance
     *
     * @return Confirmation
     */
    public function getConfirmation(): Confirmation
    {
        return $this->confirmation;
    }

    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'legacyCollectionsExtension';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Install extension "legacy_collections" from TER for sys_collection database records';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'The extension "legacy_collections" re-adds the database tables sys_collection_* and its TCA definition, if this was previously in use.';
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        // Extension already activated, nothing to do
        if (ExtensionManagementUtility::isLoaded('legacy_collections')) {
            return true;
        }
        // Check if database table exist
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $tableNames = $connection->createSchemaManager()->listTableNames();
        if (in_array('sys_collection', $tableNames, true)) {
            // table is available, now check if there are entries in it
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_collection');
            $numberOfEntries = $queryBuilder->count('*')
                ->from('sys_collection')
                ->executeQuery()
                ->fetchOne();
            return (bool)$numberOfEntries;
        }

        return false;
    }

    /**
     * Returns an array of class names of Prerequisite classes
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }
}
