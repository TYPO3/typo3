<?php
namespace TYPO3\CMS\Recordlist\Tests\Unit\RecordList;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList;

/**
 * Test case
 */
class AbstractDatabaseRecordListTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider setTableDisplayOrderConvertsStringsDataProvider
     * @param array $input
     * @param array $expected
     */
    public function setTableDisplayOrderConvertsStringInput(array $input, array $expected)
    {
        /** @var AbstractDatabaseRecordList|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(AbstractDatabaseRecordList::class, ['dummy']);
        $subject->setTableDisplayOrder($input);
        $this->assertSame($expected, $subject->_get('tableDisplayOrder'));
    }

    /**
     * @return array
     */
    public function setTableDisplayOrderConvertsStringsDataProvider()
    {
        return [
            'no information at all' => [
                [],
                []
            ],
            'string in before' => [
                [
                    'tableA' => [
                        'before' => 'tableB, tableC'
                    ]
                ],
                [
                    'tableA' => [
                        'before' => ['tableB', 'tableC']
                    ]
                ]
            ],
            'array is preserved in before' => [
                [
                    'tableA' => [
                        'before' => ['tableB', 'tableC']
                    ]
                ],
                [
                    'tableA' => [
                        'before' => ['tableB', 'tableC']
                    ]
                ]
            ],
            'array is preserved in before, after is modified' => [
                [
                    'tableA' => [
                        'before' => ['tableB', 'tableC'],
                        'after' => 'tableD'
                    ]
                ],
                [
                    'tableA' => [
                        'before' => ['tableB', 'tableC'],
                        'after' => ['tableD']
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     * @expectedExceptionCode 1436195934
     */
    public function setTableDisplayOrderThrowsExceptionOnInvalidAfter()
    {
        $test = [
            'table' => [ 'after' => new \stdClass ]
        ];
        $subject = new AbstractDatabaseRecordList();
        $subject->setTableDisplayOrder($test);
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     * @expectedExceptionCode 1436195933
     */
    public function setTableDisplayOrderThrowsExceptionOnInvalidBefore()
    {
        $test = [
            'table' => [ 'before' => new \stdClass ]
        ];
        $subject = new AbstractDatabaseRecordList();
        $subject->setTableDisplayOrder($test);
    }
}
