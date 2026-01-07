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
                'iconOverlay' => 'actions-approve',
            ],
            'creationOptions' => [],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'iconOverlay' => 'actions-approve',
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
                'iconOverlay' => 'actions-approve',
            ],
            'creationOptions' => [],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'iconOverlay' => 'actions-approve',
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
                'iconOverlay' => 'actions-approve',
            ],
            'creationOptions' => [],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'iconOverlay' => 'actions-approve',
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
                'iconOverlay' => 'actions-approve',
            ],
            'creationOptions' => [
                'defaultValues' => [
                    'header' => 'Foo',
                ],
                'saveAndClose' => true,
            ],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'iconOverlay' => 'actions-approve',
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
    public function contentElementWizardsAreOrderedByTcaItemGroupsOrder(): void
    {
        $this->fillDefaultContentTypeTCA();
        $wizards = [
            'default.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.default',
                'elements.' => [
                    'header.' => [
                        'iconIdentifier' => 'content-header',
                        'iconOverlay' => 'actions-approve',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header.description',
                        'defaultValues' => [
                            'CType' => 'header',
                        ],
                    ],
                    'text.' => [
                        'iconIdentifier' => 'content-text',
                        'iconOverlay' => 'actions-approve',
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
            ],
            'menu.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.menu',
            ],
            'plugins.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.plugins',
            ],
            'lists.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.lists',
            ],
            'special.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.special',
            ],
        ];
        $expected = [
            'default.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.default',
                'elements.' => [
                    'header.' => [
                        'iconIdentifier' => 'content-header',
                        'iconOverlay' => 'actions-approve',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header.description',
                        'defaultValues' => [
                            'CType' => 'header',
                        ],
                    ],
                    'text.' => [
                        'iconIdentifier' => 'content-text',
                        'iconOverlay' => 'actions-approve',
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
        $subject = $this->get(NewContentElementController::class);
        $orderWizardsMethod = (new \ReflectionMethod($subject, 'orderWizards'));
        $result = $orderWizardsMethod->invoke($subject, $wizards);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function contentElementWizardsAreOrderedByTcaItemGroupsOrderWhenGroupsAreRemoved(): void
    {
        $this->fillDefaultContentTypeTCA();
        $wizards = [
            'default.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.default',
                'elements.' => [
                    'header.' => [
                        'iconIdentifier' => 'content-header',
                        'iconOverlay' => 'actions-approve',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header.description',
                        'defaultValues' => [
                            'CType' => 'header',
                        ],
                    ],
                    'text.' => [
                        'iconIdentifier' => 'content-text',
                        'iconOverlay' => 'actions-approve',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text.description',
                        'defaultValues' => [
                            'CType' => 'text',
                        ],
                    ],
                ],
            ],
            'menu.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.menu',
            ],
            'forms.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.forms',
            ],
            'plugins.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.plugins',
            ],
            // Group "lists" was removed e.g. by pageTS config.
            'special.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.special',
            ],
        ];
        $expected = [
            'default.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.default',
                'elements.' => [
                    'header.' => [
                        'iconIdentifier' => 'content-header',
                        'iconOverlay' => 'actions-approve',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header.description',
                        'defaultValues' => [
                            'CType' => 'header',
                        ],
                    ],
                    'text.' => [
                        'iconIdentifier' => 'content-text',
                        'iconOverlay' => 'actions-approve',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text.description',
                        'defaultValues' => [
                            'CType' => 'text',
                        ],
                    ],
                ],
            ],
            'menu.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.menu',
                'after' => [
                    'default.',
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
        $subject = $this->get(NewContentElementController::class);
        $orderWizardsMethod = (new \ReflectionMethod($subject, 'orderWizards'));
        $result = $orderWizardsMethod->invoke($subject, $wizards);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function contentElementWizardsAreLoadedFromTca(): void
    {
        $this->fillDefaultContentTypeTCA();
        $expected = [
            'default.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.default',
                'elements.' => [
                    'header.' => [
                        'iconIdentifier' => 'content-header',
                        'iconOverlay' => 'actions-approve',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header.description',
                        'defaultValues' => [
                            'CType' => 'header',
                        ],
                    ],
                    'text.' => [
                        'iconIdentifier' => 'content-text',
                        'iconOverlay' => 'actions-approve',
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
            ],
            'menu.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.menu',
            ],
            'forms.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.forms',
            ],
            'special.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.special',
            ],
            'plugins.' => [
                'header' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.plugins',
            ],
        ];
        $subject = $this->get(NewContentElementController::class);
        $loadAvailableWizardsMethod = (new \ReflectionMethod($subject, 'loadAvailableWizards'));
        $result = $loadAvailableWizardsMethod->invoke($subject);
        self::assertSame($expected, $result);
    }

    private function fillDefaultContentTypeTCA(): void
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
                        'iconOverlay' => 'actions-approve',
                        'group' => 'default',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text.description',
                        'value' => 'text',
                        'icon' => 'content-text',
                        'iconOverlay' => 'actions-approve',
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
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $tcaSchemaFactory->load($GLOBALS['TCA'], true);
    }
}
