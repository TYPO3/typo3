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

namespace TYPO3\CMS\Form\Tests\Unit\Storage;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Storage\JsonObjectKeyOrderPreserver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class JsonObjectKeyOrderPreserverTest extends UnitTestCase
{
    #[Test]
    public function protectWrapsOptionsMapWithOrderAndMarker(): void
    {
        $formDefinition = [
            'renderables' => [
                [
                    'identifier' => 'select1',
                    'type' => 'SingleSelect',
                    'properties' => [
                        'options' => ['3' => '3', '5' => '5', '4' => '4'],
                    ],
                ],
            ],
        ];

        $protected = (new JsonObjectKeyOrderPreserver())->protect($formDefinition);
        $wrapper = $protected['renderables'][0]['properties']['options'];

        self::assertTrue($wrapper['__jsonKeyOrderProtected']);
        self::assertSame([3, 5, 4], $wrapper['order']);
        self::assertSame(['3' => '3', '5' => '5', '4' => '4'], $wrapper['values']);
    }

    #[Test]
    public function restoreRecoversOriginalOrderEvenWhenTheUnderlyingMapWasReordered(): void
    {
        $formDefinition = [
            'renderables' => [
                [
                    'properties' => [
                        'options' => ['3' => '3', '5' => '5', '4' => '4'],
                    ],
                ],
            ],
        ];

        $subject = new JsonObjectKeyOrderPreserver();
        $protected = $subject->protect($formDefinition);

        // Simulate what MySQL's native JSON column type may do to a JSON
        // *object* on a write/read round trip: renormalize member order.
        // The "order" key is a JSON *array* and is not subject to this.
        $roundTripped = json_decode(json_encode($protected), true);
        ksort($roundTripped['renderables'][0]['properties']['options']['values']);

        $restored = $subject->restore($roundTripped);

        self::assertSame(
            ['3' => '3', '5' => '5', '4' => '4'],
            $restored['renderables'][0]['properties']['options']
        );
    }

    #[Test]
    public function restoreStripsTheProtectionMarkerCompletely(): void
    {
        $formDefinition = ['properties' => ['options' => ['a' => 'A', 'b' => 'B']]];

        $subject = new JsonObjectKeyOrderPreserver();
        $restored = $subject->restore($subject->protect($formDefinition));

        self::assertArrayNotHasKey('__jsonKeyOrderProtected', $restored['properties']['options']);
        self::assertSame(['a' => 'A', 'b' => 'B'], $restored['properties']['options']);
    }

    #[Test]
    public function protectLeavesEmptyOptionsArrayUntouched(): void
    {
        $formDefinition = ['properties' => ['options' => []]];

        self::assertSame($formDefinition, (new JsonObjectKeyOrderPreserver())->protect($formDefinition));
    }

    #[Test]
    public function restoreIsANoOpForDataWithoutTheProtectionMarker(): void
    {
        // Records saved before this workaround existed have a plain
        // "options" map with no wrapper - restore() must not touch them.
        $legacy = ['properties' => ['options' => ['a' => 'Label A', 'b' => 'Label B']]];

        self::assertSame($legacy, (new JsonObjectKeyOrderPreserver())->restore($legacy));
    }

    #[Test]
    public function nonNumericOptionKeysSurviveRoundTrip(): void
    {
        $formDefinition = [
            'properties' => [
                'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'],
            ],
        ];

        $subject = new JsonObjectKeyOrderPreserver();
        $roundTripped = json_decode(json_encode($subject->protect($formDefinition)), true);
        ksort($roundTripped['properties']['options']['values']);

        $restored = $subject->restore($roundTripped);

        self::assertSame(
            ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'],
            $restored['properties']['options']
        );
    }

    #[Test]
    public function nestedRenderablesAndVariantsAreBothProtected(): void
    {
        // The workaround intentionally matches on the "options" key name
        // rather than resolving each renderable's prototype configuration,
        // so it also protects option order inside "variants" overrides
        // for free, without needing prototype/DI wiring in the persistence
        // layer.
        $formDefinition = [
            'renderables' => [
                [
                    'renderables' => [
                        ['properties' => ['options' => ['2' => '2', '1' => '1']]],
                    ],
                ],
            ],
            'variants' => [
                ['overrideOptions' => ['options' => ['9' => '9', '8' => '8']]],
            ],
        ];

        $subject = new JsonObjectKeyOrderPreserver();
        $roundTripped = json_decode(json_encode($subject->protect($formDefinition)), true);
        ksort($roundTripped['renderables'][0]['renderables'][0]['properties']['options']['values']);
        ksort($roundTripped['variants'][0]['overrideOptions']['options']['values']);

        $restored = $subject->restore($roundTripped);

        self::assertSame(['2' => '2', '1' => '1'], $restored['renderables'][0]['renderables'][0]['properties']['options']);
        self::assertSame(['9' => '9', '8' => '8'], $restored['variants'][0]['overrideOptions']['options']);
    }
}
