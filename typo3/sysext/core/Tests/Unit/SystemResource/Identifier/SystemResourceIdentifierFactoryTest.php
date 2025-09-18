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

namespace TYPO3\CMS\Core\Tests\Unit\SystemResource\Identifier;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SystemResource\Exception\InvalidSystemResourceIdentifierException;
use TYPO3\CMS\Core\SystemResource\Identifier\FalResourceIdentifier;
use TYPO3\CMS\Core\SystemResource\Identifier\PackageResourceIdentifier;
use TYPO3\CMS\Core\SystemResource\Identifier\SystemResourceIdentifierFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SystemResourceIdentifierFactoryTest extends UnitTestCase
{
    public static function correctIdentifierCreatedFromStringDataProvider(): \Generator
    {
        yield 'EXT: syntax' => [
            'EXT:core/Resources/Public/Icons/Extension.svg',
            PackageResourceIdentifier::class,
            'PKG:typo3/cms-core:Resources/Public/Icons/Extension.svg',
            'core',
        ];
        yield 'EXT: syntax with fragment' => [
            'EXT:core/Resources/Public/Icons/Extension.svg#fragment',
            PackageResourceIdentifier::class,
            'PKG:typo3/cms-core:Resources/Public/Icons/Extension.svg#fragment',
            'core',
        ];
        yield 'PKG: syntax' => [
            'PKG:typo3/cms-core:Resources/Public/Icons/Extension.svg',
            PackageResourceIdentifier::class,
        ];
        yield 'FAL: syntax' => [
            'FAL:1:/identifier/of/file.ext',
            FalResourceIdentifier::class,
        ];
    }

    #[DataProvider('correctIdentifierCreatedFromStringDataProvider')]
    #[Test]
    public function correctIdentifierCreatedFromString(string $potentialIdentifier, string $expectedClass, ?string $expectedNormalizedIdentifier = null, string $packageKey = 'core'): void
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager
            ->method('extractPackageKeyFromPackagePath')
            ->with($potentialIdentifier)
            ->willReturn($packageKey);
        $package = $this->createMock(PackageInterface::class);
        $package
            ->method('getValueFromComposerManifest')
            ->with('name')
            ->willReturn('typo3/cms-' . $packageKey);
        $packageManager
            ->method('getPackage')
            ->with($packageKey)
            ->willReturn($package);

        $subject = new SystemResourceIdentifierFactory($packageManager);
        $identifier = $subject->create($potentialIdentifier);
        self::assertInstanceOf($expectedClass, $identifier);
        self::assertSame($expectedNormalizedIdentifier ?? $potentialIdentifier, (string)$identifier);
    }

    public static function invalidIdentifierThrowsExceptionDataProvider(): \Generator
    {
        yield 'PKG: syntax, leading slash' => [
            'PKG:typo3/cms-core:/Resources/Public/Icons/Extension.svg',
        ];
        yield 'FAL: syntax, storage not int' => [
            'FAL:fileadmin:/identifier/of/file.ext',
            FalResourceIdentifier::class,
        ];
        yield 'FAL: syntax, too few colons' => [
            'FAL:1/identifier/of/file.ext',
            FalResourceIdentifier::class,
        ];
        yield 'FAL: syntax, too many colons' => [
            'FAL:1:/identifier/of/file:ext',
            FalResourceIdentifier::class,
        ];
    }

    #[DataProvider('invalidIdentifierThrowsExceptionDataProvider')]
    #[Test]
    public function invalidIdentifierThrowsException(string $potentialIdentifier): void
    {
        $this->expectException(InvalidSystemResourceIdentifierException::class);
        $packageManager = $this->createMock(PackageManager::class);
        $subject = new SystemResourceIdentifierFactory($packageManager);
        $subject->create($potentialIdentifier);
    }

    #[Test]
    public function createFromPackagePathReturnsIdentifier(): void
    {
        $packageManager = $this->createMock(PackageManager::class);
        $package = $this->createMock(PackageInterface::class);
        $package
            ->method('getValueFromComposerManifest')
            ->with('name')
            ->willReturn('typo3/cms-core');
        $packageManager
            ->method('getPackage')
            ->with('core')
            ->willReturn($package);

        $subject = new SystemResourceIdentifierFactory($packageManager);
        $identifier = $subject->createFromPackagePath(
            'core',
            'Resources/Public/Icons/Extension.svg',
            'core:Resources/Public/Icons/Extension.svg'
        );
        self::assertSame('PKG:typo3/cms-core:Resources/Public/Icons/Extension.svg', (string)$identifier);
        self::assertSame('core:Resources/Public/Icons/Extension.svg', $identifier->givenIdentifier);
    }
}
