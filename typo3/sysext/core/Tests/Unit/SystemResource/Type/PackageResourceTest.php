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

namespace TYPO3\CMS\Core\Tests\Unit\SystemResource\Type;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\Resource\Definition\ResourceDefinitionInterface;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotDetectImageDimensionOfSystemResourceException;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceDoesNotExistException;
use TYPO3\CMS\Core\SystemResource\Identifier\PackageResourceIdentifier;
use TYPO3\CMS\Core\SystemResource\Type\PackageResource;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PackageResourceTest extends UnitTestCase
{
    private string $packagePath;

    protected function setUp(): void
    {
        parent::setUp();
        // This is required due to how ImageInfo implementation currently works
        $this->resetSingletonInstances = true;
        $this->packagePath = Environment::getVarPath() . '/PackageResourceTest';
        @mkdir($this->packagePath);
        $this->testFilesToDelete[] = $this->packagePath;
    }

    #[Test]
    public function isImageReturnsTrueForImage(): void
    {
        $this->copyFixtureImage('test.png');

        $resource = $this->createResource('test.png');

        self::assertTrue($resource->isImage());
    }

    #[Test]
    public function isImageReturnsFalseForNonImage(): void
    {
        file_put_contents($this->packagePath . '/test.txt', 'test');

        $resource = $this->createResource('test.txt');

        self::assertFalse($resource->isImage());
    }

    #[Test]
    public function isImageThrowsExceptionForNonExistingResource(): void
    {
        $resource = $this->createResource('does-not-exist.png');

        $this->expectException(SystemResourceDoesNotExistException::class);

        $resource->isImage();
    }

    #[Test]
    public function getImageDimensionReturnsImageDimensions(): void
    {
        $this->copyFixtureImage('test.png');

        $resource = $this->createResource('test.png');

        self::assertEquals(
            new ImageDimension(200, 85),
            $resource->getImageDimension(),
        );
    }

    #[Test]
    public function getImageDimensionThrowsExceptionForNonImage(): void
    {
        file_put_contents($this->packagePath . '/test.txt', 'test');

        $resource = $this->createResource('test.txt');

        $this->expectException(CanNotDetectImageDimensionOfSystemResourceException::class);

        $resource->getImageDimension();
    }

    #[Test]
    public function getImageDimensionThrowsExceptionForBrokenImage(): void
    {
        $this->copyFixtureImage('test-broken.png');

        $previousMimeType = $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['png'] ?? null;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['png'] = 'image/png';

        $resource = $this->createResource('test-broken.png');

        $this->expectException(CanNotDetectImageDimensionOfSystemResourceException::class);
        $this->expectExceptionCode(1787399730);

        try {
            $resource->getImageDimension();
        } finally {
            if ($previousMimeType === null) {
                unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['png']);
            } else {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['png'] = $previousMimeType;
            }
        }
    }

    #[Test]
    public function getImageDimensionThrowsExceptionForNonExistingResource(): void
    {
        $resource = $this->createResource('does-not-exist.png');

        $this->expectException(SystemResourceDoesNotExistException::class);

        $resource->getImageDimension();
    }

    #[Test]
    public function getImageDimensionReturnsSameInstanceOnSubsequentCalls(): void
    {
        $this->copyFixtureImage('test.png');

        $resource = $this->createResource('test.png');

        $firstDimension = $resource->getImageDimension();
        $secondDimension = $resource->getImageDimension();

        self::assertSame($firstDimension, $secondDimension);
    }

    #[Test]
    public function getExtensionReturnsEmptyStringForFileWithoutExtension(): void
    {
        $resource = $this->createResource('test');

        self::assertSame('', $resource->getExtension());
    }

    private function createResource(string $relativePath): PackageResource
    {
        $package = self::createStub(PackageInterface::class);
        $package
            ->method('getPackagePath')
            ->willReturn($this->packagePath . '/');
        $package->method('getPackageKey')
            ->willReturn('test');

        $identifier = new PackageResourceIdentifier(
            $package,
            $relativePath,
            'PKG:test:' . $relativePath,
        );

        return new PackageResource(
            $identifier,
            self::createStub(ResourceDefinitionInterface::class),
        );
    }

    private function copyFixtureImage(string $filename): void
    {
        copy(__DIR__ . '/Fixtures/' . $filename, $this->packagePath . '/' . $filename);
    }
}
