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
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * An URI Builder
 */
class UriBuilder
{
    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Extbase\Service\ExtensionService
     */
    protected $extensionService;

    /**
     * An instance of \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected $contentObject;

    /**
     * @var Request|null
     */
    protected $request;

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
     * @var bool
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
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectExtensionService(ExtensionService $extensionService): void
    {
        $this->extensionService = $extensionService;
    }

    /**
     * Life-cycle method that is called by the DI container as soon as this object is completely built
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function initializeObject(): void
    {
        $this->contentObject = $this->configurationManager->getContentObject()
            ?? GeneralUtility::makeInstance(ContentObjectRenderer::class);
    }

    /**
     * Sets the current request
     *
     * @param Request $request
     * @return static the current UriBuilder to allow method chaining
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setRequest(Request $request): UriBuilder
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return Request|null
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * Additional query parameters.
     * If you want to "prefix" arguments, you can pass in multidimensional arrays:
     * array('prefix1' => array('foo' => 'bar')) gets "&prefix1[foo]=bar"
     *
     * @param array $arguments
     * @return static the current UriBuilder to allow method chaining
     */
    public function setArguments(array $arguments): UriBuilder
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return array
     * @internal
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * If specified, adds a given HTML anchor to the URI (#...)
     *
     * @param string $section
     * @return static the current UriBuilder to allow method chaining
     */
    public function setSection(string $section): UriBuilder
    {
        $this->section = $section;
        return $this;
    }

    /**
     * @return string
     * @internal
     */
    public function getSection(): string
    {
        return $this->section;
    }

    /**
     * Specifies the format of the target (e.g. "html" or "xml")
     *
     * @param string $format
     * @return static the current UriBuilder to allow method chaining
     */
    public function setFormat(string $format): UriBuilder
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @return string
     * @internal
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * If set, the URI is prepended with the current base URI. Defaults to FALSE.
     *
     * @param bool $createAbsoluteUri
     * @return static the current UriBuilder to allow method chaining
     */
    public function setCreateAbsoluteUri(bool $createAbsoluteUri): UriBuilder
    {
        $this->createAbsoluteUri = $createAbsoluteUri;
        return $this;
    }

    /**
     * @return bool
     * @internal
     */
    public function getCreateAbsoluteUri(): bool
    {
        return $this->createAbsoluteUri;
    }

    /**
     * @return string|null
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
     * @param string|null $language
     * @return UriBuilder
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
     * If set, the current query parameters will be merged with $this->arguments. Defaults to FALSE.
     *
     * @param bool $addQueryString
     * @return static the current UriBuilder to allow method chaining
     * @see https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Typolink.html#addquerystring
     */
    public function setAddQueryString(bool $addQueryString): UriBuilder
    {
        $this->addQueryString = $addQueryString;
        return $this;
    }

    /**
     * @return bool
     * @internal
     */
    public function getAddQueryString(): bool
    {
        return $this->addQueryString;
    }

    /**
     * Sets the method to get the addQueryString parameters. Defaults to an empty string
     * which results in using GeneralUtility::_GET(). Possible values are
     *
     * + ''      -> uses GeneralUtility::_GET()
     * + '0'     -> uses GeneralUtility::_GET()
     * + 'GET'   -> uses GeneralUtility::_GET()
     * + '<any>' -> uses parse_str(GeneralUtility::getIndpEnv('QUERY_STRING'))
     *              (<any> refers to literally everything else than previously mentioned values)
     *
     * @param string $addQueryStringMethod
     * @return static the current UriBuilder to allow method chaining
     * @see https://docs.typo3.org/m/typo3/reference-typoscript/master/en-us/Functions/Typolink.html#addquerystring
     */
    public function setAddQueryStringMethod(string $addQueryStringMethod): UriBuilder
    {
        trigger_error('Calling UriBuilder->setAddQueryStringMethod() has no effect anymore and will be removed in TYPO3 v12.  Remove any call in your custom code, as it will result in a fatal error.', E_USER_DEPRECATED);
        return $this;
    }

    /**
     * A list of arguments to be excluded from the query parameters
     * Only active if addQueryString is set
     *
     * @param array $argumentsToBeExcludedFromQueryString
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
     * @return array
     * @internal
     */
    public function getArgumentsToBeExcludedFromQueryString(): array
    {
        return $this->argumentsToBeExcludedFromQueryString;
    }

    /**
     * Specifies the prefix to be used for all arguments.
     *
     * @param string $argumentPrefix
     * @return static the current UriBuilder to allow method chaining
     */
    public function setArgumentPrefix(string $argumentPrefix): UriBuilder
    {
        $this->argumentPrefix = $argumentPrefix;
        return $this;
    }

    /**
     * @return string|null
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getArgumentPrefix(): ?string
    {
        return $this->argumentPrefix;
    }

    /**
     * If set, URIs for pages without access permissions will be created
     *
     * @param bool $linkAccessRestrictedPages
     * @return static the current UriBuilder to allow method chaining
     */
    public function setLinkAccessRestrictedPages(bool $linkAccessRestrictedPages): UriBuilder
    {
        $this->linkAccessRestrictedPages = $linkAccessRestrictedPages;
        return $this;
    }

    /**
     * @return bool
     * @internal
     */
    public function getLinkAccessRestrictedPages(): bool
    {
        return $this->linkAccessRestrictedPages;
    }

    /**
     * Uid of the target page
     *
     * @param int $targetPageUid
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
     * @return int|null
     * @internal
     */
    public function getTargetPageUid(): ?int
    {
        return $this->targetPageUid;
    }

    /**
     * Sets the page type of the target URI. Defaults to 0
     *
     * @param int $targetPageType
     * @return static the current UriBuilder to allow method chaining
     */
    public function setTargetPageType(int $targetPageType): UriBuilder
    {
        $this->targetPageType = $targetPageType;
        return $this;
    }

    /**
     * @return int
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getTargetPageType(): int
    {
        return $this->targetPageType;
    }

    /**
     * by default FALSE; if TRUE, &no_cache=1 will be appended to the URI
     *
     * @param bool $noCache
     * @return static the current UriBuilder to allow method chaining
     */
    public function setNoCache(bool $noCache): UriBuilder
    {
        $this->noCache = $noCache;
        return $this;
    }

    /**
     * @return bool
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
         * todo: consider making the request a constructor dependency or get rid of it's usage
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
        $isFrontend = ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend();
        if ($pluginName === null && $isFrontend) {
            $pluginName = $this->extensionService->getPluginNameByAction($extensionName, $controllerArguments['controller'], $controllerArguments['action'] ?? null);
        }
        if ($pluginName === null) {
            $pluginName = $this->request->getPluginName();
        }
        if ($isFrontend && $this->configurationManager->isFeatureEnabled('skipDefaultArguments')) {
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
     * @return array
     */
    protected function removeDefaultControllerAndAction(array $controllerArguments, string $extensionName, string $pluginName): array
    {
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
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
        ) {
            return $this->buildBackendUri();
        }
        return $this->buildFrontendUri();
    }

