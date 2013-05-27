<?php
namespace TYPO3\CMS\Extbase\Mvc\Exception;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * An "Ambiguous command identifier" exception
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AmbiguousCommandIdentifierException extends \TYPO3\CMS\Extbase\Mvc\Exception\CommandException {

	/**
	 * @var array<\TYPO3\CMS\Extbase\Mvc\Cli\Command>
	 */
	protected $matchingCommands = array();

	/**
	 * Overwrites parent constructor to be able to inject matching commands.
	 *
	 * @param string $message
	 * @param integer $code
	 * @param \Exception|NULL $previousException
	 * @param array $matchingCommands <\TYPO3\CMS\Extbase\Mvc\Cli\Command> $matchingCommands Commands that matched the command identifier
	 * @see Exception
	 */
	public function __construct($message = '', $code = 0, \Exception $previousException = NULL, array $matchingCommands) {
		$this->matchingCommands = $matchingCommands;
		parent::__construct($message, $code, $previousException);
	}

	/**
	 * @return array<\TYPO3\CMS\Extbase\Mvc\Cli\Command>
	 */
	public function getMatchingCommands() {
		return $this->matchingCommands;
	}
}

?>