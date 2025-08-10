<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Fluid\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3Fluid\Fluid\Core\Variables\ScopedVariableProvider;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper which renders the flash messages (output messages / message bubbles, which can also
 * be queued from a preceding request). No output occurs if no flash messages are
 * queued. Output is done with a hard-coded HTML definition, but the raw contents can be
 * extracted via the `as` attribute, and rendered with custom formatting.
 *
 * ```
 *   <f:flashMessages />
 *
 *   <f:flashMessages as="flashMessages">
 *        <dl class="messages">
 *           <f:for each="{flashMessages}" as="flashMessage">
 *              <dt>{flashMessage.code}</dt>
 *              <dd>{flashMessage.message}</dd>
 *           </f:for>
 *        </dl>
 *   </f:flashMessages>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-flashmessages
 */
final class FlashMessagesViewHelper extends AbstractViewHelper
{
    /**
     * ViewHelper outputs HTML therefore output escaping has to be disabled
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly FlashMessageService $flashMessageService,
        private readonly FlashMessageRendererResolver $flashMessageRendererResolver
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('queueIdentifier', 'string', 'Flash-message queue to use');
        $this->registerArgument('as', 'string', 'The name of the current flashMessage variable for rendering inside');
    }

    /**
     * Renders FlashMessages and flushes the FlashMessage queue
     *
     * Note: This does not disable the current page cache in order to prevent FlashMessage output
     *       from being cached.
     *       In case of conditional flash message rendering, caching must be disabled
     *       (e.g. for a controller action).
     *       Custom caching using the Caching Framework can be used in this case.
     */
    public function render(): string
    {
        $as = $this->arguments['as'];
        $queueIdentifier = $this->arguments['queueIdentifier'];
        if ($queueIdentifier === null) {
            if (!$this->renderingContext->hasAttribute(ServerRequestInterface::class)
                || !$this->renderingContext->getAttribute(ServerRequestInterface::class) instanceof RequestInterface
            ) {
                // Throw if not an extbase request
                throw new \RuntimeException(
                    'ViewHelper f:flashMessages needs an extbase Request object to resolve the Queue identifier magically.'
                    . ' When not in extbase context, set attribute "queueIdentifier".',
                    1639821269
                );
            }
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
            $extensionService = GeneralUtility::makeInstance(ExtensionService::class);
            $pluginNamespace = $extensionService->getPluginNamespace($request->getControllerExtensionName(), $request->getPluginName());
            $queueIdentifier = 'extbase.flashmessages.' . $pluginNamespace;
        }
        $flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier($queueIdentifier);
        $flashMessages = $flashMessageQueue->getAllMessagesAndFlush();
        if (count($flashMessages) === 0) {
            return '';
        }
        if ($as === null) {
            return $this->flashMessageRendererResolver->resolve()->render($flashMessages);
        }
        $variableProvider = new ScopedVariableProvider($this->renderingContext->getVariableProvider(), new StandardVariableProvider([$as => $flashMessages]));
        $this->renderingContext->setVariableProvider($variableProvider);
        $content = (string)$this->renderChildren();
        $this->renderingContext->setVariableProvider($variableProvider->getGlobalVariableProvider());
        return $content;
    }
}
