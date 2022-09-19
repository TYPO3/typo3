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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Menus;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper which returns an option tag.
 * This ViewHelper only works in conjunction with :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\Menus\ActionMenuViewHelper`.
 * This ViewHelper is tailored to be used only in extbase context.
 *
 * .. note::
 *    This ViewHelper is experimental!
 *
 * Examples
 * ========
 *
 * Simple::
 *
 *    <f:be.menus.actionMenu>
 *       <f:be.menus.actionMenuItem label="Overview" controller="Blog" action="index" />
 *       <f:be.menus.actionMenuItem label="Create new Blog" controller="Blog" action="new" />
 *       <f:be.menus.actionMenuItem label="List Posts" controller="Post" action="index" arguments="{blog: blog}" />
 *    </f:be.menus.actionMenu>
 *
 * Select box with the options "Overview", "Create new Blog" and "List Posts".
 *
 * Localized::
 *
 *    <f:be.menus.actionMenu>
 *       <f:be.menus.actionMenuItem label="{f:translate(key='overview')}" controller="Blog" action="index" />
 *       <f:be.menus.actionMenuItem label="{f:translate(key='create_blog')}" controller="Blog" action="new" />
 *    </f:be.menus.actionMenu>
 *
 * Localized select box.
 */
final class ActionMenuItemViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'option';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('label', 'string', 'label of the option tag', true);
        $this->registerArgument('controller', 'string', 'controller to be associated with this ActionMenuItem', true);
        $this->registerArgument('action', 'string', 'the action to be associated with this ActionMenuItem', true);
        $this->registerArgument('arguments', 'array', 'additional controller arguments to be passed to the action when this ActionMenuItem is selected', false, []);
    }

    public function render(): string
    {
        $label = $this->arguments['label'];
        $controller = $this->arguments['controller'];
        $action = $this->arguments['action'];
        $arguments = $this->arguments['arguments'];

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        $request = $renderingContext->getRequest();
        if (!$request instanceof RequestInterface) {
            // Throw if not an extbase request
            throw new \RuntimeException(
                'ViewHelper f:be.menus.actionMenuItem needs an extbase Request object to create URIs.',
                1639741792
            );
        }
        $uriBuilder->setRequest($request);

        $uri = $uriBuilder->reset()->uriFor($action, $arguments, $controller);
        $this->tag->addAttribute('value', $uri);

        if (!$this->tag->hasAttribute('selected')) {
            $this->evaluateSelectItemState($controller, $action, $arguments);
        }

        $this->tag->setContent(htmlspecialchars($label, ENT_QUOTES, '', true));
        return $this->tag->render();
    }

    protected function evaluateSelectItemState(string $controller, string $action, array $arguments): void
    {
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        /** @var RequestInterface $request */
        $request = $renderingContext->getRequest();
        $flatRequestArguments = ArrayUtility::flattenPlain(
            array_merge([
                'controller' => $request->getControllerName(),
                'action' => $request->getControllerActionName(),
            ], $request->getArguments())
        );
        $flatViewHelperArguments = ArrayUtility::flattenPlain(
            array_merge(['controller' => $controller, 'action' => $action], $arguments)
        );
        if (
            ($this->arguments['selected'] ?? false) ||
            array_diff($flatRequestArguments, $flatViewHelperArguments) === []
        ) {
            $this->tag->addAttribute('selected', 'selected');
        }
    }
}
