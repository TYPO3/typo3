<?php
namespace TYPO3\CMS\Core\Type;

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
 * This is an interface that has to be used by all Core Types.
 * All of them have to implement a __toString() method that is
 * used to get a flatten string for the persistence of the object.
 */
interface TypeInterface
{
    /**
     * Core types must implement the __toString function in order to be
     * serialized to the database;
     *
     * @return string
     */
    public function __toString();
}
