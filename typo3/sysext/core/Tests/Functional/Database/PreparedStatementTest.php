<?php
namespace TYPO3\CMS\Core\Tests\Functional\Database;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;

/**
 * Test case for \TYPO3\CMS\Core\Database\PreparedStatement
 */
class PreparedStatementTest extends FunctionalTestCase
{
    /**
     * @var DatabaseConnection
     */
    protected $subject = null;

    /**
     * @var string
     */
    protected $testTable = 'test_database_connection';

    /**
     * @var string
     */
    protected $testField = 'test_field';

    /**
     * @var string
     */
    protected $anotherTestField = 'another_test_field';

    /**
     * Set the test up
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->subject = $GLOBALS['TYPO3_DB'];
        $this->subject->sql_query(
            "CREATE TABLE {$this->testTable} (" .
            '   id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,' .
            "   {$this->testField} MEDIUMBLOB," .
            "   {$this->anotherTestField} MEDIUMBLOB," .
            '   PRIMARY KEY (id)' .
            ') ENGINE=MyISAM DEFAULT CHARSET=utf8;'
        );
    }

    /**
     * Tear the test down
     *
     * @return void
     */
    protected function tearDown()
    {
        $this->subject->sql_query("DROP TABLE {$this->testTable};");
        unset($this->subject);
    }

    /**
     * @test
     *
     * @return void
     */
    public function prepareSelectQueryCreateValidQuery()
    {
        $this->assertTrue(
            $this->subject->admin_query("INSERT INTO {$this->testTable} ({$this->testField}) VALUES ('aTestValue')")
        );
        $preparedQuery = $this->subject->prepare_SELECTquery(
            "{$this->testField},{$this->anotherTestField}",
            $this->testTable,
            'id=:id',
            '',
            '',
            '',
            [':id' => 1]
        );
        $preparedQuery->execute();
        $result = $preparedQuery->fetch();
        $expectedResult = [
            $this->testField => 'aTestValue',
            $this->anotherTestField => null,
        ];
        $this->assertSame($expectedResult, $result);
    }
}
