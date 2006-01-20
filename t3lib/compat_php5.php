<?php
/**
 * TYPO3 compatibility layer PHP4 <> PHP5
 * These functions provide PHP5 functionality when not available (in PHP4).
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 */


/**
 * borrowed from PEAR
 * @author Aidan Lister <aidan@php.net>
 */

eval('
    function clone($object)
    {
        // Sanity check
        if (!is_object($object)) {
            user_error(\'clone() __clone method called on non-object\', E_USER_WARNING);
            return;
        }

        // Use serialize/unserialize trick to deep copy the object
        $object = unserialize(serialize($object));

        // If there is a __clone method call it on the "new" class
        if (method_exists($object, \'__clone\')) {
            $object->__clone();
        }

        return $object;
    }
');


?>