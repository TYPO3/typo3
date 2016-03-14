<?php
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;

/**
 * Manage a page tree with all test / demo styleguide data
 */
class Generator
{
    /**
     * @return void
     */
    public function create()
    {
        $database = $this->getDatabase();

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

        // Add a page for each main table below entry page
        $mainTables = $this->getListOfStyleguideMainTables();
        // Have the first main table inside entry page
        $neighborPage = $newIdOfEntryPage;
        foreach ($mainTables as $mainTable) {
            $newIdOfPage = StringUtility::getUniqueId('NEW');
            $data['pages'][$newIdOfPage] = [
                'title' => str_replace('_', ' ', substr($mainTable, strlen('tx_styleguide_'))),
                'tx_styleguide_containsdemo' => $mainTable,
                'hidden' => 0,
                'pid' => $neighborPage,
            ];
            // Have next page after this page
            $neighborPage = '-' . $newIdOfPage;
        }

        // Populate page tree via DataHandler
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        BackendUtility::setUpdateSignal('updatePageTree');

        // Add rows of hard coded tables. Must be done *before* the
        // casual records, so those can use hard coded records in relations.
        $this->populateHardCodedTableRows();

        // Create data for each main table
        /** @var RecordData $recordData */
        $recordData = GeneralUtility::makeInstance(RecordData::class);
        // Some tables are manually taken care off, skip them
        $tableBlacklist = [
            'tx_styleguide_staticdata',
        ];
        foreach ($mainTables as $mainTable) {
            if (in_array($mainTable, $tableBlacklist)) {
                continue;
            }
            $fieldValues = $recordData->generate($mainTable);
            $database->exec_INSERTquery($mainTable, $fieldValues);
        }
    }

    /**
     * Delete all pages and their records that belong to the
     * tx_styleguide demo pages
     *
     * @return void
     */
    public function delete()
    {
        /** @var RecordFinder $recordFinder */
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

        // Do the thing
        if (!empty($commands)) {
            /** @var DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->deleteTree = true;
            $dataHandler->start([], $commands);
            $dataHandler->process_cmdmap();
            BackendUtility::setUpdateSignal('updatePageTree');
        }
    }

    /**
     * Add rows for "tx_styleguide_staticdata" hard coded.
     * Add rows for test be_users and test be_groups
     *
     * Table tx_styleguide_staticdata gets a special handling and a
     * couple of records inserted by default.
     *
     * @return void
     */
    protected function populateHardCodedTableRows()
    {
        $database = $this->getDatabase();

        // tx_styleguide_staticdata is used in other TCA demo fields. We need some default
        // rows to later connect other fields to these rows.
        /** @var RecordFinder $recordFinder */
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        $pid = $recordFinder->findPidOfMainTableRecord('tx_styleguide_staticdata');
        $database->exec_INSERTmultipleRows(
            'tx_styleguide_staticdata',
            [ 'pid', 'value_1' ],
            [
                [ $pid, 'foo' ],
                [ $pid, 'bar' ],
                [ $pid, 'foofoo' ],
                [ $pid, 'foobar' ],
            ]
        );

        $demoGroupUids = $recordFinder->findUidsOfDemoBeGroups();
        if (empty($demoGroupUids)) {
            // Add two be_groups and fetch their uids to assign the non-admin be_user to these groups
            $fields = [
                'pid' => 0,
                'hidden' => 1,
                'tx_styleguide_isdemorecord' => 1,
                'title' => 'styleguide demo group 1',
            ];
            $database->exec_INSERTquery('be_groups', $fields);
            $fields['title'] = 'styleguide demo group 2';
            $database->exec_INSERTquery('be_groups', $fields);
            $demoGroupUids = $recordFinder->findUidsOfDemoBeGroups();

            // If there were no groups, it is assumed (!) there are no users either. So they are just created.
            // This may lead to duplicate demo users if a group was manually deleted, but the styleguide
            // "delete" action would delete them all anyway and the next "create" action would create a new set.
            // Also, it may lead to missing be_users if they were manually deleted, but be_groups not.
            // These edge cases are ignored for now.

            // Add two be_users, one admin user, one non-admin user, both hidden and with a random password
            /** @var $saltedpassword \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface */
            $saltedpassword = SaltFactory::getSaltingInstance();
            /** @var Random $random */
            $random = GeneralUtility::makeInstance(Random::class);
            $fields = [
                'pid' => 0,
                'disable' => 1,
                'admin' => 0,
                'tx_styleguide_isdemorecord' => 1,
                'username' => 'styleguide demo user 1',
                'usergroup' => implode(',', $demoGroupUids),
                'password' => $saltedpassword->getHashedPassword($random->generateRandomBytes(10)),
            ];
            $database->exec_INSERTquery('be_users', $fields);
            $fields['admin'] = 1;
            $fields['username'] = 'styleguide demo user 2';
            $fields['usergroup'] = '';
            $fields['password'] = $saltedpassword->getHashedPassword($random->generateRandomBytes(10));
            $database->exec_INSERTquery('be_users', $fields);
        }
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
            'tx_styleguide_elements_',
            'tx_styleguide_inline_',
        ];
        $result = [];
        foreach ($GLOBALS['TCA'] as $tablename => $_) {
            foreach ($prefixes as $prefix) {
                if (!StringUtility::beginsWith($tablename, $prefix)) {
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
        return $result;
    }

    /**
     * Returns the uid of the last "top level" page (has pid 0)
     * in the page tree. This is either a positive integer or 0
     * if no page exists in the page tree at all.
     *
     * @return int
     */
    protected function getUidOfLastTopLevelPage(): int
    {
        $database = $this->getDatabase();
        $lastPage = $database->exec_SELECTgetSingleRow(
            'uid',
            'pages',
            'pid = 0' . BackendUtility::deleteClause('pages'),
            '',
            'sorting DESC'
        );
        $uid = 0;
        if (is_array($lastPage) && count($lastPage) === 1) {
            $uid = (int)$lastPage['uid'];
        }
        return $uid;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabase(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
