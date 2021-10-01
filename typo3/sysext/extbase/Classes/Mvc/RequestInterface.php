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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;

/**
 * Contract for an extbase request.
 *
 * @todo v12: interface RequestInterface extends ServerRequestInterface
 */
interface RequestInterface
{
    /**
     * Returns the plugin key.
     * @todo v12: Enable
     */
    // public function getPluginName(): string;

    /**
     * Return an instance with the specified plugin name set.
     *
     * @param string|null Plugin name
     * @return self
     * @todo v12: Enable
     */
    // public function withPluginName($pluginName = null): self;

    /**
     * Returns the extension name of the specified controller.
     *
     * @return string|null
     * @todo v12: Enable
     */
    // public function getControllerExtensionName(): ?string;

    /**
     * Return an instance with the specified controller extension name set.
     *
     * @param string|null Extension name
     * @return self
     * @todo v12: Enable
     */
    // public function withControllerExtensionName($controllerExtensionName): RequestInterface;

    /**
     * Returns the extension key of the specified controller.
     * @todo v12: Enable
     */
    // public function getControllerExtensionKey(): string;

    /**
     * Returns the object name of the controller defined by the package
     * key and controller name.
     *
     * @return string The controller's Object Name
     * @todo v12: public function getControllerObjectName(): string
     */
    public function getControllerObjectName();

    /**
     * Return an instance with the specified controller object name set.
     * @todo v12: Enable
     */
    // public function withControllerObjectName(string $controllerObjectName): RequestInterface;

    /**
     * Return an instance with the specified controller alias
     * to class name mapping set.
     * @todo v12: Enable or refactor to render it obsolete.
     */
    // public function withControllerAliasToClassNameMapping(array $controllerAliasToClassNameMapping): RequestInterface;

    /**
     * Returns the object name of the controller supposed to handle this request, if one
     * was specified already (if not, the name of the default controller is returned)
     * @todo v12: Enable
     */
    // public function getControllerName(): string;

    /**
     * Returns the name of the action the controller is supposed to execute.
     * @todo v12: Enable
     */
    // public function getControllerActionName(): string;

    /**
     * Return an instance with the specified controller action name set.
     *
     * Note that the action name must start with a lower case letter and is case sensitive.
     * @param string|null Action name
     * @return self
     * @todo v12: Enable
     */
    //public function withControllerActionName($actionName): RequestInterface;

    /**
     * Returns the value of the specified argument.
     *
     * @param string $argumentName Name of the argument
     * @return string|array Value of the argument
     * @throws NoSuchArgumentException if such an argument does not exist
     * @todo v12: public function getArgument(string $argumentName)
     */
    public function getArgument($argumentName);

    /**
     * Checks if an argument of the given name exists (is set).
     * @todo v12: public function hasArgument(string $argumentName): bool
     */
    public function hasArgument($argumentName);

    /**
     * Return an instance with the specified argument set.
     *
     * @param string $argumentName Name of the argument to set
     * @param mixed $value The new value
     * @return RequestInterface
     * @todo v12: Enable
     */
    // public function withArgument(string $argumentName, $value): RequestInterface;

    /**
     * Returns an array of extbase arguments and their values.
     * @todo v12: public function getArguments(): array
     */
    public function getArguments();

    /**
     * Return an instance with the specified extbase arguments, replacing
     * any arguments which existed before.
     * @todo v12: Enable
     */
    // public function withArguments(array $arguments): RequestInterface;

    /**
     * Returns the requested representation format, something
     * like "html", "xml", "png", "json" or the like.
     * @todo v12: Enable
     */
    // public function getFormat(): string;

    /**
     * Return an instance with the specified format.
     *
     * This method allows setting the format as described in getFormat().
     * @todo v12: Enable
     */
    // public function withFormat(string $format): RequestInterface;
}
