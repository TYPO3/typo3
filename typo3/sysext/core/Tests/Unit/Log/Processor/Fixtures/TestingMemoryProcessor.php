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

namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor\Fixtures;

use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor;

/**
 * Testing subclass of the abstract class.
 */
final class TestingMemoryProcessor extends AbstractMemoryProcessor
{
    public function processLogRecord(LogRecord $logRecord): LogRecord
    {
        throw new \BadMethodCallException('Not implemented', 1691578434);
    }
}
