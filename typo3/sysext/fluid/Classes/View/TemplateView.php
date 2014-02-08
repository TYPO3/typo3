<?php
namespace TYPO3\CMS\Fluid\View;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

/**
 * The main template view. Should be used as view if you want Fluid Templating
 *
 * @api
 */
class TemplateView extends AbstractTemplateView {

	/**
	 * Pattern to be resolved for "@templateRoot" in the other patterns.
	 * Following placeholders are supported:
	 * - "@packageResourcesPath"
	 *
	 * @var string
	 */
	protected $templateRootPathPattern = '@packageResourcesPath/Private/Templates';

	/**
	 * Pattern to be resolved for "@partialRoot" in the other patterns.
	 * Following placeholders are supported:
	 * - "@packageResourcesPath"
	 *
	 * @var string
	 */
	protected $partialRootPathPattern = '@packageResourcesPath/Private/Partials';

	/**
	 * Pattern to be resolved for "@layoutRoot" in the other patterns.
	 * Following placeholders are supported:
	 * - "@packageResourcesPath"
	 *
	 * @var string
	 */
	protected $layoutRootPathPattern = '@packageResourcesPath/Private/Layouts';

	/**
	 * Path(s) to the template root. If NULL, then $this->templateRootPathPattern will be used.
	 *
	 * @var array
	 */
	protected $templateRootPaths = NULL;

	/**
	 * Path(s) to the partial root. If NULL, then $this->partialRootPathPattern will be used.
	 *
	 * @var array
	 */
	protected $partialRootPaths = NULL;

	/**
	 * Path(s) to the layout root. If NULL, then $this->layoutRootPathPattern will be used.
	 *
	 * @var array
	 */
	protected $layoutRootPaths = NULL;

	/**
	 * File pattern for resolving the template file
	 * Following placeholders are supported:
	 * - "@templateRoot"
	 * - "@partialRoot"
	 * - "@layoutRoot"
	 * - "@subpackage"
	 * - "@action"
	 * - "@format"
	 *
	 * @var string
	 */
	protected $templatePathAndFilenamePattern = '@templateRoot/@subpackage/@controller/@action.@format';

	/**
	 * Directory pattern for global partials. Not part of the public API, should not be changed for now.
	 * Following placeholders are supported:
	 * - "@templateRoot"
	 * - "@partialRoot"
	 * - "@layoutRoot"
	 * - "@subpackage"
	 * - "@partial"
	 * - "@format"
	 *
	 * @var string
	 */
	private $partialPathAndFilenamePattern = '@partialRoot/@subpackage/@partial.@format';

	/**
	 * File pattern for resolving the layout
	 * Following placeholders are supported:
	 * - "@templateRoot"
	 * - "@partialRoot"
	 * - "@layoutRoot"
	 * - "@subpackage"
	 * - "@layout"
	 * - "@format"
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
		$this->templateParser = \TYPO3\CMS\Fluid\Compatibility\TemplateParserBuilder::build();
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->setRenderingContext($this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContextInterface'));
	}

	public function initializeView() {
	}
	// Here, the backporter can insert a constructor method, which is needed for the TYPO3 CMS extension

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
	 * Set the root path to the templates.
	 * If set, overrides the one determined from $this->templateRootPathPattern
	 *
	 * @param string $templateRootPath Root path to the templates. If set, overrides the one determined from $this->templateRootPathPattern
	 * @return void
	 * @api
	 * @see setTemplateRootPaths()
	 */
	public function setTemplateRootPath($templateRootPath) {
		$this->setTemplateRootPaths(array($templateRootPath));
	}

	/**
	 * @return string Path to template root directory
	 * @deprecated since fluid 6.2, will be removed two versions later. Use getTemplateRootPaths() instead
	 */
	protected function getTemplateRootPath() {
		GeneralUtility::logDeprecatedFunction();
		$templateRootPaths = $this->getTemplateRootPaths();
		return array_shift($templateRootPaths);
	}

	/**
	 * Resolves the template root to be used inside other paths.
	 *
	 * @return array Path(s) to template root directory
	 */
	public function getTemplateRootPaths() {
		if ($this->templateRootPaths !== NULL) {
			return $this->templateRootPaths;
		}
		/** @var $actionRequest \TYPO3\CMS\Extbase\Mvc\Request */
		$actionRequest = $this->controllerContext->getRequest();
		return array(str_replace('@packageResourcesPath', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($actionRequest->getControllerExtensionKey()) . 'Resources/', $this->templateRootPathPattern));
	}

