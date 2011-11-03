<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
 * Abstract class for the form element containers (FORM and FIELDSET) view
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_View_Form_Element_Container extends tx_form_View_Form_Element_Abstract {
	/**
	 * Default layout of the container
	 *
	 * @var string
	 */
	protected $containerWrap = '
		<ol>
			<elements />
		</ol>
	';

	/**
	 * Get the child objects
	 * and render them as document fragment
	 *
	 * @param DOMDocument $dom DOMDocument
	 * @return DOMDocumentFragment
	 */
	public function getChildElements(DOMDocument $dom) {
		$modelChildren = $this->model->getElements();
		$documentFragment = $dom->createDocumentFragment();
		foreach ($modelChildren as $key => $modelChild) {
			$child = $this->createChildElementFromModel($modelChild);
			if ($child->noWrap() === TRUE) {
				$childNode = $child->render();
			} else {
				$childNode = $child->render('elementWrap');
				$childNode->setAttribute('class', $child->getElementWraps());
			}
			$importedNode = $dom->importNode($childNode, TRUE);
			$documentFragment->appendChild($importedNode);
		}
		return $documentFragment;
	}

	/**
	 * Create child object from the classname of the model
	 *
	 * @param object $modelChild The childs model
	 * @return tx_form_View_Form_Element_Abstract
	 */
	public function createChildElementFromModel($modelChild) {
		$childElement = NULL;

		$class = tx_form_Common::getInstance()->getLastPartOfClassName($modelChild);
		$className = 'tx_form_View_Form_Element_' . ucfirst($class);

		if (class_exists($className)) {
			$childElement = t3lib_div::makeInstance($className, $modelChild);
		}

		return $childElement;
	}
}
?>