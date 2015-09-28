<?php
namespace TYPO3\CMS\Form\Utility;

/*
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Builder\FormBuilder;
use TYPO3\CMS\Form\Domain\Model\Element;

/**
 * Compatibility layer.
 * Used in the scope of one(!) specific form element.
 */
class CompatibilityLayerUtility {

	/**
	 * @param FormBuilder $formBuilder
	 * @return CompatibilityLayerUtility
	 */
	static public function create(FormBuilder $formBuilder) {
		/** @var CompatibilityLayerUtility $compatibilityService */
		$compatibilityService = \TYPO3\CMS\Form\Utility\FormUtility::getObjectManager()->get(CompatibilityLayerUtility::class);
		$compatibilityService->setFormBuilder($formBuilder);
		return $compatibilityService;
	}

	/**
	 * Layout array from form configuration
	 *
	 * @var array
	 */
	protected $layout = array();

	/**
	 * @var FormBuilder
	 */
	protected $formBuilder;

	/**
	 * @var array
	 */
	protected $registeredFormElements = array(
		'TEXTLINE',
		'SUBMIT',
		'RESET',
		'RADIO',
		'PASSWORD',
		'IMAGEBUTTON',
		'FILEUPLOAD',
		'CHECKBOX',
		'BUTTON',
		'TEXTAREA',
		'HIDDEN',
		'CONTENTELEMENT',
		'TEXTBLOCK',
		'SELECT',
		'FIELDSET',
		'RADIOGROUP',
		'CHECKBOXGROUP',
	);

	/**
	 * @var array
	 */
	protected $elementsWithoutLabel = array(
		'HIDDEN',
		'CONTENTELEMENT',
		'TEXTBLOCK',
		'FIELDSET',
		'RADIOGROUP',
		'CHECKBOXGROUP',
	);

	/**
	 * @var array
	 */
	protected $containerElements = array(
		'FIELDSET',
		'RADIOGROUP',
		'CHECKBOXGROUP',
	);

	/**
	 * @param FormBuilder $formBuilder
	 */
	public function setFormBuilder(FormBuilder $formBuilder) {
		$this->formBuilder = $formBuilder;
	}

	/**
	 * Set the layout configuration for one or more elements
	 *
	 * @param NULL|array $typoscript The configuration array
	 * @return void
	 * @deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8, as the functionality is now done via fluid
	 */
	public function setGlobalLayoutConfiguration($typoscript = array()) {
		if (!empty($typoscript['layout.'])) {
			GeneralUtility::deprecationLog('EXT:form: Do not use "layout." anymore. Deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8.');
			$layout = $typoscript['layout.'];
		} else if (!empty($typoscript['layout'])) {
			GeneralUtility::deprecationLog('EXT:form: Do not use "layout." anymore. Deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8.');
			$layout = $typoscript['layout'];
		}
		if (is_array($layout)) {
			foreach ($layout as $elementType => $elementValue) {
				$elementType = strtoupper($elementType);
				$this->layout[$elementType] = $elementValue;
			}
		}
	}

	/**
	 * Get the layout of the object
	 * Looks if there is an assigned layout by configuration of the element
	 * otherwise it will look if there is a layout set in the form configuration.
	 *
	 * @param string $elementType Type of element e.g BUTTON
	 * @return string The element layout
	 * @deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8, as the functionality is now done via fluid
	 */
	public function getGlobalLayoutByElementType($elementType) {
		GeneralUtility::deprecationLog('EXT:form: Do not use "layout." anymore. Deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8.');
		$layout = '';
		if (!empty($this->layout[$elementType])) {
			$layout = $this->layout[$elementType];
		} else {
			switch ($elementType) {
				case 'FORM':
					$layout = '<form><containerWrap /></form>';
					break;
				case 'CONTAINERWRAP':
					$layout = '<ol><elements /></ol>';
					break;
				case 'ELEMENTWRAP':
					$layout = '<li><element /></li>';
					break;
				case 'LABEL':
					$layout = '<label><labelvalue /><mandatory /><error /></label>';
					break;
				case 'MANDATORY':
					$layout = '<em><mandatoryvalue /></em>';
					break;
				case 'ERROR':
					$layout = '<strong><errorvalue /></strong>';
					break;
				case 'FIELDSET':
					$layout = '<fieldset><legend /><containerWrap /></fieldset>';
					break;
				case 'HIDDEN':
					$layout = '<input />';
					break;
				case 'SELECT':
					$layout = '<label /><select><elements /></select>';
					break;
				case 'TEXTAREA':
					$layout = '<label /><textarea />';
					break;
				case 'BUTTON':
				case 'CHECKBOX':
				case 'FILEUPLOAD':
				case 'IMAGEBUTTON':
				case 'PASSWORD':
				case 'RADIO':
				case 'RESET':
				case 'SUBMIT':
				case 'TEXTLINE':
					$layout = '<label /><input />';
					break;
			}
		}
		return $layout;
	}

