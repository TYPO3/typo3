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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller\ContentElement;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class NewContentElementControllerTest extends UnitTestCase
{
    #[Test]
    public function migrateCommonGroupToDefaultTest(): void
    {
        $input = [
            'common.' => [
                'elements.' => [
                    'c_element.' => [
                        'title' => 'foo',
                    ],
                ],
                'removeItems' => 'foo,bar',
            ],
            'default.' => [
                'elements.' => [
                    'd_element.' => [
                        'title' => 'bar',
                        'tt_content_defValues' => [
                            'field' => 'value',
                        ],
                    ],
                ],
                'removeItems' => 'baz',
            ],
            'custom_group.' => [
                'elements.' => [
                    'custom_element' => [
                        'title' => 'i will be migrated',
                        'saveAndClose' => true,
                        'tt_content_defValues' => [
                            'field' => 'value',
                        ],
                    ],
                ],
                'removeItems' => 'some_element',
            ],
            'removeItems' => 'forms',
        ];

        $expected = [
            'default.' => [
                'elements.' => [
                    'd_element.' => [
                        'title' => 'bar',
                        'tt_content_defValues' => [
                            'field' => 'value',
                        ],
                    ],
                    'c_element.' => [
                        'title' => 'foo',
                    ],
                ],
                'removeItems' => [
                    'baz',
                    'foo',
                    'bar',
                ],
            ],
            'custom_group.' => [
                'elements.' => [
                    'custom_element' => [
                        'title' => 'i will be migrated',
                        'saveAndClose' => true,
                        'tt_content_defValues' => [
                            'field' => 'value',
                        ],
                    ],
                ],
                'removeItems' => 'some_element',
            ],
            'removeItems' => 'forms',
        ];

        $result = (new \ReflectionClass(NewContentElementController::class))
            ->getMethod('migrateCommonGroupToDefault')
            ->invokeArgs($this->createMock(NewContentElementController::class), [$input]);

        self::assertSame($expected, $result);
    }

    #[Test]
    public function contentElementWizardItemsAreMergedCorrectlyWithPluginSubTypeWizards(): void
    {
        $inputContentElements = [
            'default.' => [
                'elements.' => [
                    'd_element.' => [
                        'title' => 'bar',
                    ],
                ],
                'header' => 'Typical Page Content',
            ],
            'special.' => [
                'elements.' => [
                    'f_element.' => [
                        'title' => 'foo',
                    ],
                ],
                'header' => 'Special',
            ],
        ];

        $inputContentPluginSubtypes = [
            'default.' => [
                'elements.' => [
                    'plugin_subtype.' => [
                        'title' => 'bar',
                    ],
                ],
                'header' => 'Default',
            ],
        ];

        $expected = [
            'default.' => [
                'elements.' => [
                    'd_element.' => [
                        'title' => 'bar',
                    ],
                    'plugin_subtype.' => [
                        'title' => 'bar',
                    ],
                ],
                'header' => 'Typical Page Content',
            ],
            'special.' => [
                'elements.' => [
                    'f_element.' => [
                        'title' => 'foo',
                    ],
                ],
                'header' => 'Special',
            ],
        ];

        $result = (new \ReflectionClass(NewContentElementController::class))
            ->getMethod('mergeContentElementAndPluginSubTypeWizards')
            ->invokeArgs($this->createMock(NewContentElementController::class), [$inputContentElements, $inputContentPluginSubtypes]);

        self::assertSame($expected, $result);
    }

    #[Test]
    public function removeWizardsByPageTsTest(): void
    {
        $wizards = [
            'default.' => [
                'elements.' => [
                    'header.' => [
                        'title' => 'header',
                    ],
                    'text.' => [
                        'title' => 'text',
                    ],
                    'image.' => [
                        'title' => 'image',
                    ],
                    'textmedia.' => [
                        'title' => 'textmedia',
                    ],
                ],
            ],
            'lists.' => [
                'elements.' => [
                    'table.' => [
                        'title' => 'table',
                    ],
                ],
            ],
            'menu.' => [
                'elements.' => [
                    'menu_abstract.' => [
                        'title' => 'menuabstract',
                    ],
                ],
            ],
            'special.' => [
                'elements.' => [
                    'html.' => [
                        'title' => 'html',
                    ],
                ],
            ],
        ];

        $wizardsTsConfig = [
            'wizardItems.' => [
                'default.' => [
                    'elements.' => [],
                    'removeItems' => [
                        'text',
                        'image',
                    ],
                ],
                'removeItems' => 'lists,special',
            ],
        ];

        $expected = $wizards;
        unset($expected['default.']['elements.']['text.']);
        unset($expected['default.']['elements.']['image.']);
        unset($expected['lists.']);
        unset($expected['special.']);

        $result = (new \ReflectionClass(NewContentElementController::class))
            ->getMethod('removeWizardsByPageTs')
            ->invokeArgs($this->createMock(NewContentElementController::class), [$wizards, $wizardsTsConfig]);

        self::assertSame($expected, $result);
    }

    #[Test]
    public function mergeContentElementWizardsWithPageTSConfigWizardsRemovesDuplicates(): void
    {
        $contentElementWizardItems = [
            'default.' => [
                'elements.' => [
                    'a_element.' => [
                        'title' => 'a',
                        'defaultValues' => [
                            'field' => 'a',
                        ],
                    ],
                ],
            ],
            'news.' => [
                'elements.' => [
                    'news_list.' => [
                        'title' => 'List',
                        'defaultValues' => [
                            'otherField' => 'foo',
                            'field' => 'list',
                        ],
                    ],
                    'news_show.' => [
                        'title' => 'Show',
                        'defaultValues' => [
                            'field' => 'show',
                        ],
                    ],
                ],
            ],
        ];

        $pageTsConfigWizardItems = [
            'default.' => [
                'elements.' => [
                    'c_element.' => [
                        'title' => 'foo',
                        'tt_content_defValues.' => [
                            'field' => 'foo',
                        ],
                    ],
                ],
            ],
            'news-group.' => [
                'elements.' => [
                    'news_list.' => [
                        'title' => 'List',
                        'tt_content_defValues.' => [
                            'field' => 'list',
                            'otherField' => 'foo',
                        ],
                    ],
                    'news_show.' => [
                        'title' => 'Show',
                        'tt_content_defValues.' => [
                            'field' => 'show',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'default.' => [
                'elements.' => [
                    'a_element.' => [
                        'title' => 'a',
                        'defaultValues' => [
                            'field' => 'a',
                        ],
                    ],
                    'c_element.' => [
                        'title' => 'foo',
                        'tt_content_defValues.' => [
                            'field' => 'foo',
                        ],
                    ],
                ],
            ],
            'news.' => [
                'elements.' => [],
            ],
            'news-group.' => [
                'elements.' => [
                    'news_list.' => [
                        'title' => 'List',
                        'tt_content_defValues.' => [
                            'field' => 'list',
                            'otherField' => 'foo',
                        ],
                    ],
                    'news_show.' => [
                        'title' => 'Show',
                        'tt_content_defValues.' => [
                            'field' => 'show',
                        ],
                    ],
                ],
            ],
        ];

        $result = (new \ReflectionClass(NewContentElementController::class))
            ->getMethod('mergeContentElementWizardsWithPageTSConfigWizards')
            ->invokeArgs(
                $this->createMock(
                    NewContentElementController::class
                ),
                [$contentElementWizardItems, $pageTsConfigWizardItems]
            );

        self::assertSame($expected, $result);
    }

    #[Test]
    public function contentElementWizardsAreLinkedTogetherWithAfterPosition(): void
    {
        $GLOBALS['TCA']['tt_content']['ctrl']['type'] = 'CType';
        $GLOBALS['TCA']['tt_content']['columns']['CType'] = [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header.description',
                        'value' => 'header',
                        'icon' => 'content-header',
                        'group' => 'default',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text.description',
                        'value' => 'text',
                        'icon' => 'content-text',
                        'group' => 'default',
                    ],
                ],
                'itemGroups' => [
                    'default' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.default',
                    'lists' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.lists',
                    'menu' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.menu',
                    'forms' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.forms',
                    'special' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.special',
                    'plugins' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.plugins',
                ],
            ],
        ];
        $expected = [
            'default.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.default',
                'elements.' => [
                    'header.' => [
                        'iconIdentifier' => 'content-header',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header.description',
                        'defaultValues' => [
                            'CType' => 'header',
                        ],
                    ],
                    'text.' => [
                        'iconIdentifier' => 'content-text',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text.description',
                        'defaultValues' => [
                            'CType' => 'text',
                        ],
                    ],
                ],
            ],
            'lists.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.lists',
                'contentElementAfter' => 'default',
            ],
            'menu.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.menu',
                'contentElementAfter' => 'lists',
            ],
            'forms.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.forms',
                'contentElementAfter' => 'menu',
            ],
            'special.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.special',
                'contentElementAfter' => 'forms',
            ],
            'plugins.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.plugins',
                'contentElementAfter' => 'special',
            ],
        ];
        $result = (new \ReflectionClass(NewContentElementController::class))
            ->getMethod('loadAvailableWizards')
            ->invokeArgs(
                $this->createMock(
                    NewContentElementController::class
                ),
                ['CType', false],
            );
        self::assertSame($expected, $result);
    }

    #[Test]
    public function contentElementWizardsAreOrderedByContentElementAfter(): void
    {
        $wizards = [
            'default.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.default',
                'elements.' => [
                    'header.' => [
                        'iconIdentifier' => 'content-header',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header.description',
                        'defaultValues' => [
                            'CType' => 'header',
                        ],
                    ],
                    'text.' => [
                        'iconIdentifier' => 'content-text',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text.description',
                        'defaultValues' => [
                            'CType' => 'text',
                        ],
                    ],
                ],
            ],
            'forms.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.forms',
                'contentElementAfter' => [
                    'menu.',
                ],
            ],
            'menu.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.menu',
                'contentElementAfter' => [
                    'lists.',
                ],
            ],
            'plugins.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.plugins',
                'contentElementAfter' => [
                    'special.',
                ],
            ],
            'lists.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.lists',
                'contentElementAfter' => [
                    'default.',
                ],
            ],
            'special.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.special',
                'contentElementAfter' => [
                    'forms.',
                ],
            ],
        ];
        $expected = [
            'default.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.default',
                'elements.' => [
                    'header.' => [
                        'iconIdentifier' => 'content-header',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header.description',
                        'defaultValues' => [
                            'CType' => 'header',
                        ],
                    ],
                    'text.' => [
                        'iconIdentifier' => 'content-text',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text.description',
                        'defaultValues' => [
                            'CType' => 'text',
                        ],
                    ],
                ],
            ],
            'lists.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.lists',
                'after' => [
                    'default.',
                ],
            ],
            'menu.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.menu',
                'after' => [
                    'lists.',
                ],
            ],
            'forms.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.forms',
                'after' => [
                    'menu.',
                ],
            ],
            'special.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.special',
                'after' => [
                    'forms.',
                ],
            ],
            'plugins.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.plugins',
                'after' => [
                    'special.',
                ],
            ],
        ];
        $result = (new \ReflectionClass(NewContentElementController::class))
            ->getMethod('orderWizards')
            ->invokeArgs(
                $this->createMock(
                    NewContentElementController::class
                ),
                [$wizards, new DependencyOrderingService()]
            );
        self::assertSame($expected, $result);
    }
}
