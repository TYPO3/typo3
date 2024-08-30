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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\ViewHelpers;

use TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders the values of a form
 *
 * Scope: frontend
 */
final class RenderAllFormValuesViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('renderable', RootRenderableInterface::class, 'A RootRenderableInterface instance', true);
        $this->registerArgument('as', 'string', 'The name within the template', false, 'formValue');
    }

    /**
     * Return array element by key.
     */
    public function render(): string
    {
        $renderable = $this->arguments['renderable'];
        if ($renderable instanceof CompositeRenderableInterface) {
            $elements = $renderable->getRenderablesRecursively();
        } else {
            $elements = [$renderable];
        }
        $as = $this->arguments['as'];
        $output = '';
        foreach ($elements as $element) {
            $output .= $this->renderingContext->getViewHelperInvoker()->invoke(
                RenderFormValueViewHelper::class,
                [
                    'renderable' => $element,
                    'as' => $as,
                ],
                $this->renderingContext,
                $this->renderChildren(...),
            );
        }
        return $output;
    }
}
