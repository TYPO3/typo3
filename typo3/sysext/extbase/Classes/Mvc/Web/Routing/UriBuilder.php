<?php
namespace TYPO3\CMS\Extbase\Mvc\Web\Routing;

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

use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Request as WebRequest;

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
     * @var Request
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
     * @var string
     */
    protected $absoluteUriScheme;

    /**
     * @var bool
     */
    protected $addQueryString = false;

    /**
     * @var string
     */
    protected $addQueryStringMethod;

    /**
     * @var array
     */
    protected $argumentsToBeExcludedFromQueryString = [];

    /**
     * @var bool
     */
    protected $linkAccessRestrictedPages = false;

    /**
     * @var int
     */
    protected $targetPageUid;

    /**
     * @var int
     */
    protected $targetPageType = 0;

    /**
     * @var bool
     */
    protected $noCache = false;

    /**
     * @var bool
     */
    protected $useCacheHash = true;

    /**
     * @var string
     */
    protected $format = '';

    /**
     * @var string
     */
    protected $argumentPrefix;

    /**
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected $environmentService;

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectExtensionService(\TYPO3\CMS\Extbase\Service\ExtensionService $extensionService)
    {
        $this->extensionService = $extensionService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * Life-cycle method that is called by the DI container as soon as this object is completely built
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function initializeObject()
    {
        $this->contentObject = $this->configurationManager->getContentObject();
    }

    /**
     * Sets the current request
     *
     * @param Request $request
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return Request
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Additional query parameters.
     * If you want to "prefix" arguments, you can pass in multidimensional arrays:
     * array('prefix1' => array('foo' => 'bar')) gets "&prefix1[foo]=bar"
     *
     * @param array $arguments
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * If specified, adds a given HTML anchor to the URI (#...)
     *
     * @param string $section
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     */
    public function setSection($section)
    {
        $this->section = $section;
        return $this;
    }

    /**
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Specifies the format of the target (e.g. "html" or "xml")
     *
     * @param string $format
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * If set, the URI is prepended with the current base URI. Defaults to FALSE.
     *
     * @param bool $createAbsoluteUri
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     */
    public function setCreateAbsoluteUri($createAbsoluteUri)
    {
        $this->createAbsoluteUri = $createAbsoluteUri;
        return $this;
    }

    /**
     * @return bool
     */
    public function getCreateAbsoluteUri()
    {
        return $this->createAbsoluteUri;
    }

    /**
     * @return string
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getAbsoluteUriScheme()
    {
        return $this->absoluteUriScheme;
    }

    /**
     * Sets the scheme that should be used for absolute URIs in FE mode
     *
     * @param string $absoluteUriScheme the scheme to be used for absolute URIs
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setAbsoluteUriScheme($absoluteUriScheme)
    {
        $this->absoluteUriScheme = $absoluteUriScheme;
        return $this;
    }

    /**
     * If set, the current query parameters will be merged with $this->arguments. Defaults to FALSE.
     *
     * @param bool $addQueryString
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     * @see TSref/typolink.addQueryString
     */
    public function setAddQueryString($addQueryString)
    {
        $this->addQueryString = (bool)$addQueryString;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAddQueryString()
    {
        return $this->addQueryString;
    }

    /**
     * Sets the method to get the addQueryString parameters. Defaults undefined
     * which results in using QUERY_STRING.
     *
     * @param string $addQueryStringMethod
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     * @see TSref/typolink.addQueryString.method
     */
    public function setAddQueryStringMethod($addQueryStringMethod)
    {
        $this->addQueryStringMethod = $addQueryStringMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddQueryStringMethod()
    {
        return (string)$this->addQueryStringMethod;
    }

    /**
     * A list of arguments to be excluded from the query parameters
     * Only active if addQueryString is set
     *
     * @param array $argumentsToBeExcludedFromQueryString
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     * @see TSref/typolink.addQueryString.exclude
     * @see setAddQueryString()
     */
    public function setArgumentsToBeExcludedFromQueryString(array $argumentsToBeExcludedFromQueryString)
    {
        $this->argumentsToBeExcludedFromQueryString = $argumentsToBeExcludedFromQueryString;
        return $this;
    }

    /**
     * @return array
     */
    public function getArgumentsToBeExcludedFromQueryString()
    {
        return $this->argumentsToBeExcludedFromQueryString;
    }

    /**
     * Specifies the prefix to be used for all arguments.
     *
     * @param string $argumentPrefix
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setArgumentPrefix($argumentPrefix)
    {
        $this->argumentPrefix = (string)$argumentPrefix;
        return $this;
    }

    /**
     * @return string
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getArgumentPrefix()
    {
        return $this->argumentPrefix;
    }

    /**
     * If set, URIs for pages without access permissions will be created
     *
     * @param bool $linkAccessRestrictedPages
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     */
    public function setLinkAccessRestrictedPages($linkAccessRestrictedPages)
    {
        $this->linkAccessRestrictedPages = (bool)$linkAccessRestrictedPages;
        return $this;
    }

    /**
     * @return bool
     */
    public function getLinkAccessRestrictedPages()
    {
        return $this->linkAccessRestrictedPages;
    }

    /**
     * Uid of the target page
     *
     * @param int $targetPageUid
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     */
    public function setTargetPageUid($targetPageUid)
    {
        $this->targetPageUid = $targetPageUid;
        return $this;
    }

    /**
     * returns $this->targetPageUid.
     *
     * @return int
     */
    public function getTargetPageUid()
    {
        return $this->targetPageUid;
    }

    /**
     * Sets the page type of the target URI. Defaults to 0
     *
     * @param int $targetPageType
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     */
    public function setTargetPageType($targetPageType)
    {
        $this->targetPageType = (int)$targetPageType;
        return $this;
    }

    /**
     * @return int
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getTargetPageType()
    {
        return $this->targetPageType;
    }

    /**
     * by default FALSE; if TRUE, &no_cache=1 will be appended to the URI
     * This overrules the useCacheHash setting
     *
     * @param bool $noCache
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     */
    public function setNoCache($noCache)
    {
        $this->noCache = (bool)$noCache;
        return $this;
    }

    /**
     * @return bool
     */
    public function getNoCache()
    {
        return $this->noCache;
    }

    /**
     * by default TRUE; if FALSE, no cHash parameter will be appended to the URI
     * If noCache is set, this setting will be ignored.
     *
     * @param bool $useCacheHash
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     */
    public function setUseCacheHash($useCacheHash)
    {
        $this->useCacheHash = (bool)$useCacheHash;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseCacheHash()
    {
        return $this->useCacheHash;
    }

    /**
     * Returns the arguments being used for the last URI being built.
     * This is only set after build() / uriFor() has been called.
     *
     * @return array The last arguments
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getLastArguments()
    {
        return $this->lastArguments;
    }

    /**
     * Resets all UriBuilder options to their default value
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
     */
    public function reset()
    {
        $this->arguments = [];
        $this->section = '';
        $this->format = '';
        $this->createAbsoluteUri = false;
        $this->addQueryString = false;
        $this->addQueryStringMethod = null;
        $this->argumentsToBeExcludedFromQueryString = [];
        $this->linkAccessRestrictedPages = false;
        $this->targetPageUid = null;
        $this->targetPageType = 0;
        $this->noCache = false;
        $this->useCacheHash = true;
        $this->argumentPrefix = null;
        return $this;
    }

    /**
     * Creates an URI used for linking to an Extbase action.
     * Works in Frontend and Backend mode of TYPO3.
     *
     * @param string $actionName Name of the action to be called
     * @param array $controllerArguments Additional query parameters. Will be "namespaced" and merged with $this->arguments.
     * @param string $controllerName Name of the target controller. If not set, current ControllerName is used.
     * @param string $extensionName Name of the target extension, without underscores. If not set, current ExtensionName is used.
     * @param string $pluginName Name of the target plugin. If not set, current PluginName is used.
     * @return string the rendered URI
     * @see build()
     */
    public function uriFor($actionName = null, $controllerArguments = [], $controllerName = null, $extensionName = null, $pluginName = null)
    {
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
        if ($pluginName === null && $this->environmentService->isEnvironmentInFrontendMode()) {
            $pluginName = $this->extensionService->getPluginNameByAction($extensionName, $controllerArguments['controller'], $controllerArguments['action']);
        }
        if ($pluginName === null) {
            $pluginName = $this->request->getPluginName();
        }
        if ($this->environmentService->isEnvironmentInFrontendMode() && $this->configurationManager->isFeatureEnabled('skipDefaultArguments')) {
            $controllerArguments = $this->removeDefaultControllerAndAction($controllerArguments, $extensionName, $pluginName);
        }
        if ($this->targetPageUid === null && $this->environmentService->isEnvironmentInFrontendMode()) {
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
    protected function removeDefaultControllerAndAction(array $controllerArguments, $extensionName, $pluginName)
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
    public function build()
    {
        if ($this->environmentService->isEnvironmentInBackendMode()) {
            return $this->buildBackendUri();
        }
        return $this->buildFrontendUri();
    }

    /**
     * Builds the URI, backend flavour
     * The resulting URI is relative and starts with "index.php".
     * The settings pageUid, pageType, noCache, useCacheHash & linkAccessRestrictedPages
     * will be ignored in the backend.
     *
     * @return string The URI
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function buildBackendUri()
    {
        $arguments = [];
        if ($this->addQueryString === true) {
            if ($this->addQueryStringMethod) {
                switch ($this->addQueryStringMethod) {
                    case 'GET':
                        $arguments = GeneralUtility::_GET();
                        break;
                    case 'POST':
                        $arguments = GeneralUtility::_POST();
                        break;
                    case 'GET,POST':
                        $arguments = array_replace_recursive(GeneralUtility::_GET(), GeneralUtility::_POST());
                        break;
                    case 'POST,GET':
                        $arguments = array_replace_recursive(GeneralUtility::_POST(), GeneralUtility::_GET());
                        break;
                    default:
                        // Explode GET vars recursively
                        parse_str(GeneralUtility::getIndpEnv('QUERY_STRING'), $arguments);
                }
            } else {
                $arguments = GeneralUtility::_GET();
            }
            foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
                $argumentArrayToBeExcluded = [];
                parse_str($argumentToBeExcluded, $argumentArrayToBeExcluded);
                $arguments = ArrayUtility::arrayDiffAssocRecursive($arguments, $argumentArrayToBeExcluded);
            }
        } else {
            $id = GeneralUtility::_GP('id');
            // backwards compatibility: check for M parameter
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
            $route = GeneralUtility::_GP('route') ?: GeneralUtility::_GP('M');
            if ($id !== null) {
                $arguments['id'] = $id;
            }
            if ($route !== null) {
                $arguments['route'] = $route;
            }
        }
        ArrayUtility::mergeRecursiveWithOverrule($arguments, $this->arguments);
        $arguments = $this->convertDomainObjectsToIdentityArrays($arguments);
        $this->lastArguments = $arguments;
        $routeName = $arguments['route'] ?? null;
        unset($arguments['route'], $arguments['token']);
        $backendUriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        try {
            if ($this->request instanceof WebRequest && $this->createAbsoluteUri) {
                $uri = (string)$backendUriBuilder->buildUriFromRoutePath($routeName, $arguments, \TYPO3\CMS\Backend\Routing\UriBuilder::ABSOLUTE_URL);
            } else {
                $uri = (string)$backendUriBuilder->buildUriFromRoutePath($routeName, $arguments, \TYPO3\CMS\Backend\Routing\UriBuilder::ABSOLUTE_PATH);
            }
        } catch (ResourceNotFoundException $e) {
            try {
                if ($this->request instanceof WebRequest && $this->createAbsoluteUri) {
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
    public function buildFrontendUri()
    {
        $typolinkConfiguration = $this->buildTypolinkConfiguration();
        if ($this->createAbsoluteUri === true) {
            $typolinkConfiguration['forceAbsoluteUrl'] = true;
            if ($this->absoluteUriScheme !== null) {
                $typolinkConfiguration['forceAbsoluteUrl.']['scheme'] = $this->absoluteUriScheme;
            }
        }
        $uri = $this->contentObject->typoLink_URL($typolinkConfiguration);
        return $uri;
    }

    /**
     * Builds a TypoLink configuration array from the current settings
     *
     * @return array typolink configuration array
     * @see TSref/typolink
     */
    protected function buildTypolinkConfiguration()
    {
        $typolinkConfiguration = [];
        $typolinkConfiguration['parameter'] = $this->targetPageUid ?? $GLOBALS['TSFE']->id;
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
                    'exclude' => implode(',', $this->argumentsToBeExcludedFromQueryString)
                ];
            }
            if ($this->addQueryStringMethod) {
                $typolinkConfiguration['addQueryString.']['method'] = $this->addQueryStringMethod;
            }
        }
        if ($this->noCache === true) {
            $typolinkConfiguration['no_cache'] = 1;
        } elseif ($this->useCacheHash) {
            $typolinkConfiguration['useCacheHash'] = 1;
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
    protected function convertDomainObjectsToIdentityArrays(array $arguments)
    {
        foreach ($arguments as $argumentKey => $argumentValue) {
            // if we have a LazyLoadingProxy here, make sure to get the real instance for further processing
            if ($argumentValue instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
                $argumentValue = $argumentValue->_loadRealInstance();
                // also update the value in the arguments array, because the lazyLoaded object could be
                // hidden and thus the $argumentValue would be NULL.
                $arguments[$argumentKey] = $argumentValue;
            }
            if ($argumentValue instanceof \Iterator) {
                $argumentValue = $this->convertIteratorToArray($argumentValue);
            }
            if ($argumentValue instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject) {
                if ($argumentValue->getUid() !== null) {
                    $arguments[$argumentKey] = $argumentValue->getUid();
                } elseif ($argumentValue instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject) {
                    $arguments[$argumentKey] = $this->convertTransientObjectToArray($argumentValue);
                } else {
                    throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException('Could not serialize Domain Object ' . get_class($argumentValue) . '. It is neither an Entity with identity properties set, nor a Value Object.', 1260881688);
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
    protected function convertIteratorToArray(\Iterator $iterator)
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
     * @todo Refactore this into convertDomainObjectsToIdentityArrays()
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function convertTransientObjectToArray(\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject $object)
    {
        $result = [];
        foreach ($object->_getProperties() as $propertyName => $propertyValue) {
            if ($propertyValue instanceof \Iterator) {
                $propertyValue = $this->convertIteratorToArray($propertyValue);
            }
            if ($propertyValue instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject) {
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
