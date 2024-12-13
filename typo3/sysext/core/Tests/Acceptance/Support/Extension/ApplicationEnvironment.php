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

namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Extension;

use Codeception\Event\SuiteEvent;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mailer\Transport\NullTransport;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use TYPO3\CMS\Styleguide\TcaDataGenerator\GeneratorFrontend;
use TYPO3\TestingFramework\Core\Acceptance\Extension\BackendEnvironment;

/**
 * Load various core extensions and styleguide and call styleguide generator
 */
final class ApplicationEnvironment extends BackendEnvironment
{
    /**
     * Load a list of core extensions and styleguide
     *
     * @var array
     */
    protected $localConfig = [
        'coreExtensionsToLoad' => [
            'core',
            'beuser',
            'extbase',
            'fluid',
            'filelist',
            'extensionmanager',
            'setup',
            'backend',
            'belog',
            'install',
            'impexp',
            'frontend',
            'redirects',
            'reports',
            'sys_note',
            'scheduler',
            'tstemplate',
            'lowlevel',
            'dashboard',
            'workspaces',
            'info',
            'fluid_styled_content',
            'indexed_search',
            'adminpanel',
            'form',
            'felogin',
            'seo',
            'recycler',
            'viewpage',
            'styleguide',
        ],
        'csvDatabaseFixtures' => [
            __DIR__ . '/../../Fixtures/BackendEnvironment.csv',
        ],
        'configurationToUseInTestInstance' => [
            'MAIL' => [
                'transport' => NullTransport::class,
            ],
        ],
        'additionalFoldersToCreate' => [
            '/fileadmin/user_upload/',
            '/typo3temp/var/lock',
        ],
    ];

    /**
     * Generate styleguide data
     */
    public function bootstrapTypo3Environment(SuiteEvent $suiteEvent): void
    {
        parent::bootstrapTypo3Environment($suiteEvent);
        $useSiteSets = !str_contains($suiteEvent->getSettings()['current_environment'] ?? '', 'systemplate');

        // styleguide generator uses DataHandler for some parts. DataHandler needs an initialized BE user
        // with admin right and the live workspace.
        $request = $this->createServerRequest('https://typo3-testing.local/typo3/');
        Bootstrap::initializeBackendUser(BackendUserAuthentication::class, $request);
        $GLOBALS['BE_USER']->user['username'] = 'acceptanceTestSetup';
        $GLOBALS['BE_USER']->user['admin'] = 1;
        $GLOBALS['BE_USER']->user['uid'] = 1;
        $GLOBALS['BE_USER']->workspace = 0;
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);

        $faviconLinkPath = '../../../../favicon.ico';
        if (!is_file($faviconLinkPath)) {
            symlink('typo3/sysext/backend/Resources/Public/Icons/favicon.ico', $faviconLinkPath);
        }

        $styleguideGenerator = GeneralUtility::makeInstance(Generator::class);
        $styleguideGenerator->create();

        // Force basePath for testing environment, required for the frontend!
        // Otherwise, the page can not be found, also do not set root page to
        // 'hidden' so menus (e.g. menu_sitemap_pages) are displayed correctly
        $styleguideGeneratorFrontend = GeneralUtility::makeInstance(GeneratorFrontend::class);
        $styleguideGeneratorFrontend->create('/', 0, $useSiteSets);

        // @todo: ugly workaround for InstallTool/AbstractCest.php
        $instancePath = getenv('TYPO3_PATH_ROOT', true);
        putenv('TYPO3_ACCEPTANCE_PATH_WEB=' . $instancePath);
        putenv('TYPO3_ACCEPTANCE_PATH_VAR=' . $instancePath . '/typo3temp/var');
        putenv('TYPO3_ACCEPTANCE_PATH_CONFIG=' . $instancePath . '/typo3conf');
    }

    // @todo Eventually move this up to TF::BackendEnvironment, but then as protected.
    private function createServerRequest(string $url, string $method = 'GET'): ServerRequestInterface
    {
        $requestUrlParts = parse_url($url);
        $docRoot = getenv('TYPO3_PATH_APP') ?? '';
        $serverParams = [
            'DOCUMENT_ROOT' => $docRoot,
            'HTTP_USER_AGENT' => 'TYPO3 Functional Test Request',
            'HTTP_HOST' => $requestUrlParts['host'] ?? 'localhost',
            'SERVER_NAME' => $requestUrlParts['host'] ?? 'localhost',
            'SERVER_ADDR' => '127.0.0.1',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'SCRIPT_FILENAME' => $docRoot . '/index.php',
            'QUERY_STRING' => $requestUrlParts['query'] ?? '',
            'REQUEST_URI' => $requestUrlParts['path'] . (isset($requestUrlParts['query']) ? '?' . $requestUrlParts['query'] : ''),
            'REQUEST_METHOD' => $method,
        ];
        // Define HTTPS and server port
        if (isset($requestUrlParts['scheme'])) {
            if ($requestUrlParts['scheme'] === 'https') {
                $serverParams['HTTPS'] = 'on';
                $serverParams['SERVER_PORT'] = '443';
            } else {
                $serverParams['SERVER_PORT'] = '80';
            }
        }

        // Define a port if used in the URL
        if (isset($requestUrlParts['port'])) {
            $serverParams['SERVER_PORT'] = $requestUrlParts['port'];
        }
        // set up normalizedParams
        $request = new ServerRequest($url, $method, null, [], $serverParams);
        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }
}
