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
 * The main template view. Should be used as view if you want Fluid Templating
 *
 * @api
 */
class TemplateView extends \TYPO3\CMS\Fluid\View\AbstractTemplateView {

	/**
	 * Pattern to be resolved for "@templateRoot" in the other patterns.
	 *
	 * @var string
	 */
	protected $templateRootPathPattern = '@packageResourcesPath/Private/Templates';

	/**
	 * Pattern to be resolved for "@partialRoot" in the other patterns.
	 *
	 * @var string
	 */
	protected $partialRootPathPattern = '@packageResourcesPath/Private/Partials';

	/**
	 * Pattern to be resolved for "@layoutRoot" in the other patterns.
	 *
	 * @var string
	 */
	protected $layoutRootPathPattern = '@packageResourcesPath/Private/Layouts';

	/**
	 * Path to the template root. If NULL, then $this->templateRootPathPattern will be used.
	 *
	 * @var string
	 */
	protected $templateRootPath = NULL;

	/**
	 * Path to the partial root. If NULL, then $this->partialRootPathPattern will be used.
	 *
	 * @var string
	 */
	protected $partialRootPath = NULL;

	/**
	 * Path to the layout root. If NULL, then $this->layoutRootPathPattern will be used.
	 *
	 * @var string
	 */
	protected $layoutRootPath = NULL;

	/**
	 * File pattern for resolving the template file
	 *
	 * @var string
	 */
	protected $templatePathAndFilenamePattern = '@templateRoot/@subpackage/@controller/@action.@format';

	/**
	 * Directory pattern for global partials. Not part of the public API, should not be changed for now.
	 *
	 * @var string
	 */
	private $partialPathAndFilenamePattern = '@partialRoot/@subpackage/@partial.@format';

	/**
	 * File pattern for resolving the layout
	 *
	 * @var string
	 */
	protected $layoutPathAndFilenamePattern = '@layoutRoot/@layout.@format';

	/**
	 * Path and filename of the template file. If set,  overrides the templatePathAndFilenamePattern
	 *
	 * @var string
	 */
	protected $templatePathAndFilename = NULL;

	/**
	 * Path and filename of the layout file. If set, overrides the layoutPathAndFilenamePattern
	 *
	 * @var string
	 */
	protected $layoutPathAndFilename = NULL;

