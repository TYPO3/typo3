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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Resource\RelativeCssPathFixer;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\DirectiveHashCollection;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceDoesNotExistException;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\StaticResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\UriResource;
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
    protected string $templateFile = 'PKG:typo3/cms-core:Resources/Private/Templates/PageRenderer.html';
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
        protected readonly Context $context,
        #[Autowire(service: 'cache.assets')]
        protected readonly FrontendInterface $assetsCache,
        protected readonly MarkerBasedTemplateService $templateService,
        protected readonly MetaTagManagerRegistry $metaTagRegistry,
        protected readonly AssetRenderer $assetRenderer,
        protected readonly AssetCollector $assetCollector,
        protected readonly RelativeCssPathFixer $relativeCssPathFixer,
        protected readonly LanguageServiceFactory $languageServiceFactory,
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly StreamFactoryInterface $streamFactory,
        protected readonly IconRegistry $iconRegistry,
        protected readonly SystemResourcePublisherInterface $resourcePublisher,
        protected readonly SystemResourceFactory $systemResourceFactory,
        protected readonly ResourceHashCollection $resourceHashCollection,
        protected readonly DirectiveHashCollection $directiveHashCollection,
    ) {
        $this->locale = new Locale();
        $this->docType = DocType::html5;
        $this->xmlPrologAndDocType = DocType::html5->getDoctypeDeclaration();
        $htmlTagAttributes = ['lang' => 'en'];
        $backendUserAspect = $this->context->getAspect('backend.user');
        if ($backendUserAspect->isLoggedIn()) {
            // If a backend user is logged in, we assume BE context and add a default html tag
            // with theme and color scheme attributes for this backend user.
            // In case this is FE context, FE RequestHandler will later override with final html tag attributes again.
            // This is done here for BE b/w compat reasons. Assuming BE context is done to prevent
            // accessing Request in __construct() and application type is only available as Request attribute.
            $themeAndColorSchemeAttributes = $this->getThemeAndColorSchemeHtmlTagAttributes($this->getBackendUser());
            $htmlTagAttributes = array_merge($htmlTagAttributes, $themeAndColorSchemeAttributes);
        }
        $this->htmlTag = '<html ' . GeneralUtility::implodeAttributes($htmlTagAttributes, true) . '>';
        $this->javaScriptRenderer = JavaScriptRenderer::create('EXT:core/Resources/Public/JavaScript/java-script-item-handler.js');
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
                case 'context':
                case 'templateService':
                case 'relativeCssPathFixer':
                case 'languageServiceFactory':
                case 'responseFactory':
                case 'streamFactory':
                case 'iconRegistry':
                case 'resourcePublisher':
                case 'systemResourceFactory':
                case 'resourceHashCollection':
                case 'directiveHashCollection':
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
                case 'context':
                case 'templateService':
                case 'relativeCssPathFixer':
                case 'languageServiceFactory':
                case 'responseFactory':
                case 'streamFactory':
                case 'iconRegistry':
                case 'resourcePublisher':
                case 'nonce':
                case 'systemResourceFactory':
                case 'resourceHashCollection':
                case 'directiveHashCollection':
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

    /**
     * BE only, FE uses a different approach to register JS
     */
    public function getJavaScriptRenderer(): JavaScriptRenderer
    {
        return $this->javaScriptRenderer;
    }

    /**
     * Content of <title> tag in <html><head>
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Sets xml prolog and docType.
     * FE only, BE is hard coded html5.
     *
     * @param string $xmlPrologAndDocType Complete tags for xml prolog and docType
     */
    public function setXmlPrologAndDocType(string $xmlPrologAndDocType): void
    {
        $this->xmlPrologAndDocType = $xmlPrologAndDocType;
    }

    /**
     * Sets language
     */
    public function setLanguage(Locale $locale, ?ServerRequestInterface $request = null): void
    {
        if ($request === null) {
            // @deprecated since v14. Method signature in v15: setLanguage(Locale $locale, ServerRequestInterface $request)
            //             Remove this if in v15.
            trigger_error(
                'PageRenderer->setLanguage() without ServerRequestInterface as second argument is deprecated since version 14.3. Argument will be mandatory in version 15.0',
                E_USER_DEPRECATED
            );
            $request = $GLOBALS['TYPO3_REQUEST'];
            if (!$request instanceof ServerRequestInterface) {
                throw new \RuntimeException('Request not found in globals', 1765333738);
            }
        }
        $this->locale = $locale;
        $this->setDefaultHtmlTag($request);
    }

    /**
     * Sets html tag
     * FE only, BE is hard coded html5.
     */
    public function setHtmlTag(string $htmlTag): void
    {
        $this->htmlTag = $htmlTag;
    }

    /**
     * Sets HTML head tag
     * FE only.
     */
    public function setHeadTag(string $headTag): void
    {
        $this->headTag = $headTag;
    }

    /**
     * Sets favicon
     */
    public function setFavIcon(string $favIcon): void
    {
        $this->favIcon = $favIcon;
    }

    /**
     * Sets icon mime type
     */
    public function setIconMimeType(string $iconMimeType): void
    {
        $this->iconMimeType = $iconMimeType;
    }

    /**
     * Sets template file
     * FE only.
     */
    public function setTemplateFile(string $file): void
    {
        $this->templateFile = $file;
    }

    /**
     * Sets Content for Body
     * BE only, FE uses addBodyContent
     */
    public function setBodyContent(string $content): void
    {
        $this->bodyContent = $content;
    }

    /**
     * BE only?
     */
    public function setApplyNonceHint(bool $applyNonceHint): void
    {
        $this->applyNonceHint = $applyNonceHint;
    }

    /**
     * FE only
     */
    public function enableMoveJsFromHeaderToFooter(): void
    {
        $this->moveJsFromHeaderToFooter = true;
    }

    /**
     * FE only, unused.
     */
    public function disableMoveJsFromHeaderToFooter(): void
    {
        $this->moveJsFromHeaderToFooter = false;
    }

    public function setNonce(?ConsumableNonce $nonce): void
    {
        $this->nonce = $nonce;
    }

    /**
     * FE only, BE is hard coded HTML5
     */
    public function setDocType(DocType $docType, ?ServerRequestInterface $request = null): void
    {
        if ($request === null) {
            // @deprecated since v14. Method signature in v15: setDocType(DocType $docType, ServerRequestInterface $request): void
            //             Remove this if in v15.
            trigger_error(
                'PageRenderer->setDocType() without ServerRequestInterface as second argument is deprecated since version 14.3. Argument will be mandatory in version 15.0',
                E_USER_DEPRECATED
            );
            $request = $GLOBALS['TYPO3_REQUEST'];
            if (!$request instanceof ServerRequestInterface) {
                throw new \RuntimeException('Request not found in globals', 1765333739);
            }
        }
        $this->docType = $docType;
        $this->xmlPrologAndDocType = $docType->getDoctypeDeclaration();
        $this->setDefaultHtmlTag($request);
    }

    /**
     * Sets a given meta tag
     *
     * @param string $type The type of the meta tag. Allowed values are property, name or http-equiv
     * @param string $name The name of the property to add
     * @param string $content The content of the meta tag
     * @param array $subProperties Subproperties of the meta tag (like e.g. og:image:width)
     * @param bool $replace Replace earlier set meta tag
     */
    public function setMetaTag(string $type, string $name, string $content, array $subProperties = [], bool $replace = true): void
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
     * Adds inline HTML comment.
     * FE only.
     */
    public function addInlineComment(string $comment): void
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
    public function addHeaderData(string $data): void
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
    public function addFooterData(string $data): void
    {
        if (!in_array($data, $this->footerData)) {
            $this->footerData[] = $data;
        }
    }

    /**
     * Adds JS Library. JS Library block is rendered on top of the JS files.
     *
     * @param string $name Arbitrary identifier
     * @param string|StaticResourceInterface $file File name
     * @param string|null $type Content Type
     * @param bool $forceOnTop Flag if added library should be inserted at begin of this block
     * @param string $allWrap
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsLibrary($name, $file, $type = '', mixed $_ = null, $forceOnTop = false, $allWrap = '', mixed $__ = null, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = []): void
    {
        $resource = $this->handleAddedResource($file);
        $isUriResource = $resource instanceof UriResource;
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        if ($integrity === ResourceHashCollection::AUTO) {
            $integrity = $this->resourceHashCollection->fetchResourceHash($resource)?->export() ?? '';
        }
        if ($crossorigin === '' && $integrity !== '' && $isUriResource) {
            $crossorigin = 'anonymous';
        }
        if (!isset($this->jsLibs[strtolower($name)])) {
            $this->jsLibs[strtolower($name)] = [
                'file' => (string)$resource,
                'type' => $type,
                'section' => self::PART_HEADER,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
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
     * @param string|StaticResourceInterface $file File name
     * @param string|null $type Content Type
     * @param bool $forceOnTop Flag if added library should be inserted at begin of this block
     * @param string $allWrap
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsFooterLibrary($name, $file, $type = '', mixed $_ = null, $forceOnTop = false, $allWrap = '', mixed $__ = null, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = []): void
    {
        $resource = $this->handleAddedResource($file);
        $isUriResource = $resource instanceof UriResource;
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        if ($integrity === ResourceHashCollection::AUTO) {
            $integrity = $this->resourceHashCollection->fetchResourceHash($resource)?->export() ?? '';
        }
        if ($crossorigin === '' && $integrity !== '' && $isUriResource) {
            $crossorigin = 'anonymous';
        }
        $name .= '_jsFooterLibrary';
        if (!isset($this->jsLibs[strtolower($name)])) {
            $this->jsLibs[strtolower($name)] = [
                'file' => (string)$resource,
                'type' => $type,
                'section' => self::PART_FOOTER,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
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
     * @param string|StaticResourceInterface $file File name
     * @param string|null $type Content Type
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsFile($file, $type = '', mixed $_ = null, $forceOnTop = false, $allWrap = '', mixed $__ = null, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = []): void
    {
        $resource = $this->handleAddedResource($file);
        $resourceIdentifier = (string)$resource;
        $isUriResource = $resource instanceof UriResource;
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        if ($integrity === ResourceHashCollection::AUTO) {
            $integrity = $this->resourceHashCollection->fetchResourceHash($resource)?->export() ?? '';
        }
        if ($crossorigin === '' && $integrity !== '' && $isUriResource) {
            $crossorigin = 'anonymous';
        }
        if (!isset($this->jsFiles[$resourceIdentifier])) {
            $this->jsFiles[$resourceIdentifier] = [
                'file' => $resourceIdentifier,
                'type' => $type,
                'section' => self::PART_HEADER,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
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
     * @param string|StaticResourceInterface $file File name
     * @param string|null $type Content Type
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsFooterFile($file, $type = '', mixed $_ = null, $forceOnTop = false, $allWrap = '', mixed $__ = null, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = []): void
    {
        $resource = $this->handleAddedResource($file);
        $resourceIdentifier = (string)$resource;
        $isUriResource = $resource instanceof UriResource;
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        if ($integrity === ResourceHashCollection::AUTO) {
            $integrity = $this->resourceHashCollection->fetchResourceHash($resource)?->export() ?? '';
        }
        if ($crossorigin === '' && $integrity !== '' && $isUriResource) {
            $crossorigin = 'anonymous';
        }
        if (!isset($this->jsFiles[$resourceIdentifier])) {
            $this->jsFiles[$resourceIdentifier] = [
                'file' => $resourceIdentifier,
                'type' => $type,
                'section' => self::PART_FOOTER,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
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
     * FE only.
     *
     * @param string $name
     * @param string $block
     * @param bool $forceOnTop
     */
    public function addJsInlineCode($name, $block, mixed $_ = null, $forceOnTop = false, bool $csp = false): void
    {
        if (!isset($this->jsInline[$name]) && !empty($block)) {
            $this->jsInline[$name] = [
                'code' => $block . LF,
                'section' => self::PART_HEADER,
                'forceOnTop' => $forceOnTop,
                'csp' => $csp,
            ];
        }
    }

    /**
     * Adds JS inline code to footer
     * FE only.
     *
     * @param string $name
     * @param string $block
     * @param bool $forceOnTop
     */
    public function addJsFooterInlineCode($name, $block, mixed $_ = null, $forceOnTop = false, bool $csp = false): void
    {
        if (!isset($this->jsInline[$name]) && !empty($block)) {
            $this->jsInline[$name] = [
                'code' => $block . LF,
                'section' => self::PART_FOOTER,
                'forceOnTop' => $forceOnTop,
                'csp' => $csp,
            ];
        }
    }

    /**
     * Adds CSS file
     *
     * @param string|StaticResourceInterface $file
     * @param string $rel
     * @param string $media
     * @param string $title
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $inline
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     * @param string $integrity Subresource Integrity (SRI)
     * @param string $crossorigin CORS settings attribute
     */
    public function addCssFile($file, $rel = 'stylesheet', $media = 'all', $title = '', mixed $_ = null, $forceOnTop = false, $allWrap = '', mixed $__ = null, $splitChar = '|', $inline = false, array $tagAttributes = [], string $integrity = '', string $crossorigin = ''): void
    {
        $resource = $this->handleAddedResource($file);
        $isUriResource = $resource instanceof UriResource;
        if ($integrity === ResourceHashCollection::AUTO) {
            $integrity = $this->resourceHashCollection->fetchResourceHash($resource)?->export() ?? '';
        }
        if ($crossorigin === '' && $integrity !== '' && $isUriResource) {
            $crossorigin = 'anonymous';
        }
        $resourceIdentifier = (string)$resource;
        if (!isset($this->cssFiles[$resourceIdentifier])) {
            $this->cssFiles[$resourceIdentifier] = [
                'file' => $resourceIdentifier,
                'rel' => $rel,
                'media' => $media,
                'title' => $title,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'splitChar' => $splitChar,
                'inline' => $inline,
                'integrity' => $integrity,
                'crossorigin' => $crossorigin,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds CSS library
     *
     * @param string|StaticResourceInterface $file
     * @param string $rel
     * @param string $media
     * @param string $title
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $inline
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     * @param string $integrity Subresource Integrity (SRI)
     * @param string $crossorigin CORS settings attribute
     */
    public function addCssLibrary($file, $rel = 'stylesheet', $media = 'all', $title = '', mixed $_ = null, $forceOnTop = false, $allWrap = '', mixed $__ = null, $splitChar = '|', $inline = false, array $tagAttributes = [], string $integrity = '', string $crossorigin = ''): void
    {
        $resource = $this->handleAddedResource($file);
        $isUriResource = $resource instanceof UriResource;
        if ($integrity === ResourceHashCollection::AUTO) {
            $integrity = $this->resourceHashCollection->fetchResourceHash($resource)?->export() ?? '';
        }
        if ($crossorigin === '' && $integrity !== '' && $isUriResource) {
            $crossorigin = 'anonymous';
        }
        $resourceIdentifier = (string)$resource;
        if (!isset($this->cssLibs[$resourceIdentifier])) {
            $this->cssLibs[$resourceIdentifier] = [
                'file' => $resourceIdentifier,
                'rel' => $rel,
                'media' => $media,
                'title' => $title,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'splitChar' => $splitChar,
                'inline' => $inline,
                'integrity' => $integrity,
                'crossorigin' => $crossorigin,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds CSS inline code
     *
     * @param string $name
     * @param string $block
     * @param bool $forceOnTop
     */
    public function addCssInlineBlock($name, $block, mixed $_ = null, $forceOnTop = false, bool $csp = false): void
    {
        if (!isset($this->cssInline[$name]) && !empty($block)) {
            $this->cssInline[$name] = [
                'code' => $block,
                'forceOnTop' => $forceOnTop,
                'csp' => $csp,
            ];
        }
    }

    /**
     * Includes an ES6/ES11 compatible JavaScript module by
     * resolving the specifier to an import-mapped filename.
     *
     * @param string $specifier Bare module identifier like @my/package/filename.js
     */
    public function loadJavaScriptModule(string $specifier): void
    {
        $this->javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create($specifier)
        );
    }

    /**
     * Adds Javascript Inline Label. This will occur in TYPO3.lang - object
     * The label can be used in scripts with TYPO3.lang.<key>
     * BE only.
     *
     * @param string $key
     * @param string $value
     */
    public function addInlineLanguageLabel($key, $value): void
    {
        $this->inlineLanguageLabels[$key] = $value;
    }

    /**
     * Adds Javascript Inline Label Array. This will occur in TYPO3.lang - object
     * The label can be used in scripts with TYPO3.lang.<key>
     * Array will be merged with existing array.
     * BE only.
     */
    public function addInlineLanguageLabelArray(array $array): void
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
    public function addInlineLanguageLabelFile($fileRef, $selectionPrefix = '', $stripFromSelectionName = ''): void
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
     * @param string|null $namespace
     * @param string $key
     * @param mixed $value
     */
    public function addInlineSetting($namespace, $key, $value): void
    {
        if ($namespace !== null && $namespace !== '') {
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
    public function addInlineSettingArray($namespace, array $array): void
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
     */
    public function addBodyContent(string $content): void
    {
        $this->bodyContent .= $content;
    }

    /**
     * Render the page.
     * BE only.
     *
     * @return string Content of rendered page
     */
    public function render(?ServerRequestInterface $request = null): string
    {
        if ($request === null) {
            // @deprecated since v14. Method signature in v15: render(ServerRequestInterface $request): string
            //             Remove this if in v15.
            trigger_error(
                'PageRenderer->render() without ServerRequestInterface as first argument is deprecated since version 14.3. Argument will be mandatory in version 15.0',
                E_USER_DEPRECATED
            );
            $request = $GLOBALS['TYPO3_REQUEST'];
            if (!$request instanceof ServerRequestInterface) {
                throw new \RuntimeException('Request not found in globals', 1765333737);
            }
        }
        $this->prepareRendering();
        [$jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs] = $this->renderJavaScriptAndCss($request);
        $metaTags = implode(LF, $this->renderMetaTagsFromAPI($this->docType));
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
        $markerArray = array_map(trim(...), $markerArray);
        $template = $this->getTemplate();
        // The page renderer needs a full reset when the page was rendered
        $this->reset($request);
        return trim($this->templateService->substituteMarkerArray($template, $markerArray, '###|###'));
    }

    /**
     * Render the page for frontend output.
     * FE only.
     *
     * @internal Not part of the public API. Only for use in TYPO3 frontend rendering.
     */
    public function renderFrontendPage(ServerRequestInterface $request): string
    {
        $this->prepareRendering();
        [$jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs] = $this->renderJavaScriptAndCss($request);
        $metaTags = implode(LF, $this->renderMetaTagsFromAPI($this->docType));
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
        $markerArray = array_map(trim(...), $markerArray);
        $template = $this->getTemplate();
        $this->reset($request);
        return trim($this->templateService->substituteMarkerArray($template, $markerArray, '###|###'));
    }

    /**
     * BE only.
     */
    public function renderResponse(
        ServerRequestInterface|int $requestOrCode = 200,
        int|string $codeOrReasonPhrase = '',
        string $reasonPhrase = '',
    ): ResponseInterface {
        if (is_int($requestOrCode) && is_string($codeOrReasonPhrase)) {
            // Old API usage
            // @deprecated since v14. Method signature in v15:
            //             renderResponse(ServerRequestInterface $request, int $code = 200, string $reasonPhrase = ''): ResponseInterface
            //             Remove if/else and exception below in v15.
            trigger_error(
                'Calling PageRenderer->renderResponse() without ServerRequestInterface as first argument is deprecated since version 14.3. This will be mandatory in version 15.0',
                E_USER_DEPRECATED
            );
            $request = $GLOBALS['TYPO3_REQUEST'];
            $code = $requestOrCode;
            $reasonPhrase = $codeOrReasonPhrase;
        } else {
            // New API usage
            if (!$requestOrCode instanceof ServerRequestInterface) {
                trigger_error(
                    'Calling PageRenderer->renderResponse() without ServerRequestInterface as first argument is deprecated since version 14.3. This will be mandatory in version 15.0',
                    E_USER_DEPRECATED
                );
                $request = $GLOBALS['TYPO3_REQUEST'];
            } else {
                $request = $requestOrCode;
            }
            $code = 200;
            if (is_int($codeOrReasonPhrase)) {
                $code = $codeOrReasonPhrase;
            }
        }
        if (!$request instanceof ServerRequestInterface) {
            throw new \RuntimeException('No request given and unable to get from globals', 1765333223);
        }

        $stream = $this->streamFactory->createStream($this->render($request));
        return $this->responseFactory->createResponse($code, $reasonPhrase)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($stream);
    }

    /**
     * Frontend related rendering of the main page HTML scaffold with placeholders
     * for dynamic sections finished by uncached element ("INT") processing later.
     * The result of this method is cached as content in page cache.
     * FE only.
     *
     * @param string $substituteHash The hash that is used for the placeholder markers
     * @internal Never use in extensions.
     */
    public function renderPageWithUncachedObjects(string $substituteHash): string
    {
        $this->prepareRendering();
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
        // Reset body content to empty string so the content is not cached twice since it is
        // already cached as 'content' section next to the other PageRenderer state.
        $this->bodyContent = '';
        $markerArray = array_map(trim(...), $markerArray);
        $template = $this->getTemplate();
        // Note in contrast to render(), this method does *not* call $this->reset() since the PageRenderer state
        // is serialized and cached for uncached element processing.
        return trim($this->templateService->substituteMarkerArray($template, $markerArray, '###|###'));
    }

    /**
     * Renders the JavaScript and CSS files that have been added during processing
     * of uncached content objects (USER_INT, COA_INT)
     * FE only.
     *
     * @param string $substituteHash The hash that is used for the variables
     * @internal Never use in extensions.
     */
    public function renderJavaScriptAndCssForProcessingOfUncachedContentObjects(ServerRequestInterface $request, string $cachedPageContent, string $substituteHash): string
    {
        $this->prepareRendering();
        // bodyContent is reset to empty string in FE both after render() and renderPageWithUncachedObjects().
        // $this->bodyContent is set to the "cached with placeholder" string here for renderJavaScriptAndCss()
        // hook to consistently receive bodyContent, otherwise it wouldn't be needed to do this here.
        $this->bodyContent = $cachedPageContent;
        [$jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs] = $this->renderJavaScriptAndCss($request);
        $title = $this->title ? str_replace('|', htmlspecialchars($this->title), $this->titleTag) : '';
        $markerArray = [
            '<!-- ###TITLE' . $substituteHash . '### -->' => $title,
            '<!-- ###CSS_LIBS' . $substituteHash . '### -->' => $cssLibs,
            '<!-- ###CSS_INCLUDE' . $substituteHash . '### -->' => $cssFiles,
            '<!-- ###CSS_INLINE' . $substituteHash . '### -->' => $cssInline,
            '<!-- ###JS_INLINE' . $substituteHash . '### -->' => $jsInline,
            '<!-- ###JS_INCLUDE' . $substituteHash . '### -->' => $jsFiles,
            '<!-- ###JS_LIBS' . $substituteHash . '### -->' => $jsLibs,
            '<!-- ###META' . $substituteHash . '### -->' => implode(LF, $this->renderMetaTagsFromAPI($this->docType)),
            '<!-- ###HEADERDATA' . $substituteHash . '### -->' => implode(LF, $this->headerData),
            '<!-- ###FOOTERDATA' . $substituteHash . '### -->' => implode(LF, $this->footerData),
            '<!-- ###JS_LIBS_FOOTER' . $substituteHash . '### -->' => $jsFooterLibs,
            '<!-- ###JS_INCLUDE_FOOTER' . $substituteHash . '### -->' => $jsFooterFiles,
            '<!-- ###JS_INLINE_FOOTER' . $substituteHash . '### -->' => $jsFooterInline,
        ];
        foreach ($markerArray as $placeHolder => $content) {
            $cachedPageContent = str_replace($placeHolder, $content, $cachedPageContent);
        }
        $this->reset($request);
        return $cachedPageContent;
    }

    /**
     * Reset all vars to initial values
     */
    protected function reset(ServerRequestInterface $request): void
    {
        $this->locale = new Locale();
        $this->setDocType(DocType::html5, $request);
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
        $this->javaScriptRenderer = JavaScriptRenderer::create('EXT:core/Resources/Public/JavaScript/java-script-item-handler.js');
    }

    /**
     * Internal method to set a basic <html> tag when in HTML5 with the proper language/locale and "dir" attributes.
     */
    protected function setDefaultHtmlTag(ServerRequestInterface $request): void
    {
        if ($this->docType === DocType::html5) {
            $attributes = [
                'lang' => $this->locale->getName(),
            ];
            if ($this->locale->isRightToLeftLanguageDirection()) {
                $attributes['dir'] = 'rtl';
            }
            // @todo: build an API to add HTML attributes cleanly
            if ($this->getApplicationType($request) === 'BE') {
                $backendUser = $this->context->getAspect('backend.user');
                if ($backendUser->isLoggedIn()) {
                    $attributes = array_merge($attributes, $this->getThemeAndColorSchemeHtmlTagAttributes($this->getBackendUser()));
                }
            }
            $this->setHtmlTag('<html ' . GeneralUtility::implodeAttributes($attributes, true) . '>');
        }
    }

    private function getThemeAndColorSchemeHtmlTagAttributes(BackendUserAuthentication $backendUser): array
    {
        $attributes = [];
        $userTS = $backendUser->getTSConfig();
        $themeDisabled = $userTS['setup.']['fields.']['theme.']['disabled'] ?? '0';
        $theme = $backendUser->uc['theme'] ?? $userTS['setup.']['fields.']['theme'] ?? 'fresh';
        if ($themeDisabled === '1') {
            $theme = $userTS['setup.']['fields.']['theme'] ?? 'fresh';
        }
        if ($theme !== 'modern') {
            $attributes['data-theme'] = $theme;
        }
        $colorSchemeDisabled = $userTS['setup.']['fields.']['colorScheme.']['disabled'] ?? '0';
        $colorScheme = $backendUser->uc['colorScheme'] ?? $userTS['setup.']['fields.']['colorScheme'] ?? 'auto';
        if ($colorSchemeDisabled === '1') {
            $colorScheme = $userTS['setup.']['fields.']['colorScheme'] ?? 'light';
        }
        if ($colorScheme !== 'auto') {
            $attributes['data-color-scheme'] = $colorScheme;
        }
        return $attributes;
    }

    /**
     * Renders metaTags based on tags added via the API
     */
    protected function renderMetaTagsFromAPI(DocType $docType): array
    {
        $metaTags = [];
        $metaTagManagers = $this->metaTagRegistry->getAllManagers();
        foreach ($metaTagManagers as $managerObject) {
            // @todo: Reflect $docType argument in MetaTagManagerInterface
            $properties = $managerObject->renderAllProperties($docType); // @phpstan-ignore arguments.count
            if (!empty($properties)) {
                $metaTags[] = $properties;
            }
        }
        return $metaTags;
    }

    /**
     * Remove ending slashes from static header block
     * if the page is being rendered as html (not xhtml)
     * and define property $this->endingSlash for further use
     */
    protected function prepareRendering(): void
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
     * @return string[]
     */
    protected function renderJavaScriptAndCss(ServerRequestInterface $request): array
    {
        $this->executePreRenderHook();
        $mainJsLibs = $this->renderMainJavaScriptLibraries($request);
        $this->executeRenderPostTransformHook();
        $cssLibs = $this->renderCssLibraries($request);
        $cssFiles = $this->renderCssFiles($request);
        $cssInline = $this->renderCssInline();
        [$jsLibs, $jsFooterLibs] = $this->renderAdditionalJavaScriptLibraries($request);
        [$jsFiles, $jsFooterFiles] = $this->renderJavaScriptFiles($request);
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
    protected function renderMainJavaScriptLibraries(ServerRequestInterface $request): string
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

        $sitePath = $request->getAttribute('normalizedParams')->getSitePath();

        $useNonce = $this->getApplicationType($request) === 'BE';
        $out .= $this->javaScriptRenderer->renderImportMap(
            $sitePath,
            $useNonce ? $this->nonce : null,
        );

        $this->loadJavaScriptLanguageStrings();
        if ($this->getApplicationType($request) === 'BE') {
            $noBackendUserLoggedIn = empty($GLOBALS['BE_USER']->user['uid']);
            $this->addAjaxUrlsToInlineSettings($noBackendUserLoggedIn);
            $this->addGlobalCSSUrlsToInlineSettings($request);
            $this->inlineSettings['cache']['iconCacheIdentifier'] = sha1($this->iconRegistry->getBackendIconsCacheIdentifier());
        }
        $assignments = array_filter([
            'settings' => $this->inlineSettings,
            'lang' => $this->parseLanguageLabelsForJavaScript(),
        ]);
        if ($assignments !== []) {
            if ($this->getApplicationType($request) === 'BE') {
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
    protected function loadJavaScriptLanguageStrings(): void
    {
        foreach ($this->inlineLanguageLabelFiles as $languageLabelFile) {
            $selectionPrefix = $languageLabelFile['selectionPrefix'];
            $stripFromSelectionName = $languageLabelFile['stripFromSelectionName'];
            $labelsFromFile = [];
            $allLabels = $this->readLLfile($languageLabelFile['fileRef']);
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
        $this->inlineLanguageLabelFiles = [];
    }

    /**
     * Make URLs to all backend ajax handlers available as inline setting.
     */
    protected function addAjaxUrlsToInlineSettings(bool $publicRoutesOnly = false): void
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

    protected function addGlobalCSSUrlsToInlineSettings(ServerRequestInterface $request): void
    {
        $this->inlineSettings['cssUrls'] = [
            'backend' => $this->getPublicUrlForFile('EXT:backend/Resources/Public/Css/backend.css', $request),
        ];
    }

    /**
     * Render CSS library files
     */
    protected function renderCssLibraries(ServerRequestInterface $request): string
    {
        $cssFiles = '';
        if (!empty($this->cssLibs)) {
            foreach ($this->cssLibs as $properties) {
                $tag = $this->createCssTag($properties, $properties['file'], $request);
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
     */
    protected function renderCssFiles(ServerRequestInterface $request): string
    {
        $cssFiles = '';
        if (!empty($this->cssFiles)) {
            foreach ($this->cssFiles as $properties) {
                $tag = $this->createCssTag($properties, $properties['file'], $request);
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
     * Adds a CSP hash for a static file to the hash collection.
     * Resolves PKG:, EXT: and relative public paths via SystemResourceFactory.
     * Silently skips URI resources (http/https) and unresolvable paths.
     */
    private function addFileHashToCollection(Directive $directive, string $file): void
    {
        $resource = $this->systemResourceFactory->createResource($file);
        if ($resource instanceof SystemResourceInterface) {
            $this->directiveHashCollection->addResourceHash($directive, $resource);
        }
    }

    /**
     * Create link (inline=0) or style (inline=1) tag
     */
    private function createCssTag(array $properties, string $file, ServerRequestInterface $request): string
    {
        $includeInline = $properties['inline'] ?? false;
        $absolutePathToFile = $includeInline ? GeneralUtility::getFileAbsFileName($file) : '';
        if ($absolutePathToFile !== '' && @is_file($absolutePathToFile)) {
            $tag = $this->createInlineCssTagFromFile($absolutePathToFile, $properties);
        } else {
            // collect CSP hash - use integrity attribute if given, else hash file content
            $integrity = $properties['integrity'] ?? '';
            if ($integrity !== '') {
                try {
                    $this->directiveHashCollection->addGenericHashValue(Directive::StyleSrcElem, $integrity);
                } catch (\LogicException) {
                    // integrity format not recognized, skip
                }
            } else {
                $this->addFileHashToCollection(Directive::StyleSrcElem, $file);
            }
            $tagAttributes = [];
            if ($properties['rel'] ?? false) {
                $tagAttributes['rel'] = $properties['rel'];
            }
            $tagAttributes['href'] = $this->getPublicUrlForFile($file, $request);
            if ($properties['media'] ?? false) {
                $tagAttributes['media'] = $properties['media'];
            }
            if ($properties['title'] ?? false) {
                $tagAttributes['title'] = $properties['title'];
            }
            if ($properties['integrity'] ?? false) {
                $tagAttributes['integrity'] = $properties['integrity'];
            }
            if ($properties['crossorigin'] ?? false) {
                $tagAttributes['crossorigin'] = $properties['crossorigin'];
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
     */
    protected function renderCssInline(): string
    {
        if (empty($this->cssInline)) {
            return '';
        }
        $cssItems = [0 => [], 1 => []];
        foreach ($this->cssInline as $name => $properties) {
            if (isset($properties['useNonce'])) {
                trigger_error(
                    'The array key "useNonce" for CSS inline blocks is deprecated, use "csp" instead.',
                    E_USER_DEPRECATED
                );
            }
            $useCsp = !empty($properties['csp']) || !empty($properties['useNonce']);
            $nonceKey = (int)$useCsp;
            $cssCode = '/*' . htmlspecialchars($name) . '*/' . LF . ($properties['code'] ?? '') . LF;
            if ($properties['forceOnTop'] ?? false) {
                array_unshift($cssItems[$nonceKey], $cssCode);
            } else {
                $cssItems[$nonceKey][] = $cssCode;
            }
        }
        $cssItems = array_filter($cssItems);
        foreach ($cssItems as $useCsp => $items) {
            $assembledContent = implode('', $items);
            if ($useCsp) {
                // Hash the full assembled content as it appears inside the <style> tag
                $this->directiveHashCollection->addInlineHash(Directive::StyleSrcElem, LF . $assembledContent . LF);
            }
            $attributes = $useCsp && $this->nonce !== null ? ['nonce' => $this->nonce->consumeInline(Directive::StyleSrcElem)] : [];
            $cssItems[$useCsp] = $this->wrapInlineStyle($assembledContent, $attributes);
        }
        return implode(LF, $cssItems);
    }

    /**
     * Render JavaScript libraries
     *
     * @return string[] jsLibs and jsFooterLibs strings
     */
    protected function renderAdditionalJavaScriptLibraries(ServerRequestInterface $request): array
    {
        $jsLibs = '';
        $jsFooterLibs = '';
        if (!empty($this->jsLibs)) {
            foreach ($this->jsLibs as $properties) {
                // collect CSP hash - use integrity attribute if given, else hash file content
                $integrity = $properties['integrity'] ?? '';
                if ($integrity !== '') {
                    try {
                        $this->directiveHashCollection->addGenericHashValue(Directive::ScriptSrcElem, $integrity);
                    } catch (\LogicException) {
                        // integrity format not recognized, skip
                    }
                } else {
                    $this->addFileHashToCollection(Directive::ScriptSrcElem, $properties['file']);
                }
                $tagAttributes = [];
                $tagAttributes['src'] = $this->getPublicUrlForFile($properties['file'], $request);
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
     * @return string[] jsFiles and jsFooterFiles strings
     */
    protected function renderJavaScriptFiles(ServerRequestInterface $request): array
    {
        $jsFiles = '';
        $jsFooterFiles = '';
        if (!empty($this->jsFiles)) {
            foreach ($this->jsFiles as $properties) {
                // collect CSP hash - use integrity attribute if given, else hash file content
                $integrity = $properties['integrity'] ?? '';
                if ($integrity !== '') {
                    try {
                        $this->directiveHashCollection->addGenericHashValue(Directive::ScriptSrcElem, $integrity);
                    } catch (\LogicException) {
                        // integrity format not recognized, skip
                    }
                } else {
                    $this->addFileHashToCollection(Directive::ScriptSrcElem, $properties['file']);
                }
                $tagAttributes = [];
                $tagAttributes['src'] = $this->getPublicUrlForFile($properties['file'], $request);
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
     * @return string[] jsInline and jsFooterInline string
     */
    protected function renderInlineJavaScript(): array
    {
        if (empty($this->jsInline)) {
            return ['', ''];
        }
        $regularItems = [0 => [], 1 => []];
        $footerItems = [0 => [], 1 => []];
        foreach ($this->jsInline as $name => $properties) {
            if (isset($properties['useNonce'])) {
                trigger_error(
                    'The array key "useNonce" for JS inline blocks is deprecated, use "csp" instead.',
                    E_USER_DEPRECATED
                );
            }
            $useCsp = !empty($properties['csp']) || !empty($properties['useNonce']);
            $nonceKey = (int)$useCsp;
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
        foreach ($regularItems as $useCsp => $items) {
            $assembledContent = implode('', $items);
            if ($useCsp) {
                // Hash the full assembled content as it appears inside the <script> tag
                $this->directiveHashCollection->addInlineHash(Directive::ScriptSrcElem, LF . $assembledContent . LF);
            }
            $attributes = $useCsp && $this->nonce !== null ? ['nonce' => $this->nonce->consumeInline(Directive::ScriptSrcElem)] : [];
            $regularItems[$useCsp] = $this->wrapInlineScript($assembledContent, $attributes);
        }
        foreach ($footerItems as $useCsp => $items) {
            $assembledContent = implode('', $items);
            if ($useCsp) {
                // Hash the full assembled content as it appears inside the <script> tag
                $this->directiveHashCollection->addInlineHash(Directive::ScriptSrcElem, LF . $assembledContent . LF);
            }
            $attributes = $useCsp && $this->nonce !== null ? ['nonce' => $this->nonce->consumeInline(Directive::ScriptSrcElem)] : [];
            $footerItems[$useCsp] = $this->wrapInlineScript($assembledContent, $attributes);
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

    private function handleAddedResource(string|StaticResourceInterface $potentialResource): StaticResourceInterface
    {
        if ($potentialResource instanceof StaticResourceInterface) {
            return $potentialResource;
        }
        return $this->systemResourceFactory->createResource($potentialResource);
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
     */
    protected function getPublicUrlForFile(string $file, ServerRequestInterface $request): string
    {
        $resource = $this->systemResourceFactory->createPublicResource($file);
        return (string)$this->resourcePublisher->generateUri($resource, $request);
    }

    /**
     * Execute PreRenderHook for possible manipulation
     */
    protected function executePreRenderHook(): void
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
    protected function executeRenderPostTransformHook(): void
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
    protected function executePostRenderHook(&$jsLibs, &$jsFiles, &$jsFooterFiles, &$cssLibs, &$cssFiles, &$jsInline, &$cssInline, &$jsFooterInline, &$jsFooterLibs): void
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
        // collect CSP hash - covers the content as it appears inside the <style> tag
        $this->directiveHashCollection->addInlineHash(Directive::StyleSrcElem, LF . $cssInlineFix . LF);
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
     */
    protected function getApplicationType(ServerRequestInterface $request): string
    {
        if (ApplicationType::fromRequest($request)->isFrontend()) {
            return 'FE';
        }
        return 'BE';
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        if (!$GLOBALS['BE_USER'] instanceof BackendUserAuthentication) {
            throw new \RuntimeException('No backend user found.', 1765402790);
        }
        return $GLOBALS['BE_USER'];
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getLanguage(): string
    {
        trigger_error('PageRenderer->getLanguage() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return (string)$this->locale;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getTitle(): string
    {
        trigger_error('PageRenderer->getTitle() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return $this->title;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getDocType(): DocType
    {
        trigger_error('PageRenderer->getDocType() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return $this->docType;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getHtmlTag(): string
    {
        trigger_error('PageRenderer->getHtmlTag() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return $this->htmlTag;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getHeadTag(): string
    {
        trigger_error('PageRenderer->getHeadTag() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return $this->headTag;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getFavIcon(): string
    {
        trigger_error('PageRenderer->getFavIcon() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return $this->favIcon;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getIconMimeType(): string
    {
        trigger_error('PageRenderer->getIconMimeType() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return $this->iconMimeType;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getTemplateFile(): string
    {
        trigger_error('PageRenderer->getTemplateFile() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return $this->templateFile;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getMoveJsFromHeaderToFooter(): bool
    {
        trigger_error('PageRenderer->getMoveJsFromHeaderToFooter() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return $this->moveJsFromHeaderToFooter;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getBodyContent(): string
    {
        trigger_error('PageRenderer->getBodyContent() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return $this->bodyContent;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getInlineLanguageLabels(): array
    {
        trigger_error('PageRenderer->getInlineLanguageLabels() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return $this->inlineLanguageLabels;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getInlineLanguageLabelFiles(): array
    {
        trigger_error('PageRenderer->getInlineLanguageLabelFiles() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        return $this->inlineLanguageLabelFiles;
    }

    /**
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function getMetaTag(string $type, string $name): array
    {
        trigger_error('PageRenderer->getMetaTag() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
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
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function removeMetaTag(string $type, string $name): void
    {
        trigger_error('PageRenderer->removeMetaTag() is deprecated since version 14.3. Use PageRenderer as data sink only.', E_USER_DEPRECATED);
        // Lowercase all the things
        $type = strtolower($type);
        $name = strtolower($name);
        $manager = $this->metaTagRegistry->getManagerForProperty($name);
        $manager->removeProperty($name, $type);
    }

    /**
     * Loads all labels from a language domain and prefixes them with the domain name.
     *
     * The domain name follows the format "extension.domain" (e.g. 'core.common', 'core.modules.media').
     * The language file is resolved automatically by the LanguageService,
     * e.g. 'EXT:core/Resources/Private/Language/locallang_common.xlf', 'EXT:core/Resources/Private/Language/Modules/media.xlf'.
     *
     * Labels are accessible in JavaScript as TYPO3.lang['domain:key'], e.g. TYPO3.lang['core.common:notAvailableAbbreviation'].
     *
     * @param string $domain The domain name in format "extension.domain" (e.g. 'core.common', 'core.modules.media')
     * @deprecated since TYPO3 v14, will be removed in TYPO3 v15.
     */
    public function addInlineLanguageDomain(string $domain): void
    {
        trigger_error(
            'PageRenderer->addInlineLanguageDomain is deprecated and will be removed with TYPO3 v15. Use "~label/{language.dom}" imports instead',
            E_USER_DEPRECATED
        );
        $languageService = $this->languageServiceFactory->create($this->locale);
        $allLabels = $languageService->getLabelsFromResource($domain);
        foreach ($allLabels as $label => $value) {
            $this->inlineLanguageLabels[$domain . ':' . $label] = $value;
        }
    }
}
