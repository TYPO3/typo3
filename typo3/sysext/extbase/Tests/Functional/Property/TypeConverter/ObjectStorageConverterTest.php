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

namespace TYPO3\CMS\Extbase\Tests\Functional\Property\TypeConverter;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Tests\Functional\Property\Fixtures\Cat;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ObjectStorageConverterTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function convertReturnsObjectStorage()
    {
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();
        $propertyMapperConfiguration->forProperty('foo')->allowAllProperties();

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $objectStorage = $propertyMapper->convert(
            [
                'foo' => ['color' => 'black']
            ],
            ObjectStorage::class . '<' . Cat::class . '>',
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(ObjectStorage::class, $objectStorage);
        self::assertCount(1, $objectStorage);

        $cat = $objectStorage->current();
        self::assertInstanceOf(Cat::class, $cat);
        self::assertSame('black', $cat->getColor());
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedReturnsEmptyArrayIfSourceIsAString()
    {
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->forProperty('foo')->allowAllProperties();

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $objectStorage = $propertyMapper->convert(
            'foo',
            ObjectStorage::class . '<' . Cat::class . '>',
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(ObjectStorage::class, $objectStorage);
        self::assertCount(0, $objectStorage);
    }
}
