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

namespace TYPO3\CMS\Dashboard\Tests\Functional;

use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfiguration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class WidgetRegistryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['dashboard'];
    protected WidgetRegistry $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_groups.csv');
    }

    protected function registerWidgets(): void
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $this->subject = new WidgetRegistry($container);

        $widgetsToRegister = [
            [
                'identifier' => 'test-widget1',
                'serviceName' => 'dashboard.widget.test1',
                'groupNames' => ['group1'],
                'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                'iconIdentifier' => 'content-widget-rss',
                'height' => 'small',
                'width' => 'small',
                'additionalCssClasses' => [
                    'custom-widget',
                    'rss-condensed',
                ],
            ],
            [
                'identifier' => 'test-widget2',
                'serviceName' => 'dashboard.widget.test2',
                'groupNames' => ['group1'],
                'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                'iconIdentifier' => 'content-widget-rss',
                'height' => 'small',
                'width' => 'small',
                'additionalCssClasses' => [
                    'custom-widget',
                    'rss-condensed',
                ],
            ],
            [
                'identifier' => 'test-widget3',
                'serviceName' => 'dashboard.widget.test3',
                'groupNames' => ['group2'],
                'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                'iconIdentifier' => 'content-widget-rss',
                'height' => 'small',
                'width' => 'small',
                'additionalCssClasses' => [
                    'custom-widget',
                    'rss-condensed',
                ],
            ],
            [
                'identifier' => 'test-widget4',
                'serviceName' => 'dashboard.widget.test3',
                'groupNames' => ['group1', 'group2'],
                'title' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                'iconIdentifier' => 'content-widget-rss',
                'height' => 'small',
                'width' => 'small',
                'additionalCssClasses' => [
                    'custom-widget',
                    'rss-condensed',
                ],
            ],
        ];

        foreach ($widgetsToRegister as $widgetToRegister) {
            $widgetConfiguration = new WidgetConfiguration(...$widgetToRegister);
            $container->set($widgetToRegister['serviceName'], $widgetConfiguration);
            $this->subject->registerWidget($widgetToRegister['serviceName']);
        }
    }

    /**
     * @test
     */
    public function initiallyZeroWidgetAreRegistered(): void
    {
        $subject = new WidgetRegistry($this->getContainer());
        self::assertCount(0, $subject->getAllWidgets());
    }

    /**
     * @test
     */
    public function getAllWidgetsReturnsAllRegisteredWidgets(): void
    {
        $this->registerWidgets();
        self::assertCount(4, $this->subject->getAllWidgets());
    }

    public function expectedAmountOfWidgetsForUserDataProvider(): array
    {
        return [
            'Admin User - access to all groups' => [
                1,
                3,
                2,
                4,
            ],
            'User with no backend group - no access' => [
                2,
                0,
                0,
                0,
            ],
            'Backend user with group UID 1 and 2 - access to all widgets by group UID 1' => [
                3,
                3,
                2,
                4,
            ],
            'Backend user with group UID 2 - access to widgets test-widget1 and test-widget2 by group UID 2' => [
                4,
                2,
                0,
                2,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider expectedAmountOfWidgetsForUserDataProvider
     */
    public function returnsExpectedAmountOfWidgetsForUser(
        int $userId,
        int $countGroup1,
        int $countGroup2,
        int $countTotal
    ): void {
        $this->registerWidgets();
        $this->setUpBackendUser($userId);

        self::assertCount($countGroup1, $this->subject->getAvailableWidgetsForWidgetGroup('group1'));
        self::assertCount($countGroup2, $this->subject->getAvailableWidgetsForWidgetGroup('group2'));
        self::assertCount($countTotal, $this->subject->getAvailableWidgets());
    }

    /**
     * @test
     */
    public function addWidgetsInItemsProcFunc(): void
    {
        $this->registerWidgets();
        $parameters = [];
        $this->subject->widgetItemsProcFunc($parameters);

        $expected = [
            'items' => [
                [
                    'label' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                    'value' => 'test-widget1',
                    'icon' => 'content-widget-rss',
                    'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                ],
                [
                    'label' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                    'value' => 'test-widget2',
                    'icon' => 'content-widget-rss',
                    'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                ],
                [
                    'label' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                    'value' => 'test-widget3',
                    'icon' => 'content-widget-rss',
                    'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                ],
                [
                    'label' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.title',
                    'value' => 'test-widget4',
                    'icon' => 'content-widget-rss',
                    'description' => 'LLL:EXT:dashboard/Resources/Private/Language/Widgets.xlf:T3OrgNews.description',
                ],
            ],
        ];

        self::assertEquals($expected, $parameters);
    }
}
