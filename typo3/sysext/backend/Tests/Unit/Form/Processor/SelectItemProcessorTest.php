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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\Processor;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Processor\SelectItemProcessor;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SelectItemProcessorTest extends UnitTestCase
{
    public static function dividersAddedForEachGroupAndSortedDataProvider(): iterable
    {
        yield 'All empty' => [
            'items' => [],
            'groups' => [],
            'sortOrders' => [],
            'expected' => [],
        ];

        yield 'no groups' => [
            'items' => [
                [
                    'label' => 'foo',
                    'value' => 'three',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                ],
            ],
            'groups' => [],
            'sortOrders' => [],
            'expected' => [
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => null,
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => null,
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => null,
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => null,
                    'description' => null,
                ],
            ],
        ];

        yield 'some with group, some without' => [
            'items' => [
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                ],
            ],
            'groups' => [
                'group1' => 'Group 1',
            ],
            'sortOrders' => [],
            'expected' => [
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'none',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'none',
                    'description' => null,
                ],
                [
                    'label' => 'Group 1',
                    'value' => '--div--',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
            ],
        ];

        yield 'groups assigned, but not defined' => [
            'items' => [
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'group' => 'group1',
                ],
            ],
            'groups' => [],
            'sortOrders' => [],
            'expected' => [
                [
                    'label' => 'group2',
                    'value' => '--div--',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
                [
                    'label' => 'group1',
                    'value' => '--div--',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
            ],
        ];

        yield 'mixed order, default sorting' => [
            'items' => [
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'group' => 'group1',
                ],
            ],
            'groups' => [
                'group1' => 'Group 1',
                'group2' => 'Group 2',
            ],
            'sortOrders' => [],
            'expected' => [
                [
                    'label' => 'Group 1',
                    'value' => '--div--',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'Group 2',
                    'value' => '--div--',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
            ],
        ];

        yield 'legacy dividers defined in items together with groups are ignored' => [
            'items' => [
                [
                    'label' => 'manual group 1',
                    'value' => '--div--',
                ],
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'group' => 'group1',
                ],
                [
                    'label' => 'manual group 2',
                    'value' => '--div--',
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'group' => 'group1',
                ],
            ],
            'groups' => [
                'group1' => 'Group 1',
                'group2' => 'Group 2',
            ],
            'sortOrders' => [],
            'expected' => [
                [
                    'label' => 'Group 1',
                    'value' => '--div--',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'Group 2',
                    'value' => '--div--',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
            ],
        ];

        yield 'legacy dividers with group defined in items together with groups override label' => [
            'items' => [
                [
                    'label' => 'manual group 1',
                    'value' => '--div--',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'group' => 'group1',
                ],
                [
                    'label' => 'manual group 2',
                    'value' => '--div--',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'group' => 'group1',
                ],
            ],
            'groups' => [
                'group1' => 'Group 1',
                'group2' => 'Group 2',
            ],
            'sortOrders' => [],
            'expected' => [
                [
                    'label' => 'manual group 1',
                    'value' => '--div--',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'manual group 2',
                    'value' => '--div--',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
            ],
        ];

        yield 'mixed order, sort by value asc' => [
            'items' => [
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'group' => 'group1',
                ],
            ],
            'groups' => [
                'group1' => 'Group 1',
                'group2' => 'Group 2',
            ],
            'sortOrders' => [
                'value' => 'asc',
            ],
            'expected' => [
                [
                    'label' => 'Group 1',
                    'value' => '--div--',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'Group 2',
                    'value' => '--div--',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
            ],
        ];

        yield 'mixed order, sort by value desc' => [
            'items' => [
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'group' => 'group1',
                ],
            ],
            'groups' => [
                'group1' => 'Group 1',
                'group2' => 'Group 2',
            ],
            'sortOrders' => [
                'value' => 'desc',
            ],
            'expected' => [
                [
                    'label' => 'Group 1',
                    'value' => '--div--',
                    'group' => 'group1',
                ],
                [
                    'label' => 'foo',
                    'value' => 'two',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'one',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'Group 2',
                    'value' => '--div--',
                    'group' => 'group2',
                ],
                [
                    'label' => 'foo',
                    'value' => 'three',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
                [
                    'label' => 'foo',
                    'value' => 'four',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
            ],
        ];

        yield 'mixed order, sort by label asc' => [
            'items' => [
                [
                    'label' => 'Three',
                    'value' => 'three',
                    'group' => 'group2',
                ],
                [
                    'label' => 'One',
                    'value' => 'one',
                    'group' => 'group1',
                ],
                [
                    'label' => 'Four',
                    'value' => 'four',
                    'group' => 'group2',
                ],
                [
                    'label' => 'Two',
                    'value' => 'two',
                    'group' => 'group1',
                ],
            ],
            'groups' => [
                'group1' => 'Group 1',
                'group2' => 'Group 2',
            ],
            'sortOrders' => [
                'label' => 'asc',
            ],
            'expected' => [
                [
                    'label' => 'Group 1',
                    'value' => '--div--',
                    'group' => 'group1',
                ],
                [
                    'label' => 'One',
                    'value' => 'one',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'Two',
                    'value' => 'two',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'Group 2',
                    'value' => '--div--',
                    'group' => 'group2',
                ],
                [
                    'label' => 'Four',
                    'value' => 'four',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
                [
                    'label' => 'Three',
                    'value' => 'three',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
            ],
        ];

        yield 'mixed order, sort by label desc' => [
            'items' => [
                [
                    'label' => 'Three',
                    'value' => 'three',
                    'group' => 'group2',
                ],
                [
                    'label' => 'One',
                    'value' => 'one',
                    'group' => 'group1',
                ],
                [
                    'label' => 'Four',
                    'value' => 'four',
                    'group' => 'group2',
                ],
                [
                    'label' => 'Two',
                    'value' => 'two',
                    'group' => 'group1',
                ],
            ],
            'groups' => [
                'group1' => 'Group 1',
                'group2' => 'Group 2',
            ],
            'sortOrders' => [
                'label' => 'desc',
            ],
            'expected' => [
                [
                    'label' => 'Group 1',
                    'value' => '--div--',
                    'group' => 'group1',
                ],
                [
                    'label' => 'Two',
                    'value' => 'two',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'One',
                    'value' => 'one',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'Group 2',
                    'value' => '--div--',
                    'group' => 'group2',
                ],
                [
                    'label' => 'Three',
                    'value' => 'three',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
                [
                    'label' => 'Four',
                    'value' => 'four',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
            ],
        ];

        yield 'mixed order, sort by label asc value asc' => [
            'items' => [
                [
                    'label' => 'Foo',
                    'value' => 'three',
                    'group' => 'group2',
                ],
                [
                    'label' => 'Foo',
                    'value' => 'one',
                    'group' => 'group1',
                ],
                [
                    'label' => 'Foo',
                    'value' => 'four',
                    'group' => 'group2',
                ],
                [
                    'label' => 'Foo',
                    'value' => 'two',
                    'group' => 'group1',
                ],
            ],
            'groups' => [
                'group1' => 'Group 1',
                'group2' => 'Group 2',
            ],
            'sortOrders' => [
                'label' => 'asc',
                'value' => 'asc',
            ],
            'expected' => [
                [
                    'label' => 'Group 1',
                    'value' => '--div--',
                    'group' => 'group1',
                ],
                [
                    'label' => 'Foo',
                    'value' => 'one',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'Foo',
                    'value' => 'two',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group1',
                    'description' => null,
                ],
                [
                    'label' => 'Group 2',
                    'value' => '--div--',
                    'group' => 'group2',
                ],
                [
                    'label' => 'Foo',
                    'value' => 'four',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
                [
                    'label' => 'Foo',
                    'value' => 'three',
                    'icon' => null,
                    'iconOverlay' => null,
                    'group' => 'group2',
                    'description' => null,
                ],
            ],
        ];
    }

    #[DataProvider('dividersAddedForEachGroupAndSortedDataProvider')]
    #[Test]
    public function dividersAreAddedForEachGroupWithLanguageServiceFactoryFallback(array $items, array $groups, array $sortOrders, array $expected): void
    {
        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)->getMock();
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturnArgument(0);
        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromUserPreferences')->with(self::anything())->willReturn($languageServiceMock);
        $selectItemProcessor = new SelectItemProcessor($languageServiceFactoryMock);
        $result = $selectItemProcessor->groupAndSortItems($items, $groups, $sortOrders);

        self::assertSame($expected, $result);
    }

    #[DataProvider('dividersAddedForEachGroupAndSortedDataProvider')]
    #[Test]
    public function dividersAreAddedForEachGroupWithGlobalLang(array $items, array $groups, array $sortOrders, array $expected): void
    {
        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)->getMock();
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceMock;
        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromUserPreferences')->with(self::anything())->willReturnCallback(static function () {
            throw new \RuntimeException(
                'LanguageServiceFactory->createFromUserPreferences() should not be called in ' . __METHOD__,
                1689946260
            );
        });
        $selectItemProcessor = new SelectItemProcessor($languageServiceFactoryMock);
        $result = $selectItemProcessor->groupAndSortItems($items, $groups, $sortOrders);

        self::assertSame($expected, $result);
    }
}