	/**
	 * Set the root path(s) to the templates.
	 * If set, overrides the one determined from $this->templateRootPathPattern
	 *
	 * @param array $templateRootPaths Root path(s) to the templates. If set, overrides the one determined from $this->templateRootPathPattern
	 * @return void
	 * @api
	 */
	public function setTemplateRootPaths(array $templateRootPaths) {
		$this->templateRootPaths = $templateRootPaths;
	}

	/**
	 * Set the root path to the partials.
	 * If set, overrides the one determined from $this->partialRootPathPattern
	 *
	 * @param string $partialRootPath Root path to the partials. If set, overrides the one determined from $this->partialRootPathPattern
	 * @return void
	 * @api
	 * @see setPartialRootPaths()
	 */
	public function setPartialRootPath($partialRootPath) {
		$this->setPartialRootPaths(array($partialRootPath));
	}

	/**
	 * @return string Path to partial root directory
	 * @deprecated since fluid 6.2, will be removed two versions later. Use setPartialRootPaths() instead
	 */
	protected function getPartialRootPath() {
		GeneralUtility::logDeprecatedFunction();
		$partialRootPaths = $this->getPartialRootPaths();
		return array_shift($partialRootPaths);
	}

	/**
	 * Set the root path(s) to the partials.
	 * If set, overrides the one determined from $this->partialRootPathPattern
	 *
	 * @param array $partialRootPaths Root paths to the partials. If set, overrides the one determined from $this->partialRootPathPattern
	 * @return void
	 * @api
	 */
	public function setPartialRootPaths(array $partialRootPaths) {
		$this->partialRootPaths = $partialRootPaths;
	}

	/**
	 * Resolves the partial root to be used inside other paths.
	 *
	 * @return array Path(s) to partial root directory
	 */
	protected function getPartialRootPaths() {
		if ($this->partialRootPaths !== NULL) {
			return $this->partialRootPaths;
		}
		/** @var $actionRequest \TYPO3\CMS\Extbase\Mvc\Request */
		$actionRequest = $this->controllerContext->getRequest();
		return array(str_replace('@packageResourcesPath', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($actionRequest->getControllerExtensionKey()) . 'Resources/', $this->partialRootPathPattern));
	}

	/**
	 * Set the root path to the layouts.
	 * If set, overrides the one determined from $this->layoutRootPathPattern
	 *
	 * @param string $layoutRootPath Root path to the layouts. If set, overrides the one determined from $this->layoutRootPathPattern
	 * @return void
	 * @api
	 * @see setLayoutRootPaths()
	 */
	public function setLayoutRootPath($layoutRootPath) {
		$this->setLayoutRootPaths(array($layoutRootPath));
	}

	/**
	 * Set the root path(s) to the layouts.
	 * If set, overrides the one determined from $this->layoutRootPathPattern
	 *
	 * @param array $layoutRootPaths Root path to the layouts. If set, overrides the one determined from $this->layoutRootPathPattern
	 * @return void
	 * @api
	 */
	public function setLayoutRootPaths(array $layoutRootPaths) {
		$this->layoutRootPaths = $layoutRootPaths;
	}

	/**
	 * @return string Path to layout root directory
	 * @deprecated since fluid 6.2, will be removed two versions later. Use getLayoutRootPaths() instead
	 */
	protected function getLayoutRootPath() {
		GeneralUtility::logDeprecatedFunction();
		$layoutRootPaths = $this->getLayoutRootPaths();
		return array_shift($layoutRootPaths);
	}

	/**
	 * Resolves the layout root to be used inside other paths.
	 *
	 * @return string Path(s) to layout root directory
	 */
	protected function getLayoutRootPaths() {
		if ($this->layoutRootPaths !== NULL) {
			return $this->layoutRootPaths;
		}
		/** @var $actionRequest \TYPO3\CMS\Extbase\Mvc\Request */
		$actionRequest = $this->controllerContext->getRequest();
		return array(str_replace('@packageResourcesPath', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($actionRequest->getControllerExtensionKey()) . 'Resources/', $this->layoutRootPathPattern));
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
			/** @var $actionRequest \TYPO3\CMS\Extbase\Mvc\Request */
			$actionRequest = $this->controllerContext->getRequest();
			$actionName = $actionRequest->getControllerActionName();
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
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getTemplateSource($actionName = NULL) {
		$templatePathAndFilename = $this->getTemplatePathAndFilename($actionName);
		$templateSource = file_get_contents($templatePathAndFilename);
		if ($templateSource === FALSE) {
			throw new Exception\InvalidTemplateResourceException('"' . $templatePathAndFilename . '" is not a valid template resource URI.', 1257246929);
		}
		return $templateSource;
	}

	/**
	 * Resolve the template path and filename for the given action. If $actionName
	 * is NULL, looks into the current request.
	 *
	 * @param string $actionName Name of the action. If NULL, will be taken from request.
	 * @return string Full path to template
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getTemplatePathAndFilename($actionName = NULL) {
		if ($this->templatePathAndFilename !== NULL) {
			return $this->templatePathAndFilename;
		}
		if ($actionName === NULL) {
			/** @var $actionRequest \TYPO3\CMS\Extbase\Mvc\Request */
			$actionRequest = $this->controllerContext->getRequest();
			$actionName = $actionRequest->getControllerActionName();
		}
		$actionName = ucfirst($actionName);

