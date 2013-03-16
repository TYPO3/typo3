<?php
namespace TYPO3\CMS\Form\View\Mail\Html\Element;

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
 * Abstract class for the form elements view
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
abstract class AbstractElementView {

	/**
	 * The model for the current object
	 *
	 * @var \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement
	 */
	protected $model;

	/**
	 * Wrap for elements
	 *
	 * @var string
	 */
	protected $elementWrap = '
		<tr>
			<element />
		</tr>
	';

	/**
	 * True if element needs no element wrap
	 * like <li>element</li>
	 *
	 * @var boolean
	 */
	protected $noWrap = FALSE;

	/**
	 * Constructor
	 *
	 * @param object $model Current elements model
	 */
	public function __construct($model) {
		$this->model = $model;
	}

	/**
	 * Parse the XML of a view object,
	 * check the node type and name
	 * and add the proper XML part of child tags
	 * to the \DOMDocument of the current tag
	 *
	 * @param \DOMDocument $dom
	 * @param \DOMNode $reference Current XML structure
	 * @param boolean $emptyElement
	 * @return boolean
	 */
	protected function parseXML(\DOMDocument $dom, \DOMNode $reference, $emptyElement = FALSE) {
		$node = $reference->firstChild;
		while (!is_null($node)) {
			$deleteNode = FALSE;
			$nodeType = $node->nodeType;
			$nodeName = $node->nodeName;
			switch ($nodeType) {
				case XML_TEXT_NODE:
					break;
				case XML_ELEMENT_NODE:
					switch ($nodeName) {
						case 'containerWrap':
							$containerWrap = $this->render('containerWrap');
							if ($containerWrap) {
								$this->replaceNodeWithFragment($dom, $node, $containerWrap);
							} else {
								$emptyElement = TRUE;
							}
							$deleteNode = TRUE;
							break;
						case 'elements':
							$replaceNode = $this->getChildElements($dom);
							if ($replaceNode) {
								$node->parentNode->replaceChild($replaceNode, $node);
							} else {
								$emptyElement = TRUE;
							}
							break;
						case 'label':
							if (!strrchr(get_class($this), 'AdditionalElement')) {
								if ($this->model->additionalIsSet($nodeName)) {
									$this->replaceNodeWithFragment($dom, $node, $this->getAdditional('label'));
								} else {
									$replaceNode = $dom->createTextNode($this->model->getName());
									$node->parentNode->insertBefore($replaceNode, $node);
								}
							}
							$deleteNode = TRUE;
							break;
						case 'legend':
							if (!strrchr(get_class($this), 'AdditionalElement')) {
								if ($this->model->additionalIsSet($nodeName)) {
									$this->replaceNodeWithFragment($dom, $node, $this->getAdditional('legend'));
								}
								$deleteNode = TRUE;
							}
							break;
						case 'inputvalue':
							if (array_key_exists('checked', $this->model->getAllowedAttributes())) {
								if (!$this->model->hasAttribute('checked')) {
									$emptyElement = TRUE;
								}
							} elseif (array_key_exists('selected', $this->model->getAllowedAttributes()) && !$this->model->hasAttribute('selected')) {
								$emptyElement = TRUE;
							} else {
								$inputValue = $this->getInputValue();
								if ($inputValue != '') {
									$replaceNode = $dom->createTextNode($this->getInputValue());
									$node->parentNode->insertBefore($replaceNode, $node);
								}
							}
							$deleteNode = TRUE;
							break;
						case 'labelvalue':

						case 'legendvalue':
							$replaceNode = $dom->createTextNode($this->getAdditionalValue());
							$node->parentNode->insertBefore($replaceNode, $node);
							$deleteNode = TRUE;
							break;
					}
					break;
			}
			// Parse the child nodes of this node if available
			if ($node->hasChildNodes()) {
				$emptyElement = $this->parseXML($dom, $node, $emptyElement);
			}
			// Get the current node for deletion if replaced. We need this because nextSibling can be empty
			$oldNode = $node;
			// Go to next sibling to parse
			$node = $node->nextSibling;
			// Delete the old node. This can only be done after going to the next sibling
			if ($deleteNode) {
				$oldNode->parentNode->removeChild($oldNode);
			}
		}
		return $emptyElement;
	}

	/**
	 * Get the content for the current object as \DOMDocument
	 *
	 * @param string $type Type of element for layout
	 * @param boolean $returnFirstChild If TRUE, the first child will be returned instead of the \DOMDocument
	 * @return \DOMDocument|\DOMNode XML part of the view object
	 */
	public function render($type = 'element', $returnFirstChild = TRUE) {
		$useLayout = $this->getLayout((string) $type);
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = TRUE;
		$dom->preserveWhiteSpace = FALSE;
		$dom->loadXML($useLayout);
		$emptyElement = $this->parseXML($dom, $dom);
		if ($emptyElement) {
			return NULL;
		} elseif ($returnFirstChild) {
			return $dom->firstChild;
		} else {
			return $dom;
		}
	}

