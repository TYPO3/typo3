<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Finishers;

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
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;

/**
 * This finisher saves the data from a submitted form into
 * a database table.
 *
 * Configuration
 * =============
 *
 * options.table (mandatory)
 * -------------
 *   Save or update values into this table
 *
 * options.mode (default: insert)
 * ------------
 *   Possible values are 'insert' or 'update'.
 *
 *   insert: will create a new database row with the values from the
 *           submitted form and/or some predefined values.
 *           @see options.elements and options.databaseFieldMappings
 *   update: will update a given database row with the values from the
 *           submitted form and/or some predefined values.
 *           'options.whereClause' is then required.
 *
 * options.whereClause
 * -------------------
 *   This where clause will be used for an database update action
 *
 * options.elements
 * ----------------
 *   Use this to map form element values to existing database columns.
 *   Each key within options.elements has to match with a
 *   form element identifier within your form definition.
 *   The value for each key within options.elements is an array with
 *   additional information.
 *
 * options.elements.<elementIdentifier>.mapOnDatabaseColumn (mandatory)
 * --------------------------------------------------------
 *   The value from the submitted form element with the identifier
 *   '<elementIdentifier>' will be written into this database column
 *
 * options.elements.<elementIdentifier>.skipIfValueIsEmpty (default: false)
 * ------------------------------------------------------
 *   Set this to true if the database column should not be written
 *   if the value from the submitted form element with the identifier
 *   '<elementIdentifier>' is empty (think about password fields etc.)
 *
 * options.elements.<elementIdentifier>.saveFileIdentifierInsteadOfUid (default: false)
 * -------------------------------------------------------------------
 *   This setting only rules for form elements which creates a FAL object
 *   like FileUpload or ImageUpload.
 *   By default, the uid of the FAL object will be written into
 *   the database column. Set this to true if you want to store the
 *   FAL identifier (1:/user_uploads/some_uploaded_pic.jpg) instead.
 *
 * options.databaseColumnMappings
 * ------------------------------
 *   Use this to map database columns to static values (which can be
 *   made dynamic through typoscript overrides of course).
 *   Each key within options.databaseColumnMappings has to match with a
 *   existing database column.
 *   The value for each key within options.databaseColumnMappings is an
 *   array with additional information.
 *
 *   This mapping is done *before* the options.elements mapping.
 *   This means if you map a database column to a value through
 *   options.databaseColumnMappings and map a submitted form element
 *   value to the same database column, the submitted form element value
 *   will override the value you set within options.databaseColumnMappings.
 *
 * options.databaseColumnMappings.<databaseColumnName>.value
 * ---------------------------------------------------------
 *   The value which will be written to the database column.
 *   You can use the FormRuntime accessor feature to access every
 *   getable property from the TYPO3\CMS\Form\Domain\Runtime\FormRuntime
 *   Read the description within
 *   TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher::parseOption
 *   In short: use something like {<elementIdentifier>} to get the value
 *   from the submitted form element with the identifier
 *   <elementIdentifier>
 *
 *   Don't be confused. If you use the FormRuntime accessor feature within
 *   options.databaseColumnMappings, the functionality is nearly equal
 *   to the the options.elements configuration.
 *
 * options.databaseColumnMappings.<databaseColumnName>.skipIfValueIsEmpty (default: false)
 * ---------------------------------------------------------------------
 *   Set this to true if the database column should not be written
 *   if the value from
 *   options.databaseColumnMappings.<databaseColumnName>.value is empty.
 *
 * Example
 * =======
 *
 *  finishers:
 *    -
 *      identifier: SaveToDatabase
 *      options:
 *        table: 'fe_users'
 *        mode: update
 *        whereClause:
 *          uid: 1
 *        databaseColumnMappings:
 *          pid:
 *            value: 1
 *        elements:
 *          text-1:
 *            mapOnDatabaseColumn: 'first_name'
 *          text-2:
 *            mapOnDatabaseColumn: 'last_name'
 *          text-3:
 *            mapOnDatabaseColumn: 'username'
 *          advancedpassword-1:
 *            mapOnDatabaseColumn: 'password'
 *            skipIfValueIsEmpty: true
 *
 * Multiple database operations
 * ============================
 *
 * You can write options as an array to perform multiple database operations.
 *
 *  finishers:
 *    -
 *      identifier: SaveToDatabase
 *      options:
 *        1:
 *          table: 'my_table'
 *          mode: insert
 *          databaseColumnMappings:
 *            some_column:
 *              value: 'cool'
 *        2:
 *          table: 'my_other_table'
 *          mode: update
 *          whereClause:
 *            pid: 1
 *          databaseColumnMappings:
 *            some_other_column:
 *              value: '{SaveToDatabase.insertedUids.1}'
 *
 * This would perform 2 database operations.
 * One insert and one update.
 * You can access the inserted uids with '{SaveToDatabase.insertedUids.<theArrayKeyNumberWithinOptions>}'
 * If you perform an insert operation, the value of the inserted database row will be stored
 * within the FinisherVariableProvider.
 * <theArrayKeyNumberWithinOptions> references to the numeric key within options
 * within which the insert operation is executed.
 *
 * Scope: frontend
 */
class SaveToDatabaseFinisher extends AbstractFinisher
{

    /**
     * @var array
     */
    protected $defaultOptions = [
        'table' => null,
        'mode' => 'insert',
        'whereClause' => [],
        'elements' => [],
        'databaseColumnMappings' => [],
    ];

