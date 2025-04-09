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

namespace TYPO3\CMS\Core\Tests\Unit\Localization;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LocalizationFactoryTest extends UnitTestCase
{
    #[Test]
    public function getParsedDataCallsLocalizationOverrideIfFileNotFoundExceptionIsThrown(): void
    {
        $languageStoreMock = $this->createMock(LanguageStore::class);
        $languageStoreMock->method('hasData')->with(self::anything())->willReturn(false);
        $languageStoreMock->method('getData')->with(self::anything())->willReturn(['default' => []]);
        $languageStoreMock->expects(self::atLeastOnce())->method('setData')->with(self::anything());
        $languageStoreMock->method('setConfiguration')->with(self::anything())->willThrowException(new FileNotFoundException('testing', 1476049512));
        $languageStoreMock->method('getFileReferenceWithoutExtension')->with(self::anything())->willReturn('');
        $languageStoreMock->method('getSupportedExtensions')->willReturn([]);
        $languageStoreMock->method('getDataByLanguage')->with(self::anything())->willReturn([]);

        $cacheFrontendMock = $this->createMock(FrontendInterface::class);
        $cacheFrontendMock->method('get')->with(self::anything())->willReturn(false);
        $cacheFrontendMock->expects(self::atLeastOnce())->method('set')->with(self::anything());

        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheManagerMock->method('getCache')->with('l10n')->willReturn($cacheFrontendMock);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'] = ['foo' => 'bar'];

        (new LocalizationFactory($languageStoreMock, $cacheManagerMock))
            ->getParsedData(__DIR__ . '/Fixtures/locallang.invalid', 'default');
    }

    #[Test]
    public function ensureLocalizationIsProperlyCached(): void
    {
        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->method('extractPackageKeyFromPackagePath')->with('EXT:core/Tests/Unit/Localization/Fixtures/locallang.xlf')->willReturn('core');

        $cacheFrontendMock = $this->createMock(FrontendInterface::class);
        $cacheFrontendMock->expects(self::atLeastOnce())->method('get')->with(self::isString())->willReturn(false);
        $cacheFrontendMock->expects(self::atLeastOnce())->method('set')->with(self::isString(), [
            'label1' => [['source' => 'This is label #1', 'target' => 'This is label #1']],
        ])->willReturn(null);

        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheManagerMock->method('getCache')->with('l10n')->willReturn($cacheFrontendMock);

        (new LocalizationFactory(new LanguageStore($packageManagerMock), $cacheManagerMock))
            ->getParsedData('EXT:core/Tests/Unit/Localization/Fixtures/locallang.xlf', 'default');
    }
}
