<?php
declare(ENCODING = 'utf-8');
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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

require_once(PATH_tslib . 'class.tslib_content.php');
require_once(PATH_t3lib . 'class.t3lib_parsehtml.php');

/**
 * A basic Template View
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 * @scope prototype
 */
class TX_EXTMVC_View_TemplateView extends TX_EXTMVC_View_AbstractView {

	/**
	 * Pattern for fetching information from controller object name
	 * @var string
	 */
	const PATTERN_CONTROLLER = '/^TX_\w*_Controller_(?P<ControllerName>\w*)Controller$/sm';

	const SCAN_PATTERN_SUBPARTS = '/<!--\s*###(?P<SubpartName>[^#]*)###.*?-->(?P<SubpartTemplateSource>.*?)<!--\s*###(?P=SubpartName)###.*?-->/sm';
	const SCAN_PATTERN_MARKER = '/###(?P<MarkerName>.*?)###/sm';

	const SPLIT_PATTERN_MARKER = '/^(?:(?P<ViewHelperName>[a-zA-Z0-9_]+):)?(?P<ContextVariable>(?:\s*[a-zA-Z0-9_]+)(?=(\s|$)))?(?P<ObjectAndProperty>(?:\s*[a-zA-Z0-9_]+\.(?:[a-zA-Z0-9_]+)(?=(\s|$))))?(?P<Attributes>(?:\s*[a-zA-Z0-9_]+=(?:"(?:[^"])*"|\'(?:[^\'])*\'|\{(?:[^\{])*\}|[a-zA-Z0-9_\.]+)\s*)*)\s*$/';
	const SPLIT_PATTERN_ARGUMENTS = '/(?P<ArgumentKey>[a-zA-Z][a-zA-Z0-9_]*)=(?:(?:"(?P<ValueDoubleQuoted>[^"]+)")|(?:\'(?P<ValueSingleQuoted>[^\']+)\')|(?:\{(?P<ValueObject>[^\'\s]+)\})|(?:(?P<ValueUnquoted>[^"\'\s]*)))/';

	/**
	 * File pattern for resolving the template file
	 * @var string
	 */
	protected $templateFilePattern = 'Resources/Private/Templates/@controller/@action.html';

	/**
	 * @var array Marker uids and their replacement content
	 */
	protected $markers = array();

	/**
	 * @var array Subparts
	 */
	protected $subparts = array();

	/**
	 * @var array Wrapped subparts
	 */
	protected $wrappedSubparts = array();

	/**
	 * Context variables
	 * @var array of context variables
	 */
	protected $contextVariables = array();

	/**
	 * Template file path. If set, overrides the templateFilePattern
	 * @var string
	 */
	protected $templateFile = NULL;

	/**
	 * @var string
	 */
	protected $templateSource = '';

	/**
	 * Name of current action to render
	 * @var string
	 */
	protected $actionName;

	/**
	 * The content object
	 *
	 * @var tslib_cObj
	 **/
	private $cObj;
	
	public function __construct() {
		$this->initializeView();
	}

