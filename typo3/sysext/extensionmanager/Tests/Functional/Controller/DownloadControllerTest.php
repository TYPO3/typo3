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

namespace TYPO3\CMS\Extensionmanager\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extensionmanager\Controller\DownloadController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DownloadControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'impexp',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function checkDependenciesActionRendersVersionOfDependencyToBeDownloadedFromRemote(): void
    {
        $this->createRemoteExtensionRecord(
            'ext_with_dependency',
            '2.0.0',
            ['depends' => ['ext_dependency' => '1.0.0-1.99.99']]
        );
        $this->createRemoteExtensionRecord('ext_dependency', '1.0.0');

        $request = $this->createCheckDependenciesRequest(
            ['packageKey' => 'ext_with_dependency', 'version' => '2.0.0', 'remote' => 'ter']
        );
        $response = $this->get(DownloadController::class)->processRequest(new Request($request));

        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($result['hasDependencies']);
        self::assertFalse($result['hasErrors']);
        self::assertStringContainsString('ext_dependency (new version 1.0.0)', $result['message']);
        self::assertSame('1.0.0', $result['dependencies']['download']['ext_dependency']['version']);
        // The follow-up action is addressed by the full package identity, not by a record uid.
        parse_str((string)parse_url($result['url'], PHP_URL_QUERY), $queryParameters);
        self::assertSame(
            ['packageKey' => 'ext_with_dependency', 'version' => '2.0.0', 'remote' => 'ter'],
            $queryParameters['identifier']
        );
    }

    public static function incompleteIdentifierProvider(): array
    {
        return [
            'empty packageKey' => [['packageKey' => '', 'version' => '1.0.0', 'remote' => 'ter']],
            'empty version' => [['packageKey' => 'ext_dependency', 'version' => '', 'remote' => 'ter']],
            'empty remote' => [['packageKey' => 'ext_dependency', 'version' => '1.0.0', 'remote' => '']],
            'missing packageKey' => [['version' => '1.0.0', 'remote' => 'ter']],
            'missing version' => [['packageKey' => 'ext_dependency', 'remote' => 'ter']],
            'missing remote' => [['packageKey' => 'ext_dependency', 'version' => '1.0.0']],
        ];
    }

    #[Test]
    #[DataProvider('incompleteIdentifierProvider')]
    public function checkDependenciesActionRejectsIncompleteIdentifier(array $identifier): void
    {
        $this->createRemoteExtensionRecord('ext_dependency', '1.0.0');

        $request = $this->createCheckDependenciesRequest($identifier);
        $response = $this->get(DownloadController::class)->processRequest(new Request($request));

        self::assertSame(400, $response->getStatusCode());
    }

    /**
     * The catalogue is read from TER while the package to download is resolved
     * against the imported list, so a version shown in the list may have no
     * record - as it may after an extension list update, which drops and
     * recreates all records of a remote. The answer has to tell the user.
     */
    #[Test]
    public function checkDependenciesActionReportsAPackageMissingFromTheImportedList(): void
    {
        $request = $this->createCheckDependenciesRequest(
            ['packageKey' => 'ext_dependency', 'version' => '1.0.0', 'remote' => 'ter']
        );
        $response = $this->get(DownloadController::class)->processRequest(new Request($request));

        self::assertSame(200, $response->getStatusCode());
        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($result['hasErrors']);
        self::assertFalse($result['hasDependencies']);
        self::assertNotSame('', $result['title']);
        self::assertStringContainsString('"ext_dependency" in version "1.0.0"', $result['message']);
        self::assertStringContainsString('update it again', $result['message']);
    }

    #[Test]
    public function checkDependenciesActionAnswersAFailedDependencyCheckWithATitledError(): void
    {
        // "requires" is not a dependency type, so building the dependencies throws
        $this->createRemoteExtensionRecord(
            'ext_with_dependency',
            '2.0.0',
            ['requires' => ['ext_dependency' => '1.0.0-1.99.99']]
        );

        $request = $this->createCheckDependenciesRequest(
            ['packageKey' => 'ext_with_dependency', 'version' => '2.0.0', 'remote' => 'ter']
        );
        $response = $this->get(DownloadController::class)->processRequest(new Request($request));

        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($result['hasErrors']);
        self::assertStringContainsString('requires was not a valid dependency type', $result['message']);
        self::assertSame('Dependencies could not be resolved', $result['title']);
    }

    #[Test]
    public function checkDistributionDependenciesActionReportsAPackageMissingFromTheImportedList(): void
    {
        $request = $this->createDownloadRequest(
            'checkDistributionDependencies',
            ['packageKey' => 'ext_distribution', 'version' => '1.0.0', 'remote' => 'ter'],
            'POST'
        );
        $response = $this->get(DownloadController::class)->processRequest(new Request($request));

        self::assertSame(200, $response->getStatusCode());
        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($result['installed']);
        self::assertFalse($result['hasDependencyErrors']);
        self::assertStringContainsString('"ext_distribution" in version "1.0.0"', $result['error']);
    }

    #[Test]
    public function installDistributionActionReportsAPackageMissingFromTheImportedList(): void
    {
        $request = $this->createDownloadRequest(
            'installDistribution',
            ['packageKey' => 'ext_distribution', 'version' => '1.0.0', 'remote' => 'ter'],
            'POST'
        );
        $response = $this->get(DownloadController::class)->processRequest(new Request($request));

        self::assertSame(200, $response->getStatusCode());
        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($result['success']);
        self::assertStringContainsString('"ext_distribution" in version "1.0.0"', $result['error']);
    }

    /**
     * Creates a record in the local mirror of the remote (TER) extension list.
     */
    private function createRemoteExtensionRecord(string $extensionKey, string $version, array $constraints = []): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tx_extensionmanager_domain_model_extension')
            ->insert(
                'tx_extensionmanager_domain_model_extension',
                [
                    'extension_key' => $extensionKey,
                    'remote' => 'ter',
                    'version' => $version,
                    'integer_version' => VersionNumberUtility::convertVersionNumberToInteger($version),
                    'title' => $extensionKey,
                    'serialized_dependencies' => $constraints === [] ? '' : serialize($constraints),
                    'current_version' => 1,
                    'review_state' => 0,
                ]
            );
    }

    /**
     * @param array{packageKey?: string, version?: string, remote?: string} $identifier
     */
    private function createCheckDependenciesRequest(array $identifier): ServerRequest
    {
        return $this->createDownloadRequest('checkDependencies', $identifier);
    }

    /**
     * @param array{packageKey?: string, version?: string, remote?: string} $identifier
     */
    private function createDownloadRequest(string $actionName, array $identifier, string $method = 'GET'): ServerRequest
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setPluginName('extensionmanager');
        $extbaseRequestParameters->setControllerExtensionName('Extensionmanager');
        $extbaseRequestParameters->setControllerName('Download');
        $extbaseRequestParameters->setControllerActionName($actionName);
        $extbaseRequestParameters->setFormat('json');
        $extbaseRequestParameters->setArgument('identifier', $identifier);
        $request = new ServerRequest('https://example.com/typo3/module/extensions', $method)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('moduleData', new ModuleData('extensionmanager', []))
            ->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''))
            ->withAttribute(
                'route',
                new Route(
                    '/module/extensions',
                    [
                        'packageName' => 'typo3/cms-extensionmanager',
                        '_identifier' => 'extensionmanager',
                    ]
                )
            );
        // ConfigurationManager is injected into the controller (DI) but needs a request.
        $GLOBALS['TYPO3_REQUEST'] = $request;
        return $request;
    }
}
