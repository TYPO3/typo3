<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Tests\UnitDeprecated\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for TcaFlexPrepare to render the functionality when a TCA migration happened
 */
class DatabaseRowInitializeNewTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addDataSetsDefaultDataFromGetIfColumnIsDefinedInTca()
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [],
                ],
            ],
        ];
        $GLOBALS['_GET'] = [
            'defVals' => [
                'aTable' => [
                    'aField' => 'getValue',
                ],
            ],
        ];
        $expected = [
            'aField' => 'getValue',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        $this->assertSame($expected, $result['databaseRow']);
    }

    /**
     * @test
     */
    public function addDataSetsDefaultDataFromPostIfColumnIsDefinedInTca()
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [],
                ],
            ],
        ];
        $GLOBALS['_POST'] = [
            'defVals' => [
                'aTable' => [
                    'aField' => 'postValue',
                ],
            ],
        ];
        $expected = [
            'aField' => 'postValue',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        $this->assertSame($expected, $result['databaseRow']);
    }

    /**
     * @test
     */
    public function addDataSetsPrioritizesDefaultPostOverDefaultGet()
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [],
                ],
            ],
        ];
        $GLOBALS['_GET'] = [
            'defVals' => [
                'aTable' => [
                    'aField' => 'getValue',
                ],
            ],
        ];
        $GLOBALS['_POST'] = [
            'defVals' => [
                'aTable' => [
                    'aField' => 'postValue',
                ],
            ],
        ];
        $expected = [
            'aField' => 'postValue',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        $this->assertSame($expected, $result['databaseRow']);
    }

    /**
     * @test
     */
    public function addDataDoesNotSetDefaultDataFromGetPostIfColumnIsMissingInTca()
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'neighborRow' => null,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'pageTsValue',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [],
            ],
        ];
        $GLOBALS['_GET'] = [
            'defVals' => [
                'aTable' => [
                    'aField' => 'getValue',
                ],
            ],
        ];
        $GLOBALS['_POST'] = [
            'defVals' => [
                'aTable' => [
                    'aField' => 'postValue',
                ],
            ],
        ];
        $expected = [
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        $this->assertSame($expected, $result['databaseRow']);
    }

    /**
     * @test
     */
    public function addDataSetsDefaultDataOverrulingGetPost()
    {
        $input = [
            'command' => 'new',
            'tableName' => 'aTable',
            'vanillaUid' => 23,
            'inlineChildChildUid' => null,
            'isInlineChild' => false,
            'databaseRow' => [],
            'neighborRow' => [
                'aField' => 'valueFromNeighbor',
            ],
            'pageTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'pageTsValue',
                    ],
                ],
            ],
            'userTsConfig' => [
                'TCAdefaults.' => [
                    'aTable.' => [
                        'aField' => 'userTsValue',
                    ],
                ],
            ],
            'processedTca' => [
                'ctrl' => [
                    'useColumnsForDefaultValues' => 'aField',
                ],
                'columns' => [
                    'aField' => [],
                ],
            ],
        ];
        $GLOBALS['_POST'] = [
            'defVals' => [
                'aTable' => [
                    'aField' => 'postValue',
                ],
            ],
        ];
        $expected = [
            'aField' => 'postValue',
            'pid' => 23,
        ];
        $result = (new DatabaseRowInitializeNew())->addData($input);
        $this->assertSame($expected, $result['databaseRow']);
    }
}
