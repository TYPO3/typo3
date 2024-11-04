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

        $result = $loadAvailableWizardsFromContentElements->invoke($newContentElementWizardController);
        $actual = $result[$group . '.']['elements.'][$type . '.'] ?? [];
        self::assertSame($expected, $actual);
    }
}
