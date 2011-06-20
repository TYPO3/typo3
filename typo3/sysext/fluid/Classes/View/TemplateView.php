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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Tx_Fluid_View_TemplateView extends Tx_Fluid_View_AbstractTemplateView {

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
	protected $templatePathAndFilenamePattern = '@templateRoot/@subpackage/@controller/@action.@format';

	/**
	 * Directory pattern for global partials. Not part of the public API, should not be changed for now.
	 * @var string
	 */
	private $partialPathAndFilenamePattern = '@partialRoot/@subpackage/@partial.@format';

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
			$this->injectTemplateParser(Tx_Fluid_Compatibility_TemplateParserBuilder::build());
			$this->injectObjectManager(t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager'));
			$this->setRenderingContext($this->objectManager->create('Tx_Fluid_Core_Rendering_RenderingContext'));
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
	 * Checks whether a template can be resolved for the current request context.
	 *
	 * @param Tx_Extbase_MVC_Controller_ControllerContext $controllerContext Controller context which is available inside the view
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function canRender(Tx_Extbase_MVC_Controller_ControllerContext $controllerContext) {
		$this->setControllerContext($controllerContext);
		try {
			$this->getTemplateSource();
			return TRUE;
		} catch (Tx_Fluid_View_Exception_InvalidTemplateResourceException $e) {
			return FALSE;
		}
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
	 * Resolve the template path and filename for the given action. If $actionName
	 * is NULL, looks into the current request.
	 *
	 * @param string $actionName Name of the action. If NULL, will be taken from request.
	 * @return string Full path to template
	 * @throws Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getTemplateSource($actionName = NULL) {
		if ($this->templatePathAndFilename !== NULL) {
			$templatePathAndFilename = $this->templatePathAndFilename;
		} else {
			$actionName = ($actionName !== NULL ? $actionName : $this->controllerContext->getRequest()->getControllerActionName());

			$paths = $this->expandGenericPathPattern($this->templatePathAndFilenamePattern, FALSE, FALSE);
			$found = FALSE;
			foreach ($paths as &$templatePathAndFilename) {
				// These tokens are replaced by the Backporter for the graceful fallback in version 4.
				$fallbackPath = str_replace('@action', $actionName, $templatePathAndFilename);
				$templatePathAndFilename = str_replace('@action', ucfirst($actionName), $templatePathAndFilename);
				if (file_exists($templatePathAndFilename)) {
					$found = TRUE;
					// additional check for deprecated template filename for case insensitive file systems (Windows)
					$realFileName = basename(realpath($templatePathAndFilename));
					if ($realFileName !== ucfirst($realFileName)) {
						t3lib_div::deprecationLog('the template filename "' . t3lib_div::fixWindowsFilePath(realpath($templatePathAndFilename)) . '" is lowercase. This is deprecated since TYPO3 4.4. Please rename the template to "' . basename($templatePathAndFilename) . '"');
					}
					break;
				} elseif (file_exists($fallbackPath)) {
					t3lib_div::deprecationLog('the template filename "' . $fallbackPath . '" is lowercase. This is deprecated since TYPO3 4.4. Please rename the template to "' . basename($templatePathAndFilename) . '"');
					$found = TRUE;
					$templatePathAndFilename = $fallbackPath;
					break;
				}
			}
			if (!$found) {
				throw new Tx_Fluid_View_Exception_InvalidTemplateResourceException('Template could not be loaded. I tried "' . implode('", "', $paths) . '"', 1225709595);
			}
		}

		$templateSource = file_get_contents($templatePathAndFilename);
		if ($templateSource === FALSE) {
			throw new Tx_Fluid_View_Exception_InvalidTemplateResourceException('"' . $templatePathAndFilename . '" is not a valid template resource URI.', 1257246929);
		}
		return $templateSource;
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
	 * @return string Path and filename of layout file
	 * @throws Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getLayoutSource($layoutName = 'Default') {
		if ($this->layoutPathAndFilename !== NULL) {
			 $layoutPathAndFilename = $this->layoutPathAndFilename;
		} else {
			$paths = $this->expandGenericPathPattern($this->layoutPathAndFilenamePattern, TRUE, TRUE);
			$found = FALSE;
			foreach ($paths as &$layoutPathAndFilename) {
				$fallbackPath = str_replace('@layout', $layoutName, $layoutPathAndFilename);
				$layoutPathAndFilename = str_replace('@layout', ucfirst($layoutName), $layoutPathAndFilename);
				if (file_exists($layoutPathAndFilename)) {
					$found = TRUE;
					break;
				} elseif (file_exists($fallbackPath)) {
					t3lib_div::deprecationLog('the layout filename "' . $fallbackPath . '" is lowercase. This is deprecated since TYPO3 4.6 Please rename the layout to "' . basename($layoutPathAndFilename) . '"');
					$found = TRUE;
					$layoutPathAndFilename = $fallbackPath;
					break;
				}
			}

			if (!$found) {
				throw new Tx_Fluid_View_Exception_InvalidTemplateResourceException('The template files "' . implode('", "', $paths) . '" could not be loaded.', 1225709595);
			}
		}

		$layoutSource = file_get_contents($layoutPathAndFilename);
		if ($layoutSource === FALSE) {
			throw new Tx_Fluid_View_Exception_InvalidTemplateResourceException('"' . $layoutPathAndFilename . '" is not a valid template resource URI.', 1257246929);
		}
		return $layoutSource;
	}

	/**
	 * Figures out which partial to use.
	 *
	 * @param string $partialName The name of the partial
	 * @return string the full path which should be used. The path definitely exists.
	 * @throws Tx_Fluid_View_Exception_InvalidTemplateResourceException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getPartialSource($partialName) {
		$paths = $this->expandGenericPathPattern($this->partialPathAndFilenamePattern, TRUE, TRUE);
		$found = FALSE;
		foreach ($paths as &$partialPathAndFilename) {
			$partialPathAndFilename = str_replace('@partial', $partialName, $partialPathAndFilename);
			if (file_exists($partialPathAndFilename)) {
				$found = TRUE;
				break;
			}
		}
		if (!$found) {
			throw new Tx_Fluid_View_Exception_InvalidTemplateResourceException('The template files "' . implode('", "', $paths) . '" could not be loaded.', 1225709595);
		}
		$partialSource = file_get_contents($partialPathAndFilename);
		if ($partialSource === FALSE) {
			throw new Tx_Fluid_View_Exception_InvalidTemplateResourceException('"' . $partialPathAndFilename . '" is not a valid template resource URI.', 1257246929);
		}
		return $partialSource;
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
	 * If pattern is @templateRoot/@subpackage/@controller/@action.@format, then the resulting array is:
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

		$subpackageKey = $this->controllerContext->getRequest()->getControllerSubpackageKey();
		$controllerName = $this->controllerContext->getRequest()->getControllerName();

		$subpackageParts = ($subpackageKey !== NULL) ? explode(Tx_Fluid_Fluid::NAMESPACE_SEPARATOR, $subpackageKey) : array();

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