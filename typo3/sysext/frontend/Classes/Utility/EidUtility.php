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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Tools for scripts using the eID feature of the TYPO3 Frontend.
 * Included from \TYPO3\CMS\Frontend\Http\RequestHandler.
 * Since scripts using the eID feature does not
 * have a full FE environment initialized by default
 * this class seeks to provide functions that can
 * initialize parts of the FE environment as needed,
 * eg. Frontend User session, Database connection etc.
 */
class EidUtility
{
    /**
     * Load and initialize Frontend User. Note, this process is slow because
     * it creates a calls many objects. Call this method only if necessary!
     *
     * @return FrontendUserAuthentication Frontend User object (usually known as TSFE->fe_user)
     */
    public static function initFeUser()
    {
        // Get TSFE instance. It knows how to initialize the user. We also
        // need TCA because services may need extra tables!
        self::initTCA();
        /** @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
        $tsfe = self::getTSFE();
        $tsfe->initFEuser();
        // Return FE user object:
        return $tsfe->fe_user;
    }

    /**
     * Initializes $GLOBALS['LANG'] for use in eID scripts.
     *
     * @param string $language TYPO3 language code
     */
    public static function initLanguage($language = 'default')
    {
        if (!is_object($GLOBALS['LANG'])) {
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
            $GLOBALS['LANG']->init($language);
        }
    }

    /**
     * Makes TCA available inside eID
     */
    public static function initTCA()
    {
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
     */
    public static function initExtensionTCA($extensionKey)
    {
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
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController New instance of \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    private static function getTSFE()
    {
        // Cached instance
        static $tsfe = null;
        if (is_null($tsfe)) {
            $tsfe = GeneralUtility::makeInstance(TypoScriptFrontendController::class, null, 0, 0);
        }
        return $tsfe;
    }
}
