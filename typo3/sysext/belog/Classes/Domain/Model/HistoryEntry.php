<?php
namespace TYPO3\CMS\Belog\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Stub model for sys history - only properties required for belog module are added currently
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class HistoryEntry extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * List of changed fields
	 *
	 * @var string
	 */
	protected $fieldlist = '';

	/**
	 * Set list of changed fields
	 *
	 * @param string $fieldlist
	 * @return void
	 */
	public function setFieldlist($fieldlist) {
		// TODO think about exploding this to an array
		$this->fieldlist = $fieldlist;
	}

	/**
	 * Get field list
	 *
	 * @return string
	 */
	public function getFieldlist() {
		return $this->fieldlist;
	}

}

?>