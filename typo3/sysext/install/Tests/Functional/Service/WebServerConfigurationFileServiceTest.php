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

namespace TYPO3\CMS\Install\Tests\Functional\Service;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Install\Service\WebServerConfigurationFileService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class WebServerConfigurationFileServiceTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    /**
     * @dataProvider webServerConfigurationIsChangedDataProvider
     * @test
     *
     * @param string $webServer The webserver to use
     * @param string $configurationFile The file to update
     * @param bool $shouldBeChanged Whether the file should be updated
     * @param array $addedParts String parts, the file should contain after the update
     * @param array $removedParts String parts, the file should not longer contain after the update
     */
    public function addWebServerSpecificBackendRoutingRewriteRulesTest(
        string $webServer,
        string $configurationFile,
        bool $shouldBeChanged = false,
        array $addedParts = [],
        array $removedParts = []
    ): void {
        $_SERVER['SERVER_SOFTWARE'] = $webServer;
        $filename = Environment::getPublicPath() . '/' . ($webServer === 'Apache' ? '.htaccess' : 'web.config');

        file_put_contents($filename, file_get_contents(__DIR__ . '/../Fixtures/' . $configurationFile));

        $changed = (new WebServerConfigurationFileService())->addWebServerSpecificBackendRoutingRewriteRules();

        self::assertEquals($shouldBeChanged, $changed);

        if ($shouldBeChanged) {
            $newFileContent = file_get_contents($filename);
            // Check if updated file contains parts
            foreach ($addedParts as $part) {
                self::assertStringContainsString($part, $newFileContent);
            }
            // Check if updated file does not contain parts
            foreach ($removedParts as $part) {
                self::assertStringNotContainsString($part, $newFileContent);
            }
        }

        unlink($filename);
    }

    public function webServerConfigurationIsChangedDataProvider(): \Generator
    {
        yield '.htaccess with custom configuration - will not be changed' => [
            'Apache',
            '.htaccess_custom_config',
        ];
        yield '.htaccess with wrong order - will not be changed' => [
            'Apache',
            '.htaccess_wrong_order',
        ];
        yield '.htaccess already updated -  will not be changed' => [
            'Apache',
            '.htaccess_already_updated',
        ];
        yield '.htaccess without custom configuration - will be changed' => [
            'Apache',
            '.htaccess_valid',
            true,
            [
                'TYPO3 automated migration',
                '# If the file/symlink/directory does not exist but is below /typo3/, redirect to the TYPO3 Backend entry point.',
                'RewriteRule ^typo3/(.*)$ %{ENV:CWD}typo3/index.php [QSA,L]',
            ],
            [
                'Stop rewrite processing, if we are in the typo3/ directory',
                'RewriteRule ^(?:typo3/|',
            ],
        ];
        yield 'web.config with custom configuration - will not be changed' => [
            'Microsoft-IIS',
            'web.config_custom_config',
        ];
        yield 'web.config with wrong order - will not be changed' => [
            'Microsoft-IIS',
            'web.config_wrong_order',
        ];
        yield 'web.config already updated - will not be changed' => [
            'Microsoft-IIS',
            'web.config_already_updated',
        ];
        yield 'web.config without custom configuration - will be changed' => [
            'Microsoft-IIS',
            'web.config_valid',
            true,
            [
                'TYPO3 automated migration',
                'TYPO3 - If the file/directory does not exist but is below /typo3/, redirect to the TYPO3 Backend entry point.',
                '<action type="Rewrite" url="typo3/index.php" appendQueryString="true" />',
            ],
            [
                '<match url="^/(typo3|',
            ],
        ];
    }
}
