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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Menus;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper which returns an option tag.
 * This ViewHelper only works in conjunction with :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\Menus\ActionMenuViewHelper`.
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
 * Selectbox with the options "Overview", "Create new Blog" and "List Posts".
 *
 * Localized::
 *
 *    <f:be.menus.actionMenu>
 *       <f:be.menus.actionMenuItem label="{f:translate(key='overview')}" controller="Blog" action="index" />
 *       <f:be.menus.actionMenuItem label="{f:translate(key='create_blog')}" controller="Blog" action="new" />
 *    </f:be.menus.actionMenu>
 *
 * Localized selectbox.
 */
class ActionMenuItemViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'option';

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('label', 'string', 'label of the option tag', true);
        $this->registerArgument('controller', 'string', 'controller to be associated with this ActionMenuItem', true);
        $this->registerArgument('action', 'string', 'the action to be associated with this ActionMenuItem', true);
        $this->registerArgument('arguments', 'array', 'additional controller arguments to be passed to the action when this ActionMenuItem is selected', false, []);
    }

    /**
     * Renders an ActionMenu option tag
     *
     * @return string the rendered option tag
     * @see \TYPO3\CMS\Fluid\ViewHelpers\Be\Menus\ActionMenuViewHelper
     */
    public function render()
    {
        $label = $this->arguments['label'];
        $controller = $this->arguments['controller'];
        $action = $this->arguments['action'];
        $arguments = $this->arguments['arguments'];

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($this->renderingContext->getRequest());

        $uri = $uriBuilder->reset()->uriFor($action, $arguments, $controller);
        $this->tag->addAttribute('value', $uri);

        if (!$this->tag->hasAttribute('selected')) {
            $this->evaluateSelectItemState($controller, $action, $arguments);
        }

        $this->tag->setContent(
            // Double encode can be set to true, once the typo3fluid/fluid fix is released and required
            htmlspecialchars($label, ENT_QUOTES, '', false)
        );
        return $this->tag->render();
    }

    protected function evaluateSelectItemState(string $controller, string $action, array $arguments): void
    {
        $currentRequest = $this->renderingContext->getRequest();
        $flatRequestArguments = ArrayUtility::flattenPlain(
            array_merge([
                'controller' => $currentRequest->getControllerName(),
                'action' => $currentRequest->getControllerActionName(),
            ], $currentRequest->getArguments())
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
