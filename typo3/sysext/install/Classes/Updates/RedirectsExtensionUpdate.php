<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Installs EXT:redirect if sys_domain.redirectTo is filled, and migrates the values from redirectTo
 * to a proper sys_redirect entry.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class RedirectsExtensionUpdate extends AbstractDownloadExtensionUpdate
{
    /**
     * @var \TYPO3\CMS\Install\Updates\Confirmation
     */
    protected $confirmation;

    public function __construct()
    {
        $this->extension = new ExtensionModel(
            'redirects',
            'Redirects',
            '9.2',
            'typo3/cms-redirects',
            'Manage redirects for your TYPO3-based website'
        );

        $this->confirmation = new Confirmation(
            'Are you sure?',
            'You should install the "redirects" extension only if needed. ' . $this->extension->getDescription(),
            true
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
        return 'redirects';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Install system extension "redirects" if a sys_domain entry with redirectTo is necessary';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'The extension "redirects" includes functionality to handle any kind of redirects. '
               . 'The functionality superseds sys_domain entries with the only purpose of redirecting to a different domain or entry. '
               . 'This upgrade wizard installs the redirect extension if necessary and migrates the sys_domain entries to standard redirects.';
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        return $this->checkIfWizardIsRequired();
    }

    /**
     * Performs the update:
     * - Install EXT:redirect
     * - Migrate DB records
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        // Install the EXT:redirects extension if not happened yet
        $installationSuccessful = $this->installExtension($this->extension);
        if ($installationSuccessful) {
            // Migrate the database entries
            $this->migrateRedirectDomainsToSysRedirect();
        }
        return $installationSuccessful;
    }

    /**
     * Check if the database field "sys_domain.redirectTo" exists and if so, if there are entries in the DB table with the field filled.
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function checkIfWizardIsRequired(): bool
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $columns = $connection->getSchemaManager()->listTableColumns('sys_domain');
        if (isset($columns['redirectto'])) {
            // table is available, now check if there are entries in it
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_domain');
            $queryBuilder->getRestrictions()->removeAll();
            $numberOfEntries = $queryBuilder->count('*')
                ->from('sys_domain')
                ->where(
                    $queryBuilder->expr()->neq('redirectTo', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR))
                )
                ->execute()
                ->fetchColumn();
            return (bool)$numberOfEntries;
        }

        return false;
    }

    /**
     * Move all sys_domain records with a "redirectTo" value filled (also deleted) to "sys_redirect" record
     */
    protected function migrateRedirectDomainsToSysRedirect()
    {
        $connDomains = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_domain');
        $connRedirects = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_redirect');

        $queryBuilder = $connDomains->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $domainEntries = $queryBuilder->select('*')
            ->from('sys_domain')
            ->where(
                $queryBuilder->expr()->neq('redirectTo', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR))
            )
            ->execute()
            ->fetchAll();

        foreach ($domainEntries as $domainEntry) {
            $domainName = $domainEntry['domainName'];
            $target = $domainEntry['redirectTo'];
            $sourceDetails = parse_url($domainName);
            $targetDetails = parse_url($target);
            $redirectRecord = [
                'deleted' => (int)$domainEntry['deleted'],
                'disabled' => (int)$domainEntry['hidden'],
                'createdon' => (int)$domainEntry['crdate'],
                'createdby' => (int)$domainEntry['cruser_id'],
                'updatedon' => (int)$domainEntry['tstamp'],
                'source_host' => $sourceDetails['host'] . ($sourceDetails['port'] ? ':' . $sourceDetails['port'] : ''),
                'keep_query_parameters' => (int)$domainEntry['prepend_params'],
                'target_statuscode' => (int)$domainEntry['redirectHttpStatusCode'],
                'target' => $target
            ];

            if (isset($targetDetails['scheme']) && $targetDetails['scheme'] === 'https') {
                $redirectRecord['force_https'] = 1;
            }

            if (empty($sourceDetails['path']) || $sourceDetails['path'] === '/') {
                $redirectRecord['source_path'] = '#.*#';
                $redirectRecord['is_regexp'] = 1;
            } else {
                // Remove the / and add a "/" always before, and at the very end, if path is not empty
                $sourceDetails['path'] = trim($sourceDetails['path'], '/');
                $redirectRecord['source_path'] = '/' . ($sourceDetails['path'] ? $sourceDetails['path'] . '/' : '');
            }

            // Add the redirect record
            $connRedirects->insert('sys_redirect', $redirectRecord);

            // Remove the sys_domain record (hard)
            $connDomains->delete('sys_domain', ['uid' => (int)$domainEntry['uid']]);
        }
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
