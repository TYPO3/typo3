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

/**
 * Contract for a request.
 *
 * @api
 */
interface RequestInterface
{
    /**
     * Sets the dispatched flag
     *
     * @param bool $flag If this request has been dispatched
     * @api
     */
    public function setDispatched($flag);

    /**
     * If this request has been dispatched and addressed by the responsible
     * controller and the response is ready to be sent.
     *
     * The dispatcher will try to dispatch the request again if it has not been
     * addressed yet.
     *
     * @return bool TRUE if this request has been disptached successfully
     * @api
     */
    public function isDispatched();

    /**
     * Returns the object name of the controller defined by the package key and
     * controller name
     *
     * @return string The controller's Object Name
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchControllerException if the controller does not exist
     * @api
     */
    public function getControllerObjectName();

    /**
     * Sets the value of the specified argument
     *
     * @param string $argumentName Name of the argument to set
     * @param mixed $value The new value
     * @api
     */
    public function setArgument($argumentName, $value);

    /**
     * Sets the whole arguments array and therefore replaces any arguments
     * which existed before.
     *
     * @param array $arguments An array of argument names and their values
     * @api
     */
    public function setArguments(array $arguments);

    /**
     * Returns the value of the specified argument
     *
     * @param string $argumentName Name of the argument
     * @return string Value of the argument
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException if such an argument does not exist
     * @api
     */
    public function getArgument($argumentName);

    /**
     * Checks if an argument of the given name exists (is set)
     *
     * @param string $argumentName Name of the argument to check
     * @return bool TRUE if the argument is set, otherwise FALSE
     * @api
     */
    public function hasArgument($argumentName);

    /**
     * Returns an array of arguments and their values
     *
     * @return array Array of arguments and their values (which may be arguments and values as well)
     * @api
     */
    public function getArguments();
}
