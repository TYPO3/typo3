<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * Evaluates to a literal value.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: Literal.php 2011 2009-03-18 14:22:24Z k-fish $
 * @scope prototype
 */
class Tx_Extbase_Persistence_QOM_Literal extends Tx_Extbase_Persistence_QOM_StaticOperand implements Tx_Extbase_Persistence_QOM_LiteralInterface {

	/**
	 * Constructs this Literal instance
	 *
	 * @param string $value
	 */
	public function __construct($value) {
		$this->value = $value;
	}

	/**
	 * Gets the value of the literal.
	 *
	 * @return string the literal value; non-null
	 */
	public function getLiteralValue() {
		return $this->value;
	}

}

?>