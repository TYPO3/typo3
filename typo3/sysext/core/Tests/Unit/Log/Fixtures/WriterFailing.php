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

namespace TYPO3\CMS\Core\Tests\Unit\Log\Fixtures;

use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;

/**
 * A log writer that always fails to write (for testing purposes ;-))
 */
class WriterFailing implements WriterInterface
{
    /**
     * Try to write the log entry - but throw an exception in our case
     *
     * @param LogRecord $record
     * @return WriterInterface|void
     * @throws \RuntimeException
     */
    public function writeLog(LogRecord $record)
    {
        throw new \RuntimeException('t3lib_log_writer_Failing failed', 1476122125);
    }
}