	/**
	 * Set the layout for a element
	 * Not supported / ignored: OPTGROUP, OPTION, layout.legend
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element $element
	 * @param array $userConfiguredElementTyposcript The configuration array
	 * @return void
	 * @deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8, as the functionality is now done via fluid
	 */
	public function setElementLayouts(Element $element, array $userConfiguredElementTyposcript = array()) {
		GeneralUtility::deprecationLog('EXT:form: Do not use "layout." anymore. Deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8.');
		if ($element->getElementType() === 'FORM') {
			$containerWrapReturn = $this->replaceTagWithMarker('elements', 'body', $this->getGlobalLayoutByElementType('CONTAINERWRAP'));
			$formWrapReturn = $this->replaceTagWithMarker('containerwrap', 'form', $this->getGlobalLayoutByElementType('FORM'));
			$formLayout = str_replace($formWrapReturn['marker'], $containerWrapReturn['html'], $formWrapReturn['html']);
			$formContainerWrap = explode($containerWrapReturn['marker'], $formLayout);
			$layout['containerInnerWrap'] = $formContainerWrap;
			$element->setLayout($layout);
			$classFromLayout = $this->getElementClassFromLayout('form');
			if (!empty($classFromLayout)) {
				if (!empty($element->getAdditionalArgument('class'))) {
					$classFromLayout .= ' ' . $element->getAdditionalArgument('class');
				}
				$element->setAdditionalArgument('class', $classFromLayout);
			}
			return;
		}
		if (in_array($element->getElementType(), $this->registeredFormElements)) {
			/* Get the element layout definition or fallback to the global definition (if set) */
			if (isset($userConfiguredElementTyposcript['layout'])) {
				$elementLayout = $userConfiguredElementTyposcript['layout'];
			} else {
				$elementLayout = $this->getGlobalLayoutByElementType($element->getElementType());
			}
			/* if a element layout exist */
			$elementWrap = NULL;
			if ($elementLayout) {
				$elementWrap = $this->determineElementOuterWraps($element->getElementType(), $elementLayout);
				if ($elementWrap['html'] !== '') {
					/* layout.label */
					if (!in_array($element->getElementType(), $this->elementsWithoutLabel, TRUE)) {
						$labelLayout = $this->getGlobalLayoutByElementType('LABEL');
						$mandatoryLayout = '';
						$errorLayout = '';
						if ($this->formBuilder->getControllerAction() === 'show') {
							/* layout.mandatory */
							$mandatoryMessages = $this->formBuilder->getValidationBuilder()->getMandatoryValidationMessagesByElementName($element->getName());
							if (!empty($mandatoryMessages)) {
								$mandatoryLayout = $this->replaceLabelContent('mandatory', $mandatoryMessages);
							}
							/* layout.error */
							$errorMessages = $element->getValidationErrorMessages();
							if (!empty($errorMessages)) {
								$errorLayout = $this->replaceLabelContent('error', $errorMessages);
							}
						}
						/* Replace the mandatory and error messages */
						$mandatoryReturn = $this->replaceTagWithMarker('mandatory', 'body', $labelLayout);
						$labelContainContent = FALSE;
						if ($mandatoryReturn['html'] !== '') {
							if (!empty($mandatoryLayout)) {
								$labelContainContent = TRUE;
							}
							$labelLayout = str_replace($mandatoryReturn['marker'], $mandatoryLayout, $mandatoryReturn['html']);
						}
						$errorReturn = $this->replaceTagWithMarker('error', 'body', $labelLayout);
						if ($errorReturn['html'] !== '') {
							if (!empty($errorLayout)) {
								$labelContainContent = TRUE;
							}
							$labelLayout = str_replace($errorReturn['marker'], $errorLayout, $errorReturn['html']);
						}
						/* Replace the label value */
						$labelValueReturn = $this->replaceTagWithMarker('labelvalue', 'body', $labelLayout);
						if ($labelValueReturn['html'] !== '') {
							if (!empty($element->getAdditionalArgument('label'))) {
								$labelContainContent = TRUE;
							}
							$labelLayout = str_replace($labelValueReturn['marker'], $element->getAdditionalArgument('label'), $labelValueReturn['html']);
						}
						if (!$labelContainContent) {
							$labelLayout = '';
						}
						/* Replace <label />, <error /> and <mandatory /> in the element wrap html */
						$labelReturn = $this->replaceTagWithMarker('label', 'body', $elementWrap['html']);
						if ($labelReturn['html'] !== '') {
							$elementWrap['html'] = str_replace($labelReturn['marker'], $labelLayout, $labelReturn['html']);
						}
						$errorReturn = $this->replaceTagWithMarker('error', 'body', $elementWrap['html']);
						if ($errorReturn['html'] !== '') {
							$elementWrap['html'] = str_replace($errorReturn['marker'], $errorLayout, $errorReturn['html']);
						}
						$mandatoryReturn = $this->replaceTagWithMarker('mandatory', 'body', $elementWrap['html']);
						if ($mandatoryReturn['html'] !== '') {
							$elementWrap['html'] = str_replace($mandatoryReturn['marker'], $mandatoryLayout, $mandatoryReturn['html']);
						}
					}
					$elementWrap = explode($elementWrap['marker'], $elementWrap['html']);
				} else {
					$elementWrap = NULL;
				}
			}
			/* Set element outer wraps and set the default classes */
			$elementOuterWrap = NULL;
			if ($this->getGlobalLayoutByElementType('ELEMENTWRAP')) {
				$dom = new \DOMDocument('1.0', 'utf-8');
				$dom->formatOutput = TRUE;
				$dom->preserveWhiteSpace = FALSE;
				$dom->loadXML($this->getGlobalLayoutByElementType('ELEMENTWRAP'));
				if ($dom) {
					$node = $dom->firstChild;
					if ($node) {
						if ($node->getAttribute('class') !== '') {
							$class = $node->getAttribute('class') . ' ';
						}
						$class .= 'csc-form-' . $element->getElementCounter() . ' csc-form-element csc-form-element-' . $element->getElementTypeLowerCase();
						$node->setAttribute('class', $class);
						$elementOuterWrap = $dom->saveXML($dom->firstChild);
						$return = $this->replaceTagWithMarker('element', 'body', $elementOuterWrap);
						if ($return['marker'] !== '') {
							$elementOuterWrap = explode($return['marker'], $return['html']);
						} else {
							/* this should never be happen */
							$elementOuterWrap = NULL;
						}
					}
				} else {
					$elementOuterWrap = NULL;
				}
			}
			if (
				$elementWrap
				&& !$elementOuterWrap
			) {
				/* If only $elementWrap isset */
				$layout = $element->getLayout();
				$layout['elementOuterWrap'] = $elementWrap;
				$element->setLayout($layout);
			} else if (
				!$elementWrap
				&& $elementOuterWrap
			) {
				/* If only $elementOuterWrap isset */
				$layout = $element->getLayout();
				$layout['elementOuterWrap'] = $elementOuterWrap;
				$element->setLayout($layout);
			} else if (
				$elementWrap
				&& $elementOuterWrap
			) {
				/* If $elementWrap isset and $elementOuterWrap isset */
				$elementWrap = array(
					$elementOuterWrap[0] . $elementWrap[0],
					$elementWrap[1] . $elementOuterWrap[1],
				);
				$layout = $element->getLayout();
				$layout['elementOuterWrap'] = $elementWrap;
				$element->setLayout($layout);
			}
			/* Set container inner wraps */
			if (in_array($element->getElementType(), $this->containerElements)) {
				$elementWrap = $this->determineElementOuterWraps($element->getElementType(), $elementLayout);
				$containerOuterWrap = array('', '');
				if ($elementWrap['marker'] !== '') {
					$containerOuterWrap = explode($elementWrap['marker'], $elementWrap['html']);
				}
				$containerWrapReturn = $this->replaceTagWithMarker('elements', 'body', $this->getGlobalLayoutByElementType('CONTAINERWRAP'));
				$containerInnerWrap = explode($containerWrapReturn['marker'], $containerWrapReturn['html']);
				$containerWrap = array(
					$containerOuterWrap[0] . $containerInnerWrap[0],
					$containerInnerWrap[1] . $containerOuterWrap[1],
				);
				$layout = $element->getLayout();
				$layout['containerInnerWrap'] = $containerWrap;
				$element->setLayout($layout);
				$classFromLayout = $this->getElementClassFromLayout('fieldset');
				if (!empty($classFromLayout)) {
					if (!empty($element->getHtmlAttribute('class'))) {
						$classFromLayout .= ' ' . $element->getHtmlAttribute('class');
					}
					$element->setHtmlAttribute('class', $classFromLayout);
				}
			}
			return;
		}
	}

