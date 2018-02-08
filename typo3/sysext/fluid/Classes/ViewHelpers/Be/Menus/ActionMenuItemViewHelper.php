<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Menus;

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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * View helper which returns an option tag.
 * This view helper only works in conjunction with \TYPO3\CMS\Fluid\ViewHelpers\Be\Menus\ActionMenuViewHelper
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:be.menus.actionMenu>
 * <f:be.menus.actionMenuItem label="Overview" controller="Blog" action="index" />
 * <f:be.menus.actionMenuItem label="Create new Blog" controller="Blog" action="new" />
 * <f:be.menus.actionMenuItem label="List Posts" controller="Post" action="index" arguments="{blog: blog}" />
 * </f:be.menus.actionMenu>
 * </code>
 * <output>
 * Selectbox with the options "Overview", "Create new Blog" and "List Posts"
 * </output>
 *
 * <code title="Localized">
 * <f:be.menus.actionMenu>
 * <f:be.menus.actionMenuItem label="{f:translate(key='overview')}" controller="Blog" action="index" />
 * <f:be.menus.actionMenuItem label="{f:translate(key='create_blog')}" controller="Blog" action="new" />
 * </f:be.menus.actionMenu>
 * </code>
 * <output>
 * localized selectbox
 * <output>
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

        $uriBuilder = $this->renderingContext->getControllerContext()->getUriBuilder();
        $uri = $uriBuilder->reset()->uriFor($action, $arguments, $controller);
        $this->tag->addAttribute('value', $uri);
        $currentRequest = $this->renderingContext->getControllerContext()->getRequest();
        $currentController = $currentRequest->getControllerName();
        $currentAction = $currentRequest->getControllerActionName();
        if ($action === $currentAction && $controller === $currentController) {
            $this->tag->addAttribute('selected', 'selected');
        }
        $this->tag->setContent($label);
        return $this->tag->render();
    }
}
