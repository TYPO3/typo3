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

namespace TYPO3\CMS\Belog\ViewHelpers;

use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to create detail string from a log entry.
 *
 * ```
 *   <belog:formatDetails logEntry="{logItem}" />
 * ```
 *
 * @internal
 */
final class FormatDetailsViewHelper extends AbstractViewHelper
{
    use LogDataTrait;

    public function initializeArguments(): void
    {
        $this->registerArgument('logEntry', LogEntry::class, 'Log entry instance to be rendered', true);
    }

    /**
     * Create formatted detail string from log row.
     *
     * The method handles two properties of the model: details and logData
     * Details is a string with possible %s placeholders, and logData an array
     * with the substitutions.
     * Furthermore, possible files in logData are stripped to their basename if
     * the action logged was a file action
     */
    public function render(): string
    {
        /** @var LogEntry $logEntry */
        $logEntry = $this->arguments['logEntry'];
        $detailString = $logEntry->getDetails();
        $substitutes = $logEntry->getLogData();
        // Strip paths from file names if the log was a file action
        if ($logEntry->getType() === 2) {
            $substitutes = self::stripPathFromFilenames($substitutes);
        }
        return self::formatLogDetailsStatic($detailString, $substitutes);
    }

    /**
     * Strips path from array of file names
     */
    protected static function stripPathFromFilenames(array $files = []): array
    {
        foreach ($files as $key => $file) {
            $files[$key] = PathUtility::basename((string)$file);
        }
        return $files;
    }
}
