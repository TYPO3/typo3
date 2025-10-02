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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class NewContentElementWizardControllerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function loadAvailableWizardsFromContentElementsReturnsExpectedArrayDataProvider(): iterable
    {
        yield 'Content Element in default group' => [
            'item' => [
                'label' => 'Element A',
                'value' => 'element_a',
                'group' => 'default',
                'icon' => 'content-header',
                'iconOverlay' => 'content-text',
            ],
            'creationOptions' => [],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'iconOverlay' => 'content-text',
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
                'iconOverlay' => 'content-text',
            ],
            'creationOptions' => [],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'iconOverlay' => 'content-text',
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
                'iconOverlay' => 'content-text',
            ],
            'creationOptions' => [],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'iconOverlay' => 'content-text',
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
                'iconOverlay' => 'content-text',
            ],
            'creationOptions' => [
                'defaultValues' => [
                    'header' => 'Foo',
                ],
                'saveAndClose' => true,
            ],
            'expected' => [
                'iconIdentifier' => 'content-header',
                'iconOverlay' => 'content-text',
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

    #[DataProvider('loadAvailableWizardsFromContentElementsReturnsExpectedArrayDataProvider')]
    #[Test]
    public function loadAvailableWizardsFromContentElementsReturnsExpectedArray(array $item, array $creationOptions, array $expected): void
    {
        $group = $item['group'] ?? '';
        $type = $item['value'];
        $newContentElementWizardController = $this->get(NewContentElementController::class);
        $loadAvailableWizardsFromContentElements = new \ReflectionMethod(
            NewContentElementController::class,
            'loadAvailableWizardsFromContentElements'
        );

        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        ExtensionManagementUtility::addTcaSelectItem(
            'tt_content',
            'CType',
            $item
        );

        $GLOBALS['TCA']['tt_content']['types'][$type]['creationOptions'] = $creationOptions;

        $result = $loadAvailableWizardsFromContentElements->invoke($newContentElementWizardController);
        $actual = $result[$group . '.']['elements.'][$type . '.'] ?? [];
        self::assertSame($expected, $actual);
    }

    public static function loadAvailableWizardsFromPluginSubTypeReturnsExpectedArrayDataProvider(): iterable
    {
        yield 'Plugin sub-type with icon' => [
            'item' => [
                'label' => 'Plugin A',
                'value' => 'plugin_a',
                'group' => 'plugins',
                'icon' => 'content-plugin',
                'iconOverlay' => 'content-text',
            ],
            'expected' => [
                'iconIdentifier' => 'content-plugin',
                'iconOverlay' => 'content-text',
                'title' => 'Plugin A',
                'description' => '',
                'defaultValues' => [
                    'CType' => 'list',
                    'list_type' => 'plugin_a',
                ],
            ],
        ];
    }

    #[DataProvider('loadAvailableWizardsFromPluginSubTypeReturnsExpectedArrayDataProvider')]
    #[Test]
    public function loadAvailableWizardsFromPluginSubTypeReturnsExpectedArray(array $item, array $expected): void
    {
        $group = $item['group'] ?? '';
        $type = $item['value'];
        $newContentElementWizardController = $this->get(NewContentElementController::class);
        $loadAvailableWizardsFromPluginSubTypes = new \ReflectionMethod(
            NewContentElementController::class,
            'loadAvailableWizardsFromPluginSubTypes'
        );

        // items array is undefined in TCA, add empty array.
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [];
        ExtensionManagementUtility::addTcaSelectItem(
            'tt_content',
            'list_type',
            $item
        );

        $result = $loadAvailableWizardsFromPluginSubTypes->invoke($newContentElementWizardController);
        $actual = $result[$group . '.']['elements.'][$type . '.'] ?? [];
        self::assertSame($expected, $actual);
    }
}
