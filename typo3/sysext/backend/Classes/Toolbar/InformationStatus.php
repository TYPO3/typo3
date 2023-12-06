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

namespace TYPO3\CMS\Backend\Toolbar;

/**
 * Enum with the severities of the SystemInformation toolbar menu
 */
enum InformationStatus: string
{
    case NOTICE = '';
    case INFO = 'info';
    case OK = 'success';
    case WARNING = 'warning';
    case ERROR = 'danger';

    /**
     * Check if the given status is greater than this status instance
     */
    public function isGreaterThan(InformationStatus $status): bool
    {
        return $this->getOrderRepresentation($this) > $this->getOrderRepresentation($status);
    }

    private function getOrderRepresentation(InformationStatus $status): int
    {
        return match ($status) {
            self::NOTICE => -2,
            self::INFO => -1,
            self::OK => 0,
            self::WARNING => 1,
            self::ERROR => 2,
        };
    }
}
