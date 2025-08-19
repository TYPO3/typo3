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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper which returns an option tag within a `<f:be.menus.actionMenu>` group.
 *
 * ```
 *  <f:be.menus.actionMenu>
 *      <f:be.menus.actionMenuItem label="First Menu" controller="Default" action="index" />
 *      <f:be.menus.actionMenuItemGroup label="Information">
 *          <f:be.menus.actionMenuItem label="PHP Information" controller="Information" action="listPhpInfo" />
 *          <f:be.menus.actionMenuItem label="{f:translate(key:'documentation')}" controller="Information" action="documentation" />
 *          ...
 *      </f:be.menus.actionMenuItemGroup>
 *  </f:be.menus.actionMenu>
 * ```
 *
 * **Note:** This ViewHelper is experimental and tailored to be used only in extbase context.
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-be-menus-actionmenuitem
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
        if (!$this->renderingContext->hasAttribute(ServerRequestInterface::class)
            || !$this->renderingContext->getAttribute(ServerRequestInterface::class) instanceof RequestInterface) {
            // Throw if not an extbase request
            throw new \RuntimeException(
                'ViewHelper f:be.menus.actionMenuItem needs an extbase Request object to create URIs.',
                1639741792
            );
        }
        /** @var RequestInterface $request */
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $uriBuilder->setRequest($request);

        $uri = $uriBuilder->reset()->uriFor($action, $arguments, $controller);
        $this->tag->addAttribute('value', $uri);

        if (!$this->tag->hasAttribute('selected')) {
            $this->evaluateSelectItemState($controller, $action, $arguments);
        }

        $this->tag->setContent(htmlspecialchars($label, ENT_QUOTES, '', true));
        return $this->tag->render();
    }

    private function evaluateSelectItemState(string $controller, string $action, array $arguments): void
    {
        /** @var RequestInterface $request */
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
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
