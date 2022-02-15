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

namespace TYPO3\CMS\Frontend\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\ImportMap;
use TYPO3\CMS\Core\Page\ImportMapFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderManager;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TypoScriptFrontendControllerTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var bool Reset singletons created by subject
     */
    protected bool $resetSingletonInstances = true;

    /**
     * @var MockObject|AccessibleObjectInterface|TypoScriptFrontendController
     */
    protected MockObject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $importMapProphecy = $this->prophesize(ImportMap::class);
        $importMapProphecy->render(Argument::type('string'), Argument::type('string'))->willReturn('');
        $importMapFactoryProphecy = $this->prophesize(ImportMapFactory::class);
        $importMapFactoryProphecy->create()->willReturn($importMapProphecy->reveal());
        GeneralUtility::setSingletonInstance(ImportMapFactory::class, $importMapFactoryProphecy->reveal());
        $site = $this->createSiteWithDefaultLanguage([
            'locale' => 'fr',
            'typo3Language' => 'fr',
        ]);
        $this->subject = $this->getAccessibleMock(TypoScriptFrontendController::class, ['dummy'], [], '', false);
        $this->subject->_set('context', new Context());
        $this->subject->_set('site', $site);
        $this->subject->_set('language', $site->getLanguageById(0));
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '170928423746123078941623042360abceb12341234231';

        $pageRepository = $this->getMockBuilder(PageRepository::class)->getMock();
        $this->subject->sys_page = $pageRepository;

        $pageRenderer = $this->getMockBuilder(PageRenderer::class)->getMock();
        $this->subject->_set('pageRenderer', $pageRenderer);
    }

    /**
     * Tests concerning rendering content
     */

    /**
     * @test
     */
    public function headerAndFooterMarkersAreReplacedDuringIntProcessing(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $GLOBALS['TSFE'] = $this->setupTsfeMockForHeaderFooterReplacementCheck();
        $contentObjectRendererProphecy = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRendererProphecy->stdWrapValue(Argument::cetera())->willReturn('');
        $GLOBALS['TSFE']->cObj = $contentObjectRendererProphecy->reveal();
        $GLOBALS['TSFE']->INTincScript($this->prophesize(ServerRequestInterface::class)->reveal());
        self::assertStringContainsString('headerData', $GLOBALS['TSFE']->content);
        self::assertStringContainsString('footerData', $GLOBALS['TSFE']->content);
    }

    /**
     * This is the callback that mimics a USER_INT extension
     */
    public function processNonCacheableContentPartsAndSubstituteContentMarkers(): void
    {
        $GLOBALS['TSFE']->additionalHeaderData[] = 'headerData';
        $GLOBALS['TSFE']->additionalFooterData[] = 'footerData';
    }

    /**
     * Setup a \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController object only for testing the header and footer
     * replacement during USER_INT rendering
     *
     * @return MockObject|TypoScriptFrontendController
     */
    protected function setupTsfeMockForHeaderFooterReplacementCheck()
    {
        $tsfe = $this->getAccessibleMock(
            TypoScriptFrontendController::class,
            [
                'processNonCacheableContentPartsAndSubstituteContentMarkers',
                'INTincScript_loadJSCode',
                'setAbsRefPrefix',
            ],
            [],
            '',
            false
        );
        $tsfe->expects(self::exactly(2))->method('processNonCacheableContentPartsAndSubstituteContentMarkers')->willReturnCallback([$this, 'processNonCacheableContentPartsAndSubstituteContentMarkers']);

        /**
         * prepare an EventDispatcher for ::makeInstance(AssetRenderer)
         * @see \TYPO3\CMS\Core\Page\PageRenderer::renderJavaScriptAndCss
         */
        GeneralUtility::setSingletonInstance(
            EventDispatcherInterface::class,
            new EventDispatcher(
                new ListenerProvider($this->createMock(ContainerInterface::class))
            )
        );

        $tsfe->content = file_get_contents(__DIR__ . '/Fixtures/renderedPage.html');
        $config = [
            'INTincScript_ext' => [
                'divKey' => '679b52796e75d474ccbbed486b6837ab',
            ],
            'INTincScript' => [
                'INT_SCRIPT.679b52796e75d474ccbbed486b6837ab' => [],
            ],
        ];
        $tsfe->config = $config;
        $site = $this->createSiteWithDefaultLanguage([
            'locale' => 'fr',
            'typo3Language' => 'fr',
        ]);
        $tsfe->_set('context', new Context());
        $tsfe->_set('site', $site);
        $tsfe->_set('language', $site->getLanguageById(0));

        return $tsfe;
    }

    /**
     * Tests concerning sL
     */

    /**
     * @test
     */
    public function localizationReturnsUnchangedStringIfNotLocallangLabel(): void
    {
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());
        $languageService = new LanguageService(new Locales(), new LocalizationFactory(new LanguageStore($packageManagerProphecy->reveal()), $cacheManagerProphecy->reveal()), $cacheFrontendProphecy->reveal());
        $languageServiceFactoryProphecy = $this->prophesize(LanguageServiceFactory::class);
        $languageServiceFactoryProphecy->createFromSiteLanguage(Argument::type(SiteLanguage::class))->will(static function ($args) use ($languageService) {
            $languageService->init($args[0]->getTypo3Language());
            return $languageService;
        });
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactoryProphecy->reveal());
        $string = StringUtility::getUniqueId();
        $site = $this->createSiteWithDefaultLanguage([
            'locale' => 'fr',
            'typo3Language' => 'fr',
        ]);
        $this->subject->page = [];
        $this->subject->_set('language', $site->getLanguageById(0));
        $this->subject->_call('setOutputLanguage');
        self::assertEquals($string, $this->subject->sL($string));
    }

    /**
     * Tests concerning getSysDomainCache
     */

    /**
     * @return array
     */
    public function getSysDomainCacheDataProvider(): array
    {
        return [
            'typo3.org' => [
                'typo3.org',
            ],
            'foo.bar' => [
                'foo.bar',
            ],
            'example.com' => [
                'example.com',
            ],
        ];
    }

    /**
     * @return array
     */
    public function baseUrlWrapHandlesDifferentUrlsDataProvider(): array
    {
        return [
            'without base url' => [
                '',
                'fileadmin/user_uploads/image.jpg',
                'fileadmin/user_uploads/image.jpg',
            ],
            'with base url' => [
                'http://www.google.com/',
                'fileadmin/user_uploads/image.jpg',
                'http://www.google.com/fileadmin/user_uploads/image.jpg',
            ],
            'without base url but with url prepended with a forward slash' => [
                '',
                '/fileadmin/user_uploads/image.jpg',
                '/fileadmin/user_uploads/image.jpg',
            ],
            'with base url but with url prepended with a forward slash' => [
                'http://www.google.com/',
                '/fileadmin/user_uploads/image.jpg',
                '/fileadmin/user_uploads/image.jpg',
            ],
        ];
    }

    /**
     * @dataProvider baseUrlWrapHandlesDifferentUrlsDataProvider
     * @test
     * @param string $baseUrl
     * @param string $url
     * @param string $expected
     */
    public function baseUrlWrapHandlesDifferentUrls(string $baseUrl, string $url, string $expected): void
    {
        $this->subject->baseUrl = $baseUrl;
        self::assertSame($expected, $this->subject->baseUrlWrap($url));
    }

    /**
     * @test
     * @see https://forge.typo3.org/issues/88041
     */
    public function indexedSearchHookUsesPageTitleApi(): void
    {
        $pageTitle = 'This is a test page title coming from PageTitleProviderManager';

        $pageTitleProvider = $this->prophesize(PageTitleProviderManager::class);
        $pageTitleProvider->getTitle()->willReturn($pageTitle);
        $pageTitleProvider->getPageTitleCache()->willReturn([]);
        GeneralUtility::setSingletonInstance(PageTitleProviderManager::class, $pageTitleProvider->reveal());

        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('pages')->willReturn($cacheFrontendProphecy->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());

        $contentObjectRendererProphecy = $this->prophesize(ContentObjectRenderer::class);
        $contentObjectRendererProphecy->stdWrapValue(Argument::cetera())->willReturn('');
        $this->subject->cObj = $contentObjectRendererProphecy->reveal();

        $this->subject->generatePageTitle();
        self::assertSame($pageTitle, $this->subject->indexedDocTitle);
    }

    /**
     * @test
     */
    public function pageRendererLanguageIsSetToSiteLanguageTypo3LanguageInConstructor(): void
    {
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('pages')->willReturn($cacheFrontendProphecy->reveal());
        $cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $site = $this->createSiteWithDefaultLanguage([
            'locale' => 'fr',
            'typo3Language' => 'fr-test',
        ]);
        $languageService = new LanguageService(new Locales(), new LocalizationFactory(new LanguageStore($packageManagerProphecy->reveal()), $cacheManagerProphecy->reveal()), $cacheFrontendProphecy->reveal());
        $languageServiceFactoryProphecy = $this->prophesize(LanguageServiceFactory::class);
        $languageServiceFactoryProphecy->createFromSiteLanguage(Argument::type(SiteLanguage::class))->will(static function ($args) use ($languageService) {
            $languageService->init($args[0]->getTypo3Language());
            return $languageService;
        });
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactoryProphecy->reveal());
        $frontendUserProphecy = $this->prophesize(FrontendUserAuthentication::class);
        // Constructor calling initPageRenderer()
        new TypoScriptFrontendController(
            new Context(),
            $site,
            $site->getLanguageById(0),
            new PageArguments(13, '0', []),
            $frontendUserProphecy->reveal()
        );
        // since PageRenderer is a singleton, this can be queried via the makeInstance call
        self::assertEquals('fr-test', GeneralUtility::makeInstance(PageRenderer::class)->getLanguage());
    }

    /**
     * @test
     */
    public function languageServiceIsSetUpWithSiteLanguageTypo3LanguageInConstructor(): void
    {
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('pages')->willReturn($cacheFrontendProphecy->reveal());
        $cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $site = $this->createSiteWithDefaultLanguage([
            'locale' => 'fr',
            'typo3Language' => 'fr',
        ]);
        $languageService = new LanguageService(new Locales(), new LocalizationFactory(new LanguageStore($packageManagerProphecy->reveal()), $cacheManagerProphecy->reveal()), $cacheFrontendProphecy->reveal());
        $languageServiceFactoryProphecy = $this->prophesize(LanguageServiceFactory::class);
        $languageServiceFactoryProphecy->createFromSiteLanguage(Argument::type(SiteLanguage::class))->will(static function ($args) use ($languageService) {
            $languageService->init($args[0]->getTypo3Language());
            return $languageService;
        });
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactoryProphecy->reveal());
        $frontendUserProphecy = $this->prophesize(FrontendUserAuthentication::class);
        // Constructor calling setOutputLanguage()
        $subject = $this->getAccessibleMock(
            TypoScriptFrontendController::class,
            ['dummy'],
            [
                new Context(),
                $site,
                $site->getLanguageById(0),
                new PageArguments(13, '0', []),
                $frontendUserProphecy->reveal(),
            ]
        );
        $languageService = $subject->_get('languageService');
        // since PageRenderer is a singleton, this can be queried via the makeInstance call
        self::assertEquals('fr', $languageService->lang);
    }

    /**
     * @test
     */
    public function mountPointParameterContainsOnlyValidMPValues(): void
    {
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('pages')->willReturn($cacheFrontendProphecy->reveal());
        $cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $languageService = new LanguageService(new Locales(), new LocalizationFactory(new LanguageStore($packageManagerProphecy->reveal()), $cacheManagerProphecy->reveal()), $cacheFrontendProphecy->reveal());
        $languageServiceFactoryProphecy = $this->prophesize(LanguageServiceFactory::class);
        $languageServiceFactoryProphecy->createFromSiteLanguage(Argument::type(SiteLanguage::class))->will(static function ($args) use ($languageService) {
            $languageService->init($args[0]->getTypo3Language());
            return $languageService;
        });
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactoryProphecy->reveal());

        $site = $this->createSiteWithDefaultLanguage([
            'locale' => 'fr',
            'typo3Language' => 'fr-test',
        ]);

        // no MP Parameter given
        $subject = new TypoScriptFrontendController(
            new Context(),
            $site,
            $site->getLanguageById(0),
            new PageArguments(13, '0', [], [], []),
            $this->prophesize(FrontendUserAuthentication::class)->reveal()
        );
        self::assertEquals('', $subject->MP);

        // single MP parameter given
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactoryProphecy->reveal());
        $subject = new TypoScriptFrontendController(
            new Context(),
            $site,
            $site->getLanguageById(0),
            new PageArguments(13, '0', [], [], ['MP' => '592-182']),
            $this->prophesize(FrontendUserAuthentication::class)->reveal()
        );
        self::assertEquals('592-182', $subject->MP);

        // invalid characters included
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactoryProphecy->reveal());
        $subject = new TypoScriptFrontendController(
            new Context(),
            $site,
            $site->getLanguageById(0),
            new PageArguments(13, '0', [], [], ['MP' => '12-13,a34-45/']),
            $this->prophesize(FrontendUserAuthentication::class)->reveal()
        );
        self::assertEquals('12-13,34-45', $subject->MP);

        // single MP parameter given but MP feature is turned off
        $GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] = false;
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactoryProphecy->reveal());
        $subject = new TypoScriptFrontendController(
            new Context(),
            $site,
            $site->getLanguageById(0),
            new PageArguments(13, '0', [], [], ['MP' => '592-182']),
            $this->prophesize(FrontendUserAuthentication::class)->reveal()
        );
        self::assertEquals('', $subject->MP);
    }

    private function createSiteWithDefaultLanguage(array $languageConfiguration): Site
    {
        return new Site('test', 13, [
            'identifier' => 'test',
            'rootPageId' => 13,
            'base' => 'https://www.example.com/',
            'languages' => [
                array_merge(
                    $languageConfiguration,
                    [
                        'languageId' => 0,
                        'base' => '/',
                    ]
                ),
            ],
        ]);
    }
}
