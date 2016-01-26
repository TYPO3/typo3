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
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * <f:flashMessages />
 * </code>
 * <output>
 * <ul class="typo3-messages">
 * <li class="alert alert-ok">
 * <h4>Some Message Header</h4>
 * Some message body
 * </li>
 * <li class="alert alert-notice">
 * Some notice message without header
 * </li>
 * </ul>
 * </output>
 * <code title="Output flash messages as a description list">
 * <f:flashMessages as="flashMessages">
 * 	<dl class="messages">
 * 	<f:for each="{flashMessages}" as="flashMessage">
 * 		<dt>{flashMessage.code}</dt>
 * 		<dd>{flashMessage.message}</dd>
 * 	</f:for>
 * 	</dl>
 * </f:flashMessages>
 * </code>
 * <output>
 * <dl class="messages">
 * 	<dt>1013</dt>
 * 	<dd>Some Warning Message.</dd>
 * </dl>
 * </output>
 *
 * <code title="Using a specific queue">
 * <f:flashMessages queueIdentifier="myQueue" />
 * </code>
 *
 * @api
 */
class FlashMessagesViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
    const RENDER_MODE_UL = 'ul';
    const RENDER_MODE_DIV = 'div';

    /**
     * @var string
     */
    protected $tagName = 'ul';

    /**
     * Initialize arguments
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerArgument('queueIdentifier', 'string', 'Flash-message queue to use', false);
    }

    /**
     * Renders FlashMessages and flushes the FlashMessage queue
     * Note: This disables the current page cache in order to prevent FlashMessage output
     * from being cached.
     *
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::no_cache
     * @param string $renderMode @deprecated since TYPO3 CMS 7.3. If you need custom output, use <f:flashMessages as="messages"><f:for each="messages" as="message">...</f:for></f:flashMessages>
     * @param string $as The name of the current flashMessage variable for rendering inside
     * @return string rendered Flash Messages, if there are any.
     * @api
     */
    public function render($renderMode = null, $as = null)
    {
        $queueIdentifier = isset($this->arguments['queueIdentifier']) ? $this->arguments['queueIdentifier'] : null;
        $flashMessages = $this->controllerContext->getFlashMessageQueue($queueIdentifier)->getAllMessagesAndFlush();
        if ($flashMessages === null || count($flashMessages) === 0) {
            return '';
        }
        if ($renderMode !== null) {
            GeneralUtility::deprecationLog('renderMode has been deprecated in TYPO3 CMS 7.3 and will be removed in TYPO3 CMS 8');
            if ($renderMode === self::RENDER_MODE_DIV) {
                $content = $this->renderDiv($flashMessages);
            } else {
                $content = $this->renderAsList($flashMessages);
            }
        } else {
            if ($as === null) {
                $content = $this->renderAsList($flashMessages);
            } else {
                $content = $this->renderFromTemplate($flashMessages, $as);
            }
        }

        return $content;
    }

    /**
     * Renders the flash messages as unordered list
     *
     * @param array $flashMessages \TYPO3\CMS\Core\Messaging\FlashMessage[]
     * @return string
     */
    protected function renderAsList(array $flashMessages)
    {
        $flashMessagesClass = $this->hasArgument('class') ? $this->arguments['class'] : 'typo3-messages';
        $tagContent = '';
        $this->tag->addAttribute('class', $flashMessagesClass);
        /** @var $singleFlashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
        foreach ($flashMessages as $singleFlashMessage) {
            $severityClass = sprintf('alert %s', $singleFlashMessage->getClass());
            $messageContent = htmlspecialchars($singleFlashMessage->getMessage());
            if ($singleFlashMessage->getTitle() !== '') {
                $messageContent = sprintf('<h4>%s</h4>', htmlspecialchars($singleFlashMessage->getTitle())) . $messageContent;
            }
            $tagContent .= sprintf('<li class="%s">%s</li>', htmlspecialchars($severityClass), $messageContent);
        }
        $this->tag->setContent($tagContent);
        return $this->tag->render();
    }

    /**
     * Renders the flash messages as nested divs
     * Defer the rendering of Flash Messages to the template. In this case,
     * the flash messages are stored in the template inside the variable specified
     * in "as".
     *
     * @param array $flashMessages \TYPO3\CMS\Core\Messaging\FlashMessage[]
     * @param string $as
     * @return string
     */
    protected function renderFromTemplate(array $flashMessages, $as)
    {
        $templateVariableContainer = $this->renderingContext->getTemplateVariableContainer();
        $templateVariableContainer->add($as, $flashMessages);
        $content = $this->renderChildren();
        $templateVariableContainer->remove($as);

        return $content;
    }

    /**
     * Renders the flash messages as nested divs
     *
     * @deprecated in 7.3 will be removed in 8.0
     * @param array $flashMessages \TYPO3\CMS\Core\Messaging\FlashMessage[]
     * @return string
     */
    protected function renderDiv(array $flashMessages)
    {
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
