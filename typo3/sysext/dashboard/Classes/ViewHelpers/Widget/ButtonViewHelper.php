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

namespace TYPO3\CMS\Dashboard\ViewHelpers\Widget;

use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\ElementAttributesInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * @internal
 *
 * Renders a dashboard button
 *
 * Examples
 * ========
 *
 * ::
 *
 *    <dashboard:widget.button button="{button}" class="widget-cta">
 *        {f:translate(id: button.title, default: button.title)}
 *    </dashboard:widget.button button="{button}" class="widget-cta">
 */
class ButtonViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('button', ButtonProviderInterface::class, 'Dashboard widget button', true);
    }

    public function render(): string
    {
        $button = $this->arguments['button'];

        $this->tag->addAttribute('href', $button->getLink() ?: '#');

        $target = $button->getTarget();
        if ($target !== '') {
            $this->tag->addAttribute('target', $target);
            if ($target === '_blank') {
                $this->tag->addAttribute('rel', 'noreferrer');
            }
        }

        if ($button instanceof ElementAttributesInterface) {
            $this->tag->addAttributes($button->getElementAttributes());
        }

        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
