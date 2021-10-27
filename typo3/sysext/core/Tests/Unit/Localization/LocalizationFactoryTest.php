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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LocalizationFactoryTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function getParsedDataCallsLocalizationOverrideIfFileNotFoundExceptionIsThrown(): void
    {
        $languageStoreProphecy = $this->prophesize(LanguageStore::class);
        $languageStoreProphecy->hasData(Argument::cetera())->willReturn(false);
        $languageStoreProphecy->getData(Argument::cetera())->willReturn(['default' => []]);
        $languageStoreProphecy->setData(Argument::cetera())->shouldBeCalled();
        $languageStoreProphecy->setConfiguration(Argument::cetera())->willThrow(new FileNotFoundException('testing', 1476049512));
        $languageStoreProphecy->getFileReferenceWithoutExtension(Argument::cetera())->willReturn('');
        $languageStoreProphecy->getSupportedExtensions()->willReturn([]);
        $languageStoreProphecy->getDataByLanguage(Argument::cetera())->willReturn([]);

        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->shouldBeCalled();

        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'] = ['foo' => 'bar'];

        (new LocalizationFactory($languageStoreProphecy->reveal(), $cacheManagerProphecy->reveal()))
            ->getParsedData(__DIR__ . '/Fixtures/locallang.invalid', 'default');
    }

    /**
     * @test
     */
    public function ensureLocalizationIsProperlyCached(): void
    {
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheFrontendProphecy->get(Argument::type('string'))->shouldBeCalled()->willReturn(false);
        $cacheFrontendProphecy->set(Argument::type('string'), Argument::exact([
            'label1' => [['source' => 'This is label #1', 'target' => 'This is label #1']],
        ]))->shouldBeCalled()->willReturn(null);

        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());

        (new LocalizationFactory(new LanguageStore(), $cacheManagerProphecy->reveal()))
            ->getParsedData(__DIR__ . '/Fixtures/locallang.xlf', 'default');
    }
}
