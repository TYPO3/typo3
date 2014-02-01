<?php
namespace TYPO3\CMS\Core\Versioning;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Sascha Egerer <sascha.egerer@dkd.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Enumeration object for VersionState
 *
 * @package TYPO3\CMS\Core\Versioning
 */
class VersionState extends \TYPO3\CMS\Core\Type\Enumeration {

	const __default = self::DEFAULT_STATE;

	/**
	 * If a new record is created in a workspace a version
	 * with t3ver_state -1 is created with pid=-1. This
	 * record is the version of the "live" record
	 * (t3ver_state=1) where changes are stored.
	 */
	const NEW_PLACEHOLDER_VERSION = -1;

	/**
	 * The t3ver_state 0 is used for the live version
	 * of a record and for draft records with pid -1
	 */
	const DEFAULT_STATE = 0;

	/**
	 * Creating elements is done by first creating a
	 * placeholder element which is in fact live but
	 * carrying a flag (t3ver_state=1) that makes it
	 * invisible online.
	 */
	const NEW_PLACEHOLDER = 1;

	/**
	 * Deleting elements is done by actually creating a
	 * new version of the element and setting t3ver_state=2
	 * that indicates the live element must be deleted upon
	 * swapping the versions.
	 */
	const DELETE_PLACEHOLDER = 2;

	/**
	 * Moving elements is done by first creating a placeholder
	 * element which is in fact live but carrying a flag
	 * (t3ver_state=3) that makes it invisible online.
	 * It also has a field, "t3ver_move_id", holding the
	 * uid of the record to move (source record).
	 * In addition, a new version of the source record is made
	 * and has "t3ver_state" = 4 (move-to pointer). This version
	 * is simply necessary in order for the versioning system to
	 * have something to publish for the move operation. So in
	 * summary, two records are created for a move operation in
	 * a workspace: The placeholder (online, with state=3 and
	 * t3ver_move_id set) and a new version (state=4) of the
	 * online source record (the one being moved).
	 */
	const MOVE_PLACEHOLDER = 3;
	const MOVE_POINTER = 4;

	/**
	 * @return boolean
	 */
	public function indicatesPlaceholder() {
		return (int)$this->__toString() > self::DEFAULT_STATE;
	}
}
