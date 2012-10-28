<?php
namespace TYPO3\CMS\Beuser\ViewHelpers\Form;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Felix Kopp <felix-source@phorax.com>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Extends Tx_Fluid_ViewHelpers_Form_SelectViewHelper with empty default value in select input field
 *
 * @author Felix Kopp <felix-source@phorax.com>
 */
class SelectDefaultValueViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper {

	/**
	 * @return void
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('defaultOption', 'string', 'value to prepend', FALSE);
	}

	/**
	 * @return array
	 */
	protected function getOptions() {
		return array('' => $this->arguments['defaultOption']) + parent::getOptions();
	}

}

?>