<?php
namespace TYPO3\CMS\Beuser\Domain\Model;

/**
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
