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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Database\Schema\Types\EnumType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class EnumTypeTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!Type::hasType(EnumType::TYPE)) {
            Type::addType(EnumType::TYPE, EnumType::class);
        }
    }

    /**
     * @test
     */
    public function getNameReturnsTypeIdentifier(): void
    {
        $subject = Type::getType(EnumType::TYPE);
        self::assertSame(EnumType::TYPE, $subject->getName());
    }

    /**
     * @test
     */
    public function getSQLDeclaration(): void
    {
        $fieldDeclaration = [
            'unquotedValues' => ['aValue', 'anotherValue'],
        ];

        $databaseMock = $this->createMock(AbstractPlatform::class);
        $databaseMock->method('quoteStringLiteral')->willReturnCallback(
            static function (string $str): string {
                return "'" . $str . "'";
            }
        );

        $subject = Type::getType(EnumType::TYPE);
        self::assertSame(
            "ENUM('aValue', 'anotherValue')",
            $subject->getSQLDeclaration($fieldDeclaration, $databaseMock)
        );
    }
}
