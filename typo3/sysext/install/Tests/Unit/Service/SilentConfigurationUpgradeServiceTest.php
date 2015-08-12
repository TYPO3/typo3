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

use TYPO3\CMS\Install\Controller\Exception\RedirectException;

/**
 * Test case
 */
class SilentConfigurationUpgradeServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $configurationManager;

	/**
	 * @param array $methods
	 */
	protected function createConfigurationManagerWithMockedMethods(array $methods) {
		$this->configurationManager = $this->getMock(
			\TYPO3\CMS\Core\Configuration\ConfigurationManager::class,
			$methods
		);
	}

	/**
	 * @test
	 */
	public function configureSaltedPasswordsWithDefaultConfiguration() {
		/** @var $silentConfigurationUpgradeServiceInstance \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
			\TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService::class,
			array('dummy'),
			array(),
			'',
			FALSE
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

		$this->setExpectedException(\TYPO3\CMS\Install\Controller\Exception\RedirectException::class);

		$silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

		$silentConfigurationUpgradeServiceInstance->_call('configureSaltedPasswords');
	}

	/**
	 * @test
	 */
	public function configureSaltedPasswordsWithExtensionConfigurationBeEnabled() {
		/** @var $silentConfigurationUpgradeServiceInstance \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
			\TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService::class,
			array('dummy'),
			array(),
			'',
			FALSE
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

		$this->setExpectedException(\TYPO3\CMS\Install\Controller\Exception\RedirectException::class);

		$silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

		$silentConfigurationUpgradeServiceInstance->_call('configureSaltedPasswords');
	}

	/**
	 * @test
	 */
	public function configureSaltedPasswordsWithExtensionConfigurationBeNotEnabled() {
		/** @var $silentConfigurationUpgradeServiceInstance \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
			\TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService::class,
			array('dummy'),
			array(),
			'',
			FALSE
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

		$this->setExpectedException(\TYPO3\CMS\Install\Controller\Exception\RedirectException::class);

		$silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

		$silentConfigurationUpgradeServiceInstance->_call('configureSaltedPasswords');
	}

	/**
	 * @test
	 */
	public function noProxyAuthSchemeSetInLocalConfiguration() {
		/** @var $silentConfigurationUpgradeServiceInstance \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
			\TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService::class,
			array('dummy'),
			array(),
			'',
			FALSE
		);

		$closure = function ($param) {
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
	public function proxyAuthSchemeIsDigest() {
		/** @var $silentConfigurationUpgradeServiceInstance \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
			\TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService::class,
			array('dummy'),
			array(),
			'',
			FALSE
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
	public function proxyAuthSchemeIsBasic() {
		/** @var $silentConfigurationUpgradeServiceInstance \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
			\TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService::class,
			array('dummy'),
			array(),
			'',
			FALSE
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

		$this->setExpectedException(\TYPO3\CMS\Install\Controller\Exception\RedirectException::class);

		$silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

		$silentConfigurationUpgradeServiceInstance->_call('setProxyAuthScheme');
	}

	/**
	 * Dataprovider for transferDeprecatedCurlSettings
	 *
	 * @return array
	 */
	public function curlProxySettingsToHttpSettingsMapping() {
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
	 */
	public function transferDeprecatedCurlSettings($curlProxyServer, $proxyHost, $proxyPort) {
		/** @var $silentConfigurationUpgradeServiceInstance \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
			\TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService::class,
			array('dummy'),
			array(),
			'',
			FALSE
		);

		$currentLocalConfiguration = array(
			array('SYS/curlProxyServer',  $curlProxyServer),
			array('HTTP/proxy_host', ''),
			array('SYS/curlProxyUserPass',  ''),
			array('HTTP/proxy_user', ''),
			array('SYS/curlUse', FALSE)
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

		$this->setExpectedException(\TYPO3\CMS\Install\Controller\Exception\RedirectException::class);

		$silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

		$silentConfigurationUpgradeServiceInstance->_call('transferDeprecatedCurlSettings');
	}

	/**
	 * @test
	 */
	public function curlProxyServerDoesNotOverwriteHttpSettings() {
		/** @var $silentConfigurationUpgradeServiceInstance \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
			\TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService::class,
			array('dummy'),
			array(),
			'',
			FALSE
		);

		$currentLocalConfiguration = array(
			array('SYS/curlProxyServer', 'http://proxyOld:3128/'),
			array('SYS/curlProxyUserPass', 'userOld:passOld'),
			array('HTTP/proxy_host', 'proxyNew'),
			array('HTTP/proxy_port', '3128'),
			array('HTTP/proxy_user', 'userNew'),
			array('HTTP/proxy_pass', 'passNew'),
			array('SYS/curlUse', FALSE)
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
	public function curlAdapterUsedIfCurlUse() {
		/** @var $silentConfigurationUpgradeServiceInstance \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
			\TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService::class,
			array('dummy'),
			array(),
			'',
			FALSE
		);

		$currentLocalConfiguration = array(
			array('SYS/curlProxyServer', ''),
			array('SYS/curlProxyUserPass', ''),
			array('HTTP/proxy_host', 'proxyNew'),
			array('HTTP/proxy_user', 'userNew'),
			array('SYS/curlUse', TRUE)
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

		$this->setExpectedException(\TYPO3\CMS\Install\Controller\Exception\RedirectException::class);

		$silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

		$silentConfigurationUpgradeServiceInstance->_call('transferDeprecatedCurlSettings');
	}

}
