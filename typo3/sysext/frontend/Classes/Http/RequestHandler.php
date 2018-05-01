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

use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\FrontendEditing\FrontendEditingController;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageGenerator;
use TYPO3\CMS\Frontend\Utility\CompressionUtility;
use TYPO3\CMS\Frontend\View\AdminPanelView;

/**
 * This is the main entry point of the TypoScript driven standard front-end
 *
 * Basically put, this is the script which all requests for TYPO3 delivered pages goes to in the
 * frontend (the website). The script instantiates a $TSFE object, includes libraries and does a little logic here
 * and there in order to instantiate the right classes to create the webpage.
 * Previously, this was called index_ts.php and also included the logic for the lightweight "eID" concept,
 * which is now handled in a separate request handler (EidRequestHandler).
 */
class RequestHandler implements RequestHandlerInterface
{
    /**
     * Instance of the current TYPO3 bootstrap
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Instance of the timetracker
     * @var TimeTracker
     */
    protected $timeTracker;

    /**
     * Instance of the TSFE object
     * @var TypoScriptFrontendController
     */
    protected $controller;

    /**
     * The request handed over
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * Constructor handing over the bootstrap and the original request
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Handles a frontend request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function handleRequest(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $response = null;
        $this->request = $request;
        $this->initializeTimeTracker();

        // Hook to preprocess the current request:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'] as $hookFunction) {
                $hookParameters = [];
                GeneralUtility::callUserFunction($hookFunction, $hookParameters, $hookParameters);
            }
            unset($hookFunction);
            unset($hookParameters);
        }

        $this->initializeController();

        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_force']
            && !GeneralUtility::cmpIP(
                GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
            )
        ) {
            $this->controller->pageUnavailableAndExit('This page is temporarily unavailable.');
        }

        $this->controller->connectToDB();
        $this->controller->sendRedirect();

        // Output compression
        // Remove any output produced until now
        $this->bootstrap->endOutputBufferingAndCleanPreviousOutput();
        $this->initializeOutputCompression();

        $this->bootstrap->loadBaseTca();

        // Initializing the Frontend User
        $this->timeTracker->push('Front End user initialized', '');
        $this->controller->initFEuser();
        $this->timeTracker->pull();

        // Initializing a possible logged-in Backend User
        /** @var $GLOBALS['BE_USER'] \TYPO3\CMS\Backend\FrontendBackendUserAuthentication */
        $GLOBALS['BE_USER'] = $this->controller->initializeBackendUser();

        // Process the ID, type and other parameters.
        // After this point we have an array, $page in TSFE, which is the page-record
        // of the current page, $id.
        $this->timeTracker->push('Process ID', '');
        // Initialize admin panel since simulation settings are required here:
        if ($this->controller->isBackendUserLoggedIn()) {
            $GLOBALS['BE_USER']->initializeAdminPanel();
            $this->bootstrap
                    ->initializeBackendRouter()
                    ->loadExtTables();
        }
        $this->controller->checkAlternativeIdMethods();
        $this->controller->clear_preview();
        $this->controller->determineId();

        // Now, if there is a backend user logged in and he has NO access to this page,
        // then re-evaluate the id shown! _GP('ADMCMD_noBeUser') is placed here because
        // \TYPO3\CMS\Version\Hook\PreviewHook might need to know if a backend user is logged in.
        if (
            $this->controller->isBackendUserLoggedIn()
            && (!$GLOBALS['BE_USER']->extPageReadAccess($this->controller->page) || GeneralUtility::_GP('ADMCMD_noBeUser'))
        ) {
            // Remove user
            unset($GLOBALS['BE_USER']);
            $this->controller->beUserLogin = false;
            // Re-evaluate the page-id.
            $this->controller->checkAlternativeIdMethods();
            $this->controller->clear_preview();
            $this->controller->determineId();
        }

        $this->controller->makeCacheHash();
        $this->timeTracker->pull();

        // Admin Panel & Frontend editing
        if ($this->controller->isBackendUserLoggedIn()) {
            $GLOBALS['BE_USER']->initializeFrontendEdit();
            if ($GLOBALS['BE_USER']->adminPanel instanceof AdminPanelView) {
                $this->bootstrap->initializeLanguageObject();
            }
            if ($GLOBALS['BE_USER']->frontendEdit instanceof FrontendEditingController) {
                $GLOBALS['BE_USER']->frontendEdit->initConfigOptions();
            }
        }

        // Starts the template
        $this->timeTracker->push('Start Template', '');
        $this->controller->initTemplate();
        $this->timeTracker->pull();
        // Get from cache
        $this->timeTracker->push('Get Page from cache', '');
        // Locks may be acquired here
        $this->controller->getFromCache();
        $this->timeTracker->pull();
        // Get config if not already gotten
        // After this, we should have a valid config-array ready
        $this->controller->getConfigArray();
        // Setting language and locale
        $this->timeTracker->push('Setting language and locale', '');
        $this->controller->settingLanguage();
        $this->controller->settingLocale();
        $this->timeTracker->pull();

        // Convert POST data to utf-8 for internal processing if metaCharset is different
        $this->controller->convPOSTCharset();

        $this->controller->initializeRedirectUrlHandlers();

        $this->controller->handleDataSubmission();

        // Check for shortcut page and redirect
        $this->controller->checkPageForShortcutRedirect();
        $this->controller->checkPageForMountpointRedirect();

