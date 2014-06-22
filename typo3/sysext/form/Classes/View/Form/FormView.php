<?php
namespace TYPO3\CMS\Form\View\Form;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