	public function __construct() {
		$this->injectTemplateParser(\TYPO3\CMS\Fluid\Compatibility\TemplateParserBuilder::build());
		$this->injectObjectManager(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager'));
		$this->setRenderingContext($this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContextInterface'));
	}

	public function initializeView() {
	}

	// Here, the backporter can insert a constructor method, which is needed for Fluid v4.

	/**
	 * Sets the path and name of of the template file. Effectively overrides the
	 * dynamic resolving of a template file.
	 *
	 * @param string $templatePathAndFilename Template file path
	 * @return void
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
	 * @api
	 */
	public function setLayoutPathAndFilename($layoutPathAndFilename) {
		$this->layoutPathAndFilename = $layoutPathAndFilename;
	}

	/**
	 * Checks whether a template can be resolved for the current request context.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext Controller context which is available inside the view
	 * @return boolean
	 * @api
	 */
	public function canRender(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext) {
		$this->setControllerContext($controllerContext);
		try {
			$this->getTemplateSource();
			return TRUE;
		} catch (\TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException $e) {
			return FALSE;
		}
	}

	/**
	 * Set the root path to the templates.
	 * If set, overrides the one determined from $this->templateRootPathPattern
	 *
	 * @param string $templateRootPath Root path to the templates. If set, overrides the one determined from $this->templateRootPathPattern
	 * @return void
	 * @api
	 */
	public function setTemplateRootPath($templateRootPath) {
		$this->templateRootPath = $templateRootPath;
	}

	/**
	 * Returns a unique identifier for the resolved template file
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $actionName Name of the action. If NULL, will be taken from request.
	 * @return string template identifier
	 */
	protected function getTemplateIdentifier($actionName = NULL) {
		$templatePathAndFilename = $this->getTemplatePathAndFilename($actionName);
		if ($actionName === NULL) {
			$actionName = $this->controllerContext->getRequest()->getControllerActionName();
		}
		$prefix = 'action_' . $actionName;
		return $this->createIdentifierForFile($templatePathAndFilename, $prefix);
	}

	/**
	 * Resolve the template path and filename for the given action. If $actionName
	 * is NULL, looks into the current request.
	 *
	 * @param string $actionName Name of the action. If NULL, will be taken from request.
	 * @return string Full path to template
	 * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	protected function getTemplateSource($actionName = NULL) {
		$templatePathAndFilename = $this->getTemplatePathAndFilename($actionName);
		$templateSource = file_get_contents($templatePathAndFilename);
		if ($templateSource === FALSE) {
			throw new \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException('"' . $templatePathAndFilename . '" is not a valid template resource URI.', 1257246929);
		}
		return $templateSource;
	}

	/**
	 * Resolve the template path and filename for the given action. If $actionName
	 * is NULL, looks into the current request.
	 *
	 * @param string $actionName Name of the action. If NULL, will be taken from request.
	 * @return string Full path to template
	 * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	protected function getTemplatePathAndFilename($actionName = NULL) {
		if ($this->templatePathAndFilename !== NULL) {
			return $this->templatePathAndFilename;
		}
		if ($actionName === NULL) {
			$actionName = $this->controllerContext->getRequest()->getControllerActionName();
		}
		$actionName = ucfirst($actionName);
		$paths = $this->expandGenericPathPattern($this->templatePathAndFilenamePattern, FALSE, FALSE);
		foreach ($paths as &$templatePathAndFilename) {
			$templatePathAndFilename = str_replace('@action', $actionName, $templatePathAndFilename);
			if (is_file($templatePathAndFilename)) {
				return $templatePathAndFilename;
			}
		}
		throw new \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException('Template could not be loaded. I tried "' . implode('", "', $paths) . '"', 1225709595);
	}

	/**
	 * Returns a unique identifier for the resolved layout file.
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $layoutName The name of the layout
	 * @return string layout identifier
	 */
	protected function getLayoutIdentifier($layoutName = 'Default') {
		$layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
		$prefix = 'layout_' . $layoutName;
		return $this->createIdentifierForFile($layoutPathAndFilename, $prefix);
	}

	/**
	 * Resolve the path and file name of the layout file, based on
	 * $this->layoutPathAndFilename and $this->layoutPathAndFilenamePattern.
	 *
	 * In case a layout has already been set with setLayoutPathAndFilename(),
	 * this method returns that path, otherwise a path and filename will be
	 * resolved using the layoutPathAndFilenamePattern.
	 *
	 * @param string $layoutName Name of the layout to use. If none given, use "Default"
	 * @return string contents of the layout template
	 * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	protected function getLayoutSource($layoutName = 'Default') {
		$layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
		$layoutSource = file_get_contents($layoutPathAndFilename);
		if ($layoutSource === FALSE) {
			throw new \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException('"' . $layoutPathAndFilename . '" is not a valid template resource URI.', 1257246929);
		}
		return $layoutSource;
	}

	/**
	 * Resolve the path and file name of the layout file, based on
	 * $this->layoutPathAndFilename and $this->layoutPathAndFilenamePattern.
	 *
	 * In case a layout has already been set with setLayoutPathAndFilename(),
	 * this method returns that path, otherwise a path and filename will be
	 * resolved using the layoutPathAndFilenamePattern.
	 *
	 * @param string $layoutName Name of the layout to use. If none given, use "Default"
	 * @return string Path and filename of layout files
	 * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	protected function getLayoutPathAndFilename($layoutName = 'Default') {
		if ($this->layoutPathAndFilename !== NULL) {
			return $this->layoutPathAndFilename;
		}
		$paths = $this->expandGenericPathPattern($this->layoutPathAndFilenamePattern, TRUE, TRUE);
		$layoutName = ucfirst($layoutName);
		foreach ($paths as &$layoutPathAndFilename) {
			$layoutPathAndFilename = str_replace('@layout', $layoutName, $layoutPathAndFilename);
			if (is_file($layoutPathAndFilename)) {
				return $layoutPathAndFilename;
			}
		}
		throw new \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException('The template files "' . implode('", "', $paths) . '" could not be loaded.', 1225709595);
	}

	/**
	 * Returns a unique identifier for the resolved partial file.
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $partialName The name of the partial
	 * @return string partial identifier
	 */
	protected function getPartialIdentifier($partialName) {
		$partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
		$prefix = 'partial_' . $partialName;
		return $this->createIdentifierForFile($partialPathAndFilename, $prefix);
	}

	/**
	 * Figures out which partial to use.
	 *
	 * @param string $partialName The name of the partial
	 * @return string contents of the partial template
	 * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	protected function getPartialSource($partialName) {
		$partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
		$partialSource = file_get_contents($partialPathAndFilename);
		if ($partialSource === FALSE) {
			throw new \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException('"' . $partialPathAndFilename . '" is not a valid template resource URI.', 1257246929);
		}
		return $partialSource;
	}

	/**
	 * Resolve the partial path and filename based on $this->partialPathAndFilenamePattern.
	 *
	 * @param string $partialName The name of the partial
	 * @return string the full path which should be used. The path definitely exists.
	 * @throws \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException
	 */
	protected function getPartialPathAndFilename($partialName) {
		$paths = $this->expandGenericPathPattern($this->partialPathAndFilenamePattern, TRUE, TRUE);
		foreach ($paths as &$partialPathAndFilename) {
			$partialPathAndFilename = str_replace('@partial', $partialName, $partialPathAndFilename);
			if (is_file($partialPathAndFilename)) {
				return $partialPathAndFilename;
			}
		}
		throw new \TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException('The template files "' . implode('", "', $paths) . '" could not be loaded.', 1225709595);
	}

	/**
	 * Resolves the template root to be used inside other paths.
	 *
	 * @return string Path to template root directory
	 */
	protected function getTemplateRootPath() {
		if ($this->templateRootPath !== NULL) {
			return $this->templateRootPath;
		} else {
			return str_replace('@packageResourcesPath', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->controllerContext->getRequest()->getControllerExtensionKey()) . 'Resources/', $this->templateRootPathPattern);
		}
	}

	/**
	 * Set the root path to the partials.
	 * If set, overrides the one determined from $this->partialRootPathPattern
	 *
	 * @param string $partialRootPath Root path to the partials. If set, overrides the one determined from $this->partialRootPathPattern
	 * @return void
	 * @api
	 */
	public function setPartialRootPath($partialRootPath) {
		$this->partialRootPath = $partialRootPath;
	}

	/**
	 * Resolves the partial root to be used inside other paths.
	 *
	 * @return string Path to partial root directory
	 */
	protected function getPartialRootPath() {
		if ($this->partialRootPath !== NULL) {
			return $this->partialRootPath;
		} else {
			return str_replace('@packageResourcesPath', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->controllerContext->getRequest()->getControllerExtensionKey()) . 'Resources/', $this->partialRootPathPattern);
		}
	}

