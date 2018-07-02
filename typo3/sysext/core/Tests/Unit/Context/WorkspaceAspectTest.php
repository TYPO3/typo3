<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Context;

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
        $this->assertTrue($subject->isLive());
        $this->assertEquals(0, $subject->getId());
        $this->assertEquals(0, $subject->get('id'));
        $this->assertTrue($subject->get('isLive'));
        $this->assertFalse($subject->get('isOffline'));
    }

    /**
     * @test
     */
    public function getterReturnsProperCustomValues()
    {
        $subject = new WorkspaceAspect(13);
        $this->assertEquals(13, $subject->getId());
        $this->assertEquals(13, $subject->get('id'));
        $this->assertFalse($subject->isLive());
        $this->assertFalse($subject->get('isLive'));
        $this->assertTrue($subject->get('isOffline'));
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