    /**
     * @var \TYPO3\CMS\Core\Database\Connection
     */
    protected $databaseConnection;

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     *
     * @throws FinisherException
     */
    protected function executeInternal()
    {
        if (isset($this->options['table'])) {
            $options[] = $this->options;
        } else {
            $options = $this->options;
        }

        foreach ($options as $optionKey => $option) {
            $this->options = $option;
            $this->process($optionKey);
        }
    }

    /**
     * Prepare data for saving to database
     *
     * @param array $elementsConfiguration
     * @param array $databaseData
     * @return mixed
     */
    protected function prepareData(array $elementsConfiguration, array $databaseData)
    {
        foreach ($this->getFormValues() as $elementIdentifier => $elementValue) {
            if (
                ($elementValue === null || $elementValue === '')
                && isset($elementsConfiguration[$elementIdentifier])
                && isset($elementsConfiguration[$elementIdentifier]['skipIfValueIsEmpty'])
                && $elementsConfiguration[$elementIdentifier]['skipIfValueIsEmpty'] === true
            ) {
                continue;
            }

            $element = $this->getElementByIdentifier($elementIdentifier);
            if (
                !$element instanceof FormElementInterface
                || !isset($elementsConfiguration[$elementIdentifier])
                || !isset($elementsConfiguration[$elementIdentifier]['mapOnDatabaseColumn'])
            ) {
                continue;
            }

            if ($elementValue instanceof FileReference) {
                if (isset($elementsConfiguration[$elementIdentifier]['saveFileIdentifierInsteadOfUid'])) {
                    $saveFileIdentifierInsteadOfUid = (bool)$elementsConfiguration[$elementIdentifier]['saveFileIdentifierInsteadOfUid'];
                } else {
                    $saveFileIdentifierInsteadOfUid = false;
                }

                if ($saveFileIdentifierInsteadOfUid) {
                    $elementValue = $elementValue->getOriginalResource()->getCombinedIdentifier();
                } else {
                    $elementValue = $elementValue->getOriginalResource()->getProperty('uid_local');
                }
            } elseif (is_array($elementValue)) {
                $elementValue = implode(',', $elementValue);
            } elseif ($elementValue instanceof \DateTimeInterface) {
                $format = $elementsConfiguration[$elementIdentifier]['dateFormat'] ?? 'U';
                $elementValue = $elementValue->format($format);
            }

            $databaseData[$elementsConfiguration[$elementIdentifier]['mapOnDatabaseColumn']] = $elementValue;
        }
        return $databaseData;
    }

    /**
     * Perform the current database operation
     *
     * @param int $iterationCount
     */
    protected function process(int $iterationCount)
    {
        $this->throwExceptionOnInconsistentConfiguration();

        $table = $this->parseOption('table');
        $elementsConfiguration = $this->parseOption('elements');
        $databaseColumnMappingsConfiguration = $this->parseOption('databaseColumnMappings');

        $this->databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        $databaseData = [];
        foreach ($databaseColumnMappingsConfiguration as $databaseColumnName => $databaseColumnConfiguration) {
            $value = $this->parseOption('databaseColumnMappings.' . $databaseColumnName . '.value');
            if (
                empty($value)
                && $databaseColumnConfiguration['skipIfValueIsEmpty'] === true
            ) {
                continue;
            }

            $databaseData[$databaseColumnName] = $value;
        }

        $databaseData = $this->prepareData($elementsConfiguration, $databaseData);

        $this->saveToDatabase($databaseData, $table, $iterationCount);
    }

    /**
     * Save or insert the values from
     * $databaseData into the table $table
     *
     * @param array $databaseData
     * @param string $table
     * @param int $iterationCount
     */
    protected function saveToDatabase(array $databaseData, string $table, int $iterationCount)
    {
        if (!empty($databaseData)) {
            if ($this->options['mode'] === 'update') {
                $whereClause = $this->options['whereClause'];
                foreach ($whereClause as $columnName => $columnValue) {
                    $whereClause[$columnName] = $this->parseOption('whereClause.' . $columnName);
                }
                $this->databaseConnection->update(
                    $table,
                    $databaseData,
                    $whereClause
                );
            } else {
                $this->databaseConnection->insert($table, $databaseData);
                $insertedUid = (int)$this->databaseConnection->lastInsertId($table);
                $this->finisherContext->getFinisherVariableProvider()->add(
                    $this->shortFinisherIdentifier,
                    'insertedUids.' . $iterationCount,
                    $insertedUid
                );
            }
        }
    }

    /**
     * Throws an exception if some inconsistent configuration
     * are detected.
     *
     * @throws FinisherException
     */
    protected function throwExceptionOnInconsistentConfiguration()
    {
        if (
            $this->options['mode'] === 'update'
            && empty($this->options['whereClause'])
        ) {
            throw new FinisherException(
                'An empty option "whereClause" is not allowed in update mode.',
                1480469086
            );
        }
    }

    /**
     * Returns the values of the submitted form
     *
     * @return array
     */
    protected function getFormValues(): array
    {
        return $this->finisherContext->getFormValues();
    }

    /**
     * Returns a form element object for a given identifier.
     *
     * @param string $elementIdentifier
     * @return FormElementInterface|null
     */
    protected function getElementByIdentifier(string $elementIdentifier)
    {
        return $this
            ->finisherContext
            ->getFormRuntime()
            ->getFormDefinition()
            ->getElementByIdentifier($elementIdentifier);
    }
}
