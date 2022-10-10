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

namespace TYPO3\CMS\Frontend\Tests\Functional\Controller;

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TypoScriptFrontendControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var array
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        // Test headerAndFooterMarkersAreReplacedDuringIntProcessing() relies on persisted page cache:
                        // It calls FE rendering twice to verify USER_INT stuff is called for page-cache-exists-but-has-INT.
                        // testing-framework usually sets these to NullBackend which would defeat this case.
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
    }

    /**
     * @test
     */
    public function headerAndFooterMarkersAreReplacedDuringIntProcessing(): void
    {
        $this->setUpFrontendRootPage(
            1,
            ['typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/PageWithUserInt.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        // Call page first time to trigger page cache with result
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/en/?id=88')
        );
        $body = (string)$response->getBody();
        self::assertStringContainsString('userIntContent', $body);
        self::assertStringContainsString('headerDataFromUserInt', $body);
        self::assertStringContainsString('footerDataFromUserInt', $body);

        // Call page second time to see if it works with page cache and user_int is still executed.
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/en/?id=88')
        );
        $body = (string)$response->getBody();
        self::assertStringContainsString('userIntContent', $body);
        self::assertStringContainsString('headerDataFromUserInt', $body);
        self::assertStringContainsString('footerDataFromUserInt', $body);
    }

    /**
     * A USER_INT method for headerAndFooterMarkersAreReplacedDuringIntProcessing()
     */
    public function userIntCallback(): string
    {
        $GLOBALS['TSFE']->additionalHeaderData[] = 'headerDataFromUserInt';
        $GLOBALS['TSFE']->additionalFooterData[] = 'footerDataFromUserInt';
        return 'userIntContent';
    }

    /**
     * @test
     */
    public function localizationReturnsUnchangedStringIfNotLocallangLabel(): void
    {
        $this->setUpFrontendRootPage(
            1,
            ['typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/PageWithUserObjectUsingSlWithoutLLL.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        // Call page first time to trigger page cache with result
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/en/?id=88')
        );
        $body = (string)$response->getBody();
        self::assertStringContainsString('notprefixedWithLLL', $body);
    }

    /**
     * A USER method for localizationReturnsUnchangedStringIfNotLocallangLabel()
     */
    public function slWithoutLLLCallback(): string
    {
        return $GLOBALS['TSFE']->sL('notprefixedWithLLL');
    }

    /**
     * @test
     */
    public function localizationReturnsLocalizedStringWithLocallangLabel(): void
    {
        $this->setUpFrontendRootPage(
            1,
            ['typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/PageWithUserObjectUsingSlWithLLL.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        // Call page first time to trigger page cache with result
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/en/?id=88')
        );
        $body = (string)$response->getBody();
        self::assertStringContainsString('Pagetree Overview', $body);
    }

    /**
     * A USER method for localizationReturnsUnchangedStringIfNotLocallangLabel()
     */
    public function slWithLLLCallback(): string
    {
        return $GLOBALS['TSFE']->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:mod_tx_cms_webinfo_page');
    }

    public function mountPointParameterContainsOnlyValidMPValuesDataProvider(): array
    {
        return [
            'no MP Parameter given' => [
                '',
                'empty',
            ],
            'single MP parameter given' => [
                '592-182',
                'foo592-182bar',
            ],
            'invalid characters included' => [
                '12-13,a34-45/',
                'foo12-13,34-45bar',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider mountPointParameterContainsOnlyValidMPValuesDataProvider
     */
    public function mountPointParameterContainsOnlyValidMPValues(string $inputMp, string $expected): void
    {
        $this->setUpFrontendRootPage(
            1,
            ['typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/PageExposingTsfeMpParameter.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        // Call page first time to trigger page cache with result
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/en/?id=88&MP=' . $inputMp)
        );
        $body = (string)$response->getBody();
        self::assertStringContainsString($expected, $body);
    }

    /**
     * A USER method for mountPointParameterContainsOnlyValidMPValues()
     */
    public function pageExposingMpParameterCallback(): string
    {
        if ($GLOBALS['TSFE']->MP === '') {
            return 'empty';
        }
        return 'foo' . $GLOBALS['TSFE']->MP . 'bar';
    }
}
