<?php
namespace TYPO3\CMS\Frontend\Utility;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Tools for scripts using the eID feature of the TYPO3 Frontend.
 * Included from \TYPO3\CMS\Frontend\Http\RequestHandler.
 * Since scripts using the eID feature does not
 * have a full FE environment initialized by default
 * this class seeks to provide functions that can
 * initialize parts of the FE environment as needed,
 * eg. Frontend User session, Database connection etc.
 *
 * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0. Ensure to migrate your eID script to a PSR-15 middleware at the right position, where the frontend is boot up at the level you need.
 */
class EidUtility
{
    /**
     * Load and initialize Frontend User. Note, this process is slow because
     * it creates a calls many objects. Call this method only if necessary!
     *
     * @return FrontendUserAuthentication Frontend User object (usually known as TSFE->fe_user)
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0. Use the FrontendUserAuthenticator PSR-15 middleware to set up the Frontend User.
     */
    public static function initFeUser()
    {
        trigger_error('EidUtility::initFeUser() will be removed in TYPO3 v10.0. Use a PSR-15 middleware instead.', E_USER_DEPRECATED);
        // Get TSFE instance. It knows how to initialize the user.
        $tsfe = self::getTSFE();
        $tsfe->fe_user = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        // List of pid's acceptable
        $pid = GeneralUtility::_GP('pid');
        $tsfe->fe_user->checkPid_value = $pid ? implode(',', GeneralUtility::intExplode(',', $pid)) : 0;
        // Check if a session is transferred:
        if (GeneralUtility::_GP('FE_SESSION_KEY')) {
            $fe_sParts = explode('-', GeneralUtility::_GP('FE_SESSION_KEY'));
            // If the session key hash check is OK:
            if (md5($fe_sParts[0] . '/' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']) === (string)$fe_sParts[1]) {
                $cookieName = FrontendUserAuthentication::getCookieName();
                $_COOKIE[$cookieName] = $fe_sParts[0];
                if (isset($_SERVER['HTTP_COOKIE'])) {
                    // See http://forge.typo3.org/issues/27740
                    $_SERVER['HTTP_COOKIE'] .= ';' . $cookieName . '=' . $fe_sParts[0];
                }
                $tsfe->fe_user->forceSetCookie = true;
                $tsfe->fe_user->dontSetCookie = false;
                unset($cookieName);
            }
        }
        $tsfe->fe_user->start();
        $tsfe->fe_user->unpack_uc();

        // Call hook for possible manipulation of frontend user object
        $_params = ['pObj' => &$tsfe];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $tsfe);
        }
        // Return FE user object:
        return $tsfe->fe_user;
    }

    /**
     * Initializes $GLOBALS['LANG'] for use in eID scripts.
     *
     * @param string $language TYPO3 language code
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0. Instantiate the LanguageService by yourself instead.
     */
    public static function initLanguage($language = 'default')
    {
        trigger_error('EidUtility::initLanguage() will be removed in TYPO3 v10.0. Ensure to intantiate the LanguageService by yourself.', E_USER_DEPRECATED);
        if (!is_object($GLOBALS['LANG'])) {
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
            $GLOBALS['LANG']->init($language);
        }
    }

    /**
     * Makes TCA available inside eID
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0. Is not needed anymore within eID scripts as TCA is now available at any time
     */
    public static function initTCA()
    {
        trigger_error('EidUtility::initTCA() will be removed in TYPO3 v10.0. Is not needed anymore within eID scripts as TCA is now available at any time.', E_USER_DEPRECATED);
        // Some badly made extensions attempt to manipulate TCA in a wrong way
        // (inside ext_localconf.php). Therefore $GLOBALS['TCA'] may become an array
        // but in fact it is not loaded. The check below ensure that
        // TCA is still loaded if such bad extensions are installed
        if (!is_array($GLOBALS['TCA']) || !isset($GLOBALS['TCA']['pages'])) {
            ExtensionManagementUtility::loadBaseTca();
        }
    }

    /**
     * Makes TCA for the extension available inside eID. Use this function if
     * you need not to include the whole $GLOBALS['TCA'].
     *
     * @param string $extensionKey Extension key
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    public static function initExtensionTCA($extensionKey)
    {
        trigger_error('EidUtility::initExtensionTCA() will be removed in TYPO3 v10.0 as it is discouraged to only load ext_tables.php of one extension. Use ExtensionManagementUtility instead.', E_USER_DEPRECATED);
        $extTablesPath = ExtensionManagementUtility::extPath($extensionKey, 'ext_tables.php');
        if (file_exists($extTablesPath)) {
            $GLOBALS['_EXTKEY'] = $extensionKey;
            require_once $extTablesPath;
            // We do not need to save restore the value of $GLOBALS['_EXTKEY']
            // because it is not defined to anything real outside of
            // ext_tables.php or ext_localconf.php scope.
            unset($GLOBALS['_EXTKEY']);
        }
    }

    /**
     * Creating a single static cached instance of TSFE to use with this class.
     *
     * @return TypoScriptFrontendController
     */
    private static function getTSFE(): TypoScriptFrontendController
    {
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
        $tsfe = $runtimeCache->get('eidUtilityTsfe') ?: null;
        if ($tsfe === null) {
            $tsfe = GeneralUtility::makeInstance(TypoScriptFrontendController::class, null, 0, 0);
            $runtimeCache->set('eidUtilityTsfe', $tsfe);
        }
        return $tsfe;
    }
}