	/**
	 * Initialize view
	 *
	 * @return void
	 */
	protected function initializeView() {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * Sets the template file. Effectively overrides the dynamic resolving of a template file.
	 *
	 * @param string $templateFile Template file path
	 * @return void
	 */
	public function setTemplateFile($templateFile) {
		$this->templateFile = $templateFile;
	}
	
	/**
	 * Sets the text source which contains the markers of this template view
	 * is going to fill in.
	 *
	 * @param string $templateSource The template source
	 * @return void
	 */
	public function setTemplateSource($templateSource) {
		$this->templateSource = $templateSource;
	}

	/**
	 * Resolve the template file path, based on $this->templateFilePath and $this->templatePathPattern.
	 * In case a template has been set with $this->setTemplateFile, it just uses the given template file.
	 * Otherwise, it resolves the $this->templatePathPattern
	 *
	 * @param string $action Name of action. Optional. Defaults to current action.
	 * @return string File name of template file
	 */
	protected function resolveTemplateFile() {
		if ($this->templateFile) {
			return $this->templateFile;
		} else {
			$action = ($this->actionName ? $this->actionName : $this->request->getControllerActionName());
			preg_match(self::PATTERN_CONTROLLER, $this->request->getControllerObjectName(), $matches);
			$controllerName = $matches['ControllerName'];
			$templateFile = $this->templateFilePattern;
			$templateFile = str_replace('@controller', $controllerName, $templateFile);
			$templateFile = str_replace('@action', strtolower($action), $templateFile);
			return $templateFile;
		}
	}

	/**
	 * Load the given template file.
	 *
	 * @param string $templateFilePath Full path to template file to load
	 * @return string the contents of the template file
	 */
	protected function loadTemplateFile($templateFilePath) {
		$templateSource = file_get_contents(t3lib_extMgm::extPath(strtolower($this->request->getControllerExtensionKey())) . $templateFilePath, FILE_TEXT);
		if (!$templateSource) throw new RuntimeException('The template file "' . $templateFilePath . '" was not found.', 1225709595); // TODO Specific exception
		return $templateSource;
	}

	/**
	 * Find the XHTML template according to $this->templatePathPattern and render the template.
	 *
	 * @return string Rendered Template
	 */
	public function render() {
		if ($this->templateSource == '') {
			$templateFileName = $this->resolveTemplateFile();
			$templateSource = $this->loadTemplateFile($templateFileName);
		} else {
			$templateSource = $this->templateSource;
		}
		// TODO exception if a template was not defined
		$content = $this->renderTemplate($templateSource, $this->contextVariables);
		$this->removeUnfilledMarkers($content);
		return $content;
	}

	/**
	 * Recursive rendering of a given template source.
	 *
	 * @param string $templateSource The template source
	 * @return void
	 */
	public function renderTemplate($templateSource, $variables) {
		$subpartArray = array();
		$subparts = $this->getSubparts($templateSource);
		foreach ($subparts as $subpartMarker => $subpartSource) {
			$subpartArray['###' . $subpartMarker . '###'] = $this->getMarkerContent($subpartMarker, $variables, $subpartSource);
		}
		// $content = $this->cObj->substituteMarkerArrayCached($templateSource, $markerArray, $subpartArray, $wrappedSubpartArray);
		$markerArray = array();
		$markers = $this->getMarkers($templateSource);
		foreach ($markers as $marker => $foo) {
			$markerArray['###' . $marker . '###'] = $this->getMarkerContent($marker, $variables);
		}
		$content = $this->cObj->substituteMarkerArrayCached($templateSource, $markerArray, $subpartArray, $wrappedSubpartArray);

		return $content;
	}

	public function getMarkerArray($templateSource, $value = NULL) {
		$markers = $this->getMarkers($templateSource);
		$markerArray = array();
		foreach ($markers as $marker => $foo) {
			$markerArray['###' . $marker . '###'] = $this->getMarkerContent($marker, $value);
		}
		return $markerArray;
	}
		
	protected function getMarkerContent($marker, $variables = NULL, $templateSource = NULL) {
		preg_match(self::SPLIT_PATTERN_MARKER, $marker, $explodedMarker);
		$viewHelperName = TX_EXTMVC_Utility_Strings::underscoredToUpperCamelCase($explodedMarker['ViewHelperName']);
		$contextVariable = TX_EXTMVC_Utility_Strings::underscoredToUpperCamelCase($explodedMarker['ContextVariable']);
		$explodedObjectAndProperty = explode('.', $explodedMarker['ObjectAndProperty']);
		$objectName = TX_EXTMVC_Utility_Strings::underscoredToUpperCamelCase($explodedObjectAndProperty[0]);
		$property = TX_EXTMVC_Utility_Strings::underscoredToLowerCamelCase($explodedObjectAndProperty[1]);
		if (!empty($explodedMarker['Attributes'])) {
			$arguments = $this->getArguments($explodedMarker['Attributes'], $variables);
		}
		if ($variables[$objectName] instanceof TX_EXTMVC_DomainObject_AbstractDomainObject) {
			$object = $variables[$objectName];
			$possibleMethodName = 'get' . $property;
			if (method_exists($object, $possibleMethodName)) {
				$content = $object->$possibleMethodName();
			}
		}
		
		if (!empty($viewHelperName)) {
			$viewHelperClassName = 'TX_EXTMVC_View_Helper_' . $viewHelperName . 'Helper';
			$viewHelper = $this->getViewHelper($viewHelperClassName);
			$content = $viewHelper->render($this, $content, $arguments, $templateSource, $variables);
		}
		return $content;
	}
	
	protected function getArguments($attributes, $variables) {
		preg_match_all(self::SPLIT_PATTERN_ARGUMENTS, $attributes, $explodedAttributes, PREG_SET_ORDER);		
		$arguments = array();
		foreach ($explodedAttributes as $explodedAttribute) {
			if (!empty($explodedAttribute['ValueDoubleQuoted'])) {
				 $argumentValue = $explodedAttribute['ValueDoubleQuoted'];
			} elseif (!empty($explodedAttribute['ValueSingleQuoted'])) {
				$argumentValue = $explodedAttribute['ValueSingleQuoted'];
			} elseif (!empty($explodedAttribute['ValueObject'])) {				
				$argumentValue = $this->getValueForVariableAndKey($explodedAttribute['ValueObject'], $variables);
			} elseif (!empty($explodedAttribute['ValueUnquoted'])) {
				$argumentValue = $this->getValueForVariableAndKey($explodedAttribute['ValueUnquoted'], $variables);
			} else {
				$argumentValue = NULL;
			}
			$arguments[TX_EXTMVC_Utility_Strings::underscoredToLowerCamelCase($explodedAttribute['ArgumentKey'])] = $argumentValue;
		}
		return $arguments;
	}
	
	public function replaceReferencesWithValues(&$theString, $variables) {
		preg_match_all('/(?:\{([^\s]*?)\})?/', $theString, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			if (count($match) > 1) {
				$reference = $match[0];
				$value = $this->getValueForVariableAndKey($match[1], $variables);
			}
			$theString = str_replace($reference, $value, $theString);
		}
	}
		
	public function getValueForVariableAndKey($variableAndKey, $variables) {
		$explodedVariableAndKey = explode('.', $variableAndKey);
		$variable = $variables[TX_EXTMVC_Utility_Strings::underscoredToUpperCamelCase($explodedVariableAndKey[0])];
		if (!empty($variable)) {
			if (count($explodedVariableAndKey) > 1) {
				$key = $explodedVariableAndKey[1];
				if (is_object($variable)) {
					$possibleMethodName = 'get' . TX_EXTMVC_Utility_Strings::underscoredToUpperCamelCase($key);
					if (method_exists($variable, $possibleMethodName)) {
						$value = $variable->$possibleMethodName();
					}
				} elseif (is_array($variable)) {
					$value = $variable[TX_EXTMVC_Utility_Strings::underscoredToLowerCamelCase($key)];
				}
			} else {
				if (is_object($variable)) {
					$value = $variable->__toString();
				} else {
					$value = $variable;
				}
			}
		}
		return $value;
	}

	protected function getSubpartArray($templateSource) {
		$subpartArray = array();
		if (count($subparts) > 0) {
			foreach ($subparts as $subpartMarker => $subpartTemplateSource) {
				$value = $this->getMarkerContent($subpartMarker);
				$subpartArray['###' . $subpartMarker . '###'] .= $this->renderTemplate($subpartTemplateSource, $value);
			}
		}
		return $subpartArray;
	}
		
	protected function getSubparts($templateSource) {
		preg_match_all(self::SCAN_PATTERN_SUBPARTS, $templateSource, $matches, PREG_SET_ORDER);
		$subparts = array();
		if (is_array($matches)) {
			foreach ($matches as $key => $match) {
				$subparts[$match['SubpartName']] = $match['SubpartTemplateSource'];
			}
		}
		return $subparts;
	}

	protected function getMarkers($templateSource) {
		preg_match_all(self::SCAN_PATTERN_MARKER, $templateSource, $matches, PREG_SET_ORDER);
		$markers = array();
		if (is_array($matches)) {
			foreach ($matches as $key => $match) {
				$markers[$match['MarkerName']] = NULL;
			}
		}
		return $markers;
	}
			
	protected function removeUnfilledMarkers(&$content) {
		$content = preg_replace('/###.*###|<!--[^>]*###.*###[^<]*-->(.*)/msU', '', $content);
	}

	/**
	 * Assigns domain models (single objects or aggregates) or values to the view
	 *
	 * @param string $valueName The name of the value
	 * @param mixed $value the value to assign
	 * @return void
	 */
	public function assign($key, $value) {
		$this->contextVariables[$key] = $value;
		return $this;
	}
}
?>