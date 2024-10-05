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

namespace TYPO3\CMS\Core\Tests\Functional\Routing\Aspect;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Routing\Aspect\PersistedAliasMapper;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PersistedAliasMapperTest extends FunctionalTestCase
{
    private const ASPECT_CONFIGURATION = [
        'tableName' => 'tt_content',
        'routeFieldName' => 'header',
    ];

    private const SLUG_CONFIGURATION = [
        'type' => 'slug',
        'generatorOptions' => [
            'prefixParentPageSlug' => false,
        ],
        'fallbackCharacter' => '-',
        'required' => true,
        'eval' => 'uniqueInSite',
        'default' => '',
    ];

    private const LANGUAGE_MAP = [
        'es-es' => 3,
        'fr-ca' => 2,
        'fr-fr' => 1,
        'default' => 0,
    ];

    private const SITE_ADDITION = [
        'acme' => 0,
        'other' => 4000,
    ];

    /** @var Site[] */
    private array $sites;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withDatabaseSnapshot(function () {
            $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
            $backendUser = $this->setUpBackendUser(1);
            $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
            $scenarioFile = __DIR__ . '/Fixtures/AspectScenario.yaml';
            $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
            $writer = DataHandlerWriter::withBackendUser($backendUser);
            $writer->invokeFactory($factory);
            if (!empty($writer->getErrors())) {
                self::fail(var_export($writer->getErrors(), true));
            }
        });

