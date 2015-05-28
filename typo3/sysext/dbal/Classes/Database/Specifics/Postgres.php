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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains the specifics for PostgreSQL DBMS.
 * Any logic is in AbstractSpecifics.
 */
class Postgres extends AbstractSpecifics {
	/**
	 * Contains the specifics that need to be taken care of for PostgreSQL DBMS.
	 *
	 * @var array
	 */
	protected $specificProperties = array(
		self::CAST_FIND_IN_SET => TRUE
	);

}
