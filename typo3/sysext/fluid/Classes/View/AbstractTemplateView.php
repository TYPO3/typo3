<?php
namespace TYPO3\CMS\Fluid\View;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Abstract Fluid Template View.
 *
 * Contains the fundamental methods which any Fluid based template view needs.
 */
abstract class AbstractTemplateView implements \TYPO3\CMS\Extbase\Mvc\View\ViewInterface {

	/**
	 * Constants defining possible rendering types
	 */
	const RENDERING_TEMPLATE = 1;
	const RENDERING_PARTIAL = 2;
	const RENDERING_LAYOUT = 3;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Parser\TemplateParser
	 */
	protected $templateParser;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler
	 */
	protected $templateCompiler;

	/**
	 * The initial rendering context for this template view.
	 * Due to the rendering stack, another rendering context might be active
	 * at certain points while rendering the template.
	 *
	 * @var \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface
	 */
	protected $baseRenderingContext;

	/**
	 * Stack containing the current rendering type, the current rendering context, and the current parsed template
	 * Do not manipulate directly, instead use the methods"getCurrent*()", "startRendering(...)" and "stopRendering()"
	 *
	 * @var array
	 */
	protected $renderingStack = array();

	/**
	 * Partial Name -> Partial Identifier cache.
	 * This is a performance optimization, effective when rendering a
	 * single partial many times.
	 *
	 * @var array
	 */
	protected $partialIdentifierCache = array();

	/**
	 * Injects the Object Manager
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Inject the Template Parser
	 *
	 * @param \TYPO3\CMS\Fluid\Core\Parser\TemplateParser $templateParser The template parser
	 * @return void
	 */
	public function injectTemplateParser(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser $templateParser) {
		$this->templateParser = $templateParser;
	}

	/**
	 * @param \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler
	 * @return void
	 */
	public function injectTemplateCompiler(\TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler $templateCompiler) {
		$this->templateCompiler = $templateCompiler;
		$this->templateCompiler->setTemplateCache($GLOBALS['typo3CacheManager']->getCache('fluid_template'));
	}

	/**
	 * Injects a fresh rendering context
	 *
	 * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return void
	 */
	public function setRenderingContext(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		$this->baseRenderingContext = $renderingContext;
		$this->baseRenderingContext->getViewHelperVariableContainer()->setView($this);
		$this->controllerContext = $renderingContext->getControllerContext();
	}

