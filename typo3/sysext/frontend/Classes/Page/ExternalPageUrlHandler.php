<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Page;

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
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Handles the redirection for external URL pages.
 * @deprecated since TYPO3 v9.3, will be removed in TYPO3 v10.0. The functionality has been moved into a PSR-15 middleware.
 */
class ExternalPageUrlHandler implements \TYPO3\CMS\Frontend\Http\UrlHandlerInterface
{
    /**
     * @var string
     */
    protected $externalUrl = '';

    public function __construct()
    {
        trigger_error('ExternalPageUrlHandler has been moved into a PSR-15 middleware and will be removed in TYPO3 v10.0. In order to modify the external page redirection, use a PSR-15 middleware as well.', E_USER_DEPRECATED);
    }

    /**
     * Checks if external URLs are enabled and if the current page points to an external URL.
     *
     * @return bool
     */
    public function canHandleCurrentUrl(): bool
    {
        $tsfe = $this->getTypoScriptFrontendController();

        if (!empty($tsfe->config['config']['disablePageExternalUrl'])) {
            return false;
        }

        $this->externalUrl = $tsfe->sys_page->getExtURL($tsfe->page);
        if (empty($this->externalUrl)) {
            return false;
        }
        return true;
    }

    /**
     * Redirects the user to the detected external URL.
     */
    public function handle(): ResponseInterface
    {
        return new RedirectResponse($this->externalUrl, 303);
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
