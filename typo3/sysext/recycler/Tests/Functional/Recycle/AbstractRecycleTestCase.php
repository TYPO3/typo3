<?php
namespace TYPO3\CMS\Recycler\Tests\Functional\Recycle;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the Export
 */
abstract class AbstractRecycleTestCase extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['recycler'];

    /**
     * The fixture which is used when initializing a backend user
     *
     * @var string
     */
    protected $backendUserFixture = 'typo3/sysext/recycler/Tests/Functional/Fixtures/Database/be_users.xml';

    /**
     * Set up for set up the backend user, initialize the language object
     * and creating the Export instance
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/Database/be_groups.xml');
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();
    }

    /**
     * Retrieves deleted pages using the recycler domain model "deletedRecords" class.
     *
     * @param int $pageUid
     * @param int $depth
     * @return array Retrieved deleted records
     */
    protected function getDeletedPages($pageUid, $depth = 0)
    {
        /** @var $deletedRecords \TYPO3\CMS\Recycler\Domain\Model\DeletedRecords */
        $deletedRecords = GeneralUtility::makeInstance(\TYPO3\CMS\Recycler\Domain\Model\DeletedRecords::class);
        $deletedRecords->loadData($pageUid, 'pages', $depth);
        return $deletedRecords->getDeletedRows();
    }

    /**
     * Retrieves a deleted content elment using the recycler domain model "deletedRecords" class.
     *
     * @param int $contentUid
     * @return array Retrieved deleted records
     */
    protected function getDeletedContent($contentUid)
    {
        /** @var $deletedRecords \TYPO3\CMS\Recycler\Domain\Model\DeletedRecords */
        $deletedRecords = GeneralUtility::makeInstance(\TYPO3\CMS\Recycler\Domain\Model\DeletedRecords::class);
        $deletedRecords->loadData($contentUid, 'tt_content', 0);
        return $deletedRecords->getDeletedRows();
    }

    /**
     * Loads a data set represented as XML and returns it as array.
     *
     * @param string $path Absolute path to the XML file containing the data set to load
     * @return array The records loaded from the data set
     * @throws \Exception
     */
    protected function loadDataSet($path)
    {
        if (!is_file($path)) {
            throw new \Exception(
                'Fixture file ' . $path . ' not found',
                1376746261
            );
        }

        $data = [];
        $fileContent = file_get_contents($path);
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        $xml = simplexml_load_string($fileContent);
        libxml_disable_entity_loader($previousValueOfEntityLoader);

        /** @var $table \SimpleXMLElement */
        foreach ($xml->children() as $table) {
            $record = [];

            /** @var $column \SimpleXMLElement */
            foreach ($table->children() as $column) {
                $columnName = $column->getName();
                $columnValue = null;

                if (isset($column['ref'])) {
                    $columnValue = explode('#', $column['ref']);
                } elseif (isset($column['is-NULL']) && ($column['is-NULL'] === 'yes')) {
                    $columnValue = null;
                } else {
                    $columnValue = (string)$table->$columnName;
                }

                $record[$columnName] = $columnValue;
            }

            $tableName = $table->getName();
            $data[$tableName][] = $record;
        }
        return $data;
    }
}