		$paths = $this->expandGenericPathPattern($this->templatePathAndFilenamePattern, FALSE, FALSE);
		foreach ($paths as &$templatePathAndFilename) {
			$templatePathAndFilename = $this->resolveFileNamePath(str_replace('@action', $actionName, $templatePathAndFilename));
			if (is_file($templatePathAndFilename)) {
				return $templatePathAndFilename;
			}
		}
		throw new Exception\InvalidTemplateResourceException('Template could not be loaded. I tried "' . implode('", "', $paths) . '"', 1225709595);
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
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getLayoutSource($layoutName = 'Default') {
		$layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
		$layoutSource = file_get_contents($layoutPathAndFilename);
		if ($layoutSource === FALSE) {
			throw new Exception\InvalidTemplateResourceException('"' . $layoutPathAndFilename . '" is not a valid template resource URI.', 1257246930);
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
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getLayoutPathAndFilename($layoutName = 'Default') {
		if ($this->layoutPathAndFilename !== NULL) {
			return $this->layoutPathAndFilename;
		}
		$paths = $this->expandGenericPathPattern($this->layoutPathAndFilenamePattern, TRUE, TRUE);
		$layoutName = ucfirst($layoutName);
		foreach ($paths as &$layoutPathAndFilename) {
			$layoutPathAndFilename = $this->resolveFileNamePath(str_replace('@layout', $layoutName, $layoutPathAndFilename));
			if (is_file($layoutPathAndFilename)) {
				return $layoutPathAndFilename;
			}
		}
		throw new Exception\InvalidTemplateResourceException('The template files "' . implode('", "', $paths) . '" could not be loaded.', 1225709596);
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
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getPartialSource($partialName) {
		$partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
		$partialSource = file_get_contents($partialPathAndFilename);
		if ($partialSource === FALSE) {
			throw new Exception\InvalidTemplateResourceException('"' . $partialPathAndFilename . '" is not a valid template resource URI.', 1257246931);
		}
		return $partialSource;
	}

	/**
	 * Resolve the partial path and filename based on $this->partialPathAndFilenamePattern.
	 *
	 * @param string $partialName The name of the partial
	 * @return string the full path which should be used. The path definitely exists.
	 * @throws Exception\InvalidTemplateResourceException
	 */
	protected function getPartialPathAndFilename($partialName) {
		$paths = $this->expandGenericPathPattern($this->partialPathAndFilenamePattern, TRUE, TRUE);
		foreach ($paths as &$partialPathAndFilename) {
			$partialPathAndFilename = $this->resolveFileNamePath(str_replace('@partial', $partialName, $partialPathAndFilename));
			if (@file_exists($partialPathAndFilename)) {
				return $partialPathAndFilename;
			}
		}
		throw new Exception\InvalidTemplateResourceException('The template files "' . implode('", "', $paths) . '" could not be loaded.', 1225709597);
	}

	/**
	 * Checks whether a template can be resolved for the current request context.
	 *
	 * @param ControllerContext $controllerContext Controller context which is available inside the view
	 * @return boolean
	 * @api
	 */
	public function canRender(ControllerContext $controllerContext) {
		$this->setControllerContext($controllerContext);
		try {
			$this->getTemplateSource();
			return TRUE;
		} catch (Exception\InvalidTemplateResourceException $e) {
			return FALSE;
		}
	}

	/**
	 * Processes following placeholders inside $pattern:
	 *  - "@templateRoot"
	 *  - "@partialRoot"
	 *  - "@layoutRoot"
	 *  - "@subpackage"
	 *  - "@controller"
	 *  - "@format"
	 *
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
	 * @return array unix style paths
	 */
	protected function expandGenericPathPattern($pattern, $bubbleControllerAndSubpackage, $formatIsOptional) {
		$paths = array($pattern);
		$this->expandPatterns($paths, '@templateRoot', $this->getTemplateRootPaths());
		$this->expandPatterns($paths, '@partialRoot', $this->getPartialRootPaths());
		$this->expandPatterns($paths, '@layoutRoot', $this->getLayoutRootPaths());

		/** @var $actionRequest \TYPO3\CMS\Extbase\Mvc\Request */
		$actionRequest = $this->controllerContext->getRequest();
		$subpackageKey = $actionRequest->getControllerSubpackageKey();
		$controllerName = $actionRequest->getControllerName();
		if ($subpackageKey !== NULL) {
			if (strpos($subpackageKey, \TYPO3\CMS\Fluid\Fluid::NAMESPACE_SEPARATOR) !== FALSE) {
				$namespaceSeparator = \TYPO3\CMS\Fluid\Fluid::NAMESPACE_SEPARATOR;
			} else {
				$namespaceSeparator = \TYPO3\CMS\Fluid\Fluid::LEGACY_NAMESPACE_SEPARATOR;
			}
			$subpackageKeyParts = explode($namespaceSeparator, $subpackageKey);
		} else {
			$subpackageKeyParts = array();
		}
		if ($bubbleControllerAndSubpackage) {
			$numberOfPathsBeforeSubpackageExpansion = count($paths);
			$numberOfSubpackageParts = count($subpackageKeyParts);
			$subpackageReplacements = array();
			for ($i = 0; $i <= $numberOfSubpackageParts; $i++) {
				$subpackageReplacements[] = implode('/', ($i < 0 ? $subpackageKeyParts : array_slice($subpackageKeyParts, $i)));
			}
			$this->expandPatterns($paths, '@subpackage', $subpackageReplacements);

			for ($i = ($numberOfPathsBeforeSubpackageExpansion - 1) * ($numberOfSubpackageParts + 1); $i >= 0; $i -= ($numberOfSubpackageParts + 1)) {
				array_splice($paths, $i, 0, str_replace('@controller', $controllerName, $paths[$i]));
			}
			$this->expandPatterns($paths, '@controller', array(''));
		} else {
			$i = $controllerName === NULL ? 0 : -1;
			$this->expandPatterns($paths, '@subpackage', array(implode('/', $i < 0 ? $subpackageKeyParts :
				array_slice($subpackageKeyParts, $i))));
			$this->expandPatterns($paths, '@controller', array($controllerName));
		}

		if ($formatIsOptional) {
			$this->expandPatterns($paths, '.@format', array('.' . $actionRequest->getFormat(), ''));
			$this->expandPatterns($paths, '@format', array($actionRequest->getFormat(), ''));
		} else {
			$this->expandPatterns($paths, '.@format', array('.' . $actionRequest->getFormat()));
			$this->expandPatterns($paths, '@format', array($actionRequest->getFormat()));
		}
		return array_values(array_unique($paths));
	}

	/**
	 * Expands the given $patterns by adding an array element for each $replacement
	 * replacing occurrences of $search.
	 *
	 * @param array $patterns
	 * @param string $search
	 * @param array $replacements
	 * @return void
	 */
	protected function expandPatterns(array &$patterns, $search, array $replacements) {
		$patternsWithReplacements = array();
		foreach ($patterns as $pattern) {
			foreach ($replacements as $replacement) {
				$patternsWithReplacements[] = GeneralUtility::fixWindowsFilePath(str_replace($search, $replacement, $pattern));
			}
		}
		$patterns = $patternsWithReplacements;
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
		/** @var $actionRequest \TYPO3\CMS\Extbase\Mvc\Request */
		$actionRequest = $this->controllerContext->getRequest();
		$extensionName = $actionRequest->getControllerExtensionName();
		$subPackageKey = $actionRequest->getControllerSubpackageKey();
		if ($subPackageKey !== NULL) {
			$extensionName .= '_' . $subPackageKey;
		}
		$controllerName = $actionRequest->getControllerName();
		$templateModifiedTimestamp = filemtime($pathAndFilename);
		$templateIdentifier = sprintf('%s_%s_%s_%s', $extensionName, $controllerName, $prefix, sha1($pathAndFilename . '|' . $templateModifiedTimestamp));
		return $templateIdentifier;
	}

	/**
	 * Wrapper method to make the static call to GeneralUtility mockable in tests
	 *
	 * @param string $pathAndFilename
	 *
	 * @return string absolute pathAndFilename
	 */
	protected function resolveFileNamePath($pathAndFilename) {
		return GeneralUtility::getFileAbsFileName($pathAndFilename);
	}
}
