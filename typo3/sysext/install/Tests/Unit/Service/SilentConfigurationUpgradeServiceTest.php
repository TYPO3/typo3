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
     * @param array $methods
     */
    protected function createConfigurationManagerWithMockedMethods(array $methods)
    {
        $this->configurationManager = $this->getMock(
            ConfigurationManager::class,
            $methods
        );
    }

    /**
     * Dataprovider for configureBackendLoginSecurity
     *
     * @return array
     */
    public function configureBackendLoginSecurityLocalconfiguration()
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
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        /** @var $packageManager PackageManager|\PHPUnit_Framework_MockObject_MockObject */
        $packageManager = $this->getMock(PackageManager::class, [], [], '', false);
        $packageManager->expects($this->any())
            ->method('isPackageActive')
            ->will($this->returnValue($isPackageActive));
        ExtensionManagementUtility::setPackageManager($packageManager);

        $currentLocalConfiguration = [
            ['BE/loginSecurityLevel', $current]
        ];
        $closure = function () {
            throw new \RuntimeException('Path does not exist in array', 1341397869);
        };

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
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

        $this->setExpectedException(RedirectException::class);

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
        $this->configurationManager->expects($this->exactly(1))
            ->method('removeLocalConfigurationKeysByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));

        $this->setExpectedException(RedirectException::class);

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
    public function configureSaltedPasswordsWithDefaultConfiguration()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );
        $config = 'a:2:{s:3:"BE.";a:3:{s:11:"forceSalted";i:0;s:15:"onlyAuthService";i:0;s:12:"updatePasswd";i:1;}s:3:"FE.";a:4:{s:7:"enabled";i:0;s:11:"forceSalted";i:0;s:15:"onlyAuthService";i:0;s:12:"updatePasswd";i:1;}}';
        $defaultConfiguration = [];
        $defaultConfiguration['EXT']['extConf']['saltedpasswords'] = $config;

        $closure = function () {
            throw new \RuntimeException('Path does not exist in array', 1341397869);
        };

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getDefaultConfiguration',
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getDefaultConfiguration')
            ->will($this->returnValue($defaultConfiguration));
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnCallback($closure));
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValueByPath')
            ->with($this->equalTo('EXT/extConf/saltedpasswords'), $this->equalTo($config));

        $this->setExpectedException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('configureSaltedPasswords');
    }

    /**
     * @test
     */
    public function configureSaltedPasswordsWithExtensionConfigurationBeEnabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );
        $config = 'a:2:{s:3:"BE.";a:1:{s:21:"saltedPWHashingMethod";}s:3:"FE.";a:2:{s:7:"enabled";i:0;s:11:"forceSalted";i:0;}}';
        $defaultConfiguration = [];
        $defaultConfiguration['EXT']['extConf']['saltedpasswords'] = $config;

        $currentLocalConfiguration = [
            ['EXT/extConf/saltedpasswords', 'a:2:{s:3:"BE.";a:1:{s:7:"enabled";i:1;}s:3:"FE.";a:1:{s:7:"enabled";i:0;}}']
        ];
        $newConfig = 'a:2:{s:3:"BE.";a:0:{}s:3:"FE.";a:1:{s:7:"enabled";i:0;}}';
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getDefaultConfiguration',
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getDefaultConfiguration')
            ->will($this->returnValue($defaultConfiguration));
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValueByPath')
            ->with($this->equalTo('EXT/extConf/saltedpasswords'), $this->equalTo($newConfig));

        $this->setExpectedException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('configureSaltedPasswords');
    }

    /**
     * @test
     */
    public function configureSaltedPasswordsWithExtensionConfigurationBeNotEnabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );
        $config = 'a:2:{s:3:"BE.";a:1:{s:15:"onlyAuthService";i:0;}s:3:"FE.";a:2:{s:7:"enabled";i:0;s:11:"forceSalted";i:0;}}';
        $defaultConfiguration = [];
        $defaultConfiguration['EXT']['extConf']['saltedpasswords'] = $config;

        $currentLocalConfiguration = [
            ['EXT/extConf/saltedpasswords', 'a:2:{s:3:"BE.";a:2:{s:7:"enabled";i:0;s:12:"updatePasswd";i:1;}s:3:"FE.";a:1:{s:7:"enabled";i:0;}}']
        ];
        $newConfig = 'a:2:{s:3:"BE.";a:1:{s:15:"onlyAuthService";i:0;}s:3:"FE.";a:1:{s:7:"enabled";i:0;}}';
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getDefaultConfiguration',
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getDefaultConfiguration')
            ->will($this->returnValue($defaultConfiguration));
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValueByPath')
            ->with($this->equalTo('EXT/extConf/saltedpasswords'), $this->equalTo($newConfig));

        $this->setExpectedException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('configureSaltedPasswords');
    }

    /**
     * @test
     */
    public function noProxyAuthSchemeSetInLocalConfiguration()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $closure = function () {
            throw new \RuntimeException('Path does not exist in array', 1341397869);
        };

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'removeLocalConfigurationKeysByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnCallback($closure));
        $this->configurationManager->expects($this->never())
            ->method('removeLocalConfigurationKeysByPath');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('setProxyAuthScheme');
    }

    /**
     * @test
     */
    public function proxyAuthSchemeIsDigest()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['HTTP/proxy_auth_scheme', 'digest']
        ];

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'removeLocalConfigurationKeysByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('removeLocalConfigurationKeysByPath');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('setProxyAuthScheme');
    }

    /**
     * @test
     */
    public function proxyAuthSchemeIsBasic()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['HTTP/proxy_auth_scheme', 'basic']
        ];

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'removeLocalConfigurationKeysByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->once())
            ->method('removeLocalConfigurationKeysByPath')
            ->with($this->equalTo(['HTTP/proxy_auth_scheme']));

        $this->setExpectedException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('setProxyAuthScheme');
    }

    /**
     * @test
     */
    public function doNotGenerateEncryptionKeyIfExists()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
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
            ['dummy'],
            [],
            '',
            false
        );

        $closure = function () {
            throw new \RuntimeException('Path does not exist in array', 1341397869);
        };

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnCallback($closure));
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValueByPath')
            ->with($this->equalTo('SYS/encryptionKey'), $this->isType('string'));

        $this->setExpectedException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('generateEncryptionKeyIfNeeded');
    }

    /**
     * Dataprovider for transferDeprecatedCurlSettings
     *
     * @return array
     */
    public function curlProxySettingsToHttpSettingsMapping()
    {
        return [
            ['http://proxy:3128/', 'proxy', '3128'],
            ['http://proxy:3128', 'proxy', '3128'],
            ['proxy:3128', 'proxy', '3128'],
            ['https://proxy:3128/', 'proxy', '3128'],
        ];
    }

    /**
     * @test
     * @dataProvider curlProxySettingsToHttpSettingsMapping
     * @param string $curlProxyServer
     * @param string $proxyHost
     * @param string $proxyPort
     */
    public function transferDeprecatedCurlSettings($curlProxyServer, $proxyHost, $proxyPort)
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['SYS/curlProxyServer',  $curlProxyServer],
            ['HTTP/proxy_host', ''],
            ['SYS/curlProxyUserPass',  ''],
            ['HTTP/proxy_user', ''],
            ['SYS/curlUse', false]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
                'getConfigurationValueByPath'
            ]
        );
        $this->configurationManager->expects($this->exactly(5))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->exactly(2))
            ->method('setLocalConfigurationValueByPath')
            ->withConsecutive(
                ['HTTP/proxy_host', $proxyHost],
                ['HTTP/proxy_port', $proxyPort]
            );

        $this->setExpectedException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('transferDeprecatedCurlSettings');
    }

    /**
     * @test
     */
    public function curlProxyServerDoesNotOverwriteHttpSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['SYS/curlProxyServer', 'http://proxyOld:3128/'],
            ['SYS/curlProxyUserPass', 'userOld:passOld'],
            ['HTTP/proxy_host', 'proxyNew'],
            ['HTTP/proxy_port', '3128'],
            ['HTTP/proxy_user', 'userNew'],
            ['HTTP/proxy_pass', 'passNew'],
            ['SYS/curlUse', false]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
                'getConfigurationValueByPath'
            ]
        );
        $this->configurationManager->expects($this->exactly(5))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('setLocalConfigurationValueByPath');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('transferDeprecatedCurlSettings');
    }

    /**
     * @test
     */
    public function curlAdapterUsedIfCurlUse()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['SYS/curlProxyServer', ''],
            ['SYS/curlProxyUserPass', ''],
            ['HTTP/proxy_host', 'proxyNew'],
            ['HTTP/proxy_user', 'userNew'],
            ['SYS/curlUse', true]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(5))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValueByPath')
            ->withConsecutive(
                ['HTTP/adapter', 'curl']
            );

        $this->setExpectedException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('transferDeprecatedCurlSettings');
    }

    /**
     * @test
     */
    public function disableImageMagickIfImageProcessingIsDisabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/image_processing', 0],
            ['GFX/im', 1],
            ['GFX/gdlib', 0]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects($this->exactly(3))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValuesByPathValuePairs')
            ->withConsecutive(
                [['GFX/im' => 0]]
            );

        $this->setExpectedException(\TYPO3\CMS\Install\Controller\Exception\RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('disableImageMagickAndGdlibIfImageProcessingIsDisabled');
    }

    /**
     * @test
     */
    public function disableGdlibIfImageProcessingIsDisabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/image_processing', 0],
            ['GFX/im', 0],
            ['GFX/gdlib', 1]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects($this->exactly(3))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValuesByPathValuePairs')
            ->withConsecutive(
                [['GFX/gdlib' => 0]]
            );

        $this->setExpectedException(\TYPO3\CMS\Install\Controller\Exception\RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('disableImageMagickAndGdlibIfImageProcessingIsDisabled');
    }

    /**
     * @test
     */
    public function doNotDisableImageMagickAndGdlibIfImageProcessingIsEnabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/image_processing', 1],
            ['GFX/im', 1],
            ['GFX/gdlib', 1]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects($this->exactly(3))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->never())
            ->method('setLocalConfigurationValuesByPathValuePairs');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('disableImageMagickAndGdlibIfImageProcessingIsDisabled');
    }

    /**
     * @test
     */
    public function disableImageMagickIfDefaultImageProcessingIsDisabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentDefaultConfiguration = [
            ['GFX/image_processing', 0],
        ];
        $closure = function ($param) {
            switch ($param) {
                case 'GFX/im':
                    return '1';
                    break;
                case 'GFX/gdlib':
                    return '0';
                    break;
                default:
                    throw new \RuntimeException('Path does not exist in array', 1341397869);
            }
        };

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects($this->exactly(3))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnCallback($closure));
        $this->configurationManager->expects($this->exactly(1))
            ->method('getDefaultConfigurationValueByPath')
            ->will($this->returnValueMap($currentDefaultConfiguration));
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValuesByPathValuePairs')
            ->withConsecutive(
                [['GFX/im' => 0]]
            );

        $this->setExpectedException(RedirectException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('disableImageMagickAndGdlibIfImageProcessingIsDisabled');
    }

    /**
     * @test
     */
    public function disableImageMagickDetailSettingsIfImageMagickIsDisabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
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
        $this->configurationManager->expects($this->exactly(5))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValuesByPathValuePairs')
            ->withConsecutive(
                [['GFX/imagefile_ext' => 'gif,jpg,jpeg,png']]
            );

        $this->setExpectedException(RedirectException::class);

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
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/im_version_5', 'gm'],
            ['GFX/im_mask_temp_ext_gif', 0],
            ['GFX/im_v5effects', 0]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects($this->exactly(3))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValuesByPathValuePairs')
            ->withConsecutive(
                [['GFX/im_mask_temp_ext_gif' => 1,
                            'GFX/im_v5effects' => -1]]
            );

        $this->setExpectedException(RedirectException::class);

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
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/im_version_5', ''],
            ['GFX/im_mask_temp_ext_gif', 0],
            ['GFX/im_v5effects', 0]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
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
