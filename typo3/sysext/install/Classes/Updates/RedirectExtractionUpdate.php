<?php
namespace TYPO3\CMS\Install\Updates;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Installs and downloads EXT:rdct if cache_md5params is filled
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class RedirectExtractionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var \TYPO3\CMS\Install\Updates\ExtensionModel
     */
    protected $extension;

    /**
     * @var \TYPO3\CMS\Install\Updates\Confirmation
     */
    protected $confirmation;

    public function __construct()
    {
        $this->extension = new ExtensionModel(
          'rdct',
            'Redirects based on &RDCT parameter',
            '1.0.0',
            'friendsoftypo3/rdct',
            'The extension provides redirects based on "cache_md5params" and the GET parameter &RDCT for extensions that still rely on it.'
        );

        $this->confirmation = new Confirmation(
            'Are you sure?',
            'You should install the Redirects extension only if needed. ' . $this->extension->getDescription(),
            false
        );
    }

    /**
     * Return a confirmation message instance
     *
     * @return \TYPO3\CMS\Install\Updates\Confirmation
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
        return 'rdctExtension';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Install extension "rdct" from TER if DB table cache_md5params is filled';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'The extension "rdct" includes redirects based on the GET parameter &RDCT. The functionality has been extracted to'
               . ' the TYPO3 Extension Repository. This update downloads the TYPO3 extension from the TER.'
               . ' Use this if you are dealing with extensions in the instance that rely on this kind of redirects.';
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        return !ExtensionManagementUtility::isLoaded('rdct') && $this->checkIfWizardIsRequired();
    }

    /**
     * Check if the database table "cache_md5params" exists and if so, if there are entries in the DB table.
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function checkIfWizardIsRequired(): bool
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $tableNames = $connection->getSchemaManager()->listTableNames();
        if (in_array('cache_md5params', $tableNames, true)) {
            // table is available, now check if there are entries in it
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('cache_md5params');
            $numberOfEntries = $queryBuilder->count('*')
                ->from('cache_md5params')
                ->execute()
                ->fetchColumn();
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
            DatabaseUpdatedPrerequisite::class
        ];
    }
}
