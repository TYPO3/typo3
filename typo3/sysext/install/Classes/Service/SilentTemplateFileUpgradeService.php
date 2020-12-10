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

use TYPO3\CMS\Install\Service\Exception\TemplateFileChangedException;

/**
 * Execute "silent" upgrades for folder structure template files, if needed.
 *
 * Since the content of the template files may changed over time this class
 * performs the necessary content changes in those files already present in
 * the installation. It is called by the layout controller at an early point.
 *
 * Every change is encapsulated in one method and must throw a
 * TemplateFileChangedException if its content was updated.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class SilentTemplateFileUpgradeService
{
    protected WebServerConfigurationFileService $webServerConfigurationFileService;

    public function __construct(WebServerConfigurationFileService $webServerConfigurationFileService)
    {
        $this->webServerConfigurationFileService = $webServerConfigurationFileService;
    }

    /**
     * Executed content changes. Single upgrade methods must throw a
     * TemplateFileChangedException if content of the file was updated.
     *
     * @throws TemplateFileChangedException
     */
    public function execute(): void
    {
        $this->addBackendRoutingRewriteRules();
    }

    /**
     * @throws TemplateFileChangedException
     */
    protected function addBackendRoutingRewriteRules(): void
    {
        $changed = $this->webServerConfigurationFileService->addWebServerSpecificBackendRoutingRewriteRules();

        if ($changed) {
            $this->throwTemplateFileChangedException();
        }
    }

    /**
     * Throw exception after template file content change to trigger a redirect.
     *
     * @throws TemplateFileChangedException
     */
    protected function throwTemplateFileChangedException(): void
    {
        throw new TemplateFileChangedException(
            'Template file updated, reload needed',
            1608286894
        );
    }
}