	/**
	 * Sets the current controller context
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext Controller context which is available inside the view
	 * @return void
	 * @api
	 */
	public function setControllerContext(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	public function initializeView() {
	}
	// Here, the backporter can insert the initializeView method, which is needed for Fluid v4.

	/**
	 * Assign a value to the variable container.
	 *
	 * @param string $key The key of a view variable to set
	 * @param mixed $value The value of the view variable
	 * @return \TYPO3\CMS\Fluid\View\AbstractTemplateView the instance of this view to allow chaining
	 * @api
	 */
	public function assign($key, $value) {
		$templateVariableContainer = $this->baseRenderingContext->getTemplateVariableContainer();
		if ($templateVariableContainer->exists($key)) {
			$templateVariableContainer->remove($key);
		}
		$templateVariableContainer->add($key, $value);
		return $this;
	}

	/**
	 * Assigns multiple values to the JSON output.
	 * However, only the key "value" is accepted.
	 *
	 * @param array $values Keys and values - only a value with key "value" is considered
	 * @return \TYPO3\CMS\Fluid\View\AbstractTemplateView the instance of this view to allow chaining
	 * @api
	 */
	public function assignMultiple(array $values) {
		$templateVariableContainer = $this->baseRenderingContext->getTemplateVariableContainer();
		foreach ($values as $key => $value) {
			if ($templateVariableContainer->exists($key)) {
				$templateVariableContainer->remove($key);
			}
			$templateVariableContainer->add($key, $value);
		}
		return $this;
	}

	/**
	 * Loads the template source and render the template.
	 * If "layoutName" is set in a PostParseFacet callback, it will render the file with the given layout.
	 *
	 * @param string $actionName If set, the view of the specified action will be rendered instead. Default is the action specified in the Request object
	 * @return string Rendered Template
	 * @api
	 */
	public function render($actionName = NULL) {
		$this->baseRenderingContext->setControllerContext($this->controllerContext);
		$this->templateParser->setConfiguration($this->buildParserConfiguration());

		$templateIdentifier = $this->getTemplateIdentifier($actionName);
		if ($this->templateCompiler->has($templateIdentifier)) {
			$parsedTemplate = $this->templateCompiler->get($templateIdentifier);
		} else {
			$parsedTemplate = $this->templateParser->parse($this->getTemplateSource($actionName));
			if ($parsedTemplate->isCompilable()) {
				$this->templateCompiler->store($templateIdentifier, $parsedTemplate);
			}
		}

		if ($parsedTemplate->hasLayout()) {
			$layoutName = $parsedTemplate->getLayoutName($this->baseRenderingContext);
			$layoutIdentifier = $this->getLayoutIdentifier($layoutName);
			if ($this->templateCompiler->has($layoutIdentifier)) {
				$parsedLayout = $this->templateCompiler->get($layoutIdentifier);
			} else {
				$parsedLayout = $this->templateParser->parse($this->getLayoutSource($layoutName));
				if ($parsedLayout->isCompilable()) {
					$this->templateCompiler->store($layoutIdentifier, $parsedLayout);
				}
			}
			$this->startRendering(self::RENDERING_LAYOUT, $parsedTemplate, $this->baseRenderingContext);
			$output = $parsedLayout->render($this->baseRenderingContext);
			$this->stopRendering();
		} else {
			$this->startRendering(self::RENDERING_TEMPLATE, $parsedTemplate, $this->baseRenderingContext);
			$output = $parsedTemplate->render($this->baseRenderingContext);
			$this->stopRendering();
		}

		return $output;
	}

	/**
	 * Renders a given section.
	 *
	 * @param string $sectionName Name of section to render
	 * @param array $variables The variables to use
	 * @param boolean $ignoreUnknown Ignore an unknown section and just return an empty string
	 * @return string rendered template for the section
	 * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidSectionException
	 */
	public function renderSection($sectionName, array $variables, $ignoreUnknown = FALSE) {
		$renderingContext = $this->getCurrentRenderingContext();
		if ($this->getCurrentRenderingType() === self::RENDERING_LAYOUT) {
			// in case we render a layout right now, we will render a section inside a TEMPLATE.
			$renderingTypeOnNextLevel = self::RENDERING_TEMPLATE;
		} else {
			$variableContainer = $this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TemplateVariableContainer', $variables);
			$renderingContext = clone $renderingContext;
			$renderingContext->injectTemplateVariableContainer($variableContainer);
			$renderingTypeOnNextLevel = $this->getCurrentRenderingType();
		}

		$parsedTemplate = $this->getCurrentParsedTemplate();

		if ($parsedTemplate->isCompiled()) {
			$methodNameOfSection = 'section_' . sha1($sectionName);
			if ($ignoreUnknown && !method_exists($parsedTemplate, $methodNameOfSection)) {
				return '';
			}
			$this->startRendering($renderingTypeOnNextLevel, $parsedTemplate, $renderingContext);
			$output = $parsedTemplate->{$methodNameOfSection}($renderingContext);
			$this->stopRendering();
		} else {
			$sections = $parsedTemplate->getVariableContainer()->get('sections');
			if (!array_key_exists($sectionName, $sections)) {
				$controllerObjectName = $this->controllerContext->getRequest()->getControllerObjectName();
				if ($ignoreUnknown) {
					return '';
				} else {
					throw new \TYPO3\CMS\Fluid\View\Exception\InvalidSectionException(sprintf('Could not render unknown section "%s" in %s used by %s.', $sectionName, get_class($this), $controllerObjectName), 1227108982);
				}
			}
			$section = $sections[$sectionName];

			$renderingContext->getViewHelperVariableContainer()->add('TYPO3\\CMS\\Fluid\\ViewHelpers\\SectionViewHelper', 'isCurrentlyRenderingSection', 'TRUE');

			$this->startRendering($renderingTypeOnNextLevel, $parsedTemplate, $renderingContext);
			$output = $section->evaluate($renderingContext);
			$this->stopRendering();
		}

		return $output;
	}

	/**
	 * Renders a partial.
	 *
	 * @param string $partialName
	 * @param string $sectionName
	 * @param array $variables
	 * @param \TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer $viewHelperVariableContainer the View Helper Variable container to use.
	 * @return string
	 */
	public function renderPartial($partialName, $sectionName, array $variables) {
		if (!isset($this->partialIdentifierCache[$partialName])) {
			$this->partialIdentifierCache[$partialName] = $this->getPartialIdentifier($partialName);
		}
		$partialIdentifier = $this->partialIdentifierCache[$partialName];

		if ($this->templateCompiler->has($partialIdentifier)) {
			$parsedPartial = $this->templateCompiler->get($partialIdentifier);
		} else {
			$parsedPartial = $this->templateParser->parse($this->getPartialSource($partialName));
			if ($parsedPartial->isCompilable()) {
				$this->templateCompiler->store($partialIdentifier, $parsedPartial);
			}
		}

		$variableContainer = $this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TemplateVariableContainer', $variables);
		$renderingContext = clone $this->getCurrentRenderingContext();
		$renderingContext->injectTemplateVariableContainer($variableContainer);

		$this->startRendering(self::RENDERING_PARTIAL, $parsedPartial, $renderingContext);
		if ($sectionName !== NULL) {
			$output = $this->renderSection($sectionName, $variables);
		} else {
			$output = $parsedPartial->render($renderingContext);
		}
		$this->stopRendering();

		return $output;
	}

	/**
	 * Returns a unique identifier for the resolved template file.
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $actionName Name of the action. If NULL, will be taken from request.
	 * @return string template identifier
	 */
	abstract protected function getTemplateIdentifier($actionName = NULL);

	/**
	 * Resolve the template path and filename for the given action. If $actionName
	 * is NULL, looks into the current request.
	 *
	 * @param string $actionName Name of the action. If NULL, will be taken from request.
	 * @return string Full path to template
	 * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException in case the template was not found
	 */
	abstract protected function getTemplateSource($actionName = NULL);

	/**
	 * Returns a unique identifier for the resolved layout file.
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $layoutName The name of the layout
	 * @return string layout identifier
	 */
	abstract protected function getLayoutIdentifier($layoutName = 'Default');

	/**
	 * Resolve the path and file name of the layout file, based on
	 * $this->layoutPathAndFilename and $this->layoutPathAndFilenamePattern.
	 *
	 * In case a layout has already been set with setLayoutPathAndFilename(),
	 * this method returns that path, otherwise a path and filename will be
	 * resolved using the layoutPathAndFilenamePattern.
	 *
	 * @param string $layoutName Name of the layout to use. If none given, use "Default"
	 * @return string Path and filename of layout file
	 * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	abstract protected function getLayoutSource($layoutName = 'Default');

	/**
	 * Returns a unique identifier for the resolved partial file.
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $partialName The name of the partial
	 * @return string partial identifier
	 */
	abstract protected function getPartialIdentifier($partialName);

	/**
	 * Figures out which partial to use.
	 *
	 * @param string $partialName The name of the partial
	 * @return string the full path which should be used. The path definitely exists.
	 * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	abstract protected function getPartialSource($partialName);

	/**
	 * Build parser configuration
	 *
	 * @return \TYPO3\CMS\Fluid\Core\Parser\Configuration
	 */
	protected function buildParserConfiguration() {
		$parserConfiguration = $this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Parser\\Configuration');
		if ($this->controllerContext->getRequest()->getFormat() === 'html') {
			$parserConfiguration->addInterceptor($this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Parser\\Interceptor\\Escape'));
		}
		return $parserConfiguration;
	}

	/**
	 * Start a new nested rendering. Pushes the given information onto the $renderingStack.
	 *
	 * @param integer $type one of the RENDERING_* constants
	 * @param \TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface $parsedTemplate
	 * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return void
	 */
	protected function startRendering($type, \TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface $parsedTemplate, \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
		array_push($this->renderingStack, array('type' => $type, 'parsedTemplate' => $parsedTemplate, 'renderingContext' => $renderingContext));
	}

	/**
	 * Stops the current rendering. Removes one element from the $renderingStack. Make sure to always call this
	 * method pair-wise with startRendering().
	 *
	 * @return void
	 */
	protected function stopRendering() {
		array_pop($this->renderingStack);
	}

	/**
	 * Get the current rendering type.
	 *
	 * @return integer one of RENDERING_* constants
	 */
	protected function getCurrentRenderingType() {
		$currentRendering = end($this->renderingStack);
		return $currentRendering['type'];
	}

	/**
	 * Get the parsed template which is currently being rendered.
	 *
	 * @return \TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface
	 */
	protected function getCurrentParsedTemplate() {
		$currentRendering = end($this->renderingStack);
		return $currentRendering['parsedTemplate'];
	}

	/**
	 * Get the rendering context which is currently used.
	 *
	 * @return \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface
	 */
	protected function getCurrentRenderingContext() {
		$currentRendering = end($this->renderingStack);
		return $currentRendering['renderingContext'];
	}

	/**
	 * Tells if the view implementation can render the view for the given context.
	 *
	 * By default we assume that the view implementation can handle all kinds of
	 * contexts. Override this method if that is not the case.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext Controller context which is available inside the view
	 * @return boolean TRUE if the view has something useful to display, otherwise FALSE
	 * @api
	 */
	public function canRender(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext) {
		return TRUE;
	}
}

?>