	/**
	 * Set the root path to the layouts.
	 * If set, overrides the one determined from $this->layoutRootPathPattern
	 *
	 * @param string $layoutRootPath Root path to the layouts. If set, overrides the one determined from $this->layoutRootPathPattern
	 * @return void
	 * @api
	 */
	public function setLayoutRootPath($layoutRootPath) {
		$this->layoutRootPath = $layoutRootPath;
	}

	/**
	 * Resolves the layout root to be used inside other paths.
	 *
	 * @return string Path to layout root directory
	 */
	protected function getLayoutRootPath() {
		if ($this->layoutRootPath !== NULL) {
			return $this->layoutRootPath;
		} else {
			return str_replace('@packageResourcesPath', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->controllerContext->getRequest()->getControllerExtensionKey()) . 'Resources/', $this->layoutRootPathPattern);
		}
	}

	/**
	 * Processes "@templateRoot", "@subpackage", "@controller", and "@format" placeholders inside $pattern.
	 * This method is used to generate "fallback chains" for file system locations where a certain Partial can reside.
	 *
	 * If $bubbleControllerAndSubpackage is FALSE and $formatIsOptional is FALSE, then the resulting array will only have one element
	 * with all the above placeholders replaced.
	 *
	 * If you set $bubbleControllerAndSubpackage to TRUE, then you will get an array with potentially many elements:
	 * The first element of the array is like above. The second element has the @ controller part set to "" (the empty string)
	 * The third element now has the @ controller part again stripped off, and has the last subpackage part stripped off as well.
	 * This continues until both "@subpackage" and "@controller" are empty.
	 *
	 * Example for $bubbleControllerAndSubpackage is TRUE, we have the Tx_MyExtension_MySubPackage_Controller_MyController
	 * as Controller Object Name and the current format is "html"
	 *
	 * If pattern is "@templateRoot/@subpackage/@controller/@action.@format", then the resulting array is:
	 *  - "Resources/Private/Templates/MySubPackage/My/@action.html"
	 *  - "Resources/Private/Templates/MySubPackage/@action.html"
	 *  - "Resources/Private/Templates/@action.html"
	 *
	 * If you set $formatIsOptional to TRUE, then for any of the above arrays, every element will be duplicated  - once with "@format"
	 * replaced by the current request format, and once with ."@format" stripped off.
	 *
	 * @param string $pattern Pattern to be resolved
	 * @param boolean $bubbleControllerAndSubpackage if TRUE, then we successively split off parts from "@controller" and "@subpackage" until both are empty.
	 * @param boolean $formatIsOptional if TRUE, then half of the resulting strings will have ."@format" stripped off, and the other half will have it.
	 * @return array unix style path
	 */
	protected function expandGenericPathPattern($pattern, $bubbleControllerAndSubpackage, $formatIsOptional) {
		$pattern = str_replace('@templateRoot', $this->getTemplateRootPath(), $pattern);
		$pattern = str_replace('@partialRoot', $this->getPartialRootPath(), $pattern);
		$pattern = str_replace('@layoutRoot', $this->getLayoutRootPath(), $pattern);

		$subpackageKey = $this->controllerContext->getRequest()->getControllerSubpackageKey();
		$controllerName = $this->controllerContext->getRequest()->getControllerName();
		if ($subpackageKey !== NULL) {
			if (strpos($subpackageKey, \TYPO3\CMS\Fluid\Fluid::NAMESPACE_SEPARATOR) !== FALSE) {
				$namespaceSeparator = \TYPO3\CMS\Fluid\Fluid::NAMESPACE_SEPARATOR;
			} else {
				$namespaceSeparator = \TYPO3\CMS\Fluid\Fluid::LEGACY_NAMESPACE_SEPARATOR;
			}
			$subpackageParts = explode($namespaceSeparator, $subpackageKey);
		} else {
			$subpackageParts = array();
		}
		$results = array();

		$i = ($controllerName === NULL) ? 0 : -1;
		do {
			$temporaryPattern = $pattern;
			if ($i < 0) {
				$temporaryPattern = str_replace('@controller', $controllerName, $temporaryPattern);
			} else {
				$temporaryPattern = str_replace('//', '/', str_replace('@controller', '', $temporaryPattern));
			}
			$temporaryPattern = str_replace('@subpackage', implode('/', ($i < 0 ? $subpackageParts : array_slice($subpackageParts, $i))), $temporaryPattern);

			$results[] = \TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath(str_replace('@format', $this->controllerContext->getRequest()->getFormat(), $temporaryPattern));
			if ($formatIsOptional) {
				$results[] = \TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath(str_replace('.@format', '', $temporaryPattern));
			}
		} while ($i++ < count($subpackageParts) && $bubbleControllerAndSubpackage);

		return $results;
	}

	/**
	 * Returns a unique identifier for the given file in the format
	 * <PackageKey>_<SubPackageKey>_<ControllerName>_<prefix>_<SHA1>
	 * The SH1 hash is a checksum that is based on the file path and last modification date
	 *
	 * @param string $pathAndFilename
	 * @param string $prefix
	 * @return string
	 */
	protected function createIdentifierForFile($pathAndFilename, $prefix) {
		$request = $this->controllerContext->getRequest();
		$extensionName = $request->getControllerExtensionName();
		$subPackageKey = $request->getControllerSubpackageKey();
		if ($subPackageKey !== NULL) {
			$extensionName .= '_' . $subPackageKey;
		}
		$controllerName = $request->getControllerName();
		$templateModifiedTimestamp = filemtime($pathAndFilename);
		$templateIdentifier = sprintf('%s_%s_%s_%s', $extensionName, $controllerName, $prefix, sha1($pathAndFilename . '|' . $templateModifiedTimestamp));
		return $templateIdentifier;
	}
}

?>