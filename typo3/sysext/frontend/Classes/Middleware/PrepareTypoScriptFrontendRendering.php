<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as PsrRequestHandlerInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Initialization of TypoScriptFrontendController
 *
 * Do all necessary preparation steps for rendering
 *
 * @internal this middleware might get removed in TYPO3 v10.0.
 */
class PrepareTypoScriptFrontendRendering implements MiddlewareInterface
{
    /**
     * @var TypoScriptFrontendController
     */
    protected $controller;

    /**
     * @var TimeTracker
     */
    protected $timeTracker;

    public function __construct(TypoScriptFrontendController $controller = null, TimeTracker $timeTracker = null)
    {
        $this->controller = $controller ?: $GLOBALS['TSFE'];
        $this->timeTracker = $timeTracker ?: GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * Initialize TypoScriptFrontendController to the point right before rendering of the page is triggered
     *
     * @param ServerRequestInterface $request
     * @param PsrRequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, PsrRequestHandlerInterface $handler): ResponseInterface
    {
        // Get from cache
        $this->timeTracker->push('Get Page from cache');
        // Locks may be acquired here
        $this->controller->getFromCache();
        $this->timeTracker->pull();
        // Get config if not already gotten
        // After this, we should have a valid config-array ready
        $this->controller->getConfigArray();

        // Merge Query Parameters with config.defaultGetVars
        // This is done in getConfigArray as well, but does not override the current middleware request object
        // Since we want to stay in sync with this, the option needs to be set as well.
        $pageArguments = $request->getAttribute('routing');
        if (!empty($this->controller->config['config']['defaultGetVars.'] ?? null)) {
            $modifiedGetVars = GeneralUtility::removeDotsFromTS($this->controller->config['config']['defaultGetVars.']);
            if ($pageArguments instanceof PageArguments) {
                $pageArguments = $pageArguments->withQueryArguments($modifiedGetVars);
                $this->controller->setPageArguments($pageArguments);
                $request = $request->withAttribute('routing', $pageArguments);
            }
            if (!empty($request->getQueryParams())) {
                ArrayUtility::mergeRecursiveWithOverrule($modifiedGetVars, $request->getQueryParams());
            }
            $request = $request->withQueryParams($modifiedGetVars);
            $GLOBALS['TYPO3_REQUEST'] = $request;
        }

        // Setting language and locale
        $this->timeTracker->push('Setting language and locale');
        $this->controller->settingLanguage();
        $this->controller->settingLocale();
        $this->timeTracker->pull();

        // Convert POST data to utf-8 for internal processing if metaCharset is different
        if ($this->controller->metaCharset !== 'utf-8' && is_array($_POST) && !empty($_POST)) {
            $this->convertCharsetRecursivelyToUtf8($_POST, $this->controller->metaCharset);
            $GLOBALS['HTTP_POST_VARS'] = $_POST;
            $parsedBody = $request->getParsedBody();
            $this->convertCharsetRecursivelyToUtf8($parsedBody, $this->controller->metaCharset);
            $request = $request->withParsedBody($parsedBody);
            $GLOBALS['TYPO3_REQUEST'] = $request;
        }

        // @deprecated since TYPO3 v9.3, will be removed in TYPO3 v10.0
        $this->controller->initializeRedirectUrlHandlers(true);

        // Hook for processing data submission to extensions
        // This is done at this point, because we need the config values
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission'])) {
            trigger_error('The "checkDataSubmission" hook will be removed in TYPO3 v10.0 in favor of PSR-15. Use a middleware instead.', E_USER_DEPRECATED);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission'] as $className) {
                GeneralUtility::makeInstance($className)->checkDataSubmission($this->controller);
            }
        }

        return $handler->handle($request);
    }

    /**
     * Small helper function to convert charsets for arrays to UTF-8
     *
     * @param mixed $data given by reference (string/array usually)
     * @param string $fromCharset convert FROM this charset
     */
    protected function convertCharsetRecursivelyToUtf8(&$data, string $fromCharset)
    {
        foreach ($data as $key => $value) {
            if (is_array($data[$key])) {
                $this->convertCharsetRecursivelyToUtf8($data[$key], $fromCharset);
            } elseif (is_string($data[$key])) {
                $data[$key] = mb_convert_encoding($data[$key], 'utf-8', $fromCharset);
            }
        }
    }
}
