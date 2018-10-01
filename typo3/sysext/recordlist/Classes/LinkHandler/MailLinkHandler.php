<?php
namespace TYPO3\CMS\Recordlist\LinkHandler;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Link handler for email links
 * @internal This class is a specific LinkHandler implementation and is not part of the TYPO3's Core API.
 */
class MailLinkHandler extends AbstractLinkHandler implements LinkHandlerInterface
{
    /**
     * Parts of the current link
     *
     * @var array
     */
    protected $linkParts = [];

    /**
     * We don't support updates since there is no difference to simply set the link again.
     *
     * @var bool
     */
    protected $updateSupported = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        // remove unsupported link attributes
        foreach (['target', 'rel'] as $attribute) {
            $position = array_search($attribute, $this->linkAttributes, true);
            if ($position !== false) {
                unset($this->linkAttributes[$position]);
            }
        }
    }

    /**
     * Checks if this is the handler for the given link
     *
     * The handler may store this information locally for later usage.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     *
     * @return bool
     */
    public function canHandleLink(array $linkParts)
    {
        if (isset($linkParts['url']['email'])) {
            $this->linkParts = $linkParts;
            return true;
        }
        return false;
    }

    /**
     * Format the current link for HTML output
     *
     * @return string
     */
    public function formatCurrentUrl()
    {
        return $this->linkParts['url']['email'];
    }

    /**
     * Render the link handler
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function render(ServerRequestInterface $request)
    {
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Recordlist/MailLinkHandler');

        $this->view->assign('email', !empty($this->linkParts) ? $this->linkParts['url']['email'] : '');
        return $this->view->render('Mail');
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        return [];
    }
}
