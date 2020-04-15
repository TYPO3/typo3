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

namespace TYPO3\CMS\Extbase\Mvc\Controller;

use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

/**
 * The default property mapping configuration is available
 * inside the Argument-object.
 */
class MvcPropertyMappingConfiguration extends PropertyMappingConfiguration
{
    /**
     * Allow creation of a certain sub property
     *
     * @param string $propertyPath
     */
    public function allowCreationForSubProperty($propertyPath)
    {
        $this->forProperty($propertyPath)->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);
    }

    /**
     * Allow modification for a given property path
     *
     * @param string $propertyPath
     */
    public function allowModificationForSubProperty($propertyPath)
    {
        $this->forProperty($propertyPath)->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
    }

    /**
     * Set the target type for a certain property. Especially useful
     * if there is an object which has a nested object which is abstract,
     * and you want to instantiate a concrete object instead.
     *
     * @param string $propertyPath
     * @param string $targetType
     */
    public function setTargetTypeForSubProperty($propertyPath, $targetType)
    {
        $this->forProperty($propertyPath)->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, $targetType);
    }
}
