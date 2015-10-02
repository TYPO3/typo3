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
use TYPO3\CMS\Recordlist\Controller\LinkBrowserController;

/**
 * Link handler for email links
 */
class MailLinkHandler extends AbstractLinkHandler implements LinkHandlerInterface {

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
	protected $updateSupported = FALSE;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		// remove unsupported link attribute
		unset($this->linkAttributes[array_search('target', $this->linkAttributes, TRUE)]);
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
	public function canHandleLink(array $linkParts) {
		if ($linkParts['url'] && strpos($linkParts['url'], '@')) {
			$this->linkParts = $linkParts;
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Format the current link for HTML output
	 *
	 * @return string
	 */
	public function formatCurrentUrl() {
		return $this->linkParts['url'];
	}

	/**
	 * Render the link handler
	 *
	 * @param ServerRequestInterface $request
	 *
	 * @return string
	 */
	public function render(ServerRequestInterface $request) {
		GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Recordlist/MailLinkHandler');

		$lang = $this->getLanguageService();
		$extUrl = '
			<!--
				Enter mail address:
			-->
			<form action="" id="lmailform">
				<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkMail">
					<tr>
						<td style="width: 96px;">' . $lang->getLL('emailAddress', TRUE) . ':</td>
						<td>
							<input type="text" name="lemail" size="20" value="'
								. htmlspecialchars(!empty($this->linkParts) ? $this->linkParts['url'] : '')
								. '" />
							<input class="btn btn-default" type="submit" value="' . $lang->getLL('setLink', TRUE) . '" />
						</td>
					</tr>
				</table>
			</form>';
		return $extUrl;
	}

	/**
	 * @return string[] Array of body-tag attributes
	 */
	public function getBodyTagAttributes() {
		return [];
	}

}
