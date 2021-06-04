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

        if (Environment::getPublicPath() === Environment::getProjectPath()) {
            $this->publicPath = Environment::getPublicPath();
        } else {
            $this->publicPath = substr(Environment::getPublicPath(), strlen(Environment::getProjectPath()) + 1);
        }
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

        $newRewriteRule = PHP_EOL . '
    ### BEGIN: TYPO3 automated migration
    # If the file/symlink/directory does not exist but is below /typo3/, redirect to the TYPO3 Backend entry point.
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-l
    RewriteRule ^typo3/(.*)$ %{ENV:CWD}typo3/index.php [QSA,L]
    ### END: TYPO3 automated migration';

        return (bool)file_put_contents(
            $configurationFilename,
            str_replace(
                '# Stop rewrite processing, if we are in the typo3/ directory or any other known directory',
                '# Stop rewrite processing, if we are in any other known directory',
                $this->performBackendRoutingRewriteRulesUpdate(
                    '/(RewriteRule\s\^\(\?\:(typo3\/\|).*\s\[L\])(.*RewriteRule\s\^\.\*\$\s%{ENV:CWD}index\.php\s\[QSA,L\])/s',
                    $newRewriteRule,
                    $configurationFileContent,
                )
            )
        );
    }

    protected function addMicrosoftIisBackendRoutingRewriteRules(): bool
    {
        $configurationFilename = $this->publicPath . '/web.config';
        $configurationFileContent = $this->getConfigurationFileContent($configurationFilename);

        if ($configurationFileContent === '' || !$this->updateNecessary($configurationFileContent)) {
            return false;
        }

        $newRewriteRule = '
                <!-- BEGIN: TYPO3 automated migration -->
                <rule name="TYPO3 - If the file/directory does not exist but is below /typo3/, redirect to the TYPO3 Backend entry point." stopProcessing="true">
                    <match url="^typo3/(.*)$" ignoreCase="false" />
                    <conditions logicalGrouping="MatchAll">
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                        <add input="{REQUEST_URI}" matchType="Pattern" pattern="^/typo3/.*$" />
                    </conditions>
                    <action type="Rewrite" url="typo3/index.php" appendQueryString="true" />
                </rule>
                <!-- END: TYPO3 automated migration -->';

        return (bool)file_put_contents(
            $configurationFilename,
            $this->performBackendRoutingRewriteRulesUpdate(
                '/(<match\surl="\^\/\((typo3\|).*\)\$"\s\/>.+?<\/rule>)(.*<action\stype="Rewrite"\surl="index\.php")/s',
                $newRewriteRule,
                $configurationFileContent
            )
        );
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
     * @return bool
     */
    protected function updateNecessary(string $configurationFileContent): bool
    {
        if ($this->isApache()) {
            return (bool)preg_match('/RewriteRule\s\^\(\?\:typo3\/\|.*\s\[L\].*RewriteRule\s\^\.\*\$\s%{ENV:CWD}index\.php\s\[QSA,L\]/s', $configurationFileContent)
                && !str_contains($configurationFileContent, 'RewriteRule ^typo3/(.*)$ %{ENV:CWD}typo3/index.php [QSA,L]');
        }
        if ($this->isMicrosoftIis()) {
            return (bool)preg_match('/<match\surl="\^\/\(typo3\|.*\)\$"\s\/>.*<action\stype="Rewrite"\surl="index.php"\sappendQueryString="true"\s\/>/s', $configurationFileContent)
                && !str_contains($configurationFileContent, '<action type="Rewrite" url="typo3/index.php" appendQueryString="true" />');
        }
        return false;
    }

    /**
     * Removes the 'typo3' directory from the existing "known directory" rewrite rule and
     * adds the new backend rewrite rule between this rule and the frontend rewrite rule.
     *
     * Pattern must contain three capturing groups:
     * 1: The "known directory" rule from which "typo3" should be removed
     * 2: The "typo3" string to be removed
     * 3: The subsequent part including the frontend rewrite rule
     *
     * The new rule will then be added between group 1 and group 3.
     *
     * @param string $pattern
     * @param string $newRewriteRule
     * @param string $configurationFileContent
     *
     * @return string The updated webserver configuration
     */
    protected function performBackendRoutingRewriteRulesUpdate(
        string $pattern,
        string $newRewriteRule,
        string $configurationFileContent
    ): string {
        return (string)preg_replace_callback(
            $pattern,
            static function ($matches) use ($newRewriteRule) {
                return str_replace($matches[2], '', ($matches[1] . $newRewriteRule)) . $matches[3];
            },
            $configurationFileContent,
            1
        );
    }

    protected function isApache(): bool
    {
        return strpos($this->webServer, 'Apache') === 0;
    }

    protected function isMicrosoftIis(): bool
    {
        return strpos($this->webServer, 'Microsoft-IIS') === 0;
    }

    protected function getWebServer(): string
    {
        return $_SERVER['SERVER_SOFTWARE'] ?? '';
    }
}
