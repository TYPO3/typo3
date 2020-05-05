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

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Routing\Aspect\PersistedAliasMapper;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PersistedAliasMapperTest extends FunctionalTestCase
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
        'eval' => 'required,uniqueInSite',
        'default' => ''
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

    /**
     * @var PersistedAliasMapper
     */
    private $subject;

    /**
     * @var Site[]
     */
    private $sites;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass(): void
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
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
                'base' => 'en_US.UTF-8',
                'locale' => '/en-us/',
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
        $this->subject = new PersistedAliasMapper(self::ASPECT_CONFIGURATION);
        $this->subject->setSiteLanguage($this->sites['acme']->getLanguageById(0));
        $this->subject->setSite($this->sites['acme']);
    }

    protected function setUpDatabase()
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $scenarioFile = __DIR__ . '/Fixtures/AspectScenario.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        if (!empty($writer->getErrors())) {
            self::fail(var_export($writer->getErrors(), true));
        }
    }

    protected function tearDown(): void
    {
        unset($this->subject, $this->sites);
        parent::tearDown();
    }

    public function languageAwareRecordsAreResolvedDataProvider(): array
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

    /**
     * @param string $identifier
     * @param string $requestValue
     * @param string $language
     * @param string|null $expectation
     *
     * @test
     * @dataProvider languageAwareRecordsAreResolvedDataProvider
     */
    public function languageAwareRecordsAreResolved(string $identifier, string $requestValue, string $language, ?string $expectation): void
    {
        $this->subject->setSiteLanguage(
            $this->sites[$identifier]->getLanguageById(self::LANGUAGE_MAP[$language])
        );
        $this->subject->setSite(
            $this->sites[$identifier]
        );
        if ($expectation !== null) {
            $expectation += self::SITE_ADDITION[$identifier];
            $expectation = (string)$expectation;
        }
        self::assertSame($expectation, $this->subject->resolve($requestValue));
    }

    public function recordVisibilityDataProvider(): array
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

    /**
     * @param Context $context
     * @param array $parameters
     * @param bool $expectation
     *
     * @test
     * @dataProvider recordVisibilityDataProvider
     */
    public function recordVisibilityIsConsideredForResolving(Context $context, array $parameters, bool $expectation): void
    {
        $this->subject->setContext($context);
        $expectedResult = $expectation ? $parameters['uid'] : null;
        self::assertSame($expectedResult, $this->subject->resolve($parameters['slug']));
    }

    /**
     * @param Context $context
     * @param array $parameters
     * @param bool $expectation
     *
     * @test
     * @dataProvider recordVisibilityDataProvider
     */
    public function recordVisibilityIsConsideredForGeneration(Context $context, array $parameters, bool $expectation): void
    {
        $this->subject->setContext($context);
        $expectedResult = $expectation ? $parameters['slug'] : null;
        self::assertSame($expectedResult, $this->subject->generate($parameters['uid']));
    }

    private function writeSiteConfiguration(Site $site): void
    {
        try {
            // ensure no previous site configuration influences the test
            $path = $this->instancePath . '/typo3conf/sites';
            GeneralUtility::rmdir($path . '/' . $site->getIdentifier(), true);
            GeneralUtility::makeInstance(SiteConfiguration::class, $path)->write($site->getIdentifier(), $site->getConfiguration());
        } catch (\Exception $exception) {
            self::markTestSkipped($exception->getMessage());
        }
    }
}
