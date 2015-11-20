<?php
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

use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Handles the redirection for external URL pages.
 */
class ExternalPageUrlHandler implements \TYPO3\CMS\Frontend\Http\UrlHandlerInterface
{
    /**
     * @var string
     */
    protected $externalUrl = '';

    /**
     * Checks if external URLs are enabled and if the current page points to an external URL.
     *
     * @return bool
     */
    public function canHandleCurrentUrl()
    {
        $tsfe = $this->getTypoScriptFrontendController();

        if (!empty($tsfe->config['config']['disablePageExternalUrl'])) {
            return false;
        }

        $this->externalUrl = $tsfe->sys_page->getExtURL($tsfe->page);
        if (empty($this->externalUrl)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Redirects the user to the detected external URL.
     *
     * @return void
     */
    public function handle()
    {
        HttpUtility::redirect($this->externalUrl, HttpUtility::HTTP_STATUS_303);
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
