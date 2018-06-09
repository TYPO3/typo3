<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\MetaTag;

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

interface MetaTagManagerInterface
{
    /**
     * Add a property
     *
     * @param string $property
     * @param string $content
     * @param array $subProperties
     * @param bool $replace
     * @param string $type
     */
    public function addProperty(string $property, string $content, array $subProperties = [], bool $replace = false, string $type = '');

    /**
     * Get a specific property that is set before
     *
     * @param string $property
     * @param string $type
     * @return array
     */
    public function getProperty(string $property, string $type = ''): array;

    /**
     * Check if this manager can handle the given property
     *
     * @param string $property
     * @return bool
     */
    public function canHandleProperty(string $property): bool;

    /**
     * Returns an array with all properties that can be handled by the manager
     *
     * @return array
     */
    public function getAllHandledProperties(): array;

    /**
     * Render all registered properties of this manager
     *
     * @return string
     */
    public function renderAllProperties(): string;

    /**
     * Render a meta tag for a specific property
     *
     * @param string $property
     * @return string
     */
    public function renderProperty(string $property): string;

    /**
     * Remove one property from the MetaTagManager
     * If there are multiple occurrences of a property, they all will be removed
     *
     * @param string $property
     * @param string $type
     */
    public function removeProperty(string $property, string $type = '');

    /**
     * Unset all properties of this MetaTagManager
     */
    public function removeAllProperties();
}
