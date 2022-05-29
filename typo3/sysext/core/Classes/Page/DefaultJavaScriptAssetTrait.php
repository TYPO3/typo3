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

namespace TYPO3\CMS\Core\Page;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Trait used to add default JavaScript in frontend rendering context
 * while considering TypoScript setting `config.removeDefaultJS` as well.
 *
 * @internal only to be used in EXT:frontend and TYPO3 Core, not part of TYPO3 Core API.
 */
trait DefaultJavaScriptAssetTrait
{
    protected string $defaultFrontendJavaScriptFile = 'EXT:frontend/Resources/Public/JavaScript/default_frontend.js';
    protected function addDefaultFrontendJavaScript(): void
    {
        // `config.removeDefaultJS = 1` - remove default JavaScript, no action required
        if ($this->shallRemoveDefaultFrontendJavaScript()) {
            return;
        }
        $filePath = $this->defaultFrontendJavaScriptFile;
        $collector = GeneralUtility::makeInstance(AssetCollector::class);
        // `config.removeDefaultJS = external` - persist JavaScript to `typo3temp/assets/`
        if ($this->shallExportDefaultFrontendJavaScript()) {
            $source = file_get_contents(GeneralUtility::getFileAbsFileName($filePath));
            $filePath = GeneralUtility::writeJavaScriptContentToTemporaryFile((string)$source);
        }
        $collector->addJavaScript('frontend-default', $filePath, ['async' => 'async']);
    }

    protected function shallRemoveDefaultFrontendJavaScript(): bool
    {
        /** @var ?TypoScriptFrontendController $frontendController */
        $frontendController = $GLOBALS['TSFE'] ?? null;
        return ($frontendController->config['config']['removeDefaultJS'] ?? '') === '1';
    }

    protected function shallExportDefaultFrontendJavaScript(): bool
    {
        /** @var ?TypoScriptFrontendController $frontendController */
        $frontendController = $GLOBALS['TSFE'] ?? null;
        return ($frontendController->config['config']['removeDefaultJS'] ?? '') === 'external';
    }
}
