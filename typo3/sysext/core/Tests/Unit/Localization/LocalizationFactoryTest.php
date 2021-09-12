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

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LocalizationFactoryTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function getParsedDataCallsLocalizationOverrideIfFileNotFoundExceptionIsThrown(): void
    {
        $languageStore = $this->getMockBuilder(LanguageStore::class)
            ->onlyMethods(['hasData', 'setConfiguration', 'getData', 'setData'])
            ->getMock();
        $cacheInstance = $this->getMockBuilder(VariableFrontend::class)
            ->onlyMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $languageStore->method('hasData')->willReturn(false);
        $languageStore->method('getData')->willReturn(['default' => []]);
        $languageStore->method('setConfiguration')->willThrowException(new FileNotFoundException('testing', 1476049512));
        $cacheInstance->method('get')->willReturn(false);
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('l10n')->willReturn($cacheInstance);

        /** @var $localizationFactory LocalizationFactory */
        $localizationFactory = $this->getAccessibleMock(LocalizationFactory::class, ['localizationOverride'], [$languageStore, $cacheManagerProphecy->reveal()]);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'] = ['foo' => 'bar'];

        $localizationFactory->getParsedData('EXT:backend/Resources/Private/Language/locallang_layout.xlf', 'default');
    }
}
