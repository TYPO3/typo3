<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Functional\Routing\Aspect;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\Aspect\PersistedAliasMapper;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PersistedAliasMapperTest extends FunctionalTestCase
{
    private const ASPECT_CONFIGURATION = [
        'tableName' => 'tt_content',
        'routeFieldName' => 'header',
    ];

    /**
     * @var PersistedAliasMapper
     */
    private $subject;

    /**
     * @var SiteLanguage[]
     */
    private $languages;

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

        $this->languages = [
            'es-es' => new SiteLanguage(3, 'es_ES.UTF-8', new Uri('/es-es/'), [
                'fallbackType' => 'fallback',
                'fallbacks' => [0],
                'title' => 'Spanish',
            ]),
            'fr-ca' => new SiteLanguage(2, 'fr_CA.UTF-8', new Uri('/fr-ca/'), [
                'fallbackType' => 'fallback',
                'fallbacks' => [1, 0],
                'title' => 'Franco-Canadian',
            ]),
            'fr-fr' => new SiteLanguage(1, 'fr_FR.UTF-8', new Uri('/fr-fr/'), [
                'fallbackType' => 'fallback',
                'fallbacks' => [0],
                'French',
            ]),
            'default' => new SiteLanguage(0, 'en_US.UTF-8', new Uri('/en-us/'), []),
        ];

        $this->subject = new PersistedAliasMapper(self::ASPECT_CONFIGURATION);
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
        unset($this->subject, $this->languages);
        parent::tearDown();
    }

    public function languageAwareRecordsAreResolvedDataProvider(): array
    {
        return [
            'non-existing, default language' => ['this-value-does-not-exist', 'default', null],

            '30xx-slug, default language' => ['30xx-slug', 'default', '3010'],
            '30xx-slug, fr-fr language' => ['30xx-slug', 'fr-fr', '3010'],
            '30xx-slug, fr-ca language' => ['30xx-slug', 'fr-ca', '3010'],

            '30xx-slug-fr-ca, fr-ca language' => ['30xx-slug-fr-ca', 'fr-ca', '3010'],
            // '30xx-slug-fr-ca' available in default language as well, fallbacks to that one
            '30xx-slug-fr-ca, fr-fr language' => ['30xx-slug-fr-ca', 'fr-fr', '3010'],
            // '30xx-slug-fr-ca' available in default language, use it directly
            '30xx-slug-fr-ca, default language' => ['30xx-slug-fr-ca', 'default', '3010'],

            '30xx-slug-fr, fr-ca language' => ['30xx-slug-fr', 'fr-ca', '3010'],
            '30xx-slug-fr, fr-fr language' => ['30xx-slug-fr', 'fr-fr', '3010'],
            // '30xx-slug-fr-ca' available in default language, use it directly
            '30xx-slug-fr, default language' => ['30xx-slug-fr', 'default', '3010'],

            // basically the same, but being stored in reverse order in database
            '40xx-slug, default language' => ['40xx-slug', 'default', '4040'],
            '40xx-slug, fr-fr language' => ['40xx-slug', 'fr-fr', '4040'],
            '40xx-slug, fr-ca language' => ['40xx-slug', 'fr-ca', '4040'],

            '40xx-slug-fr-ca, fr-ca language' => ['40xx-slug-fr-ca', 'fr-ca', '4030'],
            // '40xx-slug-fr-ca' available in default language as well, fallbacks to that one
            '40xx-slug-fr-ca, fr-fr language' => ['40xx-slug-fr-ca', 'fr-fr', '4030'],
            // '40xx-slug-fr-ca' available in default language, use it directly
            '40xx-slug-fr-ca, default language' => ['40xx-slug-fr-ca', 'default', '4030'],

            '40xx-slug-fr, fr-ca language' => ['40xx-slug-fr', 'fr-ca', '4020'],
            '40xx-slug-fr, fr-fr language' => ['40xx-slug-fr', 'fr-fr', '4020'],
            // '40xx-slug-fr-ca' available in default language, use it directly
            '40xx-slug-fr, default language' => ['40xx-slug-fr', 'default', '4020'],
        ];
    }

    /**
     * @param string $requestValue
     * @param string $language
     * @param string|null $expectation
     *
     * @test
     * @dataProvider languageAwareRecordsAreResolvedDataProvider
     * @group not-postgres
     */
    public function languageAwareRecordsAreResolved(string $requestValue, string $language, ?string $expectation): void
    {
        $this->subject->setSiteLanguage($this->languages[$language]);
        self::assertSame($expectation, $this->subject->resolve($requestValue));
    }
}
