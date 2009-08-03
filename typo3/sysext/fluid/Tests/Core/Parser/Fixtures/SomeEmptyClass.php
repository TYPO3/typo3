<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @version $Id: SomeEmptyClass.php 2813 2009-07-16 14:02:34Z k-fish $
 */
/**
 * Example class
 *
 * @version $Id: SomeEmptyClass.php 2813 2009-07-16 14:02:34Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @author Sebastian Kurfürst <sebastian@typo3.org>
 */
class Tx_Fluid_Core_Parser_Fixtures_SomeEmptyClass {
	public $publicVariable = "Hallo";
	protected $protectedVariable;
	
	public function __construct($protectedVariable) {
		$this->protectedVariable = $protectedVariable;
	}
	
	public function getSubproperty() {
		return $this->protectedVariable;
	}
}


?>
