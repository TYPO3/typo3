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

namespace TYPO3\CMS\Extbase\Reflection\ClassSchema;

/**
 * Class TYPO3\CMS\Extbase\Reflection\ClassSchema\Property
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Property
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $definition;

    /**
     * @var PropertyCharacteristics
     */
    private $characteristics;

    /**
     * @param string $name
     * @param array $definition
     */
    public function __construct(string $name, array $definition)
    {
        $this->name = $name;
        $this->characteristics = new PropertyCharacteristics($definition['propertyCharacteristicsBit']);
        unset($definition['propertyCharacteristicsBit']);

        $defaults = [
            'c' => null, // cascade
            'd' => null, // defaultValue
            't' => null, // type
            'e' => null, // elementType
            'v' => [], // validators
        ];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($definition[$key])) {
                $definition[$key] = $defaultValue;
            }
        }

        $this->definition = $definition;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the type (string, integer, ...) set by the @var doc comment
     *
     * Returns null if type could not be evaluated
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->definition['t'];
    }

    /**
     * If the property is a collection of one of the types defined in
     * \TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::$collectionTypes,
     * the element type is evaluated and represents the type of collection
     * items inside the collection.
     *
     * Returns null if the property is not a collection and therefore no element type is defined.
     *
     * @return string|null
     */
    public function getElementType(): ?string
    {
        return $this->definition['e'];
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::VISIBILITY_PUBLIC);
    }

    /**
     * @return bool
     */
    public function isProtected(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::VISIBILITY_PROTECTED);
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::VISIBILITY_PRIVATE);
    }

    /**
     * @return bool
     */
    public function isLazy(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::ANNOTATED_LAZY);
    }

    /**
     * @return bool
     */
    public function isTransient(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::ANNOTATED_TRANSIENT);
    }

    /**
     * @return bool
     */
    public function isInjectProperty(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::ANNOTATED_INJECT);
    }

    /**
     * @return array
     */
    public function getValidators(): array
    {
        return $this->definition['v'];
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->definition['d'];
    }

    /**
     * @return string|null
     */
    public function getCascadeValue(): ?string
    {
        return $this->definition['c'];
    }
}
