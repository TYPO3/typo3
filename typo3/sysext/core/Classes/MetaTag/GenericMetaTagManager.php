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

namespace TYPO3\CMS\Core\MetaTag;

/**
 * Handles typical meta tags (non-grouped). Use AbstractMetaTagManager
 * to create you own MetaTags, this class is final by design
 */
final class GenericMetaTagManager implements MetaTagManagerInterface
{
    /**
     * The separator to define subproperties like og:image:width
     *
     * @var string
     */
    protected $subPropertySeparator = ':';

    /**
     * Array of properties that are set by the manager
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Add a property (including subProperties)
     */
    public function addProperty(string $property, string $content, array $subProperties = [], bool $replace = false, string $type = 'name')
    {
        $property = strtolower($property);
        $type = strtolower($type) ?: 'name';

        if ($replace) {
            $this->removeProperty($property, $type);
        }

        $this->properties[$property][$type][] = [
            'content' => $content,
            'subProperties' => $subProperties,
        ];
    }

    /**
     * Get the data of a specific property
     */
    public function getProperty(string $property, string $type = 'name'): array
    {
        $property = strtolower($property);
        $type = strtolower($type) ?: 'name';

        if (!empty($this->properties[$property][$type])) {
            return $this->properties[$property][$type];
        }
        return [];
    }

    /**
     * Returns an array with all properties that can be handled by this manager
     */
    public function getAllHandledProperties(): array
    {
        return [];
    }

    /**
     * Render all registered properties of this manager
     */
    public function renderAllProperties(): string
    {
        $metatags = [];
        foreach (array_keys($this->properties) as $property) {
            $metatags[] = $this->renderProperty($property);
        }

        return implode(PHP_EOL, $metatags);
    }

    /**
     * Render a specific property including subproperties of that property
     */
    public function renderProperty(string $property): string
    {
        $property = strtolower($property);

        $metaTags = [];
        foreach ((array)$this->properties[$property] as $type => $propertyItems) {
            foreach ($propertyItems as $propertyItem) {
                $metaTags[] = '<meta ' .
                    htmlspecialchars($type) . '="' . htmlspecialchars($property) . '" ' .
                    'content="' . htmlspecialchars($propertyItem['content']) . '" />';

                if (!count($propertyItem['subProperties'])) {
                    continue;
                }
                foreach ($propertyItem['subProperties'] as $subProperty => $value) {
                    $metaTags[] = '<meta ' .
                        htmlspecialchars($type) . '="' . htmlspecialchars($property . $this->subPropertySeparator . $subProperty) . '" ' .
                        'content="' . htmlspecialchars((string)$value) . '" />';
                }
            }
        }

        return implode(PHP_EOL, $metaTags);
    }

    /**
     * Remove one property from the MetaTagManager
     * If there are multiple occurrences of a property, they all will be removed
     */
    public function removeProperty(string $property, string $type = '')
    {
        $property = strtolower($property);
        $type = strtolower($type);

        if (!empty($type)) {
            unset($this->properties[$property][$type]);
        } else {
            unset($this->properties[$property]);
        }
    }

    /**
     * Unset all properties
     */
    public function removeAllProperties()
    {
        $this->properties = [];
    }

    public function canHandleProperty(string $property): bool
    {
        return true;
    }

    public function getState(): array
    {
        return [
            'properties' => $this->properties,
        ];
    }

    public function updateState(array $state): void
    {
        $this->properties = $state['properties'] ?? [];
    }
}