	/**
	 * Ask the layoutHandler to get the layout for this object
	 *
	 * @param string $type Layout type
	 * @return string HTML string of the layout to use for this element
	 */
	public function getLayout($type) {
		/** @var $layoutHandler \TYPO3\CMS\Form\Layout */
		$layoutHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Layout');
		$layout = '';
		switch ($type) {
			case 'element':
				$layoutDefault = $this->layout;
				$layout = $layoutHandler->getLayoutByObject(\TYPO3\CMS\Form\Utility\FormUtility::getInstance()->getLastPartOfClassName($this, TRUE), $layoutDefault);
				break;
			case 'elementWrap':
				$layoutDefault = $this->elementWrap;
				$elementWrap = $layoutHandler->getLayoutByObject($type, $layoutDefault);
				$layout = str_replace('<element />', $this->getLayout('element'), $elementWrap);
				break;
			case 'containerWrap':
				$layoutDefault = $this->containerWrap;
				$layout = $layoutHandler->getLayoutByObject($type, $layoutDefault);
				break;
		}
		return $layout;
	}

	/**
	 * Replace the current node with a document fragment
	 *
	 * @param \DOMDocument $dom
	 * @param \DOMNode $node Current Node
	 * @param \DOMNode $value Value to import
	 * @return void
	 */
	public function replaceNodeWithFragment(\DOMDocument $dom, \DOMNode $node, \DOMNode $value) {
		$replaceNode = $dom->createDocumentFragment();
		$domNode = $dom->importNode($value, TRUE);
		$replaceNode->appendChild($domNode);
		$node->parentNode->insertBefore($replaceNode, $node);
	}

	/**
	 * Set the attributes on the html tags according to the attributes that are
	 * assigned in the model for a certain element
	 *
	 * @param \DOMElement $domElement DOM element of the specific HTML tag
	 * @return void
	 */
	public function setAttributes(\DOMElement $domElement) {
		$attributes = $this->model->getAttributes();
		foreach ($attributes as $key => $attribute) {
			if (!empty($attribute)) {
				$value = htmlspecialchars($attribute->getValue(), ENT_QUOTES);
				if (!empty($value)) {
					$domElement->setAttribute($key, $value);
				}
			}
		}
	}

	/**
	 * Set a single attribute of a HTML tag specified by key
	 *
	 * @param \DOMElement $domElement DOM element of the specific HTML tag
	 * @param string $key Attribute key
	 * @return void
	 */
	public function setAttribute(\DOMElement $domElement, $key) {
		$value = htmlspecialchars($this->model->getAttributeValue((string) $key), ENT_QUOTES);
		if (!empty($value)) {
			$domElement->setAttribute($key, $value);
		}
	}

	/**
	 * Sets the value of an attribute with the value of another attribute,
	 * for instance equalizing the name and id attributes for the form tag
	 *
	 * @param \DOMElement $domElement DOM element of the specific HTML tag
	 * @param string $key Key of the attribute which needs to be changed
	 * @param string $other Key of the attribute to take the value from
	 * @return void
	 */
	public function setAttributeWithValueofOtherAttribute(\DOMElement $domElement, $key, $other) {
		$value = htmlspecialchars($this->model->getAttributeValue((string) $other), ENT_QUOTES);
		if (!empty($value)) {
			$domElement->setAttribute($key, $value);
		}
	}

	/**
	 * Load and instantiate an additional object
	 *
	 * @param string $class Type of additional
	 * @return object
	 */
	protected function createAdditional($class) {
		$class = strtolower((string) $class);
		$className = 'TYPO3\\CMS\\Form\\View\\Mail\\Html\\Additional\\' . ucfirst($class) . 'AdditionalElementView';
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $this->model);
	}

	/**
	 * Create additional object by key and render the content
	 *
	 * @param string $key Type of additional
	 * @return \DOMNode
	 */
	public function getAdditional($key) {
		$additional = $this->createAdditional($key);
		return $additional->render();
	}

	/**
	 * @return string
	 */
	public function getInputValue() {
		if (method_exists($this->model, 'getData')) {
			$inputValue = $this->model->getData();
		} else {
			$inputValue = $this->model->getAttributeValue('value');
		}

		$inputValue = htmlspecialchars($inputValue, ENT_QUOTES);
		$inputValue = nl2br($inputValue, TRUE);
		return $inputValue;
	}

	/**
	 * Return the id for the element wraps,
	 * like <li id="csc-form-"> ... </li>
	 *
	 * @return string
	 */
	public function getElementWrapId() {
		$elementId = (int) $this->model->getElementId();
		$wrapId = 'csc-form-' . $elementId;
		return $wrapId;
	}

	/**
	 * Returns the type for the element wraps,
	 * like <li class="csc-form-element csc-form-element-abstract">...</li>
	 *
	 * @return string
	 */
	public function getElementWrapType() {
		$elementType = strtolower(\TYPO3\CMS\Form\Utility\FormUtility::getInstance()->getLastPartOfClassName($this->model));
		$wrapType = 'csc-form-element csc-form-element-' . $elementType;
		return $wrapType;
	}

	/**
	 * Returns all element wraps.
	 *
	 * @return string
	 */
	public function getElementWraps() {
		$wraps = array(
			$this->getElementWrapId(),
			$this->getElementWrapType()
		);
		return implode(' ', $wraps);
	}

	/**
	 * Read the noWrap value of an element
	 * if TRUE the element does not need a element wrap
	 * like <li>element</li>
	 *
	 * @return boolean
	 */
	public function noWrap() {
		return $this->noWrap;
	}

}

?>