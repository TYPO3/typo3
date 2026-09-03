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

namespace TYPO3\CMS\Linkvalidator\Tests\Unit\Task;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Linkvalidator\Result\LinkAnalyzerResult;
use TYPO3\CMS\Linkvalidator\Tests\Unit\Task\Fixtures\TestableValidatorTask;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ValidatorTaskTest extends UnitTestCase
{
    #[Test]
    public function executeSendsReportForUnchangedResultAfterPreviousExecutionFailed(): void
    {
        $linkAnalyzerResult = self::createStub(LinkAnalyzerResult::class);
        $linkAnalyzerResult->method('getTotalBrokenLinksCount')->willReturn(1);
        $linkAnalyzerResult->method('isDifferentToLastResult')->willReturn(false);

        $subject = new TestableValidatorTask($linkAnalyzerResult, true);
        $subject->setTaskParameters([
            'page' => 1,
            'email' => 'recipient@example.com',
            'emailOnBrokenLinkOnly' => true,
        ]);

        self::assertTrue($subject->execute());
        self::assertSame(1, $subject->reportEmailCallCount);
    }

    #[Test]
    public function executeDoesNotSendReportForUnchangedResultAfterSuccessfulExecution(): void
    {
        $linkAnalyzerResult = self::createStub(LinkAnalyzerResult::class);
        $linkAnalyzerResult->method('getTotalBrokenLinksCount')->willReturn(1);
        $linkAnalyzerResult->method('isDifferentToLastResult')->willReturn(false);

        $subject = new TestableValidatorTask($linkAnalyzerResult, false);
        $subject->setTaskParameters([
            'page' => 1,
            'email' => 'recipient@example.com',
            'emailOnBrokenLinkOnly' => true,
        ]);

        self::assertTrue($subject->execute());
        self::assertSame(0, $subject->reportEmailCallCount);
    }
}
