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

namespace TYPO3\CMS\Extbase\Mvc;

use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;

/**
 * Extbase request related state.
 * Attached as 'extbase' attribute to PSR-7 ServerRequestInterface.
 *
 * @internal Sets up extbase internally, use TYPO3\CMS\Extbase\Mvc\Request instead.
 */
class ExtbaseRequestParameters
{
    /**
     * Key of the plugin which identifies the plugin.
     * In frontend, it is the second argument of ExtensionUtility::configurePlugin(), example: "FormFramework" in ext:form.
     * In backend, it is the combination $mainModuleName_$extensionName$subModuleName from ExtensionUtility::registerModule(),
     * for example "web_FormFormbuilder" for the ext:form backend module.
     */
    protected string $pluginName = '';

    /**
     * Name of the extension which is supposed to handle this request. This is the extension key in UpperCamelCase.
     * This is typically defined by ExtensionUtility::configurePlugin() and friends as first argument.
     * Example: "IndexedSearch", when the extension key "directory name of extension" is indexed_search.
     */
    protected string $controllerExtensionName = '';

    /**
     * This is the FQDN of a controller, example: "TYPO3\CMS\Form\Controller\FormManagerController"
     * for ext:form backend module.
     */
    protected string $controllerObjectName = '';

    /**
     * Object name of the controller which is supposed to handle this request. This is the non-FQDN
     * version of $controllerObjectName, without the word "Controller", example: "FormManager".
     */
    protected string $controllerName = 'Standard';

    /**
     * A map $controllerName => $controllerObjectName
     */
    protected array $controllerAliasToClassNameMapping = [];

    /**
     * Name of the action the controller is supposed to execute. For example "create" with the
     * controller method name being "createAction()".
     * Action name must start with a lower case letter and is case-sensitive.
     */
    protected string $controllerActionName = 'index';

    /**
     * The arguments for this request. This receives only those arguments relevant and
     * prefixed for this extension/controller/plugin combination.
     */
    protected array $arguments = [];

    /**
     * Framework-internal arguments for this request, such as __referrer.
     * All framework-internal arguments start with double underscore (__),
     * and are only used from within the framework. Not for user consumption.
     * Internal Arguments can be objects, in contrast to public arguments
     */
    protected array $internalArguments = [];

    /**
     * The requested representation format, "html", "xml", "png", "json" or the like.
     * Can even be something like "rss.xml".
     */
    protected string $format = 'html';

    /**
     * If this request is a forward because of an error, the original request gets filled.
     */
    protected ?RequestInterface $originalRequest = null;

    /**
     * If the request is a forward because of an error, these mapping results get filled here.
     */
    protected ?Result $originalRequestMappingResults = null;

    /**
     * If files were uploaded, this array holds the files
     * prefixed for this extension/controller/plugin combination.
     */
    protected array $uploadedFiles = [];

    public function __construct(string $controllerClassName = '')
    {
        $this->controllerObjectName = $controllerClassName;
    }

    public function getControllerObjectName(): string
    {
        return $this->controllerObjectName;
    }

    public function setControllerObjectName(string $controllerObjectName): self
    {
        $nameParts = ClassNamingUtility::explodeObjectControllerName($controllerObjectName);
        $this->controllerExtensionName = $nameParts['extensionName'];
        $this->controllerName = $nameParts['controllerName'];
        return $this;
    }

    public function setPluginName(string $pluginName): self
    {
        $this->pluginName = $pluginName;
        return $this;
    }

    public function getPluginName(): string
    {
        return $this->pluginName;
    }

    public function setControllerExtensionName(string $controllerExtensionName): self
    {
        $this->controllerExtensionName = $controllerExtensionName;
        return $this;
    }

    public function getControllerExtensionName(): string
    {
        return $this->controllerExtensionName;
    }

    public function getControllerExtensionKey(): string
    {
        return GeneralUtility::camelCaseToLowerCaseUnderscored($this->controllerExtensionName);
    }

