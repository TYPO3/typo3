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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for translation domain aliasing via PSR-14 event
 *
 * Scenario:
 * - Extension renamed wizard.xlf to wizards.xlf
 * - Old domain: test_label_alias.wizard
 * - New domain: test_label_alias.wizards
 * - Event listener provides backward compatibility by aliasing old domain to new file
 */
final class TranslationDomainAliasTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_label_alias',
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
     * Test that the new domain works (normal behavior)
     */
    #[Test]
    public function newDomainResolvesToCorrectFile(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);

        // New domain should resolve to wizards.xlf
        $result = $subject->mapDomainToFileName('test_label_alias.wizards');

        self::assertSame(
            'EXT:test_label_alias/Resources/Private/Language/wizards.xlf',
            $result
        );
    }

    /**
     * Test that the old domain is aliased to the new file via event listener
     */
    #[Test]
    public function oldDomainIsAliasedToNewFile(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);

        // Old domain "wizard" should be aliased to "wizards" by the event listener
        // The event listener rewrites the domain from "wizard" to "wizards"
        $result = $subject->mapDomainToFileName('test_label_alias.wizard');

        self::assertSame(
            'EXT:test_label_alias/Resources/Private/Language/wizards.xlf',
            $result,
            'Old domain "wizard" should resolve to wizards.xlf via alias'
        );
    }

    /**
     * Test that file-to-domain mapping generates the new domain name
     */
    #[Test]
    public function fileNameToDomainGeneratesNewDomain(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);

        // wizards.xlf should map to the new domain
        $domain = $subject->mapFileNameToDomain(
            'EXT:test_label_alias/Resources/Private/Language/wizards.xlf'
        );

        self::assertStringEndsWith('.wizards', $domain);
    }

    /**
     * Test that both old and new domains work in findLabelResourcesInPackage
     */
    #[Test]
    public function packageResourcesContainNewDomain(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);
        $resources = $subject->findLabelResourcesInPackage('test_label_alias');

        // Should contain the new domain (with extension key format)
        self::assertArrayHasKey('test_label_alias.wizards', $resources);

        // Should point to wizards.xlf
        self::assertSame(
            'EXT:test_label_alias/Resources/Private/Language/wizards.xlf',
            $resources['test_label_alias.wizards']
        );
    }

    /**
     * Test the complete workflow: old domain -> event rewrite -> new file resolution
     */
    #[Test]
    public function completeAliasWorkflow(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);

        // Step 1: Old domain is used
        $oldDomain = 'test_label_alias.wizard';

        // Step 2: Event listener rewrites to new domain
        // Step 3: New domain resolves to wizards.xlf
        $resolvedFile = $subject->mapDomainToFileName($oldDomain);

        // Step 4: Verify we get the new file
        self::assertSame(
            'EXT:test_label_alias/Resources/Private/Language/wizards.xlf',
            $resolvedFile
        );

        // Verify both domains resolve to the same file
        $newDomain = 'test_label_alias.wizards';
        $resolvedFileNew = $subject->mapDomainToFileName($newDomain);

        self::assertSame(
            $resolvedFile,
            $resolvedFileNew,
            'Both old and new domains should resolve to the same file'
        );
    }

    /**
     * Test that the event is actually being called
     */
    #[Test]
    public function eventListenerIsInvoked(): void
    {
        $subject = $this->get(TranslationDomainMapper::class);

        // If the event listener is NOT invoked, this would return the domain as-is
        // (since wizard.xlf doesn't exist, only wizards.xlf exists)
        $result = $subject->mapDomainToFileName('test_label_alias.wizard');

        // If we get wizards.xlf back, the event was invoked and rewrote the domain
        self::assertStringEndsWith(
            'wizards.xlf',
            $result,
            'Event listener must have rewritten wizard -> wizards'
        );
    }
}
