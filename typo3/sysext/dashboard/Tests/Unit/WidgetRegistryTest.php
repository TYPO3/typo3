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

namespace TYPO3\CMS\Dashboard\Tests\Unit;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class WidgetRegistryTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var WidgetRegistry
     */
    protected $subject;

    /**
     * @var BackendUserAuthentication|ObjectProphecy
     */
    protected $beUserProphecy;

    /**
     * @var ContainerInterface|ObjectProphecy
     */
    protected $containerProphecy;

    public function setUp(): void
    {
        $this->beUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $this->containerProphecy = $this->prophesize(ContainerInterface::class);

        $GLOBALS['BE_USER'] = $this->beUserProphecy->reveal();
        $this->subject = new WidgetRegistry($this->containerProphecy->reveal());
    }

    /**
     * @test
     */
    public function initiallyZeroWidgetAreRegistered(): void
    {
        self::assertCount(0, $this->subject->getAllWidgets());
    }

    /**
     * @param array $expectedValues
     * @param array $widgetsToRegister
     *
     * @test
     * @dataProvider widgetsToRegister
     */
    public function getAllWidgetReturnsAllRegisteredWidgets(array $expectedValues, array $widgetsToRegister): void
    {
        $this->registerWidgets($widgetsToRegister);

        self::assertCount((int)$expectedValues['count'], $this->subject->getAllWidgets());
    }

    /**
     * @param array $expectedValues
     * @param array $widgetsToRegister
     *
     * @test
     * @dataProvider widgetsToRegister
     */
    public function returnsWidgetsForGroup(
        array $expectedValues,
        array $widgetsToRegister
    ): void {
        $this->registerWidgets($widgetsToRegister);
        $this->beUserProphecy->check('available_widgets', Argument::any())->willReturn(true);

        self::assertCount(
            (int)$expectedValues['group1Count'],
            $this->subject->getAvailableWidgetsForWidgetGroup('group1')
        );
    }

    /**
     * @param array $expectedValues
     * @param array $widgetsToRegister
     *
     * @test
     * @dataProvider widgetsToRegister
     */
    public function getAvailableWidgetsOnlyReturnWidgetsAccessibleByAdmin(
        array $expectedValues,
        array $widgetsToRegister
    ): void {
        foreach ($widgetsToRegister as $widget) {
            $this->registerWidget($widget);

            $this->beUserProphecy
                ->check(
                    'available_widgets',
                    $widget['identifier']
                )
                ->willReturn(true);
        }

        self::assertCount((int)$expectedValues['adminCount'], $this->subject->getAvailableWidgets());
    }

    /**
     * @param array $expectedValues
     * @param array $widgetsToRegister
     *
     * @test
     * @dataProvider widgetsToRegister
     */
    public function getAvailableWidgetsOnlyReturnWidgetsAccessibleByUser(
        array $expectedValues,
        array $widgetsToRegister
    ): void {
        foreach ($widgetsToRegister as $widget) {
            $this->registerWidget($widget);

            $this->beUserProphecy
                ->check(
                    'available_widgets',
                    $widget['identifier']
                )
                ->shouldBeCalled()
                ->willReturn($widget['availableForUser']);
        }

        self::assertCount((int)$expectedValues['userCount'], $this->subject->getAvailableWidgets());
    }

    /**
     * @test
     */
    public function addWidgetsInItemsProcFunc(): void
    {
        $this->registerWidgets([
            [
                'serviceName' => 'dashboard.widget.t3news',
                'identifier' => 't3orgnews',
                'groups' => ['typo3'],
                'iconIdentifier' => 'content-widget-rss',
                'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                'height' => 4,
                'width' => 4,
                'additionalCssClasses' => [],
            ],
            [
                'serviceName' => 'dashboard.widget.t3comnews',
                'identifier' => '2ndWidget',
                'groups' => ['typo3'],
                'iconIdentifier' => 'content-widget-2nd',
                'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:2ndWidget.title',
                'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:2ndWidget.description',
                'height' => 4,
                'width' => 4,
                'additionalCssClasses' => [],
            ],
        ]);

        $parameters = [];
        $this->subject->widgetItemsProcFunc($parameters);

        self::assertEquals(
            [
                'items' => [
                    [
                        'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                        't3orgnews',
                        'content-widget-rss'
                    ],
                    [
                        'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:2ndWidget.title',
                        '2ndWidget',
                        'content-widget-2nd'
                    ],
                ]
            ],
            $parameters
        );
    }

    private function registerWidgets(array $widgetsToRegister): void
    {
        foreach ($widgetsToRegister as $widget) {
            $this->registerWidget($widget);
        }
    }

    private function registerWidget(array $widget)
    {
        $widgetConfiguration = $this->prophesize(WidgetConfigurationInterface::class);
        $widgetConfiguration->getTitle()->willReturn($widget['title']);
        $widgetConfiguration->getIdentifier()->willReturn($widget['identifier']);
        $widgetConfiguration->getIconIdentifier()->willReturn($widget['iconIdentifier']);
        $widgetConfiguration->getDescription()->willReturn($widget['description']);
        $widgetConfiguration->getGroupNames()->willReturn($widget['groups']);
        $widgetConfiguration->getHeight()->willReturn($widget['height']);
        $widgetConfiguration->getWidth()->willReturn($widget['width']);
        $widgetConfiguration->getAdditionalCssClasses()->willReturn($widget['additionalCssClasses']);

        $this->containerProphecy->get($widget['serviceName'])->willReturn($widgetConfiguration->reveal());
        $this->subject->registerWidget($widget['serviceName']);
    }

    public function widgetsToRegister(): array
    {
        return [
            'Single widget' => [
                [
                    'count' => 1,
                    'adminCount' => 1,
                    'userCount' => 1,
                    'group1Count' => 1,
                ],
                [
                    [
                        'identifier' => 'test-widget1',
                        'serviceName' => 'dashboard.widget.t3news',
                        'groups' => ['group1'],
                        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                        'iconIdentifier' => 'content-widget-rss',
                        'height' => 2,
                        'width' => 4,
                        'additionalCssClasses' => [
                            'custom-widget',
                            'rss-condensed',
                        ],
                        'availableForUser' => true
                    ]
                ]
            ],
            'Two widgets' => [
                [
                    'count' => 2,
                    'adminCount' => 2,
                    'userCount' => 1,
                    'group1Count' => 2,
                ],
                [
                    [
                        'identifier' => 'test-widget1',
                        'serviceName' => 'dashboard.widget.t3news',
                        'groups' => ['group1'],
                        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                        'iconIdentifier' => 'content-widget-rss',
                        'height' => 2,
                        'width' => 4,
                        'additionalCssClasses' => [
                            'custom-widget',
                            'rss-condensed',
                        ],
                        'availableForUser' => true,
                    ],
                    [
                        'identifier' => 'test-widget2',
                        'serviceName' => 'dashboard.widget.t3comnews',
                        'groups' => ['group1'],
                        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                        'iconIdentifier' => 'content-widget-rss',
                        'height' => 2,
                        'width' => 4,
                        'additionalCssClasses' => [
                            'custom-widget',
                            'rss-condensed',
                        ],
                        'availableForUser' => false
                    ],
                ]
            ],
            'Three widgets, two having same identifier' => [
                [
                    'count' => 2,
                    'adminCount' => 2,
                    'userCount' => 2,
                    'group1Count' => 1,
                ],
                [
                    [
                        'identifier' => 'test-widget1',
                        'serviceName' => 'dashboard.widget.t3news',
                        'groups' => ['group1'],
                        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                        'iconIdentifier' => 'content-widget-rss',
                        'height' => 2,
                        'width' => 4,
                        'additionalCssClasses' => [
                            'custom-widget',
                            'rss-condensed',
                        ],
                        'availableForUser' => true
                    ],
                    [
                        'identifier' => 'test-widget1',
                        'serviceName' => 'dashboard.widget.t3news',
                        'groups' => ['group1'],
                        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                        'iconIdentifier' => 'content-widget-rss',
                        'height' => 2,
                        'width' => 4,
                        'additionalCssClasses' => [
                            'custom-widget',
                            'rss-condensed',
                        ],
                        'availableForUser' => true
                    ],
                    [
                        'identifier' => 'test-widget2',
                        'serviceName' => 'dashboard.widget.t3orgnews',
                        'groups' => ['group2'],
                        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                        'iconIdentifier' => 'content-widget-rss',
                        'height' => 2,
                        'width' => 4,
                        'additionalCssClasses' => [
                            'custom-widget',
                            'rss-condensed',
                        ],
                        'availableForUser' => true
                    ],
                ]
            ],
            'Two widgets, one not available for user' => [
                [
                    'count' => 2,
                    'adminCount' => 2,
                    'userCount' => 1,
                    'group1Count' => 1,
                ],
                [
                    [
                        'identifier' => 'test-widget1',
                        'serviceName' => 'dashboard.widget.t3news',
                        'groups' => ['group1'],
                        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                        'iconIdentifier' => 'content-widget-rss',
                        'height' => 2,
                        'width' => 4,
                        'additionalCssClasses' => [
                            'custom-widget',
                            'rss-condensed',
                        ],
                        'availableForUser' => true
                    ],
                    [
                        'identifier' => 'test-widget2',
                        'serviceName' => 'dashboard.widget.t3comnews',
                        'groups' => ['group2'],
                        'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                        'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                        'iconIdentifier' => 'content-widget-rss',
                        'height' => 2,
                        'width' => 4,
                        'additionalCssClasses' => [
                            'custom-widget',
                            'rss-condensed',
                        ],
                        'availableForUser' => false
                    ],
                ]
            ],
        ];
    }
}
