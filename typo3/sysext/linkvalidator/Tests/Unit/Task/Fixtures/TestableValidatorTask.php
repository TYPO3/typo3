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

namespace TYPO3\CMS\Linkvalidator\Tests\Unit\Task\Fixtures;

use TYPO3\CMS\Linkvalidator\Result\LinkAnalyzerResult;
use TYPO3\CMS\Linkvalidator\Task\ValidatorTask;

final class TestableValidatorTask extends ValidatorTask
{
    public int $reportEmailCallCount = 0;

    public function __construct(
        private readonly LinkAnalyzerResult $linkAnalyzerResult,
        private readonly bool $lastExecutionFailed,
    ) {}

    protected function setCliArguments(): self
    {
        return $this;
    }

    protected function loadModTSconfig(): void {}

    protected function getLinkAnalyzerResult(): LinkAnalyzerResult
    {
        return $this->linkAnalyzerResult;
    }

    protected function reportEmail(LinkAnalyzerResult $linkAnalyzerResult): bool
    {
        ++$this->reportEmailCallCount;
        return true;
    }

    protected function hasLastExecutionFailed(): bool
    {
        return $this->lastExecutionFailed;
    }
}
