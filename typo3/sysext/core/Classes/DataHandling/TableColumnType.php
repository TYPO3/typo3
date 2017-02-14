<?php
namespace TYPO3\CMS\Core\DataHandling;

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
 * Enumeration object for tca type
 */
class TableColumnType extends \TYPO3\CMS\Core\Type\Enumeration
{
    const __default = self::INPUT;

    /**
     * Constants reflecting the table column type
     */
    const INPUT = 'INPUT';
    const TEXT = 'TEXT';
    const CHECK = 'CHECK';
    const RADIO = 'RADIO';
    const SELECT = 'SELECT';
    const GROUP = 'GROUP';
    const NONE = 'NONE';
    const PASSTHROUGH = 'PASSTHROUGH';
    const USER = 'USER';
    const FLEX = 'FLEX';
    const INLINE = 'INLINE';
    const IMAGEMANIPULATION = 'IMAGEMANIPULATION';

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
