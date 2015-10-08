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
        $GLOBALS['TYPO3_LOADED_EXT'] = array();

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
        return array(
            array('INT', 'I8'),
            array('INTEGER', 'I8'),
            array('TINYINT', 'I8'),
            array('SMALLINT', 'I8'),
            array('MEDIUMINT', 'I8'),
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
            array('TINYBLOB', 'C'),
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
            array('I', 'BIGINT'),
            array('I1', 'BIGINT'),
            array('I2', 'BIGINT'),
            array('I4', 'BIGINT'),
            array('I8', 'BIGINT'),
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
            array('INT', '4', '(11)'),
            array('VARCHAR', -1, ''),
            array('VARCHAR', 30, '(30)')
        );
    }
}
