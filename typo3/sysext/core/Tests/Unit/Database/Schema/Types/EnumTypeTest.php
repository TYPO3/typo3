<?php
declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Unit\Database;

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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Prophecy\Argument;
use TYPO3\CMS\Core\Database\Schema\Types\EnumType;

/**
 * Test for EnumType
 */
class EnumTypeTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Set up the test subject
     */
    protected function setUp()
    {
        parent::setUp();
        if (!Type::hasType(EnumType::TYPE)) {
            Type::addType(EnumType::TYPE, EnumType::class);
        }
    }

    /**
     * @test
     */
    public function getNameReturnsTypeIdentifier()
    {
        $subject = Type::getType(EnumType::TYPE);
        $this->assertSame(EnumType::TYPE, $subject->getName());
    }

    /**
     * @test
     */
    public function getSQLDeclaration()
    {
        $fieldDeclaration = [
            'unquotedValues' => ['aValue', 'anotherValue'],
        ];

        $databaseProphet = $this->prophesize(AbstractPlatform::class);
        $databaseProphet->quoteStringLiteral(Argument::cetera())->will(
            function ($args) {
                return "'" . $args[0] . "'";
            }
        );

        $subject = Type::getType(EnumType::TYPE);
        $this->assertSame(
            "ENUM('aValue', 'anotherValue')",
            $subject->getSQLDeclaration($fieldDeclaration, $databaseProphet->reveal())
        );
    }
}
