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

namespace TYPO3\CMS\Install\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache;
use TYPO3\CMS\Install\Service\Exception\ConfigurationChangedException;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SilentConfigurationUpgradeServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['install'];

    protected bool $initializeDatabase = false;

    protected array $localConfigurationBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->localConfigurationBackup = $this->get(ConfigurationManager::class)->getLocalConfiguration();
    }

    protected function tearDown(): void
    {
        $this->get(ConfigurationManager::class)->writeLocalConfiguration($this->localConfigurationBackup);
        parent::tearDown();
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function defaultCreatedConfigurationIsClean(): void
    {
        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $subject->execute();
    }

    #[Test]
    public function removeObsoleteLocalConfigurationSettingsIfThereAreOldSettings(): void
    {
        $testConfig = [
            'BE' => [
                'spriteIconGenerator_handler' => 'shouldBeRemoved',
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertTrue($exceptionCaught);
            $settings = $configurationManager->getLocalConfiguration();
            self::assertArrayNotHasKey('spriteIconGenerator_handler', $settings['BE']);
        }
    }

    #[Test]
    public function removeObsoleteLocalConfigurationSettingsKeepsUnaffectedSettings(): void
    {
        $testConfig = [
            'BE' => [
                'spriteIconGenerator_handlerKeep' => 'shouldBeKept',
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertFalse($exceptionCaught);
            self::assertSame('shouldBeKept', $configurationManager->getLocalConfigurationValueByPath('BE/spriteIconGenerator_handlerKeep'));
        }
    }

    #[Test]
    public function doNotGenerateEncryptionKeyIfExists(): void
    {
        $testConfig = [
            'SYS' => [
                'encryptionKey' => 'EnCrYpTiOnKeY',
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertFalse($exceptionCaught);
            self::assertSame('EnCrYpTiOnKeY', $configurationManager->getLocalConfigurationValueByPath('SYS/encryptionKey'));
        }
    }

    #[Test]
    public function generateEncryptionKeyIfNotExists(): void
    {
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->removeLocalConfigurationKeysByPath(['SYS/encryptionKey']);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertTrue($exceptionCaught);
            self::assertIsString($configurationManager->getLocalConfigurationValueByPath('SYS/encryptionKey'));
            self::assertNotEmpty($configurationManager->getLocalConfigurationValueByPath('SYS/encryptionKey'));
        }
    }

    public static function transferHttpSettingsIfSetDataProvider(): array
    {
        return [
            'No changes overridden in Local Configuration' => [
                ['timeout' => 100],
                ['HTTP/timeout' => 100],
                false,
            ],
            'Old and unused settings removed' => [
                ['adapter' => 'curl'],
                [],
                true,
            ],
            'Old and used settings changed' => [
                ['protocol_version' => '1.1'],
                ['HTTP/version' => '1.1'],
                true,
            ],

            /** redirect options */
            'Redirects moved to default' => [
                ['follow_redirects' => true],
                [],
                true,
            ],
            'Redirects moved #1' => [
                ['follow_redirects' => true, 'max_redirects' => 200, 'strict_redirects' => false],
                ['HTTP/allow_redirects' => ['max' => 200]],
                true,
            ],
            'Redirects moved #2' => [
                ['follow_redirects' => false, 'max_redirects' => 200, 'strict_redirects' => false],
                ['HTTP/allow_redirects' => false],
                true,
            ],
            'Redirects moved #3' => [
                ['follow_redirects' => true, 'max_redirects' => 400, 'strict_redirects' => 1],
                ['HTTP/allow_redirects' => ['max' => 400, 'strict' => true]],
                true,
            ],

            /** Proxy settings */
            'Proxy host set' => [
                ['proxy_host' => 'vpn.myproxy.com'],
                ['HTTP/proxy' => 'http://vpn.myproxy.com'],
                true,
            ],
            'Proxy host set + port' => [
                ['proxy_host' => 'vpn.myproxy.com', 'proxy_port' => 8080],
                ['HTTP/proxy' => 'http://vpn.myproxy.com:8080'],
                true,
            ],
            'Proxy host set + port + verification' => [
                ['proxy_host' => 'vpn.myproxy.com', 'proxy_port' => 8080, 'proxy_auth_scheme' => 'basic', 'proxy_user' => 'myuser', 'proxy_password' => 'mysecret'],
                ['HTTP/proxy' => 'http://myuser:mysecret@vpn.myproxy.com:8080'],
                true,
            ],

            /** SSL verification */
            'Only ssl_capath set, invalid migration' => [
                ['ssl_capath' => '/foo/bar/'],
                [],
                true,
            ],
            'Verification activated, but only ssl_capath set, using default' => [
                ['ssl_verify_peer' => 1, 'ssl_capath' => '/foo/bar/'],
                [],
                true,
            ],
            'Verification activated, with ssl_capath and ssl_cafile set' => [
                ['ssl_verify_peer' => 1, 'ssl_capath' => '/foo/bar/', 'ssl_cafile' => 'supersecret.crt'],
                ['HTTP/verify' => '/foo/bar/supersecret.crt'],
                true,
            ],

            /** SSL key + passphrase */
            'SSL key certification' => [
                ['ssl_local_cert' => '/foo/bar/supersecret.key'],
                ['HTTP/ssl_key' => '/foo/bar/supersecret.key'],
                true,
            ],
            'SSL key certification + passphrase' => [
                ['ssl_local_cert' => '/foo/bar/supersecret.key', 'ssl_passphrase' => 'donotcopypasteme'],
                ['HTTP/ssl_key' => ['/foo/bar/supersecret.key', 'donotcopypasteme']],
                true,
            ],
            'SSL key passphrase only - no migration' => [
                ['ssl_passphrase' => 'donotcopypasteme'],
                [],
                true,
            ],
        ];
    }

    #[DataProvider('transferHttpSettingsIfSetDataProvider')]
    #[Test]
    public function transferHttpSettingsIfSet(array $currentLocalConfiguration, array $newSettings, bool $localConfigurationNeedsUpdate): void
    {
        $testConfig = [
            'HTTP' => $currentLocalConfiguration,
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertSame($localConfigurationNeedsUpdate, $exceptionCaught);
            foreach ($newSettings as $newSettingKey => $newSettingValue) {
                self::assertSame($newSettingValue, $configurationManager->getLocalConfigurationValueByPath($newSettingKey));
            }
        }
    }

    #[Test]
    public function disableImageMagickDetailSettingsIfImageMagickIsDisabled(): void
    {
        $testConfig = [
            'GFX' => [
                'processor_enabled' => false,
                'imagefile_ext' => 'gif,jpg,png',
                'thumbnails' => 0,
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertTrue($exceptionCaught);
            self::assertSame('gif,jpg,jpeg,png', $configurationManager->getLocalConfigurationValueByPath('GFX/imagefile_ext'));
        }
    }

    #[Test]
    public function doNotDisableImageMagickDetailSettingsIfImageMagickIsEnabled(): void
    {
        $testConfig = [
            'GFX' => [
                'processor_enabled' => true,
                'imagefile_ext' => 'gif,jpg,jpeg,png',
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertFalse($exceptionCaught);
            self::assertSame('gif,jpg,jpeg,png', $configurationManager->getLocalConfigurationValueByPath('GFX/imagefile_ext'));
            self::assertTrue($configurationManager->getLocalConfigurationValueByPath('GFX/processor_enabled'));
        }
    }

    #[Test]
    public function doNotSetImageMagickDetailSettings(): void
    {
        $testConfig = [
            'GFX' => [
                'processor' => '',
                'processor_effects' => 0,
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertFalse($exceptionCaught);
        }
    }

    public static function migratesGraphicsProcessorEffectsDataProvider(): array
    {
        return [
            'integer 1' => [
                1,
                true,
            ],
            'integer 0' => [
                0,
                false,
            ],
            'integer -1' => [
                -1,
                false,
            ],
            'string "1"' => [
                '1',
                true,
            ],
            'string "0"' => [
                '0',
                false,
            ],
            'string "-1"' => [
                '-1',
                false,
            ],
        ];
    }

    #[DataProvider('migratesGraphicsProcessorEffectsDataProvider')]
    #[Test]
    public function migratesGraphicsProcessorEffects(string|int $currentValue, bool $expectedMigratedValue): void
    {
        $testConfig = [
            'GFX' => [
                'processor' => 'GraphicsMagick',
                'processor_effects' => $currentValue,
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertTrue($exceptionCaught);
            self::assertSame($expectedMigratedValue, $configurationManager->getLocalConfigurationValueByPath('GFX/processor_effects'));
        }
    }

    public static function removesDefaultColorspaceSettingsDataProvider(): array
    {
        return [
            'ImageMagick' => [
                'ImageMagick',
                'sRGB',
            ],
            'GraphicsMagick' => [
                'GraphicsMagick',
                'RGB',
            ],
        ];
    }

    #[DataProvider('removesDefaultColorspaceSettingsDataProvider')]
    #[Test]
    public function removesDefaultColorspaceSettings(string $currentProcessor, string $currentColorspace)
    {
        $testConfig = [
            'GFX' => [
                'processor' => $currentProcessor,
                'processor_colorspace' => $currentColorspace,
            ],
        ];

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertTrue($exceptionCaught);
            $settings = $configurationManager->getLocalConfiguration();
            self::assertArrayNotHasKey('processor_colorspace', $settings['GFX']);
        }
    }

    #[Test]
    public function migrateCacheHashOptions(): void
    {
        $testConfig = [
            'FE' => [
                'cHashOnlyForParameters' => 'foo,bar',
                'cHashExcludedParameters' => 'bar,foo',
                'cHashRequiredParameters' => 'bar,baz',
                'cHashExcludedParametersIfEmpty' => '*',
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertTrue($exceptionCaught);
            $settings = $configurationManager->getLocalConfiguration();
            self::assertArrayNotHasKey('cHashOnlyForParameters', $settings['FE']);
            self::assertArrayNotHasKey('cHashExcludedParameters', $settings['FE']);
            self::assertArrayNotHasKey('cHashRequiredParameters', $settings['FE']);
            self::assertArrayNotHasKey('cHashExcludedParametersIfEmpty', $settings['FE']);
            self::assertSame(['foo', 'bar'], $configurationManager->getLocalConfigurationValueByPath('FE/cacheHash/cachedParametersWhiteList'));
            self::assertSame(['bar', 'foo'], $configurationManager->getLocalConfigurationValueByPath('FE/cacheHash/excludedParameters'));
            self::assertSame(['bar', 'baz'], $configurationManager->getLocalConfigurationValueByPath('FE/cacheHash/requireCacheHashPresenceParameters'));
            self::assertTrue($configurationManager->getLocalConfigurationValueByPath('FE/cacheHash/excludeAllEmptyParameters'));
        }
    }

    #[Test]
    public function migrateVersionNumberInFilename(): void
    {
        $testConfig = [
            'FE' => [
                'versionNumberInFilename' => 'embed',
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertTrue($exceptionCaught);
            self::assertTrue($configurationManager->getLocalConfigurationValueByPath('FE/versionNumberInFilename'));
        }
    }

    #[Test]
    public function versionNumberInFilenameSetToTrueStaysUntouched(): void
    {
        $testConfig = [
            'FE' => [
                'versionNumberInFilename' => true,
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertFalse($exceptionCaught);
            self::assertTrue($configurationManager->getLocalConfigurationValueByPath('FE/versionNumberInFilename'));
        }
    }

    #[Test]
    public function migrateSaltedPasswordsSettingsRemovesExtensionsConfigAndSetsNothingElseIfArgon2iIsAvailable(): void
    {
        $testConfig = [
            'EXTENSIONS' => [
                'saltedpasswords' => [
                    'thereIs' => 'something',
                ],
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertTrue($exceptionCaught);
            $settings = $configurationManager->getLocalConfiguration();
            self::assertArrayNotHasKey('saltedpasswords', $settings['EXTENSIONS']);
        }
    }

    #[Test]
    public function migrateSaltedPasswordsSetsSpecificHashMethodIfArgon2idAndArgon2iIsNotAvailable(): void
    {
        $argon2idBeMock = $this->createMock(Argon2idPasswordHash::class);
        $argon2idBeMock->expects($this->atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2idPasswordHash::class, $argon2idBeMock);
        $argonBeMock = $this->createMock(Argon2iPasswordHash::class);
        $argonBeMock->expects($this->atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonBeMock);
        $argon2idFeMock = $this->createMock(Argon2idPasswordHash::class);
        $argon2idFeMock->expects($this->atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2idPasswordHash::class, $argon2idFeMock);
        $argonFeMock = $this->createMock(Argon2iPasswordHash::class);
        $argonFeMock->expects($this->atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonFeMock);

        $testConfig = [
            'EXTENSIONS' => [
                'saltedpasswords' => [
                    'some' => 'setting',
                ],
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->updateLocalConfiguration($testConfig);

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertTrue($exceptionCaught);
            $settings = $configurationManager->getLocalConfiguration();
            self::assertArrayNotHasKey('saltedpasswords', $settings['EXTENSIONS']);
            self::assertSame(BcryptPasswordHash::class, $configurationManager->getLocalConfigurationValueByPath('BE/passwordHashing/className'));
            self::assertSame(BcryptPasswordHash::class, $configurationManager->getLocalConfigurationValueByPath('FE/passwordHashing/className'));
        }
    }

    #[Test]
    public function migrateCachingFrameworkCachesMigratesData(): void
    {
        $testConfig = [
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        'cache_rootline' => [
                            'frontend' => VariableFrontend::class,
                            'backend' => Typo3DatabaseBackend::class,
                            'options' => [
                                'defaultLifetime' => 2592000,
                            ],
                            'groups' => ['pages'],
                        ],
                        'fluid_template' => [
                            'backend' => SimpleFileBackend::class,
                            'frontend' => FluidTemplateCache::class,
                            'groups' => ['system'],
                        ],
                    ],
                ],
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->removeLocalConfigurationKeysByPath(['SYS/caching/cacheConfigurations/rootline']);
        $configurationManager->removeLocalConfigurationKeysByPath(['SYS/caching/cacheConfigurations/fluid_template']);
        $configurationManager->updateLocalConfiguration($testConfig);

        $expectedRootlineConfiguration = [
            'frontend' => VariableFrontend::class,
            'backend' => Typo3DatabaseBackend::class,
            'options' => [
                'defaultLifetime' => 2592000,
            ],
            'groups' => ['pages'],
        ];
        $expectedFluidTemplateConfiguration = [
            'backend' => SimpleFileBackend::class,
            'frontend' => FluidTemplateCache::class,
            'groups' => ['system'],
        ];

        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $exceptionCaught = false;
        try {
            $subject->execute();
        } catch (ConfigurationChangedException) {
            $exceptionCaught = true;
        } finally {
            self::assertTrue($exceptionCaught);
            self::assertEquals(
                $expectedRootlineConfiguration,
                $configurationManager->getLocalConfigurationValueByPath('SYS/caching/cacheConfigurations/rootline')
            );
            self::assertEquals(
                $expectedFluidTemplateConfiguration,
                $configurationManager->getLocalConfigurationValueByPath('SYS/caching/cacheConfigurations/fluid_template')
            );
        }
    }
}
