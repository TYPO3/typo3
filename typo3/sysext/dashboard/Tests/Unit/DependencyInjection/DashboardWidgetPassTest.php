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

use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TYPO3\CMS\Dashboard\DependencyInjection\DashboardWidgetPass;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DashboardWidgetPassTest extends UnitTestCase
{
    /**
     * @var DashboardWidgetPass
     */
    protected $subject;

    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * @var Definition
     */
    protected $widgetRegistryDefinition;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new DashboardWidgetPass('dashboard.widget');
        $this->container = $this->prophesize(ContainerBuilder::class);
        $this->widgetRegistryDefinition = $this->prophesize(Definition::class);
    }

    /**
     * @test
     */
    public function doesNothingIfWidgetRegistryIsUnknown(): void
    {
        $this->container->findDefinition(WidgetRegistry::class)->willReturn(false);
        $this->container->findTaggedServiceIds('dashboard.widget')->shouldNotBeCalled();

        $this->subject->process($this->container->reveal());
    }

    /**
     * @test
     */
    public function doesNothingIfNoWidgetsAreTagged(): void
    {
        $this->container->findDefinition(WidgetRegistry::class)->willReturn($this->widgetRegistryDefinition->reveal());
        $this->container->findTaggedServiceIds('dashboard.widget')->willReturn([])->shouldBeCalled();
        $this->widgetRegistryDefinition->addMethodCall()->shouldNotBeCalled();

        $this->subject->process($this->container->reveal());
    }

    /**
     * @test
     */
    public function makesWidgetPublic(): void
    {
        $this->container->findDefinition(WidgetRegistry::class)->willReturn($this->widgetRegistryDefinition->reveal());
        $this->container->findTaggedServiceIds('dashboard.widget')->willReturn(['NewsWidget' => []]);
        $definition = $this->prophesize(Definition::class);
        $this->container->findDefinition('NewsWidget')->willReturn($definition->reveal());
        $definition->setPublic(true)->shouldBeCalled();

        $this->subject->process($this->container->reveal());
    }

    /**
     * @test
     */
    public function registersTaggedWidgetWithMinimumConfigurationInRegistry(): void
    {
        $this->container->findDefinition(WidgetRegistry::class)->willReturn($this->widgetRegistryDefinition->reveal());
        $definition = $this->prophesize(Definition::class);
        $this->container->findDefinition('dashboard.widget.t3news')->willReturn($definition->reveal());
        $definition->setPublic(true);
        $definition->setArgument('$configuration', Argument::that(function ($argument) {
            return $argument instanceof Reference && (string)$argument === 't3newsWidgetConfiguration';
        }));

        $this->container->findTaggedServiceIds('dashboard.widget')->willReturn([
            'dashboard.widget.t3news' => [
                [
                    'identifier' => 't3news',
                    'groupNames' => 'typo3',
                    'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title',
                    'description' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description',
                ]
            ]
        ]);
        $this->container->addDefinitions(Argument::that(function (array $widgetConfigurationDefinitions) {
            $definition = $widgetConfigurationDefinitions['t3newsWidgetConfiguration'];
            /* @var Definition $definition */
            return $definition instanceof Definition
                && $definition->getClass(WidgetConfiguration::class)
                && $definition->getArgument('$identifier') === 't3news'
                && $definition->getArgument('$groupNames') === ['typo3']
                && $definition->getArgument('$title') ===  'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title'
                && $definition->getArgument('$description') ===  'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description'
                && $definition->getArgument('$iconIdentifier') ===  'content-dashboard'
                && $definition->getArgument('$height') === 'small'
                && $definition->getArgument('$width') === 'small'
                ;
        }))->shouldBeCalled();
        $this->widgetRegistryDefinition->addMethodCall(
            'registerWidget',
            [
                't3newsWidgetConfiguration',
            ]
        )->shouldBeCalled();

        $this->subject->process($this->container->reveal());
    }

    /**
     * @test
     */
    public function registersWidgetToMultipleGroupsByComma(): void
    {
        $this->container->findDefinition(WidgetRegistry::class)->willReturn($this->widgetRegistryDefinition->reveal());
        $definition = $this->prophesize(Definition::class);
        $this->container->findDefinition('dashboard.widget.t3news')->willReturn($definition->reveal());
        $definition->setPublic(true);
        $definition->setArgument('$configuration', Argument::that(function ($argument) {
            return $argument instanceof Reference && (string)$argument === 't3newsWidgetConfiguration';
        }));

        $this->container->findTaggedServiceIds('dashboard.widget')->willReturn([
            'dashboard.widget.t3news' => [
                [
                    'identifier' => 't3news',
                    'groupNames' => 'typo3, general',
                    'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title',
                    'description' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description',
                ]
            ]
        ]);
        $this->container->addDefinitions(Argument::that(function (array $widgetConfigurationDefinitions) {
            $definition = $widgetConfigurationDefinitions['t3newsWidgetConfiguration'];
            /* @var Definition $definition */
            return $definition instanceof Definition
                && $definition->getClass(WidgetConfiguration::class)
                && $definition->getArgument('$groupNames') === ['typo3', 'general']
                ;
        }))->shouldBeCalled();
        $this->widgetRegistryDefinition->addMethodCall(
            'registerWidget',
            [
                't3newsWidgetConfiguration',
            ]
        )->shouldBeCalled();

        $this->subject->process($this->container->reveal());
    }

    /**
     * @test
     */
    public function registersTaggedWidgetWithMaximumConfigurationInRegistry(): void
    {
        $this->container->findDefinition(WidgetRegistry::class)->willReturn($this->widgetRegistryDefinition->reveal());
        $definition = $this->prophesize(Definition::class);
        $this->container->findDefinition('dashboard.widget.t3news')->willReturn($definition->reveal());
        $definition->setPublic(true);
        $definition->setArgument('$configuration', Argument::that(function ($argument) {
            return $argument instanceof Reference && (string)$argument === 't3newsWidgetConfiguration';
        }));

        $this->container->findTaggedServiceIds('dashboard.widget')->willReturn([
            'dashboard.widget.t3news' => [
                [
                    'identifier' => 't3news',
                    'groupNames' => 'typo3',
                    'title' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title',
                    'description' => 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description',
                    'iconIdentifier' => 'some-icon',
                    'height' => 'large',
                    'width' => 'medium',
                ]
            ]
        ]);
        $this->container->addDefinitions(Argument::that(function (array $widgetConfigurationDefinitions) {
            $definition = $widgetConfigurationDefinitions['t3newsWidgetConfiguration'];
            /* @var Definition $definition */
            return $definition instanceof Definition
                && $definition->getClass(WidgetConfiguration::class)
                && $definition->getArgument('$identifier') === 't3news'
                && $definition->getArgument('$groupNames') === ['typo3']
                && $definition->getArgument('$title') ===  'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title'
                && $definition->getArgument('$description') ===  'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description'
                && $definition->getArgument('$iconIdentifier') ===  'some-icon'
                && $definition->getArgument('$height') === 'large'
                && $definition->getArgument('$width') === 'medium'
                ;
        }))->shouldBeCalled();
        $this->widgetRegistryDefinition->addMethodCall(
            'registerWidget',
            [
                't3newsWidgetConfiguration',
            ]
        )->shouldBeCalled();

        $this->subject->process($this->container->reveal());
    }
}
