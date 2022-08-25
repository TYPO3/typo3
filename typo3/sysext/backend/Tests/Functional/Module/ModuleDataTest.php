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

namespace TYPO3\CMS\Backend\Tests\Functional\Module;

use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ModuleDataTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function defaultValuesAreOverwritten(): void
    {
        $defaultValues = [
            'property' => 'defaultValue',
            'anotherProperty' => 'anotherDefaultValue',
        ];

        $moduleData = new ModuleData(
            'my_module',
            [
                'property' => 'newValue',
            ],
            $defaultValues
        );

        $expected = $defaultValues;
        $expected['property'] = 'newValue';

        self::assertEquals($expected, $moduleData->toArray());
        self::assertTrue($moduleData->has('property'));
        self::assertEquals('newValue', $moduleData->get('property'));
        self::assertEquals('my_module', $moduleData->getModuleIdentifier());

        $moduleData->set('anotherProperty', 'anotherPropertyValue');
        self::assertEquals('anotherPropertyValue', $moduleData->toArray()['anotherProperty']);
    }

    /**
     * @test
     */
    public function moduleDataAreCreatedFromModule(): void
    {
        $defaultValues = [
            'property' => 'defaultValue',
            'anoterProperty' => 'anotherDefaultValue',
        ];

        $module = $this->get(ModuleFactory::class)->createModule(
            'my_module',
            [
                'packageName' => 'typo3/cms-testing',
                'path' => '/module/my/module',
                'moduleData' => $defaultValues,
            ]
        );

        $moduleData = ModuleData::createFromModule($module, ['property' => 'newValue']);

        $expected = $defaultValues;
        $expected['property'] = 'newValue';

        self::assertEquals($expected, $moduleData->toArray());
        self::assertTrue($moduleData->has('property'));
        self::assertEquals('newValue', $moduleData->get('property'));
        self::assertEquals('my_module', $moduleData->getModuleIdentifier());

        $moduleData->set('anotherProperty', 'anotherPropertyValue');
        self::assertEquals('anotherPropertyValue', $moduleData->toArray()['anotherProperty']);
    }

    /**
     * @test
     */
    public function cleanModuleDataPropertyThrowsExceptionOnInvalidProperty(): void
    {
        $moduleData = new ModuleData(
            'my_module',
            [
                'property' => 'aValue',
            ],
            [
                'property' => 'defaultValue',
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1644600510);

        $moduleData->clean('invalidProperty', ['allowedValue']);
    }

    /**
     * @test
     */
    public function cleanModuleDataPropertyThrowsExceptionOnEmptyAllowedList(): void
    {
        $moduleData = new ModuleData(
            'my_module',
            [
                'property' => 'aValue',
            ],
            [
                'property' => 'defaultValue',
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1644600511);

        $moduleData->clean('property', []);
    }

    public function cleanModuleDataPropertyDataProvider(): \Generator
    {
        yield 'Nothing happens since the value is valid' => [
            ['aValue'],
            false,
            'aValue',
        ];
        yield 'Falls back to default value' => [
            ['defaultValue'],
            true,
            'defaultValue',
        ];
        yield 'Falls back to first allowed value' => [
            ['allowedValue'],
            true,
            'allowedValue',
        ];
    }

    /**
     * @test
     * @dataProvider cleanModuleDataPropertyDataProvider
     */
    public function cleanModuleDataProperty(array $allowedValues, bool $cleaned, string $cleanedValue): void
    {
        $moduleData = new ModuleData(
            'my_module',
            [
                'property' => 'aValue',
            ],
            [
                'property' => 'defaultValue',
            ]
        );

        self::assertEquals($cleaned, $moduleData->clean('property', $allowedValues));
        self::assertEquals($cleanedValue, $moduleData->get('property'));
    }

    public function cleanUpModuleDataPropertiesDataProvider(): \Generator
    {
        yield 'All valid' => [
            [
                'property' => ['aValue'],
            ],
            false,
            false,
            [
                'property' => 'aValue',
                'anotherProperty' => 1,
            ],
        ];
        yield 'All valid - use keys' => [
            [
                'property' => ['aValue' => 'LLL:'],
            ],
            true,
            false,
            [
                'property' => 'aValue',
                'anotherProperty' => 1,
            ],
        ];
        yield 'Cleanup by default value and first allowed value' => [
            [
                'property' => ['defaultValue'],
                'anotherProperty' => [2],
            ],
            false,
            true,
            [
                'property' => 'defaultValue',
                'anotherProperty' => 2,
            ],
        ];
        yield 'Cleanup by default value and first allowed value - use keys' => [
            [
                'property' => ['defaultValue' => 'LLL:'],
                'anotherProperty' => [2 => 'LLL:'],
            ],
            true,
            true,
            [
                'property' => 'defaultValue',
                'anotherProperty' => 2,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider cleanUpModuleDataPropertiesDataProvider
     */
    public function cleanUpModuleDataProperties(
        array $allowedData,
        bool $useKeys,
        bool $cleaned,
        array $cleanedValues
    ): void {
        $moduleData = new ModuleData(
            'my_module',
            [
                'property' => 'aValue',
                'anotherProperty' => 1,
            ],
            [
                'property' => 'defaultValue',
            ]
        );

        self::assertEquals($cleaned, $moduleData->cleanUp($allowedData, $useKeys));
        self::assertEquals($cleanedValues, $moduleData->toArray());
    }
}
