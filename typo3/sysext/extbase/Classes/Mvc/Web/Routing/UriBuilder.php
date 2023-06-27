<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Mvc\Web\Routing;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * An URI Builder
 */
class UriBuilder
{
    protected ConfigurationManagerInterface $configurationManager;

    protected ExtensionService $extensionService;

    /**
     * @deprecated Remove nullable in v13
     */
    protected ?ContentObjectRenderer $contentObject = null;

    protected ?RequestInterface $request = null;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Arguments which have been used for building the last URI
     *
     * @var array
     */
    protected $lastArguments = [];

    /**
     * @var string
     */
    protected $section = '';

    /**
     * @var bool
     */
    protected $createAbsoluteUri = false;

    /**
     * @var string|null
     */
    protected $absoluteUriScheme;

    /**
     * @var bool|string|int
     */
    protected $addQueryString = false;

    /**
     * @var array
     */
    protected $argumentsToBeExcludedFromQueryString = [];

    /**
     * @var bool
     */
    protected $linkAccessRestrictedPages = false;

    /**
     * @var int|null
     */
    protected $targetPageUid;

    /**
     * @var int
     */
    protected $targetPageType = 0;

    /**
     * @var string|null
     */
    protected $language;

    /**
     * @var bool
     */
    protected $noCache = false;

    /**
     * @var string
     */
    protected $format = '';

    /**
     * @var string|null
     */
    protected $argumentPrefix;

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectExtensionService(ExtensionService $extensionService): void
    {
        $this->extensionService = $extensionService;
    }

    /**
     * Sets the current request
     *
     * @return static the current UriBuilder to allow method chaining
     */
    public function setRequest(RequestInterface $request): UriBuilder
    {
        $this->request = $request;
        $contentObject = $request->getAttribute('currentContentObject');
        if ($contentObject === null) {
            // @todo: Review this. This should never be the case since extbase
            //        bootstrap adds 'currentContentObject' to request. When this
            //        if() kicks in, it most likely indicates an "out of scope" usage.
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $contentObject->setRequest($request->withAttribute('currentContentObject', $contentObject));
        }
        $this->contentObject = $contentObject;
        return $this;
    }

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getRequest(): ?RequestInterface
    {
        if (!$this->request instanceof RequestInterface) {
            trigger_error(
                __CLASS__ . ' will rely on a PSR-7 Request object to properly calculate URLs in the future. ' .
                    'Make sure to provide such object by calling $uriBuilder->setRequest() before calculating URLs.',
                E_USER_DEPRECATED
            );
            $request = $GLOBALS['TYPO3_REQUEST'];
            if ($request instanceof ServerRequestInterface && !$request instanceof RequestInterface) {
                // Usually, UriBuilder gets the request set via setRequest() from extbase abstract ActionController
                // in processRequest(). Otherwise, if not in extbase context, this class fell back to non-extbase request
                // which is set as $GLOBALS['TYPO3_REQUEST']. Since this method must return an extbase request, and the
                // constructor of Request throws an exception if no ExtbaseRequestParameters attribute is given, we simply
                // create an empty attribute, attach it and create the extbase request.
                $this->request = new Request($request->withAttribute('extbase', new ExtbaseRequestParameters()));
            }
        }
        return $this->request;
    }

