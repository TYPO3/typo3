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

namespace TYPO3\CMS\Extbase\Mvc\Controller;

use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;

/**
 * Interface for controllers
 */
interface ControllerInterface
{
    /**
     * Checks if the current request type is supported by the controller.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The current request
     * @return bool TRUE if this request type is supported, otherwise FALSE
     */
    public function canProcessRequest(RequestInterface $request);

    /**
     * Processes a general request. The result can be returned by altering the given response.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request object
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, modified by the controller
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException if the controller doesn't support the current request type
     */
    public function processRequest(RequestInterface $request, ResponseInterface $response);
}
