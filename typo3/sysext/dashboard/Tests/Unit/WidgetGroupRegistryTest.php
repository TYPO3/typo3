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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\WidgetGroup;
use TYPO3\CMS\Dashboard\WidgetGroupRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class WidgetGroupRegistryTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /** @var WidgetGroupRegistry  */
    protected $subject;

    public function setUp(): void
    {
        $this->subject = GeneralUtility::makeInstance(
            WidgetGroupRegistry::class
        );
    }

    /**
     * @test
     */
    public function initiallyZeroWidgetGroupsAreRegistered(): void
    {
        self::assertCount(0, $this->subject->getWidgetGroups());
    }

    /**
     * @test
     */
    public function getWidgetsMethodReturnsWidgetGroupObjects(): void
    {
        $widgetGroup1 = new WidgetGroup('identifier1', 'title1');
        $widgetGroup2 = new WidgetGroup('identifier2', 'title2');

        $this->subject->registerWidgetGroup($widgetGroup1);
        $this->subject->registerWidgetGroup($widgetGroup2);

        // Check if all registered widget groups contains an instance of WidgetGroup
        foreach ($this->subject->getWidgetGroups() as $identifier => $widgetGroupObject) {
            self::assertInstanceOf(WidgetGroup::class, $widgetGroupObject);
        }
    }

    /**
     * @test
     */
    public function widgetGroupsGetRegistered(): void
    {
        self::assertCount(0, $this->subject->getWidgetGroups());

        // Register a first widget group
        $widgetGroup = new WidgetGroup('identifier', 'title');
        $this->subject->registerWidgetGroup($widgetGroup);

        // Check if 1 widget group is found
        self::assertCount(1, $this->subject->getWidgetGroups());

        // Register same widget group again
        $this->subject->registerWidgetGroup($widgetGroup);
        self::assertCount(1, $this->subject->getWidgetGroups());

        // Register new widget group and check if it is registered successfully
        $widgetGroup2 = new WidgetGroup('identifier2', 'title2');
        $this->subject->registerWidgetGroup($widgetGroup2);

        $widgetGroups = $this->subject->getWidgetGroups();
        self::assertCount(2, $widgetGroups);

        // Check if the identifiers are correctly registered
        self::assertEquals(['identifier', 'identifier2'], array_keys($widgetGroups));
    }

    /**
     * @test
     */
    public function alternativeRepositoryObjectReturnsSameResults(): void
    {
        $widgetGroup1 = new WidgetGroup('identifier1', 'title1');
        $widgetGroup2 = new WidgetGroup('identifier2', 'title2');

        $this->subject->registerWidgetGroup($widgetGroup1);
        $this->subject->registerWidgetGroup($widgetGroup2);

        $alternativeWidgetGroupRepository = GeneralUtility::makeInstance(
            WidgetGroupRegistry::class
        );

        self::assertEquals(
            $this->subject->getWidgetGroups(),
            $alternativeWidgetGroupRepository->getWidgetGroups()
        );
    }
}
