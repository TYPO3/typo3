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

namespace TYPO3\CMS\Backend\Toolbar\Enumeration;

use TYPO3\CMS\Core\Type\Enumeration;

/**
 * This class holds the severities of the SystemInformation toolbar menu
 * @deprecated will be removed in TYPO3 v14.0
 */
final class InformationStatus extends Enumeration
{
    public const __default = self::STATUS_INFO;

    /**
     * @var string
     */
    public const STATUS_NOTICE = '';

    /**
     * @var string
     */
    public const STATUS_INFO = 'info';

    /**
     * @var string
     */
    public const STATUS_OK = 'success';

    /**
     * @var string
     */
    public const STATUS_WARNING = 'warning';

    /**
     * @var string
     */
    public const STATUS_ERROR = 'danger';

    /**
     * @var int[]
     */
    protected static $statusIntegerMap = [
        self::STATUS_NOTICE => -2,
        self::STATUS_INFO => -1,
        self::STATUS_OK => 0,
        self::STATUS_WARNING => 1,
        self::STATUS_ERROR => 2,
    ];

    /**
     * Check if the given status is greater than this status instance
     *
     * @return bool
     */
    public function isGreaterThan(InformationStatus $status)
    {
        trigger_error(
            'Calling ' . __METHOD__ . ' using the non-native enumeration ' . __CLASS__ . ' has been deprecated '
            . 'and will stop working in TYPO3 v14.0. Use the native ' . \TYPO3\CMS\Backend\Toolbar\InformationStatus::class . ' instead.',
            E_USER_DEPRECATED
        );
        return self::$statusIntegerMap[(string)$this] > self::$statusIntegerMap[(string)$status];
    }
}
