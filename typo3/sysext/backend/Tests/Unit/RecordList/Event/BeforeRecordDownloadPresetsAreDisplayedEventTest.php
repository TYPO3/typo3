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

namespace TYPO3\CMS\Backend\Tests\Unit\RecordList\Event;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\RecordList\DownloadPreset;
use TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadPresetsAreDisplayedEvent;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BeforeRecordDownloadPresetsAreDisplayedEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $arrayPresets = [
            0 => [
                'identifier' => 'myIdentifier',
                'label' => 'My preset name',
                'columns' => 'username, email',
            ],
            1 => [
                'label' => 'My second preset name',
                'columns' => 'email',
            ],
        ];

        $request = (new ServerRequest('https://example.com', 'POST'));
        $event = new BeforeRecordDownloadPresetsAreDisplayedEvent(
            'be_users',
            $arrayPresets,
            $request,
            4711
        );

        $expected = array_map(static fn($preset) => DownloadPreset::create($preset), $arrayPresets);

        self::assertEquals($expected, array_values($event->getPresets()), 'Unmodified input data');
        self::assertNotNull($event->getPresets()[md5($arrayPresets[1]['label'] . trim($arrayPresets[1]['columns']))] ?? null, 'Calculated identifier');
        self::assertEquals($request, $event->getRequest());
        self::assertEquals('be_users', $event->getDatabaseTable());
        self::assertEquals(4711, $event->getId());

        $modifiedPresets = $arrayPresets;
        $modifiedPresets[0] = [
            'identifier' => 'myIdentifier',
            'label' => 'My third preset name',
            'columns' => 'uid, email',
        ];

        $event->setPresets($modifiedPresets);

        $expected = array_map(static fn($preset) => DownloadPreset::create($preset), $modifiedPresets);
        self::assertEquals($expected, array_values($event->getPresets()));
    }

    #[DataProvider('presetValidationDataProvider')]
    #[Test]
    public function presetColumnsAreValidatedWhenUsingSetter(mixed $preset, array $expectation): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));
        $event = new BeforeRecordDownloadPresetsAreDisplayedEvent(
            'be_users',
            [],
            $request,
            4711
        );
        try {
            $preset = DownloadPreset::create($preset);
        } catch (\InvalidArgumentException) {
            $preset = [];
        }
        $event->setPresets([$preset]);
        self::assertEquals($expectation, array_values($event->getPresets()));
    }

    public static function presetValidationDataProvider(): array
    {
        return [
            'empty preset' => [
                [
                    'columns' => '',
                    'label' => '',
                ],
                [],
            ],

            'preset without columns' => [
                [
                    'columns' => '',
                    'label' => 'A label, will be irrelevant',
                ],
                [],
            ],

            'missing valid array keys' => [
                [
                    'invalidColumn' => '',
                    'invalidLabel' => '',
                ],
                [],
            ],

            'empty array input' => [
                [],
                [],
            ],

            'columns as empty array instead of string' => [
                [
                    'columns' => [],
                    'label' => 'a label',
                ],
                [],
            ],

            'columns as filled array instead of string' => [
                [
                    'columns' => ['a1', 'a2'],
                    'label' => 'a label',
                ],
                [
                    new DownloadPreset(
                        'a label',
                        explode(',', 'a1,a2'),
                    ),
                ],
            ],

            'csv column preset' => [
                [
                    'label' => 'Something',
                    'columns' => 'a,b,c,d',
                ],
                [
                    new DownloadPreset(
                        'Something',
                        explode(',', 'a,b,c,d'),
                    ),
                ],
            ],

            'auto identifier' => [
                [
                    'label' => 'Something',
                    'columns' => 'a,b,c,d',
                ],
                [
                    new DownloadPreset(
                        'Something',
                        explode(',', 'a,b,c,d'),
                        md5('Something' . 'abcd'),
                    ),
                ],
            ],

            'manual identifier' => [
                [
                    'label' => 'Something',
                    'columns' => 'a,b,c,d',
                    'identifier' => 'myIdentifier',
                ],
                [
                    new DownloadPreset(
                        'Something',
                        explode(',', 'a,b,c,d'),
                        'myIdentifier',
                    ),
                ],
            ],
        ];
    }
}