	/**
	 * Replace the message sections of a label.
	 * The scopes can be mandatory or error.
	 *
	 * @param string $scope
	 * @param array $messages
	 * @return string $html
	 */
	protected function replaceLabelContent($scope = '', array $messages) {
		$messages = implode(' - ', $messages);
		$return = $this->replaceTagWithMarker($scope . 'value', 'body', $this->getGlobalLayoutByElementType(strtoupper($scope)));
		$html = str_replace($return['marker'], $messages, $return['html']);
		return $html;
	}

	/**
	 * Return the class attribute for a element defined by layout.
	 *
	 * @param string $elementName
	 * @return string
	 */
	protected function getElementClassFromLayout($elementName = '') {
		$class = '';
		$libxmlUseInternalErrors = libxml_use_internal_errors(true);
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->formatOutput = TRUE;
		$dom->preserveWhiteSpace = FALSE;
		if ($dom->loadXML($this->getGlobalLayoutByElementType(strtoupper($elementName)))) {
			$nodes = $dom->getElementsByTagName($elementName);
			if ($nodes->length) {
				$node = $nodes->item(0);
				if ($node && $node->getAttribute('class') !== '') {
					$class = $node->getAttribute('class');
				}
			}
		}
		libxml_use_internal_errors($libxmlUseInternalErrors);
		return $class;
	}