    public function setControllerAliasToClassNameMapping(array $controllerAliasToClassNameMapping): self
    {
        // this is only needed as long as forwarded requests are altered and unless there
        // is no new request object created by the request builder.
        $this->controllerAliasToClassNameMapping = $controllerAliasToClassNameMapping;
        return $this;
    }

    public function setControllerName(string $controllerName): self
    {
        $this->controllerName = $controllerName;
        // There might be no Controller Class, for example for Fluid Templates.
        $this->controllerObjectName = $this->controllerAliasToClassNameMapping[$controllerName] ?? '';
        return $this;
    }

    public function getControllerName(): string
    {
        return $this->controllerName;
    }

    /**
     * @throws InvalidActionNameException if the action name is not valid
     */
    public function setControllerActionName(string $actionName): self
    {
        if ($actionName[0] !== strtolower($actionName[0])) {
            throw new InvalidActionNameException('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
        }
        $this->controllerActionName = $actionName;
        return $this;
    }

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
     * @param mixed $value The new value
     * @throws InvalidArgumentNameException
     */
    public function setArgument(string $argumentName, mixed $value): self
    {
        if ($argumentName === '') {
            throw new InvalidArgumentNameException('Invalid argument name.', 1210858767);
        }
        if (str_starts_with($argumentName, '__')) {
            $this->internalArguments[$argumentName] = $value;
            return $this;
        }
        if (!in_array($argumentName, ['@extension', '@subpackage', '@controller', '@action', '@format'], true)) {
            $this->arguments[$argumentName] = $value;
        }
        return $this;
    }

    /**
     * Sets the whole arguments array and therefore replaces any arguments which existed before.
     *
     * @param array<string, mixed> $arguments
     * @throws InvalidArgumentNameException
     */
    public function setArguments(array $arguments): self
    {
        $this->arguments = [];
        foreach ($arguments as $argumentName => $argumentValue) {
            $this->setArgument($argumentName, $argumentValue);
        }
        return $this;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Returns the value of the specified argument.
     *
     * @return mixed Value of the argument
     * @throws NoSuchArgumentException if such an argument does not exist
     */
    public function getArgument(string $argumentName): mixed
    {
        if (!isset($this->arguments[$argumentName])) {
            throw new NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
        }
        return $this->arguments[$argumentName];
    }

    /**
     * Checks if an argument of the given name exists (is set)
     */
    public function hasArgument(string $argumentName = ''): bool
    {
        return isset($this->arguments[$argumentName]);
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;
        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Returns the original request. Filled only if a property mapping error occurred.
     */
    public function getOriginalRequest(): ?RequestInterface
    {
        return $this->originalRequest;
    }

    public function setOriginalRequest(RequestInterface $originalRequest): self
    {
        $this->originalRequest = $originalRequest;
        return $this;
    }

    public function getOriginalRequestMappingResults(): Result
    {
        if ($this->originalRequestMappingResults === null) {
            return new Result();
        }
        return $this->originalRequestMappingResults;
    }

    public function setOriginalRequestMappingResults(Result $originalRequestMappingResults): self
    {
        $this->originalRequestMappingResults = $originalRequestMappingResults;
        return $this;
    }

    /**
     * Returns the value of the specified argument
     *
     * @return mixed Value of the argument, or NULL if not set.
     */
    public function getInternalArgument($argumentName): mixed
    {
        if (!isset($this->internalArguments[$argumentName])) {
            return null;
        }
        return $this->internalArguments[$argumentName];
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function setUploadedFiles(array $files): self
    {
        $this->validateUploadedFiles($files);
        $this->uploadedFiles = $files;
        return $this;
    }

    /**
     * Recursively validate the structure in an uploaded files array.
     *
     * @throws \InvalidArgumentException if any leaf is not an UploadedFileInterface instance.
     */
    protected function validateUploadedFiles(array $uploadedFiles): void
    {
        foreach ($uploadedFiles as $file) {
            if (is_array($file)) {
                $this->validateUploadedFiles($file);
                continue;
            }
            if (!$file instanceof UploadedFileInterface) {
                throw new \InvalidArgumentException('Invalid file in uploaded files structure.', 1647338470);
            }
        }
    }
}
