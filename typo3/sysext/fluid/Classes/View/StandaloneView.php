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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Request as WebRequest;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\ArrayUtility;
use TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3\CMS\Fluid\Core\Parser\TemplateParser;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * A standalone template view.
 * Should be used as view if you want to use Fluid without Extbase extensions
 *
 * @api
 */
class StandaloneView extends AbstractTemplateView
{
    /**
     * Source code of the Fluid template
     *
     * @var string
     */
    protected $templateSource = null;

    /**
     * absolute path of the Fluid template
     *
     * @var string
     */
    protected $templatePathAndFilename = null;

    /**
     * Path(s) to the template root
     *
     * @var string[]
     */
    protected $templateRootPaths = null;

    /**
     * Path(s) to the partial root
     *
     * @var string[]
     */
    protected $partialRootPaths = null;

    /**
     * Path(s) to the layout root
     *
     * @var string[]
     */
    protected $layoutRootPaths = null;

    /**
     * Constructor
     *
     * @param ContentObjectRenderer $contentObject The current cObject. If NULL a new instance will be created
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function __construct(ContentObjectRenderer $contentObject = null)
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var ConfigurationManagerInterface $configurationManager */
        $configurationManager = $this->objectManager->get(ConfigurationManagerInterface::class);
        if ($contentObject === null) {
            /** @var ContentObjectRenderer $contentObject */
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        }
        $configurationManager->setContentObject($contentObject);
        $this->templateParser = $this->objectManager->get(TemplateParser::class);
        $this->setRenderingContext($this->objectManager->get(RenderingContext::class));
        /** @var WebRequest $request */
        $request = $this->objectManager->get(WebRequest::class);
        $request->setRequestURI(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        $request->setBaseURI(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($request);
        /** @var ControllerContext $controllerContext */
        $controllerContext = $this->objectManager->get(ControllerContext::class);
        $controllerContext->setRequest($request);
        $controllerContext->setUriBuilder($uriBuilder);
        $this->setControllerContext($controllerContext);
        $this->templateCompiler = $this->objectManager->get(TemplateCompiler::class);
        // singleton
        $this->templateCompiler->setTemplateCache(GeneralUtility::makeInstance(CacheManager::class)->getCache('fluid_template'));
    }

    /**
     * Sets the format of the current request (default format is "html")
     *
     * @param string $format
     * @return void
     * @api
     */
    public function setFormat($format)
    {
        $this->getRequest()->setFormat($format);
    }

    /**
     * Returns the format of the current request (defaults is "html")
     *
     * @return string $format
     * @api
     */
    public function getFormat()
    {
        return $this->getRequest()->getFormat();
    }

    /**
     * Returns the current request object
     *
     * @return WebRequest
     */
    public function getRequest()
    {
        return $this->controllerContext->getRequest();
    }

    /**
     * Sets the absolute path to a Fluid template file
     *
     * @param string $templatePathAndFilename Fluid template path
     * @return void
     * @api
     */
    public function setTemplatePathAndFilename($templatePathAndFilename)
    {
        $this->templatePathAndFilename = $templatePathAndFilename;
    }

    /**
     * Returns the absolute path to a Fluid template file if it was specified with setTemplatePathAndFilename() before
     *
     * @return string Fluid template path
     * @api
     */
    public function getTemplatePathAndFilename()
    {
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
    public function setTemplateSource($templateSource)
    {
        $this->templateSource = $templateSource;
    }

    /**
     * Set the root path(s) to the templates.
     *
     * @param string[] $templateRootPaths Root paths to the templates.
     * @return void
     * @api
     */
    public function setTemplateRootPaths(array $templateRootPaths)
    {
        $this->templateRootPaths = $templateRootPaths;
    }

    /**
     * Set template by name
     * All set templateRootPaths are checked to find template by given name
     *
     * @param string $templateName Name of the template
     * @throws InvalidTemplateResourceException
     * @api
     */
    public function setTemplate($templateName)
    {
        if ($this->templateRootPaths === null) {
            throw new InvalidTemplateResourceException('No template root path has been specified. Use setTemplateRootPaths().', 1430635895);
        }
        $format = $this->getRequest()->getFormat();
        $templatePathAndFilename = null;
        $possibleTemplatePaths = $this->buildListOfTemplateCandidates($templateName, $this->templateRootPaths, $format);
        foreach ($possibleTemplatePaths as $possibleTemplatePath) {
            if ($this->testFileExistence($possibleTemplatePath)) {
                $templatePathAndFilename = $possibleTemplatePath;
                break;
            }
        }
        if ($templatePathAndFilename !== null) {
            $this->setTemplatePathAndFilename($templatePathAndFilename);
        } else {
            throw new InvalidTemplateResourceException('Could not load template file. Tried following paths: "' . implode('", "', $possibleTemplatePaths) . '".', 1430635896);
        }
    }

    /**
     * Set the root path to the layouts.
     *
     * @param string $layoutRootPath Root path to the layouts.
     * @return void
     * @api
     * @see setLayoutRootPaths()
     * @deprecated since Fluid 7; Use setLayoutRootPaths() instead
     */
    public function setLayoutRootPath($layoutRootPath)
    {
        GeneralUtility::logDeprecatedFunction();
        $this->setLayoutRootPaths([$layoutRootPath]);
    }

    /**
     * Set the root path(s) to the layouts.
     *
     * @param string[] $layoutRootPaths Root path to the layouts
     * @return void
     * @api
     */
    public function setLayoutRootPaths(array $layoutRootPaths)
    {
        $this->layoutRootPaths = $layoutRootPaths;
    }

    /**
     * Returns the first found entry in $this->layoutRootPaths.
     * Don't use, this might not be the desired result.
     *
     * @throws InvalidTemplateResourceException
     * @return string Path to layout root directory
     * @deprecated since Fluid 7; Use getLayoutRootPaths() instead
     */
    public function getLayoutRootPath()
    {
        GeneralUtility::logDeprecatedFunction();
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
    public function getLayoutRootPaths()
    {
        if ($this->layoutRootPaths === null && $this->templatePathAndFilename === null) {
            throw new InvalidTemplateResourceException('No layout root path has been specified. Use setLayoutRootPaths().', 1288091419);
        }
        if ($this->layoutRootPaths === null) {
            $this->layoutRootPaths = [dirname($this->templatePathAndFilename) . '/Layouts'];
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
     * @deprecated since Fluid 7; Use setPartialRootPaths() instead
     */
    public function setPartialRootPath($partialRootPath)
    {
        GeneralUtility::logDeprecatedFunction();
        $this->setPartialRootPaths([$partialRootPath]);
    }

    /**
     * Returns the first found entry in $this->partialRootPaths
     * Don't use, this might not be the desired result.
     *
     * @throws InvalidTemplateResourceException
     * @return string Path to partial root directory
     * @deprecated since Fluid 7; Use getPartialRootPaths() instead
     */
    public function getPartialRootPath()
    {
        GeneralUtility::logDeprecatedFunction();
        $partialRootPaths = $this->getPartialRootPaths();
        return array_shift($partialRootPaths);
    }

    /**
     * Set the root path(s) to the partials.
     * If set, overrides the one determined from $this->partialRootPathPattern
     *
     * @param string[] $partialRootPaths Root paths to the partials. If set, overrides the one determined from $this->partialRootPathPattern
     * @return void
     * @api
     */
    public function setPartialRootPaths(array $partialRootPaths)
    {
        $this->partialRootPaths = $partialRootPaths;
    }

    /**
     * Returns the absolute path to the folder that contains Fluid partial files
     *
     * @return string Fluid partial root path
     * @throws InvalidTemplateResourceException
     * @api
     */
    public function getPartialRootPaths()
    {
        if ($this->partialRootPaths === null && $this->templatePathAndFilename === null) {
            throw new InvalidTemplateResourceException('No partial root path has been specified. Use setPartialRootPaths().', 1288094511);
        }
        if ($this->partialRootPaths === null) {
            $this->partialRootPaths = [dirname($this->templatePathAndFilename) . '/Partials'];
        }
        return $this->partialRootPaths;
    }

    /**
     * Checks whether a template can be resolved for the current request
     *
     * @return bool
     * @api
     */
    public function hasTemplate()
    {
        try {
            $this->getTemplateSource();
            return true;
        } catch (InvalidTemplateResourceException $e) {
            return false;
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
    protected function getTemplateIdentifier($actionName = null)
    {
        if ($this->templateSource === null) {
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
    protected function getTemplateSource($actionName = null)
    {
        if ($this->templateSource === null && $this->templatePathAndFilename === null) {
            throw new InvalidTemplateResourceException('No template has been specified. Use either setTemplateSource() or setTemplatePathAndFilename().', 1288085266);
        }
        if ($this->templateSource === null) {
            if (!$this->testFileExistence($this->templatePathAndFilename)) {
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
    protected function getLayoutIdentifier($layoutName = 'Default')
    {
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
    protected function getLayoutSource($layoutName = 'Default')
    {
        $layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
        $layoutSource = file_get_contents($layoutPathAndFilename);
        if ($layoutSource === false) {
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
    protected function getLayoutPathAndFilename($layoutName = 'Default')
    {
        $possibleLayoutPaths = $this->buildListOfTemplateCandidates($layoutName, $this->getLayoutRootPaths(), $this->getRequest()->getFormat());
        foreach ($possibleLayoutPaths as $layoutPathAndFilename) {
            if ($this->testFileExistence($layoutPathAndFilename)) {
                return $layoutPathAndFilename;
            }
        }

        throw new InvalidTemplateResourceException('Could not load layout file. Tried following paths: "' . implode('", "', $possibleLayoutPaths) . '".', 1288092555);
    }

    /**
     * Returns a unique identifier for the resolved partial file.
     * This identifier is based on the template path and last modification date
     *
     * @param string $partialName The name of the partial
     * @return string partial identifier
     * @throws InvalidTemplateResourceException
     */
    protected function getPartialIdentifier($partialName)
    {
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
    protected function getPartialSource($partialName)
    {
        $partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
        $partialSource = file_get_contents($partialPathAndFilename);
        if ($partialSource === false) {
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
    protected function getPartialPathAndFilename($partialName)
    {
        $possiblePartialPaths = $this->buildListOfTemplateCandidates($partialName, $this->getPartialRootPaths(), $this->getRequest()->getFormat());
        foreach ($possiblePartialPaths as $partialPathAndFilename) {
            if ($this->testFileExistence($partialPathAndFilename)) {
                return $partialPathAndFilename;
            }
        }
        throw new InvalidTemplateResourceException('Could not load partial file. Tried following paths: "' . implode('", "', $possiblePartialPaths) . '".', 1288092556);
    }

    /**
     * Builds a list of possible candidates for a given template name
     *
     * @param string $templateName Name of the template to search for
     * @param array $paths Paths to search in
     * @param string $format The file format to use. e.g 'html' or 'txt'
     * @return array Array of paths to search for the template file
     */
    protected function buildListOfTemplateCandidates($templateName, array $paths, $format)
    {
        $upperCasedTemplateName = $this->ucFileNameInPath($templateName);
        $possibleTemplatePaths = [];
        $paths = ArrayUtility::sortArrayWithIntegerKeys($paths);
        $paths = array_reverse($paths, true);
        foreach ($paths as $layoutRootPath) {
            $possibleTemplatePaths[] = $this->resolveFileNamePath($layoutRootPath . '/' . $upperCasedTemplateName . '.' . $format);
            $possibleTemplatePaths[] = $this->resolveFileNamePath($layoutRootPath . '/' . $upperCasedTemplateName);
            if ($upperCasedTemplateName !== $templateName) {
                $possibleTemplatePaths[] = $this->resolveFileNamePath($layoutRootPath . '/' . $templateName . '.' . $format);
                $possibleTemplatePaths[] = $this->resolveFileNamePath($layoutRootPath . '/' . $templateName);
            }
        }
        return $possibleTemplatePaths;
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
    protected function createIdentifierForFile($pathAndFilename, $prefix)
    {
        $templateModifiedTimestamp = filemtime($pathAndFilename);
        $templateIdentifier = sprintf('Standalone_%s_%s', $prefix, sha1($pathAndFilename . '|' . $templateModifiedTimestamp));
        $templateIdentifier = str_replace('/', '_', str_replace('.', '_', $templateIdentifier));
        return $templateIdentifier;
    }

    /**
     * Wrapper method to make the static call to GeneralUtility mockable in tests
     *
     * @param string $pathAndFilename
     * @return string absolute pathAndFilename
     */
    protected function resolveFileNamePath($pathAndFilename)
    {
        return GeneralUtility::getFileAbsFileName(GeneralUtility::fixWindowsFilePath($pathAndFilename), false);
    }
}
