<?php

declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

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

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Manage a page tree with all test / demo styleguide data
 */
class Generator extends AbstractGenerator
{

    /**
     * List of handlers to create full table data. There is a
     * "default" handler for casual tables, but some $mainTables
     * like several inline scenarios need more sophisticated
     * handlers.
     *
     * @var array
     */
    protected $tableHandler = [
        TableHandler\StaticData::class,
        TableHandler\InlineMn::class,
        TableHandler\InlineMnGroup::class,
        TableHandler\InlineMnSymmetric::class,
        TableHandler\InlineMnSymmetricGroup::class,
        TableHandler\General::class,
    ];

    /**
     * Create a page tree for styleguide records and add records on them.
     *
     * @throws Exception
     * @throws GeneratorNotFoundException
     */
    public function create(): void
    {
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);

        // Create should not be called if demo data exists already
        if (count($recordFinder->findUidsOfStyleguideEntryPages())) {
            throw new Exception(
                'Can not create a second styleguide demo record tree',
                1597577827
            );
        }

        // Add entry page on top level
        $newIdOfEntryPage = StringUtility::getUniqueId('NEW');
        $data = [
            'pages' => [
                $newIdOfEntryPage => [
                    'title' => 'styleguide TCA demo',
                    'pid' => 0 - $this->getUidOfLastTopLevelPage(),
                    // Mark this page as entry point
                    'tx_styleguide_containsdemo' => 'tx_styleguide',
                    // Have the "globus" icon for this page
                    'is_siteroot' => 1,
                ],
            ],
        ];

        // Add rows of third party tables like be_users and fal
        $this->populateRowsOfThirdPartyTables();

        $highestLanguageId = $recordFinder->findHighestLanguageId();
        $styleguideDemoLanguageIds = range($highestLanguageId + 1, $highestLanguageId + 4);

        // Add a page for each main table below entry page
        $mainTables = $this->getListOfStyleguideMainTables();
        // Have the first main table inside entry page
        $neighborPage = $newIdOfEntryPage;
        foreach ($mainTables as $mainTable) {
            // Add default language page
            $newIdOfPage = StringUtility::getUniqueId('NEW');
            $data['pages'][$newIdOfPage] = [
                'title' => str_replace('_', ' ', substr($mainTable, strlen('tx_styleguide_'))),
                'tx_styleguide_containsdemo' => $mainTable,
                'hidden' => 0,
                'pid' => $neighborPage,
            ];

            // Add page translations for all styleguide languages
            if (!empty($styleguideDemoLanguageIds)) {
                foreach ($styleguideDemoLanguageIds as $languageUid) {
                    $newIdOfLocalizedPage = StringUtility::getUniqueId('NEW');
                    $data['pages'][$newIdOfLocalizedPage] = [
                        'title' => str_replace('_', ' ', substr($mainTable . ' - language ' . $languageUid, strlen('tx_styleguide_'))),
                        'tx_styleguide_containsdemo' => $mainTable,
                        'hidden' => 0,
                        'pid' => $neighborPage,
                        'sys_language_uid' => $languageUid,
                        'l10n_parent' => $newIdOfPage,
                        'l10n_source' => $newIdOfPage,
                    ];
                }
            }
            // Have next page after this page
            $neighborPage = '-' . $newIdOfPage;
        }

        // Populate page tree via DataHandler
        $this->executeDataHandler($data);

        // Create a site configuration on root page
        $topPageUid = $recordFinder->findUidsOfStyleguideEntryPages()[0];
        $this->createSiteConfiguration($topPageUid);

