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

abstract class AbstractMetaTagManager implements MetaTagManagerInterface
{
    /**
     * The default attribute that defines the name of the property
     *
     * This creates tags like <meta name="" /> by default
     *
     * @var string
     */
    protected $defaultNameAttribute = 'name';

    /**
     * The default attribute that defines the content
     *
     * This creates tags like <meta content="" /> by default
     *
     * @var string
     */
    protected $defaultContentAttribute = 'content';

    /**
     * Set if by default it is possible to have multiple occurrences of properties of this manager
     *
     * @var bool
     */
    protected $defaultAllowMultipleOccurrences = false;

    /**
     * The separator to define subproperties like og:image:width
     *
     * @var string
     */
    protected $subPropertySeparator = ':';

    /**
     * Array of properties that can be handled by this manager
     *
     * Example:
     *
     * $handledProperties = [
     *       'og:title' => [],
     *       'og:image' => [
     *          'allowMultipleOccurrences' => true,
     *          'allowedSubProperties' => [
     *              'url',
     *              'secure_url',
     *              'type',
     *              'width',
     *              'height',
     *              'alt'
     *          ]
     *       ],
     *       'og:locale' => [
     *          'allowedSubProperties' => [
     *             'alternate' => [
     *                'allowMultipleOccurrences' => true
     *             ]
     *          ]
     *       ]
     *];
     *
     * @var array
     */
    protected $handledProperties = [];

    /**
     * Array of properties that are set by the manager
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Add a property
     *
     * @param string $property Name of the property
     * @param string $content Content of the property
     * @param array $subProperties Optional subproperties
     * @param bool $replace Replace the currently set value
     * @param string $type Optional type of property (name, property, http-equiv)
     *
     * @throws \UnexpectedValueException
     */
    public function addProperty(string $property, string $content, array $subProperties = [], bool $replace = false, string $type = '')
    {
        $property = strtolower($property);

        if (isset($this->handledProperties[$property])) {
            $subPropertiesArray = [];
            foreach ($subProperties as $subPropertyKey => $subPropertyValue) {
                if (isset($this->handledProperties[$property]['allowedSubProperties'][$subPropertyKey])) {
                    $subPropertiesArray[$subPropertyKey] = is_array($subPropertyValue) ? $subPropertyValue : [$subPropertyValue];
                }
            }
            if (!isset($this->properties[$property]) || empty($this->properties[$property])) {
                $this->properties[$property][] = ['content' => $content, 'subProperties' => $subPropertiesArray];
            } else {
                if ($replace === true) {
                    $this->removeProperty($property, $type);
                    $this->properties[$property][] = ['content' => $content, 'subProperties' => $subPropertiesArray];
                    return;
                }

                if (isset($this->handledProperties[$property]['allowMultipleOccurrences']) &&
                    (bool)$this->handledProperties[$property]['allowMultipleOccurrences']
                ) {
                    $this->properties[$property][] = ['content' => $content, 'subProperties' => $subPropertiesArray];
                }
            }
        } else {
            // Check if there is an allowed subproperty that can handle the given property
            foreach ($this->handledProperties as $handledProperty => $handledPropertyConfig) {
                if (!isset($handledPropertyConfig['allowedSubProperties'])) {
                    continue;
                }
                foreach ((array)$handledPropertyConfig['allowedSubProperties'] as $allowedSubProperty => $allowedSubPropertyConfig) {
                    $propertyKey = is_array($allowedSubPropertyConfig) ? $allowedSubProperty : $allowedSubPropertyConfig;

                    if ($property !== $handledProperty . $this->subPropertySeparator . $propertyKey ||
                        !isset($this->properties[$handledProperty])
                    ) {
                        continue;
                    }

                    $propertyArrayKeys = array_keys($this->properties[$handledProperty]);
                    $lastIndex = end($propertyArrayKeys);

                    if (!isset($this->properties[$handledProperty][$lastIndex]['subProperties'][$propertyKey])) {
                        $this->properties[$handledProperty][$lastIndex]['subProperties'][$propertyKey][] = $content;
                    } else {
                        if ($replace === true) {
                            unset($this->properties[$handledProperty][$lastIndex]['subProperties'][$propertyKey]);
                            $this->properties[$handledProperty][$lastIndex]['subProperties'][$propertyKey][] = $content;
                            return;
                        }

                        if (is_array($allowedSubPropertyConfig) &&
                            isset($allowedSubPropertyConfig['allowMultipleOccurrences']) &&
                            (bool)$allowedSubPropertyConfig['allowMultipleOccurrences']
                        ) {
                            $this->properties[$handledProperty][$lastIndex]['subProperties'][$propertyKey][] = $content;
                        }
                    }

                    return;
                }
            }

            throw new \UnexpectedValueException(
                sprintf('This MetaTagManager can\'t handle property "%s"', $property),
                1524209729
            );
        }
    }

