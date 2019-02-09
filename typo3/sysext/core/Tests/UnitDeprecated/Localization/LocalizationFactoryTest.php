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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Localization;

use Prophecy\Argument;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
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
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);

        $store = new LanguageStore();
        $subject = new LocalizationFactory($store, $cacheManagerProphecy->reveal());

        $unique = 'locallangXMLOverrideTest' . substr(StringUtility::getUniqueId(), 0, 10);
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

        $store->flushData('EXT:core/Resources/Private/Language/locallang_core.xlf');

        // Get override value
        $overrideLL = $subject->getParsedData('EXT:core/Resources/Private/Language/locallang_core.xlf', 'default');

        self::assertNotEquals($overrideLL['default']['buttons.logout'][0]['target'], '');
        self::assertNotEquals($defaultLL['default']['buttons.logout'][0]['target'], $overrideLL['default']['buttons.logout'][0]['target']);
        self::assertEquals($overrideLL['default']['buttons.logout'][0]['target'], 'EXIT');
    }
}
