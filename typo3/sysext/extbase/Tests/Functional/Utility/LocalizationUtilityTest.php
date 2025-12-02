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

namespace TYPO3\CMS\Extbase\Tests\Functional\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LocalizationUtilityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/label_test'];

    #[Test]
    public function translateForEmptyStringKeyReturnsNull(): void
    {
        self::assertNull(LocalizationUtility::translate('', 'extbase'));
    }

    #[Test]
    public function translateForEmptyStringKeyWithArgumentsReturnsNull(): void
    {
        self::assertNull(LocalizationUtility::translate('', 'extbase', ['argument']));
    }

    public static function translateDataProvider(): array
    {
        return [
            'get translated key' =>
            ['key1', 'label_test', 'da', 'Dansk label for key1'],

            'fallback to English when translation is missing for key' =>
            ['key2', 'label_test', 'da', 'English label for key2'],

            'get translated key (russian, sort order relevant)' =>
            ['key1', 'label_test', 'ru', 'Russian label for key1'],

            'fallback to English when translation is missing for ru key' =>
            ['key2', 'label_test', 'ru', 'English label for key2'],

            'fallback to English for non existing language' =>
            ['key2', 'label_test', 'xx', 'English label for key2'],

            'Traditional LLL string as key' =>
            ['LLL:EXT:label_test/Resources/Private/Language/locallang.xlf:key1', null, 'da', 'Dansk label for key1'],

            'LLL with translation domain for default file' =>
            ['LLL:label_test.messages:key1', null, 'da', 'Dansk label for key1'],

            'LLL with translation domain' =>
            ['LLL:label_test.actions:key1', null, 'da', 'Dansk label for key1 from actions'],

            'translation domain as label for default file' =>
            ['label_test.messages:key1', null, 'da', 'Dansk label for key1'],

            'translation domain as label' =>
            ['label_test.actions:key1', null, 'da', 'Dansk label for key1 from actions'],

            'translation domain as extension name' =>
            ['key1', 'label_test.messages', 'da', 'Dansk label for key1'],

            'replace placeholder with argument' =>
            ['keyWithPlaceholder', 'label_test', 'default', 'English label with number 100', [100]],

            'placeholder and empty arguments in default' =>
            ['keyWithPlaceholderAndNoArguments', 'label_test', 'default', '%d/%m/%Y', []],

            'placeholder and empty arguments in translation' =>
            ['keyWithPlaceholderAndNoArguments', 'label_test', 'da', '%d-%m-%Y', []],
        ];
    }

    #[DataProvider('translateDataProvider')]
    #[Test]
    public function translateTestWithBackendUserLanguage(string $key, ?string $extensionName, string $languageKey, string $expected, ?array $arguments = null): void
    {
        // No TypoScript overrides
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock
            ->method('getConfiguration')
            ->with('Framework', 'label_test', null)
            ->willReturn([]);
        GeneralUtility::setSingletonInstance(ConfigurationManagerInterface::class, $configurationManagerInterfaceMock);

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user = ['lang' => $languageKey];
        self::assertSame($expected, LocalizationUtility::translate($key, $extensionName, $arguments));
    }

    #[DataProvider('translateDataProvider')]
    #[Test]
    public function translateTestWithExplicitLanguageParameters(
        string $key,
        ?string $extensionName,
        string $languageKey,
        string $expected,
        ?array $arguments = null
    ): void {
        // No TypoScript overrides
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock
            ->method('getConfiguration')
            ->with('Framework', 'label_test', null)
            ->willReturn([]);
        GeneralUtility::setSingletonInstance(ConfigurationManagerInterface::class, $configurationManagerInterfaceMock);

        self::assertSame($expected, LocalizationUtility::translate($key, $extensionName, $arguments, $languageKey));
    }

    public static function translateDataOverriddenByTypoScriptProvider(): array
    {
        return [
            'TS override simple key (key1)' =>
            ['key1', 'label_test', 'da', 'key1 value from TS core'],

            'TS override traditional LLL string does not work' =>
            ['LLL:EXT:label_test/Resources/Private/Language/locallang.xlf:key1', null, 'da', 'Dansk label for key1'],

            'TS override with language domain in key' =>
            ['label_test.messages:key1', null, 'da', 'key1 value from TS core'],

            'TS override language domain in key does not affect other domains' =>
            ['label_test.actions:key1', null, 'da', 'Dansk label for key1 from actions'],

            'TS override with language as extensionKey' =>
            ['key1', 'label_test.messages', 'da', 'key1 value from TS core'],

            'TS override does not affect other domains' =>
            ['key1', 'label_test.actions', 'da', 'Dansk label for key1 from actions'],

            'XLF label no override (key2)' =>
            ['key2', 'label_test', 'da', 'English label for key2'],

            'TS override key3 (top-level)' =>
            ['key3', 'label_test', 'da', 'English label for key3 from TypoScript'],

            'TS nested subkey (key3.subkey1)' =>
            ['key3.subkey1', 'label_test', 'da', 'key3.subkey1 value from TypoScript'],

            'TS nested subsubkey (key3.subkey2.subsubkey)' =>
            ['key3.subkey2.subsubkey', 'label_test', 'da', 'key3.subkey2.subsubkey value from TypoScript'],
        ];
    }

    /**
     * Tests whether labels from XLF are overwritten by TypoScript labels
     */
    #[DataProvider('translateDataOverriddenByTypoScriptProvider')]
    #[Test]
    public function loadTypoScriptLabels(
        string $key,
        ?string $extensionName,
        string $languageKey,
        string $expected,
    ): void {
        $request = new ServerRequest();
        $request = $request
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'plugin.' => [
                'tx_labeltest.' => ['_LOCAL_LANG.' => [
                    'default.' => [
                        'key3' => 'English label for key3 from TypoScript',
                    ],
                    'da.' => [
                        'key1' => 'key1 value from TS core',
                        'key3' => [
                            'subkey1' => 'key3.subkey1 value from TypoScript',
                            // this key doesn't exist in XLF files
                            'subkey2' => [
                                'subsubkey' => 'key3.subkey2.subsubkey value from TypoScript',
                            ],
                        ],
                    ],
                ],
                ],
            ],
        ]);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        self::assertSame($expected, LocalizationUtility::translate(key: $key, extensionName: $extensionName, languageKey: $languageKey, request: $request));
    }

    public static function translateDataClearLabelWithTypoScriptProvider(): array
    {
        return [
            'Key with extension name' =>
            ['key1', 'label_test', 'da', ''],

            'traditional LLL string does not get replaced' =>
            ['LLL:EXT:label_test/Resources/Private/Language/locallang.xlf:key1', null, 'da', 'Dansk label for key1'],

            'language domain key' =>
            ['label_test.messages:key1', null, 'da', ''],

            'language domain as extension key' =>
            ['key1', 'label_test.messages', 'da', ''],

            'Missing key with extension name' =>
            ['missingkey', 'label_test', 'da', null],
        ];
    }

    #[DataProvider('translateDataClearLabelWithTypoScriptProvider')]
    #[Test]
    public function clearLabelWithTypoScript(
        string $key,
        ?string $extensionName,
        string $languageKey,
        ?string $expected,
    ): void {
        $request = new ServerRequest();
        $request = $request
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'plugin.' => [
                'tx_labeltest.' => [
                    '_LOCAL_LANG.' => [
                        'da.' => [
                            'key1' => '',
                        ],
                    ],
                ],
            ],
        ]);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);

        $result = LocalizationUtility::translate($key, $extensionName, languageKey: $languageKey, request: $request);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function clearAndOverrideLabelsWithTypoScriptButPreserveRemainingUnmodifiedLabels(): void
    {
        $request = new ServerRequest();
        $request = $request
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'plugin.' => [
                'tx_labeltest.' => ['_LOCAL_LANG.' => [
                    'da.' => [
                        'key1' => '',
                        'key4' => 'override',
                        // 'key6' not set, expected to remain as defined in locallang.xlf of fixture
                    ],
                ],
                ],
            ],
        ]);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);

        $result = LocalizationUtility::translate('key1', 'label_test', languageKey: 'da', request: $request);
        self::assertSame('', $result);

        $result = LocalizationUtility::translate('key4', 'label_test', languageKey: 'da', request: $request);
        self::assertSame('override', $result);

        $result = LocalizationUtility::translate('key6', 'label_test', languageKey: 'da', request: $request);
        self::assertSame('Dansk label for key6', $result);

        $result = LocalizationUtility::translate('key6', 'label_test', languageKey: 'ru', request: $request);
        self::assertSame('Russian label for key6', $result);

        $result = LocalizationUtility::translate('missingkey', 'label_test', languageKey: 'da', request: $request);
        self::assertNull($result);
    }

    #[Test]
    public function overrideWithoutClearingLabelsWithTypoScriptButPreserveRemainingUnmodifiedLabels(): void
    {
        $request = new ServerRequest();
        $request = $request
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'plugin.' => [
                'tx_labeltest.' => ['_LOCAL_LANG.' => [
                    'da.' => [
                        'key4' => 'override',
                        // 'key6' not set, expected to remain as defined in locallang.xlf of fixture
                    ],
                    'ru.' => [
                        'key4' => 'override',
                        // 'key6' not set, expected to remain as defined in locallang.xlf of fixture
                    ],
                ],
                ],
            ],
        ]);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        $result = LocalizationUtility::translate('key1', 'label_test', languageKey: 'da', request: $request);
        self::assertSame('Dansk label for key1', $result);

        $result = LocalizationUtility::translate('key4', 'label_test', languageKey: 'da', request: $request);
        self::assertSame('override', $result);

        $result = LocalizationUtility::translate('key6', 'label_test', languageKey: 'da', request: $request);
        self::assertSame('Dansk label for key6', $result);

        $result = LocalizationUtility::translate('missingkey', 'label_test', languageKey: 'da', request: $request);
        self::assertNull($result);

        $result = LocalizationUtility::translate('key1', 'label_test', languageKey: 'ru', request: $request);
        self::assertSame('Russian label for key1', $result);

        $result = LocalizationUtility::translate('key4', 'label_test', languageKey: 'ru', request: $request);
        self::assertSame('override', $result);

        $result = LocalizationUtility::translate('key6', 'label_test', languageKey: 'ru', request: $request);
        self::assertSame('Russian label for key6', $result);

        $result = LocalizationUtility::translate('missingkey', 'label_test', languageKey: 'ru', request: $request);
        self::assertNull($result);

    }

    #[Test]
    public function translateThrowsExceptionWithEmptyExtensionNameIfKeyIsNotPrefixedWithLLL(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1498144052);
        LocalizationUtility::translate('foo/bar', '');
    }

    #[Test]
    public function translateThrowsExceptionWithEmptyExtensionNameIfKeyHasWrongDomainPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1498144052);
        LocalizationUtility::translate('core.form.tabs:', '');
    }

    #[Test]
    public function translateWillReturnLabelsFromTsEvenIfNoXlfFileExists(): void
    {
        $request = new ServerRequest();
        $request = $request
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'plugin.' => [
                'tx_core.' => ['_LOCAL_LANG.' => [
                    'da.' => [
                        'key1' => 'I am a new key and there is no xlf file',
                    ],
                    'ru.' => [
                        'key1' => 'I am a new key and there is no xlf file',
                    ],
                ],
                ],
            ],
        ]);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        $result = LocalizationUtility::translate('key1', 'core', [], 'da', request: $request);
        self::assertSame('I am a new key and there is no xlf file', $result);

        $result = LocalizationUtility::translate('key1', 'core', [], 'ru', request: $request);
        self::assertSame('I am a new key and there is no xlf file', $result);
    }
}
