<?php
namespace TYPO3\CMS\Core\Tests\Unit\Localization;

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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LocalizationFactoryTest extends UnitTestCase
{
    public function tearDown(): void
    {
        // Drop created singletons again
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getParsedDataHandlesLocallangXMLOverride()
    {
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);

        $subject = new LocalizationFactory;

        $unique = 'locallangXMLOverrideTest' . substr($this->getUniqueId(), 0, 10);
        $xml = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
			<T3locallang>
				<data type="array">
					<languageKey index="default" type="array">
						<label index="buttons.logout">EXIT</label>
					</languageKey>
				</data>
			</T3locallang>';
        $file = Environment::getVarPath() . '/tests/' . $unique . '.xml';
        GeneralUtility::writeFileToTypo3tempDir($file, $xml);
        $this->testFilesToDelete[] = $file;

        // Get default value
        $defaultLL = $subject->getParsedData('EXT:core/Resources/Private/Language/locallang_core.xlf', 'default');

        // Set override file
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:core/Resources/Private/Language/locallang_core.xlf'][$unique] = $file;

        /** @var $store LanguageStore */
        $store = GeneralUtility::makeInstance(LanguageStore::class);
        $store->flushData('EXT:core/Resources/Private/Language/locallang_core.xlf');

        // Get override value
        $overrideLL = $subject->getParsedData('EXT:core/Resources/Private/Language/locallang_core.xlf', 'default');

        $this->assertNotEquals($overrideLL['default']['buttons.logout'][0]['target'], '');
        $this->assertNotEquals($defaultLL['default']['buttons.logout'][0]['target'], $overrideLL['default']['buttons.logout'][0]['target']);
        $this->assertEquals($overrideLL['default']['buttons.logout'][0]['target'], 'EXIT');
    }

    /**
     * @test
     */
    public function getParsedDataCallsLocalizationOverrideIfFileNotFoundExceptionIsThrown()
    {
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());

        /** @var $subject LocalizationFactory */
        $localizationFactory = $this->getAccessibleMock(LocalizationFactory::class, ['localizationOverride']);
        $languageStore = $this->getMockBuilder(LanguageStore::class)
            ->setMethods(['hasData', 'setConfiguration', 'getData', 'setData'])
            ->getMock();
        $cacheInstance = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();
        $localizationFactory->_set('store', $languageStore);
        $localizationFactory->_set('cacheInstance', $cacheInstance);
        $languageStore->method('hasData')->willReturn(false);
        $languageStore->method('getData')->willReturn(['default' => []]);
        $languageStore->method('setConfiguration')->willThrowException(new FileNotFoundException('testing', 1476049512));
        $cacheInstance->method('get')->willReturn(false);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'] = ['foo' => 'bar'];

        $localizationFactory->expects($this->once())->method('localizationOverride');
        $localizationFactory->getParsedData('EXT:backend/Resources/Private/Language/locallang_layout.xlf', 'default');
    }
}
