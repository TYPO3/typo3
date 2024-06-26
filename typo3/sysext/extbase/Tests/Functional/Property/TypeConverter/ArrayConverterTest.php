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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ArrayConverterTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function convertToArray(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        self::assertSame([], $propertyMapper->convert([], 'array'));
        self::assertSame([], $propertyMapper->convert('', 'array'));
    }

    #[Test]
    public function delemiterConfigurationIsRespectedOnStringToArrayConversion(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverterOption(
            ArrayConverter::class,
            ArrayConverter::CONFIGURATION_DELIMITER,
            ','
        );
        self::assertSame(
            ['foo', 'bar', 'baz'],
            $propertyMapper->convert('foo,bar,baz', 'array', $propertyMappingConfiguration)
        );
    }

    #[Test]
    public function removeEmptyValuesConfigurationIsRespectedOnStringToArrayConversion(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverterOption(
            ArrayConverter::class,
            ArrayConverter::CONFIGURATION_DELIMITER,
            ','
        );
        $propertyMappingConfiguration->setTypeConverterOption(
            ArrayConverter::class,
            ArrayConverter::CONFIGURATION_REMOVE_EMPTY_VALUES,
            true
        );
        self::assertSame(
            ['foo', 'bar', 'baz'],
            $propertyMapper->convert('foo,bar,baz,,,', 'array', $propertyMappingConfiguration)
        );
    }

    #[Test]
    public function limitConfigurationIsRespectedOnStringToArrayConversion(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverterOption(
            ArrayConverter::class,
            ArrayConverter::CONFIGURATION_DELIMITER,
            ','
        );
        $propertyMappingConfiguration->setTypeConverterOption(
            ArrayConverter::class,
            ArrayConverter::CONFIGURATION_LIMIT,
            2
        );
        self::assertSame(
            ['foo', 'bar,baz'],
            $propertyMapper->convert('foo,bar,baz', 'array', $propertyMappingConfiguration)
        );
    }
}
