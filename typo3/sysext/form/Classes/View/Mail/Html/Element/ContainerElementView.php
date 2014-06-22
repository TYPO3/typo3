<?php
namespace TYPO3\CMS\Form\View\Mail\Html\Element;

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
 * Abstract class for the form element containers (FORM and FIELDSET) view
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ContainerElementView extends \TYPO3\CMS\Form\View\Mail\Html\Element\AbstractElementView {

	/**
	 * Default layout of the container
	 *
	 * @var string
	 */
	protected $containerWrap = '
		<tbody>
			<elements />
		</tbody>
	';

	/**
	 * Get the child objects
	 * and render them as document fragment
	 *
	 * @param DOMDocument $dom DOMDocument
	 * @return DOMDocumentFragment
	 */
	public function getChildElements(\DOMDocument $dom) {
		$modelChildren = $this->model->getElements();
		$documentFragment = NULL;
		foreach ($modelChildren as $key => $modelChild) {
			$child = $this->createChildElementFromModel($modelChild);
			if ($child) {
				if ($child->noWrap() === TRUE) {
					$childNode = $child->render();
				} else {
					$childNode = $child->render('elementWrap');
					if ($childNode) {
						$childNode->setAttribute('class', $child->getElementWraps());
					}
				}
				if ($childNode) {
					$importedNode = $dom->importNode($childNode, TRUE);
					if (!$documentFragment) {
						$documentFragment = $dom->createDocumentFragment();
					}
					$documentFragment->appendChild($importedNode);
				}
			}
		}
		return $documentFragment;
	}

	/**
	 * Create child object from the classname of the model
	 *
	 * @param object $modelChild The childs model
	 * @return \TYPO3\CMS\Form\View\Mail\Html\Element\AbstractElementView
	 */
	public function createChildElementFromModel($modelChild) {
		$childElement = NULL;
		$class = \TYPO3\CMS\Form\Utility\FormUtility::getInstance()->getLastPartOfClassName($modelChild);
		$className = 'TYPO3\\CMS\\Form\\View\\Mail\\Html\\Element\\' . ucfirst($class) . 'ElementView';
		if (class_exists($className)) {
			$childElement = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $modelChild);
		}
		return $childElement;
	}

}
