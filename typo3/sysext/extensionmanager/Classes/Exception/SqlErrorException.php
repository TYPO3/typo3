<?php
namespace TYPO3\CMS\Extensionmanager\Exception;

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
 * An exception when an SQL error in ext_tables.sql or ext_tables_static+adt.sql occurred
 * @internal Only internal usage in TYPO3 v7, not in TYPO3 v8 and up.
 */
class SqlErrorException extends ExtensionManagerException
{
}
