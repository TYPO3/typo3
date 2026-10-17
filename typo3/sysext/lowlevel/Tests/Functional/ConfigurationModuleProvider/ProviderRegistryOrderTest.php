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

namespace TYPO3\CMS\Lowlevel\Tests\Functional\ConfigurationModuleProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ProviderRegistryOrderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['lowlevel', 'form', 'reactions', 'webhooks'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/be_users_admin.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function coreProvidersAreOrderedAsDefinedByBeforeAndAfter(): void
    {
        $identifiers = array_keys($this->get(ProviderRegistry::class)->getProviders());

        $expectedOrder = [
            'confVars',
            'tca',
            'services',
            'pagesTypes',
            'beUserUc',
            'beUserTsConfig',
            'beRoutes',
            'backendModules',
            'httpMiddlewareStacks',
            'contentSecurityPolicyMutations',
            'sitesTcaConfiguration',
            'sitesYamlConfiguration',
            'eventListeners',
            'mfaProviders',
            'softReferenceParsers',
            'toolbaritems',
            'symfonyExpressionLanguage',
            'doctrineDbalDriverMiddlewares',
        ];
        self::assertSame($expectedOrder, array_values(array_intersect($identifiers, $expectedOrder)));
    }

    #[Test]
    public function providersOfCoreExtensionsAreOrderedAfterTheirReferencedProviders(): void
    {
        $identifiers = array_keys($this->get(ProviderRegistry::class)->getProviders());

        self::assertGreaterThan(array_search('symfonyExpressionLanguage', $identifiers, true), array_search('translationDomainList', $identifiers, true));
        self::assertGreaterThan(array_search('mfaProviders', $identifiers, true), array_search('reactions', $identifiers, true));
        self::assertGreaterThan(array_search('reactions', $identifiers, true), array_search('webhooks', $identifiers, true));
    }

    #[Test]
    public function coreProvidersAreAssignedToTheirFunctionalGroups(): void
    {
        $grouped = array_map(array_keys(...), $this->get(ProviderRegistry::class)->getGroupedProviders());
        $expected = [
            'core' => ['confVars', 'services', 'symfonyExpressionLanguage', 'doctrineDbalDriverMiddlewares'],
            'data' => ['tca', 'pagesTypes', 'softReferenceParsers'],
            'backend' => ['beUserUc', 'beUserTsConfig', 'beRoutes', 'backendModules', 'mfaProviders', 'toolbaritems'],
            'sites' => ['sitesTcaConfiguration', 'sitesYamlConfiguration'],
            'requestHandling' => ['httpMiddlewareStacks', 'contentSecurityPolicyMutations'],
            'events' => ['eventListeners', 'reactions', 'webhooks'],
            'frameworks' => ['translationDomainList', 'fluidNamespaces', 'formYamlConfiguration', 'extbaseClassConfiguration'],
        ];

        self::assertSame(array_keys($expected), array_keys($grouped));
        foreach ($expected as $group => $identifiers) {
            self::assertEqualsCanonicalizing($identifiers, $grouped[$group], $group);
        }
    }
}
