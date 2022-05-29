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

namespace TYPO3\CMS\Extbase\Property;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

/**
 * This builder creates the default configuration for Property Mapping, if no configuration has been passed to the Property Mapper.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class PropertyMappingConfigurationBuilder implements SingletonInterface
{
    /**
     * Builds the default property mapping configuration.
     *
     * @param class-string<PropertyMappingConfiguration> $type the implementation class to instantiate
     * @return PropertyMappingConfiguration
     */
    public function build($type = PropertyMappingConfiguration::class)
    {
        $configuration = new $type();

        $configuration->setTypeConverterOptions(PersistentObjectConverter::class, [
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true,
        ]);
        $configuration->allowAllProperties();

        return $configuration;
    }
}
