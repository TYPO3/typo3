<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
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
 *  ...
 * </ul>
 * </output>
 *
 * <code title="TYPO3 core style">
 * <f:flashMessages renderMode="div" />
 * </code>
 * <output>
 * <div class="typo3-messages">
 *   <div class="typo3-message message-ok">
 *     <div class="message-header">Some Message Header</div>
 *     <div class="message-body">Some message body</div>
 *   </div>
 *   <div class="typo3-message message-notice">
 *     <div class="message-body">Some notice message without header</div>
 *   </div>
 * </div>
 * </output>
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Tx_Fluid_ViewHelpers_FlashMessagesViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper {

	const RENDER_MODE_UL = 'ul';
	const RENDER_MODE_DIV = 'div';

	/**
	 * Initialize arguments
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Render method.
	 *
	 * @param string $renderMode one of the RENDER_MODE_* constants
	 * @return string rendered Flash Messages, if there are any.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function render($renderMode = self::RENDER_MODE_UL) {
		$flashMessages = $this->controllerContext->getFlashMessageContainer()->getAllMessagesAndFlush();
		if ($flashMessages === NULL || count($flashMessages) === 0) {
			return '';
		}
		switch ($renderMode) {
			case self::RENDER_MODE_UL:
				return $this->renderUl($flashMessages);
			case self::RENDER_MODE_DIV:
				return $this->renderDiv($flashMessages);
			default:
				throw new Tx_Fluid_Core_ViewHelper_Exception('Invalid render mode "' . $renderMode . '" passed to FlashMessageViewhelper', 1290697924);
		}
	}

	/**
	 * Renders the flash messages as unordered list
	 *
	 * @param array $flashMessages array<t3lib_FlashMessage>
	 * @return string
	 */
	protected function renderUl(array $flashMessages) {
		$this->tag->setTagName('ul');
		if ($this->arguments->hasArgument('class')) {
			$this->tag->addAttribute('class', $this->arguments['class']);
		}
		$tagContent = '';
		foreach ($flashMessages as $singleFlashMessage) {
			$tagContent .= '<li>' . htmlspecialchars($singleFlashMessage->getMessage()) . '</li>';
		}
		$this->tag->setContent($tagContent);
		return $this->tag->render();
	}

	/*
	 * Renders the flash messages as nested divs
	 *
	 * @param array $flashMessages array<t3lib_FlashMessage>
	 * @return string
	 */
	protected function renderDiv(array $flashMessages) {
		$this->tag->setTagName('div');
		if ($this->arguments->hasArgument('class')) {
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
