<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Extbase Team
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * An "Ambiguous command identifier" exception
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_MVC_Exception_AmbiguousCommandIdentifier extends Tx_Extbase_MVC_Exception_Command {

	/**
	 * @var array<Tx_Extbase_MVC_CLI_Command>
	 */
	protected $matchingCommands = array();

	/**
	 * Overwrites parent constructor to be able to inject matching commands.
	 *
	 * @param string $message
	 * @param integer $code
	 * @param Exception $previousException
	 * @param array<Tx_Extbase_MVC_CLI_Command> $matchingCommands Commands that matched the command identifier
	 * @see Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function __construct($message = '', $code = 0, Exception $previousException = NULL, array $matchingCommands) {
		$this->matchingCommands = $matchingCommands;
		parent::__construct($message, $code, $previousException);
	}

	/**
	 * @return array<Tx_Extbase_MVC_CLI_Command>
	 */
	public function getMatchingCommands() {
		return $this->matchingCommands;
	}

}
?>