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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Property\TypeConverter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extensionmanager\Domain\Model\PackageIdentifier;
use TYPO3\CMS\Extensionmanager\Property\TypeConverter\PackageIdentifierTypeConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PackageIdentifierTypeConverterTest extends UnitTestCase
{
    private PackageIdentifierTypeConverter $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new PackageIdentifierTypeConverter();
    }

    #[Test]
    public function convertFromReturnsPackageIdentifierForValidInput(): void
    {
        $result = $this->subject->convertFrom(
            ['packageKey' => 'my_extension', 'version' => '1.2.3', 'remote' => 'ter'],
            PackageIdentifier::class
        );

        self::assertInstanceOf(PackageIdentifier::class, $result);
        self::assertSame('my_extension', $result->packageKey);
        self::assertSame('1.2.3', $result->version);
        self::assertSame('ter', $result->remote);
    }

    public static function invalidSourceProvider(): array
    {
        return [
            'missing packageKey' => [['version' => '1.2.3', 'remote' => 'ter'], 1750687200],
            'empty packageKey'   => [['packageKey' => '', 'version' => '1.2.3', 'remote' => 'ter'], 1750687200],
            'missing version'    => [['packageKey' => 'my_ext', 'remote' => 'ter'], 1750687201],
            'empty version'      => [['packageKey' => 'my_ext', 'version' => '', 'remote' => 'ter'], 1750687201],
            'missing remote'     => [['packageKey' => 'my_ext', 'version' => '1.2.3'], 1750687202],
            'empty remote'       => [['packageKey' => 'my_ext', 'version' => '1.2.3', 'remote' => ''], 1750687202],
        ];
    }

    #[Test]
    #[DataProvider('invalidSourceProvider')]
    public function convertFromReturnsErrorForInvalidInput(array $source, int $expectedCode): void
    {
        $result = $this->subject->convertFrom($source, PackageIdentifier::class);

        self::assertInstanceOf(Error::class, $result);
        self::assertSame($expectedCode, $result->getCode());
    }
}
