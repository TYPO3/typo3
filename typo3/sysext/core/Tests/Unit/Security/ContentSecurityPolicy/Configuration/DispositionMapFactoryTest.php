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

namespace TYPO3\CMS\Core\Tests\Unit\Security\ContentSecurityPolicy\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\DispositionConfiguration;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\DispositionMapFactory;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Disposition;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DispositionMapFactoryTest extends UnitTestCase
{
    public static function featuresAreReflectedInDispositionMapDataProvider(): \Generator
    {
        yield 'all features disabled' => [
            'features' => [
                'security.frontend.enforceContentSecurityPolicy' => false,
                'security.frontend.reportContentSecurityPolicy' => false,
            ],
            'expectation' => [],
        ];
        yield 'enforce feature enabled' => [
            'features' => [
                'security.frontend.enforceContentSecurityPolicy' => true,
                'security.frontend.reportContentSecurityPolicy' => false,
            ],
            'expectation' => [Disposition::enforce],
        ];
        yield 'report feature enabled' => [
            'features' => [
                'security.frontend.enforceContentSecurityPolicy' => false,
                'security.frontend.reportContentSecurityPolicy' => true,
            ],
            'expectation' => [Disposition::report],
        ];
        yield 'both features enabled' => [
            'features' => [
                'security.frontend.enforceContentSecurityPolicy' => true,
                'security.frontend.reportContentSecurityPolicy' => true,
            ],
            'expectation' => [Disposition::enforce, Disposition::report],
        ];
    }

    #[DataProvider('featuresAreReflectedInDispositionMapDataProvider')]
    #[Test]
    public function featuresAreReflectedInDispositionMap(array $features, array $expectation): void
    {
        $featuresMock = $this->createFeaturesMock($features);
        $subject = new DispositionMapFactory($featuresMock);
        $result = $subject->buildDispositionMap([]);
        self::assertSame($expectation, $result->keys());
    }

    public static function configurationIsReflectedInDispositionMapDataProvider(): \Generator
    {
        $allFeaturesDisabled = [
            'security.frontend.enforceContentSecurityPolicy' => false,
            'security.frontend.reportContentSecurityPolicy' => false,
        ];
        $bothFeaturesEnabled = [
            'security.frontend.enforceContentSecurityPolicy' => true,
            'security.frontend.reportContentSecurityPolicy' => true,
        ];

        yield 'all features disabled, active:false' => [
            'features' => $allFeaturesDisabled,
            'configuration' => ['active' => false],
            'expectation' => [],
        ];
        yield 'both features enabled, active:false' => [
            'features' => $bothFeaturesEnabled,
            'configuration' => ['active' => false],
            'expectation' => [],
        ];
        // @todo `active: true` for explicitly activating CSP is not in effect yet (v14 topic)
        yield 'all features disabled, active:true' => [
            'features' => $allFeaturesDisabled,
            'configuration' => ['active' => true],
            'expectation' => [],
        ];
        // @todo `active: true` for explicitly activating CSP is not in effect yet (v14 topic)
        yield 'both features enabled, active:true' => [
            'features' => $bothFeaturesEnabled,
            'configuration' => ['active' => true],
            'expectation' => [Disposition::enforce, Disposition::report],
        ];
        yield 'all features disabled, enforce:false' => [
            'features' => $allFeaturesDisabled,
            'configuration' => ['enforce' => false],
            'expectation' => [],
        ];
        yield 'both features enabled, enforce:false' => [
            'features' => $bothFeaturesEnabled,
            'configuration' => ['enforce' => false],
            'expectation' => [Disposition::report],
        ];
        yield 'all features disabled, enforce:true' => [
            'features' => $allFeaturesDisabled,
            'configuration' => ['enforce' => true],
            'expectation' => [Disposition::enforce],
        ];
        yield 'both features enabled, enforce:true' => [
            'features' => $bothFeaturesEnabled,
            'configuration' => ['enforce' => true],
            'expectation' => [Disposition::enforce],
        ];
        yield 'all features disabled, enforce:array' => [
            'features' => $allFeaturesDisabled,
            'configuration' => ['enforce' => []],
            'expectation' => [Disposition::enforce],
        ];
        yield 'both features enabled, enforce:array' => [
            'features' => $bothFeaturesEnabled,
            'configuration' => ['enforce' => []],
            'expectation' => [Disposition::enforce],
        ];
        yield 'all features disabled, report:false' => [
            'features' => $allFeaturesDisabled,
            'configuration' => ['report' => false],
            'expectation' => [],
        ];
        yield 'both features enabled, report:false' => [
            'features' => $bothFeaturesEnabled,
            'configuration' => ['report' => false],
            'expectation' => [Disposition::enforce],
        ];
        yield 'all features disabled, report:true' => [
            'features' => $allFeaturesDisabled,
            'configuration' => ['report' => true],
            'expectation' => [Disposition::report],
        ];
        yield 'both features enabled, report:true' => [
            'features' => $bothFeaturesEnabled,
            'configuration' => ['report' => true],
            'expectation' => [Disposition::report],
        ];
        yield 'all features disabled, report:array' => [
            'features' => $allFeaturesDisabled,
            'configuration' => ['report' => []],
            'expectation' => [Disposition::report],
        ];
        yield 'both features enabled, report:array' => [
            'features' => $bothFeaturesEnabled,
            'configuration' => ['report' => []],
            'expectation' => [Disposition::report],
        ];
        yield 'all features disabled, enforce:false & report:false' => [
            'features' => $allFeaturesDisabled,
            'configuration' => ['enforce' => false, 'report' => false],
            'expectation' => [],
        ];
        yield 'both features enabled, enforce:false & report:false' => [
            'features' => $bothFeaturesEnabled,
            'configuration' => ['enforce' => false, 'report' => false],
            'expectation' => [],
        ];
        yield 'all features disabled, enforce:true & report:true' => [
            'features' => $allFeaturesDisabled,
            'configuration' => ['enforce' => true, 'report' => true],
            'expectation' => [Disposition::enforce, Disposition::report],
        ];
        yield 'both features enabled, enforce:true & report:true' => [
            'features' => $bothFeaturesEnabled,
            'configuration' => ['enforce' => true, 'report' => true],
            'expectation' => [Disposition::enforce, Disposition::report],
        ];
        yield 'all features disabled, enforce:array & report:array' => [
            'features' => $allFeaturesDisabled,
            'configuration' => ['enforce' => [], 'report' => []],
            'expectation' => [Disposition::enforce, Disposition::report],
        ];
        yield 'both features enabled, enforce:array & report:array' => [
            'features' => $bothFeaturesEnabled,
            'configuration' => ['enforce' => [], 'report' => []],
            'expectation' => [Disposition::enforce, Disposition::report],
        ];
    }