    /**
     * Returns an array with all properties that can be handled by this manager
     *
     * @return array
     */
    public function getAllHandledProperties(): array
    {
        return $this->handledProperties;
    }

    /**
     * Get a specific property that is set before
     *
     * @param string $property Name of the property
     * @param string $type Optional type of property (name, property, http-equiv)
     * @return array
     */
    public function getProperty(string $property, string $type = ''): array
    {
        $property = strtolower($property);

        if (isset($this->properties[$property])) {
            return $this->properties[$property];
        }

        return [];
    }

    /**
     * Render a meta tag for a specific property
     *
     * @param string $property Name of the property
     * @return string
     */
    public function renderProperty(string $property): string
    {
        $property = strtolower($property);
        $metaTags = [];

        $nameAttribute = $this->defaultNameAttribute;
        if (isset($this->handledProperties[$property]['nameAttribute'])
            && !empty((string)$this->handledProperties[$property]['nameAttribute'])) {
            $nameAttribute = (string)$this->handledProperties[$property]['nameAttribute'];
        }

        $contentAttribute = $this->defaultContentAttribute;
        if (isset($this->handledProperties[$property]['contentAttribute'])
            && !empty((string)$this->handledProperties[$property]['contentAttribute'])) {
            $contentAttribute = (string)$this->handledProperties[$property]['contentAttribute'];
        }

        if ($nameAttribute && $contentAttribute) {
            foreach ($this->getProperty($property) as $propertyItem) {
                $metaTags[] = '<meta ' .
                    htmlspecialchars($nameAttribute) . '="' . htmlspecialchars($property) . '" ' .
                    htmlspecialchars($contentAttribute) . '="' . htmlspecialchars($propertyItem['content']) . '" />';

                if (!count($propertyItem['subProperties'])) {
                    continue;
                }
                foreach ($propertyItem['subProperties'] as $subProperty => $subPropertyItems) {
                    foreach ($subPropertyItems as $subPropertyItem) {
                        $metaTags[] = '<meta ' .
                            htmlspecialchars($nameAttribute) . '="' . htmlspecialchars($property . $this->subPropertySeparator . $subProperty) . '" ' .
                            htmlspecialchars($contentAttribute) . '="' . htmlspecialchars((string)$subPropertyItem) . '" />';
                    }
                }
            }
        }

        return implode(PHP_EOL, $metaTags);
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
     * Remove one property from the MetaTagManager
     * If there are multiple occurrences of a property, they all will be removed
     *
     * @param string $property
     * @param string $type
     */
    public function removeProperty(string $property, string $type = '')
    {
        $property = strtolower($property);

        unset($this->properties[$property]);
    }

    /**
     * Unset all properties of this MetaTagManager
     */
    public function removeAllProperties()
    {
        $this->properties = [];
    }

    /**
     * Check if this manager can handle the given property
     *
     * @param string $property Name of property to check (eg. og:title)
     * @return bool
     */
    public function canHandleProperty(string $property): bool
    {
        if (isset($this->handledProperties[$property])) {
            return true;
        }

        foreach ($this->handledProperties as $handledProperty => $handledPropertyConfig) {
            foreach ((array)$handledPropertyConfig['allowedSubProperties'] as $allowedSubProperty => $allowedSubPropertyConfig) {
                $propertyKey = is_array($allowedSubPropertyConfig) ? $allowedSubProperty : $allowedSubPropertyConfig;
                if ($property === $handledProperty . $this->subPropertySeparator . $propertyKey) {
                    return true;
                }
            }
        }

        return false;
    }
}
