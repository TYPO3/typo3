<?php
namespace TYPO3\CMS\Backend\Tests\Unit\View;

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

/**
 * Testing behaviour of \TYPO3\CMS\Backend\View\BackendLayoutView
 */
class BackendLayoutViewTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Backend\View\BackendLayoutView|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendLayoutView;

    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        $this->backendLayoutView = $this->getAccessibleMock(
            \TYPO3\CMS\Backend\View\BackendLayoutView::class,
            ['getPage', 'getRootLine'],
            [], '', false
        );
    }

    /**
     * @param bool|string $expected
     * @param array $page
     * @param array $rootLine
     * @test
     * @dataProvider selectedCombinedIdentifierIsDeterminedDataProvider
     */
    public function selectedCombinedIdentifierIsDetermined($expected, array $page, array $rootLine)
    {
        $pageId = $page['uid'];

        $this->backendLayoutView->expects($this->once())
            ->method('getPage')->with($this->equalTo($pageId))
            ->will($this->returnValue($page));
        $this->backendLayoutView->expects($this->any())
            ->method('getRootLine')->with($this->equalTo($pageId))
            ->will($this->returnValue($rootLine));

        $selectedCombinedIdentifier = $this->backendLayoutView->_call('getSelectedCombinedIdentifier', $pageId);
        $this->assertEquals($expected, $selectedCombinedIdentifier);
    }

    /**
     * @return array
     */
    public function selectedCombinedIdentifierIsDeterminedDataProvider()
    {
        return [
            'first level w/o layout' => [
                '0',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'first level with layout' => [
                '1',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '1', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '1', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'first level with provided layout' => [
                'mine_current',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'first level with next layout' => [
                '0',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'first level with provided next layout' => [
                '0',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => 'mine_next'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => 'mine_next'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'second level w/o layout, first level with layout' => [
                '0',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '1', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'second level w/o layout, first level with next layout' => [
                '1',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'second level with layout, first level with next layout' => [
                '2',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '2', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '2', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'second level with layouts, first level resetting all layouts' => [
                '1',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '1', 'backend_layout_next_level' => '1'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '1', 'backend_layout_next_level' => '1'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '-1', 'backend_layout_next_level' => '-1'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'second level with provided layouts, first level resetting all layouts' => [
                'mine_current',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '-1', 'backend_layout_next_level' => '-1'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'second level resetting layout, first level with next layout' => [
                false,
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '-1', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '-1', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'second level resetting next layout, first level with next layout' => [
                '1',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '-1'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '-1'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'third level w/o layout, second level resetting layout, first level with next layout' => [
                '1',
                ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '-1', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'third level w/o layout, second level resetting next layout, first level with next layout' => [
                false,
                ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '-1'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
            'third level with provided layouts, second level w/o layout, first level resetting layouts' => [
                'mine_current',
                ['uid' => 3, 'pid' => 2, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                [
                    ['uid' => 3, 'pid' => 2, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '-1', 'backend_layout_next_level' => '-1'],
                    ['uid' => 0, 'pid' => null],
                ]
            ],
        ];
    }
}
