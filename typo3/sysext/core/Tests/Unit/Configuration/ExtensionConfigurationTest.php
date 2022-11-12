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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExtensionConfigurationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getWithEmptyPathReturnsEntireExtensionConfiguration(): void
    {
        $extConf = [
            'aFeature' => 'iAmEnabled',
            'aFlagCategory' => [
                'someFlag' => 'foo',
            ],
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['someExtension'] = $extConf;
        self::assertSame((new ExtensionConfiguration())->get('someExtension'), $extConf);
    }

    /**
     * @test
     */
    public function getWithPathReturnsGivenValue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['someExtension'] = [
            'aFeature' => 'iAmEnabled',
            'aFlagCategory' => [
                'someFlag' => 'foo',
            ],
        ];
        self::assertSame((new ExtensionConfiguration())->get('someExtension', 'aFeature'), 'iAmEnabled');
    }

    /**
     * @test
     */
    public function getWithPathReturnsGivenPathSegment(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['someExtension'] = [
            'aFeature' => 'iAmEnabled',
            'aFlagCategory' => [
                'someFlag' => 'foo',
            ],
        ];
        self::assertSame((new ExtensionConfiguration())->get('someExtension', 'aFlagCategory'), ['someFlag' => 'foo']);
    }

    /**
     * @test
     */
    public function setThrowsExceptionWithEmptyExtension(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1509715852);
        (new ExtensionConfiguration())->set('');
    }

    /**
     * @test
     */
    public function setRemovesFullExtensionConfiguration(): void
    {
        $configurationManagerMock = $this->createMock(ConfigurationManager::class);
        GeneralUtility::addInstance(ConfigurationManager::class, $configurationManagerMock);
        $configurationManagerMock->expects(self::once())->method('removeLocalConfigurationKeysByPath')->with(['EXTENSIONS/foo']);
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['foo'] = [
            'bar' => 'baz',
        ];
        (new ExtensionConfiguration())->set('foo');
        self::assertFalse(isset($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['foo']));
    }

    /**
     * @test
     */
    public function setWritesFullExtensionConfig(): void
    {
        $value = ['bar' => 'baz'];
        $configurationManagerMock = $this->createMock(ConfigurationManager::class);
        GeneralUtility::addInstance(ConfigurationManager::class, $configurationManagerMock);
        $configurationManagerMock->expects(self::once())->method('setLocalConfigurationValueByPath')->with('EXTENSIONS/foo', $value);
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['foo'] = [
            'bar' => 'fizz',
            'bee' => 'boo',
        ];
        (new ExtensionConfiguration())->set('foo', $value);
        self::assertSame($value, $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['foo']);
    }
}
