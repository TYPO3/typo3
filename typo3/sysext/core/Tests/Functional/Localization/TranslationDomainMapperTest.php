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
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for TranslationDomainMapper
 *
 * Tests the domain generation rules as documented in:
 * - Class documentation in TranslationDomainMapper.php
 * - Feature RST file Feature-93334-TranslationDomainMapping.rst
 *
 * Covers all file patterns including:
 * - Resources/Private/Language/ files
 * - Configuration/Sets/ files (site sets)
 * - Locale-prefixed files
 * - Various naming conventions and subdirectories
 */
final class TranslationDomainMapperTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_translation_domain',
    ];

    protected bool $initializeDatabase = false;

    protected array $configurationToUseInTestInstance = [
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

    /**
     * Test mapFileNameToDomain with various file patterns
     *
     * Domain Generation Rules (from class documentation):
     * - subdirectory "Resources/Private/Language" is omitted
     * - subdirectory "Configuration/Sets/{set-name}/labels.xlf" is replaced with "sets.{set-name}"
     * - Upper-camel-case is converted to lower-dash-case
     * - "locallang.xlf" is mapped to "messages"
     * - "locallang_{mysuffix}.xlf" is replaced via "mysuffix"
     * - Locale prefixes (e.g. "de.locallang.xlf") are ignored in the domain name
     */
    #[DataProvider('mapFileNameToDomainDataProvider')]
    #[Test]
    public function mapFileNameToDomain(string $fileName, string $expectedDomain): void
    {
        $subject = $this->get(TranslationDomainMapper::class);
        $actualDomain = $subject->mapFileNameToDomain($fileName);
        self::assertSame($expectedDomain, $actualDomain);
    }

    public static function mapFileNameToDomainDataProvider(): \Generator
    {
        // Rule: "locallang.xlf" is mapped to "messages"
        yield 'locallang.xlf maps to messages' => [
            'EXT:test_translation_domain/Resources/Private/Language/locallang.xlf',
            'test_translation_domain.messages',
        ];

        // Rule: "locallang_{suffix}.xlf" maps to "{suffix}"
        yield 'locallang_toolbar.xlf maps to toolbar' => [
            'EXT:test_translation_domain/Resources/Private/Language/locallang_toolbar.xlf',
            'test_translation_domain.toolbar',
        ];

        // Rule: snake_case is preserved
        yield 'locallang_sudo_mode.xlf with snake_case is preserved' => [
            'EXT:test_translation_domain/Resources/Private/Language/locallang_sudo_mode.xlf',
            'test_translation_domain.sudo_mode',
        ];

        // Rule: Subdirectories use dot notation
        yield 'Form subdirectory becomes dot notation' => [
            'EXT:test_translation_domain/Resources/Private/Language/Form/locallang_tabs.xlf',
            'test_translation_domain.form.tabs',
        ];

        // Rule: UpperCamelCase converted to snake_case
        yield 'SudoMode subdirectory with UpperCamelCase converts to snake_case' => [
            'EXT:test_translation_domain/Resources/Private/Language/SudoMode/locallang.xlf',
            'test_translation_domain.sudo_mode.messages',
        ];

        // Rule: Locale prefixes are ignored
        yield 'Locale prefix de. is ignored in domain generation' => [
            'EXT:test_translation_domain/Resources/Private/Language/de.locallang.xlf',
            'test_translation_domain.messages',
        ];

        // Test with core extension
        yield 'Core extension uses extension key' => [
            'EXT:core/Resources/Private/Language/locallang.xlf',
            'core.messages',
        ];

        yield 'Core extension with subdirectory' => [
            'EXT:backend/Resources/Private/Language/Form/locallang_tabs.xlf',
            'backend.form.tabs',
        ];

        // Rule: Site Set labels (Configuration/Sets/{SetName}/labels.xlf -> sets.{set_name})
        yield 'Site Set labels.xlf maps to sets.{setname}' => [
            'EXT:test_translation_domain/Configuration/Sets/TestSet/labels.xlf',
            'test_translation_domain.sets.test_set',
        ];
    }

    /**
     * Test mapDomainToFileName with various domain patterns
     *
     * This tests the reverse mapping and verifies that domains
     * resolve to actual existing files.
     */
    #[DataProvider('mapDomainToFileNameDataProvider')]
    #[Test]
    public function mapDomainToFileName(string $domain, string $expectedFileName): void
    {
        $subject = $this->get(TranslationDomainMapper::class);
        $actualFileName = $subject->mapDomainToFileName($domain);
        self::assertSame($expectedFileName, $actualFileName);
    }

    public static function mapDomainToFileNameDataProvider(): \Generator
    {
        // Test extension key format
        // Note: messages.xlf wins over locallang.xlf due to alphabetical ordering
        yield 'Extension key format with messages' => [
            'test_translation_domain.messages',
            'EXT:test_translation_domain/Resources/Private/Language/messages.xlf',
        ];

        yield 'Extension key format with toolbar' => [
            'test_translation_domain.toolbar',
            'EXT:test_translation_domain/Resources/Private/Language/locallang_toolbar.xlf',
        ];

        yield 'Extension key format with snake_case suffix' => [
            'test_translation_domain.sudo_mode',
            'EXT:test_translation_domain/Resources/Private/Language/locallang_sudo_mode.xlf',
        ];

        yield 'Extension key format with subdirectory' => [
            'test_translation_domain.form.tabs',
            'EXT:test_translation_domain/Resources/Private/Language/Form/locallang_tabs.xlf',
        ];

        yield 'Extension key format with UpperCamelCase subdirectory' => [
            'test_translation_domain.sudo_mode.messages',
            'EXT:test_translation_domain/Resources/Private/Language/SudoMode/locallang.xlf',
        ];

        yield 'Extension key format with site set' => [
            'test_translation_domain.sets.test_set',
            'EXT:test_translation_domain/Configuration/Sets/TestSet/labels.xlf',
        ];
    }

    /**
     * Test that file references (EXT: prefix) are passed through unchanged
     */
    #[Test]
    public function mapDomainToFileNamePassesThroughFileReferences(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);
        $fileReference = 'EXT:test_translation_domain/Resources/Private/Language/locallang.xlf';
        $actualFileName = $subject->mapDomainToFileName($fileReference);
        self::assertSame($fileReference, $actualFileName);
    }

    /**
     * Test that absolute paths are passed through unchanged
     * Note: This only works for paths within allowed base paths
     */
    #[Test]
    public function mapDomainToFileNamePassesThroughFileReferencesWithAbsolutePath(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);
        // Use a path that starts with EXT: which will be resolved to absolute
        $fileReference = 'EXT:test_translation_domain/Resources/Private/Language/locallang.xlf';
        $actualFileName = $subject->mapDomainToFileName($fileReference);
        // Should pass through unchanged
        self::assertSame($fileReference, $actualFileName);
    }

    /**
     * Test findLabelResourcesInPackage returns all label files
     */
    #[Test]
    public function findLabelResourcesInPackageReturnsAllFiles(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);
        $resources = $subject->findLabelResourcesInPackage('test_translation_domain');

        // Should include all non-locale-prefixed files from Resources/Private/Language
        self::assertArrayHasKey('test_translation_domain.messages', $resources);
        self::assertArrayHasKey('test_translation_domain.toolbar', $resources);
        self::assertArrayHasKey('test_translation_domain.sudo_mode', $resources);
        self::assertArrayHasKey('test_translation_domain.form.tabs', $resources);
        self::assertArrayHasKey('test_translation_domain.sudo_mode.messages', $resources);

        // Should include site set files from Configuration/Sets
        self::assertArrayHasKey('test_translation_domain.sets.test_set', $resources);

        // Verify file paths are correct
        // Note: messages.xlf wins over locallang.xlf due to alphabetical ordering
        self::assertSame(
            'EXT:test_translation_domain/Resources/Private/Language/messages.xlf',
            $resources['test_translation_domain.messages']
        );

        self::assertSame(
            'EXT:test_translation_domain/Configuration/Sets/TestSet/labels.xlf',
            $resources['test_translation_domain.sets.test_set']
        );
    }

    /**
     * Test findLabelResourcesInPackage with composer package name
     */
    #[Test]
    public function findLabelResourcesInPackageAcceptsComposerName(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);
        $resources = $subject->findLabelResourcesInPackage('typo3tests/test-translation-domain');

        // Should work the same as with extension key
        self::assertArrayHasKey('test_translation_domain.messages', $resources);
    }

    /**
     * Test that domain-to-file-to-domain round-trip works
     */
    #[DataProvider('roundTripDataProvider')]
    #[Test]
    public function domainToFileAndBackRoundTrip(string $originalDomain, string $expectedDomain): void
    {
        $subject = $this->get(TranslationDomainMapper::class);

        // Domain -> File
        $fileName = $subject->mapDomainToFileName($originalDomain);

        // File -> Domain
        $resultDomain = $subject->mapFileNameToDomain($fileName);

        // Should get back extension key format
        self::assertSame($expectedDomain, $resultDomain);
    }

    public static function roundTripDataProvider(): \Generator
    {
        yield 'Extension key format' => ['test_translation_domain.messages', 'test_translation_domain.messages'];
        yield 'Extension key with subdirectory' => ['test_translation_domain.form.tabs', 'test_translation_domain.form.tabs'];
    }

    /**
     * Test case conversion rules
     */
    #[DataProvider('caseConversionDataProvider')]
    #[Test]
    public function caseConversionInDomains(string $fileName, string $expectedDomainPart): void
    {
        $subject = $this->get(TranslationDomainMapper::class);
        $domain = $subject->mapFileNameToDomain($fileName);
        self::assertStringContainsString($expectedDomainPart, $domain);
    }

    public static function caseConversionDataProvider(): \Generator
    {
        yield 'UpperCamelCase directory converts to snake_case' => [
            'EXT:test_translation_domain/Resources/Private/Language/SudoMode/locallang.xlf',
            'sudo_mode.messages',
        ];

        yield 'snake_case filename is preserved' => [
            'EXT:test_translation_domain/Resources/Private/Language/locallang_sudo_mode.xlf',
            'sudo_mode',
        ];

        yield 'Mixed case in site set converts to snake_case' => [
            'EXT:test_translation_domain/Configuration/Sets/TestSet/labels.xlf',
            'sets.test_set',
        ];
    }

    /**
     * Test that non-existent domains fall back to returning the domain as-is
     */
    #[Test]
    public function mapDomainToFileNameFallsBackForNonExistentDomain(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);
        $nonExistentDomain = 'test_translation_domain.non-existent-resource';
        $result = $subject->mapDomainToFileName($nonExistentDomain);

        // Should fall back to returning the domain itself
        self::assertSame($nonExistentDomain, $result);
    }

    /**
     * Test domain collision scenarios with explicit priority rules
     *
     * When multiple files map to the same domain (e.g., locallang.xlf and messages.xlf
     * both map to .messages), files without the "locallang" prefix have precedence.
     *
     * Priority order (highest to lowest):
     * 1. Files without "locallang" prefix (e.g., messages.xlf, tabs.xlf)
     * 2. Files with "locallang_" prefix (e.g., locallang_toolbar.xlf)
     * 3. Plain locallang.xlf
     */
    #[Test]
    public function domainCollisionPriority(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);
        $resources = $subject->findLabelResourcesInPackage('test_translation_domain');

        // Both locallang.xlf and messages.xlf map to the domain "messages"
        // messages.xlf should win because it has no "locallang" prefix (priority 3 > priority 1)
        $messagesFile = $resources['test_translation_domain.messages'];

        self::assertSame(
            'EXT:test_translation_domain/Resources/Private/Language/messages.xlf',
            $messagesFile,
            'messages.xlf should win over locallang.xlf due to higher priority'
        );
    }

    /**
     * Test that file-to-domain mapping is deterministic for both files
     */
    #[Test]
    public function fileNameToDomainIsConsistentDespiteCollisions(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);

        // Both files should map to the same domain
        $domainFromLocallang = $subject->mapFileNameToDomain(
            'EXT:test_translation_domain/Resources/Private/Language/locallang.xlf'
        );
        $domainFromMessages = $subject->mapFileNameToDomain(
            'EXT:test_translation_domain/Resources/Private/Language/messages.xlf'
        );

        // Both should generate the "messages" domain
        self::assertStringEndsWith('.messages', $domainFromLocallang);
        self::assertStringEndsWith('.messages', $domainFromMessages);
        self::assertSame($domainFromLocallang, $domainFromMessages);
    }

    /**
     * Test collision with locallang_tabs.xlf and tabs.xlf
     *
     * tabs.xlf should win because it has no "locallang" prefix (priority 3 > priority 2)
     */
    #[Test]
    public function tabsFileCollisionPriority(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);

        // Both locallang_tabs.xlf and tabs.xlf should map to "tabs" domain
        $domainFromLocallangTabs = $subject->mapFileNameToDomain(
            'EXT:test_translation_domain/Resources/Private/Language/locallang_tabs.xlf'
        );
        $domainFromTabs = $subject->mapFileNameToDomain(
            'EXT:test_translation_domain/Resources/Private/Language/tabs.xlf'
        );

        // Both generate same domain
        self::assertStringEndsWith('.tabs', $domainFromLocallangTabs);
        self::assertStringEndsWith('.tabs', $domainFromTabs);
        self::assertSame($domainFromLocallangTabs, $domainFromTabs);

        // Check which file wins in the domain-to-file mapping
        $resources = $subject->findLabelResourcesInPackage('test_translation_domain');
        $tabsFile = $resources['test_translation_domain.tabs'];

        // tabs.xlf should win (priority 3) over locallang_tabs.xlf (priority 2)
        self::assertSame(
            'EXT:test_translation_domain/Resources/Private/Language/tabs.xlf',
            $tabsFile,
            'tabs.xlf should win over locallang_tabs.xlf due to higher priority'
        );
    }

    /**
     * Test that direct file references always work regardless of collisions
     */
    #[Test]
    public function directFileReferencesWorkDespiteCollisions(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);

        // Direct file references should pass through unchanged
        $locallangRef = 'EXT:test_translation_domain/Resources/Private/Language/locallang.xlf';
        $messagesRef = 'EXT:test_translation_domain/Resources/Private/Language/messages.xlf';

        self::assertSame($locallangRef, $subject->mapDomainToFileName($locallangRef));
        self::assertSame($messagesRef, $subject->mapDomainToFileName($messagesRef));

        // Both files are accessible directly, collision only affects domain-based lookup
    }

    /**
     * Test priority levels explicitly
     *
     * Priority 3 (no "locallang") > Priority 2 ("locallang_" prefix) > Priority 1 (plain "locallang")
     */
    #[DataProvider('priorityLevelsDataProvider')]
    #[Test]
    public function filePriorityLevels(string $fileName, int $expectedPriority): void
    {
        $subject = $this->get(TranslationDomainMapper::class);

        // Use reflection to test the protected getFilePriority method
        $reflection = new \ReflectionClass($subject);
        $method = $reflection->getMethod('getFilePriority');

        $actualPriority = $method->invoke($subject, $fileName);

        self::assertSame(
            $expectedPriority,
            $actualPriority,
            sprintf('File "%s" should have priority %d', $fileName, $expectedPriority)
        );
    }

    public static function priorityLevelsDataProvider(): \Generator
    {
        // Priority 3: No "locallang" prefix
        yield 'messages.xlf has priority 3' => [
            'EXT:test/Resources/Private/Language/messages.xlf',
            3,
        ];

        yield 'tabs.xlf has priority 3' => [
            'EXT:test/Resources/Private/Language/tabs.xlf',
            3,
        ];

        yield 'custom.xlf has priority 3' => [
            'EXT:test/Resources/Private/Language/custom.xlf',
            3,
        ];

        // Priority 2: "locallang_" prefix
        yield 'locallang_toolbar.xlf has priority 2' => [
            'EXT:test/Resources/Private/Language/locallang_toolbar.xlf',
            2,
        ];

        yield 'locallang_tabs.xlf has priority 2' => [
            'EXT:test/Resources/Private/Language/locallang_tabs.xlf',
            2,
        ];

        yield 'locallang_sudo_mode.xlf has priority 2' => [
            'EXT:test/Resources/Private/Language/locallang_sudo_mode.xlf',
            2,
        ];

        // Priority 1: Plain "locallang"
        yield 'locallang.xlf has priority 1' => [
            'EXT:test/Resources/Private/Language/locallang.xlf',
            1,
        ];

        // Edge cases with locale prefixes
        yield 'de.messages.xlf has priority 3' => [
            'EXT:test/Resources/Private/Language/de.messages.xlf',
            3,
        ];

        yield 'de.locallang_toolbar.xlf has priority 2' => [
            'EXT:test/Resources/Private/Language/de.locallang_toolbar.xlf',
            2,
        ];

        yield 'de.locallang.xlf has priority 1' => [
            'EXT:test/Resources/Private/Language/de.locallang.xlf',
            1,
        ];
    }

    /**
     * Test that priority system works across different scenarios
     */
    #[Test]
    public function prioritySystemPreventsDeterministicCollisions(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);
        $resources = $subject->findLabelResourcesInPackage('test_translation_domain');

        // Verify all expected high-priority files are mapped
        self::assertSame(
            'EXT:test_translation_domain/Resources/Private/Language/messages.xlf',
            $resources['test_translation_domain.messages'],
            'messages.xlf (priority 3) should be mapped'
        );

        self::assertSame(
            'EXT:test_translation_domain/Resources/Private/Language/tabs.xlf',
            $resources['test_translation_domain.tabs'],
            'tabs.xlf (priority 3) should be mapped'
        );

        // Verify that files with only locallang_ prefix are still mapped when no collision
        self::assertSame(
            'EXT:test_translation_domain/Resources/Private/Language/locallang_toolbar.xlf',
            $resources['test_translation_domain.toolbar'],
            'locallang_toolbar.xlf (priority 2) should be mapped when no higher priority exists'
        );
    }
}
