<?php
namespace TYPO3\CMS\Lang\Service;

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
 * Registry service
 */
class RegistryService
{
    /**
     * @var \TYPO3\CMS\Core\Registry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $namespaceIdentifier = 'TYPO3\\CMS\\Lang';

    /**
     * @param \TYPO3\CMS\Core\Registry $registry
     */
    public function injectRegistry(\TYPO3\CMS\Core\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Set namespace
     *
     * @param string $namespace The namespace
     * @return void
     */
    public function setNamespace($namespace)
    {
        $this->namespaceIdentifier = $namespace;
    }

    /**
     * Get namespace
     *
     * @return string The namespace
     */
    public function getNamespace()
    {
        return $this->namespaceIdentifier;
    }

    /**
     * Check for existing registry entry
     *
     * @param string $name Registry entry name
     * @param string $namespace Optional namespace
     * @return bool TRUE if exists
     */
    public function has($name, $namespace = null)
    {
        $namespace = (is_string($namespace) ? $namespace : $this->namespaceIdentifier);
        $value = $this->registry->get($namespace, $name, '__NOTFOUND__');
        return $value !== '__NOTFOUND__';
    }

    /**
     * Get registry entry
     *
     * @param string $name Registry entry name
     * @param string $namespace Optional namespace
     * @return mixed Registry content
     */
    public function get($name, $namespace = null)
    {
        $namespace = (is_string($namespace) ? $namespace : $this->namespaceIdentifier);
        return $this->registry->get($namespace, $name);
    }

    /**
     * Add / override registry entry
     *
     * @param string $name Registry entry name
     * @param mixed $value The value
     * @param string $namespace Optional namespace
     * @return void
     */
    public function set($name, $value, $namespace = null)
    {
        $namespace = (is_string($namespace) ? $namespace : $this->namespaceIdentifier);
        $this->registry->set($namespace, $name, $value);
    }

    /**
     * Remove registry entry
     *
     * @param string $name Registry entry name
     * @param string $namespace Optional namespace
     * @return void
     */
    public function remove($name, $namespace = null)
    {
        $namespace = (is_string($namespace) ? $namespace : $this->namespaceIdentifier);
        $this->registry->remove($namespace, $name);
    }
}
