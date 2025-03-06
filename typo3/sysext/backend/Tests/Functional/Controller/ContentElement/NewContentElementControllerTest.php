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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\ContentElement;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class NewContentElementControllerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function loadAvailableWizardsReturnsExpectedArrayDataProvider(): iterable
    {
        yield 'Content Element in default group' => [
            'item' => [
                'label' => 'Element A',
                'value' => 'element_a',
                'group' => 'default',
                'icon' => 'content-header',
            ],
            'creationOptions' => [],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'title' => 'Element A',
                'description' => '',
                'defaultValues' => [
                    'CType' => 'element_a',
                ],
            ],
        ];

        yield 'Content Element in undefined group' => [
            'item' => [
                'label' => 'Element A',
                'value' => 'element_a',
                'group' => 'undefined',
                'icon' => 'content-header',
            ],
            'creationOptions' => [],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'title' => 'Element A',
                'description' => '',
                'defaultValues' => [
                    'CType' => 'element_a',
                ],
            ],
        ];

        yield 'Content Element without group' => [
            'item' => [
                'label' => 'Element A',
                'value' => 'element_a',
                'group' => null,
                'icon' => 'content-header',
            ],
            'creationOptions' => [],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'title' => 'Element A',
                'description' => '',
                'defaultValues' => [
                    'CType' => 'element_a',
                ],
            ],
        ];

        yield 'Content Element with additional creation options' => [
            'item' => [
                'label' => 'Element A',
                'value' => 'element_a',
                'group' => 'default',
                'icon' => 'content-header',
            ],
            'creationOptions' => [
                'defaultValues' => [
                    'header' => 'Foo',
                ],
                'saveAndClose' => true,
            ],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'title' => 'Element A',
                'description' => '',
                'defaultValues' => [
                    'CType' => 'element_a',
                    'header' => 'Foo',
                ],
                'saveAndClose' => true,
            ],
        ];

        yield 'Dividers are ignored' => [
            'item' => [
                'label' => 'Divider',
                'value' => '--div--',
            ],
            'creationOptions' => [],
            'expected' => [],
        ];
    }

    #[DataProvider('loadAvailableWizardsReturnsExpectedArrayDataProvider')]
    #[Test]
    public function loadAvailableWizardsReturnsExpectedArray(array $item, array $creationOptions, array $expected): void
    {
        $group = $item['group'] ?? '';
        $type = $item['value'];
        $newContentElementWizardController = $this->get(NewContentElementController::class);
        $loadAvailableWizardsFromContentElements = new \ReflectionMethod(
            NewContentElementController::class,
            'loadAvailableWizards'
        );

        ExtensionManagementUtility::addTcaSelectItem(
            'tt_content',
            'CType',
            $item
        );

        $GLOBALS['TCA']['tt_content']['types'][$type]['creationOptions'] = $creationOptions;
        $this->get(TcaSchemaFactory::class)->load($GLOBALS['TCA'], true);

        $result = $loadAvailableWizardsFromContentElements->invoke($newContentElementWizardController);
        $actual = $result[$group . '.']['elements.'][$type . '.'] ?? [];
        self::assertSame($expected, $actual);
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
        $subject = $this->getAccessibleMock(
            originalClassName: NewContentElementController::class,
            methods: ['wizardAction'],
            callOriginalConstructor: false
        );
        $subject->_set('dependencyOrderingService', new DependencyOrderingService());
        $result = $subject->_call('orderWizards', $wizards);
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
        $subject = $this->getAccessibleMock(
            originalClassName: NewContentElementController::class,
            methods: ['orderWizards'],
            callOriginalConstructor: false
        );
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $tcaSchemaFactory->load($GLOBALS['TCA'], true);
        $subject->_set('tcaSchemaFactory', $tcaSchemaFactory);
        $result = $subject->_call('loadAvailableWizards');
        self::assertSame($expected, $result);
    }
}
