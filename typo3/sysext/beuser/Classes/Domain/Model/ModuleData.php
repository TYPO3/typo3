<?php
namespace TYPO3\CMS\Beuser\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Felix Kopp <felix-source@phorax.com>
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
 * Module data object
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @author Nikolas Hagelstein <nikolas.hagelstein@gmail.com>
 */
class ModuleData {

	/**
	 * @var \TYPO3\CMS\Beuser\Domain\Model\Demand
	 * @inject
	 */
	protected $demand;

	/**
	 * @var array
	 */
	protected $compareUserList = array();

	/**
	 * @return \TYPO3\CMS\Beuser\Domain\Model\Demand
	 */
	public function getDemand() {
		return $this->demand;
	}

	/**
	 * @param \TYPO3\CMS\Beuser\Domain\Model\Demand $demand
	 * @return void
	 */
	public function setDemand(\TYPO3\CMS\Beuser\Domain\Model\Demand $demand) {
		$this->demand = $demand;
	}

	/**
	 * Returns the compare list as array of user uis
	 *
	 * @return array
	 */
	public function getCompareUserList() {
		return array_keys($this->compareUserList);
	}

	/**
	 * Adds one backend user (by uid) to the compare user list
	 * Cannot be ObjectStorage, must be array
	 *
	 * @param integer $uid
	 * @return void
	 */
	public function attachUidCompareUser($uid) {
		$this->compareUserList[$uid] = TRUE;
	}

	/**
	 * Strip one backend user from the compare user list
	 *
	 * @param integer $uid
	 * @return void
	 */
	public function detachUidCompareUser($uid) {
		unset($this->compareUserList[$uid]);
	}

}

?>