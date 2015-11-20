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
        $packageManager = $this->getMock(PackageManager::class, array(), array(), '', false);
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
    public function configureSaltedPasswordsWithDefaultConfiguration()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            array('dummy'),
            array(),
            '',
            false
        );
        $config = 'a:2:{s:3:"BE.";a:3:{s:11:"forceSalted";i:0;s:15:"onlyAuthService";i:0;s:12:"updatePasswd";i:1;}s:3:"FE.";a:4:{s:7:"enabled";i:0;s:11:"forceSalted";i:0;s:15:"onlyAuthService";i:0;s:12:"updatePasswd";i:1;}}';
        $defaultConfiguration = array();
        $defaultConfiguration['EXT']['extConf']['saltedpasswords'] = $config;

        $closure = function () {
            throw new \RuntimeException('Path does not exist in array', 1341397869);
        };

        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getDefaultConfiguration',
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            )
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
            array('dummy'),
            array(),
            '',
            false
        );
        $config = 'a:2:{s:3:"BE.";a:1:{s:21:"saltedPWHashingMethod";}s:3:"FE.";a:2:{s:7:"enabled";i:0;s:11:"forceSalted";i:0;}}';
        $defaultConfiguration = array();
        $defaultConfiguration['EXT']['extConf']['saltedpasswords'] = $config;

        $currentLocalConfiguration = array(
            array('EXT/extConf/saltedpasswords', 'a:2:{s:3:"BE.";a:1:{s:7:"enabled";i:1;}s:3:"FE.";a:1:{s:7:"enabled";i:0;}}')
        );
        $newConfig = 'a:2:{s:3:"BE.";a:0:{}s:3:"FE.";a:1:{s:7:"enabled";i:0;}}';
        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getDefaultConfiguration',
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            )
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
            array('dummy'),
            array(),
            '',
            false
        );
        $config = 'a:2:{s:3:"BE.";a:1:{s:15:"onlyAuthService";i:0;}s:3:"FE.";a:2:{s:7:"enabled";i:0;s:11:"forceSalted";i:0;}}';
        $defaultConfiguration = array();
        $defaultConfiguration['EXT']['extConf']['saltedpasswords'] = $config;

        $currentLocalConfiguration = array(
            array('EXT/extConf/saltedpasswords', 'a:2:{s:3:"BE.";a:2:{s:7:"enabled";i:0;s:12:"updatePasswd";i:1;}s:3:"FE.";a:1:{s:7:"enabled";i:0;}}')
        );
        $newConfig = 'a:2:{s:3:"BE.";a:1:{s:15:"onlyAuthService";i:0;}s:3:"FE.";a:1:{s:7:"enabled";i:0;}}';
        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getDefaultConfiguration',
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            )
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
                'removeLocalConfigurationKeysByPath',
            )
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
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('HTTP/proxy_auth_scheme', 'digest')
        );

        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'removeLocalConfigurationKeysByPath',
            )
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
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('HTTP/proxy_auth_scheme', 'basic')
        );

        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'removeLocalConfigurationKeysByPath',
            )
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->once())
            ->method('removeLocalConfigurationKeysByPath')
            ->with($this->equalTo(array('HTTP/proxy_auth_scheme')));

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
        return array(
            array('http://proxy:3128/', 'proxy', '3128'),
            array('http://proxy:3128', 'proxy', '3128'),
            array('proxy:3128', 'proxy', '3128'),
            array('https://proxy:3128/', 'proxy', '3128'),
        );
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
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('SYS/curlProxyServer',  $curlProxyServer),
            array('HTTP/proxy_host', ''),
            array('SYS/curlProxyUserPass',  ''),
            array('HTTP/proxy_user', ''),
            array('SYS/curlUse', false)
        );
        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
                'getConfigurationValueByPath'
            )
        );
        $this->configurationManager->expects($this->exactly(5))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->exactly(2))
            ->method('setLocalConfigurationValueByPath')
            ->withConsecutive(
                array('HTTP/proxy_host', $proxyHost),
                array('HTTP/proxy_port', $proxyPort)
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
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('SYS/curlProxyServer', 'http://proxyOld:3128/'),
            array('SYS/curlProxyUserPass', 'userOld:passOld'),
            array('HTTP/proxy_host', 'proxyNew'),
            array('HTTP/proxy_port', '3128'),
            array('HTTP/proxy_user', 'userNew'),
            array('HTTP/proxy_pass', 'passNew'),
            array('SYS/curlUse', false)
        );
        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
                'getConfigurationValueByPath'
            )
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
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('SYS/curlProxyServer', ''),
            array('SYS/curlProxyUserPass', ''),
            array('HTTP/proxy_host', 'proxyNew'),
            array('HTTP/proxy_user', 'userNew'),
            array('SYS/curlUse', true)
        );
        $this->createConfigurationManagerWithMockedMethods(
            array(
                'getLocalConfigurationValueByPath',
                'getConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            )
        );
        $this->configurationManager->expects($this->exactly(5))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValueByPath')
            ->withConsecutive(
                array('HTTP/adapter', 'curl')
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
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('GFX/image_processing', 0),
            array('GFX/im', 1),
            array('GFX/gdlib', 0)
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
                array(array('GFX/im' => 0))
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
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('GFX/image_processing', 0),
            array('GFX/im', 0),
            array('GFX/gdlib', 1)
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
                array(array('GFX/gdlib' => 0))
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
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('GFX/image_processing', 1),
            array('GFX/im', 1),
            array('GFX/gdlib', 1)
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
            array('dummy'),
            array(),
            '',
            false
        );

        $currentDefaultConfiguration = array(
            array('GFX/image_processing', 0),
        );
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
            array(
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            )
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
                array(array('GFX/im' => 0))
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
            array('GFX/im_version_5', 'gm'),
            array('GFX/im_mask_temp_ext_gif', 0),
            array('GFX/im_v5effects', 0)
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
                array(array('GFX/im_mask_temp_ext_gif' => 1,
                            'GFX/im_v5effects' => -1))
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
            array('dummy'),
            array(),
            '',
            false
        );

        $currentLocalConfiguration = array(
            array('GFX/im_version_5', ''),
            array('GFX/im_mask_temp_ext_gif', 0),
            array('GFX/im_v5effects', 0)
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
