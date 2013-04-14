<?php
namespace TYPO3\CMS\Extbase\Mvc\Web\Routing;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
/**
 * An URI Builder
 *
 * @api
 */
class UriBuilder {

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
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Request
	 */
	protected $request;

	/**
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * Arguments which have been used for building the last URI
	 *
	 * @var array
	 */
	protected $lastArguments = array();

	/**
	 * @var string
	 */
	protected $section = '';

	/**
	 * @var boolean
	 */
	protected $createAbsoluteUri = FALSE;

	/**
	 * @var string
	 */
	protected $absoluteUriScheme = NULL;

	/**
	 * @var boolean
	 */
	protected $addQueryString = FALSE;

	/**
	 * @var array
	 */
	protected $argumentsToBeExcludedFromQueryString = array();

	/**
	 * @var boolean
	 */
	protected $linkAccessRestrictedPages = FALSE;

	/**
	 * @var integer
	 */
	protected $targetPageUid = NULL;

	/**
	 * @var integer
	 */
	protected $targetPageType = 0;

	/**
	 * @var boolean
	 */
	protected $noCache = FALSE;

	/**
	 * @var boolean
	 */
	protected $useCacheHash = TRUE;

	/**
	 * @var string
	 */
	protected $format = '';

	/**
	 * @var string
	 */
	protected $argumentPrefix = NULL;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
	 */
	protected $environmentService;

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
	 * @return void
	 */
	public function injectExtensionService(\TYPO3\CMS\Extbase\Service\ExtensionService $extensionService) {
		$this->extensionService = $extensionService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
	 * @return void
	 */
	public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService) {
		$this->environmentService = $environmentService;
	}

