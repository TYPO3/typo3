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
 * @version $Id: UploadViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 */

/**
 * A view helper which generates an <input type="file"> HTML element.
 * Make sure to set enctype="multipart/form-data" on the form!
 * 
 * = Examples =
 * 
 * <code title="Example">
 * <f:upload name="file" />
 * </code>
 * 
 * Output:
 * <input type="file" name="file" />
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: UploadViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Form_UploadViewHelper extends Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper {

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
	 * Renders the upload field.
	 *
	 * @return string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render() {
		$this->tag->addAttribute('type', 'file');
		$this->tag->addAttribute('name', $this->getName());

		return $this->tag->render();
	}
}


?>
