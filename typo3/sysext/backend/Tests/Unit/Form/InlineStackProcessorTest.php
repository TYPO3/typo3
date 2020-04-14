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

namespace TYPO3\CMS\Backend\Tests\Unit\Form;

use Prophecy\Argument;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class InlineStackProcessorTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('runtime')->willReturn($cacheProphecy->reveal());
        $cacheProphecy->get(Argument::cetera())->willReturn(false);
        $cacheProphecy->set(Argument::cetera())->willReturn(false);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
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
                [
                    'form' => '',
                    'object' => '',
                ]
            ],
            'simple 1-level table-uid structure' => [
                'data-pageId-childTable-childUid',
                [
                    'unstable' => [
                        'table' => 'childTable',
                        'uid' => 'childUid',
                    ],
                ],
                [
                    'form' => '',
                    'object' => '',
                ]
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
                [
                    'form' => '',
                    'object' => '',
                ],
            ],
            'simple 2-level table structure' => [
                'data-pageId-parentTable-parentUid-parentField-childTable',
                [
                    'stable' => [
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
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
                            'config' => [],
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
                            'config' => [],
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
                            'config' => [],
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
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
                            'config' => [],
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
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
                            'config' => [],
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
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
                            'config' => [],
                        ],
                        [
                            'table' => 'parentTable',
                            'uid' => 'parentUid',
                            'field' => 'parentField',
                            'config' => [],
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
        /** @var InlineStackProcessor|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(InlineStackProcessor::class, ['dummy']);
        $subject->initializeByParsingDomObjectIdString($string);
        $structure = $subject->_get('inlineStructure');
        self::assertEquals($expectedInlineStructure, $structure);
    }

    /**
     * @dataProvider structureStringIsParsedDataProvider
     * @test
     */
    public function getCurrentStructureFormPrefixReturnsExpectedStringAfterInitializationByStructureString($string, array $_, array $expectedFormName)
    {
        /** @var InlineStackProcessor|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = new InlineStackProcessor();
        $subject->initializeByParsingDomObjectIdString($string);
        self::assertEquals($expectedFormName['form'], $subject->getCurrentStructureFormPrefix());
    }

    /**
     * @dataProvider structureStringIsParsedDataProvider
     * @test
     */
    public function getCurrentStructureDomObjectIdPrefixReturnsExpectedStringAfterInitializationByStructureString($string, array $_, array $expectedFormName)
    {
        /** @var InlineStackProcessor|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = new InlineStackProcessor();
        $subject->initializeByParsingDomObjectIdString($string);
        self::assertEquals($expectedFormName['object'], $subject->getCurrentStructureDomObjectIdPrefix('pageId'));
    }
}
