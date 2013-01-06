<?php
namespace TYPO3\CMS\Extbase\Mvc\Cli;

/***************************************************************
 *  Copyright notice
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A CLI specific response implementation
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Response extends \TYPO3\CMS\Extbase\Mvc\Response {

	/**
	 * @var integer
	 */
	private $exitCode = 0;

	/**
	 * Sets the numerical exit code which should be returned when exiting this application.
	 *
	 * @param integer $exitCode
	 * @throws \InvalidArgumentException
	 * @return void
	 * @api
	 */
	public function setExitCode($exitCode) {
		if (!is_integer($exitCode)) {
			throw new \InvalidArgumentException(sprintf('Tried to set invalid exit code. The value must be integer, %s given.', gettype($exitCode)), 1312222064);
		}
		$this->exitCode = $exitCode;
	}

	/**
	 * Rets the numerical exit code which should be returned when exiting this application.
	 *
	 * @return integer
	 * @api
	 */
	public function getExitCode() {
		return $this->exitCode;
	}

	/**
	 * Renders and sends the whole web response
	 *
	 * @return void
	 * @api
	 */
	public function send() {
		if ($this->content !== NULL) {
			echo $this->shutdown();
		}
	}
}

?>