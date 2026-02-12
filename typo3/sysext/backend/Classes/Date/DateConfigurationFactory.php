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

namespace TYPO3\CMS\Backend\Date;

use TYPO3\CMS\Core\Localization\DateFormatter;

/**
 * @internal
 */
final class DateConfigurationFactory
{
    /**
     * Build date format configuration for JavaScript or PHP components
     *
     * Returns a DateConfiguration value object that is JSON serializable
     *
     * @param 'php'|'javascript' $context
     */
    public function getConfiguration(string $context = 'php'): DateConfiguration
    {
        $formatter = new DateFormatter();

        // Get PHP formats from TYPO3 configuration
        $phpDateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?? 'Y-m-d';
        $phpTimeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] ?? 'H:i';

        // Convert formats based on context
        if ($context === 'javascript') {
            $dateFormat = $formatter->convertPhpFormatToLuxon($phpDateFormat);
            $timeFormat = $formatter->convertPhpFormatToLuxon($phpTimeFormat);
        } else {
            $dateFormat = $phpDateFormat;
            $timeFormat = $phpTimeFormat;
        }

        return new DateConfiguration(
            timezone: date_default_timezone_get(),
            formats: new DateFormats(
                date: $dateFormat,
                datetime: $dateFormat . ' ' . $timeFormat,
            ),
        );
    }
}
