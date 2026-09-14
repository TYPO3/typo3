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

namespace TYPO3\CMS\Reactions\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Reactions\Validation\CreateRecordReactionField;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CreateRecordReactionFieldTest extends UnitTestCase
{
    public static function isWritableByDataProvider(): array
    {
        return [
            'a field that is neither excluded nor admin only is written by anyone' => [
                'isAdmin' => false,
                'hasPermission' => false,
                'displayConditions' => '',
                'excluded' => false,
                'expected' => true,
            ],
            'an admin writes an excluded field' => [
                'isAdmin' => true,
                'hasPermission' => true,
                'displayConditions' => '',
                'excluded' => true,
                'expected' => true,
            ],
            'an admin writes a field shown to admins only' => [
                'isAdmin' => true,
                'hasPermission' => true,
                'displayConditions' => 'HIDE_FOR_NON_ADMINS',
                'excluded' => false,
                'expected' => true,
            ],
            'a user granted the permission writes an excluded field' => [
                'isAdmin' => false,
                'hasPermission' => true,
                'displayConditions' => '',
                'excluded' => true,
                'expected' => true,
            ],
            'a user not granted the permission does not write an excluded field' => [
                'isAdmin' => false,
                'hasPermission' => false,
                'displayConditions' => '',
                'excluded' => true,
                'expected' => false,
            ],
            'a user who is no admin does not write a field shown to admins only' => [
                'isAdmin' => false,
                'hasPermission' => true,
                'displayConditions' => 'HIDE_FOR_NON_ADMINS',
                'excluded' => false,
                'expected' => false,
            ],
        ];
    }

    public static function isMappableDataProvider(): array
    {
        return [
            'a column of a supported type is mappable' => [
                'configuration' => ['type' => 'color'],
                'expected' => true,
            ],
            'a column the table declares read only is not' => [
                // Its element renders the input disabled, so the row would offer nothing to
                // pick a value with.
                'configuration' => ['type' => 'color', 'readOnly' => true],
                'expected' => false,
            ],
            'a column of a type the map cannot carry is not' => [
                'configuration' => ['type' => 'flex'],
                'expected' => false,
            ],
            'a select submitting a list is not' => [
                'configuration' => ['type' => 'select', 'renderType' => 'selectMultipleSideBySide'],
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('isMappableDataProvider')]
    #[Test]
    public function isMappableDecidesWhatTheFieldMapOffers(array $configuration, bool $expected): void
    {
        $field = self::createStub(FieldTypeInterface::class);
        $field->method('getType')->willReturn((string)$configuration['type']);
        $field->method('getConfiguration')->willReturn($configuration);

        self::assertSame($expected, CreateRecordReactionField::isMappable($field));
    }

    #[DataProvider('isWritableByDataProvider')]
    #[Test]
    public function isWritableByDecidesItTheWayDataHandlerDoes(
        bool $isAdmin,
        bool $hasPermission,
        array|string $displayConditions,
        bool $excluded,
        bool $expected
    ): void {
        $field = self::createStub(FieldTypeInterface::class);
        $field->method('getDisplayConditions')->willReturn($displayConditions);
        $field->method('supportsAccessControl')->willReturn($excluded);
        $backendUser = self::createStub(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn($isAdmin);
        // The permission is asked for by the name DataHandler asks for it by, and for the
        // field of the table rather than for the field alone.
        $backendUser->method('check')->willReturnCallback(
            static function (string $type, string $value) use ($hasPermission): bool {
                self::assertSame('non_exclude_fields', $type);
                self::assertSame('pages:hidden', $value);
                return $hasPermission;
            }
        );

        self::assertSame(
            $expected,
            CreateRecordReactionField::isWritableBy($field, 'pages', 'hidden', $backendUser)
        );
    }
}
