<?php
namespace TYPO3\CMS\Install\Service;

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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Install\Controller\Exception\RedirectException;

/**
 * Test case
 */
class SilentConfigurationUpgradeServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Core\Package\UnitTestPackageManager A backup of unit test package manager
     */
    protected $backupPackageManager;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->backupPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
    }

    /**
     * Tear down
     */
    protected function tearDown()
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
    public function configureBackendLoginSecurityLocalconfiguration()
    {
        return array(
            array('', 'rsa', true, false),
            array('normal', 'rsa', true, true),
            array('rsa', 'normal', false, true),
        );
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
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            array('dummy'),
            array(),
            '',
            false
        );

        /** @var $packageManager PackageManager|\PHPUnit_Framework_MockObject_MockObject */
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->any())
            ->method('isPackageActive')
            ->will($this->returnValue($isPackageActive));
        ExtensionManagementUtility::setPackageManager($packageManager);

        $currentLocalConfiguration = array(
            array('BE/loginSecurityLevel', $current)
        );
        $closure = function () {
            throw new \RuntimeException('Path does not exist in array', 1341397869);
        };

        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            )
        );
        if ($hasLocalConfig) {
            $this->configurationManager->expects($this->once())
                ->method('getLocalConfigurationValueByPath')
                ->will($this->returnValueMap($currentLocalConfiguration));
        } else {
            $this->configurationManager->expects($this->once())
                ->method('getLocalConfigurationValueByPath')
                ->will($this->returnCallback($closure));
        }
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValueByPath')
            ->with($this->equalTo('BE/loginSecurityLevel'), $this->equalTo($setting));

        $this->expectException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('configureBackendLoginSecurity');
    }

    /**
     * @test
     */
    public function removeObsoleteLocalConfigurationSettingsIfThereAreOldSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            array('dummy'),
            array(),
            '',
            false
        );

        $obsoleteLocalConfigurationSettings = array(
            'SYS/form_enctype',
        );

        $currentLocalConfiguration = array(
            array($obsoleteLocalConfigurationSettings, true)
        );
        $this->createConfigurationManagerWithMockedMethods(
            array(
                'removeLocalConfigurationKeysByPath',
            )
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('removeLocalConfigurationKeysByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));

        $this->expectException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('obsoleteLocalConfigurationSettings', $obsoleteLocalConfigurationSettings);
        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('removeObsoleteLocalConfigurationSettings');
    }

    /**
     * @test
     */
    public function doNotRemoveObsoleteLocalConfigurationSettingsIfThereAreNoOldSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            array('dummy'),
            array(),
            '',
            false
        );

        $obsoleteLocalConfigurationSettings = array(
            'SYS/form_enctype',
        );

        $currentLocalConfiguration = array(
            array($obsoleteLocalConfigurationSettings, false)
        );
        $this->createConfigurationManagerWithMockedMethods(
            array(
                'removeLocalConfigurationKeysByPath',
            )
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('removeLocalConfigurationKeysByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));

        $silentConfigurationUpgradeServiceInstance->_set('obsoleteLocalConfigurationSettings', $obsoleteLocalConfigurationSettings);
        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('removeObsoleteLocalConfigurationSettings');
    }

    /**
     * @test
     */
    public function doNotGenerateEncryptionKeyIfExists()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('SYS/encryptionKey', 'EnCrYpTiOnKeY')
        );

        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            )
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('setLocalConfigurationValueByPath');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('generateEncryptionKeyIfNeeded');
    }

    /**
     * @test
     */
    public function generateEncryptionKeyIfNotExists()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            array('dummy'),
            array(),
            '',
            false
        );

        $closure = function () {
            throw new \RuntimeException('Path does not exist in array', 1341397869);
        };

        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            )
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnCallback($closure));
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValueByPath')
            ->with($this->equalTo('SYS/encryptionKey'), $this->isType('string'));

        $this->expectException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('generateEncryptionKeyIfNeeded');
    }

    /**
     * Data provider for transferHttpSettings
     *
     * @return array
     */
    public function httpSettingsMappingDataProvider()
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
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            array('dummy'),
            array(),
            '',
            false
        );

        $this->createConfigurationManagerWithMockedMethods(
            array(
                'setLocalConfigurationValuesByPathValuePairs',
                'removeLocalConfigurationKeysByPath',
                'getLocalConfiguration'
            )
        );

        $this->configurationManager->expects($this->any())
            ->method('getLocalConfiguration')
            ->willReturn(['HTTP' => $currentLocalConfiguration]);
        if ($localConfigurationNeedsUpdate) {
            if (!empty($newSettings)) {
                $this->configurationManager->expects($this->once())
                    ->method('setLocalConfigurationValuesByPathValuePairs')
                    ->with($newSettings);
            }
            $this->configurationManager->expects($this->atMost(1))->method('removeLocalConfigurationKeysByPath');
        }

        if ($localConfigurationNeedsUpdate) {
            $this->expectException(RedirectException::class);
        }

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('transferHttpSettings');
    }

    /**
     * @test
     */
    public function disableImageMagickDetailSettingsIfImageMagickIsDisabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('GFX/im', 0),
            array('GFX/im_path', ''),
            array('GFX/im_path_lzw', ''),
            array('GFX/imagefile_ext', 'gif,jpg,png'),
            array('GFX/thumbnails', 0)
        );
        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            )
        );
        $this->configurationManager->expects($this->exactly(5))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValuesByPathValuePairs')
            ->withConsecutive(
                array(array('GFX/imagefile_ext' => 'gif,jpg,jpeg,png'))
            );

        $this->expectException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('disableImageMagickDetailSettingsIfImageMagickIsDisabled');
    }

    /**
     * @test
     */
    public function doNotDisableImageMagickDetailSettingsIfImageMagickIsEnabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('GFX/im', 1),
            array('GFX/im_path', ''),
            array('GFX/im_path_lzw', ''),
            array('GFX/imagefile_ext', 'gif,jpg,jpeg,png'),
            array('GFX/thumbnails', 0)
        );
        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            )
        );
        $this->configurationManager->expects($this->exactly(5))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->never())
            ->method('setLocalConfigurationValuesByPathValuePairs');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('disableImageMagickDetailSettingsIfImageMagickIsDisabled');
    }

    /**
     * @test
     */
    public function setImageMagickDetailSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('GFX/processor', 'GraphicsMagick'),
            array('GFX/processor_allowTemporaryMasksAsPng', 1),
            array('GFX/processor_effects', 0)
        );
        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            )
        );
        $this->configurationManager->expects($this->exactly(3))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValuesByPathValuePairs')
            ->withConsecutive(
                array(array('GFX/processor_allowTemporaryMasksAsPng' => 0,
                            'GFX/processor_effects' => -1))
            );

        $this->expectException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('setImageMagickDetailSettings');
    }

    /**
     * @test
     */
    public function doNotSetImageMagickDetailSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('GFX/processor', ''),
            array('GFX/processor_allowTemporaryMasksAsPng', 0),
            array('GFX/processor_effects', 0)
        );
        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            )
        );
        $this->configurationManager->expects($this->exactly(3))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->never())
            ->method('setLocalConfigurationValuesByPathValuePairs');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('setImageMagickDetailSettings');
    }
}
