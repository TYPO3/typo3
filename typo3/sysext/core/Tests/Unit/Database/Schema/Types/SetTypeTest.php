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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Database\Schema\Types\SetType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for SetType
 */
class SetTypeTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * Set up the test subject
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!Type::hasType(SetType::TYPE)) {
            Type::addType(SetType::TYPE, SetType::class);
        }
    }

    /**
     * @test
     */
    public function getNameReturnsTypeIdentifier(): void
    {
        $subject = Type::getType(SetType::TYPE);
        self::assertSame(SetType::TYPE, $subject->getName());
    }

    /**
     * @test
     */
    public function getSQLDeclaration(): void
    {
        $fieldDeclaration = [
            'unquotedValues' => ['aValue', 'anotherValue'],
        ];

        $databaseProphet = $this->prophesize(AbstractPlatform::class);
        $databaseProphet->quoteStringLiteral(Argument::cetera())->will(
            static function ($args) {
                return "'" . $args[0] . "'";
            }
        );

        $subject = Type::getType(SetType::TYPE);
        self::assertSame(
            "SET('aValue', 'anotherValue')",
            $subject->getSQLDeclaration($fieldDeclaration, $databaseProphet->reveal())
        );
    }
}
