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
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;

/**
 * A writer dedicated for testing
 */
class WriterFixture extends AbstractWriter
{
    /**
     * @var array
     */
    protected $records = [];

    public function writeLog(LogRecord $record)
    {
        $this->records[] = $record;
    }

    public function getRecords(): array
    {
        return $this->records;
    }
}
