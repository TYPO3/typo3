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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SetTypeTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!Type::hasType(SetType::TYPE)) {
            Type::addType(SetType::TYPE, SetType::class);
        }
    }

    #[Test]
    public function getNameReturnsTypeIdentifier(): void
    {
        $subject = Type::getType(SetType::TYPE);
        self::assertSame(SetType::TYPE, Type::getTypeRegistry()->lookupName($subject));
    }

    #[Test]
    public function getSQLDeclaration(): void
    {
        $fieldDeclaration = [
            'values' => ['aValue', 'anotherValue'],
        ];

        $databaseMock = $this->createMock(AbstractPlatform::class);
        $databaseMock->method('quoteStringLiteral')->willReturnCallback(
            static function (string $str): string {
                return "'" . $str . "'";
            }
        );

        $subject = Type::getType(SetType::TYPE);
        self::assertSame(
            "SET('aValue', 'anotherValue')",
            $subject->getSQLDeclaration($fieldDeclaration, $databaseMock)
        );
    }
}
