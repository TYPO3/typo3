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
}
