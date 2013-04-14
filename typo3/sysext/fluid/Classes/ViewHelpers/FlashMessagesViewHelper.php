<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * View helper which renders the flash messages (if there are any) as an unsorted list.
 *
 * In case you need custom Flash Message HTML output, please write your own ViewHelper for the moment.
 *
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:flashMessages />
 * </code>
 * <output>
 * An ul-list of flash messages.
 * </output>
 *
 * <code title="Output with custom css class">
 * <f:flashMessages class="specialClass" />
 * </code>
 * <output>
 * <ul class="specialClass">
 * ...
 * </ul>
 * </output>
 *
 * <code title="TYPO3 core style">
 * <f:flashMessages renderMode="div" />
 * </code>
 * <output>
 * <div class="typo3-messages">
 * <div class="typo3-message message-ok">
 * <div class="message-header">Some Message Header</div>
 * <div class="message-body">Some message body</div>
 * </div>
 * <div class="typo3-message message-notice">
 * <div class="message-body">Some notice message without header</div>
 * </div>
 * </div>
 * </output>
 *
 * @api
 */
class FlashMessagesViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	const RENDER_MODE_UL = 'ul';
	const RENDER_MODE_DIV = 'div';

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $contentObject;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
		$this->contentObject = $this->configurationManager->getContentObject();
	}

	/**
	 * Initialize arguments
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Renders FlashMessages and flushes the FlashMessage queue
	 * Note: This disables the current page cache in order to prevent FlashMessage output
	 * from being cached.
	 *
	 * @see tslib_fe::no_cache
	 * @param string $renderMode one of the RENDER_MODE_* constants
	 * @return string rendered Flash Messages, if there are any.
	 * @api
	 */
	public function render($renderMode = self::RENDER_MODE_UL) {
		$flashMessages = $this->controllerContext->getFlashMessageQueue()->getAllMessagesAndFlush();
		if ($flashMessages === NULL || count($flashMessages) === 0) {
			return '';
		}
		if (isset($GLOBALS['TSFE']) && $this->contentObject->getUserObjectType() === \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER) {
			$GLOBALS['TSFE']->no_cache = 1;
		}
		switch ($renderMode) {
			case self::RENDER_MODE_UL:
				return $this->renderUl($flashMessages);
			case self::RENDER_MODE_DIV:
				return $this->renderDiv($flashMessages);
			default:
				throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('Invalid render mode "' . $renderMode . '" passed to FlashMessageViewhelper', 1290697924);
		}
	}

	/**
	 * Renders the flash messages as unordered list
	 *
	 * @param array $flashMessages array<\TYPO3\CMS\Core\Messaging\FlashMessage>
	 * @return string
	 */
	protected function renderUl(array $flashMessages) {
		$this->tag->setTagName('ul');
		if ($this->hasArgument('class')) {
			$this->tag->addAttribute('class', $this->arguments['class']);
		}
		$tagContent = '';
		foreach ($flashMessages as $singleFlashMessage) {
			$tagContent .= '<li>' . htmlspecialchars($singleFlashMessage->getMessage()) . '</li>';
		}
		$this->tag->setContent($tagContent);
		return $this->tag->render();
	}

	/**
	 * Renders the flash messages as nested divs
	 *
	 * @param array $flashMessages array<\TYPO3\CMS\Core\Messaging\FlashMessage>
	 * @return string
	 */
	protected function renderDiv(array $flashMessages) {
		$this->tag->setTagName('div');
		if ($this->hasArgument('class')) {
			$this->tag->addAttribute('class', $this->arguments['class']);
		} else {
			$this->tag->addAttribute('class', 'typo3-messages');
		}
		$tagContent = '';
		foreach ($flashMessages as $singleFlashMessage) {
			$tagContent .= $singleFlashMessage->render();
		}
		$this->tag->setContent($tagContent);
		return $this->tag->render();
	}
}

?>