    /**
     * Builds the URI, backend flavour
     * The resulting URI is relative and starts with "index.php".
     * The settings pageUid, pageType, noCache & linkAccessRestrictedPages
     * will be ignored in the backend.
     *
     * @return string The URI
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getQueryArguments
     */
    public function buildBackendUri(): string
    {
        $arguments = [];
        if ($this->addQueryString === true) {
            $arguments = GeneralUtility::_GET();
            foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
                $argumentArrayToBeExcluded = [];
                parse_str($argumentToBeExcluded, $argumentArrayToBeExcluded);
                $arguments = ArrayUtility::arrayDiffKeyRecursive($arguments, $argumentArrayToBeExcluded);
            }
        } else {
            $id = GeneralUtility::_GP('id');
            if ($id !== null) {
                $arguments['id'] = $id;
            }
        }
        // @todo Should be replaced as soon as we have a PSR-7 object here
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ($route = $GLOBALS['TYPO3_REQUEST']->getAttribute('route')) instanceof Route
        ) {
            $arguments['route'] = $route->getPath();
        }
        ArrayUtility::mergeRecursiveWithOverrule($arguments, $this->arguments);
        $arguments = $this->convertDomainObjectsToIdentityArrays($arguments);
        $this->lastArguments = $arguments;
        $routeName = $arguments['route'] ?? null;
        unset($arguments['route'], $arguments['token']);
        $backendUriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        try {
            if ($this->createAbsoluteUri) {
                $uri = (string)$backendUriBuilder->buildUriFromRoutePath($routeName, $arguments, \TYPO3\CMS\Backend\Routing\UriBuilder::ABSOLUTE_URL);
            } else {
                $uri = (string)$backendUriBuilder->buildUriFromRoutePath($routeName, $arguments, \TYPO3\CMS\Backend\Routing\UriBuilder::ABSOLUTE_PATH);
            }
        } catch (ResourceNotFoundException $e) {
            try {
                if ($this->createAbsoluteUri) {
                    $uri = (string)$backendUriBuilder->buildUriFromRoute($routeName, $arguments, \TYPO3\CMS\Backend\Routing\UriBuilder::ABSOLUTE_URL);
                } else {
                    $uri = (string)$backendUriBuilder->buildUriFromRoute($routeName, $arguments, \TYPO3\CMS\Backend\Routing\UriBuilder::ABSOLUTE_PATH);
                }
            } catch (RouteNotFoundException $e) {
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
        // Other than stated in the doc block, typoLink_URL does not always return a string
        // Thus, we explicitly cast to string here.
        $uri = (string)$this->contentObject->typoLink_URL($typolinkConfiguration);
        return $uri;
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
        $typolinkConfiguration['parameter'] = $this->targetPageUid ?? $GLOBALS['TSFE']->id ?? '';
        if ($this->targetPageType !== 0) {
            $typolinkConfiguration['parameter'] .= ',' . $this->targetPageType;
        } elseif ($this->format !== '') {
            $targetPageType = $this->extensionService->getTargetPageTypeByFormat($this->request->getControllerExtensionName(), $this->format);
            $typolinkConfiguration['parameter'] .= ',' . $targetPageType;
        }
        if (!empty($this->arguments)) {
            $arguments = $this->convertDomainObjectsToIdentityArrays($this->arguments);
            $this->lastArguments = $arguments;
            $typolinkConfiguration['additionalParams'] = HttpUtility::buildQueryString($arguments, '&');
        }
        if ($this->addQueryString === true) {
            $typolinkConfiguration['addQueryString'] = 1;
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
            if ($argumentValue instanceof AbstractDomainObject) {
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

    /**
     * @param \Iterator $iterator
     * @return array
     */
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
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject $object
     * @return array
     * @todo Refactor this into convertDomainObjectsToIdentityArrays()
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function convertTransientObjectToArray(AbstractDomainObject $object): array
    {
        $result = [];
        foreach ($object->_getProperties() as $propertyName => $propertyValue) {
            if ($propertyValue instanceof \Iterator) {
                $propertyValue = $this->convertIteratorToArray($propertyValue);
            }
            if ($propertyValue instanceof AbstractDomainObject) {
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
