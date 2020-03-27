<?php
namespace TYPO3\CMS\Core\ViewHelpers;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Displays icon identified by icon identifier.
 *
 * Examples
 * ========
 *
 * Default::
 *
 *    <core:icon identifier="actions-menu" />
 *
 * Output::
 *
 *     <span class="t3js-icon icon icon-size-small icon-state-default icon-actions-menu" data-identifier="actions-menu">
 *         <span class="icon-markup">
 *             <img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-menu.svg" width="16" height="16">
 *         </span>
 *     </span>
 *
 * Inline::
 *
 *    <core:icon identifier="actions-menu" alternativeMarkupIdentifier="inline" />
 *
 * Output::
 *
 *     <span class="t3js-icon icon icon-size-small icon-state-default icon-actions-menu" data-identifier="actions-menu">
 *         <span class="icon-markup">
 *             <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g class="icon-color"><path d="M9 12v2H7v-2h2m.5-1h-3c-.3 0-.5.2-.5.5v3c0 .3.2.5.5.5h3c.3 0 .5-.2.5-.5v-3c0-.3-.2-.5-.5-.5zM9 7v2H7V7h2m.5-1h-3c-.3 0-.5.2-.5.5v3c0 .3.2.5.5.5h3c.3 0 .5-.2.5-.5v-3c0-.3-.2-.5-.5-.5zM9 2v2H7V2h2m.5-1h-3c-.3 0-.5.2-.5.5v3c0 .3.2.5.5.5h3c.3 0 .5-.2.5-.5v-3c0-.3-.2-.5-.5-.5zM4 7v2H2V7h2m.5-1h-3c-.3 0-.5.2-.5.5v3c0 .3.2.5.5.5h3c.3 0 .5-.2.5-.5v-3c0-.3-.2-.5-.5-.5zM4 2v2H2V2h2m.5-1h-3c-.3 0-.5.2-.5.5v3c0 .3.2.5.5.5h3c.3 0 .5-.2.5-.5v-3c0-.3-.2-.5-.5-.5zM4 12v2H2v-2h2m.5-1h-3c-.3 0-.5.2-.5.5v3c0 .3.2.5.5.5h3c.3 0 .5-.2.5-.5v-3c0-.3-.2-.5-.5-.5zM14 7v2h-2V7h2m.5-1h-3c-.3 0-.5.2-.5.5v3c0 .3.2.5.5.5h3c.3 0 .5-.2.5-.5v-3c0-.3-.2-.5-.5-.5zM14 2v2h-2V2h2m.5-1h-3c-.3 0-.5.2-.5.5v3c0 .3.2.5.5.5h3c.3 0 .5-.2.5-.5v-3c0-.3-.2-.5-.5-.5zM14 12v2h-2v-2h2m.5-1h-3c-.3 0-.5.2-.5.5v3c0 .3.2.5.5.5h3c.3 0 .5-.2.5-.5v-3c0-.3-.2-.5-.5-.5z"/></g></svg>
 *         </span>
 *     </span>
 */
class IconViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * ViewHelper returns HTML, thus we need to disable output escaping
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('identifier', 'string', 'Identifier of the icon as registered in the Icon Registry.', true);
        $this->registerArgument('size', 'string', 'Desired size of the icon. All values of the Icons.sizes enum are allowed, these are: "small", "default", "large" and "overlay".', false, Icon::SIZE_SMALL);
        $this->registerArgument('overlay', 'string', 'Identifier of an overlay icon as registered in the Icon Registry.', false, null);
        $this->registerArgument('state', 'string', 'Sets the state of the icon. All values of the Icons.states enum are allowed, these are: "default" and "disabled".', false, IconState::STATE_DEFAULT);
        $this->registerArgument('alternativeMarkupIdentifier', 'string', 'Alternative icon identifier. Takes precedence over the identifier if supported by the IconProvider.', false, null);
    }

    /**
     * Prints icon html for $identifier key
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $identifier = $arguments['identifier'];
        $size = $arguments['size'];
        $overlay = $arguments['overlay'];
        $state = IconState::cast($arguments['state']);
        $alternativeMarkupIdentifier = $arguments['alternativeMarkupIdentifier'];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return $iconFactory->getIcon($identifier, $size, $overlay, $state)->render($alternativeMarkupIdentifier);
    }
}
