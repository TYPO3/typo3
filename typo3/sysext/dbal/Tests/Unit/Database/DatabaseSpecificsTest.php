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
use TYPO3\CMS\Dbal\Database\Specifics\AbstractSpecifics;

/**
 * Test case
 */
class DatabaseSpecificsTest extends AbstractTestCase
{
    /**
     * @var AbstractSpecifics|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * Set up
     */
    protected function setUp()
    {
        $GLOBALS['TYPO3_LOADED_EXT'] = [];

        /** @var AbstractSpecifics|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $this->subject = GeneralUtility::makeInstance(\TYPO3\CMS\Dbal\Database\Specifics\NullSpecifics::class);
    }

    /**
     * @test
     * @param string $nativeType
     * @param string $expected
     * @dataProvider determineMetaTypeProvider
     */
    public function determineMetaTypeFromNativeType($nativeType, $expected)
    {
        $result = $this->subject->getMetaFieldType($nativeType);
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     * @param string $metaType
     * @param string $expected
     * @dataProvider determineNativeTypeProvider
     */
    public function determineNativeTypeFromMetaType($metaType, $expected)
    {
        $result = $this->subject->getNativeFieldType($metaType);
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     * @param string $fieldType
     * @param int $maxLength
     * @param string $expected
     * @dataProvider determineNativeFieldLengthProvider
     */
    public function determineNativeFieldLength($fieldType, $maxLength, $expected)
    {
        $result = $this->subject->getNativeFieldLength($fieldType, $maxLength);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function determineMetaTypeProvider()
    {
        return [
            ['INT', 'I8'],
            ['INTEGER', 'I8'],
            ['TINYINT', 'I8'],
            ['SMALLINT', 'I8'],
            ['MEDIUMINT', 'I8'],
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
            ['TINYBLOB', 'C'],
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
            ['I', 'BIGINT'],
            ['I1', 'BIGINT'],
            ['I2', 'BIGINT'],
            ['I4', 'BIGINT'],
            ['I8', 'BIGINT'],
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
            ['INT', '4', '(11)'],
            ['VARCHAR', -1, ''],
            ['VARCHAR', 30, '(30)']
        ];
    }
}
