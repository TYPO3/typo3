<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Compatibility;

/**
 * Trait to support the logging of deprecation of public INSTANCE methods.
 *
 * This is useful due to the long list of PHP4 methods that have been set to
 * public previously, which should be removed or moved to "protected" / "private".
 *
 * Usage:
 *
 * 1. Use this trait for the class with the method to change the visibility status or to be removed.
 * 2. Add the class methods with deprecated public visibility to $deprecatedPublicMethods.
 * 3. Set these methods to protected or private.
 * 4. Add the phpDoc tag "@private" to the method so IDEs understand that.
 *
 * With the next major release remove the "@private" tag and remove the methods from
 * $deprecatedPublicMethods. Remove the trait use after removing the last deprecation.
 *
 * Note:
 *
 * - Only use this trait in classes only that do not define their own magic __call() method.
 * - Do not use this trait for static methods.
 *
 * Example usage:
 *
 *
 * class MyControllerClass {
 *     use PublicMethodDeprecationTrait;
 *
 *     /**
 *       * List previously publicly accessible variables
 *       * @var array
 *       *...
 *     private $deprecatedPublicMethods = [
 *         'myMethod' => 'Using MyControllerClass::myMethod() is deprecated and will not be possible anymore in TYPO3 v10.0. Use MyControllerClass:myOtherMethod() instead.'
 *     ];
 *
 *     /**
 *      * This is my method.
 *      *
 *      * @deprecated (if deprecated)
 *      * @private (if switched to private)
 *      /
 *     protected function myMethod($arg1, $arg2);
 * }
 */

/**
 * This trait has no public methods by default, ensure to add a $deprecatedPublicMethods property
 * to your class when using this trait.
 */
trait PublicMethodDeprecationTrait
{
    /**
     * Checks if the method of the given name is available, calls it but throws a deprecation.
     * If the method does not exist, a fatal error is thrown.
     *
     * Unavailable protected methods must return in a fatal error as usual.
     * Marked methods are called and a deprecation entry is thrown.
     *
     * __call() is not called for public methods.
     *
     * @property array $deprecatedPublicMethods List of deprecated public methods
     * @param string $methodName
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $methodName, array $arguments)
    {
        if (method_exists($this, $methodName) && isset($this->deprecatedPublicMethods[$methodName])) {
            trigger_error($this->deprecatedPublicMethods[$methodName], E_USER_DEPRECATED);
            return $this->$methodName(...$arguments);
        }

        // Do the same behaviour as calling $myObject->method();
        if (method_exists($this, $methodName)) {
            throw new \Error('Call to protected/private method ' . self::class . '::' . $methodName . '()');
        }

        throw new \Error('Call to undefined method ' . self::class . '::' . $methodName . '()');
    }
}
