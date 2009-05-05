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
 * @version $Id: SubmitViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 */

/**
 * Creates a submit button.
 * 
 * = Examples =
 * 
 * <code title="Defaults">
 * <f:submit value="Send Mail" />
 * </code>
 * 
 * Output:
 * <input type="submit" />
 *
 * <code title="Dummy content for template preview">
 * <f:submit name="mySubmit" value="Send Mail"><button>dummy button</button></f:submit>
 * </code>
 * 
  * Output:
 * <input type="submit" name="mySubmit" value="Send Mail" />
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: SubmitViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Form_SubmitViewHelper extends Tx_Fluid_Core_TagBasedViewHelper {

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
	 * Renders the submit button.
	 *
	 * @param string name Name of submit tag
	 * @param string value Value of submit tag
	 * @return string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($name = '', $value = '') {
		$this->tag->addAttribute('type', 'submit');
		if ($name !== '') {
			$this->tag->addAttribute('name', $name);
		}
		if ($value !== '') {
			$this->tag->addAttribute('value', $value);
		}

		return $this->tag->render();
	}
}



?>
