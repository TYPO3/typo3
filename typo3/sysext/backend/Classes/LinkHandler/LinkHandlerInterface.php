<?php

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

namespace TYPO3\CMS\Backend\LinkHandler;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\AbstractLinkBrowserController;

/**
 * Interface for link handlers displayed in the LinkBrowser.
 *
 * Link handlers are used when the global "link" modal is rendered: When linking
 * an RTE text snipped to something, and for fields like "header_link" in table "tt_content".
 *
 * A link handler is a tab in the link modal.
 *
 * Link handlers are configured with page TSconfig TCEMAIN.linkHandler - each tab is a sub-key in this area.
 * The core configures a couple of default link handlers like linking to a page, a mail, telephone and similar.
 *
 * Link handlers create a TYPO3 specific URI prefixed with 't3://' managed by ext:core LinkHandling classes.
 * The frontend translates this to appropriate HTML using the ext:frontend Typolink classes.
 */
interface LinkHandlerInterface
{
    /**
     * @return array
     */
    public function getLinkAttributes();

    /**
     * @param string[] $fieldDefinitions Array of link attribute field definitions
     * @return string[]
     */
    public function modifyLinkAttributes(array $fieldDefinitions);

    /**
     * Initialize the handler
     *
     * @param string $identifier
     * @param array $configuration Page TSconfig of this link handler: TCEMAIN.linkHandler.<identifier>.configuration
     */
    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration);

    /**
     * Checks if this is the handler for the given link
     *
     * The handler may store this information locally for later usage.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     *
     * @return bool
     */
    public function canHandleLink(array $linkParts);

    /**
     * Format the current link for HTML output
     *
     * @return string
     */
    public function formatCurrentUrl();

    /**
     * Render the link handler. Ideally this modifies the view, but it can also render content directly.
     *
     *
     * @return string
     */
    public function render(ServerRequestInterface $request);

    /**
     * Return TRUE if the handler supports to update a link.
     *
     * This is useful for file or page links, when only attributes are changed.
     *
     * @return bool
     */
    public function isUpdateSupported();

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes();
}
