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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for rendering a styled content infobox markup.
 *
 * States
 * ======
 *
 * The Infobox provides different context sensitive states that
 * can be used to provide an additional visual feedback to the
 * to the user to underline the meaning of the information.
 *
 * Possible values are in range from ``-2`` to ``2``. Please choose a
 * meaningful value from the following list.
 *
 * ``-2``
 *    Notices (Default)
 * ``-1``
 *    Information
 * ``0``
 *    Positive feedback
 * ``1``
 *    Warnings
 * ``2``
 *    Error
 *
 * Examples
 * ========
 *
 * Simple::
 *
 *    <f:be.infobox title="Message title">your box content</f:be.infobox>
 *
 * All options::
 *
 *    <f:be.infobox title="Message title" message="your box content" state="{f:constant(name: 'TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper::STATE_NOTICE')}" iconName="check" disableIcon="true" />
 */
final class InfoboxViewHelper extends AbstractViewHelper
{
    public const STATE_NOTICE = -2;
    public const STATE_INFO = -1;
    public const STATE_OK = 0;
    public const STATE_WARNING = 1;
    public const STATE_ERROR = 2;

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('message', 'string', 'The message of the info box, if NULL tag content is used');
        $this->registerArgument('title', 'string', 'The title of the info box');
        $this->registerArgument('state', 'int', 'The state of the box, InfoboxViewHelper::STATE_*', false, self::STATE_NOTICE);
        $this->registerArgument('iconName', 'string', 'Identifier of the icon as registered in the Icon Registry. NULL sets default icon');
        $this->registerArgument('disableIcon', 'bool', 'If set to TRUE, the icon is not rendered.', false, false);
    }

    public function render(): string
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $title = (string)$this->arguments['title'];
        $message = (string)$this->renderChildren();
        $state = $this->arguments['state'];
        $isInRange = MathUtility::isIntegerInRange($state, -2, 2);
        if (!$isInRange) {
            $state = -2;
        }
        $severity = ContextualFeedbackSeverity::from($state);
        $disableIcon = $this->arguments['disableIcon'];
        $icon = $this->arguments['iconName'] ?? $severity->getIconIdentifier();
        $iconTemplate = '';
        if (!$disableIcon) {
            $iconTemplate = '' .
                '<div class="callout-icon">' .
                    '<span class="icon-emphasized">' .
                        $iconFactory->getIcon($icon, IconSize::SMALL)->render() .
                    '</span>' .
                '</div>';
        }
        $titleTemplate = '';
        if ($title !== null) {
            $titleTemplate = '<div class="callout-title">' . htmlspecialchars($title) . '</div>';
        }
        return '<div class="callout callout-' . htmlspecialchars($severity->getCssClass()) . '">' .
                $iconTemplate .
                '<div class="callout-content">' .
                    $titleTemplate .
                    '<div class="callout-body">' . $message . '</div>' .
                '</div>' .
            '</div>';
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'message';
    }
}
