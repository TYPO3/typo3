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
        $extensionUid = $this->createRemoteExtensionRecord(
            'ext_with_dependency',
            '2.0.0',
            ['depends' => ['ext_dependency' => '1.0.0-1.99.99']]
        );
        $this->createRemoteExtensionRecord('ext_dependency', '1.0.0');

        $request = $this->createCheckDependenciesRequest($extensionUid);
        $response = $this->get(DownloadController::class)->processRequest(new Request($request));

        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($result['hasDependencies']);
        self::assertFalse($result['hasErrors']);
        self::assertStringContainsString('ext_dependency (new version 1.0.0)', $result['message']);
        self::assertSame('1.0.0', $result['dependencies']['download']['ext_dependency']['version']);
    }

    /**
     * Creates a record in the local mirror of the remote (TER) extension list.
     */
    private function createRemoteExtensionRecord(string $extensionKey, string $version, array $constraints = []): int
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_extensionmanager_domain_model_extension');
        $connection->insert(
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
        return (int)$connection->lastInsertId();
    }

    private function createCheckDependenciesRequest(int $extensionUid): ServerRequest
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setPluginName('extensionmanager');
        $extbaseRequestParameters->setControllerExtensionName('Extensionmanager');
        $extbaseRequestParameters->setControllerName('Download');
        $extbaseRequestParameters->setControllerActionName('checkDependencies');
        $extbaseRequestParameters->setFormat('json');
        $extbaseRequestParameters->setArgument('extension', $extensionUid);
        $request = (new ServerRequest('https://example.com/typo3/module/extensions'))
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
