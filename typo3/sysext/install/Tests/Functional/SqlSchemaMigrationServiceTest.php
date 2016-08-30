<?php
namespace TYPO3\CMS\Install\Tests\Functional;

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
 * Functional tests for the SQL schema migration service.
 */
class SqlSchemaMigrationServiceTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService
     */
    protected $sqlSchemaMigrationService;

    /**
     * Initializes a SqlSchemaMigrationService instance.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->sqlSchemaMigrationService = new \TYPO3\CMS\Install\Service\SqlSchemaMigrationService();
    }

    /**
     * @test
     */
    public function columnAndKeyDeletionDoesNotReturnAnError()
    {

        // Get the current database fields.
        $currentDatabaseSchema = $this->sqlSchemaMigrationService->getFieldDefinitions_database();

        // Limit our scope to the be_users table:
        $currentDatabaseSchemaForBeUsers = [];
        $currentDatabaseSchemaForBeUsers['be_users'] = $currentDatabaseSchema['be_users'];
        unset($currentDatabaseSchema);

        // Create a key and a field that belongs to that key:
        $expectedDatabaseSchemaForBeUsers = $currentDatabaseSchemaForBeUsers;
        $expectedDatabaseSchemaForBeUsers['be_users']['fields']['functional_test_field_1'] = "tinyint(1) unsigned NOT NULL default '0'";
        $expectedDatabaseSchemaForBeUsers['be_users']['keys']['functional_test_key_1'] = 'KEY functional_test_key_1 (functional_test_field_1)';
        $createFieldDiff = $this->sqlSchemaMigrationService->getDatabaseExtra($expectedDatabaseSchemaForBeUsers, $currentDatabaseSchemaForBeUsers);
        $createFieldDiff = $this->sqlSchemaMigrationService->getUpdateSuggestions($createFieldDiff);
        $this->sqlSchemaMigrationService->performUpdateQueries($createFieldDiff['add'], $createFieldDiff['add']);

        // Now remove the fields again (without the renaming step).
        unset($currentDatabaseSchemaForBeUsers['be_users']['fields']['functional_test_field_1']);
        unset($currentDatabaseSchemaForBeUsers['be_users']['keys']['functional_test_key_1']);
        $this->sqlSchemaMigrationService->setDeletedPrefixKey('');
        $removeFieldDiff = $this->sqlSchemaMigrationService->getDatabaseExtra($expectedDatabaseSchemaForBeUsers, $currentDatabaseSchemaForBeUsers);
        $removeFieldDiff = $this->sqlSchemaMigrationService->getUpdateSuggestions($removeFieldDiff, 'remove');
        $result = $this->sqlSchemaMigrationService->performUpdateQueries($removeFieldDiff['drop'], $removeFieldDiff['drop']);
        $this->assertTrue($result, 'performUpdateQueries() did not return TRUE, this means an error occurred: ' . (is_array($result) ? array_pop($result) : ''));
    }
}