    /**
     * Additional query parameters.
     * If you want to "prefix" arguments, you can pass in multidimensional arrays:
     * array('prefix1' => array('foo' => 'bar')) gets "&prefix1[foo]=bar"
     *
     * @return static the current UriBuilder to allow method chaining
     */
    public function setArguments(array $arguments): UriBuilder
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @internal
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * If specified, adds a given HTML anchor to the URI (#...)
     *
     * @return static the current UriBuilder to allow method chaining
     */
    public function setSection(string $section): UriBuilder
    {
        $this->section = $section;
        return $this;
    }

    /**
     * @internal
     */
    public function getSection(): string
    {
        return $this->section;
    }

    /**
     * Specifies the format of the target (e.g. "html" or "xml")
     *
     * @return static the current UriBuilder to allow method chaining
     */
    public function setFormat(string $format): UriBuilder
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @internal
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * If set, the URI is prepended with the current base URI. Defaults to FALSE.
     *
     * @return static the current UriBuilder to allow method chaining
     */
    public function setCreateAbsoluteUri(bool $createAbsoluteUri): UriBuilder
    {
        $this->createAbsoluteUri = $createAbsoluteUri;
        return $this;
    }

    /**
     * @internal
     */
    public function getCreateAbsoluteUri(): bool
    {
        return $this->createAbsoluteUri;
    }

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getAbsoluteUriScheme(): ?string
    {
        return $this->absoluteUriScheme;
    }

    /**
     * Sets the scheme that should be used for absolute URIs in FE mode
     *
     * @param string $absoluteUriScheme the scheme to be used for absolute URIs
     * @return static the current UriBuilder to allow method chaining
     */
    public function setAbsoluteUriScheme(string $absoluteUriScheme): UriBuilder
    {
        $this->absoluteUriScheme = $absoluteUriScheme;
        return $this;
    }

    /**
     * Enforces a URI / link to a page to a specific language (or use "current")
     */
    public function setLanguage(?string $language): UriBuilder
    {
        $this->language = $language;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * If set, the current query parameters will be merged with $this->arguments in backend context.
     * In frontend context, setting this property will only include mapped query arguments from the
     * Page Routing. To include any - possible "unsafe" - GET parameters, the property has to be set
     * to "untrusted". Defaults to FALSE.
     *
     * @param bool|string|int $addQueryString is set to "1", "true", "0", "false" or "untrusted"
     * @return static the current UriBuilder to allow method chaining
     * @see https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Typolink.html#addquerystring
     */
    public function setAddQueryString(bool|string|int $addQueryString): UriBuilder
    {
        $this->addQueryString = $addQueryString;
        return $this;
    }

    /**
     * @internal
     */
    public function getAddQueryString(): bool|string|int
    {
        return $this->addQueryString;
    }

    /**
     * A list of arguments to be excluded from the query parameters
     * Only active if addQueryString is set
     *
     * @return static the current UriBuilder to allow method chaining
     * @see https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Typolink.html#addquerystring
     * @see setAddQueryString()
     */
    public function setArgumentsToBeExcludedFromQueryString(array $argumentsToBeExcludedFromQueryString): UriBuilder
    {
        $this->argumentsToBeExcludedFromQueryString = $argumentsToBeExcludedFromQueryString;
        return $this;
    }

    /**
     * @internal
     */
    public function getArgumentsToBeExcludedFromQueryString(): array
    {
        return $this->argumentsToBeExcludedFromQueryString;
    }

    /**
     * Specifies the prefix to be used for all arguments.
     *
     * @return static the current UriBuilder to allow method chaining
     */
    public function setArgumentPrefix(string $argumentPrefix): UriBuilder
    {
        $this->argumentPrefix = $argumentPrefix;
        return $this;
    }

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getArgumentPrefix(): ?string
    {
        return $this->argumentPrefix;
    }

    /**
     * If set, URIs for pages without access permissions will be created
     *
     * @return static the current UriBuilder to allow method chaining
     */
    public function setLinkAccessRestrictedPages(bool $linkAccessRestrictedPages): UriBuilder
    {
        $this->linkAccessRestrictedPages = $linkAccessRestrictedPages;
        return $this;
    }

    /**
     * @internal
     */
    public function getLinkAccessRestrictedPages(): bool
    {
        return $this->linkAccessRestrictedPages;
    }

    /**
     * Uid of the target page
     *
     * @return static the current UriBuilder to allow method chaining
     */
    public function setTargetPageUid(int $targetPageUid): UriBuilder
    {
        $this->targetPageUid = $targetPageUid;
        return $this;
    }

    /**
     * returns $this->targetPageUid.
     *
     * @internal
     */
    public function getTargetPageUid(): ?int
    {
        return $this->targetPageUid;
    }

    /**
     * Sets the page type of the target URI. Defaults to 0
     *
     * @return static the current UriBuilder to allow method chaining
     */
    public function setTargetPageType(int $targetPageType): UriBuilder
    {
        $this->targetPageType = $targetPageType;
        return $this;
    }

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getTargetPageType(): int
    {
        return $this->targetPageType;
    }

    /**
     * by default FALSE; if TRUE, &no_cache=1 will be appended to the URI
     *
     * @return static the current UriBuilder to allow method chaining
     */
    public function setNoCache(bool $noCache): UriBuilder
    {
        $this->noCache = $noCache;
        return $this;
    }

    /**
     * @internal
     */
    public function getNoCache(): bool
    {
        return $this->noCache;
    }

    /**
     * Returns the arguments being used for the last URI being built.
     * This is only set after build() / uriFor() has been called.
     *
     * @return array The last arguments
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getLastArguments(): array
    {
        return $this->lastArguments;
    }

    /**
     * Resets all UriBuilder options to their default value
     *
     * @return static the current UriBuilder to allow method chaining
     */
    public function reset(): UriBuilder
    {
        $this->arguments = [];
        $this->section = '';
        $this->format = '';
        $this->language = null;
        $this->createAbsoluteUri = false;
        $this->addQueryString = false;
        $this->argumentsToBeExcludedFromQueryString = [];
        $this->linkAccessRestrictedPages = false;
        $this->targetPageUid = null;
        $this->targetPageType = 0;
        $this->noCache = false;
        $this->argumentPrefix = null;
        $this->absoluteUriScheme = null;
        /*
         * $this->request MUST NOT be reset here because the request is actually a hard dependency and not part
         * of the internal state of this object.
         * todo: make the request a constructor dependency
         */
        return $this;
    }

    /**
     * Creates an URI used for linking to an Extbase action.
     * Works in Frontend and Backend mode of TYPO3.
     *
     * @param string|null $actionName Name of the action to be called
     * @param array|null $controllerArguments Additional query parameters. Will be "namespaced" and merged with $this->arguments.
     * @param string|null $controllerName Name of the target controller. If not set, current ControllerName is used.
     * @param string|null $extensionName Name of the target extension, without underscores. If not set, current ExtensionName is used.
     * @param string|null $pluginName Name of the target plugin. If not set, current PluginName is used.
     * @return string the rendered URI
     * @see build()
     */
    public function uriFor(
        ?string $actionName = null,
        ?array $controllerArguments = null,
        ?string $controllerName = null,
        ?string $extensionName = null,
        ?string $pluginName = null
    ): string {
        $controllerArguments = $controllerArguments ?? [];

        if ($actionName !== null) {
            $controllerArguments['action'] = $actionName;
        }
        if ($controllerName !== null) {
            $controllerArguments['controller'] = $controllerName;
        } else {
            $controllerArguments['controller'] = $this->request->getControllerName();
        }
        if ($extensionName === null) {
            $extensionName = $this->request->getControllerExtensionName();
        }
        $isFrontend = $this->getRequest() instanceof ServerRequestInterface
            && ApplicationType::fromRequest($this->getRequest())->isFrontend();
        if ($pluginName === null && $isFrontend) {
            $pluginName = $this->extensionService->getPluginNameByAction($extensionName, $controllerArguments['controller'], $controllerArguments['action'] ?? null);
        }
        if ($pluginName === null) {
            $pluginName = $this->request->getPluginName();
        }
        if ($isFrontend && $this->configurationManager->isFeatureEnabled('skipDefaultArguments')) {
            // @deprecated since TYPO3 v12, will be removed in TYPO3 v13. Remove together with other extbase feature toggle related code.
            //             Remove if() with body, remove removeDefaultControllerAndAction() method.
            $controllerArguments = $this->removeDefaultControllerAndAction($controllerArguments, $extensionName, $pluginName);
        }
        if ($this->targetPageUid === null && $isFrontend) {
            $this->targetPageUid = $this->extensionService->getTargetPidByPlugin($extensionName, $pluginName);
        }
        if ($this->format !== '') {
            $controllerArguments['format'] = $this->format;
        }
        if ($this->argumentPrefix !== null) {
            $prefixedControllerArguments = [$this->argumentPrefix => $controllerArguments];
        } elseif (!$isFrontend && !$this->configurationManager->isFeatureEnabled('enableNamespacedArgumentsForBackend')) {
            // @deprecated since TYPO3 v12, will be removed in TYPO3 v13. Remove together with other extbase feature toggle related code.
            //             Remove "&& !$this->configurationManager->isFeatureEnabled('enableNamespacedArgumentsForBackend')" from if()
            $prefixedControllerArguments = $controllerArguments;
        } else {
            $pluginNamespace = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
            $prefixedControllerArguments = [$pluginNamespace => $controllerArguments];
        }
        ArrayUtility::mergeRecursiveWithOverrule($this->arguments, $prefixedControllerArguments);
        return $this->build();
    }

    /**
     * This removes controller and/or action arguments from given controllerArguments
     * if they are equal to the default controller/action of the target plugin.
     * Note: This is only active in FE mode and if feature "skipDefaultArguments" is enabled
     *
     * @see \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::isFeatureEnabled()
     * @param array $controllerArguments the current controller arguments to be modified
     * @param string $extensionName target extension name
     * @param string $pluginName target plugin name
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13. Remove together with other extbase feature toggle related code.
     */
    protected function removeDefaultControllerAndAction(array $controllerArguments, string $extensionName, string $pluginName): array
    {
        trigger_error(
            'Extbase feature toggle skipDefaultArguments=1 is deprecated. Use routing to "configure-away" default arguments',
            E_USER_DEPRECATED
        );
        $defaultControllerName = $this->extensionService->getDefaultControllerNameByPlugin($extensionName, $pluginName);
        if (isset($controllerArguments['action'])) {
            $defaultActionName = $this->extensionService->getDefaultActionNameByPluginAndController($extensionName, $pluginName, $controllerArguments['controller']);
            if ($controllerArguments['action'] === $defaultActionName) {
                unset($controllerArguments['action']);
            }
        }
        if ($controllerArguments['controller'] === $defaultControllerName) {
            unset($controllerArguments['controller']);
        }
        return $controllerArguments;
    }

    /**
     * Builds the URI
     * Depending on the current context this calls buildBackendUri() or buildFrontendUri()
     *
     * @return string The URI
     * @see buildBackendUri()
     * @see buildFrontendUri()
     */
    public function build(): string
    {
        $request = $this->getRequest();
        if ($request instanceof ServerRequestInterface
            && ApplicationType::fromRequest($request)->isBackend()
        ) {
            return $this->buildBackendUri();
        }
        return $this->buildFrontendUri();
    }

    /**
     * Builds the URI, backend flavour
     * The settings pageUid, pageType, noCache & linkAccessRestrictedPages
     * will be ignored in the backend.
     *
     * @return string The URI
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function buildBackendUri(): string
    {
        $arguments = [];
        $request = $this->getRequest();
        if ($this->addQueryString && $this->addQueryString !== 'false') {
            $arguments = $request?->getQueryParams() ?? [];
            foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
                $argumentArrayToBeExcluded = [];
                parse_str($argumentToBeExcluded, $argumentArrayToBeExcluded);
                $arguments = ArrayUtility::arrayDiffKeyRecursive($arguments, $argumentArrayToBeExcluded);
            }
        } else {
            $id = $request?->getParsedBody()['id'] ?? $request?->getQueryParams()['id'] ?? null;
            if ($id !== null) {
                $arguments['id'] = $id;
            }
        }
        if (($route = $request?->getAttribute('route')) instanceof Route) {
            /** @var Route $route */
            $arguments['route'] = $route->getOption('_identifier');
        }
        $arguments = array_replace_recursive($arguments, $this->arguments);
        $arguments = $this->convertDomainObjectsToIdentityArrays($arguments);
        $this->lastArguments = $arguments;
        $routeIdentifier = $arguments['route'] ?? null;
        unset($arguments['route'], $arguments['token']);

        // @deprecated since TYPO3 v12, will be removed in TYPO3 v13. Remove together with other extbase feature toggle related code.
        //             Remove if below, keep body.
        $useArgumentsWithoutNamespace = !$this->configurationManager->isFeatureEnabled('enableNamespacedArgumentsForBackend');
        if ($useArgumentsWithoutNamespace) {
            // In case the current route identifier is an identifier of a sub route, remove the sub route
            // part to be able to add the actually requested sub route based on the current arguments.
            if ($routeIdentifier && str_contains($routeIdentifier, '.')) {
                [$routeIdentifier] = explode('.', $routeIdentifier);
            }
            // Build route identifier to the actually requested sub route (controller / action pair) - if any -
            // and unset corresponding arguments, because "enableNamespacedArgumentsForBackend" is turned off.
            if ($routeIdentifier && isset($arguments['controller'], $arguments['action'])) {
                $routeIdentifier .= '.' . $arguments['controller'] . '_' . $arguments['action'];
                unset($arguments['controller'], $arguments['action']);
            }
        }
        $uri = '';
        if ($routeIdentifier) {
            $backendUriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
            try {
                if ($this->createAbsoluteUri) {
                    $uri = (string)$backendUriBuilder->buildUriFromRoute($routeIdentifier, $arguments, \TYPO3\CMS\Backend\Routing\UriBuilder::ABSOLUTE_URL);
                } else {
                    $uri = (string)$backendUriBuilder->buildUriFromRoute($routeIdentifier, $arguments);
                }
            } catch (RouteNotFoundException $e) {
                // return empty URL
                $uri = '';
            }
        }
        if ($this->section !== '') {
            $uri .= '#' . $this->section;
        }
        return $uri;
    }

