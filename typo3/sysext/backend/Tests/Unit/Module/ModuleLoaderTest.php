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

namespace TYPO3\CMS\Backend\Tests\Unit\Module;

use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ModuleLoaderTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = new \stdClass();
        $GLOBALS['LANG']->lang = 'it';
    }

    /**
     * @return array
     */
    public function addModuleLabelsDataProvider()
    {
        return [
            'extbase only with string' => [
                'extbasemodule',
                'EXT:myext/Resources/Private/Language/modules.xlf',
                [
                    'shortdescription' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_labels_tablabel',
                    'description' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_labels_tabdescr',
                    'title' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_tabs_tab',
                ],
            ],
            'array with LLLs and proper names' => [
                'singlereferences',
                [
                    'shortdescription' => 'EXT:myext/Resources/Private/Language/modules.xlf:myshortdescription',
                    'description' => 'EXT:myext/Resources/Private/Language/modules.xlf:mydescription',
                    'title' => 'EXT:myext/Resources/Private/Language/modules.xlf:mytitle',
                ],
                [
                    'shortdescription' => 'EXT:myext/Resources/Private/Language/modules.xlf:myshortdescription',
                    'description' => 'EXT:myext/Resources/Private/Language/modules.xlf:mydescription',
                    'title' => 'EXT:myext/Resources/Private/Language/modules.xlf:mytitle',
                ],
            ],
            'XLF reference inside [ll_ref] - classic' => [
                'classicmodule',
                [
                    'll_ref' => 'EXT:myext/Resources/Private/Language/modules.xlf',
                ],
                [
                    'shortdescription' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_labels_tablabel',
                    'description' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_labels_tabdescr',
                    'title' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_tabs_tab',
                ],
            ],
            'XLF reference inside [default][ll_ref] - classic with default' => [
                'classicmodule',
                [
                    'default' => [
                        'll_ref' => 'EXT:myext/Resources/Private/Language/modules.xlf',
                    ],
                ],
                [
                    'shortdescription' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_labels_tablabel',
                    'description' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_labels_tabdescr',
                    'title' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_tabs_tab',
                ],
            ],
            'XLF reference inside [it][ll_ref] - classic with italian' => [
                'classicmodule',
                [
                    'it' => [
                        'll_ref' => 'EXT:myext/Resources/Private/Language/modules.xlf',
                    ],
                ],
                [
                    'shortdescription' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_labels_tablabel',
                    'description' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_labels_tabdescr',
                    'title' => 'EXT:myext/Resources/Private/Language/modules.xlf:mlang_tabs_tab',
                ],
            ],
            'classic inline labels' => [
                'classic_inline_labels',
                [
                    'default' => [
                        'labels' => [
                            'tablabel' => 'My short description!',
                            'tabdescr' => 'My description!',
                        ],
                        'tabs' => [
                            'tab' => 'My title',
                        ],
                    ],
                ],
                [
                    'shortdescription' => 'My short description!',
                    'description' => 'My description!',
                    'title' => 'My title',
                ],
            ],
            'classic inline labels in italian completely' => [
                'classic_italian_labels',
                [
                    'default' => [
                        'labels' => [
                            'tablabel' => 'My short description!',
                            'tabdescr' => 'My description!',
                        ],
                        'tabs' => [
                            'tab' => 'My title',
                        ],
                    ],
                    'it' => [
                        'labels' => [
                            'tablabel' => 'Mama Mia short description!',
                            'tabdescr' => 'Mama Mia description!',
                        ],
                        'tabs' => [
                            'tab' => 'Mama Mia',
                        ],
                    ],
                ],
                [
                    'shortdescription' => 'Mama Mia short description!',
                    'description' => 'Mama Mia description!',
                    'title' => 'Mama Mia',
                ],
            ],
            'classic inline labels in italian partially' => [
                'classic_italian_labels',
                [
                    'default' => [
                        'labels' => [
                            'tablabel' => 'My short description!',
                            'tabdescr' => 'My original description!',
                        ],
                        'tabs' => [
                            'tab' => 'My title',
                        ],
                    ],
                    'it' => [
                        'labels' => [
                            'tablabel' => 'Mama Mia short description!',
                        ],
                        'tabs' => [
                            'tab' => 'Mama Mia',
                        ],
                    ],
                ],
                [
                    'shortdescription' => 'Mama Mia short description!',
                    'description' => 'My original description!',
                    'title' => 'Mama Mia',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addModuleLabelsDataProvider
     *
     * @param string $moduleName
     * @param string|array $labels
     * @param array $expectedResult
     */
    public function validateLabelsString($moduleName, $labels, array $expectedResult)
    {
        $moduleLoader = new ModuleLoader();
        $moduleLoader->addLabelsForModule($moduleName, $labels);
        self::assertEquals($expectedResult, $moduleLoader->getLabelsForModule($moduleName));
    }
}
