<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

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
 * Test case
 */
class DatabaseSpecificsPostgresqlTest extends DatabaseSpecificsTest
{
    /**
     * Set up
     */
    protected function setUp()
    {
        $GLOBALS['TYPO3_LOADED_EXT'] = [];

        /** @var \TYPO3\CMS\Dbal\Database\Specifics\AbstractSpecifics|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $this->subject = GeneralUtility::makeInstance($this->buildAccessibleProxy(\TYPO3\CMS\Dbal\Database\Specifics\PostgresSpecifics::class));
    }

    /**
     * @test
     * @param array $fieldDefinition
     * @param string $expected
     * @dataProvider getNativeDefaultValueProvider
     */
    public function getNativeDefaultValueStripsPostgresqlCharacterClasses($fieldDefinition, $expected)
    {
        $actual = $this->subject->_call('getNativeDefaultValue', $fieldDefinition);
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @param array $fieldDefinition
     * @param string $expected
     * @dataProvider getNativeExtraFieldAttributeProvider
     */
    public function getNativeExtraFieldAttributeSetsAutoIncrement($fieldDefinition, $expected)
    {
        $actual = $this->subject->_call('getNativeExtraFieldAttributes', $fieldDefinition);
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @param array $fieldDefinition
     * @param string $expected
     * @dataProvider getNativeKeyForFieldProvider
     */
    public function getNativeKeyForFieldProviderIdentifiesIndexes($fieldDefinition, $expected)
    {
        $actual = $this->subject->_call('getNativeKeyForField', $fieldDefinition);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function determineMetaTypeProvider()
    {
        return [
            ['INT', 'I4'],
            ['INTEGER', 'I4'],
            ['TINYINT', 'I2'],
            ['SMALLINT', 'I2'],
            ['MEDIUMINT', 'I4'],
            ['BIGINT', 'I8'],
            ['DOUBLE', 'F'],
            ['FLOAT', 'F'],
            ['TIME', 'T'],
            ['TIMESTAMP', 'T'],
            ['DATETIME', 'T'],
            ['DATE', 'D'],
            ['YEAR', 'D'],
            ['IMAGE', 'B'],
            ['BLOB', 'B'],
            ['MEDIUMBLOB', 'B'],
            ['LONGBLOB', 'B'],
            ['IMAGE', 'B'],
            ['TEXT', 'XL'],
            ['MEDIUMTEXT', 'XL'],
            ['LONGTEXT', 'XL'],
            ['STRING', 'C'],
            ['CHAR', 'C'],
            ['VARCHAR', 'C'],
            ['TINYBLOB', 'B'],
            ['TINYTEXT', 'C'],
            ['ENUM', 'C'],
            ['SET', 'C']
        ];
    }

    /**
     * @return array
     */
    public function determineNativeTypeProvider()
    {
        return [
            ['C', 'VARCHAR'],
            ['C2', 'VARCHAR'],
            ['X', 'LONGTEXT'],
            ['X2', 'LONGTEXT'],
            ['XL', 'LONGTEXT'],
            ['B', 'LONGBLOB'],
            ['D', 'DATE'],
            ['T', 'DATETIME'],
            ['L', 'TINYINT'],
            ['I', 'INT'],
            ['I1', 'SMALLINT'],
            ['I2', 'SMALLINT'],
            ['I4', 'INT'],
            ['I8', 'BIGINT'],
            ['R', 'INT'],
            ['F', 'DOUBLE'],
            ['N', 'NUMERIC'],
            ['U', 'U']
        ];
    }

    /**
     * @return array
     */
    public function determineNativeFieldLengthProvider()
    {
        return [
            ['SMALLINT', '2', '(6)'],
            ['INT', '4', '(11)'],
            ['BIGINT', '8', '(20)'],
            ['VARCHAR', -1, ''],
            ['VARCHAR', 30, '(30)'],
            ['DOUBLE', 8, '']
        ];
    }

    /**
     * @return array
     */
    public function getNativeDefaultValueProvider()
    {
        return [
            [['type' => 'SERIAL', 'has_default' => 1, 'default_value' => "nextval('tx_extensionmanager_domain_model_repository_uid_seq'::regclass)"], null],
            [['type' => 'int4', 'has_default' => true, 'default_value' => 0], 0],
            [['type' => 'int4', 'has_default' => true, 'default_value' => '(-1)'], -1],
            [['type' => 'text', 'has_default' => false, 'default_value' => null], null],
            [['type' => 'varchar', 'has_default' => true, 'default_value' => "''::character varying"], ''],
            [['type' => 'varchar', 'has_default' => true, 'default_value' => 'NULL::character varying'], null],
            [['type' => 'varchar', 'has_default' => true, 'default_value' => "'something'::character varying"], 'something'],
            [['type' => 'varchar', 'has_default' => true, 'default_value' => "'some''thing'::character varying"], "some''thing"],
        ];
    }

    /**
     * @return array
     */
    public function getNativeExtraFieldAttributeProvider()
    {
        return [
            [['type' => 'SERIAL'], 'auto_increment'],
            [['type' => 'int4', 'default_value' => 'nextval(\'somesequence_seq\''], 'auto_increment'],
            [['type' => 'int4', 'default_value' => 0], '']
        ];
    }

    /**
     * @return array
     */
    public function getNativeKeyForFieldProvider()
    {
        return [
            [['primary_key' => true], 'PRI'],
            [['unique' => true], 'UNI'],
            [[], '']
        ];
    }
}
