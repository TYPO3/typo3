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

namespace TYPO3\CMS\Core\Versioning;

use TYPO3\CMS\Core\Type\Enumeration;

/**
 * Enumeration object for VersionState
 */
final class VersionState extends Enumeration
{
    const __default = self::DEFAULT_STATE;

    /**
     * This record was used until TYPO3 v11, but is not in use anymore.
     * If a new record is created in a workspace a version
     * with t3ver_state -1 is created. This
     * record is the version of the "live" record
     * (t3ver_state=1) where changes are stored, the so-called
     * "versioned record" for new elements.
     *
     * @deprecated this constant is not in use anymore and should be removed from any third-party code
     */
    const NEW_PLACEHOLDER_VERSION = -1;

    /**
     * The t3ver_state 0 is used for a live element, and any
     * commonly "modified" versioned record which is then identified
     * with t3ver_oid=uid of live ID
     */
    const DEFAULT_STATE = 0;

    /**
     * If a new record is created in a workspace a new
     * record is added with t3ver_state = 1, a so-called
     * "newly versioned record", which acts as a standalone
     * record and has no t3ver_oid value. Publishing this record
     * is done by changing the t3ver_wsid field to "0".
     */
    const NEW_PLACEHOLDER = 1;

    /**
     * Deleting elements is done by actually creating a
     * new version of the element and setting t3ver_state=2
     * that indicates the live element must be deleted upon
     * publishing the versions.
     */
    const DELETE_PLACEHOLDER = 2;

    /**
     * When an element is moved to a different page, a versioned
     * record is created with t3ver_state=4 and the new PID.
     * When the database table has a sorting field, the sorting
     * on the versioned record is also updated to reflect the new position.
     *
     * When reading records from the DB with workspaces in mind,
     * the t3ver_state=4 records should be fetched as well to
     * find the new position and to do "workspace overlays" properly.
     *
     * Move placeholders (t3ver_state=3) is not in use anymore, never
     * created and not evaluated anymore since TYPO3 v11.
     */
    /** @deprecated this constant is not in use anymore and should be removed from any third-party code */
    const MOVE_PLACEHOLDER = 3;
    const MOVE_POINTER = 4;

    /**
     * @return bool
     */
    public function indicatesPlaceholder()
    {
        return (int)$this->__toString() > self::NEW_PLACEHOLDER;
    }
}
