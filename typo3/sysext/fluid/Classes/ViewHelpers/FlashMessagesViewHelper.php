<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

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
 * <div class="specialClass">
 * ...
 * </ul>
 * </output>
 *
 * <code title="TYPO3 core style">
 * <f:flashMessages />
 * </code>
 * <output>
 * <div class="typo3-messages">
 *  <div class="alert alert-info">
 *      <div class="media">
 *          <div class="media-left">
 *              <span class="fa-stack fa-lg">
 *                  <i class="fa fa-circle fa-stack-2x"></i>
 *                  <i class="fa fa-info fa-stack-1x"></i>
 *              </span>
 *          </div>
 *          <div class="media-body">
 *              <h4 class="alert-title">Info - Title for Info message</h4>
 *              <p class="alert-message">Message text here.</p>
 *          </div>
 *      </div>
 *  </div>
 * </div>
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
    /**
     * @var string
     */
    protected $tagName = 'div';

    /**
     * Initialize arguments
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('queueIdentifier', 'string', 'Flash-message queue to use', false);
    }

    /**
     * Renders FlashMessages and flushes the FlashMessage queue
     * Note: This disables the current page cache in order to prevent FlashMessage output
     * from being cached.
     *
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::no_cache
     * @param string $as The name of the current flashMessage variable for rendering inside
     * @return string rendered Flash Messages, if there are any.
     * @api
     */
    public function render($as = null)
    {
        $queueIdentifier = isset($this->arguments['queueIdentifier']) ? $this->arguments['queueIdentifier'] : null;
        $flashMessages = $this->renderingContext->getControllerContext()->getFlashMessageQueue($queueIdentifier)->getAllMessagesAndFlush();
        if ($flashMessages === null || count($flashMessages) === 0) {
            return '';
        }

        if ($as === null) {
            $content = $this->renderDefault($flashMessages);
        } else {
            $content = $this->renderFromTemplate($flashMessages, $as);
        }

        return $content;
    }

    /**
     * Renders the flash messages as unordered list
     *
     * @param array $flashMessages \TYPO3\CMS\Core\Messaging\FlashMessage[]
     * @return string
     */
    protected function renderDefault(array $flashMessages)
    {
        $flashMessagesClass = $this->hasArgument('class') ? $this->arguments['class'] : 'typo3-messages';
        $tagContent = '';
        $this->tag->addAttribute('class', $flashMessagesClass);
        /** @var $singleFlashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
        foreach ($flashMessages as $singleFlashMessage) {
            $tagContent .= $singleFlashMessage->getMessageAsMarkup();
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
}
