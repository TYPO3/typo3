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

namespace TYPO3\CMS\Install\Tests\Unit\Service;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash;
use TYPO3\CMS\Core\Package\UnitTestPackageManager;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache;
use TYPO3\CMS\Install\Service\Exception\ConfigurationChangedException;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SilentConfigurationUpgradeServiceTest extends UnitTestCase
{
    /**
     * @var ConfigurationManager|MockObject
     */
    protected $configurationManager;

    /**
     * @var UnitTestPackageManager A backup of unit test package manager
     */
    protected $backupPackageManager;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->backupPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
        parent::setUp();
    }

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        ExtensionManagementUtilityAccessibleProxy::setPackageManager($this->backupPackageManager);
        parent::tearDown();
    }

    protected function createConfigurationManagerWithMockedMethods(array $methods): void
    {
        $this->configurationManager = $this->getMockBuilder(ConfigurationManager::class)
            ->onlyMethods($methods)
            ->getMock();
    }

    /**
     * @test
     */
    public function removeObsoleteLocalConfigurationSettingsIfThereAreOldSettings(): void
    {
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $obsoleteLocalConfigurationSettings = [
            'SYS/form_enctype',
        ];

        $currentLocalConfiguration = [
            [$obsoleteLocalConfigurationSettings, true],
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'removeLocalConfigurationKeysByPath',
            ]
        );
        $this->configurationManager->expects(self::once())
            ->method('removeLocalConfigurationKeysByPath')
            ->willReturnMap($currentLocalConfiguration);

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeServiceInstance->_set('obsoleteLocalConfigurationSettings', $obsoleteLocalConfigurationSettings);
        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('removeObsoleteLocalConfigurationSettings');
    }

    /**
     * @test
     */
    public function doNotRemoveObsoleteLocalConfigurationSettingsIfThereAreNoOldSettings(): void
    {
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $obsoleteLocalConfigurationSettings = [
            'SYS/form_enctype',
        ];

        $currentLocalConfiguration = [
            [$obsoleteLocalConfigurationSettings, false],
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'removeLocalConfigurationKeysByPath',
            ]
        );
        $this->configurationManager->expects(self::once())
            ->method('removeLocalConfigurationKeysByPath')
            ->willReturnMap($currentLocalConfiguration);

        $silentConfigurationUpgradeServiceInstance->_set('obsoleteLocalConfigurationSettings', $obsoleteLocalConfigurationSettings);
        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('removeObsoleteLocalConfigurationSettings');
    }

    /**
     * @test
     */
    public function doNotGenerateEncryptionKeyIfExists(): void
    {
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['SYS/encryptionKey', 'EnCrYpTiOnKeY'],
        ];

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        $this->configurationManager->expects(self::once())
            ->method('getLocalConfigurationValueByPath')
            ->willReturnMap($currentLocalConfiguration);
        $this->configurationManager->expects(self::never())
            ->method('setLocalConfigurationValueByPath');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('generateEncryptionKeyIfNeeded');
    }

    /**
     * @test
     */
    public function generateEncryptionKeyIfNotExists(): void
    {
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $closure = static function () {
            throw new MissingArrayPathException('Path does not exist in array', 1476109266);
        };

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        $this->configurationManager->expects(self::once())
            ->method('getLocalConfigurationValueByPath')
            ->willReturnCallback($closure);
        $this->configurationManager->expects(self::once())
            ->method('setLocalConfigurationValueByPath')
            ->with(self::equalTo('SYS/encryptionKey'), self::isType('string'));

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('generateEncryptionKeyIfNeeded');
    }

    /**
     * Data provider for transferHttpSettings
     *
     * @return array
     */
    public function httpSettingsMappingDataProvider(): array
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
     * @dataProvider httpSettingsMappingDataProvider
     */
    public function transferHttpSettingsIfSet(array $currentLocalConfiguration, array $newSettings, bool $localConfigurationNeedsUpdate): void
    {
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $this->createConfigurationManagerWithMockedMethods(
            [
                'setLocalConfigurationValuesByPathValuePairs',
                'removeLocalConfigurationKeysByPath',
                'getLocalConfiguration',
            ]
        );

        $this->configurationManager
            ->method('getLocalConfiguration')
            ->willReturn(['HTTP' => $currentLocalConfiguration]);
        if ($localConfigurationNeedsUpdate) {
            if (!empty($newSettings)) {
                $this->configurationManager->expects(self::once())
                    ->method('setLocalConfigurationValuesByPathValuePairs')
                    ->with($newSettings);
            }
            $this->configurationManager->expects(self::atMost(1))->method('removeLocalConfigurationKeysByPath');
        }

        if ($localConfigurationNeedsUpdate) {
            $this->expectException(ConfigurationChangedException::class);
        }

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('transferHttpSettings');
    }

    /**
     * @test
     */
    public function disableImageMagickDetailSettingsIfImageMagickIsDisabled(): void
    {
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/im', 0],
            ['GFX/im_path', ''],
            ['GFX/imagefile_ext', 'gif,jpg,png'],
            ['GFX/thumbnails', 0],
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects(self::exactly(4))
            ->method('getLocalConfigurationValueByPath')
            ->willReturnMap($currentLocalConfiguration);
        $this->configurationManager->expects(self::never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects(self::once())
            ->method('setLocalConfigurationValuesByPathValuePairs')
            ->withConsecutive(
                [['GFX/imagefile_ext' => 'gif,jpg,jpeg,png']]
            );

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('disableImageMagickDetailSettingsIfImageMagickIsDisabled');
    }

    /**
     * @test
     */
    public function doNotDisableImageMagickDetailSettingsIfImageMagickIsEnabled(): void
    {
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/im', 1],
            ['GFX/im_path', ''],
            ['GFX/imagefile_ext', 'gif,jpg,jpeg,png'],
            ['GFX/thumbnails', 0],
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects(self::exactly(4))
            ->method('getLocalConfigurationValueByPath')
            ->willReturnMap($currentLocalConfiguration);
        $this->configurationManager->expects(self::never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects(self::never())
            ->method('setLocalConfigurationValuesByPathValuePairs');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('disableImageMagickDetailSettingsIfImageMagickIsDisabled');
    }

    /**
     * @test
     */
    public function setImageMagickDetailSettings(): void
    {
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/processor', 'GraphicsMagick'],
            ['GFX/processor_allowTemporaryMasksAsPng', 1],
            ['GFX/processor_effects', false],
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects(self::exactly(3))
            ->method('getLocalConfigurationValueByPath')
            ->willReturnMap($currentLocalConfiguration);
        $this->configurationManager->expects(self::never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects(self::once())
            ->method('setLocalConfigurationValuesByPathValuePairs')
            ->withConsecutive([
                [
                    'GFX/processor_allowTemporaryMasksAsPng' => 0,
                ],
            ]);

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('setImageMagickDetailSettings');
    }

    /**
     * @test
     */
    public function doNotSetImageMagickDetailSettings(): void
    {
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/processor', ''],
            ['GFX/processor_allowTemporaryMasksAsPng', 0],
            ['GFX/processor_effects', 0],
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects(self::exactly(3))
            ->method('getLocalConfigurationValueByPath')
            ->willReturnMap($currentLocalConfiguration);
        $this->configurationManager->expects(self::never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects(self::never())
            ->method('setLocalConfigurationValuesByPathValuePairs');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('setImageMagickDetailSettings');
    }

    /**
     * @test
     * @dataProvider graphicsProcessorEffects
     *
     * @param mixed $currentValue
     */
    public function migratesGraphicsProcessorEffects($currentValue, bool $expectedMigratedValue): void
    {
        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $configurationManagerMock->expects(self::any())->method('getLocalConfigurationValueByPath')->willReturnMap([
            ['GFX/processor', 'GraphicsMagick'],
            ['GFX/processor_allowTemporaryMasksAsPng', false],
            ['GFX/processor_effects', $currentValue],
        ]);
        $configurationManagerMock->expects(self::atLeastOnce())->method('setLocalConfigurationValuesByPathValuePairs');

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeService = new SilentConfigurationUpgradeService($configurationManagerMock);
        // Call protected method
        \Closure::bind(function () {
            return $this->setImageMagickDetailSettings();
        }, $silentConfigurationUpgradeService, SilentConfigurationUpgradeService::class)();
    }

    /**
     * @return array
     */
    public function graphicsProcessorEffects(): array
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
     */
    public function migrateNonExistingLangDebug(): void
    {
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );

        $this->configurationManager->expects(self::once())
            ->method('getLocalConfigurationValueByPath')
            ->willReturnMap($currentLocalConfiguration);
        $this->configurationManager->expects(self::never())
            ->method('setLocalConfigurationValueByPath');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('migrateLangDebug');
    }

    /**
     * @test
     */
    public function migrateExistingLangDebug(): void
    {
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['BE/lang/debug', false],
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );

        $this->configurationManager->expects(self::once())
            ->method('getLocalConfigurationValueByPath')
            ->willReturnMap($currentLocalConfiguration);
        $this->configurationManager->expects(self::once())
            ->method('setLocalConfigurationValueByPath')
            ->with(self::equalTo('BE/languageDebug'), false);

        $this->expectException(ConfigurationChangedException::class);
        $this->expectExceptionCode(1379024938);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);
        $silentConfigurationUpgradeServiceInstance->_call('migrateLangDebug');
    }

    /**
     * @test
     */
    public function migrateCacheHashOptions(): void
    {
        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $configurationManagerMock->method('getLocalConfigurationValueByPath')->willReturnMap([
            ['FE/cHashOnlyForParameters', 'foo,bar'],
            ['FE/cHashExcludedParameters', 'bar,foo'],
            ['FE/cHashRequiredParameters', 'bar,baz'],
            ['FE/cHashExcludedParametersIfEmpty', '*'],
        ]);
        $configurationManagerMock->expects(self::atLeastOnce())->method('setLocalConfigurationValuesByPathValuePairs');
        $configurationManagerMock->expects(self::atLeastOnce())->method('removeLocalConfigurationKeysByPath');

        $this->expectException(ConfigurationChangedException::class);
        $this->expectExceptionCode(1379024938);

        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $configurationManagerMock);
        $silentConfigurationUpgradeServiceInstance->_call('migrateCacheHashOptions');
    }

    /**
     * @test
     */
    public function migrateSaltedPasswordsSettingsDoesNothingIfExtensionConfigsAreNotSet(): void
    {
        $configurationManagerException = new MissingArrayPathException('Path does not exist in array', 1533989414);
        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $configurationManagerMock->method('getLocalConfigurationValueByPath')->willThrowException($configurationManagerException);
        $configurationManagerMock->expects(self::never())->method('setLocalConfigurationValuesByPathValuePairs');
        $silentConfigurationUpgradeService = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [$configurationManagerMock]
        );
        $silentConfigurationUpgradeService->_call('migrateSaltedPasswordsSettings');
    }

    /**
     * @test
     */
    public function migrateSaltedPasswordsSettingsDoesNothingIfExtensionConfigsAreEmpty(): void
    {
        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $configurationManagerMock->method('getLocalConfigurationValueByPath')->willReturn([]);
        $configurationManagerMock->expects(self::never())->method('setLocalConfigurationValuesByPathValuePairs');

        $silentConfigurationUpgradeService = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [$configurationManagerMock]
        );
        $silentConfigurationUpgradeService->_call('migrateSaltedPasswordsSettings');
    }

    /**
     * @test
     */
    public function migrateSaltedPasswordsSettingsRemovesExtensionsConfigAndSetsNothingElseIfArgon2iIsAvailable(): void
    {
        $argonBeMock = $this->getMockBuilder(Argon2iPasswordHash::class)->disableOriginalConstructor()->getMock();
        $argonBeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(true);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonBeMock);
        $argonFeMock = $this->getMockBuilder(Argon2iPasswordHash::class)->disableOriginalConstructor()->getMock();
        $argonFeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(true);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonFeMock);
        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $configurationManagerMock->expects(self::atLeastOnce())->method('getLocalConfigurationValueByPath')->willReturn(['thereIs' => 'something']);
        $configurationManagerMock->expects(self::atLeastOnce())->method('removeLocalConfigurationKeysByPath')->with(['EXTENSIONS/saltedpasswords']);
        $silentConfigurationUpgradeService = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [$configurationManagerMock]
        );
        $this->expectException(ConfigurationChangedException::class);
        $this->expectExceptionCode(1379024938);
        $silentConfigurationUpgradeService->_call('migrateSaltedPasswordsSettings');
    }

    /**
     * @test
     */
    public function migrateSaltedPasswordsSetsSpecificHashMethodIfArgon2idAndArgon2iIsNotAvailable(): void
    {
        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $configurationManagerMock->expects(self::atLeastOnce())->method('removeLocalConfigurationKeysByPath')->with(['EXTENSIONS/saltedpasswords']);
        $configurationManagerMock->expects(self::atLeastOnce())->method('setLocalConfigurationValuesByPathValuePairs')->with([
            'BE/passwordHashing/className' => BcryptPasswordHash::class,
            'FE/passwordHashing/className' => BcryptPasswordHash::class,
        ]);
        $configurationManagerMock->expects(self::atLeastOnce())->method('getLocalConfigurationValueByPath')->willReturnMap([
            ['EXTENSIONS/saltedpasswords', ['thereIs' => 'something']],
        ]);
        $argon2idBeMock = $this->getMockBuilder(Argon2idPasswordHash::class)->disableOriginalConstructor()->getMock();
        $argon2idBeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2idPasswordHash::class, $argon2idBeMock);
        $argonBeMock = $this->getMockBuilder(Argon2iPasswordHash::class)->disableOriginalConstructor()->getMock();
        $argonBeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonBeMock);
        $bcryptBeMock = $this->getMockBuilder(BcryptPasswordHash::class)->disableOriginalConstructor()->getMock();
        $bcryptBeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(true);
        GeneralUtility::addInstance(BcryptPasswordHash::class, $bcryptBeMock);
        $argon2idFeMock = $this->getMockBuilder(Argon2idPasswordHash::class)->disableOriginalConstructor()->getMock();
        $argon2idFeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2idPasswordHash::class, $argon2idFeMock);
        $argonFeMock = $this->getMockBuilder(Argon2iPasswordHash::class)->disableOriginalConstructor()->getMock();
        $argonFeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonFeMock);
        $bcryptFeMock = $this->getMockBuilder(BcryptPasswordHash::class)->disableOriginalConstructor()->getMock();
        $bcryptFeMock->expects(self::atLeastOnce())->method('isAvailable')->willReturn(true);
        GeneralUtility::addInstance(BcryptPasswordHash::class, $bcryptFeMock);

        $silentConfigurationUpgradeService = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [$configurationManagerMock]
        );
        $this->expectException(ConfigurationChangedException::class);
        $this->expectExceptionCode(1379024938);
        $silentConfigurationUpgradeService->_call('migrateSaltedPasswordsSettings');
    }

    /**
     * @test
     */
    public function migrateCachingFrameworkCachesMigratesData(): void
    {
        $oldCacheConfigurations = [
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
        ];
        $newCacheConfigurations = [
            'rootline' => [
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
        ];
        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $configurationManagerMock->expects(self::atLeastOnce())->method('getLocalConfigurationValueByPath')->willReturnMap([
            ['SYS/caching/cacheConfigurations', $oldCacheConfigurations],
        ]);
        $configurationManagerMock->expects(self::atLeastOnce())->method('setLocalConfigurationValueByPath')
            ->with('SYS/caching/cacheConfigurations', $newCacheConfigurations);

        $this->expectException(ConfigurationChangedException::class);
        $this->expectExceptionCode(1379024938);

        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $configurationManagerMock);
        $silentConfigurationUpgradeServiceInstance->_call('migrateCachingFrameworkCaches');
    }

    /**
     * @test
     */
    public function migrateCachingFrameworkCachesDoesNotMigrateWithoutPrefix(): void
    {
        $oldCacheConfigurations = [
            'rootline' => [
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
        ];
        $configurationManagerMock = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $configurationManagerMock->expects(self::atLeastOnce())->method('getLocalConfigurationValueByPath')->willReturnMap([
            ['SYS/caching/cacheConfigurations', $oldCacheConfigurations],
        ]);
        $configurationManagerMock->expects(self::never())->method('setLocalConfigurationValueByPath');
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $configurationManagerMock);
        $silentConfigurationUpgradeServiceInstance->_call('migrateCachingFrameworkCaches');
    }
}
