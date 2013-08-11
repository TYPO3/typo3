<?php
namespace TYPO3\CMS\Core\Locking\Exception;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Daniel Hürtgen <huertgen@rheinschafe.de>
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
 * Lock could not be acquired exception.
 *  Thrown if lock could not be acquired during acquiring.
 *
 * @author Daniel Hürtgen <huertgen@rheinschafe.de>
 */
class LockCouldNotBeAcquiredException extends \TYPO3\CMS\Core\Locking\Exception {

	/**
	 * Holds proposed retries.
	 *
	 * @var integer
	 */
	protected $proposedRetries;

	/**
	 * Holds actual retries.
	 *
	 * @var integer
	 */
	protected $actualRetries;

	/**
	 * Constructs lock could not be acquired exception.
	 *
	 * @param integer       $proposedRetries
	 * @param integer       $actualRetries
	 * @param \Exception    $previous
	 * @return \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredException
	 */
	public function __construct($proposedRetries, $actualRetries, \Exception $previous = NULL) {
		$this->proposedRetries = (int) $proposedRetries;
		$this->actualRetries = (int) $actualRetries;

		parent::__construct('', 1361640233, $previous);
	}

	/**
	 * Get proposed retries.
	 *
	 * @return integer
	 */
	public function getProposedRetries() {
		return $this->proposedRetries;
	}

	/**
	 * Get actual retries.
	 *
	 * @return integer
	 */
	public function getActualRetries() {
		return $this->actualRetries;
	}

}

?>