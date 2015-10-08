<?php
namespace TYPO3\CMS\Core\Log\Writer;

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
 * Log writer interface
 */
interface WriterInterface
{
    /**
     * Writes the log record
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $record Log record
     * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
     * @throws \Exception
     */
    public function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record);
}
