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

namespace TYPO3\CMS\Core\Tests\Unit\Routing;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\ExpressionLanguage\DefaultProvider;
use TYPO3\CMS\Core\ExpressionLanguage\ProviderConfigurationLoader;
use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Routing\PageRouter;
use TYPO3\CMS\Core\Routing\RequestContextFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PageRouterLimitToPagesTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Creates a Resolver with the given variables, bypassing the ProviderConfigurationLoader.
     * This avoids the need to set up DI infrastructure in unit tests.
     */
    private function createResolver(array $variables): Resolver
    {
        GeneralUtility::addInstance(ProviderConfigurationLoader::class, new ProviderConfigurationLoader(
            ExtensionManagementUtilityAccessibleProxy::getPackageManager(),
            new NullFrontend('test'),
            'ExpressionLanguageProviders'
        ));
        GeneralUtility::addInstance(DefaultProvider::class, new DefaultProvider(new Typo3Version(), new Context(), new Features()));
        return GeneralUtility::makeInstance(Resolver::class, 'routing', $variables);
    }

    private function callMatchesPageLimitation(Site $site, array $limitToPages, int $pageId, array $page, SiteLanguage $language): bool
    {
        GeneralUtility::addInstance(RequestContextFactory::class, new RequestContextFactory(new BackendEntryPointResolver()));
        GeneralUtility::setSingletonInstance(EventDispatcherInterface::class, new EventDispatcher(self::createStub(ListenerProviderInterface::class)));
        $router = new class ($site) extends PageRouter {
            public function publicMatchesPageLimitation(array $limitToPages, int $pageId, array $page, SiteLanguage $language, ?Resolver &$resolver): bool
            {
                return $this->matchesPageLimitation($limitToPages, $pageId, $page, $language, $resolver);
            }
        };
        // Pre-create the resolver if expressions are present and page data is available
        $hasExpressions = false;
        foreach ($limitToPages as $limitation) {
            if (is_string($limitation) && $limitation !== '') {
                $hasExpressions = true;
                break;
            }
        }
        $resolver = null;
        if ($hasExpressions && $page !== []) {
            $resolver = $this->createResolver([
                'page' => $page,
                'site' => $site,
                'siteLanguage' => $language,
            ]);
        }
        return $router->publicMatchesPageLimitation($limitToPages, $pageId, $page, $language, $resolver);
    }

    #[Test]
    public function matchesPageLimitationWithIntegerPageIds(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);

        self::assertTrue($this->callMatchesPageLimitation($site, [13, 42], 13, [], $language));
        self::assertTrue($this->callMatchesPageLimitation($site, [13, 42], 42, [], $language));
        self::assertFalse($this->callMatchesPageLimitation($site, [13, 42], 99, [], $language));
    }

    #[Test]
    public function matchesPageLimitationWithExpressionOnDoktype(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 13, 'doktype' => 1, 'backend_layout' => 'default', 'module' => ''];

        self::assertTrue($this->callMatchesPageLimitation($site, ['page["doktype"] == 1'], 13, $page, $language));
    }

    #[Test]
    public function matchesPageLimitationWithExpressionOnDoktypeNotMatching(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 13, 'doktype' => 1, 'backend_layout' => 'default', 'module' => ''];

        self::assertFalse($this->callMatchesPageLimitation($site, ['page["doktype"] == 4'], 13, $page, $language));
    }

    #[Test]
    public function matchesPageLimitationWithExpressionOnBackendLayout(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 13, 'doktype' => 1, 'backend_layout' => 'news_layout', 'module' => ''];

        self::assertTrue($this->callMatchesPageLimitation($site, ['page["backend_layout"] == "news_layout"'], 13, $page, $language));
    }

    #[Test]
    public function matchesPageLimitationWithExpressionOnBackendLayoutNotMatching(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 13, 'doktype' => 1, 'backend_layout' => 'news_layout', 'module' => ''];

        self::assertFalse($this->callMatchesPageLimitation($site, ['page["backend_layout"] == "default"'], 13, $page, $language));
    }

    #[Test]
    public function matchesPageLimitationWithExpressionOnModule(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 13, 'doktype' => 254, 'backend_layout' => '', 'module' => 'shop'];

        self::assertTrue($this->callMatchesPageLimitation($site, ['page["module"] == "shop"'], 13, $page, $language));
    }

    #[Test]
    public function matchesPageLimitationWithExpressionOnModuleNotMatching(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 13, 'doktype' => 254, 'backend_layout' => '', 'module' => 'shop'];

        self::assertFalse($this->callMatchesPageLimitation($site, ['page["module"] == "blog"'], 13, $page, $language));
    }

    #[Test]
    public function matchesPageLimitationWithMixedIntegersAndExpressionsMatchesViaInteger(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 42, 'doktype' => 1, 'backend_layout' => 'news_layout', 'module' => ''];

        self::assertTrue($this->callMatchesPageLimitation($site, [42, 'page["doktype"] == 1'], 42, $page, $language));
    }

    #[Test]
    public function matchesPageLimitationWithMixedIntegersAndExpressionsMatchesViaExpression(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 99, 'doktype' => 1, 'backend_layout' => 'news_layout', 'module' => ''];

        self::assertTrue($this->callMatchesPageLimitation($site, [42, 'page["doktype"] == 1'], 99, $page, $language));
    }

    #[Test]
    public function matchesPageLimitationWithMixedIntegersAndExpressionsNoMatch(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 99, 'doktype' => 1, 'backend_layout' => 'news_layout', 'module' => ''];

        self::assertFalse($this->callMatchesPageLimitation($site, [42, 'page["doktype"] == 4'], 99, $page, $language));
    }

    #[Test]
    public function matchesPageLimitationWithCompoundExpressionMatches(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 13, 'doktype' => 1, 'backend_layout' => 'news_layout', 'module' => ''];

        self::assertTrue($this->callMatchesPageLimitation(
            $site,
            ['page["doktype"] == 1 && page["backend_layout"] == "news_layout"'],
            13,
            $page,
            $language
        ));
    }

    #[Test]
    public function matchesPageLimitationWithCompoundExpressionDoesNotMatch(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 13, 'doktype' => 1, 'backend_layout' => 'news_layout', 'module' => ''];

        self::assertFalse($this->callMatchesPageLimitation(
            $site,
            ['page["doktype"] == 1 && page["backend_layout"] == "default"'],
            13,
            $page,
            $language
        ));
    }

    #[Test]
    public function matchesPageLimitationWithEmptyPageRecordSkipsExpressionsButMatchesInteger(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);

        self::assertTrue($this->callMatchesPageLimitation($site, [13, 'page["doktype"] == 1'], 13, [], $language));
    }

    #[Test]
    public function matchesPageLimitationWithEmptyPageRecordSkipsExpressions(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);

        self::assertFalse($this->callMatchesPageLimitation($site, ['page["doktype"] == 1'], 99, [], $language));
    }

    #[Test]
    public function matchesPageLimitationWithInvalidExpressionDoesNotThrow(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);
        $page = ['uid' => 13, 'doktype' => 1];

        self::assertFalse($this->callMatchesPageLimitation($site, ['invalid_expression!!!'], 13, $page, $language));
    }

    #[Test]
    public function matchesPageLimitationWithEmptyArrayReturnsFalse(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);

        self::assertFalse($this->callMatchesPageLimitation($site, [], 13, [], $language));
    }

    #[Test]
    public function matchesPageLimitationWithEmptyStringIsSkipped(): void
    {
        $site = new Site('test', 1, []);
        $language = self::createStub(SiteLanguage::class);

        self::assertFalse($this->callMatchesPageLimitation($site, [''], 13, ['uid' => 13], $language));
    }
}