    /**
     * Builds the URI, frontend flavour
     *
     * @return string The URI
     * @see buildTypolinkConfiguration()
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function buildFrontendUri(): string
    {
        $typolinkConfiguration = $this->buildTypolinkConfiguration();
        if ($this->createAbsoluteUri === true) {
            $typolinkConfiguration['forceAbsoluteUrl'] = true;
            if ($this->absoluteUriScheme !== null) {
                $typolinkConfiguration['forceAbsoluteUrl.']['scheme'] = $this->absoluteUriScheme;
            }
        }
        return $this->getContentObject()->createUrl($typolinkConfiguration);
    }

    /**
     * @deprecated Remove in v13 when contentObject is no longer nullable
     */
    protected function getContentObject(): ContentObjectRenderer
    {
        if ($this->contentObject !== null) {
            return $this->contentObject;
        }

        trigger_error(
            __CLASS__ . ' relies on the current content object which should be initialized by calling ->setRequest(). The automatic fallback will be removed with TYPO3 v13.',
            E_USER_DEPRECATED
        );

        return ($contentObject = $this->getRequest()?->getAttribute('currentContentObject')) instanceof ContentObjectRenderer
            ? $contentObject
            : GeneralUtility::makeInstance(ContentObjectRenderer::class);
    }

    /**
     * Builds a TypoLink configuration array from the current settings
     *
     * @return array typolink configuration array
     * @see https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Typolink.html
     */
    protected function buildTypolinkConfiguration(): array
    {
        $typolinkConfiguration = [];
        $typolinkConfiguration['parameter'] = $this->targetPageUid ?? $GLOBALS['TSFE']?->id ?? '';
        if ($this->targetPageType !== 0) {
            $typolinkConfiguration['parameter'] .= ',' . $this->targetPageType;
        } elseif ($this->format !== '') {
            $request = $this->getRequest();
            $targetPageType = $this->extensionService->getTargetPageTypeByFormat($request->getControllerExtensionName(), $this->format);
            $typolinkConfiguration['parameter'] .= ',' . $targetPageType;
        }
        if (!empty($this->arguments)) {
            $arguments = $this->convertDomainObjectsToIdentityArrays($this->arguments);
            $this->lastArguments = $arguments;
            $typolinkConfiguration['additionalParams'] = HttpUtility::buildQueryString($arguments, '&');
        }
        if ($this->addQueryString && $this->addQueryString !== 'false') {
            $typolinkConfiguration['addQueryString'] = $this->addQueryString;
            if (!empty($this->argumentsToBeExcludedFromQueryString)) {
                $typolinkConfiguration['addQueryString.'] = [
                    'exclude' => implode(',', $this->argumentsToBeExcludedFromQueryString),
                ];
            }
        }
        if ($this->language !== null) {
            $typolinkConfiguration['language'] = $this->language;
        }

        if ($this->noCache === true) {
            $typolinkConfiguration['no_cache'] = 1;
        }
        if ($this->section !== '') {
            $typolinkConfiguration['section'] = $this->section;
        }
        if ($this->linkAccessRestrictedPages === true) {
            $typolinkConfiguration['linkAccessRestrictedPages'] = 1;
        }
        return $typolinkConfiguration;
    }

