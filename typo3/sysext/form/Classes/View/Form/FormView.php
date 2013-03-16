<?php
namespace TYPO3\CMS\Form\View\Form;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Main view layer for Forms.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class FormView extends \TYPO3\CMS\Form\View\Form\Element\ContainerElementView {

	/**
	 * @var string
	 */
	protected $expectedModelName = 'TYPO3\\CMS\\Form\\Domain\\Model\\Form';

	/**
	 * Default layout of this object
	 *
	 * @var string
	 */
	protected $layout = '
		<form>
			<containerWrap />
		</form>';

	/**
	 * Set the data for the FORM tag
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Form $formModel The model of the form
	 * @return void
	 */
	public function setData(\TYPO3\CMS\Form\Domain\Model\Form $model) {
		$this->model = (object) $model;
	}

	/**
	 * Start the main DOMdocument for the form
	 * Return it as a string using saveXML() to get a proper formatted output
	 * (when using formatOutput :-)
	 *
	 * @return string XHTML string containing the whole form
	 */
	public function get() {
		$node = $this->render('element', FALSE);
		$content = chr(10) . html_entity_decode($node->saveXML($node->firstChild), ENT_QUOTES, 'UTF-8') . chr(10);
		return $content;
	}

}

?>