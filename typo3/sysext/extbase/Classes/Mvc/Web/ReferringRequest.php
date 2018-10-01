<?php
namespace TYPO3\CMS\Extbase\Mvc\Web;

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
 * Represents a referring web request.
 */
class ReferringRequest extends Request
{
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
            case '@subpackage':
                $this->setControllerSubpackageKey($value);
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
            case '@vendor':
                $this->setControllerVendorName($value);
                break;
        }
    }
}
