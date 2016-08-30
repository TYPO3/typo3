<?php
namespace TYPO3\CMS\Core\Resource;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Enumeration object for DuplicationBehavior
 */
class DuplicationBehavior extends \TYPO3\CMS\Core\Type\Enumeration
{
    const __default = self::CANCEL;

    /**
     * If a file is uploaded and another file with
     * the same name already exists, the new file
     * is renamed.
     */
    const RENAME = 'rename';

    /**
     * If a file is uploaded and another file with
     * the same name already exists, the old file
     * gets overwritten by the new file.
     */
    const REPLACE = 'replace';

    /**
     * If a file is uploaded and another file with
     * the same name already exists, the process is
     * aborted.
     */
    const CANCEL = 'cancel';

    /**
     * Mapping of some legacy values, to assure BC
     *
     * @var string[]
     * @deprecated
     */
    protected static $legacyValueMap = [
        '1' => self::REPLACE,
        'overrideExistingFile' => self::REPLACE,
        'renameNewFile' => self::RENAME,
        'changeName' => self::RENAME
    ];

    /**
     * @param mixed $value
     */
    public function __construct($value = null)
    {
        if (isset(static::$legacyValueMap[$value])) {
            GeneralUtility::deprecationLog('Using ' . $value . ' for resolving conflicts in file names is deprecated. Make use of the enumeration "\TYPO3\CMS\Core\Resource\DuplicationBehavior" instead.');
            $value = static::$legacyValueMap[$value];
        }
        parent::__construct($value);
    }
}
