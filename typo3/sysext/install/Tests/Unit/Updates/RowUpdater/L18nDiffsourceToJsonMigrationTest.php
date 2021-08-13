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

namespace TYPO3\CMS\Install\Tests\Unit\Updates\RowUpdater;

use TYPO3\CMS\Install\Updates\RowUpdater\L18nDiffsourceToJsonMigration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class L18nDiffsourceToJsonMigrationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function hasPotentialUpdateForTableReturnsFalseIfTableIsNotLocalizable(): void
    {
        $GLOBALS['TCA']['testTable'] = [];
        self::assertFalse((new L18nDiffsourceToJsonMigration())->hasPotentialUpdateForTable('testTable'));
    }

    /**
     * @test
     */
    public function hasPotentialUpdateForTableReturnsTrueIfTableIsLocalizable(): void
    {
        $GLOBALS['TCA']['testTable'] = [
            'ctrl' => [
                'languageField' => 'sys_language_uid',
                'transOrigPointerField' => 'l10n_parent',
                'transOrigDiffSourceField' =>'l10n_diffsource',
            ]
        ];
        self::assertTrue((new L18nDiffsourceToJsonMigration())->hasPotentialUpdateForTable('testTable'));
    }

    /**
     * @test
     */
    public function updateTableRowDoesNothingIfFieldIsNotSet(): void
    {
        $GLOBALS['TCA']['testTable'] = [
            'ctrl' => [
                'transOrigDiffSourceField' =>'l10n_diffsource',
            ]
        ];
        $row = [];
        self::assertSame($row, (new L18nDiffsourceToJsonMigration())->updateTableRow('testTable', $row));
    }

    public function updateTableRowUpdatesFieldDataProvider(): array
    {
        $object = new \stdClass();
        $json = json_encode(['foo' => 'bar']);
        $serializedArray = serialize(['foo' => 'bar']);
        $serializedObject = serialize(new \stdClass());
        return [
            'null is kept' => [
                null,
                null
            ],
            'false is kept' => [
                false,
                false
            ],
            'true is kept' => [
                true,
                true
            ],
            'string is kept' => [
                'foo',
                'foo'
            ],
            'array is kept' => [
                ['foo' => 'bar'],
                ['foo' => 'bar'],
            ],
            'object is kept' => [
                $object,
                $object
            ],
            'json is kept' => [
                $json,
                $json
            ],
            'serialized object is removed' => [
                $serializedObject,
                null
            ],
            'serialized array is migrated' => [
                $serializedArray,
                $json
            ],
        ];
    }

    /**
     * @test
     * @dataProvider updateTableRowUpdatesFieldDataProvider
     *
     * @param mixed $input
     * @param mixed $expected
     */
    public function updateTableRowUpdatesField($input, $expected): void
    {
        $GLOBALS['TCA']['testTable'] = [
            'ctrl' => [
                'transOrigDiffSourceField' =>'l10n_diffsource',
            ]
        ];
        $row = ['l10n_diffsource' => $input];
        $expected = ['l10n_diffsource' => $expected];
        self::assertSame($expected, (new L18nDiffsourceToJsonMigration())->updateTableRow('testTable', $row));
    }
}
