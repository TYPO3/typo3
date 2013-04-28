<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * Abstract Form View Helper. Bundles functionality related to direct property access of objects in other Form ViewHelpers.
 *
 * If you set the "property" attribute to the name of the property to resolve from the object, this class will
 * automatically set the name and value of a form element.
 *
 * @api
 */
abstract class AbstractFormFieldViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Initialize arguments.
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('name', 'string', 'Name of input tag');
		$this->registerArgument('value', 'mixed', 'Value of input tag');
		$this->registerArgument('property', 'string', 'Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.');
	}

	/**
	 * Get the name of this form element.
	 * Either returns arguments['name'], or the correct name for Object Access.
	 *
	 * In case property is something like bla.blubb (hierarchical), then [bla][blubb] is generated.
	 *
	 * @return string Name
	 */
	protected function getName() {
		$name = $this->getNameWithoutPrefix();
		return $this->prefixFieldName($name);
	}

	/**
	 * Get the name of this form element, without prefix.
	 *
	 * @return string name
	 */
	protected function getNameWithoutPrefix() {
		if ($this->isObjectAccessorMode()) {
			$formObjectName = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formObjectName');
			if (!empty($formObjectName)) {
				$propertySegments = explode('.', $this->arguments['property']);
				$propertyPath = '';
				foreach ($propertySegments as $segment) {
					$propertyPath .= '[' . $segment . ']';
				}
				$name = $formObjectName . $propertyPath;
			} else {
				$name = $this->arguments['property'];
			}
		} else {
			$name = $this->arguments['name'];
		}
		if ($this->hasArgument('value') && is_object($this->arguments['value'])) {
			// TODO: Use  $this->persistenceManager->isNewObject() once it is implemented
			if (NULL !== $this->persistenceManager->getIdentifierByObject($this->arguments['value'])) {
				$name .= '[__identity]';
			}
		}
		return $name;
	}

	/**
	 * Get the value of this form element.
	 * Either returns arguments['value'], or the correct value for Object Access.
	 *
	 * @param boolean $convertObjects whether or not to convert objects to identifiers
	 * @return mixed Value
	 */
	protected function getValue($convertObjects = TRUE) {
		$value = NULL;
		if ($this->hasArgument('value')) {
			$value = $this->arguments['value'];
		} elseif ($this->configurationManager->isFeatureEnabled('rewrittenPropertyMapper') && $this->hasMappingErrorOccured()) {
			$value = $this->getLastSubmittedFormData();
		} elseif ($this->isObjectAccessorMode() && $this->viewHelperVariableContainer->exists('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formObject')) {
			$this->addAdditionalIdentityPropertiesIfNeeded();
			$value = $this->getPropertyValue();
		}
		if ($convertObjects === TRUE && is_object($value)) {
			$identifier = $this->persistenceManager->getIdentifierByObject($value);
			if ($identifier !== NULL) {
				$value = $identifier;
			}
		}
		return $value;
	}

	/**
	 * Checks if a property mapping error has occured in the last request.
	 *
	 * @return boolean TRUE if a mapping error occured, FALSE otherwise
	 */
	protected function hasMappingErrorOccured() {
		return $this->controllerContext->getRequest()->getOriginalRequest() !== NULL;
	}

	/**
	 * Get the form data which has last been submitted; only returns valid data in case
	 * a property mapping error has occured. Check with hasMappingErrorOccured() before!
	 *
	 * @return mixed
	 */
	protected function getLastSubmittedFormData() {
		$propertyPath = rtrim(preg_replace('/(\\]\\[|\\[|\\])/', '.', $this->getNameWithoutPrefix()), '.');
		$value = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($this->controllerContext->getRequest()->getOriginalRequest()->getArguments(), $propertyPath);
		return $value;
	}

	/**
	 * Add additional identity properties in case the current property is hierarchical (of the form "bla.blubb").
	 * Then, [bla][__identity] has to be generated as well.
	 *
	 * @return void
	 */
	protected function addAdditionalIdentityPropertiesIfNeeded() {
		$propertySegments = explode('.', $this->arguments['property']);
		if (count($propertySegments) >= 2) {
			// hierarchical property. If there is no "." inside (thus $propertySegments == 1), we do not need to do anything
			$formObject = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formObject');
			$objectName = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formObjectName');
			// If Count == 2 -> we need to go through the for-loop exactly once
			for ($i = 1; $i < count($propertySegments); $i++) {
				$object = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($formObject, implode('.', array_slice($propertySegments, 0, $i)));
				$objectName .= '[' . $propertySegments[($i - 1)] . ']';
				$hiddenIdentityField = $this->renderHiddenIdentityField($object, $objectName);
				// Add the hidden identity field to the ViewHelperVariableContainer
				$additionalIdentityProperties = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'additionalIdentityProperties');
				$additionalIdentityProperties[$objectName] = $hiddenIdentityField;
				$this->viewHelperVariableContainer->addOrUpdate('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'additionalIdentityProperties', $additionalIdentityProperties);
			}
		}
	}

	/**
	 * Get the current property of the object bound to this form.
	 *
	 * @return mixed Value
	 */
	protected function getPropertyValue() {
		$formObject = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formObject');
		$propertyName = $this->arguments['property'];
		if (is_array($formObject)) {
			return isset($formObject[$propertyName]) ? $formObject[$propertyName] : NULL;
		}
		return \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($formObject, $propertyName);
	}

	/**
	 * Internal method which checks if we should evaluate a domain object or just output arguments['name'] and arguments['value']
	 *
	 * @return boolean TRUE if we should evaluate the domain object, FALSE otherwise.
	 */
	protected function isObjectAccessorMode() {
		return $this->hasArgument('property') && $this->viewHelperVariableContainer->exists('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formObjectName');
	}

	/**
	 * Add an CSS class if this view helper has errors
	 *
	 * @return void
	 */
	protected function setErrorClassAttribute() {
		if ($this->hasArgument('class')) {
			$cssClass = $this->arguments['class'] . ' ';
		} else {
			$cssClass = '';
		}
		if ($this->configurationManager->isFeatureEnabled('rewrittenPropertyMapper')) {
			$mappingResultsForProperty = $this->getMappingResultsForProperty();
			if ($mappingResultsForProperty->hasErrors()) {
				if ($this->hasArgument('errorClass')) {
					$cssClass .= $this->arguments['errorClass'];
				} else {
					$cssClass .= 'error';
				}
				$this->tag->addAttribute('class', $cssClass);
			}
		} else {
			// @deprecated since Fluid 1.4.0, will will be removed two versions after Fluid 6.1.
			$errors = $this->getErrorsForProperty();
			if (count($errors) > 0) {
				if ($this->hasArgument('errorClass')) {
					$cssClass .= $this->arguments['errorClass'];
				} else {
					$cssClass .= 'error';
				}
				$this->tag->addAttribute('class', $cssClass);
			}
		}
	}

	/**
	 * Get errors for the property and form name of this view helper
	 *
	 * @return array<Tx_Extbase_Error_Result> Array of errors
	 */
	protected function getMappingResultsForProperty() {
		if (!$this->isObjectAccessorMode()) {
			return new \TYPO3\CMS\Extbase\Error\Result();
		}
		$originalRequestMappingResults = $this->controllerContext->getRequest()->getOriginalRequestMappingResults();
		$formObjectName = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formObjectName');
		return $originalRequestMappingResults->forProperty($formObjectName)->forProperty($this->arguments['property']);
	}

	/**
	 * Get errors for the property and form name of this view helper
	 *
	 * @return array An array of Tx_Fluid_Error_Error objects
	 * @deprecated since Fluid 1.4.0, will will be removed two versions after Fluid 6.1.
	 */
	protected function getErrorsForProperty() {
		if (!$this->isObjectAccessorMode()) {
			return array();
		}
		$errors = $this->controllerContext->getRequest()->getErrors();
		$formObjectName = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formObjectName');
		$propertyName = $this->arguments['property'];
		foreach ($errors as $error) {
			if ($error instanceof \TYPO3\CMS\Extbase\Validation\PropertyError && $error->getPropertyName() === $formObjectName) {
				$formErrors = $error->getErrors();
				foreach ($formErrors as $formError) {
					if ($formError instanceof \TYPO3\CMS\Extbase\Validation\PropertyError && $formError->getPropertyName() === $propertyName) {
						return $formError->getErrors();
					}
				}
			}
		}
		return array();
	}

	/**
	 * Renders a hidden field with the same name as the element, to make sure the empty value is submitted
	 * in case nothing is selected. This is needed for checkbox and multiple select fields
	 *
	 * @return string the hidden field.
	 */
	protected function renderHiddenFieldForEmptyValue() {
		$hiddenFieldNames = array();
		if ($this->viewHelperVariableContainer->exists('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'renderedHiddenFields')) {
			$hiddenFieldNames = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'renderedHiddenFields');
		}
		$fieldName = $this->getName();
		if (substr($fieldName, -2) === '[]') {
			$fieldName = substr($fieldName, 0, -2);
		}
		if (!in_array($fieldName, $hiddenFieldNames)) {
			$hiddenFieldNames[] = $fieldName;
			$this->viewHelperVariableContainer->addOrUpdate('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'renderedHiddenFields', $hiddenFieldNames);
			return '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="" />';
		}
		return '';
	}
}

?>