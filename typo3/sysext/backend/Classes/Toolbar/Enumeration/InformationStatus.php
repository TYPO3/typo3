<?php
namespace TYPO3\CMS\Backend\Toolbar\Enumeration;

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

use TYPO3\CMS\Core\Type\Enumeration;

/**
 * This class holds the severities of the SystemInformation toolbar menu
 */
final class InformationStatus extends Enumeration
{
    const __default = self::STATUS_INFO;

    /**
     * @var string
     */
    const STATUS_NOTICE = '';

    /**
     * @var string
     */
    const STATUS_INFO = 'info';

    /**
     * @var string
     */
    const STATUS_OK = 'success';

    /**
     * @var string
     */
    const STATUS_WARNING = 'warning';

    /**
     * @var string
     */
    const STATUS_ERROR = 'danger';

    /**
     * @var int[]
     */
    protected static $statusIntegerMap = [
        self::STATUS_NOTICE => -2,
        self::STATUS_INFO => -1,
        self::STATUS_OK => 0,
        self::STATUS_WARNING => 1,
        self::STATUS_ERROR => 2
    ];

    /**
     * Check if the given status is greater than this status instance
     *
     * @param InformationStatus $status
     * @return bool
     */
    public function isGreaterThan(InformationStatus $status)
    {
        return static::$statusIntegerMap[(string)$this] > static::$statusIntegerMap[(string)$status];
    }
}
