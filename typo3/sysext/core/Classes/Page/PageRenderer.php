<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Page;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Resource\RelativeCssPathFixer;
use TYPO3\CMS\Core\Resource\ResourceCompressor;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceDoesNotExistException;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * TYPO3 pageRender class
 * This class render the HTML of a webpage, usable for BE and FE
 */
class PageRenderer implements SingletonInterface
{
    // Constants for the part to be rendered
    protected const PART_COMPLETE = 0;
    protected const PART_HEADER = 1;
    protected const PART_FOOTER = 2;

    protected bool $compressJavascript = false;
    protected bool $compressCss = false;
    protected bool $concatenateJavascript = false;
    protected bool $concatenateCss = false;
    protected bool $moveJsFromHeaderToFooter = false;

    /**
     * The locale, used for the <html> tag (depending on the DocType) and possible translation files.
     */
    protected Locale $locale;

    // Arrays containing associative arrays for the included files
    /**
     * @var array<string, array>
     */
    protected array $jsFiles = [];
    protected array $jsLibs = [];

    /**
     * @var array<string, array>
     */
    protected array $cssFiles = [];

    /**
     * @var array<string, array>
     */
    protected array $cssLibs = [];

    /**
     * The title of the page
     */
    protected string $title = '';
    protected string $favIcon = '';

    // Static header blocks
    protected string $xmlPrologAndDocType = '';
    protected array $inlineComments = [];
    protected array $headerData = [];
    protected array $footerData = [];
    protected string $titleTag = '<title>|</title>';
    protected string $htmlTag = '<html>';
    protected string $headTag = '<head>';
    protected string $iconMimeType = '';
    protected string $shortcutTag = '<link rel="icon" href="%1$s"%2$s />';

    // Static inline code blocks
    /**
     * @var array<string, array>
     */
    protected array $jsInline = [];

    /**
     * @var array<string, array>
     */
    protected array $cssInline = [];
    protected string $bodyContent = '';
    protected string $templateFile = '';
    protected array $inlineLanguageLabels = [];
    protected array $inlineLanguageLabelFiles = [];
    protected array $inlineSettings = [];

    /**
     * Is empty string for HTML and ' /' for XHTML rendering
     */
    protected string $endingSlash = '';

    protected JavaScriptRenderer $javaScriptRenderer;
    protected ?ConsumableNonce $nonce = null;
    protected DocType $docType = DocType::html5;
    protected bool $applyNonceHint = false;

    public function __construct(
        #[Autowire(service: 'cache.assets')]
        protected readonly FrontendInterface $assetsCache,
        protected readonly MarkerBasedTemplateService $templateService,
        protected readonly MetaTagManagerRegistry $metaTagRegistry,
        protected readonly AssetRenderer $assetRenderer,
        protected readonly AssetCollector $assetCollector,
        protected readonly ResourceCompressor $resourceCompressor,
        protected readonly RelativeCssPathFixer $relativeCssPathFixer,
        protected readonly LanguageServiceFactory $languageServiceFactory,
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly StreamFactoryInterface $streamFactory,
        protected readonly IconRegistry $iconRegistry,
        protected readonly SystemResourcePublisherInterface $resourcePublisher,
        protected readonly SystemResourceFactory $systemResourceFactory,
    ) {
        $this->reset();
        $this->setMetaTag('name', 'generator', 'TYPO3 CMS');
    }

    /**
     * @internal
     */
    public function updateState(array $state): void
    {
        foreach ($state as $var => $value) {
            switch ($var) {
                case 'assetsCache':
                case 'assetRenderer':
                case 'assetCollector':
                case 'templateService':
                case 'resourceCompressor':
                case 'relativeCssPathFixer':
                case 'languageServiceFactory':
                case 'responseFactory':
                case 'streamFactory':
                case 'iconRegistry':
                case 'resourcePublisher':
                case 'systemResourceFactory':
                    break;
                case 'nonce':
                    break;
                case 'metaTagRegistry':
                    $this->metaTagRegistry->updateState($value);
                    break;
                case 'javaScriptRenderer':
                    $this->javaScriptRenderer->updateState($value);
                    break;
                default:
                    $this->{$var} = $value;
                    break;
            }
        }
    }

    /**
     * @internal
     */
    public function getState(): array
    {
        $state = [];
        foreach (get_object_vars($this) as $var => $value) {
            switch ($var) {
                case 'assetsCache':
                case 'assetRenderer':
                case 'templateService':
                case 'resourceCompressor':
                case 'relativeCssPathFixer':
                case 'languageServiceFactory':
                case 'responseFactory':
                case 'streamFactory':
                case 'iconRegistry':
                case 'resourcePublisher':
                case 'systemResourceFactory':
                    // @todo: bodyContent is cached twice: once in 'content' of pageRow (see FE setPageCacheContent()),
                    //        and a second time because it is added using addBodyContent() as well during page generation.
                    //        An easy solution is to exclude it here, but a bigger overhaul of the entire 'marker' madness
                    //        is what is *really* needed. Also see the various 'cached' and 'uncached' render methods.
                    break;
                case 'nonce':
                    break;
                case 'metaTagRegistry':
                    $state[$var] = $this->metaTagRegistry->getState();
                    break;
                case 'javaScriptRenderer':
                    $state[$var] = $this->javaScriptRenderer->getState();
                    break;
                default:
                    $state[$var] = $value;
                    break;
            }
        }
        return $state;
    }

    public function getJavaScriptRenderer(): JavaScriptRenderer
    {
        return $this->javaScriptRenderer;
    }

