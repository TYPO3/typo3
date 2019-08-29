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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
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
