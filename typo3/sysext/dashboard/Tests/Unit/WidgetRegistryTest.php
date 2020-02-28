<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Dashboard\Tests\Unit;

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

use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class WidgetRegistryTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /** @var WidgetRegistry  */
    protected $subject;

    /**
     * @var BackendUserAuthentication|ObjectProphecy
     */
    protected $beUserProphecy;

    public function setUp(): void
    {
        $this->beUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $GLOBALS['BE_USER'] = $this->beUserProphecy->reveal();
        $this->subject = new WidgetRegistry($containerProphecy->reveal());
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
        foreach ($widgetsToRegister as $widget) {
            $this->subject->registerWidget($widget['identifier'], $widget['className'], $widget['groups']);
        }

        self::assertCount((int)$expectedValues['count'], $this->subject->getAllWidgets());
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
            $this->subject->registerWidget($widget['identifier'], $widget['className'], $widget['groups']);

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
            $this->subject->registerWidget($widget['identifier'], $widget['className'], $widget['groups']);

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

    public function widgetsToRegister(): array
    {
        return [
            [
                [
                    'count' => 1,
                    'adminCount' => 1,
                    'userCount' => 1
                ],
                [
                    [
                        'identifier' => 'test-widget1',
                        'className' => 'TYPO3\CMS\Dashboard\Widgets\T3NewsWidget',
                        'groups' => ['group1'],
                        'availableForUser' => true
                    ]
                ]
            ],
            [
                [
                    'count' => 2,
                    'adminCount' => 2,
                    'userCount' => 1
                ],
                [
                    [
                        'identifier' => 'test-widget1',
                        'className' => 'TYPO3\CMS\Dashboard\Widgets\T3NewsWidget',
                        'groups' => ['group1'],
                        'availableForUser' => true
                    ],
                    [
                        'identifier' => 'test-widget2',
                        'className' => 'TYPO3\CMS\Dashboard\Widgets\T3NewsWidget',
                        'groups' => ['group1'],
                        'availableForUser' => false
                    ],
                ]
            ],
            [
                [
                    'count' => 2,
                    'adminCount' => 2,
                    'userCount' => 2
                ],
                [
                    [
                        'identifier' => 'test-widget1',
                        'className' => 'TYPO3\CMS\Dashboard\Widgets\T3NewsWidget',
                        'groups' => ['group1'],
                        'availableForUser' => true
                    ],
                    [
                        'identifier' => 'test-widget1',
                        'className' => 'TYPO3\CMS\Dashboard\Widgets\T3NewsWidget',
                        'groups' => ['group1'],
                        'availableForUser' => true
                    ],
                    [
                        'identifier' => 'test-widget2',
                        'className' => 'TYPO3\CMS\Dashboard\Widgets\T3NewsWidget',
                        'groups' => ['group2'],
                        'availableForUser' => true
                    ],
                ]
            ],
            [
                [
                    'count' => 2,
                    'adminCount' => 2,
                    'userCount' => 1
                ],
                [
                    [
                        'identifier' => 'test-widget1',
                        'className' => 'TYPO3\CMS\Dashboard\Widgets\T3NewsWidget',
                        'groups' => ['group1'],
                        'availableForUser' => true
                    ],
                    [
                        'identifier' => 'test-widget2',
                        'className' => 'TYPO3\CMS\Dashboard\Widgets\T3NewsWidget',
                        'groups' => ['group1', 'group2'],
                        'availableForUser' => false
                    ],
                ]
            ],
        ];
    }
}
