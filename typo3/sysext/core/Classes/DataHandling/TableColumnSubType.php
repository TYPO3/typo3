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
 * Enumeration object for tca internal type
 */
class TableColumnSubType extends \TYPO3\CMS\Core\Type\Enumeration
{
    const __default = self::DEFAULT_TYPE;

    /**
     * Constants reflecting the table column sub type
     */
    const DEFAULT_TYPE = '';

    const DB = 'DB';
    const FILE = 'FILE';
    const FILE_REFERENCE = 'FILE_REFERENCE';
    const FOLDER = 'FOLDER';

    /**
     * @param mixed $subType
     */
    public function __construct($subType = null)
    {
        if ($subType !== null) {
            $subType = strtoupper((string)$subType);
        }

        parent::__construct($subType);
    }
}
