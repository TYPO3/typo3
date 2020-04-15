<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Log\Fixtures;

use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\AbstractProcessor;

/**
 * A processor dedicated for testing
 */
class ProcessorFixture extends AbstractProcessor
{
    /**
     * Processing the record
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $record
     * @return \TYPO3\CMS\Core\Log\LogRecord
     */
    public function processLogRecord(LogRecord $record)
    {
        return $record;
    }
}
