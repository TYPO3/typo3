<?php

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

namespace TYPO3\CMS\Backend\Tests\Unit\View;

use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testing behaviour of \TYPO3\CMS\Backend\View\BackendLayoutView
 */
class BackendLayoutViewTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Backend\View\BackendLayoutView|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $backendLayoutView;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->backendLayoutView = $this->getAccessibleMock(
            BackendLayoutView::class,
            ['getPage', 'getRootLine'],
            [],
            '',
            false
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

        $this->backendLayoutView->expects(self::once())
            ->method('getPage')->with(self::equalTo($pageId))
            ->willReturn($page);
        $this->backendLayoutView->expects(self::any())
            ->method('getRootLine')->with(self::equalTo($pageId))
            ->willReturn($rootLine);

        $selectedCombinedIdentifier = $this->backendLayoutView->_call('getSelectedCombinedIdentifier', $pageId);
        self::assertEquals($expected, $selectedCombinedIdentifier);
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
