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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\FlexSectionContainer;

use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;

abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    protected const VALUE_PageId = 89;
    protected const VALUE_LanguageId = 1;
    protected const VALUE_ElementIdFirst = 1;

    protected const TABLE_Element = 'tx_testflexsectioncontainer';
    protected const FIELD_Flex = 'flex_1';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_flex_section_container',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(static::SCENARIO_DataSet);
    }

    public function deleteSection(): void
    {
        $GLOBALS['_POST'] = [
            '_ACTION_FLEX_FORMdata' => [
                'tx_testflexsectioncontainer' => [
                    1 => [
                        'flex_1' => [
                            'data' => [
                                'sSection' => [
                                    'lDEF' => [
                                        'section_1' => [
                                            'el' => [
                                                '_ACTION' => [
                                                    '62cd3f446f5bc303148445' => 'DELETE',
                                                    '62cd3f480ef5e265844192' => '',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->actionService->modifyRecord(
            self::TABLE_Element,
            self::VALUE_ElementIdFirst,
            [
                'flex_1' => [
                    'data' => [
                        'sSection' => [
                            'lDEF' => [
                                'section_1' => [
                                    'el' => [
                                        '62cd3f446f5bc303148445' => [
                                            'container_1' => [
                                                'el' => [
                                                    'input_1' => [
                                                        'vDEF' => 'section element 1',
                                                    ],
                                                ],
                                            ],
                                        ],
                                        '62cd3f480ef5e265844192' => [
                                            'container_1' => [
                                                'el' => [
                                                    'input_1' => [
                                                        'vDEF' => 'section element 2',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );
        $GLOBALS['_POST'] = [];
    }

    public function changeSorting(): void
    {
        $GLOBALS['_POST'] = [
            '_ACTION_FLEX_FORMdata' => [
                'tx_testflexsectioncontainer' => [
                    1 => [
                        'flex_1' => [
                            'data' => [
                                'sSection' => [
                                    'lDEF' => [
                                        'section_1' => [
                                            'el' => [
                                                '_ACTION' => [
                                                    '62cd3f480ef5e265844192' => '0',
                                                    '62cd3f446f5bc303148445' => '1',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->actionService->modifyRecord(
            self::TABLE_Element,
            self::VALUE_ElementIdFirst,
            [
                'flex_1' => [
                    'data' => [
                        'sSection' => [
                            'lDEF' => [
                                'section_1' => [
                                    'el' => [
                                        '62cd3f446f5bc303148445' => [
                                            'container_1' => [
                                                'el' => [
                                                    'input_1' => [
                                                        'vDEF' => 'section element 1',
                                                    ],
                                                ],
                                            ],
                                        ],
                                        '62cd3f480ef5e265844192' => [
                                            'container_1' => [
                                                'el' => [
                                                    'input_1' => [
                                                        'vDEF' => 'section element 2',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );
        $GLOBALS['_POST'] = [];
    }
}
