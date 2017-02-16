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
use TYPO3\CMS\Core\Database\Schema\Types\SetType;

/**
 * Tests for SetType
 */
class SetTypeTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Set up the test subject
     */
    protected function setUp()
    {
        parent::setUp();
        if (!Type::hasType(SetType::TYPE)) {
            Type::addType(SetType::TYPE, SetType::class);
        }
    }

    /**
     * @test
     */
    public function getNameReturnsTypeIdentifier()
    {
        $subject = Type::getType(SetType::TYPE);
        $this->assertSame(SetType::TYPE, $subject->getName());
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

        $subject = Type::getType(SetType::TYPE);
        $this->assertSame(
            "SET('aValue', 'anotherValue')",
            $subject->getSQLDeclaration($fieldDeclaration, $databaseProphet->reveal())
        );
    }
}
