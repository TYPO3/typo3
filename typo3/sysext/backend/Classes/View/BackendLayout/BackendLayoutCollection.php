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

namespace TYPO3\CMS\Backend\View\BackendLayout;

/**
 * Collection of backend layouts.
 */
class BackendLayoutCollection
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var array|BackendLayout[]
     */
    protected $backendLayouts = [];

    /**
     * @param string $identifier
     */
    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @throws \UnexpectedValueException
     */
    public function setIdentifier($identifier)
    {
        if (str_contains($identifier, '__')) {
            throw new \UnexpectedValueException(
                'Identifier "' . $identifier . '" must not contain "__"',
                1381597631
            );
        }

        $this->identifier = $identifier;
    }

    /**
     * Adds a backend layout to this collection.
     *
     * @param BackendLayout $backendLayout
     * @throws \LogicException
     */
    public function add(BackendLayout $backendLayout)
    {
        $identifier = $backendLayout->getIdentifier();

        if (str_contains($identifier, '__')) {
            throw new \UnexpectedValueException(
                'BackendLayout Identifier "' . $identifier . '" must not contain "__"',
                1381597628
            );
        }

        if (isset($this->backendLayouts[$identifier])) {
            throw new \LogicException(
                'Backend Layout ' . $identifier . ' is already defined',
                1381559376
            );
        }

        $this->backendLayouts[$identifier] = $backendLayout;
    }

    /**
     * Gets a backend layout by (regular) identifier.
     *
     * @param string $identifier
     * @return BackendLayout|null
     */
    public function get($identifier)
    {
        $backendLayout = null;

        if (isset($this->backendLayouts[$identifier])) {
            $backendLayout = $this->backendLayouts[$identifier];
        }

        return $backendLayout;
    }

    /**
     * Gets all backend layouts in this collection.
     *
     * @return array|BackendLayout[]
     */
    public function getAll()
    {
        return $this->backendLayouts;
    }
}