    #[DataProvider('configurationIsReflectedInDispositionMapDataProvider')]
    #[Test]
    public function configurationIsReflectedInDispositionMap(array $features, array $configuration, array $expectation): void
    {
        $featuresMock = $this->createFeaturesMock($features);
        $subject = new DispositionMapFactory($featuresMock);
        $result = $subject->buildDispositionMap($configuration);
        self::assertSame($expectation, $result->keys());
    }

    #[Test]
    public function enforceConfigurationIsReflectedInEnforceDispositionConfiguration(): void
    {
        // note: `security.frontend.enforceContentSecurityPolicy` is enabled per default for this test
        $featuresMock = $this->createFeaturesMock();
        $subject = new DispositionMapFactory($featuresMock);

        $enforceConfiguration = [
            'inheritDefault' => false,
            'includeResolutions' => false,
            'reportingUrl' => 'https://csp.example.org/',
            'mutations' => [
                [
                    'mode' => 'extend',
                    'directive' => Directive::ImgSrc,
                    'sources' => [],
                ],
            ],
            'packages' => [
                'my-vendor/my-package' => true,
            ],
        ];
        $result = $subject->buildDispositionMap(['enforce' => $enforceConfiguration]);

        self::assertInstanceOf(DispositionConfiguration::class, $result[Disposition::enforce]);
        self::assertSame($enforceConfiguration['inheritDefault'], $result[Disposition::enforce]->inheritDefault);
        self::assertSame($enforceConfiguration['includeResolutions'], $result[Disposition::enforce]->includeResolutions);
        self::assertSame($enforceConfiguration['reportingUrl'], $result[Disposition::enforce]->reportingUrl);
        self::assertSame($enforceConfiguration['mutations'], $result[Disposition::enforce]->mutations);
        self::assertSame($enforceConfiguration['packages'], $result[Disposition::enforce]->packages);
    }

    #[Test]
    public function legacyTopLevelConfigurationIsReflectedInEnforceDispositionConfiguration(): void
    {
        // note: `security.frontend.enforceContentSecurityPolicy` is enabled per default for this test
        $featuresMock = $this->createFeaturesMock();
        $subject = new DispositionMapFactory($featuresMock);

        $topLevelConfiguration = [
            'inheritDefault' => false,
            // `includeResolutions` will be ignored in top-level configuration
            'includeResolutions' => false,
            // `reportingUrl` will be ignored in top-level configuration
            'reportingUrl' => 'https://csp.example.org/',
            'mutations' => [
                [
                    'mode' => 'extend',
                    'directive' => Directive::ImgSrc,
                    'sources' => [],
                ],
            ],
            // `packages` will be ignored in top-level configuration
            'packages' => [
                'my-vendor/my-package' => true,
            ],
        ];
        $result = $subject->buildDispositionMap($topLevelConfiguration);

        self::assertInstanceOf(DispositionConfiguration::class, $result[Disposition::enforce]);
        self::assertSame($topLevelConfiguration['inheritDefault'], $result[Disposition::enforce]->inheritDefault);
        // `reportingUrl` is ignored in top-level configuration
        self::assertNull($result[Disposition::enforce]->reportingUrl);
        // `includeResolutions` is ignored in top-level configuration
        self::assertTrue($result[Disposition::enforce]->includeResolutions);
        self::assertSame($topLevelConfiguration['mutations'], $result[Disposition::enforce]->mutations);
        // `packages` are ignored in top-level configuration
        self::assertEmpty($result[Disposition::enforce]->packages);
    }

    private function createFeaturesMock(?array $features = null): Features
    {
        $features ??= ['security.frontend.enforceContentSecurityPolicy' => true];
        $featuresMock = $this->createMock(Features::class);
        $featuresMock->method('isFeatureEnabled')->willReturnCallback(
            static fn(string $featureName) => !empty($features[$featureName])
        );
        return $featuresMock;
    }
}
