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
        if ($linkParts['url'] && strpos($linkParts['url'], '@')) {
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
        return $this->linkParts['url'];
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

        $lang = $this->getLanguageService();
        $mailAddress = '
            <!--
                Enter mail address:
            -->
            <div class="link-browser-section link-browser-tab-content-mail">
                <form action="" id="lmailform" class="form-horizontal">
                        <div class="form-group form-group-sm">
                            <label class="col-xs-4 control-label">' . htmlspecialchars($lang->getLL('emailAddress')) . ':</label>
                            <div class="col-xs-6">
                                <input type="text" name="lemail" size="20" class="form-control" value="'
                                    . htmlspecialchars(!empty($this->linkParts) ? $this->linkParts['url'] : '')
                                    . '" />
                            </div>
                            <div class="col-xs-2">
                                <input class="btn btn-default" type="submit" value="' . htmlspecialchars($lang->getLL('setLink')) . '" />
                            </div>
                        </div>
                </form>
            </div>';

        return $mailAddress;
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        return [];
    }
}
