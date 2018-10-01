<?php
namespace TYPO3\CMS\Extbase\Mvc;

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

use TYPO3\CMS\Core\Utility\ClassNamingUtility;

/**
 * Represents a generic request.
 */
class Request implements RequestInterface
{
    const PATTERN_MATCH_FORMAT = '/^[a-z0-9]{1,5}$/';

    /**
     * Pattern after which the namespaced controller object name is built
     *
     * @var string
     */
    protected $namespacedControllerObjectNamePattern = '@vendor\@extension\@subpackage\Controller\@controllerController';

    /**
     * @var string Key of the plugin which identifies the plugin. It must be a string containing [a-z0-9]
     */
    protected $pluginName = '';

    /**
     * @var string Name of the extension which is supposed to handle this request. This is the extension name converted to UpperCamelCase
     */
    protected $controllerExtensionName;

    /**
     * @var string vendor prefix
     */
    protected $controllerVendorName;

    /**
     * Subpackage key of the controller which is supposed to handle this request.
     *
     * @var string
     */
    protected $controllerSubpackageKey;

    /**
     * @var string Object name of the controller which is supposed to handle this request.
     */
    protected $controllerName = 'Standard';

    /**
     * @var string Name of the action the controller is supposed to take.
     */
    protected $controllerActionName = 'index';

    /**
     * @var array The arguments for this request
     */
    protected $arguments = [];

    /**
     * Framework-internal arguments for this request, such as __referrer.
     * All framework-internal arguments start with double underscore (__),
     * and are only used from within the framework. Not for user consumption.
     * Internal Arguments can be objects, in contrast to public arguments
     *
     * @var array
     */
    protected $internalArguments = [];

    /**
     * @var string The requested representation format
     */
    protected $format = 'txt';

    /**
     * @var bool If this request has been changed and needs to be dispatched again
     */
    protected $dispatched = false;

    /**
     * If this request is a forward because of an error, the original request gets filled.
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Request
     */
    protected $originalRequest;

    /**
     * If the request is a forward because of an error, these mapping results get filled here.
     *
     * @var \TYPO3\CMS\Extbase\Error\Result
     */
    protected $originalRequestMappingResults;

    /**
     * Sets the dispatched flag
     *
     * @param bool $flag If this request has been dispatched
     */
    public function setDispatched($flag)
    {
        $this->dispatched = (bool)$flag;
    }

    /**
     * If this request has been dispatched and addressed by the responsible
     * controller and the response is ready to be sent.
     *
     * The dispatcher will try to dispatch the request again if it has not been
     * addressed yet.
     *
     * @return bool TRUE if this request has been disptached successfully
     */
    public function isDispatched()
    {
        return $this->dispatched;
    }

