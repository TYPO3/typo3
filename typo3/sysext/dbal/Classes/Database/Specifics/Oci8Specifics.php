<?php
namespace TYPO3\CMS\Dbal\Database\Specifics;

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
 * This class contains the specifics for Oracle DBMS.
 * Any logic is in AbstractSpecifics.
 */
class Oci8Specifics extends AbstractSpecifics
{
    /**
     * Contains the specifics that need to be taken care of for Oracle DBMS.
     *
     * @var array
     */
    protected $specificProperties = [
        self::TABLE_MAXLENGTH => 30,
        self::FIELD_MAXLENGTH => 30,
        self::LIST_MAXEXPRESSIONS => 1000
    ];
}
