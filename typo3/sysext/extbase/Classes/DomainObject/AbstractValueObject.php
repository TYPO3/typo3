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

namespace TYPO3\CMS\Extbase\DomainObject;

/**
 * An abstract Value Object. A Value Object is an object that describes some characteristic
 * or attribute (e.g. a color) but carries no concept of identity.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
abstract class AbstractValueObject extends AbstractDomainObject
{
    /**
     * Returns the value of the Value Object. Must be overwritten by a concrete value object.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->__toString();
    }
}
