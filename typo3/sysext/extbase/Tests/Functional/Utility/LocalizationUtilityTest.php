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

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class LocalizationUtilityTest extends FunctionalTestCase
{
    protected ConfigurationManagerInterface&MockObject $configurationManagerInterfaceMock;

    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/label_test'];

    protected function setUp(): void
    {
        parent::setUp();
        $reflectionClass = new \ReflectionClass(LocalizationUtility::class);
        $this->configurationManagerInterfaceMock = $this->createMock(ConfigurationManagerInterface::class);
        $property = $reflectionClass->getProperty('configurationManager');
        $property->setValue($this->configurationManagerInterfaceMock);
    }

    /**
     * Reset static properties
     */
    protected function tearDown(): void
    {
        $reflectionClass = new \ReflectionClass(LocalizationUtility::class);
        $property = $reflectionClass->getProperty('configurationManager');
        $property->setValue(null);
        parent::tearDown();
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function translateForEmptyStringKeyReturnsNull(): void
    {
        self::assertNull(LocalizationUtility::translate('', 'extbase'));
    }

    /**
     * @test
     */
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
            ['keyWithPlaceholder', 'default', 'English label with number 100', [], [100]],

            'placeholder and empty arguments in default' =>
            ['keyWithPlaceholderAndNoArguments', 'default', '%d/%m/%Y', [], []],

            'placeholder and empty arguments in translation' =>
            ['keyWithPlaceholderAndNoArguments', 'da', '%d-%m-%Y', [], []],

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
        $this->configurationManagerInterfaceMock
            ->method('getConfiguration')
            ->with('Framework', 'label_test', null)
            ->willReturn([]);

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
        $this->configurationManagerInterfaceMock
            ->method('getConfiguration')
            ->with('Framework', 'label_test', null)
            ->willReturn([]);

        self::assertSame($expected, LocalizationUtility::translate($key, 'label_test', $arguments, $languageKey, $altLanguageKeys));
    }

    /**
     * Tests whether labels from XLF are overwritten by TypoScript labels
     *
     * @test
     */
    public function loadTypoScriptLabels(): void
    {
        $configurationType = ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $this->configurationManagerInterfaceMock
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

        self::assertSame('key1 value from TS core', LocalizationUtility::translate('key1', 'label_test', languageKey: 'da'));
        // Label from XLF file, no override
        self::assertSame('English label for key2', LocalizationUtility::translate('key2', 'label_test', languageKey: 'da'));
        self::assertSame('English label for key3 from TypoScript', LocalizationUtility::translate('key3', 'label_test', languageKey: 'da'));
        self::assertSame('key3.subkey1 value from TypoScript', LocalizationUtility::translate('key3.subkey1', 'label_test', languageKey: 'da'));
        self::assertSame('key3.subkey2.subsubkey value from TypoScript', LocalizationUtility::translate('key3.subkey2.subsubkey', 'label_test', languageKey: 'da'));
    }

    /**
     * @test
     */
    public function clearLabelWithTypoScript(): void
    {
        $configurationType = ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $this->configurationManagerInterfaceMock
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

        $result = LocalizationUtility::translate('key1', 'label_test', languageKey: 'da');

        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function translateThrowsExceptionWithEmptyExtensionNameIfKeyIsNotPrefixedWithLLL(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1498144052);
        LocalizationUtility::translate('foo/bar', '');
    }

    /**
     * @test
     */
    public function translateWillReturnLabelsFromTsEvenIfNoXlfFileExists(): void
    {
        $typoScriptLocalLang = [
            '_LOCAL_LANG' => [
                'da' => [
                    'key1' => 'I am a new key and there is no xlf file',
                ],
            ],
        ];

        $configurationType = ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $this->configurationManagerInterfaceMock
            ->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->with($configurationType, 'core', null)
            ->willReturn($typoScriptLocalLang);

        $result = LocalizationUtility::translate('key1', 'core', languageKey: 'da');

        self::assertSame('I am a new key and there is no xlf file', $result);
    }
}
