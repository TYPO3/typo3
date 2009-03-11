<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
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

require_once(PATH_t3lib . 'class.t3lib_parsehtml.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Utility/TX_EXTMVC_Utility_Strings.php');

/**
 * A basic Template View
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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

	const SPLIT_PATTERN_MARKER = '/^(?:(?P<ViewHelperName>[a-zA-Z0-9_]+):)?(?P<ContextVariable>(?:\s*[a-zA-Z0-9_]+)(?=(\s|$)))?(?P<ObjectAndProperty>(?:\s*[a-zA-Z0-9_]+\.(?:[a-zA-Z0-9_]+)(?=(\s|$))))?(?P<Attributes>(?:\s*[a-zA-Z0-9_]+=(?:"(?:[^"])*"|\'(?:[^\'])*\'|[a-zA-Z0-9_\.]+)\s*)*)\s*$/';
	const SPLIT_PATTERN_ARGUMENTS = '/(?P<ArgumentKey>[a-zA-Z][a-zA-Z0-9_]*)=(?:(?:"(?P<ValueDoubleQuoted>[^"\s]+)")|(?:\'(?P<ValueSingleQuoted>[^\'\s]+)\')|(?:(?P<ValueUnquoted>[^"\'\s]*)))/';

	/**
	 * File pattern for resolving the template file
	 * @var string
	 */
	protected $templateFilePattern = 'Resources/Template/@controller/@action.xhtml';

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
			$this->actionName = $action;
			$templateFileName = $this->resolveTemplateFile();
			$templateSource = $this->loadTemplateFile($templateFileName);
		} else {
			$templateSource = $this->templateSource;
		}
		// TODO exception if a template was not defined
		$content = $this->renderTemplate($templateSource, $this->contextVariables);
		// $this->removeUnfilledMarkers($content);
		return $content;
	}

	/**
	 * Recursive rendering of a given template source.
	 *
	 * @param string $templateSource The template source
	 * @return void
	 */
	protected function renderTemplate($templateSource, $variables) {
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
		
		if ($viewHelperName === 'Convert') {			
			if (!empty($arguments['format'])) {
				$format = $arguments['format'];
			} else {
				$format = NULL;
			}
		}
		
		if ($viewHelperName === 'For') {		
			if (is_array($arguments['each'])) {
				foreach ($arguments['each'] as $singleElement) {
					$variables[TX_EXTMVC_Utility_Strings::underscoredToUpperCamelCase($arguments['as'])] = $singleElement; // FIXME strtolower
					$content .= $this->renderTemplate($templateSource, $variables);
				}
			}
		}
		return $this->convertValue($content, $format);
	}
	
	protected function getArguments($attributes, $variables) {
		preg_match_all(self::SPLIT_PATTERN_ARGUMENTS, $attributes, $explodedAttributes, PREG_SET_ORDER);
		$arguments = array();
		foreach ($explodedAttributes as $explodedAttribute) {
			if (!empty($explodedAttribute['ValueDoubleQuoted'])) {
				 $argumentValue = $explodedAttribute['ValueDoubleQuoted'];
			} elseif (!empty($explodedAttribute['ValueSingleQuoted'])) {
				$argumentValue = $explodedAttribute['ValueSingleQuoted'];
			} elseif (!empty($explodedAttribute['ValueUnquoted'])) {
				$explodedValue = explode('.', $explodedAttribute['ValueUnquoted']);
				if (count($explodedValue) > 1) {											
					$possibleMethodName = 'get' . TX_EXTMVC_Utility_Strings::underscoredToUpperCamelCase($explodedValue[1]);
					$argumentValueObject = $variables[TX_EXTMVC_Utility_Strings::underscoredToUpperCamelCase($explodedValue[0])];
					if (method_exists($argumentValueObject, $possibleMethodName)) {
						$argumentValue = $argumentValueObject->$possibleMethodName();
					}
				} else {
					$argumentValue = $variables[$explodedValue[0]];
				}
			} else {
				$argumentValue = NULL;
			}
			$arguments[TX_EXTMVC_Utility_Strings::underscoredToLowerCamelCase($explodedAttribute['ArgumentKey'])] = $argumentValue;
		}
		return $arguments;
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
	
	/**
	 * Resolve a view helper.
	 *
	 * @param string $namespaceIdentifier Namespace identifier for the view helper.
	 * @param string $methodIdentifier Method identifier, might be hierarchical like "link.url"
	 * @return array An Array where the first argument is the object to call the method on, and the second argument is the method name
	 */
	protected function resolveViewHelperClassName($viewHelperName) {
		$className = '';
		$className = ucfirst($explodedViewHelperName[0]);
		$className .= 'ViewHelper';

		$name =  'TX_Blogexample_View_' . $className;

		return $name;
	}


	
	protected function convertValue($value, $format = NULL) {
		if (!is_string($format)) ; // TODO Throw exception?
		if ($value instanceof DateTime) {
			if ($format === NULL) {
				$value = $value->format('Y-m-d G:i'); // TODO Date time format from extension settings
			} else {
				$value = $value->format($format);
			}
		} else {
		}
		return $value;
	}
		
	protected function removeUnfilledMarkers(&$content) {
		// TODO remove also comments
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
	}
}
?>