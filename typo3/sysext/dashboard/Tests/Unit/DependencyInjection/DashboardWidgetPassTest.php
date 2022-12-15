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

namespace TYPO3\CMS\Dashboard\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Dashboard\DependencyInjection\DashboardWidgetPass;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DashboardWidgetPassTest extends UnitTestCase
{
    protected DashboardWidgetPass $subject;
    protected ContainerBuilder&MockObject $container;
    protected Definition&MockObject $widgetRegistryDefinition;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new DashboardWidgetPass('dashboard.widget');
        $this->container = $this->createMock(ContainerBuilder::class);
        $this->widgetRegistryDefinition = $this->createMock(Definition::class);
    }

    /**
     * @test
     */
    public function doesNothingIfWidgetRegistryIsUnknown(): void
    {
        $this->container->method('hasDefinition')->with(WidgetRegistry::class)->willReturn(false);
        $this->container->expects(self::never())->method('findTaggedServiceIds')->with('dashboard.widget');

        $this->subject->process($this->container);
    }

    /**
     * @test
     */
    public function doesNothingIfNoWidgetsAreTagged(): void
    {
        $this->container->method('hasDefinition')->with(WidgetRegistry::class)->willReturn(true);
        $this->container->method('findDefinition')->with(WidgetRegistry::class)->willReturn($this->widgetRegistryDefinition);
        $this->container->expects(self::once())->method('findTaggedServiceIds')->with('dashboard.widget')->willReturn([]);
        $this->widgetRegistryDefinition->expects(self::never())->method('addMethodCall');

        $this->subject->process($this->container);
    }

    /**
     * @test
     */
    public function makesWidgetPublic(): void
    {
        $this->container->method('hasDefinition')->with(WidgetRegistry::class)->willReturn(true);
        $this->container->method('findTaggedServiceIds')->with('dashboard.widget')->willReturn(['NewsWidget' => []]);
        $definition = $this->createMock(Definition::class);
        $this->container->method('findDefinition')->willReturnMap([
            [WidgetRegistry::class, $this->widgetRegistryDefinition],
            ['NewsWidget', $definition],
        ]);
        $definition->expects(self::once())->method('setPublic')->with(true)->willReturn($definition);

        $this->subject->process($this->container);
    }

    /**
     * @test
     */
    public function registersTaggedWidgetWithMinimumConfigurationInRegistry(): void
    {
        $this->container->method('hasDefinition')->with(WidgetRegistry::class)->willReturn(true);
        $definition = $this->createMock(Definition::class);
        $this->container->method('findDefinition')->willReturnMap([
            [WidgetRegistry::class, $this->widgetRegistryDefinition],
            ['dashboard.widget.t3news', $definition],
        ]);
        $definition->method('setPublic')->with(true)->willReturn($definition);
        $definition->method('setArgument')->with('$configuration', self::callback(static function ($argument) {
            return $argument instanceof Reference && (string)$argument === 't3newsWidgetConfiguration';
        }))->willReturn($definition);

        $this->container->method('findTaggedServiceIds')->with('dashboard.widget')->willReturn([
            'dashboard.widget.t3news' => [
                [
                    'identifier' => 't3news',
                    'groupNames' => 'typo3',
                    'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title',
                    'description' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description',
                ],
            ],
        ]);
        $this->container->expects(self::once())->method('addDefinitions')->with(self::callback(static function (array $widgetConfigurationDefinitions) {
            $definition = $widgetConfigurationDefinitions['t3newsWidgetConfiguration'];
            /* @var Definition $definition */
            return $definition instanceof Definition
                && $definition->getClass() === WidgetConfiguration::class
                && $definition->getArgument('$identifier') === 't3news'
                && $definition->getArgument('$groupNames') === ['typo3']
                && $definition->getArgument('$title') ===  'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title'
                && $definition->getArgument('$description') ===  'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description'
                && $definition->getArgument('$iconIdentifier') ===  'content-dashboard'
                && $definition->getArgument('$height') === 'small'
                && $definition->getArgument('$width') === 'small'
            ;
        }))->willReturn($definition);
        $this->widgetRegistryDefinition->expects(self::once())->method('addMethodCall')->with(
            'registerWidget',
            [
                't3newsWidgetConfiguration',
            ]
        )->willReturn($this->widgetRegistryDefinition);

        $this->subject->process($this->container);
    }

    /**
     * @test
     */
    public function registersWidgetToMultipleGroupsByComma(): void
    {
        $this->container->method('hasDefinition')->with(WidgetRegistry::class)->willReturn(true);
        $definition = $this->createMock(Definition::class);
        $this->container->method('findDefinition')->willReturnMap([
            [WidgetRegistry::class, $this->widgetRegistryDefinition],
            ['dashboard.widget.t3news', $definition],
        ]);
        $definition->method('setPublic')->with(true)->willReturn($definition);
        $definition->method('setArgument')->with('$configuration', self::callback(static function ($argument) {
            return $argument instanceof Reference && (string)$argument === 't3newsWidgetConfiguration';
        }))->willReturn($definition);

        $this->container->method('findTaggedServiceIds')->with('dashboard.widget')->willReturn([
            'dashboard.widget.t3news' => [
                [
                    'identifier' => 't3news',
                    'groupNames' => 'typo3, general',
                    'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title',
                    'description' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description',
                ],
            ],
        ]);
        $this->container->expects(self::once())->method('addDefinitions')->with(self::callback(static function (array $widgetConfigurationDefinitions) {
            $definition = $widgetConfigurationDefinitions['t3newsWidgetConfiguration'];
            /* @var Definition $definition */
            return $definition instanceof Definition
                && $definition->getClass() === WidgetConfiguration::class
                && $definition->getArgument('$groupNames') === ['typo3', 'general']
            ;
        }));
        $this->widgetRegistryDefinition->expects(self::once())->method('addMethodCall')->with(
            'registerWidget',
            [
                't3newsWidgetConfiguration',
            ]
        )->willReturn($definition);

        $this->subject->process($this->container);
    }

    /**
     * @test
     */
    public function registersTaggedWidgetWithMaximumConfigurationInRegistry(): void
    {
        $this->container->method('hasDefinition')->with(WidgetRegistry::class)->willReturn(true);
        $definition = $this->createMock(Definition::class);
        $this->container->method('findDefinition')->willReturnMap([
            [WidgetRegistry::class, $this->widgetRegistryDefinition],
            ['dashboard.widget.t3news', $definition],
        ]);
        $definition->method('setPublic')->with(true)->willReturn($definition);
        $definition->method('setArgument')->with('$configuration', self::callback(static function ($argument) {
            return $argument instanceof Reference && (string)$argument === 't3newsWidgetConfiguration';
        }))->willReturn($definition);

        $this->container->method('findTaggedServiceIds')->with('dashboard.widget')->willReturn([
            'dashboard.widget.t3news' => [
                [
                    'identifier' => 't3news',
                    'groupNames' => 'typo3',
                    'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title',
                    'description' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description',
                    'iconIdentifier' => 'some-icon',
                    'height' => 'large',
                    'width' => 'medium',
                ],
            ],
        ]);
        $this->container->expects(self::once())->method('addDefinitions')->with(self::callback(static function (array $widgetConfigurationDefinitions) {
            $definition = $widgetConfigurationDefinitions['t3newsWidgetConfiguration'];
            /* @var Definition $definition */
            return $definition instanceof Definition
                && $definition->getClass() === WidgetConfiguration::class
                && $definition->getArgument('$identifier') === 't3news'
                && $definition->getArgument('$groupNames') === ['typo3']
                && $definition->getArgument('$title') ===  'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title'
                && $definition->getArgument('$description') ===  'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description'
                && $definition->getArgument('$iconIdentifier') ===  'some-icon'
                && $definition->getArgument('$height') === 'large'
                && $definition->getArgument('$width') === 'medium'
            ;
        }));
        $this->widgetRegistryDefinition->expects(self::once())->method('addMethodCall')->with(
            'registerWidget',
            [
                't3newsWidgetConfiguration',
            ]
        )->willReturn($definition);

        $this->subject->process($this->container);
    }
}
