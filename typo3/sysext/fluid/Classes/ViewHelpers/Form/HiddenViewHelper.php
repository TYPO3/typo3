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
 * @version $Id: HiddenViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 */

/**
 * Renders an <input type="hidden" ...> tag.
 * 
 * = Examples =
 *
 * <code title="Example">
 * <f:hidden name="myHiddenValue" value="42" />
 * </code>
 * 
 * Output:
 * <input type="hidden" name="myHiddenValue" value="42" />
 * 
 * You can also use the "property" attribute if you have bound an object to the form.
 * See <f:form> for more documentation.
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: HiddenViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Form_HiddenViewHelper extends Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'input';

	/**
	 * Initialize the arguments.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Renders the hidden field.
	 *
	 * @return string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render() {
		$this->tag->addAttribute('type', 'hidden');
		$this->tag->addAttribute('name', $this->getName());
		$this->tag->addAttribute('value', $this->getValue());
		
		return $this->tag->render();
	}
}


?>