    /**
     * Recursively iterates through the specified arguments and turns instances of type \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
     * into an arrays containing the uid of the domain object.
     *
     * @param array $arguments The arguments to be iterated
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException
     * @return array The modified arguments array
     */
    protected function convertDomainObjectsToIdentityArrays(array $arguments): array
    {
        foreach ($arguments as $argumentKey => $argumentValue) {
            // if we have a LazyLoadingProxy here, make sure to get the real instance for further processing
            if ($argumentValue instanceof LazyLoadingProxy) {
                $argumentValue = $argumentValue->_loadRealInstance();
                // also update the value in the arguments array, because the lazyLoaded object could be
                // hidden and thus the $argumentValue would be NULL.
                $arguments[$argumentKey] = $argumentValue;
            }
            if ($argumentValue instanceof \Iterator) {
                $argumentValue = $this->convertIteratorToArray($argumentValue);
            }
            if ($argumentValue instanceof DomainObjectInterface) {
                if ($argumentValue->getUid() !== null) {
                    $arguments[$argumentKey] = $argumentValue->getUid();
                } elseif ($argumentValue instanceof AbstractValueObject) {
                    $arguments[$argumentKey] = $this->convertTransientObjectToArray($argumentValue);
                } else {
                    throw new InvalidArgumentValueException('Could not serialize Domain Object ' . get_class($argumentValue) . '. It is neither an Entity with identity properties set, nor a Value Object.', 1260881688);
                }
            } elseif (is_array($argumentValue)) {
                $arguments[$argumentKey] = $this->convertDomainObjectsToIdentityArrays($argumentValue);
            }
        }
        return $arguments;
    }

    protected function convertIteratorToArray(\Iterator $iterator): array
    {
        if (method_exists($iterator, 'toArray')) {
            $array = $iterator->toArray();
        } else {
            $array = iterator_to_array($iterator);
        }
        return $array;
    }

    /**
     * Converts a given object recursively into an array.
     *
     * @todo Refactor this into convertDomainObjectsToIdentityArrays()
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function convertTransientObjectToArray(DomainObjectInterface $object): array
    {
        $result = [];
        foreach ($object->_getProperties() as $propertyName => $propertyValue) {
            if ($propertyValue instanceof \Iterator) {
                $propertyValue = $this->convertIteratorToArray($propertyValue);
            }
            if ($propertyValue instanceof DomainObjectInterface) {
                if ($propertyValue->getUid() !== null) {
                    $result[$propertyName] = $propertyValue->getUid();
                } else {
                    $result[$propertyName] = $this->convertTransientObjectToArray($propertyValue);
                }
            } elseif (is_array($propertyValue)) {
                $result[$propertyName] = $this->convertDomainObjectsToIdentityArrays($propertyValue);
            } else {
                $result[$propertyName] = $propertyValue;
            }
        }
        return $result;
    }
}
