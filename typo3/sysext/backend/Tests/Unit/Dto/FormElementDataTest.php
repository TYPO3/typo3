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

namespace TYPO3\CMS\Backend\Tests\Unit\Dto;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Dto\FormElementData;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormElementDataTest extends UnitTestCase
{
    public static function hasDeleteAccessDataProvider(): \Generator
    {
        yield 'pages with page delete permission' => [
            'table' => 'pages',
            'userPermissionOnPage' => Permission::PAGE_DELETE,
            'expected' => true,
        ];
        yield 'pages with content edit permission only' => [
            'table' => 'pages',
            'userPermissionOnPage' => Permission::CONTENT_EDIT,
            'expected' => false,
        ];
        yield 'tt_content with content edit permission' => [
            'table' => 'tt_content',
            'userPermissionOnPage' => Permission::CONTENT_EDIT,
            'expected' => true,
        ];
        yield 'tt_content with page delete permission only' => [
            'table' => 'tt_content',
            'userPermissionOnPage' => Permission::PAGE_DELETE,
            'expected' => false,
        ];
        yield 'tt_content without any permission' => [
            'table' => 'tt_content',
            'userPermissionOnPage' => Permission::NOTHING,
            'expected' => false,
        ];
    }

    #[DataProvider('hasDeleteAccessDataProvider')]
    #[Test]
    public function hasDeleteAccessChecksPermissionDependingOnTable(string $table, int $userPermissionOnPage, bool $expected): void
    {
        $subject = new FormElementData(
            title: 'Some record',
            table: $table,
            uid: 42,
            pid: 1,
            record: [],
            viewId: 1,
            command: 'edit',
            userPermissionOnPage: $userPermissionOnPage,
        );
        self::assertSame($expected, $subject->hasDeleteAccess());
    }
}
