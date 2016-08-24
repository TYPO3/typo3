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

use Doctrine\DBAL\Schema\SchemaDiff;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains the update class to create and alter tables, fields and keys to comply to the database schema
 */
abstract class AbstractDatabaseSchemaUpdate extends AbstractUpdate
{
    /**
     * @var \TYPO3\CMS\Core\Database\Schema\SchemaMigrator
     */
    protected $schemaMigrationService;

    protected $listTemplate = '
		<p>%1$s</p>
		<fieldset>
			<ol class="t3-install-form-label-after">%2$s</ol>
		</fieldset>
    ';

    /**
     * Template for list items consisting of table and field names
     *
     * @var string
     */
    protected $fieldListItem = '
		<li class="labelAfter">
			<label><strong>%1$s</strong>: %2$s</label>
		</li>
	';

    /**
     * Template for list items consisting of table names
     *
     * @var string
     */
    protected $tableListItem = '
		<li class="labelAfter">
			<label><strong>%1$s</strong></label>
		</li>
	';

    /**
     * Constructor function.
     *
     * @param \TYPO3\CMS\Core\Database\Schema\SchemaMigrator $schemaMigrationService
     * @throws \InvalidArgumentException
     */
    public function __construct(SchemaMigrator $schemaMigrationService = null)
    {
        $this->schemaMigrationService = $schemaMigrationService ?: GeneralUtility::makeInstance(
            SchemaMigrator::class
        );
    }

    /**
     * Compare current and expected database schemas and return the database differences
     *
     * @return SchemaDiff[] database differences as Doctrine SchemaDiff objects (per connection)
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\StatementException
     */
    protected function getDatabaseDifferences(): array
    {
        $statements = $this->getDatabaseDefinition();

        return $this->schemaMigrationService->getSchemaDiffs($statements);
    }

    /**
     * Get list of CREATE TABLE statements from all ext_tables.sql files
     *
     * @return string[]
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \InvalidArgumentException
     */
    protected function getDatabaseDefinition(): array
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);

        return $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
    }

    /**
     * @param string $tableName
     * @param string $fieldName
     * @return string
     */
    protected function renderFieldListItem(string $tableName, string $fieldName): string
    {
        return sprintf($this->fieldListItem, $tableName, $fieldName);
    }

    /**
     * @param string $tableName
     * @return string
     */
    protected function renderTableListItem(string $tableName): string
    {
        return sprintf($this->tableListItem, $tableName);
    }

    /**
     * @param string $label
     * @param string $items
     * @return string
     */
    protected function renderList(string $label, string $items): string
    {
        if (trim($items) === '') {
            return '';
        }

        return sprintf($this->listTemplate, $label, $items);
    }
}
