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
    public function __construct($request = null)
    {
        trigger_error(__CLASS__ . ' will be removed in TYPO3 v12, use ForwardResponse instead, see ActionController->forwardToReferringRequest().', E_USER_DEPRECATED);
        parent::__construct($request);
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
                $this->getExtbaseAttribute()->setControllerExtensionName($value);
                break;
            case '@controller':
                $this->getExtbaseAttribute()->setControllerName($value);
                break;
            case '@action':
                $this->getExtbaseAttribute()->setControllerActionName($value);
                break;
            case '@format':
                $this->getExtbaseAttribute()->setFormat($value);
                break;
        }
    }
}
