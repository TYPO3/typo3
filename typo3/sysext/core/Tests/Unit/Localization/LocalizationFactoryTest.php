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

use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class LocalizationFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getParsedDataHandlesLocallangXMLOverride()
    {
        /** @var $subject LocalizationFactory */
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
        $file = PATH_site . 'typo3temp/' . $unique . '.xml';
        GeneralUtility::writeFileToTypo3tempDir($file, $xml);
        // Make sure there is no cached version of the label
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('l10n')->flush();
        // Get default value
        $defaultLL = $subject->getParsedData('EXT:lang/locallang_core.xlf', 'default');
        // Clear language cache again
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('l10n')->flush();
        // Set override file
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:lang/locallang_core.xlf'][$unique] = $file;
        /** @var $store \TYPO3\CMS\Core\Localization\LanguageStore */
        $store = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\LanguageStore::class);
        $store->flushData('EXT:lang/locallang_core.xlf');
        // Get override value
        $overrideLL = $subject->getParsedData('EXT:lang/locallang_core.xlf', 'default');
        // Clean up again
        unlink($file);
        $this->assertNotEquals($overrideLL['default']['buttons.logout'][0]['target'], '');
        $this->assertNotEquals($defaultLL['default']['buttons.logout'][0]['target'], $overrideLL['default']['buttons.logout'][0]['target']);
        $this->assertEquals($overrideLL['default']['buttons.logout'][0]['target'], 'EXIT');
    }

    /**
     * @test
     */
    public function getParsedDataCallsLocalizationOverrideIfFileNotFoundExceptionIsThrown()
    {
        /** @var $subject LocalizationFactory */
        $localizationFactory = $this->getAccessibleMock(LocalizationFactory::class, ['localizationOverride']);
        $languageStore = $this->getMock(\TYPO3\CMS\Core\Localization\LanguageStore::class, ['hasData', 'setConfiguration', 'getData', 'setData']);
        $cacheInstance = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend::class, ['get', 'set'], [], '', false);
        $localizationFactory->_set('store', $languageStore);
        $localizationFactory->_set('cacheInstance', $cacheInstance);
        $languageStore->method('hasData')->willReturn(false);
        $languageStore->method('getData')->willReturn([]);
        $languageStore->method('setConfiguration')->willThrowException(new \TYPO3\CMS\Core\Localization\Exception\FileNotFoundException());
        $cacheInstance->method('get')->willReturn(false);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'] = ['foo' => 'bar'];

        $localizationFactory->expects($this->once())->method('localizationOverride');
        $localizationFactory->getParsedData('EXT:backend/Resources/Private/Language/locallang_layout.xlf', 'default');
    }
}
