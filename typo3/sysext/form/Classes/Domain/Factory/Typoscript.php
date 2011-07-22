<?php
declare(encoding = 'utf-8');

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
 * Typoscript factory for form
 *
 * Takes the incoming Typoscipt and adds all the necessary form objects
 * according to the configuration.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_factory_typoscript implements t3lib_Singleton {

	/**
	 * Constructor
	 * Sets the configuration, calls parent constructor, fills the attributes
	 * and adds all form element objects
	 *
	 * @param array $arguments Typoscript configuration
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct() {
	}

	/**
	 * Build model from Typoscript
	 *
	 * @param array $typoscript Typoscript containing all configuration
	 * @return object The form object containing the child elements
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function buildModelFromTyposcript(array $typoscript) {
		$this->setLayoutHandler($typoscript);

		$form = $this->createElement('form', $typoscript);

		return $form;
	}

	/**
	 * Rendering of a "numerical array" of Form objects from TypoScript
	 * Creates new object for each element found
	 *
	 * @param array $arguments Configuration array
	 * @throws Exception
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getChildElementsByIntegerKey(&$parentElement, $typoscript) {
		if (is_array($typoscript)) {
			$keys = t3lib_TStemplate::sortedKeyList($typoscript);
			foreach ($keys as $key)	{
				$class = $typoscript[$key];
				if (intval($key) && !strstr($key, '.')) {
					if(isset($typoscript[$key . '.'])) {
						$elementArguments = $typoscript[$key . '.'];
					} else {
						$elementArguments = array();
					}
					$this->setElementType($parentElement, $class, $elementArguments);
				}
			}
		} else {
			throw new Exception ('Container element with id=' . $this->elementId . ' has no configuration which means no children');
		}
	}

	/**
	 * Create and add element by type.
	 * This can be a derived Typoscript object by "<",
	 * a form element, or a regular Typoscript object.
	 *
	 * @param object $parentElement The parent for the new element
	 * @param string $class Classname for the element
	 * @param array $arguments Configuration array
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setElementType(&$parentElement, $class, array $arguments) {
		if (substr($class, 0, 1) == '<') {
			$key = trim(substr($class, 1));
			$typoscriptParser = t3lib_div::makeInstance('t3lib_TSparser');
			$oldArguments = $arguments;
			list($class, $arguments) = $typoscriptParser->getVal($key, $GLOBALS['TSFE']->tmpl->setup);
			if (is_array($oldArguments) && count($oldArguments)) {
				$arguments = $this->joinTSarrays($arguments, $oldArguments);
			}
			$GLOBALS['TT']->incStackPointer();
			$contentObject['cObj'] = $class;
			$contentObject['cObj.'] = $arguments;
			$this->addElement($parentElement, 'content', $contentObject);
			$GLOBALS['TT']->decStackPointer();
		} elseif(in_array($class, $GLOBALS['OBJECTS_form'])) {
			try {
				$this->addElement($parentElement, $class, $arguments);
			} catch (Exception $exception) {
				throw $exception;
			}
		} else {
			$contentObject['cObj'] = $class;
			$contentObject['cObj.'] = $arguments;
			$this->addElement($parentElement, 'content', $contentObject);
		}
	}

	/**
	 * Add child object to this element
	 *
	 * @param string $class Type of element
	 * @param array $arguments Configuration array
	 * @return object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function addElement(&$parentElement, $class, array $arguments = array()) {
		$element = $this->createElement($class, $arguments);
		$parentElement->addElement($element);
	}

	/**
	 * Create element by loading class
	 * and instantiating the object
	 *
	 * @param string $class Type of element
	 * @param array $arguments Configuration array
	 * @return object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function createElement($class, array $arguments = array()) {
		$class = strtolower((string) $class);

		if($class === 'form') {
			$className = 'tx_form_domain_model_' . $class;
		} else {
			$className = 'tx_form_domain_model_element_' . $class;
		}

		$object = t3lib_div::makeInstance($className);

		$this->setAttributes($object, $arguments);
		$this->setAdditionals($object, $arguments);
		$this->setFilters($object, $arguments['filters.']);

		$object->setLayout($arguments['layout']);
		$object->setValue($arguments['value']);
		$object->setName($arguments['name']);

		if($class === 'content') {
			$object->setData($arguments['cObj'], $arguments['cObj.']);
		} else {
			$object->setData($arguments['data']);
		}

		$object->setMessagesFromValidation();
		$object->setErrorsFromValidation();
		$object->checkFilterAndSetIncomingDataFromRequest();

		$this->getChildElementsByIntegerKey($object, $arguments);

		return $object;
	}

	/**
	 * Set the attributes
	 *
	 * @param array $configuration Configuration
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setAttributes(&$element, $arguments) {
		if($element->hasAllowedAttributes()) {
			$attributes = $element->getAllowedAttributes();
			$mandatoryAttributes = $element->getMandatoryAttributes();
			foreach($attributes as $attribute => $value) {
				if(
					isset($arguments[$attribute]) ||
					isset($arguments[$attribute . '.']) ||
					in_array($attribute, $mandatoryAttributes) ||
					!empty($value)
				) {
					if(!empty($arguments[$attribute])) {
						$value = $arguments[$attribute];
					} elseif(!empty($arguments[$attribute . '.'])) {
						$value = $arguments[$attribute . '.'];
					}

					try {
						$element->setAttribute($attribute, $value);
					} catch (Exception $exception) {
						throw new Exception ('Cannot call user function for attribute ' . ucfirst($attribute));
					}
				}
			}
		} else {
			throw new Exception ('The element with id=' . $elementId . ' has no default attributes set');
		}
	}

	/**
	 * Set the additionals from Element Typoscript configuration
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setAdditionals(&$element, $arguments) {
		if(!empty($arguments)) {
			if($element->hasAllowedAdditionals()) {
				$additionals = $element->getAllowedAdditionals();
				foreach($additionals as $additional) {
					if(isset($arguments[$additional . '.']) || isset($arguments[$additional])) {
						if(isset($arguments[$additional]) && isset($arguments[$additional . '.'])) {
							$value = $arguments[$additional . '.'];
							$type = $arguments[$additional];
						} elseif(isset($arguments[$additional . '.'])) {
							$value = $arguments[$additional . '.'];
							$type = 'TEXT';
						} else {
							$value['value'] = $arguments[$additional];
							$type = 'TEXT';
						}

						try {
							$element->setAdditional($additional, $type, $value);
						} catch (Exception $exception) {
							throw new Exception ('Cannot call user function for additional ' . ucfirst($additional));
						}
					}
					if(isset($arguments['layout.'][$additional]) && $element->additionalIsSet($additional)) {
						$layout = $arguments['layout.'][$additional];
						$element->setAdditionalLayout($additional, $layout);
					}
				}
			} else {
				throw new Exception ('The element with id=' . $elementId . ' has no additionals set');
			}
		}
	}

	/**
	 * Add the filters according to the settings in the Typoscript array
	 *
	 * @param array $arguments TypoScript
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function setFilters(&$element, $arguments) {
		if (is_array($arguments)) {
			$keys = t3lib_TStemplate::sortedKeyList($arguments);
			foreach ($keys as $key)	{
				$class = $arguments[$key];
				if (intval($key) && !strstr($key, '.')) {
					$filterArguments = $arguments[$key . '.'];
					$filter = $element->makeFilter($class, $filterArguments);
					$element->addFilter($filter);
				}
			}
		}
	}

	/**
	 * Set the layout handler
	 *
	 * @param array $typoscript TypoScript
	 * @return tx_form_system_layout The layout handler
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setLayoutHandler($typoscript) {
		$layoutHandler = t3lib_div::makeInstance('tx_form_system_layout'); // singleton
		$layoutHandler->setLayout($typoscript['layout.']);

		return $layoutHandler;
	}

	/**
	 * Set the request handler
	 *
	 * @param array $typoscript TypoScript
	 * @return tx_form_system_request The request handler
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setRequestHandler($typoscript) {
		$prefix = isset($typoscript['prefix']) ? $typoscript['prefix'] : '';
		$method = isset($typoscript['method']) ? $typoscript['method'] : '';

		/** @var $requestHandler tx_form_system_request */
		$requestHandler = t3lib_div::makeInstance('tx_form_system_request'); // singleton
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
	 * @return tx_form_system_validate The validation object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setRules($typoscript) {
		$rulesTyposcript = isset($typoscript['rules.']) ? $typoscript['rules.'] : NULL;
		$rulesClass = t3lib_div::makeInstance('tx_form_system_validate', $rulesTyposcript); // singleton

		if (is_array($rulesTyposcript)) {
			$keys = t3lib_TStemplate::sortedKeyList($rulesTyposcript);
			foreach ($keys as $key)	{
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
}
?>