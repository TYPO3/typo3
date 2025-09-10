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

namespace TYPO3\CMS\Core\Tests\Functional\Localization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LanguageServiceTest extends FunctionalTestCase
{
    // Constants to access the various language files
    private const LANGUAGE_FILE = 'EXT:test_localization/Resources/Private/Language/locallang.xlf';
    private const LANGUAGE_FILE_OVERRIDE = 'EXT:test_localization/Resources/Private/Language/locallang_override.xlf';
    private const LANGUAGE_FILE_OVERRIDE_DE = 'EXT:test_localization/Resources/Private/Language/de.locallang_override.xlf';
    private const LANGUAGE_FILE_OVERRIDE_FR = 'EXT:test_localization/Resources/Private/Language/fr.locallang_override.xlf';
    private const LANGUAGE_FILE_CORE = 'EXT:core/Resources/Private/Language/locallang_common.xlf';
    private const LANGUAGE_FILE_CORE_OVERRIDE = 'EXT:test_localization/Resources/Private/Language/locallang_common_override.xlf';
    private const LANGUAGE_FILE_CORE_OVERRIDE_FR = 'EXT:test_localization/Resources/Private/Language/fr.locallang_common_override.xlf';
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_localization',
    ];

    protected bool $initializeDatabase = false;

    protected array $configurationToUseInTestInstance = [
        'LANG' => [
            'resourceOverrides' => [],
        ],
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'l10n' => [
                        'backend' => NullBackend::class,
                    ],
                ],
            ],
        ],
    ];

    #[DataProvider('splitLabelTestDataProvider')]
    #[Test]
    public function splitLabelTest(string $input, string $expected): void
    {
        $subject = $this->get(LanguageServiceFactory::class)->create('default');
        self::assertEquals($expected, $subject->sL($input));
    }

    public static function splitLabelTestDataProvider(): \Generator
    {
        yield 'String without whitespace' => [
            'Edit content',
            'Edit content',
        ];
        yield 'String with leading whitespace' => [
            '  Edit content',
            '  Edit content',
        ];
        yield 'String with trailing whitespace' => [
            'Edit content   ',
            'Edit content   ',
        ];
        yield 'String with outer whitespace' => [
            '    Edit content   ',
            '    Edit content   ',
        ];
        yield 'String with inner whitespace' => [
            'Edit    content',
            'Edit    content',
        ];
        yield 'String with inner and outer whitespace' => [
            '    Edit    content    ',
            '    Edit    content    ',
        ];
        yield 'String containing the LLL: key' => [
            'You can use LLL: to ...',
            'You can use LLL: to ...',
        ];
        yield 'String starting with the LLL: key' => [
            'LLL: can be used to ...',
            '', // @todo Should this special case be handled to return the input string?
        ];
        yield 'Locallang label without whitespace' => [
            'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent',
            'Edit content',
        ];
        yield 'Locallang label with leading whitespace' => [
            '    LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent',
            'Edit content',
        ];
        yield 'Locallang label with trailing whitespace' => [
            'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent    ',
            'Edit content',
        ];
        yield 'Locallang label with outer whitespace' => [
            '    LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent    ',
            'Edit content',
        ];
        yield 'Locallang label with inner whitespace' => [
            'LLL:    EXT:    core/Resources/Private/Language/locallang_core.xlf:cm.editcontent',
            'Edit content',
        ];
        yield 'Locallang label with inner and outer whitespace' => [
            '    LLL:    EXT:    core/Resources/Private/Language/locallang_core.xlf:cm.editcontent    ',
            'Edit content',
        ];
    }

    private function ensureLocalizationScenarioWorks(string $locale, string $languageFile, array $expectedLabels): void
    {
        $subject = $this->get(LanguageServiceFactory::class)->create($locale);

        foreach ($expectedLabels as $label => $expectedLabel) {
            self::assertEquals($expectedLabel, $subject->sL(sprintf('LLL:%s:%s', $languageFile, $label)));
        }
    }

    #[DataProvider('ensureVariousLocalizationScenariosWorkDataProvider')]
    #[Test]
    public function ensureVariousLocalizationScenariosWork(string $locale, array $expectedLabels): void
    {
        $this->ensureLocalizationScenarioWorks($locale, self::LANGUAGE_FILE, $expectedLabels);
    }

    public static function ensureVariousLocalizationScenariosWorkDataProvider(): \Generator
    {
        yield 'Can handle localization for native language' => [
            'locale' => 'default',
            'expectedLabels' => [
                'label1' => 'This is label #1',
                'label2' => 'This is label #2',
                'label3' => 'This is label #3',
            ],
        ];
        yield 'Can handle localization for available translation' => [
            'locale' => 'fr',
            'expectedLabels' => [
                'label1' => 'Ceci est le libellé no. 1',
                'label2' => 'Ceci est le libellé no. 2',
                'label3' => 'Ceci est le libellé no. 3',
            ],
        ];
        yield 'Can handle localization for missing translation' => [
            'locale' => 'de',
            'expectedLabels' => [
                'label1' => 'This is label #1',
                'label2' => 'This is label #2',
                'label3' => 'This is label #3',
            ],
        ];
    }

    #[DataProvider('ensureVariousLocalizationOverrideScenariosWorkDataProvider')]
    #[Test]
    public function ensureVariousLocalizationOverrideScenariosWork(string $locale, array $expectedLabels): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides'][self::LANGUAGE_FILE][] = self::LANGUAGE_FILE_OVERRIDE;
        $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']['de'][self::LANGUAGE_FILE][] = self::LANGUAGE_FILE_OVERRIDE_DE;
        $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']['fr'][self::LANGUAGE_FILE][] = self::LANGUAGE_FILE_OVERRIDE_FR;

        $this->ensureLocalizationScenarioWorks($locale, self::LANGUAGE_FILE, $expectedLabels);
    }

    public static function ensureVariousLocalizationOverrideScenariosWorkDataProvider(): \Generator
    {
        yield 'Can override localization for native translation' => [
            'locale' => 'default',
            'expectedLabels' => [
                'label1' => 'This is my 1st label',
                'label2' => 'This is my 2nd label',
                'label3' => 'This is label #3',
            ],
        ];
        yield 'Can override localization for existing translation' => [
            'locale' => 'fr',
            'expectedLabels' => [
                'label1' => 'Ceci est mon 1er libellé',
                'label2' => 'Ceci est le libellé no. 2',
                'label3' => 'Ceci est mon 3e libellé',
            ],
        ];
        yield 'Can override localization for missing translation' => [
            'locale' => 'de',
            'expectedLabels' => [
                'label1' => 'Das ist Beschriftung 1',
                'label2' => 'Das ist Beschriftung 2',
                'label3' => 'Das ist Beschriftung 3',
            ],
        ];
    }

    #[DataProvider('ensureVariousLocalizationOverrideScenariosForCoreExtensionWorkDataProvider')]
    #[Test]
    public function ensureVariousLocalizationOverrideScenariosForCoreExtensionWork(string $locale, array $expectedLabels): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides'][self::LANGUAGE_FILE_CORE][] = self::LANGUAGE_FILE_CORE_OVERRIDE;
        $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']['fr'][self::LANGUAGE_FILE_CORE][] = self::LANGUAGE_FILE_CORE_OVERRIDE_FR;

        $this->ensureLocalizationScenarioWorks($locale, self::LANGUAGE_FILE_CORE, $expectedLabels);
    }

    public static function ensureVariousLocalizationOverrideScenariosForCoreExtensionWorkDataProvider(): \Generator
    {
        yield 'Can override localization of core for native locale' => [
            'locale' => 'default',
            'expectedLabels' => [
                'about' => 'Overriden About',
                'help' => 'Overriden Help',
                'ok' => 'Overriden OK',
            ],
        ];
        yield 'Can override localization of core for foreign locale' => [
            'locale' => 'fr',
            'expectedLabels' => [
                'about' => 'A propos',
                'help' => 'Aide',
                'ok' => 'OK',
            ],
        ];
    }

    #[DataProvider('ensureMultiLanguageTranslationInSameContextWorkDataProvider')]
    #[Test]
    public function ensureMultiLanguageTranslationInSameContextWork(array $expectedLabelSet): void
    {
        foreach ($expectedLabelSet as $locale => $expectedLabels) {
            $this->ensureLocalizationScenarioWorks($locale, self::LANGUAGE_FILE, $expectedLabels);
        }
    }

    public static function ensureMultiLanguageTranslationInSameContextWorkDataProvider(): \Generator
    {
        yield 'Multi language translation in same context works with default first' => [
            'expectedLabelSet' => [
                'default' => [
                    'label1' => 'This is label #1',
                    'label2' => 'This is label #2',
                    'label3' => 'This is label #3',
                ],
                'fr' => [
                    'label1' => 'Ceci est le libellé no. 1',
                    'label2' => 'Ceci est le libellé no. 2',
                    'label3' => 'Ceci est le libellé no. 3',
                ],
                'de' => [
                    'label1' => 'This is label #1',
                    'label2' => 'This is label #2',
                    'label3' => 'This is label #3',
                ],
            ],
        ];
        yield 'Multi language translation in same context works with default last' => [
            'expectedLabelSet' => [
                'fr' => [
                    'label1' => 'Ceci est le libellé no. 1',
                    'label2' => 'Ceci est le libellé no. 2',
                    'label3' => 'Ceci est le libellé no. 3',
                ],
                'de' => [
                    'label1' => 'This is label #1',
                    'label2' => 'This is label #2',
                    'label3' => 'This is label #3',
                ],
                'default' => [
                    'label1' => 'This is label #1',
                    'label2' => 'This is label #2',
                    'label3' => 'This is label #3',
                ],
            ],
        ];
    }
}
