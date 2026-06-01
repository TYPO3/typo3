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

namespace TYPO3\CMS\Core\Resource\Collection;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Registry for FileCollection classes
 */
class FileCollectionRegistry implements SingletonInterface
{
    /**
     * Registered FileCollection types
     *
     * @var array
     */
    protected $types = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredCollections'] as $type => $class) {
            $this->registerFileCollectionClass($class, $type);
        }
    }

    /**
     * Register a (new) FileCollection type
     *
     * @param string $className
     * @param string $type FileCollection type max length 30 chars (db field restriction)
     * @param bool $override existing FileCollection type
     * @return bool TRUE if registration succeeded
     * @throws \InvalidArgumentException
     */
    public function registerFileCollectionClass($className, $type, $override = false)
    {
        if (strlen($type) > 30) {
            throw new \InvalidArgumentException('FileCollection type can have a max string length of 30 bytes', 1391295611);
        }

        if (!class_exists($className)) {
            throw new \InvalidArgumentException('Class ' . $className . ' does not exist.', 1391295613);
        }

        if (!in_array(AbstractFileCollection::class, class_parents($className) ?: [], true)) {
            throw new \InvalidArgumentException('FileCollection ' . $className . ' needs to extend the AbstractFileCollection.', 1391295633);
        }

        if (isset($this->types[$type])) {
            // Return immediately without changing configuration
            if ($this->types[$type] === $className) {
                return true;
            }
            if (!$override) {
                throw new \InvalidArgumentException('FileCollections ' . $type . ' is already registered.', 1391295643);
            }
        }

        $this->types[$type] = $className;
        return true;
    }

    /**
     * Returns a class name for a given type
     *
     * @param string $type
     * @return string The class name
     * @throws \InvalidArgumentException
     */
    public function getFileCollectionClass($type)
    {
        if (!isset($this->types[$type])) {
            throw new \InvalidArgumentException('Desired FileCollection type "' . $type . '" is not in the list of available FileCollections.', 1391295644);
        }
        return $this->types[$type];
    }

    /**
     * Checks if the given FileCollection type exists
     *
     * @param string $type Type of the FileCollection
     * @return bool TRUE if the FileCollection exists, FALSE otherwise
     */
    public function fileCollectionTypeExists($type)
    {
        return isset($this->types[$type]);
    }
}
