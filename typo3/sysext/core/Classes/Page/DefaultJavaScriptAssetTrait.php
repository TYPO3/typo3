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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait used to add default JavaScript in frontend rendering context
 * while considering TypoScript setting `config.removeDefaultJS` as well.
 *
 * @internal only to be used in EXT:frontend and TYPO3 Core, not part of TYPO3 Core API.
 */
trait DefaultJavaScriptAssetTrait
{
    protected function addDefaultFrontendJavaScript(ServerRequestInterface $request): void
    {
        // `config.removeDefaultJS = 1` - remove default JavaScript, no action required
        if ($this->shallRemoveDefaultFrontendJavaScript($request)) {
            return;
        }
        $filePath = 'EXT:frontend/Resources/Public/JavaScript/default_frontend.js';
        $collector = GeneralUtility::makeInstance(AssetCollector::class);
        // `config.removeDefaultJS = external` - persist JavaScript to `typo3temp/assets/`
        if ($this->shallExportDefaultFrontendJavaScript($request)) {
            $source = file_get_contents(GeneralUtility::getFileAbsFileName($filePath));
            $filePath = GeneralUtility::writeJavaScriptContentToTemporaryFile((string)$source);
        }
        $collector->addJavaScript('frontend-default', $filePath, ['async' => 'async']);
    }

    protected function shallRemoveDefaultFrontendJavaScript(ServerRequestInterface $request): bool
    {
        $frontendTypoScriptConfigArray = $request->getAttribute('frontend.typoscript')?->getConfigArray();
        return ($frontendTypoScriptConfigArray['removeDefaultJS'] ?? 'external') === '1';
    }

    protected function shallExportDefaultFrontendJavaScript(ServerRequestInterface $request): bool
    {
        $frontendTypoScriptConfigArray = $request->getAttribute('frontend.typoscript')?->getConfigArray();
        return ($frontendTypoScriptConfigArray['removeDefaultJS'] ?? 'external') === 'external';
    }
}
