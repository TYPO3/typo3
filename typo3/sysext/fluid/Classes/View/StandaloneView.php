<?php
namespace TYPO3\CMS\Fluid\View;

/**                                                                       *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ArrayUtility;
use TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * A standalone template view.
 * Should be used as view if you want to use Fluid without Extbase extensions
 *
 * @api
 */
class StandaloneView extends AbstractTemplateView {

	/**
	 * Source code of the Fluid template
	 *
	 * @var string
	 */
	protected $templateSource = NULL;

	/**
	 * absolute path of the Fluid template
	 *
	 * @var string
	 */
	protected $templatePathAndFilename = NULL;


	/**
	 * absolute root path of the folder that contains Fluid layouts
	 *
	 * @var string
	 */
	protected $layoutRootPath = NULL;

	/**
	 * absolute root path of the folder that contains Fluid partials
	 *
	 * @var string
	 */
	protected $partialRootPath = NULL;

	/**
	 * Path(s) to the partial root
	 *
	 * @var array
	 */
	protected $partialRootPaths = NULL;

	/**
	 * Path(s) to the layout root
	 *
	 * @var array
	 */
	protected $layoutRootPaths = NULL;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler
	 */
	protected $templateCompiler;

	/**
	 * Constructor
	 *
	 * @param ContentObjectRenderer $contentObject The current cObject. If NULL a new instance will be created
	 * @throws \InvalidArgumentException
	 * @throws \UnexpectedValueException
	 */
	public function __construct(ContentObjectRenderer $contentObject = NULL) {
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		if ($contentObject === NULL) {
			/** @var ContentObjectRenderer $contentObject */
			$contentObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		}
		$configurationManager->setContentObject($contentObject);
		$this->templateParser = $this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser');
		$this->setRenderingContext($this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContext'));
		/** @var \TYPO3\CMS\Extbase\Mvc\Web\Request $request */
		$request = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Request');
		$request->setRequestURI(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseURI(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
		/** @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
		$uriBuilder = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
		$uriBuilder->setRequest($request);
		/** @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext */
		$controllerContext = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerContext');
		$controllerContext->setRequest($request);
		$controllerContext->setUriBuilder($uriBuilder);
		$this->setControllerContext($controllerContext);
		$this->templateCompiler = $this->objectManager->get('TYPO3\\CMS\\Fluid\\Core\\Compiler\\TemplateCompiler');
		// singleton
		$this->templateCompiler->setTemplateCache(GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('fluid_template'));
	}

	/**
	 * Sets the format of the current request (default format is "html")
	 *
	 * @param string $format
	 * @return void
	 * @api
	 */
	public function setFormat($format) {
		$this->getRequest()->setFormat($format);
	}

	/**
	 * Returns the format of the current request (defaults is "html")
	 *
	 * @return string $format
	 * @api
	 */
	public function getFormat() {
		return $this->getRequest()->getFormat();
	}

	/**
	 * Returns the current request object
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Request
	 */
	public function getRequest() {
		return $this->controllerContext->getRequest();
	}

	/**
	 * Sets the absolute path to a Fluid template file
	 *
	 * @param string $templatePathAndFilename Fluid template path
	 * @return void
	 * @api
	 */
	public function setTemplatePathAndFilename($templatePathAndFilename) {
		$this->templatePathAndFilename = $templatePathAndFilename;
	}

	/**
	 * Returns the absolute path to a Fluid template file if it was specified with setTemplatePathAndFilename() before
	 *
	 * @return string Fluid template path
	 * @api
	 */
	public function getTemplatePathAndFilename() {
		return $this->templatePathAndFilename;
	}

	/**
	 * Sets the Fluid template source
	 * You can use setTemplatePathAndFilename() alternatively if you only want to specify the template path
	 *
	 * @param string $templateSource Fluid template source code
	 * @return void
	 * @api
	 */
	public function setTemplateSource($templateSource) {
		$this->templateSource = $templateSource;
	}

	/**
	 * Set the root path to the layouts.
	 *
	 * @param string $layoutRootPath Root path to the layouts.
	 * @return void
	 * @api
	 * @see setLayoutRootPaths()
	 */
	public function setLayoutRootPath($layoutRootPath) {
		$this->layoutRootPath = $layoutRootPath;
		$this->setLayoutRootPaths(array($layoutRootPath));
	}

	/**
	 * Set the root path(s) to the layouts.
	 *
	 * @param array $layoutRootPaths Root path to the layouts
	 * @return void
	 * @api
	 */
	public function setLayoutRootPaths(array $layoutRootPaths) {
		$this->layoutRootPaths = $layoutRootPaths;
	}

	/**
	 * Returns the first found entry in $this->layoutRootPaths.
	 * Don't use, this might not be the desired result.
	 *
	 * @throws InvalidTemplateResourceException
	 * @return string Path to layout root directory
	 */
	public function getLayoutRootPath() {
		$layoutRootPaths = $this->getLayoutRootPaths();
		return array_shift($layoutRootPaths);
	}

	/**
	 * Resolves the layout root to be used inside other paths.
	 *
	 * @return string Fluid layout root path
	 * @throws InvalidTemplateResourceException
	 * @api
	 */
	public function getLayoutRootPaths() {
		if ($this->layoutRootPaths === NULL && $this->templatePathAndFilename === NULL) {
			throw new InvalidTemplateResourceException('No layout root path has been specified. Use setLayoutRootPaths().', 1288091419);
		}
		if ($this->layoutRootPaths === NULL) {
			$this->layoutRootPaths = array(dirname($this->templatePathAndFilename) . '/Layouts');
		}
		return $this->layoutRootPaths;
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
		$this->partialRootPath = $partialRootPath;
		$this->setPartialRootPaths(array($partialRootPath));
	}

	/**
	 * Returns the first found entry in $this->partialRootPaths
	 * Don't use, this might not be the desired result.
	 *
	 * @throws InvalidTemplateResourceException
	 * @return string Path to partial root directory
	 */
	public function getPartialRootPath() {
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
	 * Returns the absolute path to the folder that contains Fluid partial files
	 *
	 * @return string Fluid partial root path
	 * @throws InvalidTemplateResourceException
	 * @api
	 */
	public function getPartialRootPaths() {
		if ($this->partialRootPaths === NULL && $this->templatePathAndFilename === NULL) {
			throw new InvalidTemplateResourceException('No partial root path has been specified. Use setPartialRootPaths().', 1288094511);
		}
		if ($this->partialRootPaths === NULL) {
			$this->partialRootPaths = array(dirname($this->templatePathAndFilename) . '/Partials');
		}
		return $this->partialRootPaths;
	}

	/**
	 * Checks whether a template can be resolved for the current request
	 *
	 * @return boolean
	 * @api
	 */
	public function hasTemplate() {
		try {
			$this->getTemplateSource();
			return TRUE;
		} catch (InvalidTemplateResourceException $e) {
			return FALSE;
		}
	}

	/**
	 * Returns a unique identifier for the resolved template file
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $actionName Name of the action. This argument is not used in this view!
	 * @return string template identifier
	 * @throws InvalidTemplateResourceException
	 */
	protected function getTemplateIdentifier($actionName = NULL) {
		if ($this->templateSource === NULL) {
			$templatePathAndFilename = $this->getTemplatePathAndFilename();
			$templatePathAndFilenameInfo = pathinfo($templatePathAndFilename);
			$templateFilenameWithoutExtension = basename($templatePathAndFilename, '.' . $templatePathAndFilenameInfo['extension']);
			$prefix = sprintf('template_file_%s', $templateFilenameWithoutExtension);
			return $this->createIdentifierForFile($templatePathAndFilename, $prefix);
		} else {
			$templateSource = $this->getTemplateSource();
			$prefix = 'template_source';
			$templateIdentifier = sprintf('Standalone_%s_%s', $prefix, sha1($templateSource));
			return $templateIdentifier;
		}
	}

	/**
	 * Returns the Fluid template source code
	 *
	 * @param string $actionName Name of the action. This argument is not used in this view!
	 * @return string Fluid template source
	 * @throws InvalidTemplateResourceException
	 */
	protected function getTemplateSource($actionName = NULL) {
		if ($this->templateSource === NULL && $this->templatePathAndFilename === NULL) {
			throw new InvalidTemplateResourceException('No template has been specified. Use either setTemplateSource() or setTemplatePathAndFilename().', 1288085266);
		}
		if ($this->templateSource === NULL) {
			if (!is_file($this->templatePathAndFilename)) {
				throw new InvalidTemplateResourceException('Template could not be found at "' . $this->templatePathAndFilename . '".', 1288087061);
			}
			$this->templateSource = file_get_contents($this->templatePathAndFilename);
		}
		return $this->templateSource;
	}

	/**
	 * Returns a unique identifier for the resolved layout file.
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $layoutName The name of the layout
	 * @return string layout identifier
	 * @throws InvalidTemplateResourceException
	 */
	protected function getLayoutIdentifier($layoutName = 'Default') {
		$layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
		$prefix = 'layout_' . $layoutName;
		return $this->createIdentifierForFile($layoutPathAndFilename, $prefix);
	}

	/**
	 * Resolves the path and file name of the layout file, based on
	 * $this->getLayoutRootPaths() and request format and returns the file contents
	 *
	 * @param string $layoutName Name of the layout to use. If none given, use "Default"
	 * @return string contents of the layout file if it was found
	 * @throws InvalidTemplateResourceException
	 */
	protected function getLayoutSource($layoutName = 'Default') {
		$layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
		$layoutSource = file_get_contents($layoutPathAndFilename);
		if ($layoutSource === FALSE) {
			throw new InvalidTemplateResourceException('"' . $layoutPathAndFilename . '" is not a valid template resource URI.', 1312215888);
		}
		return $layoutSource;
	}

	/**
	 * Resolve the path and file name of the layout file, based on
	 * $this->getLayoutRootPaths() and request format
	 *
	 * In case a layout has already been set with setLayoutPathAndFilename(),
	 * this method returns that path, otherwise a path and filename will be
	 * resolved using the layoutPathAndFilenamePattern.
	 *
	 * @param string $layoutName Name of the layout to use. If none given, use "Default"
	 * @return string Path and filename of layout files
	 * @throws InvalidTemplateResourceException
	 */
	protected function getLayoutPathAndFilename($layoutName = 'Default') {
		$upperCasedLayoutName = ucfirst($layoutName);
		$possibleLayoutPaths = array();
		$paths = ArrayUtility::sortArrayWithIntegerKeys($this->getLayoutRootPaths());
		$paths = array_reverse($paths, TRUE);
		foreach ($paths as $layoutRootPath) {
			$possibleLayoutPaths[] = GeneralUtility::fixWindowsFilePath($layoutRootPath . '/' . $upperCasedLayoutName . '.' . $this->getRequest()->getFormat());
			$possibleLayoutPaths[] = GeneralUtility::fixWindowsFilePath($layoutRootPath . '/' . $upperCasedLayoutName);
			if ($upperCasedLayoutName !== $layoutName) {
				$possibleLayoutPaths[] = GeneralUtility::fixWindowsFilePath($layoutRootPath . '/' . $layoutName . '.' . $this->getRequest()->getFormat());
				$possibleLayoutPaths[] = GeneralUtility::fixWindowsFilePath($layoutRootPath . '/' . $layoutName);
			}
		}
		foreach ($possibleLayoutPaths as $layoutPathAndFilename) {
			if ($this->testFileExistence($layoutPathAndFilename)) {
				return $layoutPathAndFilename;
			}
		}

		throw new InvalidTemplateResourceException('Could not load layout file. Tried following paths: "' . implode('", "', $possibleLayoutPaths) . '".', 1288092555);
	}

	/**
	 * Wrapper method for is_file function for testing reasons
	 *
	 * @param string $filePath
	 * @return bool
	 */
	protected function testFileExistence($filePath) {
		return is_file($filePath);
	}

	/**
	 * Returns a unique identifier for the resolved partial file.
	 * This identifier is based on the template path and last modification date
	 *
	 * @param string $partialName The name of the partial
	 * @return string partial identifier
	 * @throws InvalidTemplateResourceException
	 */
	protected function getPartialIdentifier($partialName) {
		$partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
		$prefix = 'partial_' . $partialName;
		return $this->createIdentifierForFile($partialPathAndFilename, $prefix);
	}

	/**
	 * Resolves the path and file name of the partial file, based on
	 * $this->getPartialRootPath() and request format and returns the file contents
	 *
	 * @param string $partialName The name of the partial
	 * @return string contents of the layout file if it was found
	 * @throws InvalidTemplateResourceException
	 */
	protected function getPartialSource($partialName) {
		$partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
		$partialSource = file_get_contents($partialPathAndFilename);
		if ($partialSource === FALSE) {
			throw new InvalidTemplateResourceException('"' . $partialPathAndFilename . '" is not a valid template resource URI.', 1257246932);
		}
		return $partialSource;
	}

	/**
	 * Resolve the partial path and filename based on $this->getPartialRootPaths() and request format
	 *
	 * @param string $partialName The name of the partial
	 * @return string The full path which should be used. The path definitely exists.
	 * @throws InvalidTemplateResourceException
	 */
	protected function getPartialPathAndFilename($partialName) {
		$upperCasedPartialName = ucfirst($partialName);
		$paths = ArrayUtility::sortArrayWithIntegerKeys($this->getPartialRootPaths());
		$paths = array_reverse($paths, TRUE);
		$possiblePartialPaths = array();
		foreach ($paths as $partialRootPath) {
			$possiblePartialPaths[] = GeneralUtility::fixWindowsFilePath($partialRootPath . '/' . $upperCasedPartialName . '.' . $this->getRequest()->getFormat());
			$possiblePartialPaths[] = GeneralUtility::fixWindowsFilePath($partialRootPath . '/' . $upperCasedPartialName);
			if ($upperCasedPartialName !== $partialName) {
				$possiblePartialPaths[] = GeneralUtility::fixWindowsFilePath($partialRootPath . '/' . $partialName . '.' . $this->getRequest()->getFormat());
				$possiblePartialPaths[] = GeneralUtility::fixWindowsFilePath($partialRootPath . '/' . $partialName);
			}
		}
		foreach ($possiblePartialPaths as $partialPathAndFilename) {
			if ($this->testFileExistence($partialPathAndFilename)) {
				return $partialPathAndFilename;
			}
		}
		throw new InvalidTemplateResourceException('Could not load partial file. Tried following paths: "' . implode('", "', $possiblePartialPaths) . '".', 1288092556);
	}

	/**
	 * Returns a unique identifier for the given file in the format
	 * Standalone_<prefix>_<SHA1>
	 * The SH1 hash is a checksum that is based on the file path and last modification date
	 *
	 * @param string $pathAndFilename
	 * @param string $prefix
	 * @return string
	 */
	protected function createIdentifierForFile($pathAndFilename, $prefix) {
		$templateModifiedTimestamp = filemtime($pathAndFilename);
		$templateIdentifier = sprintf('Standalone_%s_%s', $prefix, sha1($pathAndFilename . '|' . $templateModifiedTimestamp));
		$templateIdentifier = str_replace('/', '_', str_replace('.', '_', $templateIdentifier));
		return $templateIdentifier;
	}
}
