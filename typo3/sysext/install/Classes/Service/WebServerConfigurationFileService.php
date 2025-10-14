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

namespace TYPO3\CMS\Install\Service;

use TYPO3\CMS\Core\Core\Environment;

/**
 * Handles webserver specific configuration files
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class WebServerConfigurationFileService
{
    protected string $webServer;
    protected string $publicPath;

    public function __construct()
    {
        $this->webServer = $this->getWebServer();
        $this->publicPath = Environment::getPublicPath();
    }

    public function addWebServerSpecificBackendRoutingRewriteRules(): bool
    {
        $changed = false;

        if ($this->isApache()) {
            $changed = $this->addApacheBackendRoutingRewriteRules();
        } elseif ($this->isMicrosoftIis()) {
            $changed = $this->addMicrosoftIisBackendRoutingRewriteRules();
        }

        return $changed;
    }

    protected function addApacheBackendRoutingRewriteRules(): bool
    {
        $configurationFilename = $this->publicPath . '/.htaccess';
        $configurationFileContent = $this->getConfigurationFileContent($configurationFilename);

        if ($configurationFileContent === '' || !$this->updateNecessary($configurationFileContent)) {
            return false;
        }

        $count = 0;
        $configurationFileContent = preg_replace(
            pattern: sprintf('/%s/', implode('\s*', array_map(
                static fn($s) => preg_quote($s, '/'),
                [
                    'RewriteCond %{REQUEST_FILENAME} !-d',
                    'RewriteCond %{REQUEST_FILENAME} !-l',
                    'RewriteRule ^typo3/(.*)$ %{ENV:CWD}typo3/index.php [QSA,L]',
                ]
            ))),
            replacement: 'RewriteRule ^typo3/(.*)$ %{ENV:CWD}index.php [QSA,L]',
            subject: $configurationFileContent,
            count: $count
        );

        $configurationFileContent = str_replace(
            [
                '# Stop rewrite processing, if we are in any other known directory',
                '# Stop rewrite processing, if we are in the typo3/ directory or any other known directory', // v10 style comment
                '# If the file does not exist but is below /typo3/, redirect to the TYPO3 Backend entry point.',
            ],
            [
                '# Stop rewrite processing, if we are in any known directory',
                '# Stop rewrite processing, if we are in any known directory',
                '# If the file does not exist but is below /typo3/, rewrite to the main TYPO3 entry point.',
            ],
            $configurationFileContent,
            $count
        );

        // Return FALSE in case no replacement has been done. This might be the
        // case if already modified versions of the configuration are in place.
        return $count > 0 && file_put_contents($configurationFilename, $configurationFileContent);
    }

    protected function addMicrosoftIisBackendRoutingRewriteRules(): bool
    {
        $configurationFilename = $this->publicPath . '/web.config';
        $configurationFileContent = $this->getConfigurationFileContent($configurationFilename);

        if ($configurationFileContent === '' || !$this->updateNecessary($configurationFileContent)) {
            return false;
        }

        $count = 0;
        $configurationFileContent = str_replace(
            [
                '<rule name="TYPO3 - If the file/directory does not exist but is below /typo3/, redirect to the TYPO3 Backend entry point." stopProcessing="true">',
                '<action type="Rewrite" url="typo3/index.php" appendQueryString="true" />',
            ],
            [
                '<rule name="TYPO3 - If the file/directory does not exist but is below /typo3/, redirect to the main TYPO3 entry point." stopProcessing="true">',
                '<action type="Rewrite" url="index.php" appendQueryString="true" />',
            ],
            $configurationFileContent,
            $count
        );

        // Return FALSE in case no replacement has been done. This might be the
        // case if already modified versions of the configuration are in place.
        return $count > 0 && file_put_contents($configurationFilename, $configurationFileContent);
    }

    /**
     * Returns the webserver configuration if it exists, is readable and is writeable
     *
     * @param string $filename The webserver configuration file name
     * @return string The webserver configuration or an empty string
     */
    protected function getConfigurationFileContent(string $filename): string
    {
        if (!file_exists($filename) || !is_readable($filename) || !is_writable($filename)) {
            return '';
        }

        return file_get_contents($filename) ?: '';
    }

    /**
     * Checks if the webserver configuration needs to be updated.
     *
     * This currently checks if the "known directory" rule still
     * contains the `typo3` directory and the frontend rewrite rule
     * exists. Later is needed since the backend rewrite rule must
     * be placed before.
     *
     * @param string $configurationFileContent
     */
    protected function updateNecessary(string $configurationFileContent): bool
    {
        if ($this->isApache()) {
            return str_contains($configurationFileContent, 'RewriteRule ^typo3/(.*)$ %{ENV:CWD}typo3/index.php [QSA,L]');
        }
        if ($this->isMicrosoftIis()) {
            return str_contains($configurationFileContent, '<action type="Rewrite" url="typo3/index.php" appendQueryString="true" />');
        }
        return false;
    }

    protected function isApache(): bool
    {
        return str_starts_with($this->webServer, 'Apache');
    }

    protected function isMicrosoftIis(): bool
    {
        return str_starts_with($this->webServer, 'Microsoft-IIS');
    }

    protected function getWebServer(): string
    {
        return $_SERVER['SERVER_SOFTWARE'] ?? '';
    }
}