        // Generate page
        $this->controller->setUrlIdToken();
        if ($this->controller->isGeneratePage()) {
            $this->timeTracker->push('Page generation');
            $this->controller->generatePage_preProcessing();
            $temp_theScript = $this->controller->generatePage_whichScript();
            if ($temp_theScript) {
                include $temp_theScript;
            } else {
                $this->controller->preparePageContentGeneration();
                // Content generation
                PageGenerator::renderContent();
                $this->controller->setAbsRefPrefix();
            }
            $this->controller->generatePage_postProcessing();
            $this->timeTracker->pull();
        }
        $this->controller->releaseLocks();

        // Render non-cached page parts by replacing placeholders which are taken from cache or added during page generation
        if ($this->controller->isINTincScript()) {
            if (!$this->controller->isGeneratePage()) {
                // When page was generated, this was already called. Avoid calling this twice.
                $this->controller->preparePageContentGeneration();
            }
            $this->timeTracker->push('Non-cached objects');
            $this->controller->INTincScript();
            $this->timeTracker->pull();
        }

        // Output content
        $sendTSFEContent = false;
        if ($this->controller->isOutputting()) {
            $this->timeTracker->push('Print Content', '');
            $this->controller->processOutput();
            $sendTSFEContent = true;
            $this->timeTracker->pull();
        }
        // Store session data for fe_users
        $this->controller->storeSessionData();
        // Statistics
        $GLOBALS['TYPO3_MISC']['microtime_end'] = microtime(true);
        if ($this->controller->isOutputting()) {
            if (isset($this->controller->config['config']['debug'])) {
                $debugParseTime = (bool)$this->controller->config['config']['debug'];
            } else {
                $debugParseTime = !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
            }
            if ($debugParseTime) {
                $this->controller->content .= LF . '<!-- Parsetime: ' . $this->timeTracker->getParseTime() . 'ms -->';
            }
        }
        $this->controller->redirectToExternalUrl();
        // Preview info
        $this->controller->previewInfo();
        // Hook for end-of-frontend
        $this->controller->hook_eofe();
        // Finish timetracking
        $this->timeTracker->pull();

        // Admin panel
        if ($this->controller->isBackendUserLoggedIn() && $GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication) {
            if ($GLOBALS['BE_USER']->isAdminPanelVisible()) {
                $this->controller->content = str_ireplace('</body>', $GLOBALS['BE_USER']->displayAdminPanel() . '</body>', $this->controller->content);
            }
        }

        if ($sendTSFEContent) {
            /** @var \TYPO3\CMS\Core\Http\Response $response */
            $response = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\Response::class);
            // Send content-length header.
            // Notice that all HTML content outside the length of the content-length header will be cut off!
            // Therefore content of unknown length from included PHP-scripts and if admin users are logged
            // in (admin panel might show...) or if debug mode is turned on, we disable it!
            if (
                (!isset($this->controller->config['config']['enableContentLengthHeader']) || $this->controller->config['config']['enableContentLengthHeader'])
                && !$this->controller->beUserLogin && !$GLOBALS['TYPO3_CONF_VARS']['FE']['debug']
                && !$this->controller->config['config']['debug'] && !$this->controller->doWorkspacePreview()
            ) {
                header('Content-Length: ' . strlen($this->controller->content));
            }
            $response->getBody()->write($this->controller->content);
        }
        // Debugging Output
        if (isset($GLOBALS['error']) && is_object($GLOBALS['error']) && @is_callable([$GLOBALS['error'], 'debugOutput'])) {
            $GLOBALS['error']->debugOutput();
        }
        GeneralUtility::devLog('END of FRONTEND session', 'cms', 0, ['_FLUSH' => true]);
        return $response;
    }

    /**
     * This request handler can handle any frontend request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return bool If the request is not an eID request, TRUE otherwise FALSE
     */
    public function canHandleRequest(\Psr\Http\Message\ServerRequestInterface $request)
    {
        return $request->getQueryParams()['eID'] || $request->getParsedBody()['eID'] ? false : true;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority()
    {
        return 50;
    }

    /**
     * Initializes output compression when enabled, could be split up and put into Bootstrap
     * at a later point
     */
    protected function initializeOutputCompression()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel'] && extension_loaded('zlib')) {
            if (MathUtility::canBeInterpretedAsInteger($GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel'])) {
                @ini_set('zlib.output_compression_level', $GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']);
            }
            ob_start([GeneralUtility::makeInstance(CompressionUtility::class), 'compressionOutputHandler']);
        }
    }

    /**
     * Timetracking started depending if a Backend User is logged in
     */
    protected function initializeTimeTracker()
    {
        $configuredCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName']) ?: 'be_typo_user';

        /** @var TimeTracker timeTracker */
        $this->timeTracker = GeneralUtility::makeInstance(TimeTracker::class, ($this->request->getCookieParams()[$configuredCookieName] ? true : false));
        $this->timeTracker->start();
    }

    /**
     * Creates an instance of TSFE and sets it as a global variable
     */
    protected function initializeController()
    {
        $this->controller = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            null,
            GeneralUtility::_GP('id'),
            GeneralUtility::_GP('type'),
            GeneralUtility::_GP('no_cache'),
            GeneralUtility::_GP('cHash'),
            null,
            GeneralUtility::_GP('MP'),
            GeneralUtility::_GP('RDCT')
        );
        // setting the global variable for the controller
        // We have to define this as reference here, because there is code around
        // which exchanges the TSFE object in the global variable. The reference ensures
        // that the $controller member always works on the same object as the global variable.
        // This is a dirty workaround and bypasses the protected access modifier of the controller member.
        $GLOBALS['TSFE'] = &$this->controller;
    }
}
