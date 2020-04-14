<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Recordlist\Tests\Unit\RecordList;

use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DatabaseRecordListTest extends UnitTestCase
{
    /**
     * @var DatabaseRecordList|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getAccessibleMock(DatabaseRecordList::class, ['dummy'], [], '', false);
    }

    public function visibleColumnsDataProvider(): array
    {
        return [
            'basicTest' => [
                [
                    'ctrl' => [
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
                    ],
                    'types' => [
                        'bullets' => [
                            'showitem' => '--div--;LABEL,--palette--;;general,bodytext;LABEL',
                        ],
                    ],
                    'palettes' => [
                        'general' => [
                            'label' => 'Baz',
                            'showitem' => 'CType;LABEL,colPos;LABEL',
                        ],
                    ],
                ],
                'bullets',
                '--div--;LABEL,CType;LABEL,colPos;LABEL,bodytext;LABEL',
            ],
            'linebreaks' => [
                [
                    'ctrl' => [
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
                    ],
                    'types' => [
                        'bullets' => [
                            'showitem' => '--div--;LABEL,
                            --palette--;;general,bodytext;LABEL',
                        ],
                    ],
                    'palettes' => [
                        'general' => [
                            'label' => 'Baz',
                            'showitem' => 'CType;LABEL,colPos;LABEL',
                        ],
                    ],
                ],
                'bullets',
                '--div--;LABEL,
                            CType;LABEL,colPos;LABEL,bodytext;LABEL',
            ],
            'spacesInShowItems' => [
                [
                    'ctrl' => [
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
                    ],
                    'types' => [
                        'bullets' => [
                            'showitem' => '--div-- ; LABEL , --palette-- ; ; general , bodytext ; LABEL',
                        ],
                    ],
                    'palettes' => [
                        'general' => [
                            'label' => 'Baz',
                            'showitem' => 'CType ; LABEL , colPos ; LABEL',
                        ],
                    ],
                ],
                'bullets',
                '--div-- ; LABEL , CType ; LABEL , colPos ; LABEL , bodytext ; LABEL',
            ],
            'spacesInShowItemsAndLinebreaks' => [
                [
                    'ctrl' => [
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
                    ],
                    'types' => [
                        'bullets' => [
                            'showitem' => '--div-- ; LABEL ,
                            --palette-- ; ; general , bodytext ; LABEL',
                        ],
                    ],
                    'palettes' => [
                        'general' => [
                            'label' => 'Baz',
                            'showitem' => 'CType ; LABEL , colPos ; LABEL',
                        ],
                    ],
                ],
                'bullets',
                '--div-- ; LABEL ,
                            CType ; LABEL , colPos ; LABEL , bodytext ; LABEL',
            ],
            'nonExistentPalette' => [
                [
                    'ctrl' => [
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
                    ],
                    'types' => [
                        'bullets' => [
                            'showitem' => '--div--;LABEL,--palette--;;iDoNotExist,bodytext;LABEL',
                        ],
                    ],
                ],
                'bullets',
                '--div--;LABEL,,bodytext;LABEL',
            ],
            'trailingCommaInPalette' => [
                [
                    'ctrl' => [
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
                    ],
                    'types' => [
                        'bullets' => [
                            'showitem' => '--div--;LABEL,--palette--;;general,bodytext;LABEL',
                        ],
                    ],
                    'palettes' => [
                        'general' => [
                            'label' => 'Baz',
                            'showitem' => 'CType;LABEL,colPos;LABEL,',
                        ],
                    ],
                ],
                'bullets',
                '--div--;LABEL,CType;LABEL,colPos;LABEL,bodytext;LABEL',
            ],
            'trailingSpaceInPalette' => [
                [
                    'ctrl' => [
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
                    ],
                    'types' => [
                        'bullets' => [
                            'showitem' => '--div--;LABEL,--palette--;;general,bodytext;LABEL',
                        ],
                    ],
                    'palettes' => [
                        'general' => [
                            'label' => 'Baz',
                            'showitem' => 'CType;LABEL,colPos;LABEL ',
                        ],
                    ],
                ],
                'bullets',
                '--div--;LABEL,CType;LABEL,colPos;LABEL,bodytext;LABEL',
            ],
            'trailingTabInPalette' => [
                [
                    'ctrl' => [
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
                    ],
                    'types' => [
                        'bullets' => [
                            'showitem' => '--div--;LABEL,--palette--;;general,bodytext;LABEL',
                        ],
                    ],
                    'palettes' => [
                        'general' => [
                            'label' => 'Baz',
                            'showitem' => 'CType;LABEL,colPos;LABEL ',
                        ],
                    ],
                ],
                'bullets',
                '--div--;LABEL,CType;LABEL,colPos;LABEL,bodytext;LABEL',
            ],
            'trailingLinebreakInPalette' => [
                [
                    'ctrl' => [
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
                    ],
                    'types' => [
                        'bullets' => [
                            'showitem' => '--div--;LABEL,--palette--;;general,bodytext;LABEL',
                        ],
                    ],
                    'palettes' => [
                        'general' => [
                            'label' => 'Baz',
                            'showitem' => 'CType;LABEL,colPos;LABEL
                            ',
                        ],
                    ],
                ],
                'bullets',
                '--div--;LABEL,CType;LABEL,colPos;LABEL,bodytext;LABEL',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider visibleColumnsDataProvider
     * @param array $tableTCA
     * @param string $type
     * @param string $expected
     */
    public function getVisibleColumns(array $tableTCA, string $type, string $expected)
    {
        self::assertSame($expected, $this->subject->_call('getVisibleColumns', $tableTCA, $type));
    }
}
