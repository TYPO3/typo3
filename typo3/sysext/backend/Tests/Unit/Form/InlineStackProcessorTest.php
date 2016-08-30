<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form;

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

use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class InlineStackProcessorTest extends UnitTestCase
{
    protected function setUp()
    {
        // @todo: Remove if stack processor does not fiddle with tsConfig anymore and no longer sets 'config'
        $dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $dbProphecy->reveal();
    }

    /**
     * @return array
     */
    public function structureStringIsParsedDataProvider()
    {
        return [
            'simple 1-level table structure' => [
                'data-pageId-childTable',
                [
                    'unstable' => [
                        'table' => 'childTable',
                    ],
                ],
                []
            ],
            'simple 1-level table-uid structure' => [
                'data-pageId-childTable-childUid',
                [
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                    ],
                ],
                []
            ],
            'simple 1-level table-uid-field structure' => [
                'data-pageId-childTable-childUid-childField',
                [
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                        'field' => 'childField',
                    ],
                ],
                [],
            ],
            'simple 2-level table structure' => [
                'data-pageId-parentTable-parentUid-parentField-childTable',
                [
                    'stable' => [
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => null,
                            'localizationMode' => false,
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-parentTable-parentUid-parentField',
                ],
            ],
            'simple 2-level table-uid structure' => [
                'data-pageId-parentTable-parentUid-parentField-childTable-childUid',
                [
                    'stable' => [
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => null,
                            'localizationMode' => false,
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-parentTable-parentUid-parentField',
                ],
            ],
            'simple 2-level table-uid-field structure' => [
                'data-pageId-parentTable-parentUid-parentField-childTable-childUid-childField',
                [
                    'stable' => [
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => null,
                            'localizationMode' => false,
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                        'field' => 'childField',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-parentTable-parentUid-parentField',
                ],
            ],
            'simple 3-level table structure' => [
                'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField-childTable',
                [
                    'stable' => [
                        [
                            'table' => 'grandParentTable',
                            'uid' => 'grandParentUid',
                            'field' => 'grandParentField',
                            'config' => null,
                            'localizationMode' => false,
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => null,
                            'localizationMode' => false,
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField',
                ],
            ],
            'simple 3-level table-uid structure' => [
                'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField-childTable-childUid',
                [
                    'stable' => [
                        [
                            'table' => 'grandParentTable',
                            'uid' => 'grandParentUid',
                            'field' => 'grandParentField',
                            'config' => null,
                            'localizationMode' => false,
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => null,
                            'localizationMode' => false,
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField',
                ],
            ],
            'simple 3-level table-uid-field structure' => [
                'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField-childTable-childUid-childField',
                [
                    'stable' => [
                        [
                            'table' => 'grandParentTable',
                            'uid' => 'grandParentUid',
                            'field' => 'grandParentField',
                            'config' => null,
                            'localizationMode' => false,
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => null,
                            'localizationMode' => false,
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                        'field' => 'childField',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField-parentTable-parentUid-parentField',
                ],
            ],
            'flexform 3-level table-uid structure' => [
                'data-pageId-grandParentTable-grandParentUid-grandParentField---data---sDEF---lDEF---grandParentFlexForm---vDEF-parentTable-parentUid-parentField-childTable-childUid',
                [
                    'stable' => [
                        [
                            'table' => 'grandParentTable',
                            'uid' => 'grandParentUid',
                            'field' => 'grandParentField',
                            'flexform' => [
                                'data', 'sDEF', 'lDEF', 'grandParentFlexForm', 'vDEF',
                            ],
                            'config' => null,
                            'localizationMode' => false,
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => null,
                            'localizationMode' => false,
                        ],
                    ],
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                    ],
                ],
                [
                    'form' => 'data[parentTable][parentUid][parentField]',
                    'object' => 'data-pageId-grandParentTable-grandParentUid-grandParentField---data---sDEF---lDEF---grandParentFlexForm---vDEF-parentTable-parentUid-parentField',
                ],
            ],
        ];
    }

    /**
     * @dataProvider structureStringIsParsedDataProvider
     * @test
     */
    public function initializeByParsingDomObjectIdStringParsesStructureString($string, array $expectedInlineStructure, array $_)
    {
        /** @var InlineStackProcessor|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(InlineStackProcessor::class, ['dummy']);
        $subject->initializeByParsingDomObjectIdString($string);
        $structure = $subject->_get('inlineStructure');
        $this->assertEquals($expectedInlineStructure, $structure);
    }

    /**
     * @dataProvider structureStringIsParsedDataProvider
     * @test
     */
    public function getCurrentStructureFormPrefixReturnsExceptedStringAfterInitializationByStructureString($string, array $_, array $expectedFormName)
    {
        /** @var InlineStackProcessor|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = new InlineStackProcessor;
        $subject->initializeByParsingDomObjectIdString($string);
        $this->assertEquals($expectedFormName['form'], $subject->getCurrentStructureFormPrefix());
    }

    /**
     * @dataProvider structureStringIsParsedDataProvider
     * @test
     */
    public function getCurrentStructureDomObjectIdPrefixReturnsExceptedStringAfterInitializationByStructureString($string, array $_, array $expectedFormName)
    {
        /** @var InlineStackProcessor|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = new InlineStackProcessor;
        $subject->initializeByParsingDomObjectIdString($string);
        $this->assertEquals($expectedFormName['object'], $subject->getCurrentStructureDomObjectIdPrefix('pageId'));
    }
}
