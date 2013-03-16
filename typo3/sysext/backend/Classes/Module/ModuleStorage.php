<?php
namespace TYPO3\CMS\Backend\Module;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog <typo3@susannemoog.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Model for the module storage
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class ModuleStorage implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \SplObjectStorage
	 */
	protected $entries;

	/**
	 * construct
	 */
	public function __construct() {
		$this->entries = new \SplObjectStorage();
	}

	/**
	 * Set Entries
	 *
	 * @param \SplObjectStorage $entries
	 * @return void
	 */
	public function setEntries($entries) {
		$this->entries = $entries;
	}

	/**
	 * Get Entries
	 *
	 * @return \SplObjectStorage
	 */
	public function getEntries() {
		return $this->entries;
	}

	/**
	 * Attach Entry
	 *
	 * @param \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $entry
	 * @return void
	 */
	public function attachEntry(\TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $entry) {
		$this->entries->attach($entry);
	}

}


?>