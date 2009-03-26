<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package
 * @subpackage
 * @version $Id:$
 */

class Tx_Fluid_View_TemplateView extends Tx_ExtBase_View_ViewInterface {

	protected $templateParser;

	protected $objectFactory;

	public function __construct() {
		$this->templateParser = Tx_Fluid_Compatibility_TemplateParserBuilder::build();
		$this->objectFactory = t3lib_div::makeInstance('Tx_Fluid_Compatibility_ObjectFactory');
	}
	/**
	 * Pattern for fetching information from controller object name
	 * @var string
	 */
	const PATTERN_CONTROLLER = '/^Tx_\w*_(?:(?P<SubpackageName>.*)_)?Controller_(?P<ControllerName>\w*)Controller$/';

	/**
	 * File pattern for resolving the template file
	 * @var string
	 */
	protected $templatePathAndFilenamePattern = '@packageResources/Private/Templates/@subpackage@controller/@action.html';

	/**
	 * Directory pattern for global partials. Not part of the public API, should not be changed for now.
	 * @var string
	 * @internal
	 */
	private $globalPartialBasePath = '@packageResources/Private/Templates';

	/**
	 * @var array
	 */
	protected $contextVariables = array();

	/**
	 * Path and filename of the template file. If set,  overrides the templatePathAndFilenamePattern
	 * @var string
	 */
	protected $templatePathAndFilename = NULL;

	/**
	 * Path and filename of the layout file. If set, overrides the layoutPathAndFilenamePattern
	 * @var string
	 */
	protected $layoutPathAndFilename = NULL;

	/**
	 * Name of current action to render
	 * @var string
	 */
	protected $actionName;

	/**
	 * Initialize view
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function initializeView() {
		$this->contextVariables['view'] = $this;
	}

	/**
	 * Sets the path and name of of the template file. Effectively overrides the
	 * dynamic resolving of a template file.
	 *
	 * @param string $templatePathAndFilename Template file path
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setTemplatePathAndFilename($templatePathAndFilename) {
		$this->templatePathAndFilename = $templatePathAndFilename;
	}


	/**
	 * Find the XHTML template according to $this->templatePathAndFilenamePattern and render the template.
	 * If "layoutName" is set in a PostParseFacet callback, it will render the file with the given layout.
	 *
	 * @param string $actionName If set, the view of the specified action will be rendered instead. Default is the action specified in the Request object
	 * @return string Rendered Template
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render($actionName = NULL) {
		$this->actionName = $actionName;

		$parsedTemplate = $this->parseTemplate($this->resolveTemplatePathAndFilename());

		$variableContainer = $parsedTemplate->getVariableContainer();
		if ($variableContainer !== NULL && $variableContainer->exists('layoutName')) {
			return $this->renderWithLayout($variableContainer->get('layoutName'));
		}
		$templateTree = $parsedTemplate->getRootNode();
		return $templateTree->render($this->objectFactory->create('Tx_Fluid_Core_VariableContainer', $this->contextVariables));
	}

	/**
	 * Renders a partial. If $partialName starts with /, the partial is resolved globally. Else, locally.
	 * SHOULD NOT BE USED BY USERS!
	 * @internal
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
/*	public function renderPartial($partialName, $sectionToRender, array $variables) {
		if ($partialName[0] === '/') {
			$partialBasePath = str_replace('@package', $this->packageManager->getPackagePath($this->request->getControllerPackageKey()), $this->globalPartialBasePath);
			$partialName = substr($partialName, 1);
		} else {
			$partialBasePath = dirname($this->resolveTemplatePathAndFilename());
		}
		$partialNameSplitted = explode('/', $partialName);
		$partialFileName = '_' . array_pop($partialNameSplitted) . '.html';
		$partialDirectoryName = $partialBasePath . '/' . implode('/', $partialNameSplitted);

		$partialPathAndFileName = $partialDirectoryName . '/' . $partialFileName;

		$partial = $this->parseTemplate($partialPathAndFileName);
		$syntaxTree = $partial->getRootNode();

		$variables['view'] = $this;
		$variableStore = $this->objectFactory->create('F3\Fluid\Core\VariableContainer', $variables);

		if ($sectionToRender != NULL) {
			$sections = $partial->getVariableContainer()->get('sections');
			if(!array_key_exists($sectionToRender, $sections)) throw new \F3\Fluid\Core\RuntimeException('The given section does not exist!', 1227108983);
			$result = $sections[$sectionToRender]->render($variableStore);
		} else {
			$result = $syntaxTree->render($variableStore);
		}
		return $result;
	}*/

	/**
	 * Add a variable to the context.
	 * Can be chained, so $template->addVariable(..., ...)->addVariable(..., ...); is possible,
	 *
	 * @param string $key Key of variable
	 * @param object $value Value of object
	 * @return \F3\Fluid\View\TemplateView an instance of $this, to enable chaining.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function assign($key, $value) {
		if ($key === 'view') throw new Tx_Fluid_Core_RuntimeException('The variable "view" cannot be set using assign().', 1233317880);
		$this->contextVariables[$key] = $value;
		return $this;
	}

	/**
	 * Return the current request
	 *
	 * @return \F3\FLOW3\MVC\Web\Request the current request
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Parse the given template and return it.
	 *
	 * Will cache the results for one call.
	 *
	 * @param $templatePathAndFilename absolute filename of the template to be parsed
	 * @return \F3\Fluid\Core\ParsedTemplateInterface the parsed template tree
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function parseTemplate($templatePathAndFilename) {
		$templateSource = file_get_contents($templatePathAndFilename, FILE_TEXT);
		if ($templateSource === FALSE) {
			throw new Tx_Fluid_Core_RuntimeException('The template file "' . $templatePathAndFilename . '" could not be loaded.', 1225709595);
		}
		return $this->templateParser->parse($templateSource);
	}

	/**
	 * Resolve the path and name of the template, based on $this->templatePathAndFilename and $this->templatePathAndFilenamePattern.
	 * In case a template has been set with $this->setTemplatePathAndFilename, it just uses the given template file.
	 * Otherwise, it resolves the $this->templatePathAndFilenamePattern
	 *
	 * @return string Path and filename of template file
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function resolveTemplatePathAndFilename() {
		if ($this->templatePathAndFilename !== NULL) {
			return $this->templatePathAndFilename;
		} else {
			$actionName = ($this->actionName !== NULL ? $this->actionName : $this->request->getControllerActionName());
			preg_match(self::PATTERN_CONTROLLER, $this->request->getControllerObjectName(), $matches);
			$subpackageName = '';
			if ($matches['SubpackageName'] !== '') {
				$subpackageName = str_replace('\\', '/', $matches['SubpackageName']);
				$subpackageName .= '/';
			}
			$controllerName = $matches['ControllerName'];
			$templatePathAndFilename = str_replace('@package', $this->packageManager->getPackagePath($this->request->getControllerPackageKey()), $this->templatePathAndFilenamePattern);
			$templatePathAndFilename = str_replace('@subpackage', $subpackageName, $templatePathAndFilename);
			$templatePathAndFilename = str_replace('@controller', $controllerName, $templatePathAndFilename);
			$templatePathAndFilename = str_replace('@action', strtolower($actionName), $templatePathAndFilename);

			return $templatePathAndFilename;
		}
	}
}
?>