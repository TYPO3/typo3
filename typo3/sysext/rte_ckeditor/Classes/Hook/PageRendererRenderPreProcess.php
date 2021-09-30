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

namespace TYPO3\CMS\RteCKEditor\Hook;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * PageRenderer hook to add require js configuration for backend calls
 *
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
final class PageRendererRenderPreProcess
{
    public function addRequireJsConfiguration(array $params, PageRenderer $pageRenderer): void
    {
        // @todo: Add an event to PageRenderer for registration of RequireJS configuration, see #93236
        if ($pageRenderer->getApplicationType() === 'BE') {
            $pageRenderer->addRequireJsConfiguration([
                'shim' => [
                    'ckeditor' => ['exports' => 'CKEDITOR'],
                ],
                'paths' => [
                    'ckeditor' => PathUtility::getAbsoluteWebPath(
                        ExtensionManagementUtility::extPath('rte_ckeditor', 'Resources/Public/JavaScript/Contrib/')
                    ) . 'ckeditor',
                ],
            ]);
        }
    }
}