        // declare tt_content.header as `slug` field having `uniqueInSite` set
        $tableName = self::ASPECT_CONFIGURATION['tableName'];
        $fieldName = self::ASPECT_CONFIGURATION['routeFieldName'];
        $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] = self::SLUG_CONFIGURATION;

        $languages = [
            [
                'languageId' => 3,
                'base' => '/es-es/',
                'locale' => 'es_ES.UTF-8',
                'fallbackType' => 'fallback',
                'fallbacks' => [0],
                'title' => 'Spanish',
            ],
            [
                'languageId' => 2,
                'base' => '/fr-ca/',
                'locale' => 'fr_CA.UTF-8',
                'fallbackType' => 'fallback',
                'fallbacks' => [1, 0],
                'title' => 'Franco-Canadian',
            ],
            [
                'languageId' => 1,
                'base' => '/fr-fr/',
                'locale' => 'fr_FR.UTF-8',
                'fallbackType' => 'fallback',
                'fallbacks' => [0],
                'French',
            ],
            [
                'languageId' => 0,
                'base' => '/en-us/',
                'locale' => 'en_US.UTF-8',
            ],
        ];
        $this->sites = [
            'acme' => new Site('acme-inc', 1000, [
                'identifier' => 'acme-inc',
                'rootPageId' => 1000,
                'base' => 'https://acme.com/',
                'languages' => $languages,
            ]),
            'other' => new Site('other-inc', 5000, [
                'identifier' => 'other-inc',
                'rootPageId' => 5000,
                'base' => 'https://other.com/',
                'languages' => $languages,
            ]),
        ];
        $this->writeSiteConfiguration($this->sites['acme']);
        $this->writeSiteConfiguration($this->sites['other']);
    }

    private function writeSiteConfiguration(Site $site): void
    {
        // ensure no previous site configuration influences the test
        $path = $this->instancePath . '/typo3conf/sites';
        $cache = $this->get('cache.core');
        $eventDispatcher = $this->get(EventDispatcherInterface::class);
        GeneralUtility::rmdir($path . '/' . $site->getIdentifier(), true);
        (new SiteWriter($path, $eventDispatcher, $cache, $this->get(YamlFileLoader::class)))
            ->write($site->getIdentifier(), $site->getConfiguration());
    }

    public static function languageAwareRecordsAreResolvedDataProvider(): array
    {
        $baseDataSet = [
            'non-existing, default language' => ['this-value-does-not-exist', 'default', null],

            '30xx-slug, default language' => ['30xx-slug', 'default', '3010'],
            '30xx-slug, fr-fr language' => ['30xx-slug', 'fr-fr', '3010'],
            '30xx-slug, fr-ca language' => ['30xx-slug', 'fr-ca', '3010'],

            '30xx-slug-fr-ca, fr-ca language' => ['30xx-slug-fr-ca', 'fr-ca', '3010'],
            // '30xx-slug-fr-ca' available in default language as well, fallbacks to that one
            '30xx-slug-fr-ca, fr-fr language' => ['30xx-slug-fr-ca', 'fr-fr', '3030'],
            // '30xx-slug-fr-ca' available in default language, use it directly
            '30xx-slug-fr-ca, default language' => ['30xx-slug-fr-ca', 'default', '3030'],

            '30xx-slug-fr, fr-ca language' => ['30xx-slug-fr', 'fr-ca', '3010'],
            '30xx-slug-fr, fr-fr language' => ['30xx-slug-fr', 'fr-fr', '3010'],
            // '30xx-slug-fr-ca' available in default language, use it directly
            '30xx-slug-fr, default language' => ['30xx-slug-fr', 'default', '3020'],

            // basically the same, but being stored in reverse order in database
            '40xx-slug, default language' => ['40xx-slug', 'default', '4040'],
            '40xx-slug, fr-fr language' => ['40xx-slug', 'fr-fr', '4040'],
            '40xx-slug, fr-ca language' => ['40xx-slug', 'fr-ca', '4040'],

            '40xx-slug-fr-ca, fr-ca language' => ['40xx-slug-fr-ca', 'fr-ca', '4040'],
            // '40xx-slug-fr-ca' available in default language as well, fallbacks to that one
            '40xx-slug-fr-ca, fr-fr language' => ['40xx-slug-fr-ca', 'fr-fr', '4030'],
            // '40xx-slug-fr-ca' available in default language, use it directly
            '40xx-slug-fr-ca, default language' => ['40xx-slug-fr-ca', 'default', '4030'],

            '40xx-slug-fr, fr-ca language' => ['40xx-slug-fr', 'fr-ca', '4040'],
            '40xx-slug-fr, fr-fr language' => ['40xx-slug-fr', 'fr-fr', '4040'],
            // '40xx-slug-fr-ca' available in default language, use it directly
            '40xx-slug-fr, default language' => ['40xx-slug-fr', 'default', '4020'],
        ];
        // permute $baseDataSet to be either prepended
        // with site identifier argument 'acme' or 'other'
        $dataSet = [];
        foreach (['acme', 'other'] as $site) {
            foreach ($baseDataSet as $key => $arguments) {
                array_unshift($arguments, $site);
                $dataSet[$site . ':' . $key] = $arguments;
            }
        }
        return $dataSet;
    }

    #[DataProvider('languageAwareRecordsAreResolvedDataProvider')]
    #[Test]
    public function languageAwareRecordsAreResolved(string $identifier, string $requestValue, string $language, ?string $expectation): void
    {
        $subject = new PersistedAliasMapper(self::ASPECT_CONFIGURATION);
        $subject->setSiteLanguage(
            $this->sites[$identifier]->getLanguageById(self::LANGUAGE_MAP[$language])
        );
        $subject->setSite(
            $this->sites[$identifier]
        );
        if ($expectation !== null) {
            $expectation = (string)((int)$expectation + self::SITE_ADDITION[$identifier]);
        }
        self::assertSame($expectation, $subject->resolve($requestValue));
    }

    public static function recordVisibilityDataProvider(): array
    {
        $rawContext = new Context();
        $visibleContext = new Context();
        $visibleContext->setAspect(
            'visibility',
            new VisibilityAspect(false, true, false)
        );
        $frontendGroupsContext = new Context();
        $frontendGroupsContext->setAspect(
            'frontend.user',
            new UserAspect(null, [13])
        );
        $scheduledContext = new Context();
        $scheduledContext->setAspect(
            'date',
            new DateTimeAspect(new \DateTimeImmutable('@20000'))
        );

        return [
            'hidden-visibility-slug, raw context' => [
                $rawContext,
                ['slug' => 'hidden-visibility-slug', 'uid' => '4051'],
                false,
            ],
            // fe_group slugs are always considered
            'restricted-visibility-slug, raw context' => [
                $rawContext,
                ['slug' => 'restricted-visibility-slug', 'uid' => '4052'],
                true,
            ],
            'scheduled-visibility-slug, raw context' => [
                $rawContext,
                ['slug' => 'scheduled-visibility-slug', 'uid' => '4053'],
                false,
            ],
            'hidden-visibility-slug, visibility context (include hidden content)' => [
                $visibleContext,
                ['slug' => 'hidden-visibility-slug', 'uid' => '4051'],
                true,
            ],
            // fe_group slugs are always considered
            'restricted-visibility-slug, frontend-groups context (13)' => [
                $frontendGroupsContext,
                ['slug' => 'restricted-visibility-slug', 'uid' => '4052'],
                true,
            ],
            'scheduled-visibility-slug, scheduled context (timestamp 20000)' => [
                $scheduledContext,
                ['slug' => 'scheduled-visibility-slug', 'uid' => '4053'],
                false, // @todo actually `true`, Start-/EndTimeRestriction do not support Context, yet
            ],
        ];
    }

    #[DataProvider('recordVisibilityDataProvider')]
    #[Test]
    public function recordVisibilityIsConsideredForResolving(Context $context, array $parameters, bool $expectation): void
    {
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $expectedResult = $expectation ? $parameters['uid'] : null;
        $subject = new PersistedAliasMapper(self::ASPECT_CONFIGURATION);
        $subject->setSiteLanguage($this->sites['acme']->getLanguageById(0));
        $subject->setSite($this->sites['acme']);
        self::assertSame($expectedResult, $subject->resolve($parameters['slug']));
    }

    #[DataProvider('recordVisibilityDataProvider')]
    #[Test]
    public function recordVisibilityIsConsideredForGeneration(Context $context, array $parameters, bool $expectation): void
    {
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $expectedResult = $expectation ? $parameters['slug'] : null;
        $subject = new PersistedAliasMapper(self::ASPECT_CONFIGURATION);
        $subject->setSiteLanguage($this->sites['acme']->getLanguageById(0));
        $subject->setSite($this->sites['acme']);
        self::assertSame($expectedResult, $subject->generate($parameters['uid']));
    }

    #[Test]
    public function generateWithUidOfExistingPageReturnsPageSlug(): void
    {
        $subject = new PersistedAliasMapper(self::ASPECT_CONFIGURATION);
        $subject->setSiteLanguage($this->sites['acme']->getLanguageById(0));
        $subject->setSite($this->sites['acme']);
        $result = $subject->generate('3010');
        self::assertSame('30xx-slug', $result);
    }

    #[Test]
    public function generateWithUidOfExistingPageSuffixedWithGarbageStringReturnsNull(): void
    {
        $subject = new PersistedAliasMapper(self::ASPECT_CONFIGURATION);
        $subject->setSiteLanguage($this->sites['acme']->getLanguageById(0));
        $subject->setSite($this->sites['acme']);
        $result = $subject->generate('3010-i-am-garbage');
        self::assertNull($result);
    }
}
