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
        $GLOBALS['TYPO3_LOADED_EXT'] = array();

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
        return array(
            array('INT', 'I4'),
            array('INTEGER', 'I4'),
            array('TINYINT', 'I2'),
            array('SMALLINT', 'I2'),
            array('MEDIUMINT', 'I4'),
            array('BIGINT', 'I8'),
            array('DOUBLE', 'F'),
            array('FLOAT', 'F'),
            array('TIME', 'T'),
            array('TIMESTAMP', 'T'),
            array('DATETIME', 'T'),
            array('DATE', 'D'),
            array('YEAR', 'D'),
            array('IMAGE', 'B'),
            array('BLOB', 'B'),
            array('MEDIUMBLOB', 'B'),
            array('LONGBLOB', 'B'),
            array('IMAGE', 'B'),
            array('TEXT', 'XL'),
            array('MEDIUMTEXT', 'XL'),
            array('LONGTEXT', 'XL'),
            array('STRING', 'C'),
            array('CHAR', 'C'),
            array('VARCHAR', 'C'),
            array('TINYBLOB', 'B'),
            array('TINYTEXT', 'C'),
            array('ENUM', 'C'),
            array('SET', 'C')
        );
    }

    /**
     * @return array
     */
    public function determineNativeTypeProvider()
    {
        return array(
            array('C', 'VARCHAR'),
            array('C2', 'VARCHAR'),
            array('X', 'LONGTEXT'),
            array('X2', 'LONGTEXT'),
            array('XL', 'LONGTEXT'),
            array('B', 'LONGBLOB'),
            array('D', 'DATE'),
            array('T', 'DATETIME'),
            array('L', 'TINYINT'),
            array('I', 'INT'),
            array('I1', 'SMALLINT'),
            array('I2', 'SMALLINT'),
            array('I4', 'INT'),
            array('I8', 'BIGINT'),
            array('R', 'INT'),
            array('F', 'DOUBLE'),
            array('N', 'NUMERIC'),
            array('U', 'U')
        );
    }

    /**
     * @return array
     */
    public function determineNativeFieldLengthProvider()
    {
        return array(
            array('SMALLINT', '2', '(6)'),
            array('INT', '4', '(11)'),
            array('BIGINT', '8', '(20)'),
            array('VARCHAR', -1, ''),
            array('VARCHAR', 30, '(30)'),
            array('DOUBLE', 8, '')
        );
    }

    /**
     * @return array
     */
    public function getNativeDefaultValueProvider()
    {
        return array(
            array(array('type' => 'SERIAL', 'has_default' => 1, 'default_value' => "nextval('tx_extensionmanager_domain_model_repository_uid_seq'::regclass)"), null),
            array(array('type' => 'int4', 'has_default' => true, 'default_value' => 0), 0),
            array(array('type' => 'int4', 'has_default' => true, 'default_value' => '(-1)'), -1),
            array(array('type' => 'text', 'has_default' => false, 'default_value' => null), null),
            array(array('type' => 'varchar', 'has_default' => true, 'default_value' => "''::character varying"), ''),
            array(array('type' => 'varchar', 'has_default' => true, 'default_value' => 'NULL::character varying'), null),
            array(array('type' => 'varchar', 'has_default' => true, 'default_value' => "'something'::character varying"), 'something'),
            array(array('type' => 'varchar', 'has_default' => true, 'default_value' => "'some''thing'::character varying"), "some''thing"),
        );
    }

    /**
     * @return array
     */
    public function getNativeExtraFieldAttributeProvider()
    {
        return array(
            array(array('type' => 'SERIAL'), 'auto_increment'),
            array(array('type' => 'int4', 'default_value' => 'nextval(\'somesequence_seq\''), 'auto_increment'),
            array(array('type' => 'int4', 'default_value' => 0), '')
        );
    }

    /**
     * @return array
     */
    public function getNativeKeyForFieldProvider()
    {
        return array(
            array(array('primary_key' => true), 'PRI'),
            array(array('unique' => true), 'UNI'),
            array(array(), '')
        );
    }
}
