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

namespace TYPO3\CMS\Core\DataHandling;

use TYPO3\CMS\Core\Type\Enumeration;

/**
 * Enumeration object for tca type
 */
final class TableColumnType extends Enumeration
{
    public const __default = self::INPUT;

    /**
     * Constants reflecting the table column type
     */
    public const INPUT = 'INPUT';
    public const TEXT = 'TEXT';
    public const CHECK = 'CHECK';
    public const RADIO = 'RADIO';
    public const SELECT = 'SELECT';
    public const GROUP = 'GROUP';
    public const NONE = 'NONE';
    public const LANGUAGE = 'LANGUAGE';
    public const PASSTHROUGH = 'PASSTHROUGH';
    public const USER = 'USER';
    public const FLEX = 'FLEX';
    public const INLINE = 'INLINE';
    public const IMAGEMANIPULATION = 'IMAGEMANIPULATION';
    public const SLUG = 'SLUG';
    public const CATEGORY = 'CATEGORY';

    /**
     * @param mixed $type
     */
    public function __construct($type = null)
    {
        if ($type !== null) {
            $type = strtoupper((string)$type);
        }

        parent::__construct($type);
    }
}
