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
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function defaultCreatedConfigurationIsClean(): void
    {
        $subject = $this->get(SilentConfigurationUpgradeService::class);
        $subject->execute();
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     * @dataProvider transferHttpSettingsIfSetDataProvider
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function setImageMagickDetailSettings(): void
    {
        $testConfig = [
            'GFX' => [
                'processor' => 'GraphicsMagick',
                'processor_allowTemporaryMasksAsPng' => 1,
                'processor_effects' => false,
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
            self::assertSame(0, $configurationManager->getLocalConfigurationValueByPath('GFX/processor_allowTemporaryMasksAsPng'));
        }
    }

    /**
     * @test
     */
    public function doNotSetImageMagickDetailSettings(): void
    {
        $testConfig = [
            'GFX' => [
                'processor' => '',
                'processor_allowTemporaryMasksAsPng' => 0,
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

    /**
     * @test
     * @dataProvider migratesGraphicsProcessorEffectsDataProvider
     */
    public function migratesGraphicsProcessorEffects(string|int $currentValue, bool $expectedMigratedValue): void
    {
        $testConfig = [
            'GFX' => [
                'processor' => 'GraphicsMagick',
                'processor_allowTemporaryMasksAsPng' => false,
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

    /**
     * @test
     */
    public function migrateExistingLangDebug(): void
    {
        $testConfig = [
            'BE' => [
                'lang' => [
                    'debug' => false,
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
            self::assertFalse($configurationManager->getLocalConfigurationValueByPath('BE/languageDebug'));
        }
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function migrateSaltedPasswordsSetsSpecificHashMethodIfArgon2idAndArgon2iIsNotAvailable(): void
    {
        $argon2idBeMock = $this->createMock(Argon2idPasswordHash::class);
        $argon2idBeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2idPasswordHash::class, $argon2idBeMock);
        $argonBeMock = $this->createMock(Argon2iPasswordHash::class);
        $argonBeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonBeMock);
        $argon2idFeMock = $this->createMock(Argon2idPasswordHash::class);
        $argon2idFeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2idPasswordHash::class, $argon2idFeMock);
        $argonFeMock = $this->createMock(Argon2iPasswordHash::class);
        $argonFeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(false);
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

    /**
     * @test
     */
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