    /**
     * Reset all vars to initial values
     */
    protected function reset(): void
    {
        $this->locale = new Locale();
        $this->setDocType(DocType::html5);
        $this->templateFile = 'PKG:typo3/cms-core:Resources/Private/Templates/PageRenderer.html';
        $this->bodyContent = '';
        $this->jsFiles = [];
        $this->jsInline = [];
        $this->jsLibs = [];
        $this->cssFiles = [];
        $this->cssInline = [];
        $this->inlineComments = [];
        $this->headerData = [];
        $this->footerData = [];
        $this->javaScriptRenderer = JavaScriptRenderer::create();
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Setters                                   */
    /*                                                   */
    /*                                                   */
    /*****************************************************/
    /**
     * Sets the title
     *
     * @param string $title	title of webpage
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Sets xml prolog and docType
     *
     * @param string $xmlPrologAndDocType Complete tags for xml prolog and docType
     */
    public function setXmlPrologAndDocType($xmlPrologAndDocType)
    {
        $this->xmlPrologAndDocType = $xmlPrologAndDocType;
    }

    /**
     * Sets language
     */
    public function setLanguage(Locale $locale): void
    {
        $this->locale = $locale;
        $this->setDefaultHtmlTag();
    }

    /**
     * Internal method to set a basic <html> tag when in HTML5 with the proper language/locale and "dir"
     * attributes.
     */
    protected function setDefaultHtmlTag(): void
    {
        if ($this->docType === DocType::html5) {
            $attributes = [
                'lang' => $this->locale->getName(),
            ];
            if ($this->locale->isRightToLeftLanguageDirection()) {
                $attributes['dir'] = 'rtl';
            }
            // TODO: build an API to add HTML attributes cleanly
            if ($this->getApplicationType() === 'BE') {
                $context = GeneralUtility::makeInstance(Context::class);
                $backendUser = $context->getAspect('backend.user');

                if ($backendUser->isLoggedIn()) {
                    $userTS = $GLOBALS['BE_USER']->getTSConfig();

                    $themeDisabled = $userTS['setup.']['fields.']['theme.']['disabled'] ?? '0';
                    $theme = $GLOBALS['BE_USER']->uc['theme'] ?? $userTS['setup.']['fields.']['theme'] ?? 'auto';
                    if ($themeDisabled === '1') {
                        $theme = $userTS['setup.']['fields.']['theme'] ?? 'modern';
                    }
                    if ($theme !== 'modern') {
                        $attributes['data-theme'] = $theme;
                    }

                    $colorSchemeDisabled = $userTS['setup.']['fields.']['colorScheme.']['disabled'] ?? '0';
                    $colorScheme = $GLOBALS['BE_USER']->uc['colorScheme'] ?? $userTS['setup.']['fields.']['colorScheme'] ?? 'auto';
                    if ($colorSchemeDisabled === '1') {
                        $colorScheme = $userTS['setup.']['fields.']['colorScheme'] ?? 'light';
                    }
                    if ($colorScheme !== 'auto') {
                        $attributes['data-color-scheme'] = $colorScheme;
                    }
                }
            }
            $this->setHtmlTag('<html ' . GeneralUtility::implodeAttributes($attributes, true) . '>');
        }
    }

    /**
     * Sets html tag
     *
     * @param string $htmlTag Html tag
     */
    public function setHtmlTag($htmlTag)
    {
        $this->htmlTag = $htmlTag;
    }

    /**
     * Sets HTML head tag
     *
     * @param string $headTag HTML head tag
     */
    public function setHeadTag($headTag)
    {
        $this->headTag = $headTag;
    }

    /**
     * Sets favicon
     *
     * @param string $favIcon
     */
    public function setFavIcon($favIcon)
    {
        $this->favIcon = $favIcon;
    }

    /**
     * Sets icon mime type
     *
     * @param string $iconMimeType
     */
    public function setIconMimeType($iconMimeType)
    {
        $this->iconMimeType = $iconMimeType;
    }

    /**
     * Sets template file
     *
     * @param string $file
     */
    public function setTemplateFile($file)
    {
        $this->templateFile = $file;
    }

    /**
     * Sets Content for Body
     *
     * @param string $content
     */
    public function setBodyContent($content)
    {
        $this->bodyContent = $content;
    }

    public function setApplyNonceHint(bool $applyNonceHint): void
    {
        $this->applyNonceHint = $applyNonceHint;
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Enablers / Disablers                      */
    /*                                                   */
    /*                                                   */
    /*****************************************************/
    /**
     * Enables MoveJsFromHeaderToFooter
     */
    public function enableMoveJsFromHeaderToFooter()
    {
        $this->moveJsFromHeaderToFooter = true;
    }

    /**
     * Disables MoveJsFromHeaderToFooter
     */
    public function disableMoveJsFromHeaderToFooter()
    {
        $this->moveJsFromHeaderToFooter = false;
    }

    /**
     * Enables compression of javascript
     */
    public function enableCompressJavascript()
    {
        $this->compressJavascript = true;
    }

    /**
     * Disables compression of javascript
     */
    public function disableCompressJavascript()
    {
        $this->compressJavascript = false;
    }

    /**
     * Enables compression of css
     */
    public function enableCompressCss()
    {
        $this->compressCss = true;
    }

    /**
     * Disables compression of css
     */
    public function disableCompressCss()
    {
        $this->compressCss = false;
    }

    /**
     * Enables concatenation of js files
     */
    public function enableConcatenateJavascript()
    {
        $this->concatenateJavascript = true;
    }

    /**
     * Disables concatenation of js files
     */
    public function disableConcatenateJavascript()
    {
        $this->concatenateJavascript = false;
    }

    /**
     * Enables concatenation of css files
     */
    public function enableConcatenateCss()
    {
        $this->concatenateCss = true;
    }

    /**
     * Disables concatenation of css files
     */
    public function disableConcatenateCss()
    {
        $this->concatenateCss = false;
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Getters                                   */
    /*                                                   */
    /*                                                   */
    /*****************************************************/
    /**
     * Gets the title
     *
     * @return string $title Title of webpage
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Gets the language
     */
    public function getLanguage(): string
    {
        return (string)$this->locale;
    }

    public function setNonce(?ConsumableNonce $nonce): void
    {
        $this->nonce = $nonce;
    }

    public function setDocType(DocType $docType): void
    {
        $this->docType = $docType;
        $this->xmlPrologAndDocType = $docType->getDoctypeDeclaration();
        $this->setDefaultHtmlTag();
    }

    public function getDocType(): DocType
    {
        return $this->docType;
    }

    /**
     * Gets html tag
     *
     * @return string $htmlTag Html tag
     */
    public function getHtmlTag()
    {
        return $this->htmlTag;
    }

    /**
     * Gets head tag
     *
     * @return string $tag Head tag
     */
    public function getHeadTag()
    {
        return $this->headTag;
    }

    /**
     * Gets favicon
     *
     * @return string $favIcon
     */
    public function getFavIcon()
    {
        return $this->favIcon;
    }

    /**
     * Gets icon mime type
     *
     * @return string $iconMimeType
     */
    public function getIconMimeType()
    {
        return $this->iconMimeType;
    }

    /**
     * Gets template file
     *
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->templateFile;
    }

    /**
     * Gets MoveJsFromHeaderToFooter
     *
     * @return bool
     */
    public function getMoveJsFromHeaderToFooter()
    {
        return $this->moveJsFromHeaderToFooter;
    }

    /**
     * Gets compress of javascript
     *
     * @return bool
     */
    public function getCompressJavascript()
    {
        return $this->compressJavascript;
    }

    /**
     * Gets compress of css
     *
     * @return bool
     */
    public function getCompressCss()
    {
        return $this->compressCss;
    }

    /**
     * Gets concatenate of js files
     *
     * @return bool
     */
    public function getConcatenateJavascript()
    {
        return $this->concatenateJavascript;
    }

    /**
     * Gets concatenate of css files
     *
     * @return bool
     */
    public function getConcatenateCss()
    {
        return $this->concatenateCss;
    }

    /**
     * Gets content for body
     *
     * @return string
     */
    public function getBodyContent()
    {
        return $this->bodyContent;
    }

    /**
     * Gets the inline language labels.
     *
     * @return array The inline language labels
     */
    public function getInlineLanguageLabels()
    {
        return $this->inlineLanguageLabels;
    }

    /**
     * Gets the inline language files
     *
     * @return array
     */
    public function getInlineLanguageLabelFiles()
    {
        return $this->inlineLanguageLabelFiles;
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Functions to add Data                     */
    /*                                                   */
    /*                                                   */
    /*****************************************************/

    /**
     * Sets a given meta tag
     *
     * @param string $type The type of the meta tag. Allowed values are property, name or http-equiv
     * @param string $name The name of the property to add
     * @param string $content The content of the meta tag
     * @param array $subProperties Subproperties of the meta tag (like e.g. og:image:width)
     * @param bool $replace Replace earlier set meta tag
     * @throws \InvalidArgumentException
     */
    public function setMetaTag(string $type, string $name, string $content, array $subProperties = [], $replace = true)
    {
        // Lowercase all the things
        $type = strtolower($type);
        $name = strtolower($name);
        if (!in_array($type, ['property', 'name', 'http-equiv'], true)) {
            throw new \InvalidArgumentException(
                'When setting a meta tag the only types allowed are property, name or http-equiv. "' . $type . '" given.',
                1496402460
            );
        }
        $manager = $this->metaTagRegistry->getManagerForProperty($name);
        $manager->addProperty($name, $content, $subProperties, $replace, $type);
    }

    /**
     * Returns the requested meta tag
     */
    public function getMetaTag(string $type, string $name): array
    {
        // Lowercase all the things
        $type = strtolower($type);
        $name = strtolower($name);

        $manager = $this->metaTagRegistry->getManagerForProperty($name);
        $propertyContent = $manager->getProperty($name, $type);

        if (!empty($propertyContent[0])) {
            return [
                'type' => $type,
                'name' => $name,
                'content' => $propertyContent[0]['content'],
            ];
        }
        return [];
    }

    /**
     * Unset the requested meta tag
     */
    public function removeMetaTag(string $type, string $name)
    {
        // Lowercase all the things
        $type = strtolower($type);
        $name = strtolower($name);

        $manager = $this->metaTagRegistry->getManagerForProperty($name);
        $manager->removeProperty($name, $type);
    }

    /**
     * Adds inline HTML comment
     *
     * @param string $comment
     */
    public function addInlineComment($comment)
    {
        if (!in_array($comment, $this->inlineComments)) {
            $this->inlineComments[] = $comment;
        }
    }

    /**
     * Adds header data
     *
     * @param string $data Free header data for HTML header
     */
    public function addHeaderData($data)
    {
        if (!in_array($data, $this->headerData)) {
            $this->headerData[] = $data;
        }
    }

    /**
     * Adds footer data
     *
     * @param string $data Free footer data for HTML footer before closing body tag
     */
    public function addFooterData($data)
    {
        if (!in_array($data, $this->footerData)) {
            $this->footerData[] = $data;
        }
    }

    /**
     * Adds JS Library. JS Library block is rendered on top of the JS files.
     *
     * @param string $name Arbitrary identifier
     * @param string $file File name
     * @param string|null $type Content Type
     * @param bool $compress Flag if library should be compressed
     * @param bool $forceOnTop Flag if added library should be inserted at begin of this block
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsLibrary($name, $file, $type = '', $compress = false, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = [])
    {
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        if (!isset($this->jsLibs[strtolower($name)])) {
            $this->jsLibs[strtolower($name)] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_HEADER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
                'defer' => $defer,
                'crossorigin' => $crossorigin,
                'nomodule' => $nomodule,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds JS Library to Footer. JS Library block is rendered on top of the Footer JS files.
     *
     * @param string $name Arbitrary identifier
     * @param string $file File name
     * @param string|null $type Content Type
     * @param bool $compress Flag if library should be compressed
     * @param bool $forceOnTop Flag if added library should be inserted at begin of this block
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsFooterLibrary($name, $file, $type = '', $compress = false, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = [])
    {
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        $name .= '_jsFooterLibrary';
        if (!isset($this->jsLibs[strtolower($name)])) {
            $this->jsLibs[strtolower($name)] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_FOOTER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
                'defer' => $defer,
                'crossorigin' => $crossorigin,
                'nomodule' => $nomodule,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds JS file
     *
     * @param string $file File name
     * @param string|null $type Content Type
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsFile($file, $type = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = [])
    {
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        if (!isset($this->jsFiles[$file])) {
            $this->jsFiles[$file] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_HEADER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
                'defer' => $defer,
                'crossorigin' => $crossorigin,
                'nomodule' => $nomodule,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds JS file to footer
     *
     * @param string $file File name
     * @param string|null $type Content Type
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsFooterFile($file, $type = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = [])
    {
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        if (!isset($this->jsFiles[$file])) {
            $this->jsFiles[$file] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_FOOTER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
                'defer' => $defer,
                'crossorigin' => $crossorigin,
                'nomodule' => $nomodule,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds JS inline code
     *
     * @param string $name
     * @param string $block
     * @param bool $compress
     * @param bool $forceOnTop
     */
    public function addJsInlineCode($name, $block, $compress = true, $forceOnTop = false, bool $useNonce = false)
    {
        if (!isset($this->jsInline[$name]) && !empty($block)) {
            $this->jsInline[$name] = [
                'code' => $block . LF,
                'section' => self::PART_HEADER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'useNonce' => $useNonce,
            ];
        }
    }

    /**
     * Adds JS inline code to footer
     *
     * @param string $name
     * @param string $block
     * @param bool $compress
     * @param bool $forceOnTop
     */
    public function addJsFooterInlineCode($name, $block, $compress = true, $forceOnTop = false, bool $useNonce = false)
    {
        if (!isset($this->jsInline[$name]) && !empty($block)) {
            $this->jsInline[$name] = [
                'code' => $block . LF,
                'section' => self::PART_FOOTER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'useNonce' => $useNonce,
            ];
        }
    }

    /**
     * Adds CSS file
     *
     * @param string $file
     * @param string $rel
     * @param string $media
     * @param string $title
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $inline
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addCssFile($file, $rel = 'stylesheet', $media = 'all', $title = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $inline = false, array $tagAttributes = [])
    {
        if (!isset($this->cssFiles[$file])) {
            $this->cssFiles[$file] = [
                'file' => $file,
                'rel' => $rel,
                'media' => $media,
                'title' => $title,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'inline' => $inline,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds CSS file
     *
     * @param string $file
     * @param string $rel
     * @param string $media
     * @param string $title
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $inline
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addCssLibrary($file, $rel = 'stylesheet', $media = 'all', $title = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $inline = false, array $tagAttributes = [])
    {
        if (!isset($this->cssLibs[$file])) {
            $this->cssLibs[$file] = [
                'file' => $file,
                'rel' => $rel,
                'media' => $media,
                'title' => $title,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'inline' => $inline,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds CSS inline code
     *
     * @param string $name
     * @param string $block
     * @param bool $compress
     * @param bool $forceOnTop
     */
    public function addCssInlineBlock($name, $block, $compress = false, $forceOnTop = false, bool $useNonce = false)
    {
        if (!isset($this->cssInline[$name]) && !empty($block)) {
            $this->cssInline[$name] = [
                'code' => $block,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'useNonce' => $useNonce,
            ];
        }
    }

    /**
     * Includes an ES6/ES11 compatible JavaScript module by
     * resolving the specifier to an import-mapped filename.
     *
     * @param string $specifier Bare module identifier like @my/package/filename.js
     */
    public function loadJavaScriptModule(string $specifier)
    {
        $this->javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create($specifier)
        );
    }

    /**
     * Adds Javascript Inline Label. This will occur in TYPO3.lang - object
     * The label can be used in scripts with TYPO3.lang.<key>
     *
     * @param string $key
     * @param string $value
     */
    public function addInlineLanguageLabel($key, $value)
    {
        $this->inlineLanguageLabels[$key] = $value;
    }

    /**
     * Adds Javascript Inline Label Array. This will occur in TYPO3.lang - object
     * The label can be used in scripts with TYPO3.lang.<key>
     * Array will be merged with existing array.
     */
    public function addInlineLanguageLabelArray(array $array)
    {
        $this->inlineLanguageLabels = array_merge($this->inlineLanguageLabels, $array);
    }

    /**
     * Gets labels to be used in JavaScript fetched from a locallang file.
     *
     * @param string $fileRef Input is a file-reference (see GeneralUtility::getFileAbsFileName). That file is expected to be a 'locallang.xlf' file containing a valid XML TYPO3 language structure.
     * @param string $selectionPrefix Prefix to select the correct labels (default: '')
     * @param string $stripFromSelectionName String to be removed from the label names in the output. (default: '')
     */
    public function addInlineLanguageLabelFile($fileRef, $selectionPrefix = '', $stripFromSelectionName = '')
    {
        $index = md5($fileRef . $selectionPrefix . $stripFromSelectionName);
        if ($fileRef && !isset($this->inlineLanguageLabelFiles[$index])) {
            $this->inlineLanguageLabelFiles[$index] = [
                'fileRef' => $fileRef,
                'selectionPrefix' => $selectionPrefix,
                'stripFromSelectionName' => $stripFromSelectionName,
            ];
        }
    }

    /**
     * Adds Javascript Inline Setting. This will occur in TYPO3.settings - object
     * The label can be used in scripts with TYPO3.setting.<key>
     *
     * @param string $namespace
     * @param string $key
     * @param mixed $value
     */
    public function addInlineSetting($namespace, $key, $value)
    {
        if ($namespace) {
            if (strpos($namespace, '.')) {
                $parts = explode('.', $namespace);
                $a = &$this->inlineSettings;
                foreach ($parts as $part) {
                    $a = &$a[$part];
                }
                $a[$key] = $value;
            } else {
                $this->inlineSettings[$namespace][$key] = $value;
            }
        } else {
            $this->inlineSettings[$key] = $value;
        }
    }

    /**
     * Adds Javascript Inline Setting. This will occur in TYPO3.settings - object
     * The label can be used in scripts with TYPO3.setting.<key>
     * Array will be merged with existing array.
     *
     * @param string $namespace
     */
    public function addInlineSettingArray($namespace, array $array)
    {
        if ($namespace) {
            if (strpos($namespace, '.')) {
                $parts = explode('.', $namespace);
                $a = &$this->inlineSettings;
                foreach ($parts as $part) {
                    $a = &$a[$part];
                }
                $a = array_merge((array)$a, $array);
            } else {
                $this->inlineSettings[$namespace] = array_merge((array)($this->inlineSettings[$namespace] ?? []), $array);
            }
        } else {
            $this->inlineSettings = array_merge($this->inlineSettings, $array);
        }
    }

    /**
     * Adds content to body content
     *
     * @param string $content
     */
    public function addBodyContent($content)
    {
        $this->bodyContent .= $content;
    }

    /*****************************************************/
    /*                                                   */
    /*  Render Functions                                 */
    /*                                                   */
    /*****************************************************/
    /**
     * Render the page
     *
     * @return string Content of rendered page
     */
    public function render()
    {
        $this->prepareRendering();
        [$jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs] = $this->renderJavaScriptAndCss();
        $metaTags = implode(LF, $this->renderMetaTagsFromAPI());
        $markerArray = $this->getPreparedMarkerArray($jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs, $metaTags);
        $template = $this->getTemplate();

        // The page renderer needs a full reset when the page was rendered
        $this->reset();
        return trim($this->templateService->substituteMarkerArray($template, $markerArray, '###|###'));
    }

    public function renderResponse(
        int $code = 200,
        string $reasonPhrase = '',
    ): ResponseInterface {
        $stream = $this->streamFactory->createStream($this->render());
        return $this->responseFactory->createResponse($code, $reasonPhrase)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($stream);
    }

    /**
     * Renders metaTags based on tags added via the API
     *
     * @return array
     */
    protected function renderMetaTagsFromAPI()
    {
        $metaTags = [];
        $metaTagManagers = $this->metaTagRegistry->getAllManagers();

        foreach ($metaTagManagers as $managerObject) {
            $properties = $managerObject->renderAllProperties();
            if (!empty($properties)) {
                $metaTags[] = $properties;
            }
        }
        return $metaTags;
    }

    /**
     * Render the page but not the JavaScript and CSS Files
     *
     * @param string $substituteHash The hash that is used for the placeholder markers
     * @internal
     * @return string Content of rendered page
     */
    public function renderPageWithUncachedObjects($substituteHash)
    {
        $this->prepareRendering();
        $markerArray = $this->getPreparedMarkerArrayForPageWithUncachedObjects($substituteHash);
        $template = $this->getTemplate();
        return trim($this->templateService->substituteMarkerArray($template, $markerArray, '###|###'));
    }

    /**
     * Renders the JavaScript and CSS files that have been added during processing
     * of uncached content objects (USER_INT, COA_INT)
     *
     * @param string $cachedPageContent
     * @param string $substituteHash The hash that is used for the variables
     * @internal
     * @return string
     */
    public function renderJavaScriptAndCssForProcessingOfUncachedContentObjects($cachedPageContent, $substituteHash)
    {
        $this->prepareRendering();
        [$jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs] = $this->renderJavaScriptAndCss();
        $title = $this->title ? str_replace('|', htmlspecialchars($this->title), $this->titleTag) : '';
        $markerArray = [
            '<!-- ###TITLE' . $substituteHash . '### -->' => $title,
            '<!-- ###CSS_LIBS' . $substituteHash . '### -->' => $cssLibs,
            '<!-- ###CSS_INCLUDE' . $substituteHash . '### -->' => $cssFiles,
            '<!-- ###CSS_INLINE' . $substituteHash . '### -->' => $cssInline,
            '<!-- ###JS_INLINE' . $substituteHash . '### -->' => $jsInline,
            '<!-- ###JS_INCLUDE' . $substituteHash . '### -->' => $jsFiles,
            '<!-- ###JS_LIBS' . $substituteHash . '### -->' => $jsLibs,
            '<!-- ###META' . $substituteHash . '### -->' => implode(LF, $this->renderMetaTagsFromAPI()),
            '<!-- ###HEADERDATA' . $substituteHash . '### -->' => implode(LF, $this->headerData),
            '<!-- ###FOOTERDATA' . $substituteHash . '### -->' => implode(LF, $this->footerData),
            '<!-- ###JS_LIBS_FOOTER' . $substituteHash . '### -->' => $jsFooterLibs,
            '<!-- ###JS_INCLUDE_FOOTER' . $substituteHash . '### -->' => $jsFooterFiles,
            '<!-- ###JS_INLINE_FOOTER' . $substituteHash . '### -->' => $jsFooterInline,
        ];
        foreach ($markerArray as $placeHolder => $content) {
            $cachedPageContent = str_replace($placeHolder, $content, $cachedPageContent);
        }
        $this->reset();
        return $cachedPageContent;
    }

    /**
     * Remove ending slashes from static header block
     * if the page is being rendered as html (not xhtml)
     * and define property $this->endingSlash for further use
     */
    protected function prepareRendering()
    {
        if ($this->docType->isXmlCompliant()) {
            $this->endingSlash = ' /';
        } else {
            $this->shortcutTag = str_replace(' />', '>', $this->shortcutTag);
            $this->endingSlash = '';
        }
    }

    /**
     * Renders all JavaScript and CSS
     *
     * @return array|string[]
     */
    protected function renderJavaScriptAndCss()
    {
        $this->executePreRenderHook();
        $mainJsLibs = $this->renderMainJavaScriptLibraries();
        if ($this->concatenateJavascript || $this->concatenateCss) {
            // Do the file concatenation
            $this->doConcatenate();
        }
        if ($this->compressCss || $this->compressJavascript) {
            // Do the file compression
            $this->doCompress();
        }
        $this->executeRenderPostTransformHook();
        $cssLibs = $this->renderCssLibraries();
        $cssFiles = $this->renderCssFiles();
        $cssInline = $this->renderCssInline();
        [$jsLibs, $jsFooterLibs] = $this->renderAdditionalJavaScriptLibraries();
        [$jsFiles, $jsFooterFiles] = $this->renderJavaScriptFiles();
        [$jsInline, $jsFooterInline] = $this->renderInlineJavaScript();
        $jsLibs = $mainJsLibs . $jsLibs;
        if ($this->moveJsFromHeaderToFooter) {
            $jsFooterLibs = $jsLibs . LF . $jsFooterLibs;
            $jsLibs = '';
            $jsFooterFiles = $jsFiles . LF . $jsFooterFiles;
            $jsFiles = '';
            $jsFooterInline = $jsInline . LF . $jsFooterInline;
            $jsInline = '';
        }
        // Use AssetRenderer to inject all JavaScripts and CSS files
        $jsInline .= $this->assetRenderer->renderInlineJavaScript(true, $this->nonce);
        $jsFooterInline .= $this->assetRenderer->renderInlineJavaScript(false, $this->nonce);
        $jsFiles .= $this->assetRenderer->renderJavaScript(true, $this->nonce);
        $jsFooterFiles .= $this->assetRenderer->renderJavaScript(false, $this->nonce);
        $cssInline .= $this->assetRenderer->renderInlineStyleSheets(true, $this->nonce);
        // append inline CSS to footer (as there is no cssFooterInline)
        $jsFooterFiles .= $this->assetRenderer->renderInlineStyleSheets(false, $this->nonce);
        $cssLibs .= $this->assetRenderer->renderStyleSheets(true, $this->endingSlash, $this->nonce);
        $cssFiles .= $this->assetRenderer->renderStyleSheets(false, $this->endingSlash, $this->nonce);

        $this->executePostRenderHook($jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs);
        return [$jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs];
    }

    /**
     * Fills the marker array with the given strings and trims each value
     *
     * @param string $jsLibs
     * @param string $jsFiles
     * @param string $jsFooterFiles
     * @param string $cssLibs
     * @param string $cssFiles
     * @param string $jsInline
     * @param string $cssInline
     * @param string $jsFooterInline
     * @param string $jsFooterLibs
     * @param string $metaTags
     * @return array Marker array
     */
    protected function getPreparedMarkerArray($jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs, $metaTags)
    {
        $markerArray = [
            'XMLPROLOG_DOCTYPE' => $this->xmlPrologAndDocType,
            'HTMLTAG' => $this->htmlTag,
            'HEADTAG' => $this->headTag,
            'INLINECOMMENT' => $this->inlineComments ? LF . LF . '<!-- ' . LF . implode(LF, $this->inlineComments) . '-->' . LF . LF : '',
            'SHORTCUT' => $this->favIcon ? sprintf($this->shortcutTag, htmlspecialchars($this->favIcon), $this->iconMimeType) : '',
            'CSS_LIBS' => $cssLibs,
            'CSS_INCLUDE' => $cssFiles,
            'CSS_INLINE' => $cssInline,
            'JS_INLINE' => $jsInline,
            'JS_INCLUDE' => $jsFiles,
            'JS_LIBS' => $jsLibs,
            'TITLE' => $this->title ? str_replace('|', htmlspecialchars($this->title), $this->titleTag) : '',
            'META' => $metaTags,
            'HEADERDATA' => $this->headerData ? implode(LF, $this->headerData) : '',
            'FOOTERDATA' => $this->footerData ? implode(LF, $this->footerData) : '',
            'JS_LIBS_FOOTER' => $jsFooterLibs,
            'JS_INCLUDE_FOOTER' => $jsFooterFiles,
            'JS_INLINE_FOOTER' => $jsFooterInline,
            'BODY' => $this->bodyContent,
            // @internal
            'TRAILING_SLASH_FOR_SELF_CLOSING_TAG' => $this->endingSlash ? ' ' . $this->endingSlash : '',
        ];

        return array_map(trim(...), $markerArray);
    }

    /**
     * Fills the marker array with the given strings and trims each value
     *
     * @param string $substituteHash The hash that is used for the placeholder markers
     * @return array Marker array
     */
    protected function getPreparedMarkerArrayForPageWithUncachedObjects($substituteHash)
    {
        $markerArray = [
            'XMLPROLOG_DOCTYPE' => $this->xmlPrologAndDocType,
            'HTMLTAG' => $this->htmlTag,
            'HEADTAG' => $this->headTag,
            'INLINECOMMENT' => $this->inlineComments ? LF . LF . '<!-- ' . LF . implode(LF, $this->inlineComments) . '-->' . LF . LF : '',
            'SHORTCUT' => $this->favIcon ? sprintf($this->shortcutTag, htmlspecialchars($this->favIcon), $this->iconMimeType) : '',
            'META' => '<!-- ###META' . $substituteHash . '### -->',
            'BODY' => $this->bodyContent,
            'TITLE' => '<!-- ###TITLE' . $substituteHash . '### -->',
            'CSS_LIBS' => '<!-- ###CSS_LIBS' . $substituteHash . '### -->',
            'CSS_INCLUDE' => '<!-- ###CSS_INCLUDE' . $substituteHash . '### -->',
            'CSS_INLINE' => '<!-- ###CSS_INLINE' . $substituteHash . '### -->',
            'JS_INLINE' => '<!-- ###JS_INLINE' . $substituteHash . '### -->',
            'JS_INCLUDE' => '<!-- ###JS_INCLUDE' . $substituteHash . '### -->',
            'JS_LIBS' => '<!-- ###JS_LIBS' . $substituteHash . '### -->',
            'HEADERDATA' => '<!-- ###HEADERDATA' . $substituteHash . '### -->',
            'FOOTERDATA' => '<!-- ###FOOTERDATA' . $substituteHash . '### -->',
            'JS_LIBS_FOOTER' => '<!-- ###JS_LIBS_FOOTER' . $substituteHash . '### -->',
            'JS_INCLUDE_FOOTER' => '<!-- ###JS_INCLUDE_FOOTER' . $substituteHash . '### -->',
            'JS_INLINE_FOOTER' => '<!-- ###JS_INLINE_FOOTER' . $substituteHash . '### -->',
            // @internal
            'TRAILING_SLASH_FOR_SELF_CLOSING_TAG' => $this->endingSlash ? ' ' . $this->endingSlash : '',
        ];
        $markerArray = array_map(trim(...), $markerArray);
        return $markerArray;
    }

    /**
     * Reads the template file and returns the requested part as string
     */
    protected function getTemplate(): string
    {
        $templateResource = $this->systemResourceFactory->createResource($this->templateFile);
        try {
            if ($templateResource instanceof SystemResourceInterface) {
                return $templateResource->getContents();
            }
        } catch (SystemResourceDoesNotExistException) {
        }
        return '';
    }

    /**
     * Helper function for render the main JavaScript libraries
     *
     * @return string Content with JavaScript libraries
     */
    protected function renderMainJavaScriptLibraries()
    {
        $out = '';

        foreach ($this->assetCollector->getJavaScriptModules() as $module) {
            $this->loadJavaScriptModule($module);
        }

        // adds a nonce hint/work-around for lit-elements (which is only applied automatically in ShadowDOM)
        // see https://lit.dev/docs/api/ReactiveElement/#ReactiveElement.styles)
        if ($this->applyNonceHint && $this->nonce !== null) {
            $this->javaScriptRenderer->addGlobalAssignment(['litNonce' => $this->nonce->consumeInline(Directive::ScriptSrcElem)]);
        }

        // @todo hookup with PSR-7 request/response
        $sitePath = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');

        $useNonce = $this->getApplicationType() === 'BE';
        $out .= $this->javaScriptRenderer->renderImportMap(
            $sitePath,
            $useNonce ? $this->nonce : null,
        );

        $this->loadJavaScriptLanguageStrings();
        if ($this->getApplicationType() === 'BE') {
            $noBackendUserLoggedIn = empty($GLOBALS['BE_USER']->user['uid']);
            $this->addAjaxUrlsToInlineSettings($noBackendUserLoggedIn);
            $this->addGlobalCSSUrlsToInlineSettings();
            $this->inlineSettings['cache']['iconCacheIdentifier'] = sha1($this->iconRegistry->getBackendIconsCacheIdentifier());
        }
        $assignments = array_filter([
            'settings' => $this->inlineSettings,
            'lang' => $this->parseLanguageLabelsForJavaScript(),
        ]);
        if ($assignments !== []) {
            if ($this->getApplicationType() === 'BE') {
                $this->javaScriptRenderer->addGlobalAssignment(['TYPO3' => $assignments]);
            } else {
                $out .= $this->wrapInlineScript(
                    sprintf(
                        "var TYPO3 = Object.assign(TYPO3 || {}, %s);\r\n",
                        // filter potential prototype pollution
                        sprintf(
                            'Object.fromEntries(Object.entries(%s).filter((entry) => '
                            . "!['__proto__', 'prototype', 'constructor'].includes(entry[0])))",
                            json_encode($assignments)
                        )
                    ),
                    $this->nonce !== null ? ['nonce' => $this->nonce->consumeInline(Directive::ScriptSrcElem)] : []
                );
            }
        }
        $out .= $this->javaScriptRenderer->render($this->nonce, $sitePath);
        return $out;
    }

    /**
     * Converts the language labels for usage in JavaScript
     */
    protected function parseLanguageLabelsForJavaScript(): array
    {
        if (empty($this->inlineLanguageLabels)) {
            return [];
        }

        $labels = [];
        foreach ($this->inlineLanguageLabels as $key => $translationUnit) {
            if (is_array($translationUnit)) {
                $translationUnit = current($translationUnit);
                $labels[$key] = $translationUnit['target'] ?? $translationUnit['source'];
            } else {
                $labels[$key] = $translationUnit;
            }
        }

        return $labels;
    }

    /**
     * Load the language strings into JavaScript
     */
    protected function loadJavaScriptLanguageStrings()
    {
        foreach ($this->inlineLanguageLabelFiles as $languageLabelFile) {
            $this->includeLanguageFileForInline($languageLabelFile['fileRef'], $languageLabelFile['selectionPrefix'], $languageLabelFile['stripFromSelectionName']);
        }
        $this->inlineLanguageLabelFiles = [];
    }

    /**
     * Make URLs to all backend ajax handlers available as inline setting.
     */
    protected function addAjaxUrlsToInlineSettings(bool $publicRoutesOnly = false)
    {
        $ajaxUrls = [];
        // Add the ajax-based routes
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $router = GeneralUtility::makeInstance(Router::class);
        foreach ($router->getRoutes() as $routeIdentifier => $route) {
            if ($publicRoutesOnly && $route->getOption('access') !== 'public') {
                continue;
            }
            if ($route->getOption('ajax')) {
                $uri = (string)$uriBuilder->buildUriFromRoute($routeIdentifier);
                // use the shortened value in order to use this in JavaScript
                if (str_starts_with($routeIdentifier, 'ajax_')) {
                    $routeIdentifier = substr($routeIdentifier, 5);
                }
                $ajaxUrls[$routeIdentifier] = $uri;
            }
        }

        $this->inlineSettings['ajaxUrls'] = $ajaxUrls;
    }

    protected function addGlobalCSSUrlsToInlineSettings()
    {
        $this->inlineSettings['cssUrls'] = [
            'backend' => $this->getPublicUrlForFile('EXT:backend/Resources/Public/Css/backend.css'),
        ];
    }

    /**
     * Render CSS library files
     *
     * @return string
     */
    protected function renderCssLibraries()
    {
        $cssFiles = '';
        if (!empty($this->cssLibs)) {
            foreach ($this->cssLibs as $file => $properties) {
                $tag = $this->createCssTag($properties, $file);
                if ($properties['forceOnTop'] ?? false) {
                    $cssFiles = $tag . $cssFiles;
                } else {
                    $cssFiles .= $tag;
                }
            }
        }
        return $cssFiles;
    }

    /**
     * Render CSS files
     *
     * @return string
     */
    protected function renderCssFiles()
    {
        $cssFiles = '';
        if (!empty($this->cssFiles)) {
            foreach ($this->cssFiles as $file => $properties) {
                $tag = $this->createCssTag($properties, $file);
                if ($properties['forceOnTop'] ?? false) {
                    $cssFiles = $tag . $cssFiles;
                } else {
                    $cssFiles .= $tag;
                }
            }
        }
        return $cssFiles;
    }

    /**
     * Create link (inline=0) or style (inline=1) tag
     */
    private function createCssTag(array $properties, string $file): string
    {
        $includeInline = $properties['inline'] ?? false;
        $absolutePathToFile = $includeInline ? GeneralUtility::getFileAbsFileName($file) : '';
        if ($absolutePathToFile !== '' && @is_file($absolutePathToFile)) {
            $tag = $this->createInlineCssTagFromFile($absolutePathToFile, $properties);
        } else {
            $tagAttributes = [];
            if ($properties['rel'] ?? false) {
                $tagAttributes['rel'] = $properties['rel'];
            }
            $tagAttributes['href'] = $this->getPublicUrlForFile($file);
            if ($properties['media'] ?? false) {
                $tagAttributes['media'] = $properties['media'];
            }
            if ($properties['title'] ?? false) {
                $tagAttributes['title'] = $properties['title'];
            }
            // use nonce if given
            if ($this->nonce !== null) {
                $tagAttributes['nonce'] = $this->nonce->consumeStatic(Directive::StyleSrcElem);
            }
            $tagAttributes = array_merge($tagAttributes, $properties['tagAttributes'] ?? []);
            $tag = '<link ' . GeneralUtility::implodeAttributes($tagAttributes, true, true) . $this->endingSlash . '>';
        }
        if ($properties['allWrap'] ?? false) {
            $wrapArr = explode(($properties['splitChar'] ?? false) ?: '|', $properties['allWrap'], 2);
            $tag = $wrapArr[0] . $tag . $wrapArr[1];
        }
        $tag .= LF;

        return $tag;
    }

    /**
     * Render inline CSS
     *
     * @return string
     */
    protected function renderCssInline()
    {
        if (empty($this->cssInline)) {
            return '';
        }
        $cssItems = [0 => [], 1 => []];
        foreach ($this->cssInline as $name => $properties) {
            $nonceKey = (int)(!empty($properties['useNonce']));
            $cssCode = '/*' . htmlspecialchars($name) . '*/' . LF . ($properties['code'] ?? '') . LF;
            if ($properties['forceOnTop'] ?? false) {
                array_unshift($cssItems[$nonceKey], $cssCode);
            } else {
                $cssItems[$nonceKey][] = $cssCode;
            }
        }
        $cssItems = array_filter($cssItems);
        foreach ($cssItems as $useNonce => $items) {
            $attributes = $useNonce && $this->nonce !== null ? ['nonce' => $this->nonce->consumeInline(Directive::StyleSrcElem)] : [];
            $cssItems[$useNonce] = $this->wrapInlineStyle(implode('', $items), $attributes);
        }
        return implode(LF, $cssItems);
    }

    /**
     * Render JavaScript libraries
     *
     * @return array|string[] jsLibs and jsFooterLibs strings
     */
    protected function renderAdditionalJavaScriptLibraries()
    {
        $jsLibs = '';
        $jsFooterLibs = '';
        if (!empty($this->jsLibs)) {
            foreach ($this->jsLibs as $properties) {
                $tagAttributes = [];
                $tagAttributes['src'] = $this->getPublicUrlForFile($properties['file'] ?? '');
                if ($properties['type'] ?? false) {
                    $tagAttributes['type'] = $properties['type'];
                }
                if ($properties['async'] ?? false) {
                    $tagAttributes['async'] = 'async';
                }
                if ($properties['defer'] ?? false) {
                    $tagAttributes['defer'] = 'defer';
                }
                if ($properties['nomodule'] ?? false) {
                    $tagAttributes['nomodule'] = 'nomodule';
                }
                if ($properties['integrity'] ?? false) {
                    $tagAttributes['integrity'] = $properties['integrity'];
                }
                if ($properties['crossorigin'] ?? false) {
                    $tagAttributes['crossorigin'] = $properties['crossorigin'];
                }
                // use nonce if given
                if ($this->nonce !== null) {
                    $tagAttributes['nonce'] = $this->nonce->consumeStatic(Directive::ScriptSrcElem);
                }
                $tagAttributes = array_merge($tagAttributes, $properties['tagAttributes'] ?? []);
                $tag = '<script ' . GeneralUtility::implodeAttributes($tagAttributes, true, true) . '></script>';
                if ($properties['allWrap'] ?? false) {
                    $wrapArr = explode(($properties['splitChar'] ?? false) ?: '|', $properties['allWrap'], 2);
                    $tag = $wrapArr[0] . $tag . $wrapArr[1];
                }
                $tag .= LF;
                if ($properties['forceOnTop'] ?? false) {
                    if (($properties['section'] ?? 0) === self::PART_HEADER) {
                        $jsLibs = $tag . $jsLibs;
                    } else {
                        $jsFooterLibs = $tag . $jsFooterLibs;
                    }
                } elseif (($properties['section'] ?? 0) === self::PART_HEADER) {
                    $jsLibs .= $tag;
                } else {
                    $jsFooterLibs .= $tag;
                }
            }
        }
        if ($this->moveJsFromHeaderToFooter) {
            $jsFooterLibs = $jsLibs . LF . $jsFooterLibs;
            $jsLibs = '';
        }
        return [$jsLibs, $jsFooterLibs];
    }

    /**
     * Render JavaScript files
     *
     * @return array|string[] jsFiles and jsFooterFiles strings
     */
    protected function renderJavaScriptFiles()
    {
        $jsFiles = '';
        $jsFooterFiles = '';
        if (!empty($this->jsFiles)) {
            foreach ($this->jsFiles as $file => $properties) {
                $tagAttributes = [];
                $tagAttributes['src'] = $this->getPublicUrlForFile($file);
                if ($properties['type'] ?? false) {
                    $tagAttributes['type'] = $properties['type'];
                }
                if ($properties['async'] ?? false) {
                    $tagAttributes['async'] = 'async';
                }
                if ($properties['defer'] ?? false) {
                    $tagAttributes['defer'] = 'defer';
                }
                if ($properties['nomodule'] ?? false) {
                    $tagAttributes['nomodule'] = 'nomodule';
                }
                if ($properties['integrity'] ?? false) {
                    $tagAttributes['integrity'] = $properties['integrity'];
                }
                if ($properties['crossorigin'] ?? false) {
                    $tagAttributes['crossorigin'] = $properties['crossorigin'];
                }
                // use nonce if given
                if ($this->nonce !== null) {
                    $tagAttributes['nonce'] = $this->nonce->consumeStatic(Directive::ScriptSrcElem);
                }
                $tagAttributes = array_merge($tagAttributes, $properties['tagAttributes'] ?? []);
                $tag = '<script ' . GeneralUtility::implodeAttributes($tagAttributes, true, true) . '></script>';
                if ($properties['allWrap'] ?? false) {
                    $wrapArr = explode(($properties['splitChar'] ?? false) ?: '|', $properties['allWrap'], 2);
                    $tag = $wrapArr[0] . $tag . $wrapArr[1];
                }
                $tag .= LF;
                if ($properties['forceOnTop'] ?? false) {
                    if (($properties['section'] ?? 0) === self::PART_HEADER) {
                        $jsFiles = $tag . $jsFiles;
                    } else {
                        $jsFooterFiles = $tag . $jsFooterFiles;
                    }
                } elseif (($properties['section'] ?? 0) === self::PART_HEADER) {
                    $jsFiles .= $tag;
                } else {
                    $jsFooterFiles .= $tag;
                }
            }
        }
        if ($this->moveJsFromHeaderToFooter) {
            $jsFooterFiles = $jsFiles . $jsFooterFiles;
            $jsFiles = '';
        }
        return [$jsFiles, $jsFooterFiles];
    }

    /**
     * Render inline JavaScript (must not apply `nonce="..."` if defined).
     *
     * @return array|string[] jsInline and jsFooterInline string
     */
    protected function renderInlineJavaScript()
    {
        if (empty($this->jsInline)) {
            return ['', ''];
        }
        $regularItems = [0 => [], 1 => []];
        $footerItems = [0 => [], 1 => []];
        foreach ($this->jsInline as $name => $properties) {
            $nonceKey = (int)(!empty($properties['useNonce'])); // 0 or 1
            $jsCode = '/*' . htmlspecialchars($name) . '*/' . LF . ($properties['code'] ?? '') . LF;
            if ($properties['forceOnTop'] ?? false) {
                if (($properties['section'] ?? 0) === self::PART_HEADER) {
                    array_unshift($regularItems[$nonceKey], $jsCode);
                } else {
                    array_unshift($footerItems[$nonceKey], $jsCode);
                }
            } elseif (($properties['section'] ?? 0) === self::PART_HEADER) {
                $regularItems[$nonceKey][] = $jsCode;
            } else {
                $footerItems[$nonceKey][] = $jsCode;
            }
        }
        $regularItems = array_filter($regularItems);
        $footerItems = array_filter($footerItems);
        foreach ($regularItems as $useNonce => $items) {
            $attributes = $useNonce && $this->nonce !== null ? ['nonce' => $this->nonce->consumeInline(Directive::ScriptSrcElem)] : [];
            $regularItems[$useNonce] = $this->wrapInlineScript(implode('', $items), $attributes);
        }
        foreach ($footerItems as $useNonce => $items) {
            $attributes = $useNonce && $this->nonce !== null ? ['nonce' => $this->nonce->consumeInline(Directive::ScriptSrcElem)] : [];
            $footerItems[$useNonce] = $this->wrapInlineScript(implode('', $items), $attributes);
        }
        $regularCode = implode(LF, $regularItems);
        $footerCode = implode(LF, $footerItems);
        if ($this->moveJsFromHeaderToFooter) {
            $footerCode = $regularCode . $footerCode;
            $regularCode = '';
        }
        return [$regularCode, $footerCode];
    }

    /**
     * Include language file for inline usage
     *
     * @param string $fileRef
     * @param string $selectionPrefix
     * @param string $stripFromSelectionName
     */
    protected function includeLanguageFileForInline($fileRef, $selectionPrefix = '', $stripFromSelectionName = '')
    {
        $labelsFromFile = [];
        $allLabels = $this->readLLfile($fileRef);

        // Iterate through all labels from the language file
        foreach ($allLabels as $label => $value) {
            // If $selectionPrefix is set, only respect labels that start with $selectionPrefix
            if ($selectionPrefix === '' || str_starts_with($label, $selectionPrefix)) {
                // Remove substring $stripFromSelectionName from label
                $label = str_replace($stripFromSelectionName, '', $label);
                $labelsFromFile[$label] = $value;
            }
        }
        $this->inlineLanguageLabels = array_merge($this->inlineLanguageLabels, $labelsFromFile);
    }

    /**
     * Reads a locallang file.
     *
     * @param string $fileRef Reference to a relative filename to include.
     * @return array Returns the $LOCAL_LANG array found in the file. If no array found, returns empty array.
     */
    protected function readLLfile(string $fileRef): array
    {
        $languageService = $this->languageServiceFactory->create($this->locale);
        return $languageService->getLabelsFromResource($fileRef);
    }

    /*****************************************************/
    /*                                                   */
    /*  Tools                                            */
    /*                                                   */
    /*****************************************************/
    /**
     * Concatenate files into one file
     * registered handler
     */
    protected function doConcatenate()
    {
        $this->doConcatenateCss();
        $this->doConcatenateJavaScript();
    }

    /**
     * Concatenate JavaScript files according to the configuration. Only possible in TYPO3 Frontend.
     */
    protected function doConcatenateJavaScript()
    {
        if ($this->getApplicationType() !== 'FE') {
            return;
        }
        if (!$this->concatenateJavascript) {
            return;
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['jsConcatenateHandler'])) {
            // use external concatenation routine
            // @todo: $jsFooterFiles can be removed once the hook is adapted / replaced
            $jsFooterFiles = [];
            $params = [
                'jsLibs' => &$this->jsLibs,
                'jsFiles' => &$this->jsFiles,
                'jsFooterFiles' => &$jsFooterFiles,
                'headerData' => &$this->headerData,
                'footerData' => &$this->footerData,
            ];
            GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['FE']['jsConcatenateHandler'], $params, $this);
        } else {
            $this->jsLibs = $this->resourceCompressor->concatenateJsFiles($this->jsLibs);
            $this->jsFiles = $this->resourceCompressor->concatenateJsFiles($this->jsFiles);
        }
    }

    /**
     * Concatenate CSS files according to configuration. Only possible in TYPO3 Frontend.
     */
    protected function doConcatenateCss()
    {
        if ($this->getApplicationType() !== 'FE') {
            return;
        }
        if (!$this->concatenateCss) {
            return;
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['cssConcatenateHandler'])) {
            // use external concatenation routine
            $params = [
                'cssFiles' => &$this->cssFiles,
                'cssLibs' => &$this->cssLibs,
                'headerData' => &$this->headerData,
                'footerData' => &$this->footerData,
            ];
            GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['FE']['cssConcatenateHandler'], $params, $this);
        } else {
            $this->cssLibs = $this->resourceCompressor->concatenateCssFiles($this->cssLibs);
            $this->cssFiles = $this->resourceCompressor->concatenateCssFiles($this->cssFiles);
        }
    }

    /**
     * Compresses inline code
     */
    protected function doCompress()
    {
        $this->doCompressJavaScript();
        $this->doCompressCss();
    }

    /**
     * Compresses CSS according to configuration. Only possible in TYPO3 Frontend.
     */
    protected function doCompressCss()
    {
        if ($this->getApplicationType() !== 'FE') {
            return;
        }
        if (!$this->compressCss) {
            return;
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['cssCompressHandler'])) {
            // Use external compression routine
            $params = [
                'cssInline' => &$this->cssInline,
                'cssFiles' => &$this->cssFiles,
                'cssLibs' => &$this->cssLibs,
                'headerData' => &$this->headerData,
                'footerData' => &$this->footerData,
            ];
            GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['FE']['cssCompressHandler'], $params, $this);
        } else {
            $this->cssLibs = $this->resourceCompressor->compressCssFiles($this->cssLibs);
            $this->cssFiles = $this->resourceCompressor->compressCssFiles($this->cssFiles);
        }
    }

    /**
     * Compresses JavaScript according to configuration. Only possible in TYPO3 Frontend.
     */
    protected function doCompressJavaScript()
    {
        if ($this->getApplicationType() !== 'FE') {
            return;
        }
        if (!$this->compressJavascript) {
            return;
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler'])) {
            // Use external compression routine
            // @todo: $jsFooterFiles and $jsFooterInline and $jsFooterLibs can be removed once the hook is adapted / replaced
            $jsFooterFiles = $jsFooterInline = [];
            $params = [
                'jsInline' => &$this->jsInline,
                'jsFooterInline' => &$jsFooterInline,
                'jsLibs' => &$this->jsLibs,
                'jsFiles' => &$this->jsFiles,
                'jsFooterFiles' => &$jsFooterFiles,
                'headerData' => &$this->headerData,
                'footerData' => &$this->footerData,
            ];
            GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler'], $params, $this);
        } else {
            // Traverse the arrays, compress files
            foreach ($this->jsInline as $name => $properties) {
                if ($properties['compress'] ?? false) {
                    $this->jsInline[$name]['code'] = $this->resourceCompressor->compressJavaScriptSource($properties['code'] ?? '');
                }
            }
            $this->jsLibs = $this->resourceCompressor->compressJsFiles($this->jsLibs);
            $this->jsFiles = $this->resourceCompressor->compressJsFiles($this->jsFiles);
        }
    }

    /**
     * This function acts as a wrapper to allow relative and paths starting with EXT: to be dealt with
     * in this very case to always return the "absolute web path" to be included directly before output.
     *
     * This is mainly added so the EXT: syntax can be resolved for PageRenderer in one central place,
     * and hopefully removed in the future by one standard API call.
     *
     * The file is also prepared as version numbered file and prefixed as absolute webpath
     *
     * @param string $file the filename to process
     * @internal
     */
    protected function getPublicUrlForFile(string $file): string
    {
        $resource = $this->systemResourceFactory->createPublicResource($file);
        return (string)$this->resourcePublisher->generateUri($resource, null);
    }

    /*****************************************************/
    /*                                                   */
    /*  Hooks                                            */
    /*                                                   */
    /*****************************************************/
    /**
     * Execute PreRenderHook for possible manipulation
     */
    protected function executePreRenderHook()
    {
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'] ?? false;
        if (!$hooks) {
            return;
        }
        // @todo: $jsFooterFiles and $jsFooterInline and $jsFooterLibs can be removed once the hook is adapted / replaced
        $jsFooterFiles = $jsFooterLibs = $jsFooterInline = [];
        $params = [
            'jsLibs' => &$this->jsLibs,
            'jsFooterLibs' => &$jsFooterLibs,
            'jsFiles' => &$this->jsFiles,
            'jsFooterFiles' => &$jsFooterFiles,
            'cssLibs' => &$this->cssLibs,
            'cssFiles' => &$this->cssFiles,
            'headerData' => &$this->headerData,
            'footerData' => &$this->footerData,
            'jsInline' => &$this->jsInline,
            'jsFooterInline' => &$jsFooterInline,
            'cssInline' => &$this->cssInline,
        ];
        foreach ($hooks as $hook) {
            GeneralUtility::callUserFunction($hook, $params, $this);
        }
    }

    /**
     * PostTransform for possible manipulation of concatenated and compressed files
     */
    protected function executeRenderPostTransformHook()
    {
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform'] ?? false;
        if (!$hooks) {
            return;
        }
        // @todo: $jsFooterFiles and $jsFooterInline and $jsFooterLibs can be removed once the hook is adapted / replaced
        $jsFooterFiles = $jsFooterLibs = $jsFooterInline = [];
        $params = [
            'jsLibs' => &$this->jsLibs,
            'jsFooterLibs' => &$jsFooterLibs,
            'jsFiles' => &$this->jsFiles,
            'jsFooterFiles' => &$jsFooterFiles,
            'cssLibs' => &$this->cssLibs,
            'cssFiles' => &$this->cssFiles,
            'headerData' => &$this->headerData,
            'footerData' => &$this->footerData,
            'jsInline' => &$this->jsInline,
            'jsFooterInline' => &$jsFooterInline,
            'cssInline' => &$this->cssInline,
        ];
        foreach ($hooks as $hook) {
            GeneralUtility::callUserFunction($hook, $params, $this);
        }
    }

    /**
     * Execute postRenderHook for possible manipulation
     *
     * @param string $jsLibs
     * @param string $jsFiles
     * @param string $jsFooterFiles
     * @param string $cssLibs
     * @param string $cssFiles
     * @param string $jsInline
     * @param string $cssInline
     * @param string $jsFooterInline
     * @param string $jsFooterLibs
     */
    protected function executePostRenderHook(&$jsLibs, &$jsFiles, &$jsFooterFiles, &$cssLibs, &$cssFiles, &$jsInline, &$cssInline, &$jsFooterInline, &$jsFooterLibs)
    {
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'] ?? false;
        if (!$hooks) {
            return;
        }
        $params = [
            'jsLibs' => &$jsLibs,
            'jsFiles' => &$jsFiles,
            'jsFooterFiles' => &$jsFooterFiles,
            'cssLibs' => &$cssLibs,
            'cssFiles' => &$cssFiles,
            'headerData' => &$this->headerData,
            'footerData' => &$this->footerData,
            'jsInline' => &$jsInline,
            'cssInline' => &$cssInline,
            'xmlPrologAndDocType' => &$this->xmlPrologAndDocType,
            'htmlTag' => &$this->htmlTag,
            'headTag' => &$this->headTag,
            'shortcutTag' => &$this->shortcutTag,
            'inlineComments' => &$this->inlineComments,
            'favIcon' => &$this->favIcon,
            'iconMimeType' => &$this->iconMimeType,
            'titleTag' => &$this->titleTag,
            'title' => &$this->title,
            'jsFooterInline' => &$jsFooterInline,
            'jsFooterLibs' => &$jsFooterLibs,
            'bodyContent' => &$this->bodyContent,
        ];
        foreach ($hooks as $hook) {
            GeneralUtility::callUserFunction($hook, $params, $this);
        }
    }

    /**
     * Creates a CSS inline tag
     *
     * @param string $file the filename to process
     */
    protected function createInlineCssTagFromFile(string $file, array $properties): string
    {
        $cssInline = file_get_contents($file);
        if ($cssInline === false) {
            return '';
        }
        $cssInlineFix = $this->relativeCssPathFixer->fixRelativeUrlPaths($cssInline, '/' . PathUtility::dirname($file) . '/');
        $tagAttributes = [];
        if ($properties['media'] ?? false) {
            $tagAttributes['media'] = $properties['media'];
        }
        if ($properties['title'] ?? false) {
            $tagAttributes['title'] = $properties['title'];
        }
        // use nonce if given - special case, since content is created from a static file
        if ($this->nonce !== null) {
            $tagAttributes['nonce'] = $this->nonce->consumeInline(Directive::StyleSrcElem);
        }
        $tagAttributes = array_merge($tagAttributes, $properties['tagAttributes'] ?? []);
        return $this->wrapInlineStyle($cssInlineFix, $tagAttributes);
    }

    protected function wrapInlineStyle(string $content, array $attributes = []): string
    {
        $styleTag = "<style%s>\n%s\n</style>\n";
        if ($this->docType !== DocType::html5 || $this->docType->isXmlCompliant()) {
            $styleTag = "<style%s>\n/*<![CDATA[*/\n<!-- \n%s-->\n/*]]>*/\n</style>\n";
        }

        $attributesList = GeneralUtility::implodeAttributes($attributes, true);
        return sprintf(
            $styleTag,
            $attributesList !== '' ? ' ' . $attributesList : '',
            $content
        );
    }

    protected function wrapInlineScript(string $content, array $attributes = []): string
    {
        $scriptTag = "<script%s>\n%s\n</script>\n";
        // * Whenever HTML5 is used, remove the "text/javascript" type from the wrap
        //   since this is not needed and may lead to validation errors in the future.
        // * Whenever XHTML gets disabled, remove the "text/javascript" type from the wrap
        //   since this is not needed and may lead to validation errors in the future.
        if ($this->docType !== DocType::html5 || $this->docType->isXmlCompliant()) {
            $attributes['type'] = 'text/javascript';
            $scriptTag = "<script%s>\n/*<![CDATA[*/\n%s/*]]>*/\n</script>\n";
        }

        $attributesList = GeneralUtility::implodeAttributes($attributes, true);
        return sprintf(
            $scriptTag,
            $attributesList !== '' ? ' ' . $attributesList : '',
            $content
        );
    }

    /**
     * String 'FE' if in FrontendApplication, 'BE' otherwise (also in CLI without request object)
     *
     * @internal
     */
    public function getApplicationType(): string
    {
        if (
            ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface &&
            ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
        ) {
            return 'FE';
        }

        return 'BE';
    }
}
