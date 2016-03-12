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
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Create data for a specific table and its child tables
 */
class RecordData
{
    /**
     * List of field generators to be called for values.
     * Order is important: Each class is called top-bottom until one returns
     * true on match(), then generate() is called on it.
     *
     * @var array
     */
    protected $fieldValueGenerators = [
        // dbType = date / datetime have ['config']['default'] set, so match them before general ConfigDefault
        FieldGenerator\TypeInputEvalDateDbTypeDate::class,
        FieldGenerator\TypeInputEvalDatetimeDbTypeDatetime::class,

        // Use value from ['config']['default'] if given
        FieldGenerator\ConfigDefault::class,

        // Specific type=input generator
        FieldGenerator\TypeInputMax4::class,
        FieldGenerator\TypeInputEvalAlphanum::class,
        FieldGenerator\TypeInputEvalDate::class,
        FieldGenerator\TypeInputEvalDatetime::class,
        FieldGenerator\TypeInputEvalDouble2::class,
        FieldGenerator\TypeInputEvalInt::class,
        FieldGenerator\TypeInputEvalIsIn::class,
        FieldGenerator\TypeInputEvalMd5::class,
        FieldGenerator\TypeInputEvalNum::class,
        FieldGenerator\TypeInputEvalTime::class,
        FieldGenerator\TypeInputEvalTimesec::class,
        FieldGenerator\TypeInputEvalUpper::class,
        FieldGenerator\TypeInputEvalYear::class,
        FieldGenerator\TypeInputWizardColorPicker::class,
        FieldGenerator\TypeInputWizardLink::class,
        FieldGenerator\TypeInputWizardSelect::class,
        // General type=input generator
        FieldGenerator\TypeInput::class,

        FieldGenerator\TypeTextFormatDatetime::class,
        FieldGenerator\TypeTextMax30::class,
        FieldGenerator\TypeTextWizardSelect::class,
        FieldGenerator\TypeTextWizardTable::class,
        // General type=text generator
        FieldGenerator\TypeText::class,

        // General type=check generator
        FieldGenerator\TypeCheck::class,

        // General type=radio generator
        FieldGenerator\TypeRadio::class,
    ];

    /**
     * Generate data for a given table and insert into database
     *
     * @param string $tableName The tablename to create data for
     * @param int $pid Optional page id of new record. If not given, table is a "main" table and pid is determined ottherwise
     * @return array
     * @throws Exception
     */
    public function generate(string $tableName, int $pid = NULL): array
    {
        if (is_null($pid)) {
            $pid = $this->findPidOfMainTableRecord($tableName);
        }
        $fieldValues = [
            'pid' => $pid,
        ];
        $tca = $GLOBALS['TCA'][$tableName];
        foreach ($tca['columns'] as $fieldName => $fieldConfig) {
            $data = [
                'tableName' => $tableName,
                'fieldName' => $fieldName,
                'fieldConfig' => $fieldConfig,
            ];
            foreach ($this->fieldValueGenerators as $fieldValueGenerator) {
                $generator = GeneralUtility::makeInstance($fieldValueGenerator);
                if (!$generator instanceof FieldGeneratorInterface) {
                    throw new Exception(
                        'Field value generator ' . $fieldValueGenerator . ' must implement FieldGeneratorInterface',
                        1457693564
                    );
                }
                if ($generator->match($data)) {
                    $fieldValues[$fieldName] = $generator->generate($data);
                    break;
                }
            }
        }
        $database = $this->getDatabase();
        $database->exec_INSERTquery($tableName, $fieldValues);
        $fieldValues['uid'] = $database->sql_insert_id();
        return $fieldValues;
    }

    /**
     * "Main" tables have a single page they are located on with their possible children.
     * The methods find this page by getting the highest uid of a page where field
     * tx_styleguide_containsdemo is set to given table name.
     *
     * @param string $tableName
     * @return int
     * @throws Exception
     */
    protected function findPidOfMainTableRecord(string $tableName): int
    {
        $database = $this->getDatabase();
        $row = $database->exec_SELECTgetSingleRow(
            'uid',
            'pages',
            'tx_styleguide_containsdemo=' . $database->fullQuoteStr($tableName, 'pages')
                . BackendUtility::deleteClause('pages'),
            '',
            'pid DESC'
        );
        if (!count($row) === 1) {
            throw new Exception(
                'Found no page for main table ' . $tableName,
                1457690656
            );
        }
        return (int)$row['uid'];
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabase(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }

}