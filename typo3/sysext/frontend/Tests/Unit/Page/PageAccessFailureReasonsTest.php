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

namespace TYPO3\CMS\Frontend\Tests\Unit\Page;

use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PageAccessFailureReasonsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getMessageForReasonReturnsExpectedMessageForCode()
    {
        $subject = new PageAccessFailureReasons();
        $message = $subject->getMessageForReason(PageAccessFailureReasons::NO_PAGES_FOUND);
        self::assertEquals('No page on rootlevel found', $message);
    }

    /**
     * @test
     */
    public function getMessageForReasonThrowsExceptionForWrongCode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1529299833);
        $subject = new PageAccessFailureReasons();
        $subject->getMessageForReason('Unknown Reason');
    }
}
