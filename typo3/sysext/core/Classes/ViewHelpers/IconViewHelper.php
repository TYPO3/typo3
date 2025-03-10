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

namespace TYPO3\CMS\Core\ViewHelpers;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to display an icon identified by its icon identifier.
 *
 * ```
 *    <core:icon title="Open actions menu" identifier="actions-menu" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-core-icon
 */
final class IconViewHelper extends AbstractViewHelper
{
    /**
     * ViewHelper returns HTML, thus we need to disable output escaping
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('identifier', 'string', 'Identifier of the icon as registered in the Icon Registry.', true);
        $this->registerArgument('size', 'string', 'Desired size of the icon. All values of the IconSize enum are allowed, these are: "small", "default", "medium", "large" and "mega".', false, IconSize::SMALL->value);
        $this->registerArgument('overlay', 'string', 'Identifier of an overlay icon as registered in the Icon Registry.');
        $this->registerArgument('state', 'string', 'Sets the state of the icon. All values of the Icons.states enum are allowed, these are: "default" and "disabled".', false, IconState::STATE_DEFAULT->value);
        $this->registerArgument('alternativeMarkupIdentifier', 'string', 'Alternative icon identifier. Takes precedence over the identifier if supported by the IconProvider.');
        $this->registerArgument('title', 'string', 'Title for the icon');
    }

    /**
     * Prints icon html for $identifier key
     */
    public function render(): string
    {
        $identifier = $this->arguments['identifier'];
        $size = IconSize::from($this->arguments['size']);
        $overlay = $this->arguments['overlay'];
        $state = IconState::tryFrom($this->arguments['state']);
        $alternativeMarkupIdentifier = $this->arguments['alternativeMarkupIdentifier'];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon = $iconFactory->getIcon($identifier, $size, $overlay, $state);
        if ($this->arguments['title'] ?? false) {
            $icon->setTitle($this->arguments['title']);
        }
        return $icon->render($alternativeMarkupIdentifier);
    }
}
