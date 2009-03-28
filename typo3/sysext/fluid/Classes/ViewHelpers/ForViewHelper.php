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
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: ForViewHelper.php 1962 2009-03-03 12:10:41Z k-fish $
 */

/**
 * Loop view helper
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: ForViewHelper.php 1962 2009-03-03 12:10:41Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_ForViewHelper extends Tx_Fluid_Core_AbstractViewHelper {

	/**
	 * Arguments initialization
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
		//$this->registerArgument('each', 'array', 'The array which is iterated over.', TRUE);
		//$this->registerArgument('as', 'string', 'Name of the variable where each array element is bound to.', TRUE);
	}

	/**
	 * Render.
	 *
	 * @param array $each The array to be iterated over
	 * @param string $as The name of the iteration variable
	 * @param string $key The name of the variable to store the current array key
	 * @return string Rendered string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render($each, $as, $key = '') {
		$out = '';
		if (!empty($each)) {
			foreach ($this->arguments['each'] as $keyValue => $singleElement) {
				$this->variableContainer->add($this->arguments['as'], $singleElement);
				if (strlen($key)) {
					$this->variableContainer->add($this->arguments['key'], $keyValue);
				}
				$out .= $this->renderChildren();
				$this->variableContainer->remove($this->arguments['as']);
				if (strlen($key)) {
					$this->variableContainer->remove($this->arguments['key']);
				}
			}			
		}
		return $out;
	}
}

?>
