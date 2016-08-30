<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

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

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Test case
 */
class LocalizationUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Instance of configurationManager, injected to subject
     *
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
     */
    protected $configurationManagerMock;

    /**
     * LOCAL_LANG array fixture
     *
     * @var array
     */
    protected $LOCAL_LANG = [
        'extensionKey' => [
            'default' => [
                'key1' => [
                    [
                        'source' => 'English label for key1',
                        'target' => 'English label for key1',
                    ]
                ],
                'key2' => [
                    [
                        'source' => 'English label for key2',
                        'target' => 'English label for key2',
                    ]
                ],
                'key3' => [
                    [
                        'source' => 'English label for key3',
                        'target' => 'English label for key3',
                    ]
                ],
                'key4' => [
                    [
                        'source' => 'English label for key4',
                        'target' => 'English label for key4',
                    ]
                ],
                'keyWithPlaceholder' => [
                    [
                        'source' => 'English label with number %d',
                        'target' => 'English label with number %d',
                    ]
                ],
            ],
            'dk' => [
                'key1' => [
                    [
                        'source' => 'English label for key1',
                        'target' => 'Dansk label for key1',
                    ]
                ],
                // not translated in dk => no target (llxml)
                'key2' => [
                    [
                        'source' => 'English label for key2',
                    ]
                ],
                'key3' => [
                    [
                        'source' => 'English label for key3',
                    ]
                ],
                // not translated in dk => empty target (xliff)
                'key4' => [
                    [
                        'source' => 'English label for key4',
                        'target' => '',
                    ]
                ],
                // not translated in dk => empty target (xliff)
                'key5' => [
                    [
                        'source' => 'English label for key5',
                        'target' => '',
                    ]
                ],
                'keyWithPlaceholder' => [
                    [
                        'source' => 'English label with number %d',
                    ]
                ],
            ],
            // fallback language for labels which are not translated in dk
            'dk_alt' => [
                'key1' => [
                    [
                        'source' => 'English label for key1',
                    ]
                ],
                'key2' => [
                    [
                        'source' => 'English label for key2',
                        'target' => 'Dansk alternative label for key2',
                    ]
                ],
                'key3' => [
                    [
                        'source' => 'English label for key3',
                    ]
                ],
                // not translated in dk_alt => empty target (xliff)
                'key4' => [
                    [
                        'source' => 'English label for key4',
                        'target' => '',
                    ]
                ],
                'key5' => [
                    [
                        'source' => 'English label for key5',
                        'target' => 'Dansk alternative label for key5',
                    ]
                ],
                'keyWithPlaceholder' => [
                    [
                        'source' => 'English label with number %d',
                    ]
                ],
            ],

        ],
    ];

    /**
     * Prepare class mocking some dependencies
     */
    protected function setUp()
    {
        $reflectionClass = new \ReflectionClass(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::class);

        $this->configurationManager = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class, ['getConfiguration']);
        $property = $reflectionClass->getProperty('configurationManager');
        $property->setAccessible(true);
        $property->setValue($this->configurationManager);
    }

    /**
     * Reset static properties
     */
    protected function tearDown()
    {
        $reflectionClass = new \ReflectionClass(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::class);

        $property = $reflectionClass->getProperty('configurationManager');
        $property->setAccessible(true);
        $property->setValue(null);

        $property = $reflectionClass->getProperty('LOCAL_LANG');
        $property->setAccessible(true);
        $property->setValue([]);

        $property = $reflectionClass->getProperty('languageKey');
        $property->setAccessible(true);
        $property->setValue('default');

        $property = $reflectionClass->getProperty('alternativeLanguageKeys');
        $property->setAccessible(true);
        $property->setValue([]);
    }

    /**
     * @test
     */
    public function implodeTypoScriptLabelArrayWorks()
    {
        $reflectionClass = new \ReflectionClass(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::class);
        $method = $reflectionClass->getMethod('flattenTypoScriptLabelArray');
        $method->setAccessible(true);

        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3.subkey1' => 'subvalue1',
            'key3.subkey2.subsubkey' => 'val'
        ];
        $input = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'subkey1' => 'subvalue1',
                'subkey2' => [
                    'subsubkey' => 'val'
                ]
            ]
        ];
        $result = $method->invoke(null, $input);
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function translateForEmptyStringKeyReturnsNull()
    {
        $this->assertNull(LocalizationUtility::translate('', 'extbase'));
    }

    /**
     * @test
     */
    public function translateForEmptyStringKeyWithArgumentsReturnsNull()
    {
        $this->assertNull(LocalizationUtility::translate('', 'extbase', ['argument']));
    }

    /**
     * @return array
     */
    public function translateDataProvider()
    {
        return [
            'get translated key' =>
            ['key1', $this->LOCAL_LANG, 'dk', 'Dansk label for key1'],

            'fallback to English when translation is missing for key' =>
            ['key2', $this->LOCAL_LANG, 'dk', 'English label for key2'],

            'fallback to English for non existing language' =>
            ['key2', $this->LOCAL_LANG, 'xx', 'English label for key2'],

            'replace placeholder with argument' =>
            ['keyWithPlaceholder', $this->LOCAL_LANG, 'en', 'English label with number 100', [], [100]],

            'get translated key from primary language' =>
            ['key1', $this->LOCAL_LANG, 'dk', 'Dansk label for key1', ['dk_alt']],

            'fallback to alternative language if translation is missing(llxml)' =>
            ['key2', $this->LOCAL_LANG, 'dk', 'Dansk alternative label for key2', ['dk_alt']],

            'fallback to alternative language if translation is missing(xlif)' =>
            ['key5', $this->LOCAL_LANG, 'dk', 'Dansk alternative label for key5', ['dk_alt']],

            'fallback to English for label not translated in dk and dk_alt(llxml)' =>
            ['key3', $this->LOCAL_LANG, 'dk', 'English label for key3', ['dk_alt']],

            'fallback to English for label not translated in dk and dk_alt(xlif)' =>
            ['key4', $this->LOCAL_LANG, 'dk', 'English label for key4', ['dk_alt']],
        ];
    }

    /**
     * @param string $key
     * @param array $LOCAL_LANG
     * @param string $languageKey
     * @param string $expected
     * @param array $altLanguageKeys
     * @param array $arguments
     * @return void
     * @dataProvider translateDataProvider
     * @test
     */
    public function translateTest($key, array $LOCAL_LANG, $languageKey, $expected, array $altLanguageKeys = [], array $arguments = null)
    {
        $reflectionClass = new \ReflectionClass(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::class);

        $property = $reflectionClass->getProperty('LOCAL_LANG');
        $property->setAccessible(true);
        $property->setValue($LOCAL_LANG);

        $property = $reflectionClass->getProperty('languageKey');
        $property->setAccessible(true);
        $property->setValue($languageKey);

        $property = $reflectionClass->getProperty('alternativeLanguageKeys');
        $property->setAccessible(true);
        $property->setValue($altLanguageKeys);

        $this->assertEquals($expected, LocalizationUtility::translate($key, 'extensionKey', $arguments));
    }

    /**
     * @return array
     */
    public function loadTypoScriptLabelsProvider()
    {
        return [
            'override labels with typoscript' => [
                'LOCAL_LANG' => [
                    'extensionKey' => [
                        'dk' => [
                            'key1' => [
                                [
                                    'source' => 'English label for key1',
                                    'target' => 'Dansk label for key1 extensionKey',
                                ]
                            ],
                            'key2' => [
                                [
                                    'source' => 'English label for key2',
                                ]
                            ],
                            'key3.subkey1' => [
                                [
                                    'source' => 'English label for key3',
                                ]
                            ],
                        ],
                    ],
                    'extensionKey1' => [
                        'dk' => [
                            'key1' => [
                                [
                                    'source' => 'English label for key1',
                                    'target' => 'Dansk label for key1 extensionKey1',
                                ]
                            ],
                            'key2' => [
                                [
                                    'source' => 'English label for key2',
                                ]
                            ],
                            'key3.subkey1' => [
                                [
                                    'source' => 'English label for key3',
                                ]
                            ],
                        ],
                    ],
                ],
                'typoscript LOCAL_LANG' => [
                    '_LOCAL_LANG' => [
                        'dk' => [
                            'key1' => 'key1 value from TS extensionKey',
                            'key3' => [
                                'subkey1' => 'key3.subkey1 value from TS extensionKey',
                                // this key doesn't exist in xml files
                                'subkey2' => [
                                    'subsubkey' => 'key3.subkey2.subsubkey value from TS extensionKey'
                                ]
                            ]
                        ]
                    ]
                ],
                'language key' => 'dk',
                'expected' => [
                    'key1' => [
                        [
                            'source' => 'English label for key1',
                            'target' => 'key1 value from TS extensionKey',
                        ]
                    ],
                    'key2' => [
                        [
                            'source' => 'English label for key2',
                        ]
                    ],
                    'key3.subkey1' => [
                        [
                            'source' => 'English label for key3',
                            'target' => 'key3.subkey1 value from TS extensionKey',
                        ]
                    ],
                    'key3.subkey2.subsubkey' => [
                        [
                            'target' => 'key3.subkey2.subsubkey value from TS extensionKey',
                        ]
                    ],
                ],
            ]
        ];
    }

    /**
     * Tests whether labels from xml are overwritten by TypoScript labels
     *
     * @param array $LOCAL_LANG
     * @param array $typoScriptLocalLang
     * @param string $languageKey
     * @param array $expected
     * @return void
     * @dataProvider loadTypoScriptLabelsProvider
     * @test
     */
    public function loadTypoScriptLabels(array $LOCAL_LANG, array $typoScriptLocalLang, $languageKey, array $expected)
    {
        $reflectionClass = new \ReflectionClass(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::class);

        $property = $reflectionClass->getProperty('LOCAL_LANG');
        $property->setAccessible(true);
        $property->setValue($LOCAL_LANG);

        $property = $reflectionClass->getProperty('languageKey');
        $property->setAccessible(true);
        $property->setValue($languageKey);

        $configurationType = \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $this->configurationManager->expects($this->at(0))->method('getConfiguration')->with($configurationType, 'extensionKey', null)->will($this->returnValue($typoScriptLocalLang));

        $method = $reflectionClass->getMethod('loadTypoScriptLabels');
        $method->setAccessible(true);
        $method->invoke(null, 'extensionKey');

        $property = $reflectionClass->getProperty('LOCAL_LANG');
        $property->setAccessible(true);
        $result = $property->getValue();

        $this->assertEquals($expected, $result['extensionKey'][$languageKey]);
    }

    /**
     * @return void
     * @test
     */
    public function clearLabelWithTypoScript()
    {
        $reflectionClass = new \ReflectionClass(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::class);

        $property = $reflectionClass->getProperty('LOCAL_LANG');
        $property->setAccessible(true);
        $property->setValue($this->LOCAL_LANG);

        $property = $reflectionClass->getProperty('languageKey');
        $property->setAccessible(true);
        $property->setValue('dk');

        $typoScriptLocalLang = [
            '_LOCAL_LANG' => [
                'dk' => [
                    'key1' => '',
                ]
            ]
        ];

        $configurationType = \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK;
        $this->configurationManager->expects($this->at(0))->method('getConfiguration')->with($configurationType, 'extensionKey', null)->will($this->returnValue($typoScriptLocalLang));

        $method = $reflectionClass->getMethod('loadTypoScriptLabels');
        $method->setAccessible(true);
        $method->invoke(null, 'extensionKey');

        $result = LocalizationUtility::translate('key1', 'extensionKey');
        $this->assertNotNull($result);
        $this->assertEquals('', $result);
    }
}
