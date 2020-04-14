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

namespace TYPO3\CMS\Core\Tests\Unit\Context;

use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class WorkspaceAspectTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getterReturnsProperDefaultValues()
    {
        $subject = new WorkspaceAspect();
        self::assertTrue($subject->isLive());
        self::assertEquals(0, $subject->getId());
        self::assertEquals(0, $subject->get('id'));
        self::assertTrue($subject->get('isLive'));
        self::assertFalse($subject->get('isOffline'));
    }

    /**
     * @test
     */
    public function getterReturnsProperCustomValues()
    {
        $subject = new WorkspaceAspect(13);
        self::assertEquals(13, $subject->getId());
        self::assertEquals(13, $subject->get('id'));
        self::assertFalse($subject->isLive());
        self::assertFalse($subject->get('isLive'));
        self::assertTrue($subject->get('isOffline'));
    }

    /**
     * @test
     */
    public function getThrowsExceptionOnInvalidArgument()
    {
        $this->expectException(AspectPropertyNotFoundException::class);
        $this->expectExceptionCode(1527779447);
        $subject = new WorkspaceAspect();
        $subject->get('football');
    }
}
