<?php
namespace TYPO3\CMS\Install\Tests\Unit\Service;

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
use TYPO3\CMS\Install\Service\SqlSchemaMigrationService;

/**
 * Test case
 */
class SqlSchemaMigrationServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Get a SchemaService instance with mocked DBAL enable database connection, DBAL not enabled
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected function getSqlSchemaMigrationService()
    {
        /** @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $databaseConnection */
        $subject = $this->getAccessibleMock(SqlSchemaMigrationService::class, array('isDbalEnabled'), array(), '', false);
        $subject->expects($this->any())->method('isDbalEnabled')->will($this->returnValue(false));

        return $subject;
    }

    /**
     * Get a SchemaService instance with mocked DBAL enable database connection, DBAL enabled
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected function getDbalEnabledSqlSchemaMigrationService()
    {
        /** @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $databaseConnection */
        $databaseConnection = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\DatabaseConnection::class, array('dummy'), array(), '', false);
        $databaseConnection->_set('dbmsSpecifics', GeneralUtility::makeInstance(\TYPO3\CMS\Dbal\Database\Specifics\PostgresSpecifics::class));

        $subject = $this->getAccessibleMock(SqlSchemaMigrationService::class, array('isDbalEnabled', 'getDatabaseConnection'), array(), '', false);
        $subject->expects($this->any())->method('isDbalEnabled')->will($this->returnValue(true));
        $subject->expects($this->any())->method('getDatabaseConnection')->will($this->returnValue($databaseConnection));

        return $subject;
    }

    /**
     * @test
     */
    public function getFieldDefinitionsFileContentHandlesMultipleWhitespacesInFieldDefinitions()
    {
        $subject = $this->getSqlSchemaMigrationService();
        // Multiple whitespaces and tabs in field definition
        $inputString = 'CREATE table atable (' . LF . 'aFieldName   int(11)' . TAB . TAB . TAB . 'unsigned   DEFAULT \'0\'' . LF . ');';
        $result = $subject->getFieldDefinitions_fileContent($inputString);

        $this->assertEquals(
            array(
                'atable' => array(
                    'fields' => array(
                        'aFieldName' => 'int(11) unsigned default \'0\'',
                    ),
                    'extra' => array(
                        'COLLATE' => '',
                    ),
                ),
            ),
            $result
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraFindsChangedFields()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'varchar(999) DEFAULT \'0\' NOT NULL'
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'varchar(255) DEFAULT \'0\' NOT NULL'
                    )
                )
            )
        );

        $this->assertEquals(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(
                    'tx_foo' => array(
                        'fields' => array(
                            'foo' => 'varchar(999) DEFAULT \'0\' NOT NULL'
                        )
                    )
                ),
                'diff_currentValues' => array(
                    'tx_foo' => array(
                        'fields' => array(
                            'foo' => 'varchar(255) DEFAULT \'0\' NOT NULL'
                        )
                    )
                )
            )
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraFindsChangedFieldsIncludingNull()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'varchar(999) NULL'
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'varchar(255) NULL'
                    )
                )
            )
        );

        $this->assertEquals(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(
                    'tx_foo' => array(
                        'fields' => array(
                            'foo' => 'varchar(999) NULL'
                        )
                    )
                ),
                'diff_currentValues' => array(
                    'tx_foo' => array(
                        'fields' => array(
                            'foo' => 'varchar(255) NULL'
                        )
                    )
                )
            )
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraFindsChangedFieldsIgnoreNotNull()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'varchar(999) DEFAULT \'0\' NOT NULL'
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'varchar(255) DEFAULT \'0\' NOT NULL'
                    )
                )
            ),
            '',
            true
        );

        $this->assertEquals(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(
                    'tx_foo' => array(
                        'fields' => array(
                            'foo' => 'varchar(999) DEFAULT \'0\''
                        )
                    )
                ),
                'diff_currentValues' => array(
                    'tx_foo' => array(
                        'fields' => array(
                            'foo' => 'varchar(255) DEFAULT \'0\''
                        )
                    )
                )
            )
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraIgnoresCaseDifference()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'INT(11) DEFAULT \'0\' NOT NULL',
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'int(11) DEFAULT \'0\' NOT NULL',
                    )
                )
            )
        );

        $this->assertEquals(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(),
                'diff_currentValues' => null,
            )
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraIgnoresCaseDifferenceButKeepsCaseInSetIntact()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'subtype' => 'SET(\'Tx_MyExt_Domain_Model_Xyz\',\'Tx_MyExt_Domain_Model_Abc\',\'\') NOT NULL DEFAULT \'\',',
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'subtype' => 'set(\'Tx_MyExt_Domain_Model_Xyz\',\'Tx_MyExt_Domain_Model_Abc\',\'\') NOT NULL DEFAULT \'\',',
                    )
                )
            )
        );

        $this->assertEquals(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(),
                'diff_currentValues' => null,
            )
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraDoesNotLowercaseReservedWordsForTheComparison()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'PRIMARY KEY (md5hash)',
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'PRIMARY KEY (md5hash)'),
                )
            )
        );

        $this->assertEquals(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(),
                'diff_currentValues' => null,
            )
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraFindsNewSpatialKeys()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'keys' => array(
                        'foo' => 'SPATIAL foo (foo)'
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'keys' => array()
                )
            )
        );

        $this->assertEquals(
            $differenceArray,
            array(
                'extra' => array(
                    'tx_foo' => array(
                        'keys' => array(
                            'foo' => 'SPATIAL foo (foo)'
                        )
                    )
                ),
                'diff' => array(),
                'diff_currentValues' => null
            )
        );
    }

    /**
     * @test
     */
    public function checkColumnDefinitionIfCommentIsSupplied()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $fieldDefinition = $subject->assembleFieldDefinition(
            array(
                'Field' => 'uid',
                'Type' => 'int(11)',
                'Null' => 'NO',
                'Key' => 'PRI',
                'Default' => null,
                'Extra' => 'auto_increment',
                'Comment' => 'I am a comment',
            )
        );

        $this->assertSame(
            'int(11) NOT NULL auto_increment COMMENT \'I am a comment\'',
            $fieldDefinition
        );
    }

    /**
     * @test
     */
    public function checkColumnDefinitionIfNoCommentIsSupplied()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $fieldDefinition = $subject->assembleFieldDefinition(
            array(
                'Field' => 'uid',
                'Type' => 'int(11)',
                'Null' => 'NO',
                'Key' => 'PRI',
                'Default' => null,
                'Extra' => 'auto_increment',
            )
        );

        $this->assertSame(
            'int(11) NOT NULL auto_increment',
            $fieldDefinition
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraIncludesEngineIfMySQLIsUsed()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'INT(11) DEFAULT \'0\' NOT NULL',
                    ),
                    'extra' => array(
                        'ENGINE' => 'InnoDB'
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'int(11) DEFAULT \'0\' NOT NULL',
                    ),
                    'extra' => array(
                        'ENGINE' => 'InnoDB'
                    )
                )
            )
        );

        $this->assertSame(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(),
                'diff_currentValues' => null,
            )
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraExcludesEngineIfDbalIsUsed()
    {
        $subject = $this->getDbalEnabledSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'INT(11) DEFAULT \'0\' NOT NULL',
                    ),
                    'extra' => array(
                        'ENGINE' => 'InnoDB'
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'int(11) DEFAULT \'0\' NOT NULL',
                    ),
                    'extra' => array()
                )
            )
        );

        $this->assertSame(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(),
                'diff_currentValues' => null,
            )
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraIncludesUnsignedAttributeIfMySQLIsUsed()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'INT(11) UNSIGNED DEFAULT \'0\' NOT NULL',
                    ),
                    'extra' => array(
                        'ENGINE' => 'InnoDB'
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'int(11) DEFAULT \'0\' NOT NULL',
                    ),
                    'extra' => array(
                        'ENGINE' => 'InnoDB'
                    )
                )
            )
        );

        $this->assertSame(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(
                    'tx_foo' => array(
                        'fields' => array(
                            'foo' => 'int(11) UNSIGNED DEFAULT \'0\' NOT NULL',
                        ),
                    )
                ),
                'diff_currentValues' => array(
                    'tx_foo' => array(
                        'fields' => array(
                            'foo' => 'int(11) DEFAULT \'0\' NOT NULL',
                        ),
                    )
                )
            )
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraExcludesUnsignedAttributeIfDbalIsUsed()
    {
        $subject = $this->getDbalEnabledSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'INT(11) UNSIGNED DEFAULT \'0\' NOT NULL',
                    ),
                    'extra' => array(
                        'ENGINE' => 'InnoDB'
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'fields' => array(
                        'foo' => 'int(11) DEFAULT \'0\' NOT NULL',
                    ),
                    'extra' => array(
                        'ENGINE' => 'InnoDB'
                    )
                )
            )
        );

        $this->assertSame(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(),
                'diff_currentValues' => null
            )
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraIgnoresIndexPrefixLengthIfDbalIsUsed()
    {
        $subject = $this->getDbalEnabledSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'keys' => array(
                        'foo' => 'KEY foo (foo(199))'
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'keys' => array(
                        'foo' => 'KEY foo (foo)'
                    )
                )
            )
        );

        $this->assertSame(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(),
                'diff_currentValues' => null,
            )
        );
    }

    /**
     * @test
     */
    public function getDatabaseExtraComparesIndexPrefixLengthIfMySQLIsUsed()
    {
        $subject = $this->getSqlSchemaMigrationService();
        $differenceArray = $subject->getDatabaseExtra(
            array(
                'tx_foo' => array(
                    'keys' => array(
                        'foo' => 'KEY foo (foo(199))'
                    )
                )
            ),
            array(
                'tx_foo' => array(
                    'keys' => array(
                        'foo' => 'KEY foo (foo)'
                    )
                )
            )
        );

        $this->assertSame(
            $differenceArray,
            array(
                'extra' => array(),
                'diff' => array(
                    'tx_foo' => array(
                        'keys' => array(
                            'foo' => 'KEY foo (foo(199))'
                        )
                    )
                ),
                'diff_currentValues' => array(
                    'tx_foo' => array(
                        'keys' => array(
                            'foo' => 'KEY foo (foo)'
                        )
                    )
                )
            )
        );
    }
}
