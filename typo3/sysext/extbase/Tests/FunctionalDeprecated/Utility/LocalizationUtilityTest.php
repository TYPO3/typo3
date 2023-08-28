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

namespace TYPO3\CMS\Extbase\Tests\FunctionalDeprecated\Utility;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LocalizationUtilityTest extends FunctionalTestCase
{
    protected ConfigurationManagerInterface&MockObject $configurationManagerInterfaceMock;

    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/label_test'];

    public static function translateDataProvider(): array
    {
        return [
            'get translated key from primary language' =>
            ['key1', 'da', 'Dansk label for key1', ['da_alt']],

            'fallback to alternative language if translation is missing' =>
            ['key2', 'da', 'Dansk alternative label for key2', ['da_alt']],

            'fallback to English for label not translated in da and da_alt' =>
            ['key3', 'da', 'English label for key3', ['da_alt']],
        ];
    }

    /**
     * @dataProvider translateDataProvider
     * @test
     */
    public function translateTestWithBackendUserLanguage(
        string $key,
        string $languageKey,
        string $expected,
        array $altLanguageKeys = [],
        array $arguments = null
    ): void {
        // No TypoScript overrides
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock
            ->method('getConfiguration')
            ->with('Framework', 'label_test', null)
            ->willReturn([]);
        GeneralUtility::setSingletonInstance(ConfigurationManagerInterface::class, $configurationManagerInterfaceMock);

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user = ['lang' => $languageKey];
        self::assertSame($expected, LocalizationUtility::translate($key, 'label_test', $arguments, alternativeLanguageKeys: $altLanguageKeys));
    }

    /**
     * @dataProvider translateDataProvider
     * @test
     */
    public function translateTestWithExplicitLanguageParameters(
        string $key,
        string $languageKey,
        string $expected,
        array $altLanguageKeys = [],
        array $arguments = null
    ): void {
        // No TypoScript overrides
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock
            ->method('getConfiguration')
            ->with('Framework', 'label_test', null)
            ->willReturn([]);
        GeneralUtility::setSingletonInstance(ConfigurationManagerInterface::class, $configurationManagerInterfaceMock);

        self::assertSame($expected, LocalizationUtility::translate($key, 'label_test', $arguments, $languageKey, $altLanguageKeys));
    }

}
