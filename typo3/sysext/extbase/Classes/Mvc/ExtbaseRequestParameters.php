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

namespace TYPO3\CMS\Extbase\Mvc;

use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;

/**
 * Extbase request related state.
 * Attached as 'extbase' attribute to PSR-7 ServerRequestInterface.
 *
 * @internal Set up extbase internally, use TYPO3\CMS\Extbase\Mvc\Request instead.
 */
class ExtbaseRequestParameters
{
    /**
     * @var string Key of the plugin which identifies the plugin. It must be a string containing [a-z0-9]
     */
    protected $pluginName = '';

    /**
     * @var string Name of the extension which is supposed to handle this request. This is the extension name converted to UpperCamelCase
     *
     * @todo: Should probably at least init to empty string.
     */
    protected $controllerExtensionName;

    /**
     * @var string
     *
     * @todo: Should probably at least init to empty string.
     */
    protected $controllerObjectName;

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
    protected array $internalArguments = [];

    /**
     * @var string The requested representation format
     */
    protected $format = 'html';

    /**
     * @var bool If this request has been changed and needs to be dispatched again
     * @deprecated since v11, will be removed in v12.
     */
    protected $dispatched = false;

    /**
     * If this request is a forward because of an error, the original request gets filled.
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Request|null
     */
    protected $originalRequest;

    /**
     * If the request is a forward because of an error, these mapping results get filled here.
     *
     * @var \TYPO3\CMS\Extbase\Error\Result|null
     */
    protected $originalRequestMappingResults;

    /**
     * Sets the dispatched flag
     *
     * @param bool $flag If this request has been dispatched
     * @deprecated since v11, will be removed in v12.
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
     * @return bool TRUE if this request has been dispatched successfully
     * @deprecated since v11, will be removed in v12.
     */
    public function isDispatched()
    {
        return $this->dispatched;
    }

    /**
     * @param string $controllerClassName
     */
    public function __construct(string $controllerClassName = '')
    {
        $this->controllerObjectName = $controllerClassName;
    }

    /**
     * @return string
     */
    public function getControllerObjectName(): string
    {
        return $this->controllerObjectName;
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
        $this->controllerExtensionName = $nameParts['extensionName'];
        $this->controllerName = $nameParts['controllerName'];
        return $this;
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
        return $this;
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
    public function setControllerExtensionName($controllerExtensionName): self
    {
        if ($controllerExtensionName !== null) {
            $this->controllerExtensionName = $controllerExtensionName;
        }
        return $this;
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
        return GeneralUtility::camelCaseToLowerCaseUnderscored($this->controllerExtensionName);
    }

    /**
     * @var array
     */
    protected $controllerAliasToClassNameMapping = [];

    /**
     * @param array $controllerAliasToClassNameMapping
     */
    public function setControllerAliasToClassNameMapping(array $controllerAliasToClassNameMapping)
    {
        // this is only needed as long as forwarded requests are altered and unless there
        // is no new request object created by the request builder.
        $this->controllerAliasToClassNameMapping = $controllerAliasToClassNameMapping;
        return $this;
    }

    /**
     * Sets the name of the controller which is supposed to handle the request.
     * Note: This is not the object name of the controller!
     *
     * @param string $controllerName Name of the controller
     * @throws Exception\InvalidControllerNameException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setControllerName($controllerName): self
    {
        if (!is_string($controllerName) && $controllerName !== null) {
            throw new InvalidControllerNameException('The controller name must be a valid string, ' . gettype($controllerName) . ' given.', 1187176358);
        }
        if ($controllerName !== null) {
            $this->controllerName = $controllerName;
            $this->controllerObjectName = $this->controllerAliasToClassNameMapping[$controllerName] ?? '';
            // There might be no Controller Class, for example for Fluid Templates.
        }
        return $this;
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
    public function setControllerActionName($actionName): self
    {
        if (!is_string($actionName) && $actionName !== null) {
            throw new InvalidActionNameException('The action name must be a valid string, ' . gettype($actionName) . ' given (' . $actionName . ').', 1187176359);
        }
        if ($actionName[0] !== strtolower($actionName[0]) && $actionName !== null) {
            throw new InvalidActionNameException('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
        }
        if ($actionName !== null) {
            $this->controllerActionName = $actionName;
        }
        return $this;
    }

    /**
     * Returns the name of the action the controller is supposed to execute.
     *
     * @return string Action name
     */
    public function getControllerActionName(): string
    {
        $controllerObjectName = $this->getControllerObjectName();
        if ($controllerObjectName !== '' && $this->controllerActionName === strtolower($this->controllerActionName)) {
            // todo: this is nonsense! We can detect a non existing method in
            // todo: \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin, if necessary.
            // todo: At this point, we want to have a getter for a fixed value.
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
     * @throws InvalidArgumentNameException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setArgument(string $argumentName, $value): self
    {
        if ($argumentName === '') {
            throw new InvalidArgumentNameException('Invalid argument name.', 1210858767);
        }
        if ($argumentName[0] === '_' && $argumentName[1] === '_') {
            $this->internalArguments[$argumentName] = $value;
            return $this;
        }
        if (!in_array($argumentName, ['@extension', '@subpackage', '@controller', '@action', '@format'], true)) {
            $this->arguments[$argumentName] = $value;
        }
        return $this;
    }

    /**
     * Sets the whole arguments array and therefore replaces any arguments
     * which existed before.
     *
     * @param array $arguments An array of argument names and their values
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setArguments(array $arguments): self
    {
        $this->arguments = [];
        foreach ($arguments as $argumentName => $argumentValue) {
            $this->setArgument($argumentName, $argumentValue);
        }
        return $this;
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
     * @return string|array Value of the argument
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException if such an argument does not exist
     */
    public function getArgument($argumentName)
    {
        if (!isset($this->arguments[$argumentName])) {
            throw new NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
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
    public function setFormat(string $format): self
    {
        $this->format = $format;
        return $this;
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
     * @return \TYPO3\CMS\Extbase\Mvc\Request|null the original request.
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getOriginalRequest(): ?Request
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
    public function getOriginalRequestMappingResults(): Result
    {
        if ($this->originalRequestMappingResults === null) {
            return new Result();
        }
        return $this->originalRequestMappingResults;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Error\Result $originalRequestMappingResults
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function setOriginalRequestMappingResults(Result $originalRequestMappingResults)
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
    public function getInternalArguments(): array
    {
        return $this->internalArguments;
    }

    /**
     * Returns the value of the specified argument
     *
     * @param string $argumentName Name of the argument
     * @return string|null Value of the argument, or NULL if not set.
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
