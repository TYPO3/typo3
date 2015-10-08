<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Fixtures;

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

/**
 * A log writer that always fails to write (for testing purposes ;-))
 */
class WriterFailing implements \TYPO3\CMS\Core\Log\Writer\WriterInterface
{
    /**
     * Try to write the log entry - but throw an exception in our case
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $record
     * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface|void
     * @throws \RuntimeException
     */
    public function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record)
    {
        throw new \RuntimeException('t3lib_log_writer_Failing failed');
    }
}
