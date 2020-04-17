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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache;
use TYPO3\CMS\Install\Service\Exception\ConfigurationChangedException;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SilentConfigurationUpgradeServiceTest extends UnitTestCase
{
    /**
     * @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Core\Package\UnitTestPackageManager A backup of unit test package manager
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

    /**
     * @param array $methods
     */
    protected function createConfigurationManagerWithMockedMethods(array $methods)
    {
        $this->configurationManager = $this->getMockBuilder(ConfigurationManager::class)
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * Dataprovider for configureBackendLoginSecurity
     *
     * @return array
     */
    public function configureBackendLoginSecurityLocalconfiguration(): array
    {
        return [
            ['', 'rsa', true, false],
            ['normal', 'rsa', true, true],
            ['rsa', 'normal', false, true],
        ];
    }

    /**
     * @test
     * @dataProvider configureBackendLoginSecurityLocalconfiguration
     * @param string $current
     * @param string $setting
     * @param bool $isPackageActive
     * @param bool $hasLocalConfig
     */
    public function configureBackendLoginSecurity($current, $setting, $isPackageActive, $hasLocalConfig)
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        /** @var $packageManager PackageManager|\PHPUnit\Framework\MockObject\MockObject */
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects(self::any())
            ->method('isPackageActive')
            ->willReturn($isPackageActive);
        ExtensionManagementUtility::setPackageManager($packageManager);

        $currentLocalConfiguration = [
            ['BE/loginSecurityLevel', $current]
        ];
        $closure = function () {
            throw new MissingArrayPathException('Path does not exist in array', 1538160231);
        };

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        if ($hasLocalConfig) {
            $this->configurationManager->expects(self::once())
                ->method('getLocalConfigurationValueByPath')
                ->willReturnMap($currentLocalConfiguration);
        } else {
            $this->configurationManager->expects(self::once())
                ->method('getLocalConfigurationValueByPath')
                ->willReturnCallback($closure);
        }
        $this->configurationManager->expects(self::once())
            ->method('setLocalConfigurationValueByPath')
            ->with(self::equalTo('BE/loginSecurityLevel'), self::equalTo($setting));

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('configureBackendLoginSecurity');
    }

    /**
     * Dataprovider for configureBackendLoginSecurity
     *
     * @return array
     */
    public function configureFrontendLoginSecurityLocalconfiguration(): array
    {
        return [
            ['', 'rsa', true, false],
            ['normal', 'rsa', true, true],
            ['rsa', 'normal', false, true],
        ];
    }

    /**
     * @test
     * @dataProvider configureFrontendLoginSecurityLocalconfiguration
     * @param string $current
     * @param string $setting
     * @param bool $isPackageActive
     * @param bool $hasLocalConfig
     */
    public function configureFrontendLoginSecurity($current, $setting, $isPackageActive, $hasLocalConfig)
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        /** @var $packageManager PackageManager|\PHPUnit\Framework\MockObject\MockObject */
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects(self::any())
            ->method('isPackageActive')
            ->willReturn($isPackageActive);
        ExtensionManagementUtility::setPackageManager($packageManager);

        $currentLocalConfiguration = [
            ['FE/loginSecurityLevel', $current]
        ];
        $closure = function () {
            throw new MissingArrayPathException('Path does not exist in array', 1476109311);
        };

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        if ($hasLocalConfig) {
            $this->configurationManager->expects(self::once())
                ->method('getLocalConfigurationValueByPath')
                ->willReturnMap($currentLocalConfiguration);
        } else {
            $this->configurationManager->expects(self::once())
                ->method('getLocalConfigurationValueByPath')
                ->willReturnCallback($closure);
        }
        if ($isPackageActive === false) {
            $this->configurationManager->expects(self::once())
                ->method('setLocalConfigurationValueByPath')
                ->with(self::equalTo('FE/loginSecurityLevel'), self::equalTo($setting));

            $this->expectException(ConfigurationChangedException::class);
        }

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('configureFrontendLoginSecurity');
    }

    /**
     * @test
     */
    public function removeObsoleteLocalConfigurationSettingsIfThereAreOldSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
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
            [$obsoleteLocalConfigurationSettings, true]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'removeLocalConfigurationKeysByPath',
            ]
        );
        $this->configurationManager->expects(self::exactly(1))
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
    public function doNotRemoveObsoleteLocalConfigurationSettingsIfThereAreNoOldSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
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
            [$obsoleteLocalConfigurationSettings, false]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'removeLocalConfigurationKeysByPath',
            ]
        );
        $this->configurationManager->expects(self::exactly(1))
            ->method('removeLocalConfigurationKeysByPath')
            ->willReturnMap($currentLocalConfiguration);

        $silentConfigurationUpgradeServiceInstance->_set('obsoleteLocalConfigurationSettings', $obsoleteLocalConfigurationSettings);
        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('removeObsoleteLocalConfigurationSettings');
    }

    /**
     * @test
     */
    public function doNotGenerateEncryptionKeyIfExists()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['SYS/encryptionKey', 'EnCrYpTiOnKeY']
        ];

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        $this->configurationManager->expects(self::exactly(1))
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
    public function generateEncryptionKeyIfNotExists()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $closure = function () {
            throw new MissingArrayPathException('Path does not exist in array', 1476109266);
        };

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        $this->configurationManager->expects(self::exactly(1))
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
                false
            ],
            'Old and unused settings removed' => [
                ['adapter' => 'curl'],
                [],
                true
            ],
            'Old and used settings changed' => [
                ['protocol_version' => '1.1'],
                ['HTTP/version' => '1.1'],
                true
            ],

            /** redirect options */
            'Redirects moved to default' => [
                ['follow_redirects' => true],
                [],
                true
            ],
            'Redirects moved #1' => [
                ['follow_redirects' => true, 'max_redirects' => 200, 'strict_redirects' => false],
                ['HTTP/allow_redirects' => ['max' => 200]],
                true
            ],
            'Redirects moved #2' => [
                ['follow_redirects' => false, 'max_redirects' => 200, 'strict_redirects' => false],
                ['HTTP/allow_redirects' => false],
                true
            ],
            'Redirects moved #3' => [
                ['follow_redirects' => true, 'max_redirects' => 400, 'strict_redirects' => 1],
                ['HTTP/allow_redirects' => ['max' => 400, 'strict' => true]],
                true
            ],

            /** Proxy settings */
            'Proxy host set' => [
                ['proxy_host' => 'vpn.myproxy.com'],
                ['HTTP/proxy' => 'http://vpn.myproxy.com'],
                true
            ],
            'Proxy host set + port' => [
                ['proxy_host' => 'vpn.myproxy.com', 'proxy_port' => 8080],
                ['HTTP/proxy' => 'http://vpn.myproxy.com:8080'],
                true
            ],
            'Proxy host set + port + verification' => [
                ['proxy_host' => 'vpn.myproxy.com', 'proxy_port' => 8080, 'proxy_auth_scheme' => 'basic', 'proxy_user' => 'myuser', 'proxy_password' => 'mysecret'],
                ['HTTP/proxy' => 'http://myuser:mysecret@vpn.myproxy.com:8080'],
                true
            ],

            /** SSL verification */
            'Only ssl_capath set, invalid migration' => [
                ['ssl_capath' => '/foo/bar/'],
                [],
                true
            ],
            'Verification activated, but only ssl_capath set, using default' => [
                ['ssl_verify_peer' => 1, 'ssl_capath' => '/foo/bar/'],
                [],
                true
            ],
            'Verification activated, with ssl_capath and ssl_cafile set' => [
                ['ssl_verify_peer' => 1, 'ssl_capath' => '/foo/bar/', 'ssl_cafile' => 'supersecret.crt'],
                ['HTTP/verify' => '/foo/bar/supersecret.crt'],
                true
            ],

            /** SSL key + passphrase */
            'SSL key certification' => [
                ['ssl_local_cert' => '/foo/bar/supersecret.key'],
                ['HTTP/ssl_key' => '/foo/bar/supersecret.key'],
                true
            ],
            'SSL key certification + passphrase' => [
                ['ssl_local_cert' => '/foo/bar/supersecret.key', 'ssl_passphrase' => 'donotcopypasteme'],
                ['HTTP/ssl_key' => ['/foo/bar/supersecret.key', 'donotcopypasteme']],
                true
            ],
            'SSL key passphrase only - no migration' => [
                ['ssl_passphrase' => 'donotcopypasteme'],
                [],
                true
            ],
        ];
    }

    /**
     * @test
     * @dataProvider httpSettingsMappingDataProvider
     * @param array $currentLocalConfiguration
     * @param array $newSettings
     * @param bool $localConfigurationNeedsUpdate
     */
    public function transferHttpSettingsIfSet($currentLocalConfiguration, $newSettings, $localConfigurationNeedsUpdate)
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
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
                'getLocalConfiguration'
            ]
        );

        $this->configurationManager->expects(self::any())
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
    public function disableImageMagickDetailSettingsIfImageMagickIsDisabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
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
            ['GFX/im_path_lzw', ''],
            ['GFX/imagefile_ext', 'gif,jpg,png'],
            ['GFX/thumbnails', 0]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects(self::exactly(5))
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
    public function doNotDisableImageMagickDetailSettingsIfImageMagickIsEnabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
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
            ['GFX/im_path_lzw', ''],
            ['GFX/imagefile_ext', 'gif,jpg,jpeg,png'],
            ['GFX/thumbnails', 0]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects(self::exactly(5))
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
    public function setImageMagickDetailSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
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
                ]
            ]);

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('setImageMagickDetailSettings');
    }

    /**
     * @test
     */
    public function doNotSetImageMagickDetailSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
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
     * @param bool $expectedMigratedValue
     */
    public function migratesGraphicsProcessorEffects($currentValue, $expectedMigratedValue)
    {
        /** @var ConfigurationManager|\Prophecy\Prophecy\ObjectProphecy */
        $configurationManager = $this->prophesize(ConfigurationManager::class);
        $configurationManager->getLocalConfigurationValueByPath('GFX/processor')->willReturn('GraphicsMagick');
        $configurationManager->getLocalConfigurationValueByPath('GFX/processor_allowTemporaryMasksAsPng')->willReturn(false);
        $configurationManager->getLocalConfigurationValueByPath('GFX/processor_effects')->willReturn($currentValue);
        $configurationManager->setLocalConfigurationValuesByPathValuePairs([
            'GFX/processor_effects' => $expectedMigratedValue,
        ])->shouldBeCalled();

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeService = new SilentConfigurationUpgradeService($configurationManager->reveal());
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
    public function migrateNonExistingLangDebug()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
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

        $this->configurationManager->expects(self::exactly(1))
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
    public function migrateExistingLangDebug()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['BE/lang/debug', false]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );

        $this->configurationManager->expects(self::exactly(1))
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
    public function migrateCacheHashOptions()
    {
        $oldConfig = [
            'FE/cHashOnlyForParameters' => 'foo,bar',
            'FE/cHashExcludedParameters' => 'bar,foo',
            'FE/cHashRequiredParameters' => 'bar,baz',
            'FE/cHashExcludedParametersIfEmpty' => '*'
        ];

        /** @var ConfigurationManager|ObjectProphecy $configurationManager */
        $configurationManager = $this->prophesize(ConfigurationManager::class);

        foreach ($oldConfig as $key => $value) {
            $configurationManager->getLocalConfigurationValueByPath($key)
                ->shouldBeCalled()
                ->willReturn($value);
        }

        $configurationManager->setLocalConfigurationValuesByPathValuePairs(Argument::cetera())->shouldBeCalled();
        $configurationManager->removeLocalConfigurationKeysByPath(Argument::cetera())->shouldBeCalled();

        $this->expectException(ConfigurationChangedException::class);
        $this->expectExceptionCode(1379024938);

        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $configurationManager->reveal());
        $silentConfigurationUpgradeServiceInstance->_call('migrateCacheHashOptions');
    }

    /**
     * @test
     */
    public function migrateSaltedPasswordsSettingsDoesNothingIfExtensionConfigsAreNotSet()
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        $configurationManagerException = new MissingArrayPathException('Path does not exist in array', 1533989414);
        $configurationManagerProphecy->getLocalConfigurationValueByPath('EXTENSIONS/saltedpasswords')
            ->shouldBeCalled()->willThrow($configurationManagerException);
        $configurationManagerProphecy->setLocalConfigurationValuesByPathValuePairs(Argument::cetera())
            ->shouldNotBeCalled();
        $silentConfigurationUpgradeService = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [$configurationManagerProphecy->reveal()]
        );
        $silentConfigurationUpgradeService->_call('migrateSaltedPasswordsSettings');
    }

    /**
     * @test
     */
    public function migrateSaltedPasswordsSettingsDoesNothingIfExtensionConfigsAreEmpty()
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        $configurationManagerProphecy->getLocalConfigurationValueByPath('EXTENSIONS/saltedpasswords')
            ->shouldBeCalled()->willReturn([]);
        $configurationManagerProphecy->setLocalConfigurationValuesByPathValuePairs(Argument::cetera())
            ->shouldNotBeCalled();
        $silentConfigurationUpgradeService = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [$configurationManagerProphecy->reveal()]
        );
        $silentConfigurationUpgradeService->_call('migrateSaltedPasswordsSettings');
    }

    /**
     * @test
     */
    public function migrateSaltedPasswordsSettingsRemovesExtensionsConfigAndSetsNothingElseIfArgon2iIsAvailable()
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        $configurationManagerException = new MissingArrayPathException('Path does not exist in array', 1533989428);
        $configurationManagerProphecy->getLocalConfigurationValueByPath('EXTENSIONS/saltedpasswords')
            ->shouldBeCalled()->willReturn(['thereIs' => 'something']);
        $argonBeProphecy = $this->prophesize(Argon2iPasswordHash::class);
        $argonBeProphecy->isAvailable()->shouldBeCalled()->willReturn(true);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonBeProphecy->reveal());
        $argonFeProphecy = $this->prophesize(Argon2iPasswordHash::class);
        $argonFeProphecy->isAvailable()->shouldBeCalled()->willReturn(true);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonFeProphecy->reveal());
        $configurationManagerProphecy->removeLocalConfigurationKeysByPath(['EXTENSIONS/saltedpasswords'])
            ->shouldBeCalled();
        $silentConfigurationUpgradeService = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [$configurationManagerProphecy->reveal()]
        );
        $this->expectException(ConfigurationChangedException::class);
        $this->expectExceptionCode(1379024938);
        $silentConfigurationUpgradeService->_call('migrateSaltedPasswordsSettings');
    }

    /**
     * @test
     */
    public function migrateSaltedPasswordsSetsSpecificHashMethodIfArgon2idAndArgon2iIsNotAvailable()
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        $configurationManagerProphecy->getLocalConfigurationValueByPath('EXTENSIONS/saltedpasswords')
            ->shouldBeCalled()->willReturn(['thereIs' => 'something']);
        $argon2idBeProphecy = $this->prophesize(Argon2idPasswordHash::class);
        $argon2idBeProphecy->isAvailable()->shouldBeCalled()->willReturn(false);
        GeneralUtility::addInstance(Argon2idPasswordHash::class, $argon2idBeProphecy->reveal());
        $argonBeProphecy = $this->prophesize(Argon2iPasswordHash::class);
        $argonBeProphecy->isAvailable()->shouldBeCalled()->willReturn(false);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonBeProphecy->reveal());
        $bcryptBeProphecy = $this->prophesize(BcryptPasswordHash::class);
        $bcryptBeProphecy->isAvailable()->shouldBeCalled()->willReturn(true);
        GeneralUtility::addInstance(BcryptPasswordHash::class, $bcryptBeProphecy->reveal());
        $argon2idFeProphecy = $this->prophesize(Argon2idPasswordHash::class);
        $argon2idFeProphecy->isAvailable()->shouldBeCalled()->willReturn(false);
        GeneralUtility::addInstance(Argon2idPasswordHash::class, $argon2idFeProphecy->reveal());
        $argonFeProphecy = $this->prophesize(Argon2iPasswordHash::class);
        $argonFeProphecy->isAvailable()->shouldBeCalled()->willReturn(false);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonFeProphecy->reveal());
        $bcryptFeProphecy = $this->prophesize(BcryptPasswordHash::class);
        $bcryptFeProphecy->isAvailable()->shouldBeCalled()->willReturn(true);
        GeneralUtility::addInstance(BcryptPasswordHash::class, $bcryptFeProphecy->reveal());
        $configurationManagerProphecy->setLocalConfigurationValuesByPathValuePairs([
            'BE/passwordHashing/className' => BcryptPasswordHash::class,
            'FE/passwordHashing/className' => BcryptPasswordHash::class,
        ])->shouldBeCalled();
        $configurationManagerProphecy->removeLocalConfigurationKeysByPath(['EXTENSIONS/saltedpasswords'])
            ->shouldBeCalled();
        $silentConfigurationUpgradeService = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [$configurationManagerProphecy->reveal()]
        );
        $this->expectException(ConfigurationChangedException::class);
        $this->expectExceptionCode(1379024938);
        $silentConfigurationUpgradeService->_call('migrateSaltedPasswordsSettings');
    }

    /**
     * @test
     */
    public function migrateCachingFrameworkCachesMigratesData()
    {
        $oldCacheConfigurations = [
            'cache_rootline' => [
                'frontend' => VariableFrontend::class,
                'backend' => Typo3DatabaseBackend::class,
                'options' => [
                    'defaultLifetime' => 2592000,
                ],
                'groups' => ['pages']
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
                'groups' => ['pages']
            ],
            'fluid_template' => [
                'backend' => SimpleFileBackend::class,
                'frontend' => FluidTemplateCache::class,
                'groups' => ['system'],
            ],
        ];
        /** @var ConfigurationManager|ObjectProphecy $configurationManager */
        $configurationManager = $this->prophesize(ConfigurationManager::class);
        $configurationManager->getLocalConfigurationValueByPath('SYS/caching/cacheConfigurations')
            ->shouldBeCalled()
            ->willReturn($oldCacheConfigurations);

        $configurationManager->setLocalConfigurationValueByPath('SYS/caching/cacheConfigurations', $newCacheConfigurations)->shouldBeCalled();

        $this->expectException(ConfigurationChangedException::class);
        $this->expectExceptionCode(1379024938);

        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $configurationManager->reveal());
        $silentConfigurationUpgradeServiceInstance->_call('migrateCachingFrameworkCaches');
    }

    /**
     * @test
     */
    public function migrateCachingFrameworkCachesDoesNotMigrateWithoutPrefix()
    {
        $oldCacheConfigurations = [
            'rootline' => [
                'frontend' => VariableFrontend::class,
                'backend' => Typo3DatabaseBackend::class,
                'options' => [
                    'defaultLifetime' => 2592000,
                ],
                'groups' => ['pages']
            ],
            'fluid_template' => [
                'backend' => SimpleFileBackend::class,
                'frontend' => FluidTemplateCache::class,
                'groups' => ['system'],
            ],
        ];
        /** @var ConfigurationManager|ObjectProphecy $configurationManager */
        $configurationManager = $this->prophesize(ConfigurationManager::class);
        $configurationManager->getLocalConfigurationValueByPath('SYS/caching/cacheConfigurations')
            ->shouldBeCalled()
            ->willReturn($oldCacheConfigurations);

        $configurationManager->setLocalConfigurationValueByPath(Argument::cetera())->shouldNotBeCalled();

        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $configurationManager->reveal());
        $silentConfigurationUpgradeServiceInstance->_call('migrateCachingFrameworkCaches');
    }
}
