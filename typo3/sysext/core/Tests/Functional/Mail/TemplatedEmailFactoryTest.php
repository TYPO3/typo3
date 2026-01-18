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

namespace TYPO3\CMS\Core\Tests\Functional\Mail;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Mail\TemplatedEmailFactory;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TemplatedEmailFactoryTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_fluid_email',
    ];

    public static function siteSettingsTemplateRootPaths(): iterable
    {
        // Site Settings save this as "list" (numerical array keys)
        // Leads to TemplatedEmailFactory->buildTemplatePathsWithSiteOverrides() in "append" mode
        yield [
            'rootPaths' => [
                0 => 'EXT:test_fluid_email/Resources/Private/Templates/EmailOverride0/',
                1 => 'EXT:test_fluid_email/Resources/Private/Templates/EmailOverride1/',
                2 => 'EXT:test_fluid_email/Resources/Private/Templates/EmailOverride2/',
            ],
            'expected' => [
                0 => 'typo3/sysext/core/Resources/Private/Templates/Email/',
                10 => 'typo3/sysext/backend/Resources/Private/Templates/Email/',
                101 => 'typo3conf/ext/test_fluid_email/Resources/Private/Templates/',
                102 => 'typo3conf/ext/test_fluid_email/Resources/Private/Templates/EmailOverride0/',
                103 => 'typo3conf/ext/test_fluid_email/Resources/Private/Templates/EmailOverride1/',
                104 => 'typo3conf/ext/test_fluid_email/Resources/Private/Templates/EmailOverride2/',
                300 => 'typo3/sysext/core/Resources/Private/Templates/Email/',
            ],
        ];

        // But we may also receive array keys as "priorities"
        // Leads to TemplatedEmailFactory->buildTemplatePathsWithSiteOverrides() in "replace" mode
        yield [
            'rootPaths' => [
                10 => 'EXT:test_fluid_email/Resources/Private/Templates/EmailOverride0/',
                20 => 'EXT:test_fluid_email/Resources/Private/Templates/EmailOverride1/',
            ],
            'expected' => [
                0 => 'typo3/sysext/core/Resources/Private/Templates/Email/',
                10 => 'typo3conf/ext/test_fluid_email/Resources/Private/Templates/EmailOverride0/',
                20 => 'typo3conf/ext/test_fluid_email/Resources/Private/Templates/EmailOverride1/',
                101 => 'typo3conf/ext/test_fluid_email/Resources/Private/Templates/',
                300 => 'typo3/sysext/core/Resources/Private/Templates/Email/',
            ],
        ];

        // Like above, but with an existing "300" key
        yield [
            'rootPaths' => [
                10 => 'EXT:test_fluid_email/Resources/Private/Templates/EmailOverride0/',
                300 => 'EXT:test_fluid_email/Resources/Private/Templates/EmailOverride1/',
            ],
            'expected' => [
                0 => 'typo3/sysext/core/Resources/Private/Templates/Email/',
                10 => 'typo3conf/ext/test_fluid_email/Resources/Private/Templates/EmailOverride0/',
                101 => 'typo3conf/ext/test_fluid_email/Resources/Private/Templates/',
                // The "300" from the override argument wins over site settings!
                300 => 'typo3/sysext/core/Resources/Private/Templates/Email/',
            ],
        ];
    }

    #[DataProvider('siteSettingsTemplateRootPaths')]
    #[Test]
    public function createWithOverridesMergesSiteSettingsWithPriority(array $rootPaths, array $expected): void
    {
        $settingsTree = [
            'email' => [
                'templateRootPaths' => $rootPaths,
                'format' => 'plain',
            ],
        ];

        $siteSettings = new SiteSettings(
            settings: new Settings(ArrayUtility::flattenPlain($settingsTree)),
            settingsTree: $settingsTree,
            flattenedArrayValues: [
                // This feels awkard, but the reason is the "typelist" GUI flattens
                // not the whole array, but only up to the array key, and the rest is an unflattened
                // array. This makes sense in terms of SiteSettings, so who are we but a small test to
                // question this.
                'email.templateRootPaths' => $rootPaths,
                'format' => 'plain',
            ],
        );

        $site = new Site('test-site', 1, [], $siteSettings);
        $request = $this->createRequestWithSite($site);
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->createWithOverrides(
            templateRootPaths: [300 => 'EXT:core/Resources/Private/Templates/Email/'],
            request: $request,
        );
        $actual = $fluidEmail->getView()->getRenderingContext()->getTemplatePaths()->getTemplateRootPaths();

        // The actual paths contain our instance's root directory, so we need to add it to our expectation too
        // Must be done here because the DataProvider is called statically and does not have this yet.
        $parsedExpectation = [];
        foreach ($expected as $key => $value) {
            $parsedExpectation[$key] = Environment::getProjectPath() . '/' . $value;
        }
        self::assertSame($actual, $parsedExpectation);
    }

    #[Test]
    public function createWithOverridesMergesSiteSettingsWhenSuppliedAsNumericalArray(): void
    {
        $siteSettings = SiteSettings::createFromSettingsTree([
            'email' => [
                'templateRootPaths' => [
                    200 => 'EXT:core/Resources/Private/Templates/Email/',
                ],
                'format' => 'plain',
            ],
        ]);
        $site = new Site('test-site', 1, [], $siteSettings);
        $request = $this->createRequestWithSite($site);
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->createWithOverrides(
            templateRootPaths: [300 => 'EXT:core/Resources/Private/Templates/Email/'],
            request: $request,
        );
        $fluidEmail->setTemplate('WithSubject')
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->assign('content', 'Test content');

        // Site format setting should be applied
        $fluidEmail->ensureValidity();
        self::assertNotEmpty($fluidEmail->getTextBody());
        self::assertEmpty($fluidEmail->getHtmlBody());
    }

    #[Test]
    public function createFromRequestUsesGlobalConfigWhenNoSiteIsPresent(): void
    {
        $request = $this->createRequestWithoutSite();
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->createFromRequest($request);
        $fluidEmail->setTemplate('WithSubject')
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->assign('content', 'Test content');

        // Verify email can be rendered with global template paths
        $body = $fluidEmail->getHtmlBody();
        self::assertNotEmpty($body);
        self::assertStringContainsString('Test content', $body);
    }

    #[Test]
    public function createFromRequestMergesSiteSettingsWithGlobalConfig(): void
    {
        $siteSettings = SiteSettings::createFromSettingsTree([
            'email' => [
                'templateRootPaths' => [
                    // Add a higher priority path that won't be used (templates don't exist there)
                    // but proves the merge happens
                    200 => 'EXT:core/Resources/Private/Templates/Email/',
                ],
            ],
        ]);
        $site = new Site('test-site', 1, [], $siteSettings);
        $request = $this->createRequestWithSite($site);
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->createFromRequest($request);
        $fluidEmail->setTemplate('WithSubject')
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->assign('content', 'Test content');

        // Email should still work because original paths are preserved
        $body = $fluidEmail->getHtmlBody();
        self::assertNotEmpty($body);
    }

    #[Test]
    public function createFromRequestAppliesFormatFromSiteSettings(): void
    {
        $siteSettings = SiteSettings::createFromSettingsTree([
            'email' => [
                'format' => 'plain',
            ],
        ]);
        $site = new Site('test-site', 1, [], $siteSettings);
        $request = $this->createRequestWithSite($site);
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->createFromRequest($request);
        $fluidEmail->setTemplate('WithSubject')
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->assign('content', 'Test content');

        // Force generation and check that only text body exists
        $fluidEmail->ensureValidity();
        self::assertNotEmpty($fluidEmail->getTextBody());
        self::assertEmpty($fluidEmail->getHtmlBody());
    }

    #[Test]
    public function createFromRequestIgnoresEmptyFormatFromSiteSettings(): void
    {
        $siteSettings = SiteSettings::createFromSettingsTree([
            'email' => [
                'format' => '',
            ],
        ]);
        $site = new Site('test-site', 1, [], $siteSettings);
        $request = $this->createRequestWithSite($site);
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->createFromRequest($request);
        $fluidEmail->setTemplate('WithSubject')
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->assign('content', 'Test content');

        // Should use default format (both) which creates both text and html
        $fluidEmail->ensureValidity();
        self::assertNotEmpty($fluidEmail->getHtmlBody());
        self::assertNotEmpty($fluidEmail->getTextBody());
    }

    #[Test]
    public function createFromRequestWithEmptySiteSettingsUsesGlobalConfig(): void
    {
        $siteSettings = SiteSettings::createFromSettingsTree([]);
        $site = new Site('test-site', 1, [], $siteSettings);
        $request = $this->createRequestWithSite($site);
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->createFromRequest($request);
        $fluidEmail->setTemplate('WithSubject')
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->assign('content', 'Test content');

        $body = $fluidEmail->getHtmlBody();
        self::assertNotEmpty($body);
    }

    #[Test]
    public function createWithRequestSetsRequestOnEmail(): void
    {
        $request = $this->createRequestWithoutSite();
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->create($request);
        $fluidEmail->setTemplate('WithSubject')
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->assign('content', 'Test content');

        // Verify email can be rendered
        $body = $fluidEmail->getHtmlBody();
        self::assertNotEmpty($body);
        self::assertStringContainsString('Test content', $body);
    }

    #[Test]
    public function createIgnoresSiteSettings(): void
    {
        $siteSettings = SiteSettings::createFromSettingsTree([
            'email' => [
                'format' => 'plain',
            ],
        ]);
        $site = new Site('test-site', 1, [], $siteSettings);
        $request = $this->createRequestWithSite($site);
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->create($request);
        $fluidEmail->setTemplate('WithSubject')
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->assign('content', 'Test content');

        // Should use default format (both) because site settings are ignored
        $fluidEmail->ensureValidity();
        self::assertNotEmpty($fluidEmail->getHtmlBody());
        self::assertNotEmpty($fluidEmail->getTextBody());
    }

    #[Test]
    public function createWithOverridesMergesTemplatePathsWithGlobalConfig(): void
    {
        $request = $this->createRequestWithoutSite();
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->createWithOverrides(
            templateRootPaths: [200 => 'EXT:core/Resources/Private/Templates/Email/'],
            request: $request,
        );
        $fluidEmail->setTemplate('WithSubject')
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->assign('content', 'Test content');

        // Email should work because paths are merged
        $body = $fluidEmail->getHtmlBody();
        self::assertNotEmpty($body);
        self::assertStringContainsString('Test content', $body);
    }

    #[Test]
    public function createWithOverridesRespectsSiteSettings(): void
    {
        $siteSettings = SiteSettings::createFromSettingsTree([
            'email' => [
                'templateRootPaths' => [
                    200 => 'EXT:core/Resources/Private/Templates/Email/',
                ],
                'format' => 'plain',
            ],
        ]);
        $site = new Site('test-site', 1, [], $siteSettings);
        $request = $this->createRequestWithSite($site);
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->createWithOverrides(
            templateRootPaths: [300 => 'EXT:core/Resources/Private/Templates/Email/'],
            request: $request,
        );
        $fluidEmail->setTemplate('WithSubject')
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->assign('content', 'Test content');

        // Site format setting should be applied
        $fluidEmail->ensureValidity();
        self::assertNotEmpty($fluidEmail->getTextBody());
        self::assertEmpty($fluidEmail->getHtmlBody());
    }

    #[Test]
    public function createWithOverridesIgnoresSiteSettingsWithoutRequest(): void
    {
        $factory = new TemplatedEmailFactory();

        $fluidEmail = $factory->createWithOverrides(
            templateRootPaths: [200 => 'EXT:core/Resources/Private/Templates/Email/'],
        );
        $fluidEmail->setTemplate('WithSubject')
            ->from('test@example.com')
            ->to('recipient@example.com')
            ->assign('content', 'Test content');

        // Should use default format (both) because no site context
        $fluidEmail->ensureValidity();
        self::assertNotEmpty($fluidEmail->getHtmlBody());
        self::assertNotEmpty($fluidEmail->getTextBody());
    }

    private function createRequestWithoutSite(): ServerRequest
    {
        $normalizedParams = $this->createMock(NormalizedParams::class);
        return (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('normalizedParams', $normalizedParams);
    }

    private function createRequestWithSite(Site $site): ServerRequest
    {
        $normalizedParams = $this->createMock(NormalizedParams::class);
        return (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', $normalizedParams);
    }
}
