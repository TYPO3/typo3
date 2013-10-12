<?php
namespace TYPO3\CMS\Core\DataHandling;
/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Sebastian Fischer <typo3@evoweb.de>
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
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Enumeration object for tca type
 *
 * @package TYPO3\CMS\Core
 */
class TableColumnType extends \TYPO3\CMS\Core\Type\Enumeration {
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

	/**
	 * @param mixed $type
	 */
	public function __construct($type = NULL) {
		if ($type !== NULL) {
			$type = strtoupper((string) $type);
		}

		parent::__construct($type);
	}
}