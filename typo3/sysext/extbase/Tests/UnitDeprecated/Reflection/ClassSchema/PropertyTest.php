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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection\ClassSchema;

use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection\Fixture\DummyClassWithAllTypesOfProperties;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection\PropertyTest
 */
class PropertyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function classSchemaDetectsInjectProperty(): void
    {
        $property = (new ClassSchema(DummyClassWithAllTypesOfProperties::class))
            ->getProperty('propertyWithInjectAnnotation');

        self::assertTrue($property->isInjectProperty());
    }
}