	/**
	 * Try to explode the element layout into 2 parts to get the
	 * outer wrapping
	 *
	 * @param string $elementType
	 * @return string $elementLayout
	 * @deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8, as the functionality is now done via fluid
	 */
	protected function determineElementOuterWraps($elementType, $elementLayout = '') {
		if ($elementType === 'TEXTAREA') {
			$return = $this->replaceTagWithMarker('textarea', 'body', $elementLayout);
		} elseif ($elementType === 'CONTENTELEMENT') {
			$return = $this->replaceTagWithMarker('content', 'body', $elementLayout);
		} elseif ($elementType === 'SELECT') {
			$return = $this->replaceTagWithMarker('select', 'body', $elementLayout);
		} elseif (in_array($elementType, $this->containerElements)) {
			$return = $this->replaceTagWithMarker('fieldset', 'body', $elementLayout);
		} else {
			$return = $this->replaceTagWithMarker('input', 'body', $elementLayout);
		}
		return $return;
	}

	/**
	 * Replace a html tag with a uniqe marker
	 *
	 * @param string $tagName
	 * @param string $stopTag
	 * @param string $html
	 * @return array
	 */
	protected function replaceTagWithMarker($tagName, $stopTag = 'body', $html = '') {
		if (
			$tagName === ''
			|| $html === ''
		) {
			return array(
				'html' => '',
				'marker' => ''
			);
		}
		libxml_use_internal_errors(true);
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dom->preserveWhiteSpace = FALSE;
		$dom->loadHTML($html);
		libxml_use_internal_errors(false);
		if (!$dom) {
			return array(
				'html' => '',
				'marker' => ''
			);
		}
		$nodes = $dom->getElementsByTagName($tagName);
		if (!$nodes->length) {
			return array(
				'html' => '',
				'marker' => ''
			);
		}
		$nodeToReplace = $nodes->item(0);
		/* Replace $tagname tag with a unique marker */
		$marker = '###' . uniqid() . '###';
		$markerNode = $dom->createTextNode($marker);
		$replaceNode = $dom->createDocumentFragment();
		$domNode = $dom->importNode($markerNode, TRUE);
		$replaceNode->appendChild($domNode);
		$parentNode = $nodeToReplace->parentNode;
		$parentNode->insertBefore($replaceNode, $nodeToReplace);
		$parentNode->removeChild($nodeToReplace);
		$nextParent = $parentNode;
		/* Do not save the stop tag */
		while($nextParent !== NULL) {
			if ($nextParent->tagName === $stopTag) {
				break;
			}
			$nextParent = $nextParent->parentNode;
		}
		$children = $nextParent->childNodes;
		$innerHtml = '';
		foreach ($children as $child) {
			$innerHtml .= $nextParent->ownerDocument->saveHTML($child);
		}
		return array(
			'html' => $innerHtml,
			'marker' => $marker
		);
	}

	/**
	 * Get new name for some old inconsistent attribute names
	 *
	 * @param string $elementType
	 * @param string $attributeName
	 * @return string
	 * @deprecated since TYPO3 CMS 7, this function will be removed in TYPO3 CMS 8, as the functionality is now done via fluid
	 */
	public function getNewAttributeName($elementType, $attributeName) {
		if ($elementType === 'OPTION') {
			if ($attributeName === 'data') {
				GeneralUtility::deprecationLog('EXT:form: Deprecated since TYPO3 CMS 7, use text instead of data to configure the OPTION text');
				$attributeName = 'text';
			}
		} elseif ($elementType === 'TEXTAREA') {
			if ($attributeName === 'data') {
				GeneralUtility::deprecationLog('EXT:form: Deprecated since TYPO3 CMS 7, use text instead of data to configure the TEXTAREA value');
				$attributeName = 'text';
			}
		} elseif ($elementType === 'TEXTBLOCK') {
			if ($attributeName === 'content') {
				GeneralUtility::deprecationLog('EXT:form: Deprecated since TYPO3 CMS 7, use text instead of content to configure the TEXTBLOCK value');
				$attributeName = 'text';
			}
		}
		return $attributeName;
	}
}
