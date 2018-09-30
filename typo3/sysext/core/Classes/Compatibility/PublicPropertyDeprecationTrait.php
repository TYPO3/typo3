<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Compatibility;

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
 * Trait to support the logging of deprecation of public properties.
 *
 * This is useful due to the long list of PHP4 properties have been set to
 * public previously, which should be removed or moved to "protected" / "private".
 *
 * Usage:
 *
 * - Use this trait for the class with the properties to change the visibility status or to be removed.
 * - Set internal class properties to protected.
 * - Add the phpDoc tag "private" to the property (so IDEs understand that).
 * - Remove this tag with the next major version.
 * - Remove trait after last deprecation.
 *
 * Note:
 *
 * Use this trait for classes only that do not make use of magic accessors otherwise.
 *
 * Example usage:
 *
 *
 * class MyControllerClass {
 *     use PublicPropertyDeprecationTrait;
 *
 *     /**
 *       * List previously publically accessible variables
 *       * @var array
 *       *...
 *     private $deprecatedPublicProperties = [
 *         'myProperty' => 'Using myProperty is deprecated and will not be possible anymore in TYPO3 v10.0. Use getMyProperty() instead.'
 *     ];
 *
 *     /**
 *      * This is my property.
 *      *
 *      * @var bool
 *      * @deprecated (if deprecated)
 *      * @private (if switched to private)
 *      /
 *     protected $myProperty = true;
 * }
 */

/**
 * This trait has no public properties by default, ensure to add a $deprecatedPublicProperties to your class
 * when using this trait.
 */
trait PublicPropertyDeprecationTrait
{
    /**
     * Checks if the property of the given name is set.
     *
     * Unmarked protected properties must return false as usual.
     * Marked properties are evaluated by isset().
     *
     * This method is not called for public properties.
     *
     * @property array $deprecatedPublicProperties List of deprecated public properties
     * @param string $propertyName
     * @return bool
     */
    public function __isset(string $propertyName)
    {
        if (isset($this->deprecatedPublicProperties[$propertyName])) {
            trigger_error($this->deprecatedPublicProperties[$propertyName], E_USER_DEPRECATED);
            return isset($this->$propertyName);
        }
        return false;
    }

    /**
     * Gets the value of the property of the given name if tagged.
     *
     * The evaluation is done in the assumption that this method is never
     * reached for a public property.
     *
     * @property array $deprecatedPublicProperties List of deprecated public properties
     * @param string $propertyName
     * @return mixed
     */
    public function __get(string $propertyName)
    {
        if (isset($this->deprecatedPublicProperties[$propertyName])) {
            trigger_error($this->deprecatedPublicProperties[$propertyName], E_USER_DEPRECATED);
        }
        return $this->$propertyName;
    }

    /**
     * Sets the property of the given name if tagged.
     *
     * Additionally it's allowed to set unknown properties.
     *
     * The evaluation is done in the assumption that this method is never
     * reached for a public property.
     *
     * @property array $deprecatedPublicProperties List of deprecated public properties
     * @param string $propertyName
     * @param mixed $propertyValue
     */
    public function __set(string $propertyName, $propertyValue)
    {
        // It's allowed to set an unknown property as public, the check is thus necessary
        if (property_exists($this, $propertyName) && isset($this->deprecatedPublicProperties[$propertyName])) {
            trigger_error($this->deprecatedPublicProperties[$propertyName], E_USER_DEPRECATED);
        }
        $this->$propertyName = $propertyValue;
    }

    /**
     * Unsets the property of the given name if tagged.
     *
     * @property array $deprecatedPublicProperties List of deprecated public properties
     * @param string $propertyName
     */
    public function __unset(string $propertyName)
    {
        if (isset($this->deprecatedPublicProperties[$propertyName])) {
            trigger_error($this->deprecatedPublicProperties[$propertyName], E_USER_DEPRECATED);
        }
        unset($this->$propertyName);
    }
}
