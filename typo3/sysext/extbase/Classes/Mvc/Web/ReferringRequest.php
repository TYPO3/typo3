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

namespace TYPO3\CMS\Extbase\Mvc\Web;

use TYPO3\CMS\Extbase\Mvc\Request;

/**
 * Represents a referring web request.
 *
 * @deprecated since v11, will be removed in v12. Create a ForwardResponse instead, see ActionController->forwardToReferringRequest()
 */
class ReferringRequest extends Request
{
    /**
     * @param string $controllerClassName
     */
    public function __construct(string $controllerClassName = '')
    {
        // @todo: Move to parent::__construct() in case Request is deprecated in v11, too, otherwise drop this todo.
        trigger_error(__CLASS__ . ' will be removed in TYPO3 v12, use ForwardResponse instead, see ActionController->forwardToReferringRequest().', E_USER_DEPRECATED);
        parent::__construct($controllerClassName);
    }

    /**
     * Sets the value of the specified argument
     *
     * @param string $argumentName Name of the argument to set
     * @param mixed $value The new value
     */
    public function setArgument($argumentName, $value)
    {
        parent::setArgument($argumentName, $value);

        switch ($argumentName) {
            case '@extension':
                $this->setControllerExtensionName($value);
                break;
            case '@controller':
                $this->setControllerName($value);
                break;
            case '@action':
                $this->setControllerActionName($value);
                break;
            case '@format':
                $this->setFormat($value);
                break;
        }
    }
}
