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
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class VisibilityAspectTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getterReturnsProperDefaultValues()
    {
        $subject = new VisibilityAspect();
        self::assertFalse($subject->includeHiddenPages());
        self::assertFalse($subject->includeHiddenContent());
        self::assertFalse($subject->includeDeletedRecords());
    }

    /**
     * @test
     */
    public function getterReturnsProperValues()
    {
        $subject = new VisibilityAspect(true, true, true);
        self::assertTrue($subject->includeHiddenPages());
        self::assertTrue($subject->includeHiddenContent());
        self::assertTrue($subject->includeDeletedRecords());
    }

    /**
     * @test
     */
    public function getReturnsProperValues()
    {
        $subject = new VisibilityAspect(true, true, true);
        self::assertTrue($subject->get('includeHiddenPages'));
        self::assertTrue($subject->get('includeHiddenContent'));
        self::assertTrue($subject->get('includeDeletedRecords'));
    }

    /**
     * @test
     */
    public function getThrowsExceptionOnInvalidArgument()
    {
        $this->expectException(AspectPropertyNotFoundException::class);
        $this->expectExceptionCode(1527780439);
        $subject = new VisibilityAspect();
        $subject->get('football');
    }
}
