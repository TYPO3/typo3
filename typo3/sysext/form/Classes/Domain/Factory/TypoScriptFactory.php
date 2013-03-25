<?php
namespace TYPO3\CMS\Form\Domain\Factory;

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
 * Typoscript factory for form
 *
 * Takes the incoming Typoscipt and adds all the necessary form objects
 * according to the configuration.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class TypoScriptFactory implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var string
	 */
	const PROPERTY_DisableContentElement = 'disableContentElement';

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $localContentObject;

	/**
	 * @var boolean
	 */
	protected $disableContentElement = FALSE;

	/**
	 * Build model from Typoscript
	 *
	 * @param array $typoscript Typoscript containing all configuration
	 * @return \TYPO3\CMS\Form\Domain\Model\Form The form object containing the child elements
	 */
	public function buildModelFromTyposcript(array $typoscript) {
		if (isset($typoscript[self::PROPERTY_DisableContentElement])) {
			$this->setDisableContentElement($typoscript[self::PROPERTY_DisableContentElement]);
		}
		$this->setLayoutHandler($typoscript);
		$form = $this->createElement('form', $typoscript);
		return $form;
	}

	/**
	 * Disables the content element.
	 *
	 * @param boolean $disableContentElement
	 * @return void
	 */
	public function setDisableContentElement($disableContentElement) {
		$this->disableContentElement = (bool) $disableContentElement;
	}

	/**
	 * Rendering of a "numerical array" of Form objects from TypoScript
	 * Creates new object for each element found
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $parentElement Parent model object
	 * @param array $arguments Configuration array
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	public function getChildElementsByIntegerKey(\TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $parentElement, array $typoscript) {
		if (is_array($typoscript)) {
			$keys = \TYPO3\CMS\Core\TypoScript\TemplateService::sortedKeyList($typoscript);
			foreach ($keys as $key) {
				$class = $typoscript[$key];
				if (intval($key) && !strstr($key, '.')) {
					if (isset($typoscript[$key . '.'])) {
						$elementArguments = $typoscript[$key . '.'];
					} else {
						$elementArguments = array();
					}
					$this->setElementType($parentElement, $class, $elementArguments);
				}
			}
		} else {
			throw new \InvalidArgumentException('Container element with id=' . $parentElement->getElementId() . ' has no configuration which means no children.', 1333754854);
		}
	}

	/**
	 * Create and add element by type.
	 * This can be a derived Typoscript object by "<",
	 * a form element, or a regular Typoscript object.
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $parentElement The parent for the new element
	 * @param string $class Classname for the element
	 * @param array $arguments Configuration array
	 * @return void
	 */
	public function setElementType(\TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $parentElement, $class, array $arguments) {
		if (in_array($class, \TYPO3\CMS\Form\Utility\FormUtility::getInstance()->getFormObjects())) {
			$this->addElement($parentElement, $class, $arguments);
		} elseif ($this->disableContentElement === FALSE) {
			if (substr($class, 0, 1) == '<') {
				$key = trim(substr($class, 1));
				/** @var $typoscriptParser \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser */
				$typoscriptParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
				$oldArguments = $arguments;
				list($class, $arguments) = $typoscriptParser->getVal($key, $GLOBALS['TSFE']->tmpl->setup);
				if (is_array($oldArguments) && count($oldArguments)) {
					$arguments = $this->getLocalConentObject()->joinTSarrays($arguments, $oldArguments);
				}
				$GLOBALS['TT']->incStackPointer();
				$contentObject = array(
					'cObj' => $class,
					'cObj.' => $arguments
				);
				$this->addElement($parentElement, 'content', $contentObject);
				$GLOBALS['TT']->decStackPointer();
			} else {
				$contentObject = array(
					'cObj' => $class,
					'cObj.' => $arguments
				);
				$this->addElement($parentElement, 'content', $contentObject);
			}
		}
	}

	/**
	 * Add child object to this element
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $parentElement Parent model object
	 * @param string $class Type of element
	 * @param array $arguments Configuration array
	 * @return object
	 */
	public function addElement(\TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $parentElement, $class, array $arguments = array()) {
		$element = $this->createElement($class, $arguments);
		$parentElement->addElement($element);
	}

	/**
	 * Create element by loading class
	 * and instantiating the object
	 *
	 * @param string $class Type of element
	 * @param array $arguments Configuration array
	 * @return \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement
	 */
	public function createElement($class, array $arguments = array()) {
		$class = strtolower((string) $class);
		if ($class === 'form') {
			$className = 'TYPO3\\CMS\\Form\\Domain\\Model\\' . ucfirst($class);
		} else {
			$className = 'TYPO3\\CMS\\Form\\Domain\\Model\\Element\\' . ucfirst($class) . 'Element';
		}
		/** @var $object \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement */
		$object = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
		if ($object->getElementType() === \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement::ELEMENT_TYPE_CONTENT) {
			$object->setData($arguments['cObj'], $arguments['cObj.']);
		} elseif ($object->getElementType() === \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement::ELEMENT_TYPE_PLAIN) {
			$object->setProperties($arguments);
		} elseif ($object->getElementType() === \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement::ELEMENT_TYPE_FORM) {
			$object->setData($arguments['data']);
			$this->reconstituteElement($object, $arguments);
		} else {
			throw new \InvalidArgumentException('Element type "' . $object->getElementType() . '" is not supported.', 1333754878);
		}
		return $object;
	}

	/**
	 * Reconstitutes the domain model of the accordant element.
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element
	 * @param array $arguments Configuration array
	 * @return void
	 */
	protected function reconstituteElement(\TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element, array $arguments = array()) {
		if (isset($arguments['value.'])) {
			$cObj = $this->getLocalConentObject();
			$arguments['value'] = $cObj->stdWrap($arguments['value'], $arguments['value.']);
		}

		$this->setAttributes($element, $arguments);
		$this->setAdditionals($element, $arguments);
		if (isset($arguments['filters.'])) {
			$this->setFilters($element, $arguments['filters.']);
		}
		$element->setLayout($arguments['layout']);
		$element->setValue($arguments['value']);
		$element->setName($arguments['name']);
		$element->setMessagesFromValidation();
		$element->setErrorsFromValidation();
		$element->checkFilterAndSetIncomingDataFromRequest();
		$this->getChildElementsByIntegerKey($element, $arguments);
	}

	/**
	 * Set the attributes
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element Model object
	 * @param array $arguments Arguments
	 * @return void
	 */
	public function setAttributes(\TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element, array $arguments) {
		if ($element->hasAllowedAttributes()) {
			$attributes = $element->getAllowedAttributes();
			$mandatoryAttributes = $element->getMandatoryAttributes();
			foreach ($attributes as $attribute => $value) {
				if (isset($arguments[$attribute]) || isset($arguments[$attribute . '.']) || in_array($attribute, $mandatoryAttributes) || !empty($value)) {
					if (!empty($arguments[$attribute])) {
						$value = $arguments[$attribute];
					} elseif (!empty($arguments[($attribute . '.')])) {
						$value = $arguments[$attribute . '.'];
					}
					try {
						$element->setAttribute($attribute, $value);
					} catch (\Exception $exception) {
						throw new \RuntimeException('Cannot call user function for attribute ' . ucfirst($attribute), 1333754904);
					}
				}
			}
		} else {
			throw new \InvalidArgumentException('The element with id=' . $element->getElementId() . ' has no default attributes set.', 1333754925);
		}
	}

	/**
	 * Set the additionals from Element Typoscript configuration
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element Model object
	 * @param array $arguments Arguments
	 * @return void
	 */
	public function setAdditionals(\TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element, array $arguments) {
		if (!empty($arguments)) {
			if ($element->hasAllowedAdditionals()) {
				$additionals = $element->getAllowedAdditionals();
				foreach ($additionals as $additional) {
					if (isset($arguments[$additional . '.']) || isset($arguments[$additional])) {
						if (isset($arguments[$additional]) && isset($arguments[$additional . '.'])) {
							$value = $arguments[$additional . '.'];
							$type = $arguments[$additional];
						} elseif (isset($arguments[$additional . '.'])) {
							$value = $arguments[$additional . '.'];
							$type = 'TEXT';
						} else {
							$value['value'] = $arguments[$additional];
							$type = 'TEXT';
						}
						try {
							$element->setAdditional($additional, $type, $value);
						} catch (\Exception $exception) {
							throw new \RuntimeException('Cannot call user function for additional ' . ucfirst($additional), 1333754941);
						}
					}
					if (isset($arguments['layout.'][$additional]) && $element->additionalIsSet($additional)) {
						$layout = $arguments['layout.'][$additional];
						$element->setAdditionalLayout($additional, $layout);
					}
				}
			} else {
				throw new \InvalidArgumentException('The element with id=' . $element->getElementId() . ' has no additionals set.', 1333754962);
			}
		}
	}

	/**
	 * Add the filters according to the settings in the Typoscript array
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element Model object
	 * @param array $arguments TypoScript
	 * @return void
	 */
	protected function setFilters(\TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $element, array $arguments) {
		$keys = \TYPO3\CMS\Core\TypoScript\TemplateService::sortedKeyList($arguments);
		foreach ($keys as $key) {
			$class = $arguments[$key];
			if (intval($key) && !strstr($key, '.')) {
				$filterArguments = $arguments[$key . '.'];
				$filter = $element->makeFilter($class, $filterArguments);
				$element->addFilter($filter);
			}
		}
	}

	/**
	 * Set the layout handler
	 *
	 * @param array $typoscript TypoScript
	 * @return \TYPO3\CMS\Form\Layout The layout handler
	 */
	public function setLayoutHandler(array $typoscript) {
		/** @var $layoutHandler \TYPO3\CMS\Form\Layout */
		$layoutHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Layout');
		// singleton
		if (isset($typoscript['layout.'])) {
			$layoutHandler->setLayout($typoscript['layout.']);
		}
		return $layoutHandler;
	}

	/**
	 * Set the request handler
	 *
	 * @param array $typoscript TypoScript
	 * @return \TYPO3\CMS\Form\Request The request handler
	 */
	public function setRequestHandler($typoscript) {
		$prefix = isset($typoscript['prefix']) ? $typoscript['prefix'] : '';
		$method = isset($typoscript['method']) ? $typoscript['method'] : '';
		/** @var $requestHandler \TYPO3\CMS\Form\Request */
		$requestHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Request');
		// singleton
		$requestHandler->setPrefix($prefix);
		$requestHandler->setMethod($method);
		$requestHandler->storeFiles();
		return $requestHandler;
	}

	/**
	 * Set the validation rules
	 *
	 * Makes the validation object and adds rules to it
	 *
	 * @param array $typoscript TypoScript
	 * @return \TYPO3\CMS\Form\Utility\ValidatorUtility The validation object
	 */
	public function setRules(array $typoscript) {
		$rulesTyposcript = isset($typoscript['rules.']) ? $typoscript['rules.'] : NULL;
		/** @var $rulesClass \TYPO3\CMS\Form\Utility\ValidatorUtility */
		$rulesClass = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Utility\\ValidatorUtility', $rulesTyposcript);
		// singleton
		if (is_array($rulesTyposcript)) {
			$keys = \TYPO3\CMS\Core\TypoScript\TemplateService::sortedKeyList($rulesTyposcript);
			foreach ($keys as $key) {
				$class = $rulesTyposcript[$key];
				if (intval($key) && !strstr($key, '.')) {
					$elementArguments = $rulesTyposcript[$key . '.'];
					$rule = $rulesClass->createRule($class, $elementArguments);
					$rule->setFieldName($elementArguments['element']);
					$breakOnError = isset($elementArguments['breakOnError']) ? $elementArguments['breakOnError'] : FALSE;
					$rulesClass->addRule($rule, $elementArguments['element'], $breakOnError);
				}
			}
		}
		return $rulesClass;
	}

	/**
	 * Gets the local content object.
	 *
	 * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected function getLocalConentObject() {
		if (!isset($this->localContentObject)) {
			$this->localContentObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		}
		return $this->localContentObject;
	}

}
?>