<?php
namespace TYPO3\CMS\Extbase\Property;

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
 * This builder creates the default configuration for Property Mapping, if no configuration has been passed to the Property Mapper.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class PropertyMappingConfigurationBuilder implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Builds the default property mapping configuration.
     *
     * @param string $type the implementation class name of the PropertyMappingConfiguration to instanciate; must be a subclass of \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration
     * @return \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration
     */
    public function build($type = \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration::class)
    {
        /** @var \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $configuration */
        $configuration = new $type();

        $configuration->setTypeConverterOptions(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, [
            \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
        ]);
        $configuration->allowAllProperties();

        return $configuration;
    }
}
