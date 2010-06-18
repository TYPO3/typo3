<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
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

/**
 * The main template view. Should be used as view if you want Fluid Templating
 *
 * @version $Id: TemplateView.php 2043 2010-03-16 08:49:45Z sebastian $
 * @package Fluid
 * @subpackage View
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_View_TemplateView extends Tx_Extbase_MVC_View_AbstractView implements Tx_Fluid_View_TemplateViewInterface {

	/**
	 * @var Tx_Fluid_Core_Parser_TemplateParser
	 */
	protected $templateParser;

	/**
	 * Pattern to be resolved for @templateRoot in the other patterns.
	 * @var string
	 */
	protected $templateRootPathPattern = '@packageResourcesPath/Private/Templates';

	/**
	 * Pattern to be resolved for @partialRoot in the other patterns.
	 * @var string
	 */
	protected $partialRootPathPattern = '@packageResourcesPath/Private/Partials';

	/**
	 * Pattern to be resolved for @layoutRoot in the other patterns.
	 * @var string
	 */
	protected $layoutRootPathPattern = '@packageResourcesPath/Private/Layouts';

	/**
	 * Path to the template root. If NULL, then $this->templateRootPathPattern will be used.
	 * @var string
	 */
	protected $templateRootPath = NULL;

	/**
	 * Path to the partial root. If NULL, then $this->partialRootPathPattern will be used.
	 * @var string
	 */
	protected $partialRootPath = NULL;

	/**
	 * Path to the layout root. If NULL, then $this->layoutRootPathPattern will be used.
	 * @var string
	 */
	protected $layoutRootPath = NULL;

	/**
	 * File pattern for resolving the template file
	 * @var string
	 */
	protected $templatePathAndFilenamePattern = '@templateRoot/@controller/@action.@format';

	/**
	 * Directory pattern for global partials. Not part of the public API, should not be changed for now.
	 * @var string
	 */
	private $partialPathAndFilenamePattern = '@partialRoot/@partial.@format';

	/**
	 * File pattern for resolving the layout
	 * @var string
	 */
	protected $layoutPathAndFilenamePattern = '@layoutRoot/@layout.@format';

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

	public function __construct() {
						$this->templateParser = Tx_Fluid_Compatibility_TemplateParserBuilder::build();
						$this->objectManager = t3lib_div::makeInstance('Tx_Fluid_Compatibility_ObjectManager');
					}
	// Here, the backporter can insert a constructor method, which is needed for Fluid v4.

	/**
	 * Inject the template parser
	 *
	 * @param Tx_Fluid_Core_Parser_TemplateParser $templateParser The template parser
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectTemplateParser(Tx_Fluid_Core_Parser_TemplateParser $templateParser) {
		$this->templateParser = $templateParser;
	}

	/**
	 * Initialize view
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function initializeView() {
	}

	/**
	 * Sets the path and name of of the template file. Effectively overrides the
	 * dynamic resolving of a template file.
	 *
	 * @param string $templatePathAndFilename Template file path
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function setTemplatePathAndFilename($templatePathAndFilename) {
		$this->templatePathAndFilename = $templatePathAndFilename;
	}

	/**
	 * Sets the path and name of the layout file. Overrides the dynamic resolving of the layout file.
	 *
	 * @param string $layoutPathAndFilename Path and filename of the layout file
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function setLayoutPathAndFilename($layoutPathAndFilename) {
		$this->layoutPathAndFilename = $layoutPathAndFilename;
	}

	/**
	 * Build the rendering context
	 *
	 * @param Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer
	 * @return Tx_Fluid_Core_Rendering_RenderingContext
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function buildRenderingContext(Tx_Fluid_Core_ViewHelper_TemplateVariableContainer $variableContainer = NULL) {
		if ($variableContainer === NULL) {
			$variableContainer = $this->objectManager->create('Tx_Fluid_Core_ViewHelper_TemplateVariableContainer', $this->variables);
		}

		$renderingContext = $this->objectManager->create('Tx_Fluid_Core_Rendering_RenderingContext');
		$renderingContext->setTemplateVariableContainer($variableContainer);
		if ($this->controllerContext !== NULL) {
			$renderingContext->setControllerContext($this->controllerContext);
		}

		$viewHelperVariableContainer = $this->objectManager->create('Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer');
		$viewHelperVariableContainer->setView($this);
		$renderingContext->setViewHelperVariableContainer($viewHelperVariableContainer);

		return $renderingContext;
	}

	/**
	 * Build parser configuration
	 *
	 * @return Tx_Fluid_Core_Parser_Configuration
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function buildParserConfiguration() {
		$parserConfiguration = $this->objectManager->create('Tx_Fluid_Core_Parser_Configuration');
		if ($this->controllerContext->getRequest()->getFormat() === 'html') {
			$parserConfiguration->addInterceptor($this->objectManager->get('Tx_Fluid_Core_Parser_Interceptor_Escape'));
			
		}
		return $parserConfiguration;
	}

	/**
	 * Find the XHTML template according to $this->templatePathAndFilenamePattern and render the template.
	 * If "layoutName" is set in a PostParseFacet callback, it will render the file with the given layout.
	 *
	 * @param string $actionName If set, the view of the specified action will be rendered instead. Default is the action specified in the Request object
	 * @return string Rendered Template
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function render($actionName = NULL) {
		$templatePathAndFilename = $this->resolveTemplatePathAndFilename($actionName);

		$this->templateParser->setConfiguration($this->buildParserConfiguration());
		$parsedTemplate = $this->parseTemplate($templatePathAndFilename);

		$variableContainer = $parsedTemplate->getVariableContainer();
		if ($variableContainer !== NULL && $variableContainer->exists('layoutName')) {
			return $this->renderWithLayout($variableContainer->get('layoutName'));
		}

		return $parsedTemplate->render($this->buildRenderingContext());
	}

	/**
	 * Resolve the template path and filename for the given action. If $actionName
	 * is NULL, looks into the current request.
	 *
	 * @param string $actionName Name of the action. If NULL, will be taken from request.
	 * @return string Full path to template
	 * @throws Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function resolveTemplatePathAndFilename($actionName = NULL) {
		if ($this->templatePathAndFilename !== NULL) {
			return $this->templatePathAndFilename;
		}

		$actionName = ($actionName !== NULL ? $actionName : $this->controllerContext->getRequest()->getControllerActionName());
		$actionName = ucfirst($actionName);

		$paths = $this->expandGenericPathPattern($this->templatePathAndFilenamePattern, FALSE, FALSE);

		foreach ($paths as &$path) {
			// TODO remove fallback to lower case template files after grace period
			$fallbackPath = str_replace('@action', strtolower($actionName), $path);
			$path = str_replace('@action', $actionName, $path);
			if (file_exists($path)) {
				return $path;
			} else {
				if (file_exists($fallbackPath)) {
					t3lib_div::deprecationLog('the template filename "' . $fallbackPath . '" is lowercase. This is deprecated since TYPO3 4.4. Please rename the template to "' . basename($path) . '"');
					return $fallbackPath;
				}
			}
		}
		throw new Tx_Fluid_View_Exception_InvalidTemplateResourceException('Template could not be loaded. I tried "' . implode('", "', $paths) . '"', 1225709595);
	}

	/**
	 * Renders a given section.
	 *
	 * @param string $sectionName Name of section to render
	 * @return string rendered template for the section
	 * @throws Tx_Fluid_View_Exception_InvalidSectionException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderSection($sectionName) {
		$parsedTemplate = $this->parseTemplate($this->resolveTemplatePathAndFilename());

		$sections = $parsedTemplate->getVariableContainer()->get('sections');
		if(!array_key_exists($sectionName, $sections)) {
			throw new Tx_Fluid_View_Exception_InvalidSectionException('The given section does not exist!', 1227108982);
		}
		$section = $sections[$sectionName];

		$renderingContext = $this->buildRenderingContext();
		$section->setRenderingContext($renderingContext);
		return $section->evaluate();
	}

	/**
	 * Render a template with a given layout.
	 *
	 * @param string $layoutName Name of layout
	 * @return string rendered HTML
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderWithLayout($layoutName) {
		$parsedTemplate = $this->parseTemplate($this->resolveLayoutPathAndFilename($layoutName));

		$renderingContext = $this->buildRenderingContext();
		return $parsedTemplate->render($renderingContext);
	}

	/**
	 * Resolve the path and file name of the layout file, based on
	 * $this->layoutPathAndFilename and $this->layoutPathAndFilenamePattern.
	 *
	 * In case a layout has already been set with setLayoutPathAndFilename(),
	 * this method returns that path, otherwise a path and filename will be
	 * resolved using the layoutPathAndFilenamePattern.
	 *
	 * @param string $layoutName Name of the layout to use. If none given, use "default"
	 * @return string Path and filename of layout file
	 * @throws Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function resolveLayoutPathAndFilename($layoutName = 'default') {
		if ($this->layoutPathAndFilename) {
			return $this->layoutPathAndFilename;
		}

		$paths = $this->expandGenericPathPattern($this->layoutPathAndFilenamePattern, TRUE, TRUE);
		foreach ($paths as &$path) {
			$path = str_replace('@layout', $layoutName, $path);
			if (file_exists($path)) {
				return $path;
			}
		}
		throw new Tx_Fluid_View_Exception_InvalidTemplateResourceException('The template files "' . implode('", "', $paths) . '" could not be loaded.', 1225709595);
	}

	/**
	 * Renders a partial.
	 *
	 * @param string $partialName
	 * @param string $sectionToRender
	 * @param array $variables
	 * @param Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer $viewHelperVariableContainer the View Helper Variable container to use.
	 * @return string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function renderPartial($partialName, $sectionToRender, array $variables, $viewHelperVariableContainer = NULL) {
		$partial = $this->parseTemplate($this->resolvePartialPathAndFilename($partialName));
		$variableContainer = $this->objectManager->create('Tx_Fluid_Core_ViewHelper_TemplateVariableContainer', $variables);
		$renderingContext = $this->buildRenderingContext($variableContainer);
		if ($viewHelperVariableContainer !== NULL) {
			$renderingContext->setViewHelperVariableContainer($viewHelperVariableContainer);
		}
		return $partial->render($renderingContext);
	}

	/**
	 * Figures out which partial to use.
	 *
	 * @param string $partialName The name of the partial
	 * @return string the full path which should be used. The path definitely exists.
	 * @throws Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function resolvePartialPathAndFilename($partialName) {
		$paths = $this->expandGenericPathPattern($this->partialPathAndFilenamePattern, TRUE, TRUE);
		foreach ($paths as &$path) {
			$path = str_replace('@partial', $partialName, $path);
			if (file_exists($path)) {
				return $path;
			}
		}
		throw new Tx_Fluid_View_Exception_InvalidTemplateResourceException('The template files "' . implode('", "', $paths) . '" could not be loaded.', 1225709595);
	}

	/**
	 * Checks whether a template can be resolved for the current request context.
	 *
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function hasTemplate() {
		try {
			$this->resolveTemplatePathAndFilename();
			return TRUE;
		} catch (Tx_Fluid_View_Exception_InvalidTemplateResourceException $e) {
			return FALSE;
		}
	}

	/**
	 * Parse the given template and return it.
	 *
	 * Will cache the results for one call.
	 *
	 * @param string $templatePathAndFilename absolute filename of the template to be parsed
	 * @return Tx_Fluid_Core_Parser_ParsedTemplateInterface the parsed template tree
	 * @throws Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function parseTemplate($templatePathAndFilename) {
		$templateSource = file_get_contents($templatePathAndFilename);
		if ($templateSource === FALSE) {
			throw new Tx_Fluid_View_Exception_InvalidTemplateResourceException('"' . $templatePathAndFilename . '" is not a valid template resource URI.', 1257246929);
		}
		return $this->templateParser->parse($templateSource);
	}

	/**
	 * Set the root path to the templates.
	 * If set, overrides the one determined from $this->templateRootPathPattern
	 *
	 * @param string $templateRootPath Root path to the templates. If set, overrides the one determined from $this->templateRootPathPattern
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function setTemplateRootPath($templateRootPath) {
		$this->templateRootPath = $templateRootPath;
	}

	/**
	 * Resolves the template root to be used inside other paths.
	 *
	 * @return string Path to template root directory
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getTemplateRootPath() {
		if ($this->templateRootPath !== NULL) {
			return $this->templateRootPath;
		} else {
			return str_replace('@packageResourcesPath', t3lib_extMgm::extPath($this->controllerContext->getRequest()->getControllerExtensionKey()) . 'Resources/', $this->templateRootPathPattern);
		}
	}

	/**
	 * Set the root path to the partials.
	 * If set, overrides the one determined from $this->partialRootPathPattern
	 *
	 * @param string $partialRootPath Root path to the partials. If set, overrides the one determined from $this->partialRootPathPattern
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function setPartialRootPath($partialRootPath) {
		$this->partialRootPath = $partialRootPath;
	}

	/**
	 * Resolves the partial root to be used inside other paths.
	 *
	 * @return string Path to partial root directory
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getPartialRootPath() {
		if ($this->partialRootPath !== NULL) {
			return $this->partialRootPath;
		} else {
			return str_replace('@packageResourcesPath', t3lib_extMgm::extPath($this->controllerContext->getRequest()->getControllerExtensionKey()) . 'Resources/', $this->partialRootPathPattern);
		}
	}

	/**
	 * Set the root path to the layouts.
	 * If set, overrides the one determined from $this->layoutRootPathPattern
	 *
	 * @param string $layoutRootPath Root path to the layouts. If set, overrides the one determined from $this->layoutRootPathPattern
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function setLayoutRootPath($layoutRootPath) {
		$this->layoutRootPath = $layoutRootPath;
	}

	/**
	 * Resolves the layout root to be used inside other paths.
	 *
	 * @return string Path to layout root directory
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getLayoutRootPath() {
		if ($this->layoutRootPath !== NULL) {
			return $this->layoutRootPath;
		} else {
			return str_replace('@packageResourcesPath', t3lib_extMgm::extPath($this->controllerContext->getRequest()->getControllerExtensionKey()) . 'Resources/', $this->layoutRootPathPattern);
		}
	}

	/**
	 * Processes @templateRoot, @subpackage, @controller, and @format placeholders inside $pattern.
	 * This method is used to generate "fallback chains" for file system locations where a certain Partial can reside.
	 *
	 * If $bubbleControllerAndSubpackage is FALSE and $formatIsOptional is FALSE, then the resulting array will only have one element
	 * with all the above placeholders replaced.
	 *
	 * If you set $bubbleControllerAndSubpackage to TRUE, then you will get an array with potentially many elements:
	 * The first element of the array is like above. The second element has the @controller part set to "" (the empty string)
	 * The third element now has the @controller part again stripped off, and has the last subpackage part stripped off as well.
	 * This continues until both @subpackage and @controller are empty.
	 *
	 * Example for $bubbleControllerAndSubpackage is TRUE, we have the Tx_Fluid_MySubPackage_Controller_MyController as Controller Object Name and the current format is "html"
	 * If pattern is @templateRoot/@controller/@action.@format, then the resulting array is:
	 *  - Resources/Private/Templates/MySubPackage/My/@action.html
	 *  - Resources/Private/Templates/MySubPackage/@action.html
	 *  - Resources/Private/Templates/@action.html
	 *
	 * If you set $formatIsOptional to TRUE, then for any of the above arrays, every element will be duplicated  - once with @format
	 * replaced by the current request format, and once with .@format stripped off.
	 *
	 * @param string $pattern Pattern to be resolved
	 * @param boolean $bubbleControllerAndSubpackage if TRUE, then we successively split off parts from @controller and @subpackage until both are empty.
	 * @param boolean $formatIsOptional if TRUE, then half of the resulting strings will have .@format stripped off, and the other half will have it.
	 * @return array unix style path
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function expandGenericPathPattern($pattern, $bubbleControllerAndSubpackage, $formatIsOptional) {
		$pattern = str_replace('@templateRoot', $this->getTemplateRootPath(), $pattern);
		$pattern = str_replace('@partialRoot', $this->getPartialRootPath(), $pattern);
		$pattern = str_replace('@layoutRoot', $this->getLayoutRootPath(), $pattern);

		$subPackageKey = '';
		$controllerName = $this->controllerContext->getRequest()->getControllerName();

		$subpackageParts = ($subPackageKey !== '') ? explode(Tx_Fluid_Fluid::NAMESPACE_SEPARATOR, $subPackageKey) : array();

		$results = array();

		$i = ($controllerName === NULL) ? 0 : -1;
		do {
			$temporaryPattern = $pattern;
			if ($i < 0) {
				$temporaryPattern = str_replace('@controller', $controllerName, $temporaryPattern);
			} else {
				$temporaryPattern = str_replace('//', '/', str_replace('@controller', '', $temporaryPattern));
			}
			$temporaryPattern = str_replace('@subpackage', implode('/', ($i<0 ? $subpackageParts : array_slice($subpackageParts, $i))), $temporaryPattern);

			$results[] = t3lib_div::fixWindowsFilePath(str_replace('@format', $this->controllerContext->getRequest()->getFormat(), $temporaryPattern));
			if ($formatIsOptional) {
				$results[] =  t3lib_div::fixWindowsFilePath(str_replace('.@format', '', $temporaryPattern));
			}

		} while($i++ < count($subpackageParts) && $bubbleControllerAndSubpackage);

		return $results;
	}
}

?>