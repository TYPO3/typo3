<?php

namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

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

use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for EditDocumentController
 */
class EditDocumentControllerTest extends UnitTestCase
{
    /**
     * @var bool
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function parseAdditionalGetParametersCreatesCorrectParameterArray()
    {
        $typoScript = [
            'tx_myext.' => [
                'controller' => 'test',
                'action' => 'run'
            ],
            'magic' => 'yes'
        ];
        $expectedParameters = [
            'tx_myext' => [
                'controller' => 'test',
                'action' => 'run'
            ],
            'magic' => 'yes'
        ];
        $result = [];
        $uriBuilder = $this->prophesize(UriBuilder::class);
        GeneralUtility::setSingletonInstance(UriBuilder::class, $uriBuilder->reveal());
        $mock = $this->getAccessibleMock(EditDocumentController::class, ['dummy'], [], '', false);
        $mock->_callRef('parseAdditionalGetParameters', $result, $typoScript);
        $this->assertSame($expectedParameters, $result);
    }

    /**
     * @test
     * @dataProvider slugDependendFieldsAreAddedToColumnsOnlyDataProvider
     */
    public function slugDependendFieldsAreAddedToColumnsOnly(string $result, string $selectedFields, string $tableName, array $configuration): void
    {
        $GLOBALS['TCA'][$tableName]['columns'] = $configuration;

        $editDocumentControllerMock = $this->getAccessibleMock(EditDocumentController::class, ['dummy'], [], '', false);
        $editDocumentControllerMock->_set('columnsOnly', $selectedFields);
        $queryParams = [
            'edit' => [
                $tableName => [
                    '123,456' => 'edit'
                ]
            ],
        ];
        $editDocumentControllerMock->_call('addSlugFieldsToColumnsOnly', $queryParams);

        $this->assertEquals($result, $editDocumentControllerMock->_get('columnsOnly'));
    }

    public function slugDependendFieldsAreAddedToColumnsOnlyDataProvider(): array
    {
        return [
            'fields in string' => [
                'fo,bar,slug,title',
                'fo,bar,slug',
                'fake',
                [
                    'slug' => [
                        'config' => [
                            'type' => 'slug',
                            'generatorOptions' => [
                                'fields' => ['title'],
                            ],
                        ]
                    ],
                ]
            ],
            'fields in string and array' => [
                'slug,fo,title,nav_title,title,other_field',
                'slug,fo,title',
                'fake',
                [
                    'slug' => [
                        'config' => [
                            'type' => 'slug',
                            'generatorOptions' => [
                                'fields' => [['nav_title', 'title'], 'other_field']
                            ],
                        ]
                    ],
                ]
            ],
            'no slug field given' => [
                'slug,fo',
                'slug,fo',
                'fake',
                [
                    'slug' => [
                        'config' => [
                            'type' => 'input',
                            'generatorOptions' => [
                                'fields' => [['nav_title', 'title'], 'other_field']
                            ],
                        ]
                    ],
                ]
            ],
        ];
    }
}
