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

namespace TYPO3\CMS\Lowlevel\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Lowlevel\Command\InspectCommand;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;
use TYPO3\CMS\Lowlevel\Tests\Functional\Command\Fixtures\InspectFixtureProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class InspectCommandTest extends FunctionalTestCase
{
    private const string LABELS = 'LLL:EXT:lowlevel/Tests/Functional/Command/Fixtures/Language/inspect.xlf';

    protected array $coreExtensionsToLoad = [
        'lowlevel',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $this->registerFixtureProvider('fixture', [
            'site' => ['name' => 'Fixture site', 'port' => 443, 'secure' => true],
            'stack' => ['typo3/cms-frontend/timetracker' => ['target' => 'Timetracker']],
            'labels' => [
                'greeting' => self::LABELS . ':greeting',
                'untranslated' => self::LABELS . ':untranslated',
                'missing' => self::LABELS . ':doesNotExist',
                'bareDomain' => 'core.form.tabs:general',
                'notADomain' => 'redirects:slugChanged',
                'embedded' => '--div--;' . self::LABELS . ':greeting,header',
            ],
        ]);
        $this->registerFixtureProvider('broken', new \RuntimeException('it requires a web request'));
    }

    private function registerFixtureProvider(string $identifier, array|\Throwable $configuration): void
    {
        $this->get(ProviderRegistry::class)->registerProvider(
            new InspectFixtureProvider($configuration),
            ['identifier' => $identifier, 'label' => ucfirst($identifier) . ' provider', 'group' => 'Test group']
        );
    }

    private function execute(array $input): CommandTester
    {
        $tester = new CommandTester($this->get(InspectCommand::class));
        $tester->execute($input, ['capture_stderr_separately' => true]);
        return $tester;
    }

    #[Test]
    public function listsProvidersGroupedLikeTheBackendModule(): void
    {
        $tester = $this->execute([]);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $core = strpos($display, 'System & Infrastructure');
        $data = strpos($display, 'Data & Content');
        $custom = strpos($display, 'Test group');
        self::assertIsInt($core);
        self::assertIsInt($data);
        self::assertIsInt($custom);
        self::assertLessThan($data, $core);
        self::assertLessThan($custom, $data);
        self::assertStringContainsString('confVars', $display);
        self::assertStringContainsString('Fixture provider', $display);
    }

    #[Test]
    public function providerTableRowsAreAlignedWhenLabelsContainAngleBrackets(): void
    {
        $display = $this->execute([])->getDisplay();
        self::assertStringContainsString('User Settings (BE_USER->uc) ', $display);
        $widths = array_unique(array_map(mb_strwidth(...), explode("\n", trim($display))));
        self::assertCount(1, $widths);
    }

    #[Test]
    public function listsProvidersAsJsonWithGroupKeyAndLabel(): void
    {
        $tester = $this->execute(['--json' => true]);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $entries = array_column(json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR), null, 'identifier');
        self::assertSame(
            ['identifier' => 'confVars', 'label' => 'TYPO3_CONF_VARS (Global Configuration)', 'group' => 'core', 'groupLabel' => 'System & Infrastructure'],
            $entries['confVars']
        );
        self::assertSame('Test group', $entries['fixture']['group']);
    }

    #[Test]
    public function listsProvidersAsFlatLines(): void
    {
        $tester = $this->execute(['--flat' => true]);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('fixture = "Fixture provider"' . PHP_EOL, $tester->getDisplay());
    }

    #[Test]
    public function showsProviderDataAsTree(): void
    {
        $tester = $this->execute(['provider' => 'fixture', 'path' => ['site']]);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame('name = "Fixture site"' . PHP_EOL . 'port = 443' . PHP_EOL . 'secure = true' . PHP_EOL, $tester->getDisplay());
    }

    #[Test]
    public function showsSelectedSingleValueAsJsonInEveryFormat(): void
    {
        foreach (['tree' => [], 'flat' => ['--flat' => true], 'json' => ['--json' => true]] as $format => $options) {
            $tester = $this->execute(['provider' => 'fixture', 'path' => ['site', 'name'], ...$options]);
            self::assertSame('"Fixture site"' . PHP_EOL, $tester->getDisplay(), $format);
        }
    }

    #[Test]
    public function showsFlatPathsRelativeToTheSelection(): void
    {
        $tester = $this->execute(['provider' => 'fixture', 'path' => ['stack'], '--flat' => true]);
        self::assertSame('typo3/cms-frontend/timetracker target = "Timetracker"' . PHP_EOL, $tester->getDisplay());
    }

    #[Test]
    public function showsCompleteJson(): void
    {
        $tester = $this->execute(['provider' => 'fixture', 'path' => ['site'], '--json' => true]);
        self::assertSame(['name' => 'Fixture site', 'port' => 443, 'secure' => true], json_decode($tester->getDisplay(), true));
    }

    public static function outputFormatDataProvider(): array
    {
        return [
            'tree' => [['--depth' => '0']],
            'flat' => [['--flat' => true]],
            'json' => [['--json' => true]],
        ];
    }

    #[DataProvider('outputFormatDataProvider')]
    #[Test]
    public function blindedConfigurationOptionsStayBlindedInEveryFormat(array $options): void
    {
        $secrets = [
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = 'smtp-password-to-be-blinded',
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Secondary']['password'] = 'db-password-to-be-blinded',
        ];
        $tester = $this->execute(['provider' => 'confVars', ...$options]);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        foreach ($secrets as $secret) {
            self::assertNotSame('', $secret);
            self::assertStringNotContainsString($secret, $tester->getDisplay());
        }
        self::assertStringContainsString('"******"', $tester->getDisplay());
    }

    #[Test]
    public function blindedSingleValueStaysBlinded(): void
    {
        $tester = $this->execute(['provider' => 'confVars', 'path' => ['SYS', 'encryptionKey']]);
        self::assertSame('"******"' . PHP_EOL, $tester->getDisplay());
    }

    #[DataProvider('outputFormatDataProvider')]
    #[Test]
    public function unsafeCharactersAreEscapedInEveryFormat(array $options): void
    {
        $this->registerFixtureProvider('unsafe', [
            "key\u{9B}" => [
                'escape' => "\e[31m",
                'controlSequenceIntroducer' => "\u{9B}31m",
                'rightToLeftOverride' => "\u{202E}txt.exe",
                'label' => self::LABELS . ":greeting \u{202E}",
            ],
        ]);
        $tester = $this->execute(['provider' => 'unsafe', ...$options, '--resolve-labels' => null]);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertDoesNotMatchRegularExpression('/\e|\xC2\x9B|\xE2\x80\xAE/', $tester->getDisplay());
        self::assertStringContainsString('\u202etxt.exe', $tester->getDisplay());
    }

    #[Test]
    public function unsafeCharactersAreEscapedInProviderListAndErrors(): void
    {
        $this->get(ProviderRegistry::class)->registerProvider(
            new InspectFixtureProvider(["site\u{9B}" => 1]),
            ['identifier' => 'unsafe', 'label' => "Unsafe\u{202E} provider", 'group' => "Group\u{9B}"]
        );
        $list = $this->execute([])->getDisplay();
        $error = preg_replace('/\s+/', ' ', $this->execute(['provider' => 'unsafe', 'path' => ['site']])->getErrorOutput());
        self::assertDoesNotMatchRegularExpression('/\xC2\x9B|\xE2\x80\xAE/', $list . $error);
        self::assertStringContainsString('Unsafe\u202e provider', $list);
        self::assertStringContainsString('Group\u009b', $list);
        self::assertStringContainsString('Did you mean: site\u009b?', $error);
    }

    public static function invalidInputDataProvider(): array
    {
        return [
            'unknown provider' => [['provider' => 'doesNotExist'], 'No provider "doesNotExist"'],
            'unknown key with suggestion' => [['provider' => 'fixture', 'path' => ['sit']], 'Did you mean: site?'],
            'key below a single value' => [['provider' => 'fixture', 'path' => ['site', 'name', 'x']], 'is a single value'],
            'flat and json combined' => [['--flat' => true, '--json' => true], '--flat and --json cannot be combined'],
            'depth with json' => [['provider' => 'fixture', '--json' => true, '--depth' => '2'], '--depth only applies to the tree'],
            'depth without provider' => [['--depth' => '1'], '--depth needs a provider'],
            'depth not a number' => [['provider' => 'fixture', '--depth' => 'x'], '--depth expects a number'],
            'no size limit with tree' => [['provider' => 'fixture', '--no-size-limit' => true], '--no-size-limit only applies'],
            'invalid language' => [['provider' => 'fixture', '--resolve-labels' => 'german'], 'Unknown language "german"'],
        ];
    }

    #[DataProvider('invalidInputDataProvider')]
    #[Test]
    public function invalidInputFailsWithExitCodeTwoOnStderr(array $input, string $expectedError): void
    {
        $tester = $this->execute($input);
        self::assertSame(Command::INVALID, $tester->getStatusCode());
        self::assertSame('', $tester->getDisplay());
        self::assertStringContainsString($expectedError, preg_replace('/\s+/', ' ', $tester->getErrorOutput()));
    }

    #[Test]
    public function failingProviderFailsWithExitCodeOne(): void
    {
        $tester = $this->execute(['provider' => 'broken']);
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('it requires a web request', preg_replace('/\s+/', ' ', $tester->getErrorOutput()));
    }

    #[Test]
    public function outputAboveOneMegabyteIsRefusedUnlessTheLimitIsLifted(): void
    {
        $this->registerFixtureProvider('large', ['values' => array_fill(0, 30000, str_repeat('x', 40))]);
        foreach (['--json', '--flat'] as $format) {
            $refused = $this->execute(['provider' => 'large', $format => true]);
            self::assertSame(Command::FAILURE, $refused->getStatusCode(), $format);
            self::assertSame('', $refused->getDisplay(), $format);
            self::assertStringContainsString('above the 1 MB limit', preg_replace('/\s+/', ' ', $refused->getErrorOutput()), $format);
            $allowed = $this->execute(['provider' => 'large', $format => true, '--no-size-limit' => true]);
            self::assertSame(Command::SUCCESS, $allowed->getStatusCode(), $format);
        }
    }

    #[Test]
    public function labelsAreRawByDefault(): void
    {
        $tester = $this->execute(['provider' => 'fixture', 'path' => ['labels', 'greeting']]);
        self::assertSame('"' . self::LABELS . ':greeting"' . PHP_EOL, $tester->getDisplay());
    }

    #[Test]
    public function resolveLabelsTranslatesReferencesInEnglishByDefault(): void
    {
        $tester = $this->execute(['provider' => 'fixture', 'path' => ['labels'], '--json' => true, '--resolve-labels' => null]);
        self::assertSame(
            [
                'greeting' => 'Hello',
                'untranslated' => 'Only English',
                'missing' => self::LABELS . ':doesNotExist',
                'bareDomain' => 'General',
                'notADomain' => 'redirects:slugChanged',
                'embedded' => '--div--;Hello,header',
            ],
            json_decode($tester->getDisplay(), true)
        );
    }

    #[Test]
    public function resolveLabelsUsesTheRequestedLanguageAndFallsBackToEnglish(): void
    {
        $tester = $this->execute(['provider' => 'fixture', 'path' => ['labels'], '--flat' => true, '--resolve-labels' => 'de']);
        self::assertStringContainsString('greeting = "Hallo"', $tester->getDisplay());
        self::assertStringContainsString('untranslated = "Only English"', $tester->getDisplay());
    }

    #[Test]
    public function resolvedLabelsShowTheirReferenceInTheTree(): void
    {
        $tester = $this->execute(['provider' => 'fixture', 'path' => ['labels', 'greeting'], '--resolve-labels' => null]);
        self::assertSame('"Hello"  # ' . self::LABELS . ':greeting' . PHP_EOL, $tester->getDisplay());
    }

    #[Test]
    public function completesProvidersPathKeysAndLanguages(): void
    {
        $tester = new CommandCompletionTester($this->get(InspectCommand::class));
        self::assertContains('fixture', $tester->complete(['fi']));
        self::assertSame(['site', 'stack', 'labels'], $tester->complete(['fixture', '']));
        self::assertSame(['name', 'port', 'secure'], $tester->complete(['fixture', 'site', '']));
        self::assertContains('en', $tester->complete(['--resolve-labels=']));
    }
}
