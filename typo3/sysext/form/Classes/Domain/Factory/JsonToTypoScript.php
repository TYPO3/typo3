<?php
namespace TYPO3\CMS\Form\Domain\Factory;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 * Json to Typoscript converter
 *
 * Takes the incoming Json and converts it to Typoscipt
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class JsonToTypoScript {

	/**
	 * Internal counter for the elements
	 *
	 * @var integer
	 */
	protected $elementId = 0;

	/**
	 * Storage for the validation rules
	 * In TypoScript they are set in the form, in the wizard on the elements
	 *
	 * @var array
	 */
	protected $validationRules = array();

	/**
	 * Internal counter for the validation rules
	 *
	 * @var integer
	 */
	protected $validationRulesCounter = 1;

	/**
	 * Convert JSON to TypoScript
	 *
	 * First a TypoScript array is constructed,
	 * which will be converted to a formatted string
	 *
	 * @param string $json Json containing all configuration for the form
	 * @return string The typoscript for the form
	 */
	public function convert($json) {
		$elements = json_decode((string) $json, TRUE);
		$typoscriptArray = array();
		$typoscript = NULL;
		$this->convertToTyposcriptArray($elements, $typoscriptArray);
		if ($typoscriptArray['10.'] && is_array($typoscriptArray['10.']) && !empty($typoscriptArray['10.'])) {
			$typoscript = $this->typoscriptArrayToString($typoscriptArray['10.']);
		}
		return $typoscript;
	}

	/**
	 * Converts the JSON array for each element to a TypoScript array
	 * and adds this Typoscript array to the parent
	 *
	 * @param array $elements The JSON array
	 * @param array $parent The parent element
	 * @param boolean $childrenWithParentName Indicates if the children use the parent name
	 * @return void
	 */
	protected function convertToTyposcriptArray(array $elements, array &$parent, $childrenWithParentName = FALSE) {
		if (is_array($elements)) {
			$elementCounter = 10;
			foreach ($elements as $element) {
				if ($element['xtype']) {
					$this->elementId++;
					switch ($element['xtype']) {
						case 'typo3-form-wizard-elements-basic-button':

						case 'typo3-form-wizard-elements-basic-checkbox':

						case 'typo3-form-wizard-elements-basic-fileupload':

						case 'typo3-form-wizard-elements-basic-hidden':

						case 'typo3-form-wizard-elements-basic-password':

						case 'typo3-form-wizard-elements-basic-radio':

						case 'typo3-form-wizard-elements-basic-reset':

						case 'typo3-form-wizard-elements-basic-select':

						case 'typo3-form-wizard-elements-basic-submit':

						case 'typo3-form-wizard-elements-basic-textarea':

						case 'typo3-form-wizard-elements-basic-textline':

						case 'typo3-form-wizard-elements-predefined-email':

						case 'typo3-form-wizard-elements-content-header':

						case 'typo3-form-wizard-elements-content-textblock':
							$this->getDefaultElementSetup($element, $parent, $elementCounter, $childrenWithParentName);
							break;
						case 'typo3-form-wizard-elements-basic-fieldset':

						case 'typo3-form-wizard-elements-predefined-name':
							$this->getDefaultElementSetup($element, $parent, $elementCounter);
							$this->getContainer($element, $parent, $elementCounter);
							break;
						case 'typo3-form-wizard-elements-predefined-checkboxgroup':

						case 'typo3-form-wizard-elements-predefined-radiogroup':
							$this->getDefaultElementSetup($element, $parent, $elementCounter);
							$this->getContainer($element, $parent, $elementCounter, TRUE);
							break;
						case 'typo3-form-wizard-elements-basic-form':
							$this->getDefaultElementSetup($element, $parent, $elementCounter);
							$this->getContainer($element, $parent, $elementCounter);
							$this->getForm($element, $parent, $elementCounter);
							break;
						default:

					}
				}
				$elementCounter = $elementCounter + 10;
			}
		}
	}

	/**
	 * Called for elements are a container for other elements like FORM and FIELDSET
	 *
	 * @param array $element The JSON array for this element
	 * @param array $parent The parent element
	 * @param boolean $childrenWithParentName Indicates if the children use the parent name
	 * @param integer $elementCounter The element counter
	 * @return void
	 */
	protected function getContainer(array $element, array &$parent, $elementCounter, $childrenWithParentName = FALSE) {
		if ($element['elementContainer'] && $element['elementContainer']['items']) {
			$this->convertToTyposcriptArray($element['elementContainer']['items'], $parent[$elementCounter . '.'], $childrenWithParentName);
		}
	}

	/**
	 * Only called for the type FORM
	 *
	 * Adds the validation rules to the form. In the wizard they are added to
	 * each element. In this script the validation rules are stored in a
	 * separate array to add them to the form at a later point.
	 *
	 * @param array $element The JSON array for this element
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @return void
	 */
	protected function getForm(array $element, array &$parent, $elementCounter) {
		// TODO: Put at the top of the form
		if (!empty($this->validationRules)) {
			$parent[$elementCounter . '.']['rules'] = $this->validationRules;
		}
	}

	/**
	 * Called for each element
	 *
	 * Adds the content object type to the parent array and starts adding the
	 * configuration for the element
	 *
	 * @param array $element The JSON array for this element
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @param boolean $childrenWithParentName Indicates if the children use the parent name
	 * @return void
	 */
	protected function getDefaultElementSetup(array $element, array &$parent, $elementCounter, $childrenWithParentName = FALSE) {
		$contentObjectType = $this->getContentObjectType($element);
		if (is_null($contentObjectType) === FALSE) {
			$parent[$elementCounter] = $contentObjectType;
			$parent[$elementCounter . '.'] = array();
			if ($element['configuration']) {
				$this->setConfiguration($element, $parent, $elementCounter, $childrenWithParentName);
			}
		}
	}

	/**
	 * Returns the content object type which is related to the ExtJS xtype
	 *
	 * @param array $element The JSON array for this element
	 * @return string The Content Object Type
	 */
	protected function getContentObjectType(array $element) {
		$contentObjectType = NULL;
		$shortXType = str_replace('typo3-form-wizard-elements-', '', $element['xtype']);
		list($category, $type) = explode('-', $shortXType);
		switch ($category) {
			case 'basic':
				$contentObjectType = strtoupper($type);
				break;
			case 'predefined':
				switch ($type) {
				case 'checkboxgroup':

				case 'radiogroup':
					$contentObjectType = strtoupper($type);
					break;
				case 'email':
					$contentObjectType = 'TEXTLINE';
					break;
				case 'name':
					$contentObjectType = 'FIELDSET';
				}
				break;
			case 'content':
				switch ($type) {
				case 'header':

				case 'textblock':
					$contentObjectType = strtoupper($type);
				}
			default:

		}
		return $contentObjectType;
	}

	/**
	 * Iterates over the various configuration settings and calls the
	 * appropriate function for each setting
	 *
	 * @param array $element The JSON array for this element
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @param boolean $childrenWithParentName Indicates if the children use the parent name
	 * @return void
	 */
	protected function setConfiguration(array $element, array &$parent, $elementCounter, $childrenWithParentName = FALSE) {
		foreach ($element['configuration'] as $key => $value) {
			switch ($key) {
				case 'attributes':
					$this->setAttributes($value, $parent, $elementCounter, $childrenWithParentName);
					break;
				case 'confirmation':
					$this->setConfirmation($value, $parent, $elementCounter);
					break;
				case 'filters':
					$this->setFilters($value, $parent, $elementCounter);
					break;
				case 'label':
					$this->setLabel($value, $parent, $elementCounter);
					break;
				case 'layout':
					$this->setLayout($element, $value, $parent, $elementCounter);
					break;
				case 'legend':
					$this->setLegend($value, $parent, $elementCounter);
					break;
				case 'options':
					$this->setOptions($element, $value, $parent, $elementCounter);
					break;
				case 'postProcessor':
					$this->setPostProcessor($value, $parent, $elementCounter);
					break;
				case 'prefix':
					$this->setPrefix($value, $parent, $elementCounter);
					break;
				case 'validation':
					$this->setValidationRules($element, $value);
					break;
				case 'various':
					$this->setVarious($element, $value, $parent, $elementCounter);
					break;
				default:

			}
		}
	}

	/**
	 * Set the attributes for the element
	 *
	 * @param array $attributes The JSON array for the attributes of this element
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @param boolean $childrenWithParentName Indicates if the children use the parent name
	 * @return void
	 */
	protected function setAttributes(array $attributes, array &$parent, $elementCounter, $childrenWithParentName = FALSE) {
		foreach ($attributes as $key => $value) {
			if ($key === 'name' && $value === '' && !$childrenWithParentName) {
				$value = $this->elementId;
			}
			if ($value != '' && $key != 'type') {
				$parent[$elementCounter . '.'][$key] = $value;
			}
		}
	}

	/**
	 * Set the confirmation for the element FORM
	 *
	 * The confirmation indicates a confirmation screen has to be displayed
	 *
	 * @param boolean $confirmation TRUE when confirmation screen
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @return void
	 */
	protected function setConfirmation($confirmation, array &$parent, $elementCounter) {
		$parent[$elementCounter . '.']['confirmation'] = $confirmation;
	}

	/**
	 * Set the filters for the element
	 *
	 * @param array $filters The JSON array for the filters of this element
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @return void
	 */
	protected function setFilters(array $filters, array &$parent, $elementCounter) {
		if (!empty($filters)) {
			$parent[$elementCounter . '.']['filters'] = array();
			$filterCounter = 1;
			foreach ($filters as $name => $filterConfiguration) {
				$parent[$elementCounter . '.']['filters'][$filterCounter] = $name;
				$parent[$elementCounter . '.']['filters'][$filterCounter . '.'] = $filterConfiguration;
				$filterCounter++;
			}
		}
	}

	/**
	 * Set the label for the element
	 *
	 * @param array $label The JSON array for the label of this element
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @return void
	 */
	protected function setLabel(array $label, array &$parent, $elementCounter) {
		if ($label['value'] != '') {
			$parent[$elementCounter . '.']['label.']['value'] = $label['value'];
		}
	}

	/**
	 * Changes the layout of some elements when this has been set in the wizard
	 *
	 * The wizard only uses 'back' or 'front' to set the layout. The TypoScript
	 * of the form uses a XML notation for the position of the label to the
	 * field.
	 *
	 * @param array $element The JSON array for this element
	 * @param string $value The layout setting, back or front
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @return void
	 */
	protected function setLayout(array $element, $value, array &$parent, $elementCounter) {
		switch ($element['xtype']) {
			case 'typo3-form-wizard-elements-basic-button':

			case 'typo3-form-wizard-elements-basic-fileupload':

			case 'typo3-form-wizard-elements-basic-password':

			case 'typo3-form-wizard-elements-basic-reset':

			case 'typo3-form-wizard-elements-basic-submit':

			case 'typo3-form-wizard-elements-basic-textline':
				if ($value === 'back') {
					$parent[$elementCounter . '.']['layout'] = '<input />' . chr(10) . '<label />';
				}
				break;
			case 'typo3-form-wizard-elements-basic-checkbox':

			case 'typo3-form-wizard-elements-basic-radio':
				if ($value === 'front') {
					$parent[$elementCounter . '.']['layout'] = '<label />' . chr(10) . '<input />';
				}
				break;
			case 'typo3-form-wizard-elements-basic-select':
				if ($value === 'back') {
					$parent[$elementCounter . '.']['layout'] = '<select>' . chr(10) . '<elements />' . chr(10) . '</select>' . chr(10) . '<label />';
				}
				break;
			case 'typo3-form-wizard-elements-basic-textarea':
				if ($value === 'back') {
					$parent[$elementCounter . '.']['layout'] = '<textarea />' . chr(10) . '<label />';
				}
				break;
			default:

		}
	}

	/**
	 * Set the legend for the element
	 *
	 * @param array $legend The JSON array for the legend of this element
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @return void
	 */
	protected function setLegend(array $legend, array &$parent, $elementCounter) {
		if ($legend['value'] != '') {
			$parent[$elementCounter . '.']['legend.']['value'] = $legend['value'];
		}
	}

	/**
	 * Set the options for a SELECT
	 *
	 * Although other elements like CHECKBOXGROUP and RADIOGROUP are using the
	 * option setting as well, they act like containers and are handled
	 * differently
	 *
	 * @param array $element The JSON array for this element
	 * @param array $options The JSON array for the options of this element
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @return void
	 */
	protected function setOptions(array $element, array $options, array &$parent, $elementCounter) {
		if (is_array($options) && $element['xtype'] === 'typo3-form-wizard-elements-basic-select') {
			$optionCounter = 10;
			foreach ($options as $option) {
				$parent[$elementCounter . '.'][$optionCounter] = 'OPTION';
				$parent[$elementCounter . '.'][$optionCounter . '.']['data'] = $option['data'];
				if (isset($option['attributes'])) {
					$parent[$elementCounter . '.'][$optionCounter . '.'] += $option['attributes'];
				}
				$optionCounter = $optionCounter + 10;
			}
		}
	}

	/**
	 * Set the post processors for the element
	 *
	 * @param array $postProcessors The JSON array for the post processors of this element
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @return void
	 */
	protected function setPostProcessor(array $postProcessors, array &$parent, $elementCounter) {
		if (!empty($postProcessors)) {
			$parent[$elementCounter . '.']['postProcessor'] = array();
			$postProcessorCounter = 1;
			foreach ($postProcessors as $name => $postProcessorConfiguration) {
				$parent[$elementCounter . '.']['postProcessor'][$postProcessorCounter] = $name;
				$parent[$elementCounter . '.']['postProcessor'][$postProcessorCounter . '.'] = $postProcessorConfiguration;
				$postProcessorCounter++;
			}
		}
	}

	/**
	 * Set the prefix for the element FORM
	 *
	 * The prefix will be used in the names of all elements in the form
	 *
	 * @param string $prefix The prefix for all element names
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @return void
	 */
	protected function setPrefix($prefix, array &$parent, $elementCounter) {
		$parent[$elementCounter . '.']['prefix'] = $prefix;
	}

	/**
	 * Stores the validation rules, set to the elements, in a temporary array
	 *
	 * In the wizard the validation rules are added to the element,
	 * in TypoScript they are added to the form.
	 *
	 * @param array $element The JSON array for this element
	 * @param array $validationRules The temporary storage array for the rules
	 * @return void
	 */
	protected function setValidationRules(array $element, array $validationRules) {
		foreach ($validationRules as $name => $ruleConfiguration) {
			if (isset($element['configuration']['attributes']['name']) && $element['configuration']['attributes']['name'] != '') {
				$ruleConfiguration['element'] = $element['configuration']['attributes']['name'];
			} elseif (isset($element['configuration']['various']['name']) && $element['configuration']['various']['name'] != '') {
				$ruleConfiguration['element'] = $element['configuration']['various']['name'];
			} else {
				$ruleConfiguration['element'] = $this->elementId;
			}
			$this->validationRules[$this->validationRulesCounter] = $name;
			$this->validationRules[$this->validationRulesCounter . '.'] = $ruleConfiguration;
			$this->validationRulesCounter++;
		}
	}

	/**
	 * Set the various configuration of an element
	 *
	 * @param array $element The JSON array for this element
	 * @param array $options The JSON array for the various options of this element
	 * @param array $parent The parent element
	 * @param integer $elementCounter The element counter
	 * @return void
	 */
	protected function setVarious(array $element, array $various, array &$parent, $elementCounter) {
		foreach ($various as $key => $value) {
			switch ($key) {
				case 'headingSize':

				case 'content':

				case 'name':
					$parent[$elementCounter . '.'][$key] = (string) $value;
					break;
			}
		}
	}

	/**
	 * Converts a TypoScript array to a formatted string
	 *
	 * Takes care of indentation, curly brackets and parentheses
	 *
	 * @param array $typoscriptArray The TypoScript array
	 * @param string $addKey Key which has underlying configuration
	 * @param integer $tabCount The amount of tabs for indentation
	 * @return string The formatted TypoScript string
	 */
	protected function typoscriptArrayToString(array $typoscriptArray, $addKey = '', $tabCount = -1) {
		$typoscript = '';
		if ($addKey != '') {
			$typoscript .= str_repeat(chr(9), $tabCount) . str_replace('.', '', $addKey) . ' {' . chr(10);
		}
		$tabCount++;
		foreach ($typoscriptArray as $key => $value) {
			if (!is_array($value)) {
				if (strstr($value, chr(10))) {
					$typoscript .= str_repeat(chr(9), $tabCount) . $key . ' (' . chr(10);
					$value = str_replace(chr(10), chr(10) . str_repeat(chr(9), ($tabCount + 1)), $value);
					$typoscript .= str_repeat(chr(9), ($tabCount + 1)) . $value . chr(10);
					$typoscript .= str_repeat(chr(9), $tabCount) . ')' . chr(10);
				} else {
					$typoscript .= str_repeat(chr(9), $tabCount) . $key . ' = ' . $value . chr(10);
				}
			} else {
				$typoscript .= $this->typoscriptArrayToString($value, $key, $tabCount);
			}
		}
		if ($addKey != '') {
			$tabCount--;
			$typoscript .= str_repeat(chr(9), $tabCount) . '}' . chr(10);
		}
		return $typoscript;
	}

}

?>