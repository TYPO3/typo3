<?php
namespace TYPO3\CMS\Frontend\Http;

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
 * This interface needs to be implemented by all classes that register for the hook in:
 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlHandlers']
 *
 * It can be used to do custom URL processing during a Frontend request.
 * @deprecated since TYPO3 v9.3, will be removed in TYPO3 v10.0 in favor of PSR-15 middlewares.
 */
interface UrlHandlerInterface
{
    /**
     * Return TRUE if this hook handles the current URL.
     * Warning! If TRUE is returned content rendering will be disabled!
     * This method will be called in the constructor of the TypoScriptFrontendController
     *
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::__construct()
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::initializeRedirectUrlHandlers()
     * @return bool
     */
    public function canHandleCurrentUrl();

    /**
     * Custom processing of the current URL.
     *
     * If canHandle() has returned TRUE this method needs to take care of redirecting the user or generating custom output.
     * This hook will be called BEFORE the user is redirected to an external URL configured in the page properties.
     *
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::redirectToExternalUrl()
     */
    public function handle();
}
