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

/**
 * Contains the update class to create and alter tables, fields and keys to comply to the database schema
 */
abstract class AbstractDatabaseSchemaUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService
     */
    protected $schemaMigrationService;

    /**
     * @var \TYPO3\CMS\Install\Service\SqlExpectedSchemaService
     */
    protected $expectedSchemaService;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * Constructor function.
     */
    public function __construct()
    {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->schemaMigrationService = $this->objectManager->get(\TYPO3\CMS\Install\Service\SqlSchemaMigrationService::class);
        $this->expectedSchemaService = $this->objectManager->get(\TYPO3\CMS\Install\Service\SqlExpectedSchemaService::class);
    }

    /**
     * Compare current and expected database schemas and return the database differences
     *
     * @return array database differences
     */
    protected function getDatabaseDifferences()
    {
        $expectedSchema = $this->expectedSchemaService->getExpectedDatabaseSchema();
        $currentSchema = $this->schemaMigrationService->getFieldDefinitions_database();

        // Difference from expected to current
        return $this->schemaMigrationService->getDatabaseExtra($expectedSchema, $currentSchema);
    }
}
