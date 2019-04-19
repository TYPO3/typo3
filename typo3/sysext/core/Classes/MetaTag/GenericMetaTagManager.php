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
     *
     * @param string $property
     * @param string $content
     * @param array $subProperties
     * @param bool $replace
     * @param string $type
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
            'subProperties' => $subProperties
        ];
    }

    /**
     * Get the data of a specific property
     *
     * @param string $property
     * @param string $type
     * @return array
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
     * @return array
     */
    public function getAllHandledProperties(): array
    {
        return [];
    }

    /**
     * Render all registered properties of this manager
     *
     * @return string
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
     *
     * @param string $property
     * @return string
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
     *
     * @param string $property
     * @param string $type
     */
    public function removeProperty(string $property, string $type = '')
    {
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

    /**
     * @param string $property
     * @return bool
     */
    public function canHandleProperty(string $property): bool
    {
        return true;
    }
}
