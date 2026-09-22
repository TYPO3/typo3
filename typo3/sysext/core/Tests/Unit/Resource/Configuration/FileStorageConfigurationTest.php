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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfiguration;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileStorageConfigurationException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileStorageConfigurationTest extends UnitTestCase
{
    private function createSubject(): FileStorageConfiguration
    {
        return new FileStorageConfiguration(Environment::getVarPath() . '/tests/non-existing-config-path', new YamlFileLoader(new NullLogger()), new NullLogger(), new PhpFrontend('core', new NullBackend()));
    }

    #[Test]
    public function implicitDefaultIsReturnedWithoutFile(): void
    {
        $subject = $this->createSubject();
        self::assertFalse($subject->isFilePresent());
        self::assertSame(
            [
                1 => [
                    'identifier' => 1,
                    'name' => 'fileadmin',
                    'driver' => 'Local',
                    'default' => true,
                    'browsable' => true,
                    'public' => true,
                    'writable' => true,
                    'online' => true,
                    'autoExtractMetadata' => true,
                    'processingFolder' => '',
                    'configuration' => [
                        'basePath' => Environment::getRelativePublicPath() . 'fileadmin/',
                    ],
                ],
            ],
            $subject->getAll()
        );
        self::assertNull($subject->get(2));
    }

    #[Test]
    public function implicitDefaultUsesConfiguredFileadminDirectory(): void
    {
        $backendConfiguration = $GLOBALS['TYPO3_CONF_VARS']['BE'] ?? null;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] = 'files/';
        try {
            self::assertSame(Environment::getRelativePublicPath() . 'files/', $this->createSubject()->getImplicitDefault()[1]['configuration']['basePath']);
        } finally {
            if ($backendConfiguration === null) {
                unset($GLOBALS['TYPO3_CONF_VARS']['BE']);
            } else {
                $GLOBALS['TYPO3_CONF_VARS']['BE'] = $backendConfiguration;
            }
        }
    }

    #[Test]
    public function normalizeAppliesDefaults(): void
    {
        self::assertSame(
            [
                'identifier' => 3,
                'name' => 'Storage 3',
                'driver' => 'Local',
                'default' => false,
                'browsable' => true,
                'public' => true,
                'writable' => true,
                'online' => true,
                'autoExtractMetadata' => true,
                'processingFolder' => '',
                'configuration' => [],
            ],
            $this->createSubject()->normalize(3, [])
        );
    }

    public static function normalizeCastsBooleansDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
            ['1', true],
            ['0', false],
            ['true', true],
            ['false', false],
            ['yes', true],
            ['off', false],
            [1, true],
            [0, false],
        ];
    }

    #[DataProvider('normalizeCastsBooleansDataProvider')]
    #[Test]
    public function normalizeCastsBooleans(mixed $value, bool $expected): void
    {
        self::assertSame($expected, $this->createSubject()->normalize(1, ['public' => $value])['public']);
    }

    public static function normalizeRejectsInvalidEntriesDataProvider(): array
    {
        return [
            'identifier zero' => [0, [], 1789862402],
            'unknown option' => [1, ['basePath' => 'fileadmin/'], 1789862403],
            'empty driver' => [1, ['driver' => ' '], 1789862405],
            'configuration not a map' => [1, ['configuration' => 'fileadmin/'], 1789862406],
            'boolean from arbitrary string' => [1, ['online' => 'maybe'], 1789862412],
        ];
    }

    #[DataProvider('normalizeRejectsInvalidEntriesDataProvider')]
    #[Test]
    public function normalizeRejectsInvalidEntries(int $identifier, array $entry, int $expectedCode): void
    {
        $this->expectException(InvalidFileStorageConfigurationException::class);
        $this->expectExceptionCode($expectedCode);
        $this->createSubject()->normalize($identifier, $entry);
    }

    #[Test]
    public function validateSetAcceptsSingleStorageWithoutDefault(): void
    {
        $this->expectNotToPerformAssertions();
        $subject = $this->createSubject();
        $subject->validateSet([1 => $subject->normalize(1, [])]);
    }

    #[Test]
    public function validateSetRejectsSeveralDefaults(): void
    {
        $subject = $this->createSubject();
        $this->expectException(InvalidFileStorageConfigurationException::class);
        $this->expectExceptionCode(1789862408);
        $subject->validateSet([
            1 => $subject->normalize(1, ['default' => true]),
            2 => $subject->normalize(2, ['default' => true]),
        ]);
    }

    #[Test]
    public function validateSetRequiresDefaultAmongSeveralStorages(): void
    {
        $subject = $this->createSubject();
        $this->expectException(InvalidFileStorageConfigurationException::class);
        $this->expectExceptionCode(1789862409);
        $subject->validateSet([
            1 => $subject->normalize(1, []),
            2 => $subject->normalize(2, []),
        ]);
    }
}
