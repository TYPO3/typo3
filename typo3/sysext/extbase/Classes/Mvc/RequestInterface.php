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

/**
 * Contract for an extbase request.
 */
interface RequestInterface extends ServerRequestInterface
{
    /**
     * Returns the plugin key.
     */
    public function getPluginName(): string;

    /**
     * Return an instance with the specified plugin name set.
     */
    public function withPluginName(string $pluginName): RequestInterface;

    /**
     * Returns the extension name of the specified controller.
     */
    public function getControllerExtensionName(): string;

    /**
     * Return an instance with the specified controller extension name set.
     */
    public function withControllerExtensionName(string $controllerExtensionName): RequestInterface;

    /**
     * Returns the extension key of the specified controller.
     */
    public function getControllerExtensionKey(): string;

    /**
     * Returns the object name of the controller defined by the package
     * key and controller name.
     */
    public function getControllerObjectName(): string;

    /**
     * Return an instance with the specified controller object name set.
     */
    public function withControllerObjectName(string $controllerObjectName): RequestInterface;

    /**
     * Returns the object name of the controller supposed to handle this request, if one
     * was specified already (if not, the name of the default controller is returned)
     */
    public function getControllerName(): string;

    /**
     * Return an instance with the specified controller name set.
     */
    public function withControllerName(string $controllerName): RequestInterface;

    /**
     * Returns the name of the action the controller is supposed to execute.
     */
    public function getControllerActionName(): string;

    /**
     * Return an instance with the specified controller action name set.
     *
     * Note that the action name must start with a lower case letter and is case-sensitive.
     */
    public function withControllerActionName(string $actionName): RequestInterface;

    /**
     * Returns the value of the specified argument.
     */
    public function getArgument(string $argumentName): mixed;

    /**
     * Checks if an argument of the given name exists (is set).
     */
    public function hasArgument(string $argumentName): bool;

    /**
     * Return an instance with the specified argument set.
     */
    public function withArgument(string $argumentName, mixed $value): RequestInterface;

    /**
     * Returns an array of extbase arguments and their values.
     */
    public function getArguments(): array;

    /**
     * Return an instance with the specified extbase arguments, replacing
     * any arguments which existed before.
     */
    public function withArguments(array $arguments): RequestInterface;

    /**
     * Returns the requested representation format, something
     * like "html", "xml", "png", "json" or the like.
     */
    public function getFormat(): string;

    /**
     * Return an instance with the specified format.
     * This method allows setting the format as described in getFormat().
     */
    public function withFormat(string $format): RequestInterface;
}
