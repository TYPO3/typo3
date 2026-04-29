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

namespace TYPO3\CMS\Redirects\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Redirects\Event\RedirectIntegrityCheckEvent;
use TYPO3\CMS\Redirects\Service\IntegrityService;
use TYPO3\CMS\Redirects\Service\RedirectService;
use TYPO3\CMS\Redirects\Utility\RedirectConflict;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class IntegrityServiceTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    // Needed to happify phpstan in combination with SiteBasedTestTrait
    protected const LANGUAGE_PRESETS = [
        'unused' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['redirects'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'simple-page',
            $this->buildSiteConfiguration(1, 'https://example.com'),
            [
                [
                    'languageId' => 0,
                    'title' => 'United States',
                    'locale' => 'en_US.UTF-8',
                ],
            ]
        );
        $this->writeSiteConfiguration(
            'localized-page',
            $this->buildSiteConfiguration(100, 'https://another.example.com'),
            [
                [
                    'languageId' => 0,
                    'title' => 'United States',
                    'locale' => 'en_US.UTF-8',
                ],
                [
                    'base' => '/de/',
                    'languageId' => 1,
                    'title' => 'DE',
                    'locale' => 'de_DE.UTF-8',
                ],
                [
                    'base' => '/fr/',
                    'languageId' => 2,
                    'title' => 'FR',
                    'locale' => 'fr_FR.UTF-8',
                ],
            ]
        );
    }

    /**
     * This is a regression test for forge issue #95650: Source slug matches page slug of translated
     * page WITHOUT language component (e.g.  /en, /de etc.).
     *
     * The integrity service should NOT detect this as a conflict.
     */
    #[Test]
    public function sourcePathWithMatchingSlugInLocalizedPageIsNotReportedAsConflict(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/IntegrityServiceTest_sourcePathWithMatchingSlugInLocalizedPageIsNotReportedAsConflict.csv');
        $subject = $this->get(IntegrityService::class);
        $result = $subject->findConflictingRedirects('localized-page');
        $this->assertExpectedPathsFromGenerator([], $result);
    }

    #[Test]
    public function conflictingRedirectsAreFoundForDefinedSiteOnly(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SimplePages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');
        $expectedConflicts = [
            [
                'uri' => 'https://example.com/',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => 'example.com',
                    'source_path' => '/',
                    'uid' => 7,
                ],
            ],
            [
                'uri' => 'https://example.com/about-us/we-are-here',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => '*',
                    'source_path' => '/about-us/we-are-here',
                    'uid' => 1,
                ],
            ],
            [
                'uri' => 'https://example.com/contact',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => 'example.com',
                    'source_path' => '/contact',
                    'uid' => 6,
                ],
            ],
            [
                'uri' => 'https://example.com/features',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => '*',
                    'source_path' => '/features',
                    'uid' => 9,
                ],
            ],
        ];
        $subject = $this->get(IntegrityService::class);
        $this->assertExpectedPathsFromGenerator($expectedConflicts, $subject->findConflictingRedirects('simple-page'));
    }

    #[Test]
    public function conflictingRedirectsAreFoundForLocalizedPages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LocalizedPages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');
        $expectedConflicts = [
            [
                'uri' => 'https://another.example.com/about-us/we-are-here',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => '*',
                    'source_path' => '/about-us/we-are-here',
                    'uid' => 1,
                ],
            ],
            [
                'uri' => 'https://another.example.com/de/merkmale',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => 'another.example.com',
                    'source_path' => '/de/merkmale',
                    'uid' => 4,
                ],
            ],
            [
                'uri' => 'https://another.example.com/features',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => 'another.example.com',
                    'source_path' => '/features',
                    'uid' => 8,
                ],
            ],
        ];
        $subject = $this->get(IntegrityService::class);
        $conflicts = $subject->findConflictingRedirects('localized-page');
        $this->assertExpectedPathsFromGenerator($expectedConflicts, $conflicts);
    }

    #[Test]
    public function conflictingRedirectsAreFoundForAllSites(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SimplePages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LocalizedPages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');
        $expectedConflicts = [
            [
                'uri' => 'https://example.com/',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => 'example.com',
                    'source_path' => '/',
                    'uid' => 7,
                ],
            ],
            [
                'uri' => 'https://example.com/about-us/we-are-here',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => '*',
                    'source_path' => '/about-us/we-are-here',
                    'uid' => 1,
                ],
            ],
            [
                'uri' => 'https://another.example.com/about-us/we-are-here',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => '*',
                    'source_path' => '/about-us/we-are-here',
                    'uid' => 1,
                ],
            ],
            [
                'uri' => 'https://another.example.com/de/merkmale',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => 'another.example.com',
                    'source_path' => '/de/merkmale',
                    'uid' => 4,
                ],
            ],
            [
                'uri' => 'https://example.com/contact',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => 'example.com',
                    'source_path' => '/contact',
                    'uid' => 6,
                ],
            ],
            [
                'uri' => 'https://example.com/features',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => '*',
                    'source_path' => '/features',
                    'uid' => 9,
                ],
            ],
            [
                'uri' => 'https://another.example.com/features',
                'redirect' => [
                    'integrity_status' => RedirectConflict::SELF_REFERENCE,
                    'source_host' => 'another.example.com',
                    'source_path' => '/features',
                    'uid' => 8,
                ],
            ],
        ];
        $subject = $this->get(IntegrityService::class);
        $this->assertExpectedPathsFromGenerator($expectedConflicts, $subject->findConflictingRedirects());
    }

    #[Test]
    public function checkRedirectTargetIntegrityYieldsNothingWithoutListeners(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');
        $subject = $this->get(IntegrityService::class);
        $result = iterator_to_array($subject->checkRedirectIntegrity());
        self::assertSame([], $result);
    }

    #[Test]
    public function checkRedirectTargetIntegrityDispatchesEventForEachRedirect(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');
        $eventDispatcher = new class implements EventDispatcherInterface {
            /** @var list<RedirectIntegrityCheckEvent> */
            public array $dispatchedEvents = [];
            public function dispatch(object $event): object
            {
                if ($event instanceof RedirectIntegrityCheckEvent) {
                    $this->dispatchedEvents[] = $event;
                    $event->setIntegrityStatus('test_broken');
                }
                return $event;
            }
        };
        $subject = new IntegrityService(
            $this->get(RedirectService::class),
            $this->get(SiteFinder::class),
            $this->get(ConnectionPool::class),
            $eventDispatcher,
            $this->get(TcaSchemaFactory::class),
        );
        $conflicts = iterator_to_array($subject->checkRedirectIntegrity());
        // 9 non-deleted redirects in fixture
        self::assertCount(9, $eventDispatcher->dispatchedEvents);
        self::assertCount(9, $conflicts);
        foreach ($conflicts as $conflict) {
            self::assertSame('test_broken', $conflict['redirect']['integrity_status']);
            self::assertArrayHasKey('uri', $conflict);
            self::assertArrayHasKey('uid', $conflict['redirect']);
            self::assertArrayHasKey('source_host', $conflict['redirect']);
            self::assertArrayHasKey('source_path', $conflict['redirect']);
        }
    }

    private function assertExpectedPathsFromGenerator(array $expectedConflicts, \Generator $generator): void
    {
        $matches = 0;
        foreach ($generator as $reportedConflict) {
            self::assertContains($reportedConflict, $expectedConflicts);
            $matches++;
        }
        self::assertSame(count($expectedConflicts), $matches);
    }
}
