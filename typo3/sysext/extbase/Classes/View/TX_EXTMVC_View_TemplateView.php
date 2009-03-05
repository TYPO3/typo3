<?php

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
			preg_match('/^TX_\w*_Controller_(?P<ControllerName>\w*)Controller$/', $this->request->getControllerObjectName(), $matches);
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
	 * @param string $action: If given, renders this action instead.
	 * @return string Rendered Template
	 */
	public function render($action = NULL) {
		if ($this->templateSource == '') {
			$this->actionName = $action;
			$templateFileName = $this->resolveTemplateFile();
			$templateSource = $this->loadTemplateFile($templateFileName);
		} else {
			$templateSource = $this->templateSource;
		}
		$content = $this->renderTemplate('template', $templateSource);
		// $this->removeUnfilledMarkers($content);
		return $content;
	}

	/**
	 * Recursive rendering of a given template source.
	 *
	 * @param string $templateSource The template source
	 * @return void
	 */
	protected function renderTemplate($templateName, $templateSource, $value = NULL) {
		$markerArray = $this->getMarkerArray($templateName, $templateSource, $value);
		$subpartArray = $this->getSubpartArray($templateName, $templateSource, $value);

		// debug($templateSource,-2);
		// debug($markerArray, 'markerArray');
		// debug($subpartArray,'subpartArray');
		$content = $this->cObj->substituteMarkerArrayCached($templateSource, $markerArray, $subpartArray, $wrappedSubpartArray);
		return $content;
	}

	public function getMarkerArray($templateName, $templateSource, $value) {
		$markers = $this->getMarkers($templateSource);
		$markerArray = array();
		foreach ($markers as $markerName => $markerContent) {
			$markerArray['###' . $markerName . '###'] = $this->getMarkerContent($markerName, $value);
		}
		return $markerArray;
	}
		
	protected function getMarkerContent($marker, $value) {
		$explodedMarker = explode(':', $marker);
		if (count($explodedMarker) == 2) {
			$command = $explodedMarker[0];
			$markerName = $explodedMarker[1];
		} else {
			$markerName = $marker;
		}

		if ($command === 'LLL') {
			$result = $this->translate(strtolower($markerContent));
		}
		// FIXME
		return $this->getValueForMarker($markerName, $value);
	}
	
	protected function getSubpartArray($templateName, $templateSource, $value) {
		$subparts = $this->getSubparts($templateSource);
		$subpartArray = array();
		if (count($subparts) > 0) {
			foreach ($subparts as $subpartMarker => $subpartTemplateSource) {
				$value = $this->getValueForMarker($subpartMarker, $value);
				if (is_array($value) || ($value instanceof ArrayObject)) {
					foreach ($value as $key => $innerValue) {
						$subpartArray['###' . $subpartMarker . '###'] .= $this->renderTemplate($subpartName, $subpartTemplateSource, $innerValue);
					}
				}
			}
		}
		return $subpartArray;
	}
	
	protected function getValueForMarker($markerName, $value) {
		$explodedMarkerName = explode('.', $markerName);
		if ($value === NULL) {
				$value = $this->contextVariables[strtolower($explodedMarkerName[0])];
		}
		if ($value instanceof TX_EXTMVC_DomainObject_AbstractDomainObject) {
			$possibleMethodName = 'get' . TX_EXTMVC_Utility_Strings::underscoredToUpperCamelCase($explodedMarkerName[1]);
			if (method_exists($value, $possibleMethodName)) {
				$result = $value->$possibleMethodName();
			}
		} else {
		}
		return $this->convertValue($result);
	}
	
	protected function getSubparts($templateSource) {
		preg_match_all('/<!--\s*###(?P<SubpartName>[A-Z0-9_-|:.]*)###.*-->(?P<SubpartTemplateSource>.*)<!--\s*###\k<SubpartName>###.*-->/msU', $templateSource, $matches, PREG_SET_ORDER);
		$subparts = array();
		if (is_array($matches)) {
			foreach ($matches as $key => $match) {
				$subparts[$match['SubpartName']] = $match['SubpartTemplateSource'];
			}
		}
		return $subparts;
	}

	protected function getMarkers($templateSource) {
		preg_match_all('/###(?P<MarkerName>[A-Z0-9_-|:.]*)###(?![^>]*-->)/msU', $templateSource, $matches, PREG_SET_ORDER);
		$markers = array();
		if (is_array($matches)) {
			foreach ($matches as $key => $match) {
				$markers[$match['MarkerName']] = NULL;
			}
		}
		return $markers;
	}
	
	protected function convertValue($value) {
		if ($value instanceof DateTime) {
			$value = $value->format('Y-m-d G:i'); // TODO Date time format from extension settings
		}
		return $value;
	}
		
	protected function removeUnfilledMarkers(&$content) {
		// TODO remove also comments
		$content = preg_replace('/###.*###/msU', '', $content);
	}

	/**
	 * Add a variable to the context.
	 * Can be chained, so $template->addVariable(..., ...)->addVariable(..., ...); is possible,
	 *
	 * @param string $key Key of variable
	 * @param object $value Value of object
	 * @return TX_EXTMVC_View_TemplateView an instance of $this, to enable chaining.
	 */
	public function assign($key, $value) {
		$this->contextVariables[$key] = $value;
		return $this;
	}	
}
?>