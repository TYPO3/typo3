<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ExtensionConfigurationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getThrowExceptionIfExtensionConfigurationDoesNotExist()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['someOtherExtension']['someKey'] = 'someValue';
        $this->expectException(ExtensionConfigurationExtensionNotConfiguredException::class);
        $this->expectExceptionCode(1509654728);
        (new ExtensionConfiguration())->get('someExtension');
    }

    /**
     * @test
     */
    public function getWithEmptyPathReturnsEntireExtensionConfiguration()
    {
        $extConf = [
            'aFeature' => 'iAmEnabled',
            'aFlagCategory' => [
                'someFlag' => 'foo',
            ],
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['someExtension'] = $extConf;
        $this->assertSame((new ExtensionConfiguration())->get('someExtension'), $extConf);
    }

    /**
     * @test
     */
    public function getWithPathReturnsGivenValue()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['someExtension'] = [
            'aFeature' => 'iAmEnabled',
            'aFlagCategory' => [
                'someFlag' => 'foo',
            ],
        ];
        $this->assertSame((new ExtensionConfiguration())->get('someExtension', 'aFeature'), 'iAmEnabled');
    }

    /**
     * @test
     */
    public function getWithPathReturnsGivenPathSegment()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['someExtension'] = [
            'aFeature' => 'iAmEnabled',
            'aFlagCategory' => [
                'someFlag' => 'foo',
            ],
        ];
        $this->assertSame((new ExtensionConfiguration())->get('someExtension', 'aFlagCategory'), ['someFlag' => 'foo']);
    }

    /**
     * @test
     */
    public function setThrowsExceptionWithEmptyExtension()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1509715852);
        (new ExtensionConfiguration())->set('');
    }

    /**
     * @test
     */
    public function setRemovesFullExtensionConfiguration()
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        GeneralUtility::addInstance(ConfigurationManager::class, $configurationManagerProphecy->reveal());
        $configurationManagerProphecy->getConfigurationValueByPath(Argument::cetera())->willReturn([]);
        $configurationManagerProphecy->removeLocalConfigurationKeysByPath(['EXTENSIONS/foo'])->shouldBeCalled();
        (new ExtensionConfiguration())->set('foo');
    }

    /**
     * @test
     */
    public function setRemovesPath()
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        GeneralUtility::addInstance(ConfigurationManager::class, $configurationManagerProphecy->reveal());
        $configurationManagerProphecy->getConfigurationValueByPath(Argument::cetera())->willReturn([]);
        $configurationManagerProphecy->removeLocalConfigurationKeysByPath(['EXTENSIONS/foo/bar'])->shouldBeCalled();
        (new ExtensionConfiguration())->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setWritesFullExtensionConfig()
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        GeneralUtility::addInstance(ConfigurationManager::class, $configurationManagerProphecy->reveal());
        $configurationManagerProphecy->getConfigurationValueByPath(Argument::cetera())->willReturn([]);
        $configurationManagerProphecy->setLocalConfigurationValueByPath('EXTENSIONS/foo', ['bar' => 'baz'])->shouldBeCalled();
        (new ExtensionConfiguration())->set('foo', '', ['bar' => 'baz']);
    }

    /**
     * @test
     */
    public function setWritesPath()
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        GeneralUtility::addInstance(ConfigurationManager::class, $configurationManagerProphecy->reveal());
        $configurationManagerProphecy->getConfigurationValueByPath(Argument::cetera())->willReturn([]);
        $configurationManagerProphecy->setLocalConfigurationValueByPath('EXTENSIONS/foo/aPath', ['bar' => 'baz'])->shouldBeCalled();
        (new ExtensionConfiguration())->set('foo', 'aPath', ['bar' => 'baz']);
    }

    /**
     * @test
     */
    public function setUpdatesLegacyExtConfToNewValues()
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        GeneralUtility::addInstance(ConfigurationManager::class, $configurationManagerProphecy->reveal());
        $configurationManagerProphecy->setLocalConfigurationValueByPath(Argument::cetera())->shouldBeCalled();
        $configurationManagerProphecy->getConfigurationValueByPath('EXTENSIONS')->willReturn(['foo' => ['bar' => 'baz']]);
        $configurationManagerProphecy->setLocalConfigurationValueByPath('EXT/extConf/foo', serialize(['bar' => 'baz']))->shouldBeCalled();
        (new ExtensionConfiguration())->set('foo', '', ['bar' => 'baz']);
    }

    /**
     * @test
     */
    public function setUpdatesLegacyExtConfWithDottedArrayKeysForNestedConfiguration()
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        GeneralUtility::addInstance(ConfigurationManager::class, $configurationManagerProphecy->reveal());
        $configurationManagerProphecy->setLocalConfigurationValueByPath(Argument::cetera())->shouldBeCalled();
        $nestedInput = [
            'FE' => [
                'forceSalted' => true,
            ]
        ];
        $expectedLegacyExtConf = [
            'FE.' => [
                'forceSalted' => true,
            ]
        ];
        $configurationManagerProphecy->getConfigurationValueByPath('EXTENSIONS')->willReturn(['saltedPasswords' => $nestedInput]);
        $configurationManagerProphecy->setLocalConfigurationValueByPath('EXT/extConf/saltedPasswords', serialize($expectedLegacyExtConf))->shouldBeCalled();
        (new ExtensionConfiguration())->set('saltedPasswords', '', $nestedInput);
    }

    /**
     * @test
     */
    public function setUpdatesLegacyExtConfWithDottedArrayKeysForNestedConfigurationWithMultiNestedArrays()
    {
        $configurationManagerProphecy = $this->prophesize(ConfigurationManager::class);
        GeneralUtility::addInstance(ConfigurationManager::class, $configurationManagerProphecy->reveal());
        $configurationManagerProphecy->setLocalConfigurationValueByPath(Argument::cetera())->shouldBeCalled();
        $nestedInput = [
            'aCategory' => [
                'aSubCategory' => [
                    'aKey' => 'aValue',
                ],
            ],
        ];
        $expectedLegacyExtConf = [
            'aCategory.' => [
                'aSubCategory.' => [
                    'aKey' => 'aValue',
                ],
            ],
        ];
        $configurationManagerProphecy->getConfigurationValueByPath('EXTENSIONS')->willReturn(['someExtension' => $nestedInput]);
        $configurationManagerProphecy->setLocalConfigurationValueByPath('EXT/extConf/someExtension', serialize($expectedLegacyExtConf))->shouldBeCalled();
        (new ExtensionConfiguration())->set('someExtension', '', $nestedInput);
    }
}
