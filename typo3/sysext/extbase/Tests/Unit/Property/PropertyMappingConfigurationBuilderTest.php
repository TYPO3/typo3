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

namespace TYPO3\CMS\Extbase\Tests\Unit\Property;

use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationBuilder;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PropertyMappingConfigurationBuilderTest extends UnitTestCase
{
    protected PropertyMappingConfigurationBuilder $propertyMappingConfigurationBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->propertyMappingConfigurationBuilder = new PropertyMappingConfigurationBuilder();
    }

    /**
     * @test
     */
    public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration(): void
    {
        $defaultConfiguration = $this->propertyMappingConfigurationBuilder->build();
        self::assertTrue($defaultConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertTrue($defaultConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));

        self::assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }
}
