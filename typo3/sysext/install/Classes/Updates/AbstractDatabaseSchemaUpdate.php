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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\SqlExpectedSchemaService;
use TYPO3\CMS\Install\Service\SqlSchemaMigrationService;

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
     * Constructor function.
     */
    public function __construct(SqlSchemaMigrationService $schemaMigrationService = null, SqlExpectedSchemaService $expectedSchemaService = null)
    {
        $this->schemaMigrationService = $schemaMigrationService ?: GeneralUtility::makeInstance(SqlSchemaMigrationService::class);
        $this->expectedSchemaService = $expectedSchemaService ?: GeneralUtility::makeInstance(SqlExpectedSchemaService::class);
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
