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

namespace TYPO3\CMS\Core\Tests\Functional\Security\ContentSecurityPolicy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\DispositionConfiguration;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PolicyProviderTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'Français', 'locale' => 'fr_FR.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Environment::initialize(
            Environment::getContext(),
            true,
            true,
            Environment::getProjectPath(),
            Environment::getPublicPath() . '/typo3temp/public',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );

        $this->writeSiteConfiguration(
            'relative',
            $this->buildSiteConfiguration(1000, '/relative/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('FR', '/fr/'),
            ]
        );
        $this->writeSiteConfiguration(
            'absolute-same-site',
            $this->buildSiteConfiguration(1000, 'https://website.local/same/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('FR', '/fr/'),
            ]
        );
        $this->writeSiteConfiguration(
            'absolute-cross-site',
            $this->buildSiteConfiguration(1000, 'https://website.local/cross/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://en.website.local/'),
                $this->buildLanguageConfiguration('FR', 'https://fr.website.local/'),
            ]
        );
    }

    public static function defaultReportingUriBaseIsResolvedDataProvider(): \Generator
    {
        $frontendRelative = Scope::frontendSiteIdentifier('relative');
        $frontendAbsoluteSameSite = Scope::frontendSiteIdentifier('absolute-same-site');
        $frontendAbsoluteCrossSite = Scope::frontendSiteIdentifier('absolute-cross-site');

        // parts: scope (BE, FE, FE+site) | language preset | absolute | expected URI
        yield [$frontendRelative, null, false, '/relative/en/@http-reporting?csp=report'];
        yield [$frontendRelative, 'EN', false, '/relative/en/@http-reporting?csp=report'];
        yield [$frontendRelative, 'FR', false, '/relative/fr/@http-reporting?csp=report'];
        yield [$frontendRelative, null, true, 'https://website.fallback/relative/en/@http-reporting?csp=report'];
        yield [$frontendRelative, 'EN', true, 'https://website.fallback/relative/en/@http-reporting?csp=report'];
        yield [$frontendRelative, 'FR', true, 'https://website.fallback/relative/fr/@http-reporting?csp=report'];

        yield [$frontendAbsoluteSameSite, null, false, '/same/en/@http-reporting?csp=report'];
        yield [$frontendAbsoluteSameSite, 'EN', false, '/same/en/@http-reporting?csp=report'];
        yield [$frontendAbsoluteSameSite, 'FR', false, '/same/fr/@http-reporting?csp=report'];
        yield [$frontendAbsoluteSameSite, null, true, 'https://website.local/same/en/@http-reporting?csp=report'];
        yield [$frontendAbsoluteSameSite, 'EN', true, 'https://website.local/same/en/@http-reporting?csp=report'];
        yield [$frontendAbsoluteSameSite, 'FR', true, 'https://website.local/same/fr/@http-reporting?csp=report'];

        yield [$frontendAbsoluteCrossSite, null, false, '/@http-reporting?csp=report'];
        yield [$frontendAbsoluteCrossSite, 'EN', false, '/@http-reporting?csp=report'];
        yield [$frontendAbsoluteCrossSite, 'FR', false, '/@http-reporting?csp=report'];
        yield [$frontendAbsoluteCrossSite, null, true, 'https://en.website.local/@http-reporting?csp=report'];
        yield [$frontendAbsoluteCrossSite, 'EN', true, 'https://en.website.local/@http-reporting?csp=report'];
        yield [$frontendAbsoluteCrossSite, 'FR', true, 'https://fr.website.local/@http-reporting?csp=report'];

        yield [Scope::frontend(), null, false, '/@http-reporting?csp=report'];
        yield [Scope::frontend(), null, true, 'https://website.fallback/@http-reporting?csp=report'];

        yield [Scope::backend(), null, false, '/typo3/@http-reporting?csp=report'];
        yield [Scope::backend(), null, true, 'https://website.fallback/typo3/@http-reporting?csp=report'];
    }

    #[DataProvider('defaultReportingUriBaseIsResolvedDataProvider')]
    #[Test]
    public function defaultReportingUriBaseIsResolved(Scope $scope, ?string $languagePreset, bool $absolute, string $expectation): void
    {
        $request = $this->buildServerRequest($scope, $languagePreset);
        $subject = $this->get(PolicyProvider::class);
        $actual = (string)$subject->getDefaultReportingUriBase($scope, $request, $absolute);
        self::assertSame($expectation, $actual);
    }

    public static function effectiveReportingUrlIsResolvedDataProvider(): \Generator
    {
        $disabledReportingEndpoint = new DispositionConfiguration(
            inheritDefault: true,
            includeResolutions: true,
            reportingUrl: false,
        );
        $externalReportingEndpoint = new DispositionConfiguration(
            inheritDefault: true,
            includeResolutions: true,
            reportingUrl: 'https://example.org/csp-report',
        );

        // parts: scope (BE, FE, FE+site) | language preset | disposition-configuration | expected URI pattern
        yield [null, '#^https://website\.fallback/relative/en/@http-reporting\?csp=report&requestTime=\d+&requestHash=[[:xdigit:]]+$#'];
        yield [$disabledReportingEndpoint, null];
        yield [$externalReportingEndpoint, '#^https://example\.org/csp-report$#'];
    }

    #[DataProvider('effectiveReportingUrlIsResolvedDataProvider')]
    #[Test]
    public function effectiveReportingUrlIsResolved(?DispositionConfiguration $dispositionConfiguration, ?string $expectation): void
    {
        $scope = Scope::frontendSiteIdentifier('relative');
        $request = $this->buildServerRequest($scope, null);
        $subject = $this->get(PolicyProvider::class);
        $actual = $subject->getReportingUrlFor($scope, $request, $dispositionConfiguration);

        if ($expectation === null) {
            self::assertNull($actual);
        } else {
            self::assertMatchesRegularExpression($expectation, (string)$actual);
        }
    }

    private function buildServerRequest(Scope $scope, ?string $languagePreset): ServerRequestInterface
    {
        $request = new ServerRequest(
            '/',
            'GET',
            null,
            [],
            [
                'HTTPS' => 'on',
                'HTTP_HOST' =>  'website.fallback',
            ]
        );
        if ($scope->siteIdentifier !== null) {
            $site = $this->get(SiteFinder::class)->getSiteByIdentifier($scope->siteIdentifier);
            $request = $request
                ->withAttribute('site', $site)
                ->withAttribute(
                    'siteLanguage',
                    $languagePreset !== null
                        ? $site->getLanguageById($this->resolveLanguagePreset($languagePreset)['id'])
                        : $site->getDefaultLanguage()
                );
        }
        return $request;
    }
}
