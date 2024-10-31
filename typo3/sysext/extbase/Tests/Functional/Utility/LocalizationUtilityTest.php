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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LocalizationUtilityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/label_test'];

    #[Test]
    public function implodeTypoScriptLabelArrayWorks(): void
    {
        $reflectionClass = new \ReflectionClass(LocalizationUtility::class);
        $method = $reflectionClass->getMethod('flattenTypoScriptLabelArray');

        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key3.subkey1' => 'subvalue1',
            'key3.subkey2.subsubkey' => 'val',
        ];
        $input = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                '_typoScriptNodeValue' => 'value3',
                'subkey1' => 'subvalue1',
                'subkey2' => [
                    'subsubkey' => 'val',
                ],
            ],
        ];
        $result = $method->invoke(null, $input);
        self::assertSame($expected, $result);
    }

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
            ['key1', 'da', 'Dansk label for key1'],

            'fallback to English when translation is missing for key' =>
            ['key2', 'da', 'English label for key2'],

            'fallback to English for non existing language' =>
            ['key2', 'xx', 'English label for key2'],

            'replace placeholder with argument' =>
            ['keyWithPlaceholder', 'default', 'English label with number 100', [100]],

            'placeholder and empty arguments in default' =>
            ['keyWithPlaceholderAndNoArguments', 'default', '%d/%m/%Y', []],

            'placeholder and empty arguments in translation' =>
            ['keyWithPlaceholderAndNoArguments', 'da', '%d-%m-%Y', []],
        ];
    }

    #[DataProvider('translateDataProvider')]
    #[Test]
    public function translateTestWithBackendUserLanguage(string $key, string $languageKey, string $expected, ?array $arguments = null): void
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
        self::assertSame($expected, LocalizationUtility::translate($key, 'label_test', $arguments));
    }

    #[DataProvider('translateDataProvider')]
    #[Test]
    public function translateTestWithExplicitLanguageParameters(
        string $key,
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

        self::assertSame($expected, LocalizationUtility::translate($key, 'label_test', $arguments, $languageKey));
    }

    /**
     * Tests whether labels from XLF are overwritten by TypoScript labels
     */
    #[Test]
    public function loadTypoScriptLabels(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $configurationType = ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with($configurationType, 'label_test', null)
            ->willReturn(['_LOCAL_LANG' => [
                'default' => [
                    'key3' => 'English label for key3 from TypoScript',
                ],
                'da' => [
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
            ]);
        GeneralUtility::setSingletonInstance(ConfigurationManagerInterface::class, $configurationManagerInterfaceMock);

        self::assertSame('key1 value from TS core', LocalizationUtility::translate('key1', 'label_test', languageKey: 'da'));
        // Label from XLF file, no override
        self::assertSame('English label for key2', LocalizationUtility::translate('key2', 'label_test', languageKey: 'da'));
        self::assertSame('English label for key3 from TypoScript', LocalizationUtility::translate('key3', 'label_test', languageKey: 'da'));
        self::assertSame('key3.subkey1 value from TypoScript', LocalizationUtility::translate('key3.subkey1', 'label_test', languageKey: 'da'));
        self::assertSame('key3.subkey2.subsubkey value from TypoScript', LocalizationUtility::translate('key3.subkey2.subsubkey', 'label_test', languageKey: 'da'));
    }

    #[Test]
    public function clearLabelWithTypoScript(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $configurationType = ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with($configurationType, 'label_test', null)
            ->willReturn([
                '_LOCAL_LANG' => [
                    'da' => [
                        'key1' => '',
                    ],
                ],
            ]);
        GeneralUtility::setSingletonInstance(ConfigurationManagerInterface::class, $configurationManagerInterfaceMock);

        $result = LocalizationUtility::translate('key1', 'label_test', languageKey: 'da');
        self::assertSame('', $result);

        $result = LocalizationUtility::translate('missingkey', 'label_test', languageKey: 'da');
        self::assertNull($result);
    }

    #[Test]
    public function clearAndOverrideLabelsWithTypoScriptButPreserveRemainingUnmodifiedLabels(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $configurationType = ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with($configurationType, 'label_test', null)
            ->willReturn([
                '_LOCAL_LANG' => [
                    'da' => [
                        'key1' => '',
                        'key4' => 'override',
                        // 'key6' not set, expected to remain as defined in locallang.xlf of fixture
                    ],
                ],
            ]);
        GeneralUtility::setSingletonInstance(ConfigurationManagerInterface::class, $configurationManagerInterfaceMock);

        $result = LocalizationUtility::translate('key1', 'label_test', languageKey: 'da');
        self::assertSame('', $result);

        $result = LocalizationUtility::translate('key4', 'label_test', languageKey: 'da');
        self::assertSame('override', $result);

        $result = LocalizationUtility::translate('key6', 'label_test', languageKey: 'da');
        self::assertSame('Dansk label for key6', $result);

        $result = LocalizationUtility::translate('missingkey', 'label_test', languageKey: 'da');
        self::assertNull($result);
    }

    #[Test]
    public function overrideWithoutClearingLabelsWithTypoScriptButPreserveRemainingUnmodifiedLabels(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $configurationType = ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with($configurationType, 'label_test', null)
            ->willReturn([
                '_LOCAL_LANG' => [
                    'da' => [
                        'key4' => 'override',
                        // 'key6' not set, expected to remain as defined in locallang.xlf of fixture
                    ],
                ],
            ]);
        GeneralUtility::setSingletonInstance(ConfigurationManagerInterface::class, $configurationManagerInterfaceMock);

        $result = LocalizationUtility::translate('key1', 'label_test', languageKey: 'da');
        self::assertSame('Dansk label for key1', $result);

        $result = LocalizationUtility::translate('key4', 'label_test', languageKey: 'da');
        self::assertSame('override', $result);

        $result = LocalizationUtility::translate('key6', 'label_test', languageKey: 'da');
        self::assertSame('Dansk label for key6', $result);

        $result = LocalizationUtility::translate('missingkey', 'label_test', languageKey: 'da');
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
    public function translateWillReturnLabelsFromTsEvenIfNoXlfFileExists(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $typoScriptLocalLang = [
            '_LOCAL_LANG' => [
                'da' => [
                    'key1' => 'I am a new key and there is no xlf file',
                ],
            ],
        ];

        $configurationType = ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerInterfaceMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with($configurationType, 'core', null)
            ->willReturn($typoScriptLocalLang);
        GeneralUtility::setSingletonInstance(ConfigurationManagerInterface::class, $configurationManagerInterfaceMock);

        $result = LocalizationUtility::translate('key1', 'core', [], 'da');

        self::assertSame('I am a new key and there is no xlf file', $result);
    }
}
