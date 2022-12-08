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

    /**
     * @test
     */
    public function headerAndFooterMarkersAreReplacedDuringIntProcessing(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/PageWithUserInt.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        // Call page first time to trigger page cache with result
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/en/'))->withPageId(88));
        $body = (string)$response->getBody();
        self::assertStringContainsString('userIntContent', $body);
        self::assertStringContainsString('headerDataFromUserInt', $body);
        self::assertStringContainsString('footerDataFromUserInt', $body);

        // Call page second time to see if it works with page cache and user_int is still executed.
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/en/'))->withPageId(88));
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
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/PageWithUserObjectUsingSlWithoutLLL.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/en/'))->withPageId(88));
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
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/PageWithUserObjectUsingSlWithLLL.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/en/'))->withPageId(88));
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
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/PageExposingTsfeMpParameter.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('https://website.local/en/'))
                ->withPageId(88)
                ->withQueryParameter('MP', $inputMp)
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

    public function getFromCacheSetsConfigRootlineToLocalRootlineDataProvider(): array
    {
        $page1 = [
            'pid' => 0,
            'uid' => 1,
            't3ver_oid' => 0,
            't3ver_wsid' => 0,
            't3ver_state' => 0,
            'title' => 'Pre page without template',
            'nav_title' => '',
            'media' => '',
            'layout' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'fe_group' => '0',
            'extendToSubpages' => 0,
            'doktype' => 1,
            'TSconfig' => null,
            'tsconfig_includes' => null,
            'is_siteroot' => 0,
            'mount_pid' => 0,
            'mount_pid_ol' => 0,
            'backend_layout_next_level' => '',
        ];
        $page2 = [
            'pid' => 1,
            'uid' => 2,
            't3ver_oid' => 0,
            't3ver_wsid' => 0,
            't3ver_state' => 0,
            'title' => 'Root page having template with root flag set by tests',
            'nav_title' => '',
            'media' => '',
            'layout' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'fe_group' => '0',
            'extendToSubpages' => 0,
            'doktype' => 1,
            'TSconfig' => null,
            'tsconfig_includes' => null,
            'is_siteroot' => 1,
            'mount_pid' => 0,
            'mount_pid_ol' => 0,
            'backend_layout_next_level' => '',
        ];
        $page88 = [
            'pid' => 2,
            'uid' => 88,
            't3ver_oid' => 0,
            't3ver_wsid' => 0,
            't3ver_state' => 0,
            'title' => 'Sub page 1',
            'nav_title' => '',
            'media' => '',
            'layout' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'fe_group' => '0',
            'extendToSubpages' => 0,
            'doktype' => 1,
            'TSconfig' => null,
            'tsconfig_includes' => null,
            'is_siteroot' => 0,
            'mount_pid' => 0,
            'mount_pid_ol' => 0,
            'backend_layout_next_level' => '',
        ];
        $page89 = [
            'pid' => 88,
            'uid' => 89,
            't3ver_oid' => 0,
            't3ver_wsid' => 0,
            't3ver_state' => 0,
            'title' => 'Sub sub page 1',
            'nav_title' => '',
            'media' => '',
            'layout' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'fe_group' => '0',
            'extendToSubpages' => 0,
            'doktype' => 1,
            'TSconfig' => null,
            'tsconfig_includes' => null,
            'is_siteroot' => 0,
            'mount_pid' => 0,
            'mount_pid_ol' => 0,
            'backend_layout_next_level' => '',
        ];
        $page98 = [
            'pid' => 2,
            'uid' => 98,
            't3ver_oid' => 0,
            't3ver_wsid' => 0,
            't3ver_state' => 0,
            'title' => 'Sub page 2 having template with root flag',
            'nav_title' => '',
            'media' => '',
            'layout' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'fe_group' => '0',
            'extendToSubpages' => 0,
            'doktype' => 1,
            'TSconfig' => null,
            'tsconfig_includes' => null,
            'is_siteroot' => 0,
            'mount_pid' => 0,
            'mount_pid_ol' => 0,
            'backend_layout_next_level' => '',
        ];
        $page99 = [
            'pid' => 98,
            'uid' => 99,
            't3ver_oid' => 0,
            't3ver_wsid' => 0,
            't3ver_state' => 0,
            'title' => 'Sub sub page 2',
            'nav_title' => '',
            'media' => '',
            'layout' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'fe_group' => '0',
            'extendToSubpages' => 0,
            'doktype' => 1,
            'TSconfig' => null,
            'tsconfig_includes' => null,
            'is_siteroot' => 0,
            'mount_pid' => 0,
            'mount_pid_ol' => 0,
            'backend_layout_next_level' => '',
        ];
        return [
            'page with one root template on pid 2' => [
                89,
                [ 3 => $page89, 2 => $page88, 1 => $page2, 0 => $page1 ],
                [ 0 => $page2, 1 => $page88, 2 => $page89 ],
                false,
            ],
            'page with one root template on pid 2 no cache' => [
                89,
                [ 3 => $page89, 2 => $page88, 1 => $page2, 0 => $page1 ],
                [ 0 => $page2, 1 => $page88, 2 => $page89 ],
                true,
            ],
            'page with one root template on pid 2 and one on pid 98' => [
                99,
                [ 3 => $page99, 2 => $page98, 1 => $page2, 0 => $page1 ],
                [ 0 => $page98, 1 => $page99 ],
                false,
            ],
            'page with one root template on pid 2 and one on pid 98 no cache' => [
                99,
                [ 3 => $page99, 2 => $page98, 1 => $page2, 0 => $page1 ],
                [ 0 => $page98, 1 => $page99 ],
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getFromCacheSetsConfigRootlineToLocalRootlineDataProvider
     */
    public function getFromCacheSetsConfigRootlineToLocalRootline(int $pid, array $expectedRootLine, array $expectedConfigRootLine, bool $nocache): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/PageExposingTsfeMpParameter.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        $request = (new InternalRequest('https://website.local/en/'))->withPageId($pid);
        if ($nocache) {
            $request = $request->withAttribute('noCache', true);
        }
        $this->executeFrontendSubRequest($request);
        self::assertSame($expectedRootLine, $GLOBALS['TSFE']->rootLine);
        self::assertSame($expectedConfigRootLine, $GLOBALS['TSFE']->config['rootLine']);
        // @deprecated: b/w compat. Drop when TemplateService is removed.
        self::assertSame($expectedConfigRootLine, $GLOBALS['TSFE']->tmpl->rootLine);
    }

    /**
     * @test
     */
    public function applicationConsidersTrueConditionVerdict(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/PageHttpsConditionHelloWorld.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );
        $request = (new InternalRequest('https://website.local/en/'))->withPageId(2);
        $response = $this->executeFrontendSubRequest($request);
        self::assertStringContainsString('https-condition-on', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function applicationConsidersFalseConditionVerdictToElseBranch(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['typo3/sysext/frontend/Tests/Functional/Controller/Fixtures/PageHttpsConditionHelloWorld.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'http://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );
        $request = (new InternalRequest('http://website.local/en/'))->withPageId(2);
        $response = $this->executeFrontendSubRequest($request);
        self::assertStringContainsString('https-condition-off', (string)$response->getBody());
    }
}
