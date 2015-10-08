<?php
namespace TYPO3\CMS\Extbase\Reflection;

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
 * Extended version of the ReflectionParameter
 */
class ParameterReflection extends \ReflectionParameter
{
    /**
     * Returns the declaring class
     *
     * @return ClassReflection The declaring class
     */
    public function getDeclaringClass()
    {
        return new ClassReflection(parent::getDeclaringClass()->getName());
    }

    /**
     * Returns the parameter class
     *
     * @return ClassReflection The parameter class
     */
    public function getClass()
    {
        try {
            $class = parent::getClass();
        } catch (\Exception $e) {
            return null;
        }
        return is_object($class) ? new ClassReflection($class->getName()) : null;
    }
}
