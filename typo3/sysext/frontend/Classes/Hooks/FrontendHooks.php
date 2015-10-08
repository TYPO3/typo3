<?php
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

/**
 * Uses frontend hooks to show preview informations
 */
class FrontendHooks
{
    /**
     * Include the preview block in cause we're looking at a hidden page
     * in the LIVE workspace
     *
     * @param array $params
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pObj
     * @return string
     */
    public function hook_previewInfo($params, $pObj)
    {
        if ($pObj->fePreview !== 1) {
            return '';
        }
        if ($pObj->config['config']['message_preview']) {
            $message = $pObj->config['config']['message_preview'];
        } else {
            $message = '<div id="typo3-previewInfo" style="position: absolute; top: 20px; right: 20px; border: 2px solid #000; padding: 5px 5px; background: #f00; font: 1em Verdana; color: #000; font-weight: bold; z-index: 10001">PREVIEW!</div>';
        }
        return $message;
    }
}
