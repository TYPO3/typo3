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

namespace TYPO3\CMS\Impexp\Tests\Functional;

use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Export;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Abstract used by ext:impexp functional tests
 */
abstract class AbstractImportExportTestCase extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $coreExtensionsToLoad = [
        'impexp',
    ];

    /**
     * Absolute path to files that must be removed
     * after a test - handled in tearDown
     *
     * @var array
     */
    protected $testFilesToDelete = [];

    /**
     * Set up for set up the backend user, initialize the language object
     * and creating the Export instance
     */
    protected function setUp(): void
    {
        parent::setUp();

        $backendUser = $this->setUpBackendUserFromFixture(1);
        $backendUser->workspace = 0;
        Bootstrap::initializeLanguageObject();
    }

    /**
     * Tear down for remove of the test files
     */
    protected function tearDown(): void
    {
        foreach ($this->testFilesToDelete as $absoluteFileName) {
            if (@is_file($absoluteFileName)) {
                unlink($absoluteFileName);
            }
        }
        parent::tearDown();
    }

    /**
     * Builds a flat array containing the page tree with the PageTreeView
     * based on given start pid and depth and set it in the Export object.
     *
     * Used in export tests
     *
     * @param $export Export instance
     * @param int $pidToStart
     * @param int $depth
     */
    protected function setPageTree(Export $export, $pidToStart, $depth = 1)
    {
        $permsClause = $GLOBALS['BE_USER']->getPagePermsClause(1);

        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init('AND ' . $permsClause);
        $tree->tree[] = ['row' => $pidToStart];
        $tree->buffer_idH = [];
        if ($depth > 0) {
            $tree->getTree($pidToStart, $depth, '');
        }

        $idH = [];
        $idH[$pidToStart]['uid'] = $pidToStart;
        if (!empty($tree->buffer_idH)) {
            $idH[$pidToStart]['subrow'] = $tree->buffer_idH;
        }

        $export->setPageTree($idH);
    }

    /**
     * Adds records to the export object for a specific page id.
     *
     * Used in export tests.
     *
     * @param $export Export instance
     * @param int $pid Page id for which to select records to add
     * @param array $tables Array of table names to select from
     */
    protected function addRecordsForPid(Export $export, $pid, array $tables)
    {
        foreach ($GLOBALS['TCA'] as $table => $value) {
            if ($table !== 'pages' && (in_array($table, $tables) || in_array('_ALL', $tables))) {
                if ($GLOBALS['BE_USER']->check('tables_select', $table) && !$GLOBALS['TCA'][$table]['ctrl']['is_static']) {
                    $orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?: $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];

                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable($table);

                    $queryBuilder->getRestrictions()
                        ->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

                    $queryBuilder
                        ->select('*')
                        ->from($table)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'pid',
                                $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                            )
                        );

                    foreach (QueryHelper::parseOrderBy((string)$orderBy) as $orderPair) {
                        [$fieldName, $order] = $orderPair;
                        $queryBuilder->addOrderBy($fieldName, $order);
                    }
                    $queryBuilder->addOrderBy('uid', 'ASC');

                    $result = $queryBuilder->execute();
                    while ($row = $result->fetch()) {
                        $export->export_addRecord($table, $this->forceStringsOnRowValues($row));
                    }
                }
            }
        }
    }

    /**
     * All not null values are forced to be strings to align
     * db driver differences
     *
     * @param array $row
     * @return array
     */
    protected function forceStringsOnRowValues(array $row): array
    {
        foreach ($row as $fieldName => $value) {
            // Keep null but force everything else to string
            $row[$fieldName] = $value === null ? $value : (string)$value;
        }
        return $row;
    }

    /**
     * Test if the local filesystem is case sensitive.
     * Needed for some export related tests
     *
     * @return bool
     */
    protected function isCaseSensitiveFilesystem()
    {
        $caseSensitive = true;
        $path = GeneralUtility::tempnam('aAbB');

        // do the actual sensitivity check
        if (@file_exists(strtoupper($path)) && @file_exists(strtolower($path))) {
            $caseSensitive = false;
        }

        // clean filesystem
        unlink($path);
        return $caseSensitive;
    }
}