        // Create data for each main table
        foreach ($mainTables as $mainTable) {
            $generator = null;
            foreach ($this->tableHandler as $handlerName) {
                $generator = GeneralUtility::makeInstance($handlerName);
                if (!$generator instanceof TableHandlerInterface) {
                    throw new Exception(
                        'Table handler ' . $handlerName . ' must implement TableHandlerInterface',
                        1458302830
                    );
                }
                if ($generator->match($mainTable)) {
                    break;
                }
                $generator = null;
            }
            if (is_null($generator)) {
                throw new GeneratorNotFoundException(
                    'No table handler found',
                    1458302901
                );
            }
            $generator->handle($mainTable);
        }
    }

    /**
     * Delete all pages and their records that belong to the
     * tx_styleguide demo pages
     */
    public function delete(): void
    {
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);

        $commands = [];

        // Delete page tree and all their records on this tree
        $topUids = $recordFinder->findUidsOfStyleguideEntryPages();
        if (!empty($topUids)) {
            foreach ($topUids as $topUid) {
                $commands['pages'][(int)$topUid]['delete'] = 1;
            }
        }

        // Delete demo users
        $demoUserUids = $recordFinder->findUidsOfDemoBeUsers();
        if (!empty($demoUserUids)) {
            foreach ($demoUserUids as $demoUserUid) {
                $commands['be_users'][(int)$demoUserUid]['delete'] = 1;
            }
        }

        // Delete demo groups
        $demoGroupUids = $recordFinder->findUidsOfDemoBeGroups();
        if (!empty($demoGroupUids)) {
            foreach ($demoGroupUids as $demoUserGroup) {
                $commands['be_groups'][(int)$demoUserGroup]['delete'] = 1;
            }
        }

        // Process commands to delete records
        $this->executeDataHandler([], $commands);

        // Delete demo images in fileadmin again
        $this->deleteFalFolder('styleguide');

        // Delete site configuration
        if ($topUids[0] ?? false) {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId($topUids[0]);
            GeneralUtility::makeInstance(SiteConfiguration::class)->delete($site->getIdentifier());
        }
    }

    /**
     * Add rows for third party tables like be_users or FAL
     */
    protected function populateRowsOfThirdPartyTables(): void
    {
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);

        $demoGroupUids = $recordFinder->findUidsOfDemoBeGroups();
        if (empty($demoGroupUids)) {
            // Add two be_groups and fetch their uids to assign the non-admin be_user to these groups
            $fields = [
                'pid' => 0,
                'hidden' => 1,
                'tx_styleguide_isdemorecord' => 1,
                'title' => 'styleguide demo group 1',
            ];
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_groups');
            $connection->insert('be_groups', $fields);
            $fields['title'] = 'styleguide demo group 2';
            $connection->insert('be_groups', $fields);
            $demoGroupUids = $recordFinder->findUidsOfDemoBeGroups();

            // If there were no groups, it is assumed (!) there are no users either. So they are just created.
            // This may lead to duplicate demo users if a group was manually deleted, but the styleguide
            // "delete" action would delete them all anyway and the next "create" action would create a new set.
            // Also, it may lead to missing be_users if they were manually deleted, but be_groups not.
            // These edge cases are ignored for now.

            // Add two be_users, one admin user, one non-admin user, both hidden and with a random password
            $passwordHash = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('BE');
            $random = GeneralUtility::makeInstance(Random::class);
            $fields = [
                'pid' => 0,
                'disable' => 1,
                'admin' => 0,
                'tx_styleguide_isdemorecord' => 1,
                'username' => 'styleguide demo user 1',
                'usergroup' => implode(',', $demoGroupUids),
                'password' => $passwordHash->getHashedPassword($random->generateRandomBytes(10)),
            ];
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_users');
            $connection->insert('be_users', $fields);
            $fields['admin'] = 1;
            $fields['username'] = 'styleguide demo user 2';
            $fields['usergroup'] = '';
            $fields['password'] = $passwordHash->getHashedPassword($random->generateRandomBytes(10));
            $connection->insert('be_users', $fields);
        }

        // Add 3 files from resources directory to default storage
        $this->addToFal([
            'bus_lane.jpg',
            'telephone_box.jpg',
            'underground.jpg',
        ], 'EXT:styleguide/Resources/Public/Images/Pictures/', 'styleguide');
    }

    /**
     * List of styleguide "main" pages.
     *
     * A styleguide table is either a "main" entry table or a "child" table that
     * belongs to a main table. Each "main" table is located at an own page with all its children.
     *
     * The difference is a naming thing, styleguide tables have a
     * "prefix"_"identifier"_"childidentifier" structure.
     *
     * Example:
     * prefix = tx_styleguide_inline, identifier = 1n
     * -> "tx_styleguide_inline_1n" is a "main" table
     * -> "tx_styleguide_inline_1n1n" is a "child" table
     *
     * In general the list of prefixes is hard coded. If a specific table name is a concatenation
     * of a prefix plus a single word, then the table is considered a "main" table, if there are more
     * than one words after prefix, it is a "child" table.
     *
     * This method return the list of "main" tables.
     *
     * @return array
     */
    protected function getListOfStyleguideMainTables(): array
    {
        $prefixes = [
            'tx_styleguide_',
            'tx_styleguide_ctrl_',
            'tx_styleguide_elements_',
            'tx_styleguide_inline_',
        ];
        $result = [];
        foreach ($GLOBALS['TCA'] as $tablename => $_) {
            if ($tablename === 'tx_styleguide_staticdata') {
                continue;
            }
            foreach ($prefixes as $prefix) {
                if (!str_starts_with($tablename, $prefix)) {
                    continue;
                }

                // See if string after $prefix is only one _ separated segment
                $suffix = substr($tablename, strlen($prefix));
                $suffixArray = explode('_', $suffix);
                if (count($suffixArray) !==  1) {
                    continue;
                }

                // Found a main table
                $result[] = $tablename;

                // No need to scan other prefixes
                break;
            }
        }
        // Manual resorting - the "staticdata" table is used by other tables later.
        // We resort this on top so it is handled first and other tables can rely on
        // created data already. This is a bit hacky but a quick workaround.
        array_unshift($result, 'tx_styleguide_staticdata');
        return $result;
    }
}
