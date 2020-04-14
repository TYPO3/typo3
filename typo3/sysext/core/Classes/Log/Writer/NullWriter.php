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

namespace TYPO3\CMS\Core\Log\Writer;

use TYPO3\CMS\Core\Log\LogRecord;

/**
 * Null writer - just forgets about everything
 */
class NullWriter extends AbstractWriter
{
    /**
     * Writes the log record
     *
     * @param LogRecord $record Log record
     * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
     */
    public function writeLog(LogRecord $record)
    {
        // do nothing
        return $this;
    }
}
