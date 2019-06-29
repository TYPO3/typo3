<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Hooks;

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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Uses frontend hooks to show preview information
 * @internal this is a concrete TYPO3 hook implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
class FrontendHooks
{
    /**
     * Include the preview block in case we're looking at a hidden page
     * in the LIVE workspace
     *
     * @param array $params
     * @param TypoScriptFrontendController $controller
     */
    public function displayPreviewInfoMessage($params, TypoScriptFrontendController $controller)
    {
        if (!$controller->fePreview || $controller->doWorkspacePreview() || ($controller->config['config']['disablePreviewNotification'] ?? false)) {
            return;
        }
        if ($controller->config['config']['message_preview']) {
            $message = $controller->config['config']['message_preview'];
        } else {
            $label = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_tsfe.xlf:preview');
            $styles = [];
            $styles[] = 'position: fixed';
            $styles[] = 'top: 15px';
            $styles[] = 'right: 15px';
            $styles[] = 'padding: 8px 18px';
            $styles[] = 'background: #fff3cd';
            $styles[] = 'border: 1px solid #ffeeba';
            $styles[] = 'font-family: sans-serif';
            $styles[] = 'font-size: 14px';
            $styles[] = 'font-weight: bold';
            $styles[] = 'color: #856404';
            $styles[] = 'z-index: 20000';
            $styles[] = 'user-select: none';
            $styles[] = 'pointer-events: none';
            $styles[] = 'text-align: center';
            $styles[] = 'border-radius: 2px';
            $message = '<div id="typo3-preview-info" style="' . implode(';', $styles) . '">' . htmlspecialchars($label) . '</div>';
        }
        if (!empty($message)) {
            $controller->content = str_ireplace('</body>', $message . '</body>', $controller->content);
        }
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
