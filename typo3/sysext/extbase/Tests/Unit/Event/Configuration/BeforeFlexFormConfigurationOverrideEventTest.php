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

namespace TYPO3\CMS\Extbase\Tests\Unit\Event\Configuration;

use TYPO3\CMS\Extbase\Event\Configuration\BeforeFlexFormConfigurationOverrideEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class BeforeFlexFormConfigurationOverrideEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function canAccessCurrentConfiguration(): void
    {
        $event = new BeforeFlexFormConfigurationOverrideEvent(
            frameworkConfiguration: ['foo' => 'bar'],
            originalFlexFormConfiguration: ['foo' => ''],
            flexFormConfiguration: ['foo' => 'bar']
        );

        self::assertSame(['foo' => 'bar'], $event->getFrameworkConfiguration());
        self::assertSame(['foo' => ''], $event->getOriginalFlexFormConfiguration());
        self::assertSame(['foo' => 'bar'], $event->getFlexFormConfiguration());
    }

    /**
     * @test
     */
    public function canOverrideFlexFormConfiguration(): void
    {
        $event = new BeforeFlexFormConfigurationOverrideEvent(
            frameworkConfiguration: ['foo' => 'bar'],
            originalFlexFormConfiguration: ['foo' => ''],
            flexFormConfiguration: ['foo' => 'bar']
        );

        $event->setFlexFormConfiguration(['foo' => 'new']);

        self::assertSame(['foo' => 'new'], $event->getFlexFormConfiguration());
    }
}
