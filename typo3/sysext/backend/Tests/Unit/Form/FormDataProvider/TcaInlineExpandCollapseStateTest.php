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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaInlineExpandCollapseStateTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function addDataAddsInlineStatusForTableUid(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'aParentTable',
            'databaseRow' => [
                'uid' => 5,
            ],
            'isInlineChildExpanded' => false,
            'inlineParentConfig' => [
                'foreign_table' => 'aTable',
            ],
            'isInlineAjaxOpeningContext' => false,
        ];
        $inlineState = [
            'aParentTable' => [
                5 => [
                    'aChildTable' => [
                        // Records 23 and 42 are expanded
                        23,
                        42,
                    ],
                ],
            ],
        ];
        $GLOBALS['BE_USER'] = new \stdClass();
        $GLOBALS['BE_USER']->uc = [
            'inlineView' => json_encode($inlineState),
        ];
        $expected = $input;
        $expected['inlineExpandCollapseStateArray'] = $inlineState['aParentTable'][5];
        self::assertSame($expected, (new TcaInlineExpandCollapseState())->addData($input));
    }

    /**
     * @test
     */
    public function addDataAddsInlineStatusForSecondLevelChild(): void
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'bChildTable',
            'databaseRow' => [
                'uid' => 13,
            ],
            'inlineTopMostParentTableName' => 'aParentTable',
            'inlineTopMostParentUid' => 5,
            'isInlineChildExpanded' => false,
            'inlineParentConfig' => [
                'foreign_table' => 'aTable',
            ],
            'isInlineAjaxOpeningContext' => false,
        ];
        $inlineState = [
            'aParentTable' => [
                5 => [
                    'aChildTable' => [
                        // Records 23 and 42 are expanded
                        23,
                        42,
                    ],
                    'bChildTable' => [
                        // Records 13 and 66 are expanded
                        13,
                        66,
                    ],
                ],
            ],
        ];
        $GLOBALS['BE_USER'] = new \stdClass();
        $GLOBALS['BE_USER']->uc = [
            'inlineView' => json_encode($inlineState),
        ];
        $expected = $input;
        $expected['inlineExpandCollapseStateArray'] = $inlineState['aParentTable'][5];
        self::assertSame($expected, (new TcaInlineExpandCollapseState())->addData($input));
    }

    /**
     * @return array
     */
    public function addDataAddsCorrectIsInlineChildExpandedDataProvider(): array
    {
        return [
            'Inline child is expanded because of state in expandCollapseStateArray' => [
                [
                    'command' => 'edit',
                    'databaseRow' => [
                        'uid' => 42,
                    ],
                    'recordTypeValue' => 'aType',
                    'processedTca' => [
                        'types' => [
                            'aType' => [
                                'showitem' => 'aField',
                            ],
                        ],
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                    'inlineParentConfig' => [
                        'foreign_table' => 'aTable',
                    ],
                    'isInlineChild' => true,
                    'isInlineChildExpanded' => false,
                    'isInlineAjaxOpeningContext' => false,
                    'inlineExpandCollapseStateArray' => [
                        'aTable' => [
                            42,
                        ],
                    ],
                ],
                true,
            ],
            'Inline child is expanded because of ajax opening context' => [
                [
                    'command' => 'edit',
                    'tableName' => 'aParentTable',
                    'databaseRow' => [
                        'uid' => 42,
                    ],
                    'recordTypeValue' => 'aType',
                    'processedTca' => [
                        'types' => [
                            'aType' => [
                                'showitem' => 'aField',
                            ],
                        ],
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                    'inlineParentConfig' => [
                        'foreign_table' => 'aTable',
                        'appearance' => [
                            'collapseAll' => true,
                        ],
                    ],
                    'isInlineChild' => true,
                    'isInlineChildExpanded' => false,
                    'isInlineAjaxOpeningContext' => true,
                    'inlineExpandCollapseStateArray' => [],
                ],
                true,
            ],
            'Inline child is collapsed because of collapseAll' => [
                [
                    'command' => 'edit',
                    'tableName' => 'aParentTable',
                    'databaseRow' => [
                        'uid' => 42,
                    ],
                    'recordTypeValue' => 'aType',
                    'processedTca' => [
                        'types' => [
                            'aType' => [
                                'showitem' => 'aField',
                            ],
                        ],
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                    'inlineParentConfig' => [
                        'foreign_table' => 'aTable',
                        'appearance' => [
                            'collapseAll' => true,
                        ],
                    ],
                    'isInlineChild' => true,
                    'isInlineChildExpanded' => false,
                    'isInlineAjaxOpeningContext' => false,
                    'inlineExpandCollapseStateArray' => [],
                ],
                false,
            ],
            'Inline child is expanded because of expandAll (inverse collapseAll setting)' => [
                [
                    'command' => 'edit',
                    'tableName' => 'aParentTable',
                    'databaseRow' => [
                        'uid' => 42,
                    ],
                    'recordTypeValue' => 'aType',
                    'processedTca' => [
                        'types' => [
                            'aType' => [
                                'showitem' => 'aField',
                            ],
                        ],
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                    'inlineParentConfig' => [
                        'foreign_table' => 'aTable',
                        'appearance' => [
                            'collapseAll' => false,
                        ],
                    ],
                    'isInlineChild' => true,
                    'isInlineChildExpanded' => false,
                    'inlineExpandCollapseStateArray' => [],
                ],
                true,
            ],
            'New inline child is expanded' => [
                [
                    'command' => 'new',
                    'databaseRow' => [
                        'uid' => 'NEW1234',
                    ],
                    'recordTypeValue' => 'aType',
                    'processedTca' => [
                        'types' => [
                            'aType' => [
                                'showitem' => 'aField',
                            ],
                        ],
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                    'inlineParentConfig' => [
                        'foreign_table' => 'aTable',
                        'appearance' => [
                            'collapseAll' => true,
                        ],
                    ],
                    'isInlineChild' => true,
                    'isInlineChildExpanded' => false,
                    'isInlineAjaxOpeningContext' => false,
                    'inlineExpandCollapseStateArray' => [],
                ],
                true,
            ],
            'Inline child marked as expanded stays expanded (e.g. combination child)' => [
                [
                    'command' => 'edit',
                    'tableName' => 'aParentTable',
                    'databaseRow' => [
                        'uid' => 42,
                    ],
                    'recordTypeValue' => 'aType',
                    'processedTca' => [
                        'types' => [
                            'aType' => [
                                'showitem' => 'aField',
                            ],
                        ],
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                    'inlineParentConfig' => [
                        'foreign_table' => 'aTable',
                        'appearance' => [
                            'collapseAll' => true,
                        ],
                    ],
                    'isInlineChild' => true,
                    'isInlineChildExpanded' => true,
                    'inlineExpandCollapseStateArray' => [],
                ],
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addDataAddsCorrectIsInlineChildExpandedDataProvider
     *
     * @param array $input
     * @param bool $expectedIsInlineChildExpanded
     */
    public function addDataAddsCorrectIsInlineChildExpanded(array $input, bool $expectedIsInlineChildExpanded): void
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

        $expected = $input;
        $expected['isInlineChildExpanded'] = $expectedIsInlineChildExpanded;
        self::assertSame($expected, (new TcaInlineExpandCollapseState())->addData($input));
    }
}
