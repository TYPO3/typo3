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

namespace TYPO3\CMS\Extbase\ContentObject;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Contains EXTBASEPLUGIN class object.
 *
 * Creates a request and dispatches it to the controller which was specified
 * by TS Setup and returns the content, currently handed over to the
 * Extbase Bootstrap.
 *
 * This class is the main entry point for extbase extensions in the TYPO3 Frontend.
 */
class ExtbasePluginContentObject extends AbstractContentObject
{
    public function render($conf = [])
    {
        $extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $extbaseBootstrap->setContentObjectRenderer($this->getContentObjectRenderer());
        if ($this->cObj->getUserObjectType() === false) {
            // Come here only if we are not called from $TSFE->processNonCacheableContentPartsAndSubstituteContentMarkers()!
            $this->cObj->setUserObjectType(ContentObjectRenderer::OBJECTTYPE_USER);
        }
        $request = $extbaseBootstrap->initialize($conf, $this->request);
        $content = $extbaseBootstrap->handleFrontendRequest($request);
        // Rendering is deferred, as the action should not be cached, we pump this now to TSFE to be executed later-on
        if ($this->cObj->doConvertToUserIntObject) {
            $this->cObj->doConvertToUserIntObject = false;
            // @todo: this should be removed in the future in TSFE to allow more "uncacheables" than USER_INTs
            // also, the handleFrontendRequest() should return the full response in the future
            $conf['userFunc'] = Bootstrap::class . '->run';
            $this->cObj->setUserObjectType(ContentObjectRenderer::OBJECTTYPE_USER_INT);
            $tsfe = $this->getTypoScriptFrontendController();
            $substKey = 'INT_SCRIPT.' . $tsfe->uniqueHash();
            $content = '<!--' . $substKey . '-->';
            $tsfe->config['INTincScript'][$substKey] = [
                'conf' => $conf,
                'cObj' => serialize($this->cObj),
                'type' => 'FUNC',
            ];
        } elseif (isset($conf['stdWrap.'])) {
            // Only executed when the element is not converted to USER_INT
            $content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
        }
        $this->cObj->setUserObjectType(false);
        return $content;
    }
}