	/**
	 * Life-cycle method that is called by the DI container as soon as this object is completely built
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->contentObject = $this->configurationManager->getContentObject();
	}

	/**
	 * Sets the current request
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Request $request
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 */
	public function setRequest(\TYPO3\CMS\Extbase\Mvc\Request $request) {
		$this->request = $request;
		return $this;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Additional query parameters.
	 * If you want to "prefix" arguments, you can pass in multidimensional arrays:
	 * array('prefix1' => array('foo' => 'bar')) gets "&prefix1[foo]=bar"
	 *
	 * @param array $arguments
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setArguments(array $arguments) {
		$this->arguments = $arguments;
		return $this;
	}

	/**
	 * @return array
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * If specified, adds a given HTML anchor to the URI (#...)
	 *
	 * @param string $section
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setSection($section) {
		$this->section = $section;
		return $this;
	}

	/**
	 * @return string
	 * @api
	 */
	public function getSection() {
		return $this->section;
	}

	/**
	 * Specifies the format of the target (e.g. "html" or "xml")
	 *
	 * @param string $format
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setFormat($format) {
		$this->format = $format;
		return $this;
	}

	/**
	 * @return string
	 * @api
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * If set, the URI is prepended with the current base URI. Defaults to FALSE.
	 *
	 * @param boolean $createAbsoluteUri
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setCreateAbsoluteUri($createAbsoluteUri) {
		$this->createAbsoluteUri = $createAbsoluteUri;
		return $this;
	}

	/**
	 * @return boolean
	 * @api
	 */
	public function getCreateAbsoluteUri() {
		return $this->createAbsoluteUri;
	}

	/**
	 * @return string
	 */
	public function getAbsoluteUriScheme() {
		return $this->absoluteUriScheme;
	}

	/**
	 * Sets the scheme that should be used for absolute URIs in FE mode
	 *
	 * @param string $absoluteUriScheme the scheme to be used for absolute URIs
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 */
	public function setAbsoluteUriScheme($absoluteUriScheme) {
		$this->absoluteUriScheme = $absoluteUriScheme;
		return $this;
	}

	/**
	 * If set, the current query parameters will be merged with $this->arguments. Defaults to FALSE.
	 *
	 * @param boolean $addQueryString
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @see TSref/typolink.addQueryString
	 */
	public function setAddQueryString($addQueryString) {
		$this->addQueryString = (boolean) $addQueryString;
		return $this;
	}

	/**
	 * @return boolean
	 * @api
	 */
	public function getAddQueryString() {
		return $this->addQueryString;
	}

	/**
	 * A list of arguments to be excluded from the query parameters
	 * Only active if addQueryString is set
	 *
	 * @param array $argumentsToBeExcludedFromQueryString
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @see TSref/typolink.addQueryString.exclude
	 * @see setAddQueryString()
	 */
	public function setArgumentsToBeExcludedFromQueryString(array $argumentsToBeExcludedFromQueryString) {
		$this->argumentsToBeExcludedFromQueryString = $argumentsToBeExcludedFromQueryString;
		return $this;
	}

	/**
	 * @return array
	 * @api
	 */
	public function getArgumentsToBeExcludedFromQueryString() {
		return $this->argumentsToBeExcludedFromQueryString;
	}

	/**
	 * Specifies the prefix to be used for all arguments.
	 *
	 * @param string $argumentPrefix
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 */
	public function setArgumentPrefix($argumentPrefix) {
		$this->argumentPrefix = (string) $argumentPrefix;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getArgumentPrefix() {
		return $this->argumentPrefix;
	}

	/**
	 * If set, URIs for pages without access permissions will be created
	 *
	 * @param boolean $linkAccessRestrictedPages
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setLinkAccessRestrictedPages($linkAccessRestrictedPages) {
		$this->linkAccessRestrictedPages = (boolean) $linkAccessRestrictedPages;
		return $this;
	}

	/**
	 * @return boolean
	 * @api
	 */
	public function getLinkAccessRestrictedPages() {
		return $this->linkAccessRestrictedPages;
	}

	/**
	 * Uid of the target page
	 *
	 * @param integer $targetPageUid
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setTargetPageUid($targetPageUid) {
		$this->targetPageUid = $targetPageUid;
		return $this;
	}

	/**
	 * returns $this->targetPageUid.
	 *
	 * @return integer
	 * @api
	 */
	public function getTargetPageUid() {
		return $this->targetPageUid;
	}

	/**
	 * Sets the page type of the target URI. Defaults to 0
	 *
	 * @param integer $targetPageType
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setTargetPageType($targetPageType) {
		$this->targetPageType = (integer) $targetPageType;
		return $this;
	}

	/**
	 * @return integer
	 * @api
	 */
	public function getTargetPageType() {
		return $this->targetPageType;
	}

	/**
	 * by default FALSE; if TRUE, &no_cache=1 will be appended to the URI
	 * This overrules the useCacheHash setting
	 *
	 * @param boolean $noCache
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setNoCache($noCache) {
		$this->noCache = (boolean) $noCache;
		return $this;
	}

	/**
	 * @return boolean
	 * @api
	 */
	public function getNoCache() {
		return $this->noCache;
	}

	/**
	 * by default TRUE; if FALSE, no cHash parameter will be appended to the URI
	 * If noCache is set, this setting will be ignored.
	 *
	 * @param boolean $useCacheHash
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function setUseCacheHash($useCacheHash) {
		$this->useCacheHash = (boolean) $useCacheHash;
		return $this;
	}

	/**
	 * @return boolean
	 * @api
	 */
	public function getUseCacheHash() {
		return $this->useCacheHash;
	}

	/**
	 * Returns the arguments being used for the last URI being built.
	 * This is only set after build() / uriFor() has been called.
	 *
	 * @return array The last arguments
	 */
	public function getLastArguments() {
		return $this->lastArguments;
	}

	/**
	 * Resets all UriBuilder options to their default value
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 */
	public function reset() {
		$this->arguments = array();
		$this->section = '';
		$this->format = '';
		$this->createAbsoluteUri = FALSE;
		$this->addQueryString = FALSE;
		$this->argumentsToBeExcludedFromQueryString = array();
		$this->linkAccessRestrictedPages = FALSE;
		$this->targetPageUid = NULL;
		$this->targetPageType = 0;
		$this->noCache = FALSE;
		$this->useCacheHash = TRUE;
		$this->argumentPrefix = NULL;
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
	 * @api
	 * @see build()
	 */
	public function uriFor($actionName = NULL, $controllerArguments = array(), $controllerName = NULL, $extensionName = NULL, $pluginName = NULL) {
		if ($actionName !== NULL) {
			$controllerArguments['action'] = $actionName;
		}
		if ($controllerName !== NULL) {
			$controllerArguments['controller'] = $controllerName;
		} else {
			$controllerArguments['controller'] = $this->request->getControllerName();
		}
		if ($extensionName === NULL) {
			$extensionName = $this->request->getControllerExtensionName();
		}
		if ($pluginName === NULL && $this->environmentService->isEnvironmentInFrontendMode()) {
			$pluginName = $this->extensionService->getPluginNameByAction($extensionName, $controllerArguments['controller'], $controllerArguments['action']);
		}
		if ($pluginName === NULL) {
			$pluginName = $this->request->getPluginName();
		}
		if ($this->environmentService->isEnvironmentInFrontendMode() && $this->configurationManager->isFeatureEnabled('skipDefaultArguments')) {
			$controllerArguments = $this->removeDefaultControllerAndAction($controllerArguments, $extensionName, $pluginName);
		}
		if ($this->targetPageUid === NULL && $this->environmentService->isEnvironmentInFrontendMode()) {
			$this->targetPageUid = $this->extensionService->getTargetPidByPlugin($extensionName, $pluginName);
		}
		if ($this->format !== '') {
			$controllerArguments['format'] = $this->format;
		}
		if ($this->argumentPrefix !== NULL) {
			$prefixedControllerArguments = array($this->argumentPrefix => $controllerArguments);
		} else {
			$pluginNamespace = $this->extensionService->getPluginNamespace($extensionName, $pluginName);
			$prefixedControllerArguments = array($pluginNamespace => $controllerArguments);
		}
		$this->arguments = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($this->arguments, $prefixedControllerArguments);
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
	protected function removeDefaultControllerAndAction(array $controllerArguments, $extensionName, $pluginName) {
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
	 * @api
	 * @see buildBackendUri()
	 * @see buildFrontendUri()
	 */
	public function build() {
		if ($this->environmentService->isEnvironmentInBackendMode()) {
			return $this->buildBackendUri();
		} else {
			return $this->buildFrontendUri();
		}
	}

	/**
	 * Builds the URI, backend flavour
	 * The resulting URI is relative and starts with "mod.php".
	 * The settings pageUid, pageType, noCache, useCacheHash & linkAccessRestrictedPages
	 * will be ignored in the backend.
	 *
	 * @return string The URI
	 */
	public function buildBackendUri() {
		if ($this->addQueryString === TRUE) {
			$arguments = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
			foreach ($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
				$argumentToBeExcluded = \TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array($argumentToBeExcluded, TRUE);
				$arguments = \TYPO3\CMS\Core\Utility\GeneralUtility::arrayDiffAssocRecursive($arguments, $argumentToBeExcluded);
			}
		} else {
			$arguments = array(
				'M' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M'),
				'id' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('id')
			);
		}
		$arguments = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($arguments, $this->arguments);
		$arguments = $this->convertDomainObjectsToIdentityArrays($arguments);
		$this->lastArguments = $arguments;
		$uri = 'mod.php?' . http_build_query($arguments, NULL, '&');
		if ($this->section !== '') {
			$uri .= '#' . $this->section;
		}
		if ($this->createAbsoluteUri === TRUE) {
			$uri = $this->request->getBaseUri() . $uri;
		}
		return $uri;
	}

	/**
	 * Builds the URI, frontend flavour
	 *
	 * @return string The URI
	 * @see buildTypolinkConfiguration()
	 */
	public function buildFrontendUri() {
		$typolinkConfiguration = $this->buildTypolinkConfiguration();
		if ($this->createAbsoluteUri === TRUE) {
			$typolinkConfiguration['forceAbsoluteUrl'] = TRUE;
			if ($this->absoluteUriScheme !== NULL) {
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
	protected function buildTypolinkConfiguration() {
		$typolinkConfiguration = array();
		$typolinkConfiguration['parameter'] = $this->targetPageUid !== NULL ? $this->targetPageUid : $GLOBALS['TSFE']->id;
		if ($this->targetPageType !== 0) {
			$typolinkConfiguration['parameter'] .= ',' . $this->targetPageType;
		}
		if (count($this->arguments) > 0) {
			$arguments = $this->convertDomainObjectsToIdentityArrays($this->arguments);
			$this->lastArguments = $arguments;
			$typolinkConfiguration['additionalParams'] = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl(NULL, $arguments);
		}
		if ($this->addQueryString === TRUE) {
			$typolinkConfiguration['addQueryString'] = 1;
			if (count($this->argumentsToBeExcludedFromQueryString) > 0) {
				$typolinkConfiguration['addQueryString.'] = array(
					'exclude' => implode(',', $this->argumentsToBeExcludedFromQueryString)
				);
			}
		}
		if ($this->noCache === TRUE) {
			$typolinkConfiguration['no_cache'] = 1;
		} elseif ($this->useCacheHash) {
			$typolinkConfiguration['useCacheHash'] = 1;
		}
		if ($this->section !== '') {
			$typolinkConfiguration['section'] = $this->section;
		}
		if ($this->linkAccessRestrictedPages === TRUE) {
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
	protected function convertDomainObjectsToIdentityArrays(array $arguments) {
		foreach ($arguments as $argumentKey => $argumentValue) {
			// if we have a LazyLoadingProxy here, make sure to get the real instance for further processing
			if ($argumentValue instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
				$argumentValue = $argumentValue->_loadRealInstance();
				// also update the value in the arguments array, because the lazyLoaded object could be
				// hidden and thus the $argumentValue would be NULL.
				$arguments[$argumentKey] = $argumentValue;
			}
			if ($argumentValue instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject) {
				if ($argumentValue->getUid() !== NULL) {
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
	 * Converts a given object recursively into an array.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject $object
	 * @return array
	 * @todo Refactore this into convertDomainObjectsToIdentityArrays()
	 */
	public function convertTransientObjectToArray(\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject $object) {
		$result = array();
		foreach ($object->_getProperties() as $propertyName => $propertyValue) {
			if ($propertyValue instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject) {
				if ($propertyValue->getUid() !== NULL) {
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

?>