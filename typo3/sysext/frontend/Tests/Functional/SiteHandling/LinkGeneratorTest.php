<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Fixtures\LinkGeneratorController;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\ArrayValueInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\TypoScriptInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * Test case for frontend requests having site handling configured
 */
class LinkGeneratorTest extends AbstractTestCase
{
    /**
     * @var string
     */
    private $siteTitle = 'A Company that Manufactures Everything Inc';

    /**
     * @var InternalRequestContext
     */
    private $internalRequestContext;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass()
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp()
    {
        parent::setUp();

        // these settings are forwarded to the frontend sub-request as well
        $this->internalRequestContext = (new InternalRequestContext())
            ->withGlobalSettings(['TYPO3_CONF_VARS' => static::TYPO3_CONF_VARS]);

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );
        $this->writeSiteConfiguration(
            'products-acme-com',
            $this->buildSiteConfiguration(1300, 'https://products.acme.com/')
        );
        $this->writeSiteConfiguration(
            'blog-acme-com',
            $this->buildSiteConfiguration(2000, 'https://blog.acme.com/')
        );
        $this->writeSiteConfiguration(
            'john-blog-acme-com',
            $this->buildSiteConfiguration(2110, 'https://blog.acme.com/john/')
        );
        $this->writeSiteConfiguration(
            'jane-blog-acme-com',
            $this->buildSiteConfiguration(2120, 'https://blog.acme.com/jane/')
        );
    }

    protected function setUpDatabase()
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $scenarioFile = __DIR__ . '/Fixtures/PlainScenario.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );

        $this->setUpFrontendRootPage(
            1000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript',
            ],
            [
                'title' => 'ACME Root',
                'sitetitle' => $this->siteTitle,
            ]
        );
        $this->setUpFrontendRootPage(
            2000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript',
            ],
            [
                'title' => 'ACME Blog',
                'sitetitle' => $this->siteTitle,
            ]
        );
    }

    protected function tearDown()
    {
        unset($this->internalRequestContext);
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function linkIsGeneratedDataProvider(): array
    {
        $instructions = [
            // acme.com -> acme.com (same site)
            [1100, 1000, '/?id=acme-root'],
            [1100, 1100, '/?id=acme-first'],
            [1100, 1200, '/?id=1200'],
            [1100, 1210, '/?id=1210'],
            [1100, 404, '/?id=404'],
            // acme.com -> products.acme.com (nested sub-site)
            [1100, 1300, '/?id=1300'],
            [1100, 1310, '/?id=1310'],
            // acme.com -> blog.acme.com (different site)
            // @todo https://blog.acme.com/ not prefixed
            [1100, 2000, '/?id=blog-root'],
            [1100, 2100, '/?id=2100'],
            [1100, 2110, '/john/?id=2110'],
            [1100, 2111, '/john/?id=2111'],
            // blog.acme.com -> acme.com (different site)
            // @todo https://acme.com/ not prefixed
            [2100, 1000, '/?id=acme-root'],
            [2100, 1100, '/?id=acme-first'],
            [2100, 1200, '/?id=1200'],
            [2100, 1210, '/?id=1210'],
            [2100, 404, '/?id=404'],
            // blog.acme.com -> products.acme.com (different sub-site)
            [2100, 1300, '/?id=1300'],
            [2100, 1310, '/?id=1310'],
        ];

        return $this->keysFromTemplate(
            $instructions,
            '%1$d->%2$d'
        );
    }

    /**
     * @param int $sourcePageId
     * @param int $targetPageId
     * @param string $expectation
     *
     * @test
     * @dataProvider linkIsGeneratedDataProvider
     */
    public function linkIsGenerated(int $sourcePageId, int $targetPageId, string $expectation)
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest())
                ->withPageId($sourcePageId)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => $targetPageId,
                    ])
                ]),
            $this->internalRequestContext
        );

        static::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @return array
     */
    public function linkIsGeneratedFromMountPointDataProvider(): array
    {
        $instructions = [
            // acme.com -> acme.com (same site)
            [[7100, 1700], 7110, 1000, '/?id=acme-root'],
            [[7100, 1700], 7110, 1100, '/?id=acme-first'],
            [[7100, 1700], 7110, 1200, '/?id=1200'],
            [[7100, 1700], 7110, 1210, '/?id=1210'],
            [[7100, 1700], 7110, 404, '/?id=404'],
            // acme.com -> products.acme.com (nested sub-site)
            [[7100, 1700], 7110, 1300, '/?id=1300'],
            [[7100, 1700], 7110, 1310, '/?id=1310'],
            // acme.com -> blog.acme.com (different site)
            // @todo https://blog.acme.com/ not prefixed
            [[7100, 1700], 7110, 2000, '/?id=blog-root'],
            [[7100, 1700], 7110, 2100, '/?id=2100'],
            [[7100, 1700], 7110, 2110, '/john/?id=2110'],
            [[7100, 1700], 7110, 2111, '/john/?id=2111'],
            // blog.acme.com -> acme.com (different site)
            // @todo https://acme.com/ not prefixed
            [[7100, 2700], 7110, 1000, '/?id=acme-root'],
            [[7100, 2700], 7110, 1100, '/?id=acme-first'],
            [[7100, 2700], 7110, 1200, '/?id=1200'],
            [[7100, 2700], 7110, 1210, '/?id=1210'],
            [[7100, 2700], 7110, 404, '/?id=404'],
            // blog.acme.com -> products.acme.com (different sub-site)
            [[7100, 2700], 7110, 1300, '/?id=1300'],
            [[7100, 2700], 7110, 1310, '/?id=1310'],
        ];

        return $this->keysFromTemplate(
            $instructions,
            '%2$d->%3$d (mount:%1$s)',
            function (array $items) {
                array_splice(
                    $items,
                    0,
                    1,
                    [implode('->', $items[0])]
                );
                return $items;
            }
        );
    }

    /**
     * @param array $pageMount
     * @param int $sourcePageId
     * @param int $targetPageId
     * @param string $expectation
     *
     * @test
     * @dataProvider linkIsGeneratedFromMountPointDataProvider
     */
    public function linkIsGeneratedFromMountPoint(array $pageMount, int $sourcePageId, int $targetPageId, string $expectation)
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest())
                ->withMountPoint(...$pageMount)
                ->withPageId($sourcePageId)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => $targetPageId,
                    ])
                ]),
            $this->internalRequestContext
        );

        static::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @return array
     */
    public function linkIsGeneratedForLanguageDataProvider(): array
    {
        // @todo L-parameter is not applied
        $instructions = [
            // acme.com -> acme.com (same site)
            [1100, 1100, 0, '/?id=acme-first'],
            [1100, 1100, 1, '/?id=acme-first'],
            [1100, 1100, 2, '/?id=acme-first'],
            // @todo Configuration bug on duplicating alias names and uniqueness
            [1100, 1101, 0, '/?id=acme-first0'],
            [1100, 1102, 0, '/?id=acme-first1'],
            // acme.com -> products.acme.com (nested sub-site)
            [1100, 1300, 0, '/?id=1300'],
            [1100, 1310, 0, '/?id=1310'],
            // acme.com -> archive (outside site)
            [1100, 3100, 0, 'index.php?id=3100&L=0'],
            [1100, 3100, 1, 'index.php?id=3100&L=1'],
            [1100, 3100, 2, 'index.php?id=3100&L=2'],
            [1100, 3101, 0, 'index.php?id=3101&L=0'],
            [1100, 3102, 0, 'index.php?id=3102&L=0'],
            // blog.acme.com -> acme.com (different site)
            // @todo https://acme.com/ not prefixed
            [2100, 1100, 0, '/?id=acme-first'],
            [2100, 1100, 1, '/?id=acme-first'],
            [2100, 1100, 2, '/?id=acme-first'],
            // @todo Configuration bug on duplicating alias names and uniqueness
            [2100, 1101, 0, '/?id=acme-first0'],
            [2100, 1102, 0, '/?id=acme-first1'],
            // blog.acme.com -> archive (outside site)
            [2100, 3100, 0, 'index.php?id=3100&L=0'],
            [2100, 3100, 1, 'index.php?id=3100&L=1'],
            [2100, 3100, 2, 'index.php?id=3100&L=2'],
            [2100, 3101, 0, 'index.php?id=3101&L=0'],
            [2100, 3102, 0, 'index.php?id=3102&L=0'],
            // blog.acme.com -> products.acme.com (different sub-site)
            [2100, 1300, 0, '/?id=1300'],
            [2100, 1310, 0, '/?id=1310'],
        ];

        return $this->keysFromTemplate(
            $instructions,
            '%1$d->%2$d (lang:%3$d)'
        );
    }

    /**
     * @param int $sourcePageId
     * @param int $targetPageId
     * @param int $targetLanguageId
     * @param string $expectation
     *
     * @test
     * @dataProvider linkIsGeneratedForLanguageDataProvider
     */
    public function linkIsGeneratedForLanguage(int $sourcePageId, int $targetPageId, int $targetLanguageId, string $expectation)
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest())
                ->withPageId($sourcePageId)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => $targetPageId,
                        'additionalParams' => '&L=' . $targetLanguageId,
                    ])
                ]),
            $this->internalRequestContext
        );

        static::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @return array
     */
    public function linkIsGeneratedWithQueryParametersDataProvider(): array
    {
        $instructions = [
            // acme.com -> acme.com (same site)
            [1100, 1000, '/?id=acme-root&testing%5Bvalue%5D=1&cHash=7d1f13fa91159dac7feb3c824936b39d'],
            [1100, 1100, '/?id=acme-first&testing%5Bvalue%5D=1&cHash=f42b850e435f0cedd366f5db749fc1af'],
            [1100, 1200, '/?id=1200&testing%5Bvalue%5D=1&cHash=784e11c50ea1a13fd7d969df4ec53ea3'],
            [1100, 1210, '/?id=1210&testing%5Bvalue%5D=1&cHash=ccb7067022b9835ebfd8f720722bc708'],
            [1100, 404, '/?id=404&testing%5Bvalue%5D=1&cHash=864e96f586a78a53452f3bf0f4d24591'],
            // acme.com -> products.acme.com (nested sub-site)
            [1100, 1300, '/?id=1300&testing%5Bvalue%5D=1&cHash=dbd6597d72ed5098cce3d03eac1eeefe'],
            [1100, 1310, '/?id=1310&testing%5Bvalue%5D=1&cHash=e64bfc7ab7dd6b70d161e4d556be9726'],
            // acme.com -> blog.acme.com (different site)
            // @todo https://blog.acme.com/ not prefixed
            [1100, 2000, '/?id=blog-root&testing%5Bvalue%5D=1&cHash=a14da633e46dba71640cb85226cd12c5'],
            [1100, 2100, '/?id=2100&testing%5Bvalue%5D=1&cHash=d23d74cb50383f8788a9930ec8ba679f'],
            [1100, 2110, '/john/?id=2110&testing%5Bvalue%5D=1&cHash=bf25eea89f44a9a79dabdca98f38a432'],
            [1100, 2111, '/john/?id=2111&testing%5Bvalue%5D=1&cHash=42dbaeb9172b6b1ca23b49941e194db2'],
            // blog.acme.com -> acme.com (different site)
            // @todo https://acme.com/ not prefixed
            [2100, 1000, '/?id=acme-root&testing%5Bvalue%5D=1&cHash=7d1f13fa91159dac7feb3c824936b39d'],
            [2100, 1100, '/?id=acme-first&testing%5Bvalue%5D=1&cHash=f42b850e435f0cedd366f5db749fc1af'],
            [2100, 1200, '/?id=1200&testing%5Bvalue%5D=1&cHash=784e11c50ea1a13fd7d969df4ec53ea3'],
            [2100, 1210, '/?id=1210&testing%5Bvalue%5D=1&cHash=ccb7067022b9835ebfd8f720722bc708'],
            [2100, 404, '/?id=404&testing%5Bvalue%5D=1&cHash=864e96f586a78a53452f3bf0f4d24591'],
            // blog.acme.com -> products.acme.com (different sub-site)
            [2100, 1300, '/?id=1300&testing%5Bvalue%5D=1&cHash=dbd6597d72ed5098cce3d03eac1eeefe'],
            [2100, 1310, '/?id=1310&testing%5Bvalue%5D=1&cHash=e64bfc7ab7dd6b70d161e4d556be9726'],
        ];

        return $this->keysFromTemplate(
            $instructions,
            '%1$d->%2$d'
        );
    }

    /**
     * @param int $sourcePageId
     * @param int $targetPageId
     * @param string $expectation
     *
     * @test
     * @dataProvider linkIsGeneratedWithQueryParametersDataProvider
     */
    public function linkIsGeneratedWithQueryParameters(int $sourcePageId, int $targetPageId, string $expectation)
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest())
                ->withPageId($sourcePageId)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => $targetPageId,
                        'additionalParams' => '&testing[value]=1',
                        'useCacheHash' => 1,
                    ])
                ]),
            $this->internalRequestContext
        );

        static::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @return array
     */
    public function linkIsGeneratedForRestrictedPageDataProvider(): array
    {
        $instructions = [
            [1100, 1510, 0, ''],
            // [1100, 1511, 0, ''], // @todo Fails, not expanded to sub-pages
            [1100, 1512, 0, ''],
            [1100, 1515, 0, ''],
            [1100, 1520, 0, ''],
            // [1100, 1521, 0, ''], // @todo Fails, not expanded to sub-pages
            //
            [1100, 1510, 1, '/?id=1510'],
            [1100, 1511, 1, '/?id=1511'],
            [1100, 1512, 1, '/?id=1512'],
            [1100, 1515, 1, ''],
            [1100, 1520, 1, ''],
            // [1100, 1521, 1, ''], // @todo Fails, not expanded to sub-pages
            //
            [1100, 1510, 2, '/?id=1510'],
            [1100, 1511, 2, '/?id=1511'],
            [1100, 1512, 2, ''],
            [1100, 1515, 2, '/?id=1515'],
            [1100, 1520, 2, '/?id=1520'],
            [1100, 1521, 2, '/?id=1521'],
            //
            [1100, 1510, 3, '/?id=1510'],
            [1100, 1511, 3, '/?id=1511'],
            [1100, 1512, 3, '/?id=1512'],
            [1100, 1515, 3, '/?id=1515'],
            [1100, 1520, 3, '/?id=1520'],
            [1100, 1521, 3, '/?id=1521'],
        ];

        return $this->keysFromTemplate(
            $instructions,
            '%1$d->%2$d (user:%3$d)'
        );
    }

    /**
     * @param int $sourcePageId
     * @param int $targetPageId
     * @param int $frontendUserId
     * @param string $expectation
     *
     * @test
     * @dataProvider linkIsGeneratedForRestrictedPageDataProvider
     */
    public function linkIsGeneratedForRestrictedPage(int $sourcePageId, int $targetPageId, int $frontendUserId, string $expectation)
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest())
                ->withPageId($sourcePageId)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => $targetPageId,
                    ])
                ]),
            $this->internalRequestContext
                ->withFrontendUserId($frontendUserId)
        );

        static::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @return array
     */
    public function linkIsGeneratedForRestrictedPageUsingLoginPageDataProvider(): array
    {
        $instructions = [
            // no frontend user given
            [1100, 1510, 1500, 0, '/?id=1500&pageId=1510'],
            // [1100, 1511, 1500, 0, '/?id=1500&pageId=1511'], // @todo Fails, not expanded to sub-pages
            [1100, 1512, 1500, 0, '/?id=1500&pageId=1512'],
            [1100, 1515, 1500, 0, '/?id=1500&pageId=1515'],
            [1100, 1520, 1500, 0, '/?id=1500&pageId=1520'],
            // [1100, 1521, 1500, 0, '/?id=1500&pageId=1521'], // @todo Fails, not expanded to sub-pages
            // frontend user 1
            [1100, 1510, 1500, 1, '/?id=1510'],
            [1100, 1511, 1500, 1, '/?id=1511'],
            [1100, 1512, 1500, 1, '/?id=1512'],
            [1100, 1515, 1500, 1, '/?id=1500&pageId=1515'],
            [1100, 1520, 1500, 1, '/?id=1500&pageId=1520'],
            // [1100, 1521, 1500, 1, '/?id=1500&pageId=1521'], // @todo Fails, not expanded to sub-pages
            // frontend user 2
            [1100, 1510, 1500, 2, '/?id=1510'],
            [1100, 1511, 1500, 2, '/?id=1511'],
            [1100, 1512, 1500, 2, '/?id=1500&pageId=1512'],
            [1100, 1515, 1500, 2, '/?id=1515'],
            [1100, 1520, 1500, 2, '/?id=1520'],
            [1100, 1521, 1500, 2, '/?id=1521'],
            // frontend user 3
            [1100, 1510, 1500, 3, '/?id=1510'],
            [1100, 1511, 1500, 3, '/?id=1511'],
            [1100, 1512, 1500, 3, '/?id=1512'],
            [1100, 1515, 1500, 3, '/?id=1515'],
            [1100, 1520, 1500, 3, '/?id=1520'],
            [1100, 1521, 1500, 3, '/?id=1521'],
        ];

        return $this->keysFromTemplate(
            $instructions,
            '%1$d->%2$d (via: %3$d, user:%4$d)'
        );
    }

    /**
     * @param int $sourcePageId
     * @param int $targetPageId
     * @param int $loginPageId
     * @param int $frontendUserId
     * @param string $expectation
     *
     * @test
     * @dataProvider linkIsGeneratedForRestrictedPageUsingLoginPageDataProvider
     */
    public function linkIsGeneratedForRestrictedPageUsingLoginPage(int $sourcePageId, int $targetPageId, int $loginPageId, int $frontendUserId, string $expectation)
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest())
                ->withPageId($sourcePageId)
                ->withInstructions([
                    (new TypoScriptInstruction(TemplateService::class))
                        ->withTypoScript([
                            'config.' => [
                                'typolinkLinkAccessRestrictedPages' => $loginPageId,
                                'typolinkLinkAccessRestrictedPages_addParams' => '&pageId=###PAGE_ID###'
                            ],
                        ]),
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => $targetPageId,
                    ])
                ]),
            $this->internalRequestContext
                ->withFrontendUserId($frontendUserId)
        );

        static::assertSame($expectation, (string)$response->getBody());
    }

    public function linkIsGeneratedForPageVersionDataProvider(): array
    {
        // @todo Generation is not consistent "?id=" vs "index.php?id="
        // -> most probably since pid=-1 is not correctly resolved
        $instructions = [
            // acme.com -> acme.com (same site)
            [1100, 1100, false, '/?id=acme-first'],
            [1100, 1100, true, 'index.php?id=acme-first'],
            // [1100, 1950, false, '/?id=1950'], // @todo Not generated for new-placeholder
            [1100, 1950, true, 'index.php?id={targetPageId}'],
            // blog.acme.com -> acme.com (different site)
            // @todo https://acme.com/ not prefixed
            [2100, 1100, false, '/?id=acme-first'],
            [2100, 1100, true, 'index.php?id=acme-first'],
            // [2100, 1950, false, '/?id=1950'], // @todo Not generated for new-placeholder
            [2100, 1950, true, 'index.php?id={targetPageId}'],
        ];

        return $this->keysFromTemplate(
            $instructions,
            '%1$d->%2$d (resolve:%3$d)'
        );
    }

    /**
     * @param int $sourcePageId
     * @param int $targetPageId
     * @param bool $resolveVersion
     * @param string $expectation
     *
     * @test
     * @dataProvider linkIsGeneratedForPageVersionDataProvider
     */
    public function linkIsGeneratedForPageVersion(int $sourcePageId, int $targetPageId, bool $resolveVersion, string $expectation)
    {
        $workspaceId = 1;
        if ($resolveVersion) {
            $targetPageId = BackendUtility::getWorkspaceVersionOfRecord(
                $workspaceId,
                'pages',
                $targetPageId,
                'uid'
            )['uid'] ?? null;
        }

        $response = $this->executeFrontendRequest(
            (new InternalRequest())
                ->withPageId($sourcePageId)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => $targetPageId,
                    ])
                ]),
            $this->internalRequestContext
                ->withWorkspaceId($workspaceId)
        );

        $expectation = str_replace(
            ['{targetPageId}'],
            [$targetPageId],
            $expectation
        );

        static::assertSame($expectation, (string)$response->getBody());
    }

    public function menuIsGeneratedDataProvider(): array
    {
        return [
            'ACME Inc' => [
                1100,
                [
                    ['title' => 'EN: Welcome', 'link' => '/?id=acme-first'],
                    [
                        'title' => 'EN: Features',
                        'link' => '/?id=1200',
                        'children' => [
                            [
                                'title' => 'EN: Frontend Editing',
                                'link' => '/?id=1210',
                            ],
                        ],
                    ],
                    [
                        'title' => 'EN: Products',
                        'link' => '/?id=1300',
                        'children' => [
                            [
                                'title' => 'EN: Planets',
                                'link' => '/?id=1310',
                            ],
                            [
                                'title' => 'EN: Spaceships',
                                'link' => '/?id=1320',
                            ],
                            [
                                'title' => 'EN: Dark Matter',
                                'link' => '/?id=1330',
                            ],
                        ],
                    ],
                    ['title' => 'Internal', 'link' => '/?id=1500'],
                    ['title' => 'About us', 'link' => '/?id=1600'],
                    [
                        'title' => 'Announcements & News',
                        'link' => '/?id=1700',
                        'children' => [
                            [
                                'title' => 'Markets',
                                'link' => 'index.php?id=7110&MP=7100-1700',
                            ],
                            [
                                'title' => 'Products',
                                'link' => 'index.php?id=7120&MP=7100-1700',
                            ],
                            [
                                'title' => 'Partners',
                                'link' => 'index.php?id=7130&MP=7100-1700',
                            ],
                        ],
                    ],
                    ['title' => 'Page not found', 'link' => '/?id=404'],
                    // @todo Link should be prefixed with different site
                    ['title' => 'Our Blog', 'link' => '/?id=2100'],
                ]
            ],
            'ACME Blog' => [
                2100,
                [
                    [
                        'title' => 'Authors',
                        'link' => '/?id=2100',
                        'children' => [
                            [
                                'title' => 'John Doe',
                                'link' => '/john/?id=2110',
                            ],
                            [
                                'title' => 'Jane Doe',
                                'link' => '/jane/?id=2120',
                            ],
                        ],
                    ],
                    1 =>
                        [
                            'title' => 'Announcements & News',
                            'link' => '/?id=2700',
                            'children' => [
                                [
                                    'title' => 'Markets',
                                    'link' => 'index.php?id=7110&MP=7100-2700',
                                ],
                                [
                                    'title' => 'Products',
                                    'link' => 'index.php?id=7120&MP=7100-2700',
                                ],
                                [
                                    'title' => 'Partners',
                                    'link' => 'index.php?id=7130&MP=7100-2700',
                                ],
                            ],
                        ],
                    // @todo Link should be prefixed with different site
                    ['title' => 'ACME Inc', 'link' => '/?id=acme-first'],
                ]
            ]
        ];
    }

    /**
     * @param int $sourcePageId
     * @param array $expectation
     *
     * @test
     * @dataProvider menuIsGeneratedDataProvider
     */
    public function menuIsGenerated(int $sourcePageId, array $expectation)
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest())
                ->withPageId($sourcePageId)
                ->withInstructions([
                    $this->createMenuProcessorInstruction([
                        'levels' => 2,
                        'entryLevel' => 0,
                        'expandAll' => 1,
                        'includeSpacer' => 1,
                        'titleField' => 'title',
                        'as' => 'results',
                    ])
                ]),
            $this->internalRequestContext
        );

        $json = json_decode((string)$response->getBody(), true);
        $json = $this->filterMenu($json);

        static::assertSame($expectation, $json);
    }

    /**
     * @param array $typoScript
     * @return ArrayValueInstruction
     */
    private function createTypoLinkUrlInstruction(array $typoScript): ArrayValueInstruction
    {
        return (new ArrayValueInstruction(LinkGeneratorController::class))
            ->withArray([
                '10' => 'TEXT',
                '10.' => [
                    'typolink.' => array_merge(
                        $typoScript,
                        ['returnLast' => 'url']
                    )
                ]
            ]);
    }

    /**
     * @param array $typoScript
     * @return ArrayValueInstruction
     */
    private function createMenuProcessorInstruction(array $typoScript): ArrayValueInstruction
    {
        return (new ArrayValueInstruction(LinkGeneratorController::class))
            ->withArray([
                '10' => 'FLUIDTEMPLATE',
                '10.' => [
                    'file' => 'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/FluidJson.html',
                    'dataProcessing.' => [
                        '1' => 'TYPO3\\CMS\\Frontend\\DataProcessing\\MenuProcessor',
                        '1.' => $typoScript
                    ],
                ],
            ]);
    }

    /**
     * Filters and keeps only desired names.
     *
     * @param array $menu
     * @param array $keepNames
     * @return array
     */
    private function filterMenu(
        array $menu,
        array $keepNames = ['title', 'link']
    ): array {
        if (!in_array('children', $keepNames)) {
            $keepNames[] = 'children';
        }
        return array_map(
            function (array $menuItem) use ($keepNames) {
                $menuItem = array_filter(
                    $menuItem,
                    function (string $name) use ($keepNames) {
                        return in_array($name, $keepNames);
                    },
                    ARRAY_FILTER_USE_KEY
                );
                if (is_array($menuItem['children'] ?? null)) {
                    $menuItem['children'] = $this->filterMenu(
                        $menuItem['children'],
                        $keepNames
                    );
                }
                return $menuItem;
            },
            $menu
        );
    }
}