    /**
     * Returns the object name of the controller defined by the extension name and
     * controller name
     *
     * @return string The controller's Object Name
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchControllerException if the controller does not exist
     */
    public function getControllerObjectName()
    {
        $objectName = str_replace(
            [
                '@extension',
                '@subpackage',
                '@controller',
                '@vendor',
                '\\\\'
            ],
            [
                $this->controllerExtensionName,
                $this->controllerSubpackageKey,
                $this->controllerName,
                $this->controllerVendorName,
                '\\'
            ],
            $this->namespacedControllerObjectNamePattern
        );

        // @todo implement getCaseSensitiveObjectName()
        if ($objectName === false) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchControllerException('The controller object "' . $objectName . '" does not exist.', 1220884009);
        }
        return $objectName;
    }

    /**
     * Explicitly sets the object name of the controller
     *
     * @param string $controllerObjectName The fully qualified controller object name
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setControllerObjectName($controllerObjectName)
    {
        $nameParts = ClassNamingUtility::explodeObjectControllerName($controllerObjectName);
        $this->controllerVendorName = $nameParts['vendorName'] ?? null;
        $this->controllerExtensionName = $nameParts['extensionName'];
        $this->controllerSubpackageKey = $nameParts['subpackageKey'] ?? null;
        $this->controllerName = $nameParts['controllerName'];
    }

    /**
     * Sets the plugin name.
     *
     * @param string|null $pluginName
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setPluginName($pluginName = null)
    {
        if ($pluginName !== null) {
            $this->pluginName = $pluginName;
        }
    }

    /**
     * Returns the plugin key.
     *
     * @return string The plugin key
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }

    /**
     * Sets the extension name of the controller.
     *
     * @param string $controllerExtensionName The extension name.
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException if the extension name is not valid
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setControllerExtensionName($controllerExtensionName)
    {
        if ($controllerExtensionName !== null) {
            $this->controllerExtensionName = $controllerExtensionName;
        }
    }

    /**
     * Returns the extension name of the specified controller.
     *
     * @return string The extension name
     */
    public function getControllerExtensionName()
    {
        return $this->controllerExtensionName;
    }

    /**
     * Returns the extension name of the specified controller.
     *
     * @return string The extension key
     */
    public function getControllerExtensionKey()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($this->controllerExtensionName);
    }

    /**
     * Sets the subpackage key of the controller.
     *
     * @param string $subpackageKey The subpackage key.
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setControllerSubpackageKey($subpackageKey)
    {
        $this->controllerSubpackageKey = $subpackageKey;
    }

    /**
     * Returns the subpackage key of the specified controller.
     * If there is no subpackage key set, the method returns NULL
     *
     * @return string The subpackage key
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getControllerSubpackageKey()
    {
        return $this->controllerSubpackageKey;
    }

    /**
     * Sets the name of the controller which is supposed to handle the request.
     * Note: This is not the object name of the controller!
     *
     * @param string $controllerName Name of the controller
     * @throws Exception\InvalidControllerNameException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setControllerName($controllerName)
    {
        if (!is_string($controllerName) && $controllerName !== null) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException('The controller name must be a valid string, ' . gettype($controllerName) . ' given.', 1187176358);
        }
        if ($controllerName !== null) {
            $this->controllerName = $controllerName;
        }
    }

    /**
     * Returns the object name of the controller supposed to handle this request, if one
     * was set already (if not, the name of the default controller is returned)
     *
     * @return string Object name of the controller
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * Sets the name of the action contained in this request.
     *
     * Note that the action name must start with a lower case letter and is case sensitive.
     *
     * @param string $actionName Name of the action to execute by the controller
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException if the action name is not valid
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setControllerActionName($actionName)
    {
        if (!is_string($actionName) && $actionName !== null) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException('The action name must be a valid string, ' . gettype($actionName) . ' given (' . $actionName . ').', 1187176359);
        }
        if ($actionName[0] !== strtolower($actionName[0]) && $actionName !== null) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
        }
        if ($actionName !== null) {
            $this->controllerActionName = $actionName;
        }
    }

    /**
     * Returns the name of the action the controller is supposed to execute.
     *
     * @return string Action name
     */
    public function getControllerActionName()
    {
        $controllerObjectName = $this->getControllerObjectName();
        if ($controllerObjectName !== '' && $this->controllerActionName === strtolower($this->controllerActionName)) {
            $actionMethodName = $this->controllerActionName . 'Action';
            $classMethods = get_class_methods($controllerObjectName);
            if (is_array($classMethods)) {
                foreach ($classMethods as $existingMethodName) {
                    if (strtolower($existingMethodName) === strtolower($actionMethodName)) {
                        $this->controllerActionName = substr($existingMethodName, 0, -6);
                        break;
                    }
                }
            }
        }
        return $this->controllerActionName;
    }

    /**
     * Sets the value of the specified argument
     *
     * @param string $argumentName Name of the argument to set
     * @param mixed $value The new value
     * @throws Exception\InvalidArgumentNameException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setArgument($argumentName, $value)
    {
        if (!is_string($argumentName) || $argumentName === '') {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException('Invalid argument name.', 1210858767);
        }
        if ($argumentName[0] === '_' && $argumentName[1] === '_') {
            $this->internalArguments[$argumentName] = $value;
            return;
        }
        if (!in_array($argumentName, ['@extension', '@subpackage', '@controller', '@action', '@format', '@vendor'], true)) {
            $this->arguments[$argumentName] = $value;
        }
    }

    /**
     * sets the VendorName
     *
     * @param string $vendorName
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setControllerVendorName($vendorName)
    {
        $this->controllerVendorName = $vendorName;
    }

    /**
     * get the VendorName
     *
     * @return string
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getControllerVendorName()
    {
        return $this->controllerVendorName;
    }

    /**
     * Sets the whole arguments array and therefore replaces any arguments
     * which existed before.
     *
     * @param array $arguments An array of argument names and their values
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = [];
        foreach ($arguments as $argumentName => $argumentValue) {
            $this->setArgument($argumentName, $argumentValue);
        }
    }

    /**
     * Returns an array of arguments and their values
     *
     * @return array Associative array of arguments and their values (which may be arguments and values as well)
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Returns the value of the specified argument
     *
     * @param string $argumentName Name of the argument
     *
     * @return string Value of the argument
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException if such an argument does not exist
     */
    public function getArgument($argumentName)
    {
        if (!isset($this->arguments[$argumentName])) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
        }
        return $this->arguments[$argumentName];
    }

    /**
     * Checks if an argument of the given name exists (is set)
     *
     * @param string $argumentName Name of the argument to check
     *
     * @return bool TRUE if the argument is set, otherwise FALSE
     */
    public function hasArgument($argumentName)
    {
        return isset($this->arguments[$argumentName]);
    }

    /**
     * Sets the requested representation format
     *
     * @param string $format The desired format, something like "html", "xml", "png", "json" or the like. Can even be something like "rss.xml".
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Returns the requested representation format
     *
     * @return string The desired format, something like "html", "xml", "png", "json" or the like.
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Returns the original request. Filled only if a property mapping error occurred.
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Request the original request.
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getOriginalRequest()
    {
        return $this->originalRequest;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Request $originalRequest
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setOriginalRequest(\TYPO3\CMS\Extbase\Mvc\Request $originalRequest)
    {
        $this->originalRequest = $originalRequest;
    }

    /**
     * Get the request mapping results for the original request.
     *
     * @return \TYPO3\CMS\Extbase\Error\Result
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getOriginalRequestMappingResults()
    {
        if ($this->originalRequestMappingResults === null) {
            return new \TYPO3\CMS\Extbase\Error\Result();
        }
        return $this->originalRequestMappingResults;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Error\Result $originalRequestMappingResults
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setOriginalRequestMappingResults(\TYPO3\CMS\Extbase\Error\Result $originalRequestMappingResults)
    {
        $this->originalRequestMappingResults = $originalRequestMappingResults;
    }

    /**
     * Get the internal arguments of the request, i.e. every argument starting
     * with two underscores.
     *
     * @return array
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getInternalArguments()
    {
        return $this->internalArguments;
    }

    /**
     * Returns the value of the specified argument
     *
     * @param string $argumentName Name of the argument
     * @return string Value of the argument, or NULL if not set.
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getInternalArgument($argumentName)
    {
        if (!isset($this->internalArguments[$argumentName])) {
            return null;
        }
        return $this->internalArguments[$argumentName];
    }
}
